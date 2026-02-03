<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id']) || empty($_POST['application_id'])) {
    $_SESSION['app_error'] = 'Invalid request';
    header("Location: applications.php");
    exit();
}

$employee_id = (int) $_SESSION['employee_id'];
$application_id = (int) $_POST['application_id'];
if ($application_id <= 0) {
    $_SESSION['app_error'] = 'Invalid application';
    header("Location: applications.php");
    exit();
}

$app = Attendance::get_application_by_id($conn, $application_id);
if (!$app) {
    $_SESSION['app_error'] = 'Application not found';
    header("Location: applications.php");
    exit();
}

$is_hr = RoleHelper::is_hr($conn, $employee_id);
$is_md = RoleHelper::is_managing_director($conn, $employee_id);
$is_owner = ((int) $app['employee_id']) === $employee_id;
$status = $app['status'] ?? '';

// HR or MD can delete any application. Employee can only cancel their own pending application.
$can_delete = $is_hr || $is_md || ($is_owner && $status === 'pending');

if (!$can_delete) {
    $_SESSION['app_error'] = 'You cannot delete this application';
    header("Location: applications.php");
    exit();
}

if (Attendance::delete_application($conn, $application_id)) {
    $_SESSION['app_success'] = $is_owner ? "Application cancelled." : "Application deleted.";
} else {
    $_SESSION['app_error'] = "Failed to delete application.";
}
header("Location: applications.php");
exit();
