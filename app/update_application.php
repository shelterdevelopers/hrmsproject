<?php
ob_start(); // Start output buffering to prevent header errors
session_start();
require_once "../DB_connection.php";
require_once "Model/ApplicationWorkflow.php";
require_once "Model/RoleHelper.php";
require_once "Model/Attendance.php";

if (!isset($_SESSION['employee_id']) || !isset($_POST['application_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verify the user has permission to respond to this application
$application = Attendance::get_application_by_id($conn, $_POST['application_id']);
if (!$application) {
    header("Location: ../index.php?error=Application+not+found");
    exit();
}

// Check permissions using RoleHelper
$can_approve = false;
if ($application['type'] === 'leave') {
    $can_approve = RoleHelper::can_approve_leave($conn, $_SESSION['employee_id'], $application['employee_id']);
} elseif ($application['type'] === 'loan') {
    $can_approve = RoleHelper::can_approve_loan($conn, $_SESSION['employee_id'], $_POST['application_id']);
}

if (!$can_approve) {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

// Use new workflow system
$result = ApplicationWorkflow::update_application(
    $conn,
    $_POST['application_id'],
    $_SESSION['employee_id'],
    $_POST['status'],
    $_POST['comment'] ?? ''
);

ob_end_clean(); // Clean any output before redirect
if ($result['success']) {
    header("Location: ../app/applications.php?success=" . urlencode($result['message']));
} else {
    header("Location: ../app/applications.php?error=" . urlencode($result['message']));
}
exit();
?>