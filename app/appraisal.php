<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];

// Include RoleHelper for department checking
require_once "Model/RoleHelper.php";
$user_department = RoleHelper::get_department($conn, $employee_id);
$is_managing_director = RoleHelper::is_managing_director($conn, $employee_id);
$is_hr = RoleHelper::is_hr($conn, $employee_id);
$is_finance_manager = (RoleHelper::is_manager($conn, $employee_id) && $user_department === RoleHelper::DEPT_FINANCE);

// Get appraisal data based on user role
// Admin is treated as a regular employee (no manager/appraiser role) – they only see their own appraisals
if ($is_managing_director) {
    // MD sees appraisals for all managers
    $active_forms = Appraisal::get_active_appraisal_forms($conn, $employee_id, $role);
    $completed_forms = Appraisal::get_completed_appraisal_forms($conn, $employee_id, $role);
    $pending_appraisals = Appraisal::get_pending_appraisals_for_managers($conn);
    $completed_appraisals = []; // MD can see completed appraisals for managers
    $is_manager = true; // MD can create appraisals for managers
} elseif ($role == 'Admin') {
    // Admin has no appraiser role – see only their own appraisals (as employee)
    $active_forms = Appraisal::get_active_appraisal_forms($conn, $employee_id, 'employee');
    $completed_forms = Appraisal::get_completed_appraisal_forms($conn, $employee_id, 'employee');
} elseif ($is_hr) {
    $active_forms = Appraisal::get_active_appraisal_forms($conn, $employee_id, $role);
    // HR sees all completed appraisals for file-keeping (download/print and file)
    $completed_forms = Appraisal::get_all_completed_for_hr($conn);
} else {
    $active_forms = $is_finance_manager 
        ? Appraisal::get_active_appraisal_forms($conn, $employee_id, $role, $user_department)
        : Appraisal::get_active_appraisal_forms($conn, $employee_id, $role);
    $completed_forms = Appraisal::get_completed_appraisal_forms($conn, $employee_id, $role, $is_finance_manager ? $user_department : null);
}

// After getting active forms but before including the view:
// Admin is treated as employee for appraisals (no manager view)
$is_employee = ($role == 'Admin' || ($role != 'Admin' && !Appraisal::is_manager($conn, $employee_id) && !$is_managing_director));

// Handle tab selection
$active_tab = $_GET['tab'] ?? 'active';

// Show session success/error after redirect (e.g. after create appraisal)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['appraisal_error'])) {
        $error = $_SESSION['appraisal_error'];
        unset($_SESSION['appraisal_error']);
    }
}

// Check manager status (Admin has no appraiser role – they only see their own appraisals)
if ($role == 'Admin') {
    $pending_appraisals = [];
    $completed_appraisals = [];
    $employee_appraisals = Appraisal::get_employee_appraisals($conn, $employee_id);
    $is_manager = false;
} elseif ($is_managing_director) {
    // MD already handled above
    $is_manager = true;
} else {
    $is_manager = Appraisal::is_manager($conn, $employee_id);

    if ($is_manager && !$is_managing_director) {
        // For Finance Manager, pass department to get all department appraisals
        $pending_appraisals = Appraisal::get_pending_appraisals($conn, $employee_id, $is_finance_manager ? $user_department : null);
        $completed_appraisals = Appraisal::get_completed_appraisals($conn, $employee_id, $is_finance_manager ? $user_department : null);
    } elseif ($is_hr) {
        // HR can create appraisals but has no "my pending" list; they see their own as employee
        $pending_appraisals = [];
        $completed_appraisals = [];
        $employee_appraisals = Appraisal::get_employee_appraisals($conn, $employee_id);
    } else {
        $employee_appraisals = Appraisal::get_employee_appraisals($conn, $employee_id);
    }
}

// Who can see "Create New Appraisal": managers (including MD, Finance Manager) or HR – not Admin (Admin is a regular employee with admin rights only)
$can_create_appraisal = ($is_manager && $role != 'Admin') || $is_managing_director || $is_hr;

// Who can conduct appraisals (and thus see "Appraisals you're conducting"): MD and managers only – not HR, not average employees
$can_conduct_appraisal = $is_managing_director || ($is_manager && $role != 'Admin');

// Filter active forms: only pure employees (who cannot create appraisals) see just 'shared' appraisals.
// HR and managers need to see their draft appraisals too, so don't filter when they can create.
if ($is_employee && !$can_create_appraisal && $active_tab == 'active') {
    $active_forms = array_filter($active_forms, function($form) {
        return $form['appraisal_status'] == 'shared';
    });
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['share_appraisal'])) {
        // Handle sharing appraisal with employee
        $result = Appraisal::share_appraisal(
            $conn,
            $_POST['appraisal_id']
        );

        if ($result) {
            $success = "Appraisal shared with employee!";
        } else {
            $error = "Failed to share appraisal";
        }
    } elseif (isset($_POST['save_appraisal'])) {
        // Handle saving appraisal to database
        $result = Appraisal::save_appraisal(
            $conn,
            $_POST['appraisal_id']
        );

        if ($result) {
            $success = "Appraisal saved to database!";
        } else {
            $error = "Failed to save appraisal";
        }
    } elseif (isset($_POST['acknowledge_appraisal'])) {
        // Handle employee acknowledgement
        $result = Appraisal::acknowledge_appraisal(
            $conn,
            $_POST['appraisal_id'],
            $_POST['employee_comments']
        );

        if ($result) {
            $success = "Appraisal acknowledged!";
        } else {
            $error = "Failed to acknowledge appraisal";
        }
    } elseif (isset($_POST['create_appraisal'])) {
        $appraisee_id = (int)($_POST['employee_id'] ?? 0);
        if ($appraisee_id <= 0) {
            $_SESSION['appraisal_error'] = "Please select an employee.";
            header("Location: appraisal.php?tab=new");
            exit();
        }
        $period_start = trim((string)($_POST['period_start'] ?? ''));
        $period_end = trim((string)($_POST['period_end'] ?? ''));
        if ($period_start === '' || $period_end === '') {
            $_SESSION['appraisal_error'] = "Please set both period start and end dates.";
            header("Location: appraisal.php?tab=new");
            exit();
        }
        if (strtotime($period_end) < strtotime($period_start)) {
            $_SESSION['appraisal_error'] = "Period end date must be on or after period start date.";
            header("Location: appraisal.php?tab=new");
            exit();
        }
        try {
            $conn->beginTransaction();

            // HR appraises employees (HR is the appraiser). MD and departmental managers are the appraiser when they create.
            $manager_id_for_appraisal = $employee_id;

            // 1. Create appraisal form
            $sql = "INSERT INTO appraisal_forms (
                employee_id, 
                manager_id, 
                period_start, 
                period_end, 
                status
            ) VALUES (?, ?, ?, ?, 'draft')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $appraisee_id,
                $manager_id_for_appraisal,
                $period_start,
                $period_end
            ]);

            $form_id = $conn->lastInsertId();

            // 2. Create appraisal record
            $metrics = Appraisal::get_metrics();
            $sql = "INSERT INTO appraisals (
                id,
                employee_id,
                manager_id,
                metrics,
                status
            ) VALUES (?, ?, ?, ?, 'draft')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $form_id,
                $appraisee_id,
                $manager_id_for_appraisal,
                json_encode($metrics)
            ]);

            $conn->commit();
            $_SESSION['success'] = "Appraisal created successfully! You can now fill it in under Active Appraisals and share it with the employee.";
            header("Location: appraisal.php?tab=active");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['appraisal_error'] = "Error creating appraisal: " . $e->getMessage();
            header("Location: appraisal.php?tab=new");
            exit();
        }
    }
}

include "views/appraisal_view.php";
