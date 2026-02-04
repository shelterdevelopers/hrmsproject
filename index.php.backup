<?php
session_start();
if (!defined('BASE_URL')) {
    require_once 'config.php';
}

// Ensure timezone is set to Harare for all operations
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Africa/Harare');
}
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}

if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    require_once "app/Model/User.php";
    require_once "app/Model/Learning.php";
    require_once "app/Model/Attendance.php";
    
    // Auto-checkout employees at 5:00 PM (Harare time) if they haven't checked out
    // This runs on every page load to ensure employees are checked out
    try {
        $current_time = date('H:i:s');
        // Only run if it's 5:00 PM or later (and before midnight)
        if ($current_time >= '17:00:00' && $current_time < '23:59:59') {
            Attendance::auto_checkout_all($conn);
        }
    } catch (\Throwable $e) {
        // Silently fail - don't interrupt page load
        error_log("Auto checkout error: " . $e->getMessage());
    }
    require_once "app/Model/Notification.php";
    require_once "app/Model/Appraisal.php";

    // Include RoleHelper for role checking
    require_once "app/Model/RoleHelper.php";
    
    $role = strtolower($_SESSION['role']);
    $is_managing_director = RoleHelper::is_managing_director($conn, $_SESSION['employee_id']);
    $is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id']);
    $is_manager = RoleHelper::is_manager($conn, $_SESSION['employee_id']);
    $is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id']);
    
    // Get user department (normalize for comparison)
    $department = RoleHelper::get_department($conn, $_SESSION['employee_id']);
    $department_normalized = trim($department);
    
    // Check if Finance Manager (must be manager AND in Finance department)
    $is_finance_manager = false;
    if ($is_manager) {
        // Check if department matches Finance (case-insensitive)
        $is_finance_manager = (strcasecmp($department_normalized, RoleHelper::DEPT_FINANCE) === 0);
    }
    
    // Initialize variables
    $num_users = 0;
    $pending_employees_count = 0;
    $pending_applications = [];
    $pending_repayments = [];
    $courses_completed = 0;
    $department_employees_count = 0;
    $pending_leave_applications = [];
    $pending_loan_applications = [];
    $department_attendance_stats = [];
    $unread_notifications = 0;
    $recent_notifications = [];
    $pending_appraisals_count = 0;
    
    // Admin gets special treatment - check FIRST (admin has both admin and employee functions)
    if ($is_admin) {
        // Admin dashboard - has both admin functions AND employee functions
        $num_users = count_users($conn);
        $pending_employees_count = count_pending_employees($conn);
        
        // Admin employee functions (like regular employee)
        $courses_completed = Learning::get_completed_courses_count($conn, $_SESSION['employee_id']);
        $my_pending_applications = Attendance::get_pending_applications($conn, $_SESSION['employee_id']);
        $unread_notifications = count_notification($conn, $_SESSION['employee_id']);
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? ORDER BY id DESC LIMIT 6");
        $stmtN->execute([$_SESSION['employee_id']]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Admin functions
        $pending_repayments = Attendance::get_pending_repayments($conn);
    }
    // Finance Manager gets special treatment - check AFTER admin
    elseif ($is_finance_manager) {
        // Department employees count – same as Department Attendance tab (exclude managers)
        $department_employees_count = RoleHelper::get_department_employee_count_excluding_managers($conn, RoleHelper::DEPT_FINANCE);
        
        // Get pending leave applications from Finance department (first approval)
        $pending_leave_applications = Attendance::get_pending_applications($conn, $_SESSION['employee_id'], RoleHelper::DEPT_FINANCE);
        
        // Get hr_approved loan applications (final approval)
        $pending_loan_applications = Attendance::get_hr_approved_loan_applications($conn);
        
        // Department attendance stats – same set as Department Attendance tab (non-managers, no direct reports)
        $dept_ids = RoleHelper::get_department_employees($conn, RoleHelper::DEPT_FINANCE);
        $department_attendance_stats = ['present_count' => 0, 'absent_count' => 0, 'employees_present_today' => 0];
        if (!empty($dept_ids)) {
            $placeholders = implode(',', array_fill(0, count($dept_ids), '?'));
            $sql = "SELECT e.employee_id, e.role FROM employee e WHERE e.employee_id IN ($placeholders) AND e.department = ? AND e.status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_merge($dept_ids, [RoleHelper::DEPT_FINANCE]));
            $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $non_manager_ids = [];
            foreach ($emps as $e) {
                $rl = strtolower((string)($e['role'] ?? ''));
                if ($rl === 'manager' || strpos($rl, '_manager') !== false || $rl === 'hr_manager' || $rl === 'managing_director') continue;
                $stmt2 = $conn->prepare("SELECT COUNT(*) FROM employee WHERE manager_id = ? AND status = 'active'");
                $stmt2->execute([$e['employee_id']]);
                if ($stmt2->fetchColumn() > 0) continue;
                $non_manager_ids[] = $e['employee_id'];
            }
            if (!empty($non_manager_ids)) {
                $ph = implode(',', array_fill(0, count($non_manager_ids), '?'));
                $sql2 = "SELECT COUNT(DISTINCT a.employee_id) as employees_present_today,
                         COUNT(CASE WHEN a.status IN ('present','late') THEN 1 END) as present_count
                         FROM attendance a WHERE a.employee_id IN ($ph) AND a.date = CURDATE()";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->execute($non_manager_ids);
                $department_attendance_stats = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
                $department_attendance_stats['absent_count'] = count($non_manager_ids) - ($department_attendance_stats['employees_present_today'] ?? 0);
            }
        }
        
        $pending_applications = array_merge($pending_leave_applications, $pending_loan_applications);

        // Unread notifications – same logic as Notifications tab (exclude attendance for FM)
        $unread_notifications = count_notification($conn, $_SESSION['employee_id'], true);
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? AND type NOT IN ('attendance','team_attendance','activity') ORDER BY id DESC LIMIT 6");
        $stmtN->execute([$_SESSION['employee_id']]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Pending appraisals for department (reuse existing model)
        $pending_appraisals = Appraisal::get_pending_appraisals($conn, $_SESSION['employee_id'], RoleHelper::DEPT_FINANCE);
        $pending_appraisals_count = is_array($pending_appraisals) ? count($pending_appraisals) : 0;
    } elseif ($is_managing_director) {
        // Managing Director dashboard - sees all activity, pending approvals
        // MD does NOT apply for leaves or loans, so no leave-related data needed
        // Count ALL active employees (not just role='employee')
        $sql_total_employees = "SELECT COUNT(*) FROM employee WHERE status = 'active'";
        $num_users = $conn->query($sql_total_employees)->fetchColumn();
        $pending_employees_count = count_pending_employees($conn);
        
        // Pending applications – same source as MD Approvals tab (only for approval, not application)
        $pending_applications = Attendance::get_md_pending_applications($conn);
        
        // Get recent activity count
        require_once "app/Model/ActivityLog.php";
        $recent_activity_count = ActivityLog::get_activity_count($conn, ['date_from' => date('Y-m-d', strtotime('-7 days'))]);
        
        // Get pending appraisals for managers (MD appraises managers)
        $pending_appraisals = Appraisal::get_pending_appraisals_for_managers($conn);
        $pending_appraisals_count = is_array($pending_appraisals) ? count($pending_appraisals) : 0;
        
        // Get all activity stats
        $stats_by_dept = ActivityLog::get_activity_by_department($conn, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        $stats_by_type = ActivityLog::get_activity_by_type($conn, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        
        $pending_repayments = Attendance::get_pending_repayments($conn);
        
        // Unread notifications (exclude attendance for MD)
        $unread_notifications = count_notification($conn, $_SESSION['employee_id'], true); // true = exclude_attendance
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? AND type != 'attendance' AND type != 'team_attendance' ORDER BY id DESC LIMIT 10");
        $stmtN->execute([$_SESSION['employee_id']]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Initialize leave-related variables to null for MD (they don't apply for leaves/loans)
        $leave_balance = null;
        $sick_leave_balance = null;
        $employee_info = null;
        $my_pending_applications = [];
        $today_attendance = null;
        
    } elseif ($is_hr) {
        // HR Manager dashboard data
        $num_users = count_users($conn);
        $pending_employees_count = count_pending_employees($conn);
        $pending_repayments = Attendance::get_pending_repayments($conn);
        
        // Get all pending applications HR Manager needs to approve
        $pending_applications = Attendance::get_hr_pending_applications($conn);
        
        // Separate by type for dashboard display
        $pending_manager_leaves = [];
        $pending_employee_leaves = [];
        $pending_loans = [];
        
        foreach ($pending_applications as $app) {
            if ($app['type'] === 'leave') {
                $employee_role = strtolower($app['employee_role'] ?? '');
                if ($employee_role === 'manager' || strpos($employee_role, '_manager') !== false) {
                    $pending_manager_leaves[] = $app;
                } else {
                    $pending_employee_leaves[] = $app;
                }
            } elseif ($app['type'] === 'loan') {
                $pending_loans[] = $app;
            }
        }
        
        // Get training statistics
        $total_courses = $conn->query("SELECT COUNT(*) FROM learning_courses WHERE status = 'active'")->fetchColumn();
        $completed_courses = $conn->query("SELECT COUNT(DISTINCT employee_id) FROM learning_enrollments WHERE status = 'completed'")->fetchColumn();
        $training_completion_rate = $total_courses > 0 ? round(($completed_courses / $num_users) * 100, 1) : 0;
        
        // Get attendance statistics (today)
        $sql_attendance = "SELECT 
                            COUNT(DISTINCT a.employee_id) as total_checked_in,
                            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
                            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count
                          FROM attendance a
                          WHERE a.date = CURDATE()";
        $attendance_stats = $conn->query($sql_attendance)->fetch(PDO::FETCH_ASSOC) ?: [];
        $attendance_rate = $num_users > 0 ? round(($attendance_stats['total_checked_in'] / $num_users) * 100, 1) : 0;
        
        // Unread notifications – same logic as Notifications tab (exclude attendance for HR)
        $unread_notifications = count_notification($conn, $_SESSION['employee_id'], true);
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? AND type NOT IN ('attendance','team_attendance','activity') ORDER BY id DESC LIMIT 10");
        $stmtN->execute([$_SESSION['employee_id']]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } elseif ($is_manager) {
        // Other managers dashboard (not Finance Manager)
        // Department employees count – same as Department Attendance tab (exclude managers)
        $department_employees_count = RoleHelper::get_department_employee_count_excluding_managers($conn, $department);
        // Pending team leave applications (first approval) for this manager's department
        $pending_leave_applications = Attendance::get_pending_applications($conn, $_SESSION['employee_id'], $department);
        $pending_applications = $pending_leave_applications;

        // Department attendance stats (today) – same set as tab: employees in dept excluding managers
        $dept_ids = RoleHelper::get_department_employees($conn, $department);
        $department_attendance_stats = ['present_count' => 0, 'absent_count' => 0, 'employees_present_today' => 0];
        if (!empty($dept_ids)) {
            $placeholders = implode(',', array_fill(0, count($dept_ids), '?'));
            $sql = "SELECT e.employee_id, e.role FROM employee e WHERE e.employee_id IN ($placeholders) AND e.department = ? AND e.status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_merge($dept_ids, [$department]));
            $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $non_manager_ids = [];
            foreach ($emps as $e) {
                $rl = strtolower((string)($e['role'] ?? ''));
                if ($rl === 'manager' || strpos($rl, '_manager') !== false || $rl === 'hr_manager' || $rl === 'managing_director') {
                    continue;
                }
                $stmt2 = $conn->prepare("SELECT COUNT(*) FROM employee WHERE manager_id = ? AND status = 'active'");
                $stmt2->execute([$e['employee_id']]);
                if ($stmt2->fetchColumn() > 0) continue;
                $non_manager_ids[] = $e['employee_id'];
            }
            if (!empty($non_manager_ids)) {
                $ph = implode(',', array_fill(0, count($non_manager_ids), '?'));
                $sql2 = "SELECT
                            COUNT(DISTINCT a.employee_id) as employees_present_today,
                            COUNT(CASE WHEN a.status IN ('present','late') THEN 1 END) as present_count,
                            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count
                        FROM attendance a
                        WHERE a.employee_id IN ($ph) AND a.date = CURDATE()";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->execute($non_manager_ids);
                $department_attendance_stats = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
                $department_attendance_stats['absent_count'] = count($non_manager_ids) - ($department_attendance_stats['employees_present_today'] ?? 0);
            }
        }

        // Unread notifications – same logic as Notifications tab (no exclude for non-FM managers)
        $unread_notifications = count_notification($conn, $_SESSION['employee_id']);
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? ORDER BY id DESC LIMIT 6");
        $stmtN->execute([$_SESSION['employee_id']]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Pending appraisals (for manager view)
        $pending_appraisals = Appraisal::get_pending_appraisals($conn, $_SESSION['employee_id'], $department);
        $pending_appraisals_count = is_array($pending_appraisals) ? count($pending_appraisals) : 0;
        
        // Department statistics - leave balances
        $sql_leave_balances = "SELECT 
                                SUM(normal_leave_days) as total_normal_leave,
                                SUM(special_leave_days_remaining) as total_special_leave,
                                SUM(sick_leave_days_remaining) as total_sick_leave
                              FROM employee 
                              WHERE department = ? AND status = 'active'";
        $stmt_leave = $conn->prepare($sql_leave_balances);
        $stmt_leave->execute([$department]);
        $leave_balances = $stmt_leave->fetch(PDO::FETCH_ASSOC) ?: [];
        
        // Team learning statistics
        $sql_learning = "SELECT 
                          COUNT(DISTINCT le.employee_id) as employees_with_courses,
                          COUNT(DISTINCT le.course_id) as courses_taken
                        FROM learning_enrollments le
                        JOIN employee e ON le.employee_id = e.employee_id
                        WHERE e.department = ? AND e.status = 'active' AND le.status = 'completed'";
        $stmt_learning = $conn->prepare($sql_learning);
        $stmt_learning->execute([$department]);
        $learning_stats = $stmt_learning->fetch(PDO::FETCH_ASSOC) ?: [];
        $learning_completion_rate = $department_employees_count > 0 
            ? round((($learning_stats['employees_with_courses'] ?? 0) / $department_employees_count) * 100, 1) 
            : 0;
    } else {
        // Employee dashboard data
        $employee_id = $_SESSION['employee_id'];
        $courses_completed = Learning::get_completed_courses_count($conn, $employee_id);
        $pending_applications = Attendance::get_pending_applications($conn, $employee_id);
        $unread_notifications = count_notification($conn, $employee_id);
        
        // Get employee details for leave balance
        $employee_info = get_user_by_id($conn, $employee_id);
        $leave_balance = $employee_info['leave_days_remaining'] ?? 0;
        $sick_leave_balance = $employee_info['sick_leave_days_remaining'] ?? 0;
        
        // Get today's attendance status
        $sql_today = "SELECT status, check_in, check_out 
                      FROM attendance 
                      WHERE employee_id = ? AND date = CURDATE() 
                      ORDER BY attendance_id DESC LIMIT 1";
        $stmt_today = $conn->prepare($sql_today);
        $stmt_today->execute([$employee_id]);
        $today_attendance = $stmt_today->fetch(PDO::FETCH_ASSOC);
        
        // Get active course enrollments
        $sql_enrollments = "SELECT COUNT(*) FROM learning_enrollments 
                           WHERE employee_id = ? AND status != 'completed'";
        $stmt_enroll = $conn->prepare($sql_enrollments);
        $stmt_enroll->execute([$employee_id]);
        $active_enrollments = $stmt_enroll->fetchColumn();
        
        // Get upcoming appraisals (if any)
        $sql_appraisals = "SELECT COUNT(*) FROM appraisal_forms 
                          WHERE employee_id = ? AND status = 'pending'";
        $stmt_app = $conn->prepare($sql_appraisals);
        $stmt_app->execute([$employee_id]);
        $pending_appraisals_count = $stmt_app->fetchColumn();
        
        // Get recent notifications
        $stmtN = $conn->prepare("SELECT * FROM notifications WHERE recipient = ? ORDER BY id DESC LIMIT 5");
        $stmtN->execute([$employee_id]);
        $recent_notifications = $stmtN->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Handle repayment verification
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_repayment'])) {
        $repayment_id = $_POST['repayment_id'];
        $action = $_POST['action'];
        $admin_comment = $_POST['admin_comment'];

        $result = Attendance::verify_repayment(
            $conn,
            $repayment_id,
            $_SESSION['employee_id'],
            $action,
            $admin_comment
        );

        if ($result['success']) {
            $success = $result['message'];
            $pending_repayments = Attendance::get_pending_repayments($conn);
        } else {
            $error = $result['message'];
        }
    }
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard · Shelter HRMS</title>
        <?php include "inc/head_common.php"; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/aesthetic-improvements.css">
    </head>

    <body>
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>

            <div class="dashboard-container">
                <!-- Welcome Back Message -->
                <?php 
                // Get current user's name for personalized greeting
                $current_user = get_user_by_id($conn, $_SESSION['employee_id']);
                $user_first_name = ($current_user ? $current_user['first_name'] : '');
                if ($user_first_name): 
                ?>
                <div class="welcome-back-banner">
                    <h2 class="welcome-back-title">Welcome back, <?= htmlspecialchars($user_first_name) ?>!</h2>
                </div>
                <?php endif; ?>

                <!-- Pending Payment Verifications -->
                <?php if (!empty($pending_repayments)): ?>
                    <div class="dashboard-section">
                        <h3><i class="fa fa-money"></i> Pending Payment Verifications</h3>
                        <div class="table-responsive">
                            <table class="verification-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Loan Amount</th>
                                        <th>Payment Date</th>
                                        <th>Amount Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_repayments as $repayment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($repayment['first_name'] . ' ' . $repayment['last_name']) ?></td>
                                            <td>$<?= number_format($repayment['loan_amount'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($repayment['payment_date'])) ?></td>
                                            <td>$<?= number_format($repayment['amount_due'], 2) ?></td>
                                            <td><span class="status-pending">Pending</span></td>
                                        </tr>
                                        <!-- Add form below each row -->
                                        <tr class="verification-form-row">
                                            <td colspan="6">
                                                <form method="post" class="verification-form">
                                                    <input type="hidden" name="repayment_id" value="<?= $repayment['id'] ?>">
                                                    <div class="form-group">
                                                        <label>Verification Notes</label>
                                                        <textarea name="admin_comment" class="input-1" rows="3" required></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Action</label>
                                                        <div class="action-buttons">
                                                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                                                <i class="fa fa-check"></i> Approve
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                                <i class="fa fa-times"></i> Reject
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="verify_repayment" value="1">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Stats -->
                <section class="section-1">
                    <?php if ($is_finance_manager) { ?>
                        <!-- Finance Manager Dashboard -->
                        <div class="dashboard">
                            <div class="dashboard-item">
                                <i class="fa fa-users"></i>
                                <span><?= $department_employees_count ?> Department Employees</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-check-circle"></i>
                                <span><?= $department_attendance_stats['present_count'] ?? 0 ?> Present Today</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-user-times"></i>
                                <span><?= $department_attendance_stats['absent_count'] ?? 0 ?> Absent Today</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php#approvals-tab'">
                                <i class="fa fa-file-text"></i>
                                <span><?= count($pending_leave_applications) ?> Pending Application Approvals (Leave)</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php#approvals-tab'">
                                <i class="fa fa-money"></i>
                                <span><?= count($pending_loan_applications) ?> Pending Application Approvals (Loan)</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/appraisal.php'">
                                <i class="fa fa-star"></i>
                                <span>Department Appraisals</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/appraisal.php'">
                                <i class="fa fa-exclamation-circle"></i>
                                <span><?= (int)$pending_appraisals_count ?> Pending Appraisals</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>notifications.php'">
                                <i class="fa fa-bell"></i>
                                <span><?= (int)$unread_notifications ?> Unread Notifications</span>
                            </div>
                        </div>
                    <?php } elseif ($is_managing_director) { ?>
                        <?php 
                        $pending_leaves_count = count(array_filter($pending_applications, function($app) { return ($app['type'] ?? '') === 'leave'; }));
                        $pending_loans_count = count(array_filter($pending_applications, function($app) { return ($app['type'] ?? '') === 'loan'; }));
                        $total_pending = count($pending_applications);
                        ?>
                        <!-- Managing Director Dashboard - Executive Overview -->
                        <div class="md-executive-dashboard">
                            <div class="md-executive-header">
                                <h2 class="md-executive-title"><i class="fa fa-dashboard"></i> Executive Overview</h2>
                                <p class="md-executive-subtitle">Key metrics and actions at a glance</p>
                            </div>
                            <div class="md-executive-grid">
                                <a href="<?= BASE_URL ?>app/md_approvals.php" class="md-executive-card md-card-priority <?= $total_pending > 0 ? 'has-pending' : '' ?>">
                                    <div class="md-card-icon"><i class="fa fa-check-circle"></i></div>
                                    <div class="md-card-value"><?= $total_pending ?></div>
                                    <div class="md-card-label">Pending Approvals</div>
                                    <?php if ($total_pending > 0): ?>
                                        <div class="md-card-meta"><?= $pending_leaves_count ?> leave · <?= $pending_loans_count ?> loan</div>
                                    <?php endif; ?>
                                </a>
                                <div class="md-executive-card">
                                    <div class="md-card-icon"><i class="fa fa-users"></i></div>
                                    <div class="md-card-value"><?= (int)$num_users ?></div>
                                    <div class="md-card-label">Total Employees</div>
                                </div>
                                <?php if ($pending_employees_count > 0): ?>
                                <a href="<?= BASE_URL ?>pending-employees.php" class="md-executive-card md-card-priority">
                                    <div class="md-card-icon"><i class="fa fa-user-plus"></i></div>
                                    <div class="md-card-value"><?= $pending_employees_count ?></div>
                                    <div class="md-card-label">Pending Registrations</div>
                                </a>
                                <?php endif; ?>
                                <?php if ($pending_appraisals_count > 0): ?>
                                <a href="<?= BASE_URL ?>app/appraisal.php" class="md-executive-card">
                                    <div class="md-card-icon"><i class="fa fa-star"></i></div>
                                    <div class="md-card-value"><?= $pending_appraisals_count ?></div>
                                    <div class="md-card-label">Pending Appraisals</div>
                                </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>notifications.php" class="md-executive-card">
                                    <div class="md-card-icon"><i class="fa fa-bell"></i></div>
                                    <div class="md-card-value"><?= (int)$unread_notifications ?></div>
                                    <div class="md-card-label">Unread Notifications</div>
                                </a>
                                <a href="<?= BASE_URL ?>app/activity_log.php" class="md-executive-card">
                                    <div class="md-card-icon"><i class="fa fa-line-chart"></i></div>
                                    <div class="md-card-label">Activity &amp; Insights</div>
                                </a>
                                <a href="<?= BASE_URL ?>notify_all_form.php" class="md-executive-card">
                                    <div class="md-card-icon"><i class="fa fa-bullhorn"></i></div>
                                    <div class="md-card-label">Company Announcements</div>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Employees by Department Section for MD -->
                        <?php
                        // Get employee count by department (normalize so "Sales" and "SALES AND MARKETING" are one)
                        $sql_dept_count = "SELECT 
                                          CASE WHEN TRIM(LOWER(department)) = 'sales' THEN 'SALES AND MARKETING' ELSE TRIM(department) END AS department,
                                          COUNT(*) as employee_count 
                                          FROM employee 
                                          WHERE status = 'active' 
                                          AND department IS NOT NULL 
                                          AND department != ''
                                          GROUP BY CASE WHEN TRIM(LOWER(department)) = 'sales' THEN 'SALES AND MARKETING' ELSE TRIM(department) END 
                                          ORDER BY department ASC";
                        $stmt_dept = $conn->prepare($sql_dept_count);
                        $stmt_dept->execute();
                        $employees_by_dept = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if (!empty($employees_by_dept)): ?>
                            <div class="dashboard-section" style="margin-top: 20px;">
                                <h3><i class="fa fa-users"></i> Employees by Department</h3>
                                <div class="table-responsive">
                                    <table class="verification-table">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Total Employees</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees_by_dept as $dept): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($dept['department']) ?></strong></td>
                                                    <td><?= (int)$dept['employee_count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr style="background-color: #e8f4f8; font-weight: 700;">
                                                <td><strong>Total</strong></td>
                                                <td><strong><?= array_sum(array_column($employees_by_dept, 'employee_count')) ?></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php } elseif ($is_admin) { ?>
                        <!-- System Admin Dashboard (has both admin functions AND employee functions) -->
                        <div class="dashboard">
                            <!-- Admin Functions -->
                            <div class="dashboard-item">
                                <i class="fa fa-users"></i>
                                <span><a href="user.php"><?= $num_users ?> Total Employees</a></span>
                            </div>
                            <?php if ($pending_employees_count > 0): ?>
                                <div class="dashboard-item pending-employees clickable" onclick="window.location.href='<?= BASE_URL ?>pending-employees.php'">
                                    <i class="fa fa-user-plus"></i>
                                    <span><a href="<?= BASE_URL ?>pending-employees.php"><?= $pending_employees_count ?> Pending Employee Registrations</a></span>
                                </div>
                            <?php endif; ?>
                            <div class="dashboard-item">
                                <i class="fa fa-user-plus"></i>
                                <span><a href="add-user.php">Register New Employee</a></span>
                            </div>
                            <?php if (!empty($pending_repayments)): ?>
                                <div class="dashboard-item">
                                    <i class="fa fa-money"></i>
                                    <span><?= count($pending_repayments) ?> Pending Repayments</span>
                                </div>
                            <?php endif; ?>
                            <!-- Employee Functions -->
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php'">
                                <i class="fa fa-file-text"></i>
                                <span><?= count($my_pending_applications) ?> My Pending Applications</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/learning.php'">
                                <i class="fa fa-graduation-cap"></i>
                                <span><?= $courses_completed ?> Courses Completed</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>notifications.php'">
                                <i class="fa fa-bell"></i>
                                <span><?= (int)$unread_notifications ?> Unread Notifications</span>
                            </div>
                        </div>
                    <?php } elseif ($is_hr) {
                        $hr_pending_count = count($pending_applications);
                    ?>
                        <!-- HR Manager Dashboard -->
                        <div class="dashboard">
                            <div class="dashboard-item">
                                <i class="fa fa-users"></i>
                                <span><?= $num_users ?> Total Employees</span>
                            </div>
                            <?php if ($pending_employees_count > 0): ?>
                                <div class="dashboard-item pending-employees clickable" onclick="window.location.href='<?= BASE_URL ?>pending-employees.php'">
                                    <i class="fa fa-user-plus"></i>
                                    <span><a href="<?= BASE_URL ?>pending-employees.php"><?= $pending_employees_count ?> Pending Employee Registrations</a></span>
                                </div>
                            <?php endif; ?>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php#approvals-tab'">
                                <i class="fa fa-file-text"></i>
                                <span><?= $hr_pending_count ?> Pending Application Approvals</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-check-circle"></i>
                                <span><?= $attendance_stats['present_count'] ?? 0 ?> Present Today</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-percent"></i>
                                <span><?= $attendance_rate ?>% Attendance Rate</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-graduation-cap"></i>
                                <span><?= $training_completion_rate ?>% Training Completion</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>notifications.php'">
                                <i class="fa fa-bell"></i>
                                <span><?= (int)$unread_notifications ?> Unread Notifications</span>
                            </div>
                        </div>
                    <?php } elseif ($is_manager) { ?>
                        <!-- Department Managers Dashboard (Sales, Operations, etc. - NOT Finance) -->
                        <div class="dashboard">
                            <div class="dashboard-item">
                                <i class="fa fa-users"></i>
                                <span><?= $department_employees_count ?> Department Employees</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-check-circle"></i>
                                <span><?= $department_attendance_stats['present_count'] ?? 0 ?> Present Today</span>
                            </div>
                            <div class="dashboard-item">
                                <i class="fa fa-user-times"></i>
                                <span><?= $department_attendance_stats['absent_count'] ?? 0 ?> Absent Today</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php#approvals-tab'">
                                <i class="fa fa-file-text"></i>
                                <span><?= count($pending_leave_applications) ?> Pending Application Approvals (Leave)</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/appraisal.php'">
                                <i class="fa fa-exclamation-circle"></i>
                                <span><?= (int)$pending_appraisals_count ?> Pending Appraisals</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/department_attendance.php'">
                                <i class="fa fa-calendar-check-o"></i>
                                <span>Department Attendance</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/department_announcement.php'">
                                <i class="fa fa-bullhorn"></i>
                                <span>Post Department Announcement</span>
                            </div>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>notifications.php'">
                                <i class="fa fa-bell"></i>
                                <span><?= (int)$unread_notifications ?> Unread Notifications</span>
                            </div>
                        </div>
                        <?php if (!empty($recent_notifications)): ?>
                            <div class="dashboard-section">
                                <h3><i class="fa fa-history"></i> Recent Updates</h3>
                                <div class="table-responsive">
                                    <table class="verification-table">
                                        <thead>
                                            <tr>
                                                <th>Message</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_notifications as $n): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($n['message']) ?></td>
                                                    <td><?= htmlspecialchars($n['type']) ?></td>
                                                    <td><?= htmlspecialchars($n['date'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php } else { ?>
                        <!-- Employee Dashboard -->
                        <div class="dashboard">
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/attendance.php'">
                                <i class="fa fa-calendar-check-o"></i>
                                <span>
                                    <?php if ($today_attendance): ?>
                                        <?= ucfirst($today_attendance['status']) ?> Today
                                        <?php if ($today_attendance['check_in']): ?>
                                            <br><small>Checked in: <?= date('H:i', strtotime($today_attendance['check_in'])) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Not Checked In
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php'">
                                <i class="fa fa-calendar"></i>
                                <span><?= number_format($leave_balance, 1) ?> Leave Days Remaining</span>
                            </div>
                            
                            <?php if ($sick_leave_balance > 0): ?>
                            <div class="dashboard-item">
                                <i class="fa fa-heartbeat"></i>
                                <span><?= number_format($sick_leave_balance, 1) ?> Sick Leave Days</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/applications.php'">
                                <i class="fa fa-file-text"></i>
                                <span><?= count($pending_applications) ?> My Pending Applications</span>
                            </div>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/learning.php'">
                                <i class="fa fa-graduation-cap"></i>
                                <span><?= (int)$active_enrollments ?> Active Courses</span>
                            </div>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/learning.php'">
                                <i class="fa fa-certificate"></i>
                                <span><?= $courses_completed ?> Courses Completed</span>
                            </div>
                            
                            <?php if ($pending_appraisals_count > 0): ?>
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/appraisal.php'">
                                <i class="fa fa-star"></i>
                                <span><?= $pending_appraisals_count ?> Pending Appraisal<?= $pending_appraisals_count > 1 ? 's' : '' ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>notifications.php'">
                                <i class="fa fa-bell"></i>
                                <span><?= (int)$unread_notifications ?> Unread Notifications</span>
                            </div>
                            
                            <div class="dashboard-item clickable" onclick="window.location.href='<?= BASE_URL ?>app/company_announcements.php'">
                                <i class="fa fa-newspaper-o"></i>
                                <span>Company Announcements</span>
                            </div>
                        </div>
                    <?php } ?>
                </section>
            </div>
        </div>
        </div>
        
    </body>

    </html>
<?php } else {
    header("Location: login.php");
    exit();
}
?>
