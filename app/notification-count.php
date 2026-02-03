<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only serve this endpoint for AJAX requests (prevents stray output if included directly)
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    // Just return nothing if someone hits this directly
    return;
}

if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "../DB_connection.php";
    include "Model/Notification.php";
    require_once "Model/RoleHelper.php";

    // Exclude attendance/team_attendance/activity for MD, HR, and Finance Manager
    $emp_id = (int)$_SESSION['employee_id'];
    $is_managing_director = RoleHelper::is_managing_director($conn, $emp_id);
    $is_hr = RoleHelper::is_hr($conn, $emp_id);
    $is_finance_manager = (strcasecmp(trim(RoleHelper::get_department($conn, $emp_id) ?? ''), RoleHelper::DEPT_FINANCE) === 0 && RoleHelper::is_manager($conn, $emp_id));
    $exclude_attendance = $is_managing_director || $is_hr || $is_finance_manager;

    $count_notification = count_notification($conn, $_SESSION['employee_id'], $exclude_attendance);
    if ($count_notification > 0) {
        echo $count_notification; // Just the number, no HTML
    } else {
        echo ""; // Empty string when no notifications
    }
} else {
    echo "";
}