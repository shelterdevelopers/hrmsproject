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
$pending_leaves = array_values(array_filter($pending_applications, function ($a) { return ($a['type'] ?? '') === 'leave'; }));
$pending_loans = array_values(array_filter($pending_applications, function ($a) { return ($a['type'] ?? '') === 'loan'; }));

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
                            <button class="tab-button active" onclick="showTab('leaves')">
                                <i class="fa fa-calendar"></i> Leave Applications (<?= count($pending_leaves) ?>)
                            </button>
                            <button class="tab-button" onclick="showTab('loans')">
                                <i class="fa fa-money"></i> Loan Applications (<?= count($pending_loans) ?>)
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
                                                <th>Employee</th>
                                                <th>Details</th>
                                                <th>Date Submitted</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_leaves as $app): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                                    <td>
                                                        <strong>Duration:</strong> <?= date('M d, Y', strtotime($app['start_date'])) ?> to <?= date('M d, Y', strtotime($app['end_date'])) ?><br>
                                                        <strong>Days:</strong> <?= $app['days_requested'] ?? 'N/A' ?><br>
                                                        <strong>Leave Type:</strong> <?= htmlspecialchars($app['leave_type'] ?? 'N/A') ?><br>
                                                        <strong>Reason:</strong> <?= htmlspecialchars($app['reason']) ?>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
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
                                                <th>Employee</th>
                                                <th>Details</th>
                                                <th>Date Submitted</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_loans as $app): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                                    <td>
                                                        <strong>Amount:</strong> $<?= number_format($app['amount'], 2) ?><br>
                                                        <strong>Repayment Period:</strong> <?= $app['repayment_plan'] ?> months<br>
                                                        <strong>Reason:</strong> <?= htmlspecialchars($app['reason']) ?>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
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
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Your Decision</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responded_applications as $app): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                        <td><span class="status-badge"><?= ucfirst($app['type']) ?></span></td>
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
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
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
