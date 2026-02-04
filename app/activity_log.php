<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/ActivityLog.php";
require_once "Model/RoleHelper.php";
require_once "Model/Attendance.php";
require_once "Model/Appraisal.php";

// Only Managing Director can view activity logs
if (!isset($_SESSION['employee_id']) || !RoleHelper::is_managing_director($conn, $_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get overview period (default: last 7 days)
$period = $_GET['period'] ?? '7days';
$date_from = null;
$date_to = date('Y-m-d');

switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        break;
    case '7days':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $date_from = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'custom':
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        break;
}

// Get key metrics
$today_activity_count = ActivityLog::get_activity_count($conn, ['date_from' => date('Y-m-d')]);
$week_activity_count = ActivityLog::get_activity_count($conn, ['date_from' => $date_from, 'date_to' => $date_to]);

// Get pending items requiring MD attention
$pending_manager_leaves = [];
$pending_hr_leaves = [];
$pending_manager_loans = [];
$pending_fm_loans = [];

// Check if md_approval_status column exists
$check_md_column = "SHOW COLUMNS FROM applications LIKE 'md_approval_status'";
$stmt_check = $conn->query($check_md_column);
$md_column_exists = $stmt_check->rowCount() > 0;

if ($md_column_exists) {
    // Managers' leave applications needing MD approval
    $sql = "SELECT COUNT(*) FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.type = 'leave' AND a.status = 'pending'
            AND (e.role = 'manager' OR e.role LIKE '%_manager')
            AND a.manager_approval_status = 'approved'
            AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    $pending_manager_leaves_count = $conn->query($sql)->fetchColumn();
    
    // HR leave applications needing MD approval
    $sql = "SELECT COUNT(*) FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.type = 'leave' AND a.status = 'pending'
            AND (e.role = 'hr' OR e.role = 'hr_manager')
            AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    $pending_hr_leaves_count = $conn->query($sql)->fetchColumn();
    
    // Managers' loan applications needing MD approval
    $sql = "SELECT COUNT(*) FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.type = 'loan' AND a.status = 'pending'
            AND (e.role = 'manager' OR e.role LIKE '%_manager')
            AND a.hr_approval_status = 'approved'
            AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    $pending_manager_loans_count = $conn->query($sql)->fetchColumn();
    
    // Finance Manager loan applications needing MD approval
    $sql = "SELECT COUNT(*) FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.type = 'loan' AND a.status = 'pending'
            AND (e.role = 'manager' OR e.role = 'finance_manager')
            AND e.department = 'Finance'
            AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')";
    $pending_fm_loans_count = $conn->query($sql)->fetchColumn();
} else {
    $pending_manager_leaves_count = 0;
    $pending_hr_leaves_count = 0;
    $pending_manager_loans_count = 0;
    $pending_fm_loans_count = 0;
}

$total_pending_approvals = $pending_manager_leaves_count + $pending_hr_leaves_count + $pending_manager_loans_count + $pending_fm_loans_count;

// Get pending appraisals for managers
$pending_appraisals = Appraisal::get_pending_appraisals_for_managers($conn);
$pending_appraisals_count = is_array($pending_appraisals) ? count($pending_appraisals) : 0;

// Get department activity breakdown
$stats_by_dept = ActivityLog::get_activity_by_department($conn, $date_from, $date_to);
$stats_by_type = ActivityLog::get_activity_by_type($conn, $date_from, $date_to);

// Get high-level insights (not detailed activities - executive overview)
// Get activity summary by day for the period
$sql_daily_summary = "SELECT 
    DATE(created_at) as activity_date,
    COUNT(*) as activity_count,
    COUNT(DISTINCT user_id) as unique_users
FROM activity_logs
WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
GROUP BY DATE(created_at)
ORDER BY activity_date DESC
LIMIT 7";
$stmt_daily = $conn->prepare($sql_daily_summary);
$stmt_daily->execute([$date_from, $date_to]);
$daily_summary = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

// Get top active departments
$top_depts = array_slice($stats_by_dept, 0, 5);

// Get top activity types
$top_types = array_slice($stats_by_type, 0, 5);

// Get activity growth (compare this week to last week)
$last_week_start = date('Y-m-d', strtotime('-14 days'));
$last_week_end = date('Y-m-d', strtotime('-8 days'));
$last_week_count = ActivityLog::get_activity_count($conn, ['date_from' => $last_week_start, 'date_to' => $last_week_end]);
$activity_growth = $last_week_count > 0 ? round((($week_activity_count - $last_week_count) / $last_week_count) * 100, 1) : 0;

// Detailed activity feed: applications, approvals, appraisals. Exclude attendance (MD does not need check-in/out detail).
$recent_activities = ActivityLog::get_all_activities($conn, 100, 0, [
    'date_from' => $date_from,
    'date_to' => $date_to,
    'exclude_activity_types' => [
        'attendance_auto_checkout',
        'attendance',
        'attendance_checkin',
        'attendance_checkout',
        'team_attendance',
    ],
]);

include "views/activity_log_view.php";
