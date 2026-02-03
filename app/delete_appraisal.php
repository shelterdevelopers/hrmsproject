<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php?error=Please login first");
    exit();
}

$form_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($form_id <= 0) {
    $_SESSION['appraisal_error'] = "Invalid appraisal.";
    header("Location: appraisal.php?tab=active");
    exit();
}

$employee_id = (int) $_SESSION['employee_id'];
$is_hr = RoleHelper::is_hr($conn, $employee_id);
$is_md = RoleHelper::is_managing_director($conn, $employee_id);
$form = Appraisal::get_appraisal_form_details($conn, $form_id);

if (!$form) {
    $_SESSION['appraisal_error'] = "Appraisal not found.";
    header("Location: appraisal.php?tab=active");
    exit();
}

$manager_id = (int) ($form['manager_id'] ?? 0);
$status = $form['appraisal_status'] ?? $form['status'] ?? '';

// HR or MD can delete any appraisal. Manager can only delete draft/shared they are conducting.
$can_delete = $is_hr || $is_md || ($manager_id === $employee_id && in_array($status, ['draft', 'shared', 'employee_review'], true));

if (!$can_delete) {
    $_SESSION['appraisal_error'] = "You do not have permission to delete this appraisal.";
    header("Location: appraisal.php?tab=active");
    exit();
}

if (Appraisal::delete_appraisal($conn, $form_id)) {
    $_SESSION['success'] = "Appraisal deleted.";
} else {
    $_SESSION['appraisal_error'] = "Failed to delete appraisal.";
}
header("Location: appraisal.php?tab=active");
exit();
