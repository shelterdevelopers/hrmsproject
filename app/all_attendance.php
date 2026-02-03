<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// HR only (department managers already use department_attendance.php)
if (!RoleHelper::is_hr($conn, $_SESSION['employee_id'])) {
    header("Location: ../index.php?error=Access+denied");
    exit();
}

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
$selected_department = $_GET['department'] ?? '';
$selected_employee = $_GET['employee_id'] ?? '';

// Departments list
$departments = $conn->query("SELECT DISTINCT department FROM employee WHERE department IS NOT NULL AND department != '' ORDER BY department")
    ->fetchAll(PDO::FETCH_COLUMN);

// Employees list (filtered by department if provided, excluding Managing Director)
$employee_list = [];
$params = [];
$sql_emp = "SELECT employee_id, first_name, last_name, job_title, department
            FROM employee
            WHERE status = 'active' AND role != ?";
$params[] = RoleHelper::ROLE_MANAGING_DIRECTOR;

if ($selected_department !== '') {
    $sql_emp .= " AND department = ?";
    $params[] = $selected_department;
}

$sql_emp .= " ORDER BY department, first_name, last_name";
$stmt_emp = $conn->prepare($sql_emp);
$stmt_emp->execute($params);
$employee_list = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

// Attendance for selected employee or all visible employees
$attendance_data = [];
if ($selected_employee !== '') {
    $selected_employee = (int)$selected_employee;
    $attendance_data[$selected_employee] = Attendance::get_attendance($conn, $selected_employee, $month, $year);
} else {
    foreach ($employee_list as $emp) {
        $emp_id = (int)$emp['employee_id'];
        $attendance_data[$emp_id] = Attendance::get_attendance($conn, $emp_id, $month, $year);
    }
}

// Basic stats per employee
// Get number of days in month (native PHP, no calendar extension needed)
$days_in_month = (int)(new DateTime("$year-$month-01"))->format('t');
$stats = [];
foreach ($employee_list as $emp) {
    $emp_id = (int)$emp['employee_id'];
    $attendance = $attendance_data[$emp_id] ?? [];
    $present_count = count(array_filter($attendance, fn($a) => ($a['status'] ?? '') === 'present'));
    $late_count = count(array_filter($attendance, fn($a) => ($a['status'] ?? '') === 'late'));
    $leave_count = count(array_filter($attendance, fn($a) => ($a['status'] ?? '') === 'leave'));
    $holiday_count = count(array_filter($attendance, fn($a) => ($a['status'] ?? '') === 'holiday'));
    // Absent = total days - days with any attendance record (present, late, leave, holiday)
    // This ensures leave days are NOT counted as absent
    $absent_count = $days_in_month - ($present_count + $late_count + $leave_count + $holiday_count);
    
    $stats[$emp_id] = [
        'present' => $present_count,
        'late' => $late_count,
        'absent' => max(0, $absent_count), // Ensure non-negative
        'leave' => $leave_count,
    ];
}

include "views/all_attendance_view.php";

