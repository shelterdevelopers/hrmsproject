<?php
ob_start(); // Start output buffering to prevent header errors
session_start();
// Use require_once for critical files
require_once "../DB_connection.php";
require_once "Model/Attendance.php";
require_once "Model/User.php";
require_once "Model/Notification.php";
require_once "Model/RoleHelper.php";

// --- Authentication Check ---
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php?error=Please login first");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role']; // Store role

// Check if manager to get department for filtering
$is_manager = RoleHelper::is_manager($conn, $employee_id);
$is_hr = RoleHelper::is_hr($conn, $employee_id);
$is_managing_director = RoleHelper::is_managing_director($conn, $employee_id);
$department = null;
$is_finance_manager = false;
$show_loans = true; // Default: show loans

// HR and MD have full rights (leave + loans); check them before manager so HR Manager is never treated as "only department manager"
if ($is_hr || $is_managing_director) {
    $show_loans = true;
    if ($is_manager) {
        $department = RoleHelper::get_department($conn, $employee_id);
        $is_finance_manager = (strcasecmp(trim($department), RoleHelper::DEPT_FINANCE) === 0);
    }
} elseif ($is_manager) {
    $department = RoleHelper::get_department($conn, $employee_id);
    $is_finance_manager = (strcasecmp(trim($department), RoleHelper::DEPT_FINANCE) === 0);
    // All managers (including Operations Manager) can apply for leave and loan; loans go HR → Finance Manager
    $show_loans = true;
}

// --- Form Submission Handling ---
$success = null;
$error = null;
if (isset($_SESSION['app_success'])) { $success = $_SESSION['app_success']; unset($_SESSION['app_success']); }
if (isset($_SESSION['app_error'])) { $error = $_SESSION['app_error']; unset($_SESSION['app_error']); }
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Leave Application Submission ---
    // The name 'submit_leave' comes directly from the button in the leave tab
    if (isset($_POST['submit_leave'])) {
        if (empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['reason']) || empty($_POST['leave_type'])) {
            $error = "All leave application fields are required.";
        } else {
            // ... (Your leave validation and Attendance::apply_leave call logic remains the same)
             try {
                 $start_date = new DateTime($_POST['start_date']);
                 $end_date = new DateTime($_POST['end_date']);
                 if ($end_date < $start_date) { $error = "End date cannot be earlier than start."; }
                 else {
                     $requested_days = $start_date->diff($end_date)->days + 1;
                     $employee = get_user_by_id($conn, $employee_id);
                     if (!$employee) throw new Exception("Employee data not found.");
                     $leave_type = $_POST['leave_type'];
                     $column_to_check = ''; $leave_name = ''; $can_apply = true;
                     switch ($leave_type) {
                         case 'special': $column_to_check = 'special_leave_days_remaining'; $leave_name = 'Special Case'; break;
                         case 'sick': $column_to_check = 'sick_leave_days_remaining'; $leave_name = 'Sick'; break;
                         case 'maternity': $column_to_check = 'maternity_leave_days_remaining'; $leave_name = 'Maternity'; break;
                         case 'normal': $column_to_check = 'normal_leave_days'; $leave_name = 'Normal'; break;
                         default: $can_apply = false; $error = "Invalid leave type."; break;
                     }
                     if ($can_apply && !empty($column_to_check)) {
                         $remaining = $employee[$column_to_check] ?? 0;
                         if ($requested_days > $remaining) {
                             $can_apply = false;
                             $error = "Requested $requested_days days, but only $remaining $leave_name days remain.";
                         }
                     }
                     if ($can_apply) {
                         $result = Attendance::apply_leave(
                             $conn,
                             $employee_id,
                             $_POST['start_date'],
                             $_POST['reason'],
                             $leave_type,
                             $_POST['end_date']
                         );
                         if ($result['success']) { $success = "Leave application submitted!"; $_POST = []; }
                         else { $error = $result['message'] ?? 'Failed to submit leave.'; }
                     }
                 }
             } catch (Exception $e) { $error = "Error: " . $e->getMessage(); error_log("Leave app error: ".$e->getMessage()); }
        }
    }

    // --- Loan Application Submission ---
    // The name 'submit_loan' comes directly from the button in the loan tab
    elseif (isset($_POST['submit_loan'])) {
        if (empty($_POST['amount']) || empty($_POST['repayment_plan']) || empty($_POST['loan_reason'])) {
            $error = "All loan application fields are required.";
        } else {
            // Validate repayment_plan is a positive integer before calling apply_loan
            if (!ctype_digit((string)$_POST['repayment_plan']) || (int)$_POST['repayment_plan'] <= 0) {
                 $error = "Repayment plan must be a positive number of months.";
            } else {
                $result = Attendance::apply_loan( $conn, $employee_id, $_POST['amount'], $_POST['repayment_plan'], $_POST['loan_reason'] );
                if ($result['success']) { $success = "Loan application submitted for approval!"; $_POST = []; }
                else { $error = $result['message'] ?? 'Failed to submit loan application.'; }
            }
        }
    }

    // --- Manager/Admin/MD Responding to an Application ---
    elseif (isset($_POST['respond_application'])) {
        if (empty($_POST['application_id']) || !isset($_POST['status'])) { 
            $error = "Missing required info."; 
        } else {
            $app_id_resp = $_POST['application_id']; 
            $new_status_resp = $_POST['status']; 
            $comment_resp = $_POST['comment'] ?? '';
            $app_to_resp = Attendance::get_application_by_id($conn, $app_id_resp); 
            $can_resp = false;
            
            if ($app_to_resp) {
                // Check permissions using RoleHelper
                if ($app_to_resp['type'] === 'leave') {
                    $can_resp = RoleHelper::can_approve_leave($conn, $employee_id, $app_to_resp['employee_id']);
                } elseif ($app_to_resp['type'] === 'loan') {
                    $can_resp = RoleHelper::can_approve_loan($conn, $employee_id, $app_id_resp);
                }
                
                // Also allow if user is the manager assigned to this application
                if (!$can_resp && $app_to_resp['manager_id'] == $employee_id) {
                    $can_resp = true;
                }
            }
            
            if ($can_resp) {
                // Use ApplicationWorkflow for proper approval handling
                require_once "Model/ApplicationWorkflow.php";
                $result = ApplicationWorkflow::update_application(
                    $conn,
                    $app_id_resp,
                    $employee_id,
                    $new_status_resp,
                    $comment_resp
                );
                if ($result['success']) { 
                    $success = $result['message'] ?? "Response submitted."; 
                } else { 
                    $error = $result['message'] ?? "Failed to submit response."; 
                }
            } else { 
                $error = "Permission denied or application not found."; 
            }
        }
    }
} // End of POST handling


// --- Fetch Data for Display ---
$my_leave_applications = [];
$my_loan_applications = [];
$pending_approvals = [];
$responded_applications = [];

// Fetch user's own applications (Always needed)
$all_my_applications = Attendance::get_employee_applications($conn, $employee_id) ?? [];
foreach ($all_my_applications as $app) {
    if ($app['type'] == 'leave') {
        $my_leave_applications[] = $app;
    } elseif ($app['type'] == 'loan') {
        if ($app['status'] == 'approved') {
            try { $app['repayment_history'] = Attendance::get_repayment_history($conn, $app['id']); }
            catch(Exception $e) { error_log("Repay hist err: ".$e->getMessage()); $app['repayment_history'] = []; }
        } else { $app['repayment_history'] = []; }
        $my_loan_applications[] = $app;
    }
}

// If user has loan applications, they should be able to view them even if they can't apply for new ones
if (!empty($my_loan_applications)) {
    $show_loans = true;
}

// Fetch manager/admin specific data if applicable
// For department managers (except Finance), only get leave applications
// Finance Manager can see loans (handled separately)
// Managing Director can see applications that need their approval
// Regular employees should only see their own applications (no approval tabs)
$is_finance_manager = false;
// Check HR before manager so HR account always gets HR's pending list (not manager's)
if ($is_managing_director) {
    // Managing Director: Get applications that need MD approval
    // 1. HR Manager leave applications (needs MD only)
    // 2. Other managers' leave applications (after manager approval, needs MD)
    // 3. Finance Manager loan applications (after HR approval, needs MD)
    
    // Check if md_approval_status column exists
    $check_md_column = "SHOW COLUMNS FROM applications LIKE 'md_approval_status'";
    $stmt_check = $conn->query($check_md_column);
    $md_column_exists = $stmt_check->rowCount() > 0;
    
    $md_pending_applications = [];
    
    // HR leave applications (needs MD only)
    if ($md_column_exists) {
        $sql_hr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role
                        FROM applications a
                        JOIN employee e ON a.employee_id = e.employee_id
                        WHERE a.type = 'leave' 
                        AND a.status = 'pending'
                        AND (e.role = 'hr' OR e.role = 'hr_manager')
                        AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    } else {
        $sql_hr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role
                        FROM applications a
                        JOIN employee e ON a.employee_id = e.employee_id
                        WHERE a.type = 'leave' 
                        AND a.status = 'pending'
                        AND (e.role = 'hr' OR e.role = 'hr_manager')
                        AND a.manager_approval_status IS NULL";
    }
    $stmt_hr_leave = $conn->prepare($sql_hr_leave);
    $stmt_hr_leave->execute();
    $hr_leaves = $stmt_hr_leave->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $md_pending_applications = array_merge($md_pending_applications, $hr_leaves);
    
    // Other managers' leave applications (after HR approval, needs MD)
    if ($md_column_exists) {
        $sql_mgr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                         FROM applications a
                         JOIN employee e ON a.employee_id = e.employee_id
                         WHERE a.type = 'leave' 
                         AND a.status = 'pending'
                         AND (e.role = 'manager' OR e.role LIKE '%_manager')
                         AND e.role != 'hr_manager'
                         AND e.role != 'hr'
                         AND a.hr_approval_status = 'approved'
                         AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    } else {
        $sql_mgr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                         FROM applications a
                         JOIN employee e ON a.employee_id = e.employee_id
                         WHERE a.type = 'leave' 
                         AND a.status = 'pending'
                         AND (e.role = 'manager' OR e.role LIKE '%_manager')
                         AND e.role != 'hr_manager'
                         AND e.role != 'hr'
                         AND a.hr_approval_status = 'approved'
                         AND a.md_approval_status IS NULL";
    }
    $stmt_mgr_leave = $conn->prepare($sql_mgr_leave);
    $stmt_mgr_leave->execute();
    $mgr_leaves = $stmt_mgr_leave->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $md_pending_applications = array_merge($md_pending_applications, $mgr_leaves);
    
    // Finance Manager loan applications (after HR approval, needs MD)
    if ($md_column_exists) {
        $sql_fm_loan = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                       FROM applications a
                       JOIN employee e ON a.employee_id = e.employee_id
                       WHERE a.type = 'loan' 
                       AND a.status = 'pending'
                       AND (e.role = 'manager' OR e.role = 'finance_manager' OR e.role LIKE '%_manager')
                       AND e.department = 'Finance'
                       AND a.hr_approval_status = 'approved'
                       AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    } else {
        $sql_fm_loan = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                       FROM applications a
                       JOIN employee e ON a.employee_id = e.employee_id
                       WHERE a.type = 'loan' 
                       AND a.status = 'pending'
                       AND (e.role = 'manager' OR e.role = 'finance_manager' OR e.role LIKE '%_manager')
                       AND e.department = 'Finance'
                       AND a.hr_approval_status = 'approved'
                       AND a.md_approval_status IS NULL";
    }
    $stmt_fm_loan = $conn->prepare($sql_fm_loan);
    $stmt_fm_loan->execute();
    $fm_loans = $stmt_fm_loan->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $md_pending_applications = array_merge($md_pending_applications, $fm_loans);
    
    $pending_approvals = $md_pending_applications;
    // MD can see responded applications they've approved
    $responded_applications = Attendance::get_responded_applications($conn, $employee_id);
    
} elseif ($is_hr) {
    // HR: same source as dashboard – all applications HR needs to approve (leave + loan, first approval)
    $pending_approvals = Attendance::get_hr_pending_applications($conn);
    $responded_applications = Attendance::get_responded_applications($conn, $employee_id, null, false, true);
} elseif ($is_manager && $department) {
    $is_finance_manager = (strcasecmp(trim($department), RoleHelper::DEPT_FINANCE) === 0);
    
    if ($is_finance_manager) {
        // Finance Manager: get both leave and loan applications
        // 1. Leave applications from Finance department employees (first approval)
        $pending_leave_approvals = Attendance::get_pending_applications($conn, $employee_id, $department);
        // 2. HR-approved loan applications from ALL departments (final approval)
        $pending_loan_approvals = Attendance::get_hr_approved_loan_applications($conn);
        // Merge both types
        $pending_approvals = array_merge($pending_leave_approvals, $pending_loan_approvals);
        // Finance Manager can see all responded applications (including loans)
        $responded_applications = Attendance::get_responded_applications($conn, $employee_id);
    } else {
        // Other department managers: ONLY leave applications (no loans)
        $pending_approvals = Attendance::get_pending_applications($conn, $employee_id, $department);
        // Filter responded applications to only show leave (no loans)
        $responded_applications = Attendance::get_responded_applications($conn, $employee_id, $department, true);
    }
} else {
    // Regular employees: only see their own applications, no approval tabs
    $pending_approvals = [];
    $responded_applications = [];
}


// --- Include the View ---
include "views/applications_view.php"; // Pass all variables implicitly

?>