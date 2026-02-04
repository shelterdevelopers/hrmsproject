<?php
/**
 * Managing Director Approvals Page
 * MD can only approve applications, not apply for anything
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . "/../DB_connection.php";
require_once __DIR__ . "/Model/Attendance.php";
require_once __DIR__ . "/Model/RoleHelper.php";
require_once __DIR__ . "/Model/ApplicationWorkflow.php";
require_once __DIR__ . "/Model/Notification.php";

// Authentication - MD only
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php?error=Please login first");
    exit();
}

$is_managing_director = RoleHelper::is_managing_director($conn, $_SESSION['employee_id']);
if (!$is_managing_director) {
    header("Location: ../index.php?error=Access+denied.+This+page+is+for+Managing+Director+only");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$success = null;
$error = null;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_application'])) {
    if (empty($_POST['application_id']) || !isset($_POST['status'])) {
        $error = "Missing required information.";
    } else {
        $app_id = (int)$_POST['application_id'];
        $status = $_POST['status'];
        $comment = $_POST['comment'] ?? '';
        
        // Verify MD can approve this application
        $app = Attendance::get_application_by_id($conn, $app_id);
        if (!$app) {
            $error = "Application not found.";
        } else {
            // Check permissions
            $can_approve = false;
            if ($app['type'] === 'leave') {
                $can_approve = RoleHelper::can_approve_leave($conn, $employee_id, $app['employee_id']);
            } elseif ($app['type'] === 'loan') {
                $can_approve = RoleHelper::can_approve_loan($conn, $employee_id, $app_id);
            }
            
            if ($can_approve) {
                // Use ApplicationWorkflow for proper approval handling
                $result = ApplicationWorkflow::update_application(
                    $conn,
                    $app_id,
                    $employee_id,
                    $status,
                    $comment
                );
                
                if ($result['success']) {
                    $success = $result['message'] ?? "Application " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully.";
                } else {
                    $error = $result['message'] ?? "Failed to process application.";
                }
            } else {
                $error = "You are not authorized to approve this application.";
            }
        }
    }
}

// Pending applications – same source as dashboard (Attendance::get_md_pending_applications)
$pending_applications = Attendance::get_md_pending_applications($conn);
$pending_leaves = array_values(array_filter($pending_applications, function ($a) { return strtolower(trim($a['type'] ?? '')) === 'leave'; }));
$pending_loans = array_values(array_filter($pending_applications, function ($a) { return strtolower(trim($a['type'] ?? '')) === 'loan'; }));

// Debug: Log what we're getting
error_log("MD Approvals Page - Total pending: " . count($pending_applications));
error_log("MD Approvals Page - Pending leaves: " . count($pending_leaves));
error_log("MD Approvals Page - Pending loans: " . count($pending_loans));
if (count($pending_applications) > 0) {
    error_log("MD Approvals Page - Sample: " . json_encode(array_slice($pending_applications, 0, 2)));
}

// Debug: Check for ALL pending manager leave applications (regardless of HR approval)
$debug_all_mgr_leaves = "SELECT a.id, a.status, a.hr_approval_status, a.md_approval_status, 
                          e.role, e.first_name, e.last_name, e.department
                          FROM applications a 
                          JOIN employee e ON a.employee_id = e.employee_id
                          WHERE a.type = 'leave' 
                          AND a.status = 'pending'
                          AND (LOWER(TRIM(e.role)) LIKE '%manager%' OR LOWER(TRIM(e.role)) = 'manager')
                          AND LOWER(TRIM(e.role)) != 'hr_manager'
                          AND LOWER(TRIM(e.role)) != 'managing_director'
                          ORDER BY a.created_at DESC
                          LIMIT 20";
$debug_mgr_stmt = $conn->prepare($debug_all_mgr_leaves);
$debug_mgr_stmt->execute();
$debug_mgr_results = $debug_mgr_stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("Debug - All pending manager leaves: " . json_encode($debug_mgr_results));

// Debug: Check for Finance Manager loans directly
$debug_fm_loans = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department, 
                    a.hr_approval_status, a.md_approval_status, a.status
                    FROM applications a 
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'loan' 
                    AND a.status = 'pending'
                    AND a.hr_approval_status = 'approved'
                    AND (LOWER(TRIM(e.role)) = 'finance_manager' 
                         OR LOWER(TRIM(e.role)) LIKE '%finance%manager%'
                         OR (LOWER(TRIM(e.department)) = 'finance' AND LOWER(TRIM(e.role)) LIKE '%manager%'))";
$debug_stmt = $conn->prepare($debug_fm_loans);
$debug_stmt->execute();
$debug_results = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("Debug Finance Manager Loans: " . json_encode($debug_results));

// Debug: Log what we're getting (remove in production)
// error_log("MD Approvals - Pending Leaves: " . count($pending_leaves));
// error_log("MD Approvals - Pending Loans: " . count($pending_loans));
// error_log("MD Approvals - Total Pending: " . count($pending_applications));

// Get responded applications (applications MD has already approved/rejected)
$sql_responded = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                  FROM applications a
                  JOIN employee e ON a.employee_id = e.employee_id
                  WHERE a.status IN ('approved', 'denied')
                  AND (
                      (a.type = 'leave' AND (e.role = 'hr' OR e.role = 'hr_manager') AND a.manager_approval_status IN ('approved', 'denied'))
                      OR (a.type = 'leave' AND (e.role = 'manager' OR e.role LIKE '%_manager') AND e.role != 'hr_manager' AND a.md_approval_status IN ('approved', 'denied'))
                      OR (a.type = 'loan' AND e.department = 'Finance' AND (e.role = 'manager' OR e.role LIKE '%_manager') AND a.md_approval_status IN ('approved', 'denied'))
                  )
                  ORDER BY a.updated_at DESC
                  LIMIT 50";
$stmt_responded = $conn->prepare($sql_responded);
$stmt_responded->execute();
$responded_applications = $stmt_responded->fetchAll(PDO::FETCH_ASSOC) ?: [];

// End output buffering and clean any accidental output
if (ob_get_level() > 0) {
    ob_end_clean();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pending Approvals · Shelter HRMS</title>
    <?php include __DIR__ . '/../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/aesthetic-improvements.css">
    <style>
        .main-table .app-detail-cell { min-width: 220px; max-width: 400px; white-space: normal; word-break: break-word; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        /* Ensure active tab content is visible (critical: style.css sets .tab-content { display:none }) */
        .tab-content.active { display: block !important; }
        .tab-container .tab-content { padding: 20px; background: var(--white); }
        .tab-buttons .tab-button { cursor: pointer; user-select: none; }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include __DIR__ . "/../inc/header.php" ?>
    <div class="body">
        <?php include __DIR__ . "/../inc/nav.php" ?>
        <section class="section-1">
            <h2 class="title">Pending Application Approvals</h2>
            
            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (empty($pending_applications)): ?>
                <div class="no-records">
                    <i class="fa fa-check-circle"></i>
                    <p>No pending application approvals at this time.</p>
                </div>
            <?php else: ?>
                <div class="applications-container">
                    <h3>Leave/Loan Applications Pending Your Approval (<?= count($pending_applications) ?>)</h3>
                    
                    <!-- Tabs for Leaves and Loans -->
                    <div class="tab-container">
                        <div class="tab-buttons">
                            <button type="button" class="tab-button active" onclick="showTab(event, 'leaves')" aria-label="Leave applications">
                                <i class="fa fa-calendar" aria-hidden="true"></i>
                                <span>Leave Applications (<?= count($pending_leaves) ?>)</span>
                            </button>
                            <button type="button" class="tab-button" onclick="showTab(event, 'loans')" aria-label="Loan applications">
                                <i class="fa fa-money" aria-hidden="true"></i>
                                <span>Loan Applications (<?= count($pending_loans) ?>)</span>
                            </button>
                        </div>
                        
                        <!-- Leaves Tab -->
                        <div id="leaves-tab" class="tab-content active">
                            <?php if (empty($pending_leaves)): ?>
                                <div class="no-records">
                                    <i class="fa fa-check-circle"></i>
                                    <p>No pending leave applications</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="main-table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Employee</th>
                                                <th>Details</th>
                                                <th>Date Submitted</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_leaves as $app): 
                                                $start = $app['start_date'] ?? null;
                                                $end = $app['end_date'] ?? null;
                                                $days = $app['days_requested'] ?? 'N/A';
                                                $leaveType = $app['leave_type'] ?? 'N/A';
                                                $reason = $app['reason'] ?? '';
                                                $created = $app['created_at'] ?? '';
                                                $name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="status-badge" style="background: #2596be; color: white; padding: 5px 10px; border-radius: 4px;">
                                                            <i class="fa fa-calendar"></i> Leave
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($name ?: 'Employee #' . ($app['employee_id'] ?? '')) ?></td>
                                                    <td class="app-detail-cell">
                                                        <strong>Duration:</strong> <?= $start ? date('M d, Y', strtotime($start)) : 'N/A' ?> to <?= $end ? date('M d, Y', strtotime($end)) : 'N/A' ?><br>
                                                        <strong>Days:</strong> <?= htmlspecialchars((string)$days) ?><br>
                                                        <strong>Leave Type:</strong> <?= htmlspecialchars((string)$leaveType) ?><br>
                                                        <strong>Reason:</strong> <?= htmlspecialchars((string)$reason) ?>
                                                    </td>
                                                    <td><?= $created ? date('M d, Y', strtotime($created)) : '—' ?></td>
                                                    <td>
                                                        <?php if ($app['employee_role'] == 'hr' || $app['employee_role'] == 'hr_manager'): ?>
                                                            <span class="status-badge" style="background: #ffc107;">Awaiting MD Approval</span>
                                                        <?php elseif ($app['manager_approval_status'] == 'approved'): ?>
                                                            <span class="status-badge" style="background: #17a2b8;">Manager Approved → MD</span>
                                                        <?php else: ?>
                                                            <span class="status-badge" style="background: #ffc107;">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <select name="status" class="input-1" style="width: 120px; margin-bottom: 5px;">
                                                                <option value="pending">Pending</option>
                                                                <option value="approved">Approve</option>
                                                                <option value="denied">Deny</option>
                                                            </select>
                                                            <textarea name="comment" placeholder="Comment (optional)" class="input-1" rows="2" style="width: 200px; margin-bottom: 5px;"></textarea>
                                                            <button type="submit" name="respond_application" class="btn" style="width: 100%;">
                                                                <i class="fa fa-check"></i> Submit
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Loans Tab -->
                        <div id="loans-tab" class="tab-content">
                            <?php if (empty($pending_loans)): ?>
                                <div class="no-records">
                                    <i class="fa fa-check-circle"></i>
                                    <p>No pending loan applications</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="main-table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Employee</th>
                                                <th>Details</th>
                                                <th>Date Submitted</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_loans as $app): 
                                                $amount = isset($app['amount']) ? (float)$app['amount'] : 0;
                                                $repayment = $app['repayment_plan'] ?? 'N/A';
                                                $reasonLoan = $app['reason'] ?? '';
                                                $createdLoan = $app['created_at'] ?? '';
                                                $nameLoan = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="status-badge" style="background: #28a745; color: white; padding: 5px 10px; border-radius: 4px;">
                                                            <i class="fa fa-money"></i> Loan
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($nameLoan ?: 'Employee #' . ($app['employee_id'] ?? '')) ?></td>
                                                    <td class="app-detail-cell">
                                                        <strong>Amount:</strong> $<?= number_format($amount, 2) ?><br>
                                                        <strong>Repayment Period:</strong> <?= htmlspecialchars((string)$repayment) ?> months<br>
                                                        <strong>Reason:</strong> <?= htmlspecialchars((string)$reasonLoan) ?>
                                                    </td>
                                                    <td><?= $createdLoan ? date('M d, Y', strtotime($createdLoan)) : '—' ?></td>
                                                    <td>
                                                        <?php if ($app['hr_approval_status'] == 'approved'): ?>
                                                            <span class="status-badge" style="background: #17a2b8;">HR Approved → MD</span>
                                                        <?php else: ?>
                                                            <span class="status-badge" style="background: #ffc107;">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <select name="status" class="input-1" style="width: 120px; margin-bottom: 5px;">
                                                                <option value="pending">Pending</option>
                                                                <option value="approved">Approve</option>
                                                                <option value="denied">Deny</option>
                                                            </select>
                                                            <textarea name="comment" placeholder="Comment (optional)" class="input-1" rows="2" style="width: 200px; margin-bottom: 5px;"></textarea>
                                                            <button type="submit" name="respond_application" class="btn" style="width: 100%;">
                                                                <i class="fa fa-check"></i> Submit
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($responded_applications)): ?>
                <div class="applications-container" style="margin-top: 30px;">
                    <h3>Recently Responded Applications</h3>
                    
                    <div class="table-responsive">
                        <table class="main-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Details</th>
                                    <th>Your Decision</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responded_applications as $app): ?>
                                    <tr>
                                        <td>
                                            <?php if ($app['type'] == 'leave'): ?>
                                                <span class="status-badge" style="background: #2596be; color: white; padding: 5px 10px; border-radius: 4px;">
                                                    <i class="fa fa-calendar"></i> Leave
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background: #28a745; color: white; padding: 5px 10px; border-radius: 4px;">
                                                    <i class="fa fa-money"></i> Loan
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                        <td>
                                            <?php if ($app['type'] == 'leave'): ?>
                                                <?= date('M d', strtotime($app['start_date'])) ?> - <?= date('M d, Y', strtotime($app['end_date'])) ?>
                                                (<?= $app['days_requested'] ?? 'N/A' ?> days)
                                            <?php else: ?>
                                                $<?= number_format($app['amount'], 2) ?> (<?= $app['repayment_plan'] ?> months)
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $md_status = $app['md_approval_status'] ?? null;
                                            if ($md_status == 'approved'): 
                                            ?>
                                                <span class="status-badge" style="background: #28a745;">Approved</span>
                                            <?php elseif ($md_status == 'denied'): ?>
                                                <span class="status-badge" style="background: #dc3545;">Denied</span>
                                            <?php else: ?>
                                                <span class="status-badge"><?= ucfirst($app['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($app['updated_at'] ?? $app['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
        function showTab(ev, tabName) {
            ev = ev || window.event;
            var targetTab = document.getElementById(tabName + '-tab');
            var buttons = document.querySelectorAll('.tab-container .tab-button');
            if (!targetTab || !buttons.length) return;
            // Hide all tab contents
            document.querySelectorAll('.tab-container .tab-content').forEach(function(tab) {
                tab.classList.remove('active');
            });
            // Remove active from all buttons
            buttons.forEach(function(btn) { btn.classList.remove('active'); });
            // Show selected tab
            targetTab.classList.add('active');
            // Active class on the button that was clicked (use currentTarget so it's the button, not the icon)
            var btn = ev.currentTarget || ev.target;
            while (btn && !btn.classList.contains('tab-button')) btn = btn.parentElement;
            if (btn) btn.classList.add('active');
        }
    </script>
</body>
</html>
<?php
// Flush output
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
