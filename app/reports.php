<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/ActivityLog.php";
require_once "Model/RoleHelper.php";

// Only Managing Director can view reports
if (!isset($_SESSION['employee_id']) || !RoleHelper::is_managing_director($conn, $_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get date range (default to last 30 days)
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get statistics
$stats_by_dept = ActivityLog::get_activity_by_department($conn, $date_from, $date_to);
$stats_by_type = ActivityLog::get_activity_by_type($conn, $date_from, $date_to);

// Get total activity count
$total_activities = ActivityLog::get_activity_count($conn, [
    'date_from' => $date_from,
    'date_to' => $date_to
]);

include "views/reports_view.php";
