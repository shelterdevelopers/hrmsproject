<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";
require_once "Model/User.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is a manager
$is_manager = RoleHelper::is_manager($conn, $_SESSION['employee_id']);
$department = RoleHelper::get_department($conn, $_SESSION['employee_id']);

if (!$is_manager) {
    header("Location: ../index.php?error=Access+denied");
    exit();
}

// Handle month_year input (format: YYYY-MM) or separate month/year
if (isset($_GET['month_year']) && !empty($_GET['month_year'])) {
    $date_parts = explode('-', $_GET['month_year']);
    $year = (int)$date_parts[0];
    $month = (int)$date_parts[1];
} else {
    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');
}
$selected_employee = $_GET['employee_id'] ?? null;

// Get department employees (excluding managers - they have their own "My Attendance" tab)
$department_employees = RoleHelper::get_department_employees($conn, $department);

// Filter out managers - they should not appear in departmental attendance
// Managers have their own "My Attendance" tab
$employee_list = [];
$employee_ids = [];
if (!empty($department_employees)) {
    $placeholders = str_repeat('?,', count($department_employees) - 1) . '?';
    // Get employee details and filter out managers
    $sql = "SELECT employee_id, first_name, last_name, job_title, role 
            FROM employee 
            WHERE employee_id IN ($placeholders) 
            ORDER BY first_name, last_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute($department_employees);
    $all_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out managers (role = 'manager' or role contains '_manager')
    foreach ($all_employees as $emp) {
        $role_lower = strtolower($emp['role'] ?? '');
        $is_manager_role = ($role_lower === 'manager' || 
                           strpos($role_lower, '_manager') !== false ||
                           $role_lower === 'finance_manager' ||
                           $role_lower === 'hr_manager' ||
                           $role_lower === 'managing_director');
        // Also check if they have direct reports (someone has them as manager_id)
        if (!$is_manager_role) {
            $check_reports = "SELECT COUNT(*) FROM employee WHERE manager_id = ? AND status = 'active'";
            $stmt_check = $conn->prepare($check_reports);
            $stmt_check->execute([$emp['employee_id']]);
            $has_reports = $stmt_check->fetchColumn() > 0;
            if (!$has_reports) {
                $employee_ids[] = $emp['employee_id'];
                $employee_list[] = $emp;
            }
        }
    }
}

// Get attendance for selected employee or all employees
$attendance_data = [];
if ($selected_employee && in_array($selected_employee, $employee_ids)) {
    $attendance_data[$selected_employee] = Attendance::get_attendance($conn, $selected_employee, $month, $year);
} else {
    foreach ($employee_ids as $emp_id) {
        $attendance_data[$emp_id] = Attendance::get_attendance($conn, $emp_id, $month, $year);
    }
}

// Calculate statistics
// Get number of days in month (native PHP, no calendar extension needed)
$days_in_month = (int)(new DateTime("$year-$month-01"))->format('t');
$stats = [];
foreach ($employee_list as $emp) {
    $emp_id = $emp['employee_id'];
    $attendance = $attendance_data[$emp_id] ?? [];
    $present_count = count(array_filter($attendance, fn($a) => $a['status'] == 'present'));
    $late_count = count(array_filter($attendance, fn($a) => $a['status'] == 'late'));
    $leave_count = count(array_filter($attendance, fn($a) => $a['status'] == 'leave'));
    $holiday_count = count(array_filter($attendance, fn($a) => $a['status'] == 'holiday'));
    // Absent = total days - days with any attendance record (present, late, leave, holiday)
    // This ensures leave days are NOT counted as absent
    $absent_count = $days_in_month - ($present_count + $late_count + $leave_count + $holiday_count);
    
    $stats[$emp_id] = [
        'present' => $present_count,
        'late' => $late_count,
        'absent' => max(0, $absent_count), // Ensure non-negative
        'leave' => $leave_count
    ];
}

include "views/department_attendance_view.php";
