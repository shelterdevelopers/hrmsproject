<?php
session_start();
require_once __DIR__ . '/../DB_connection.php';
require_once __DIR__ . '/Model/Attendance.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['employee_id']) || !isset($_GET['month']) || !isset($_GET['year'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Missing parameters']));
}

$employee_id = (int)$_GET['employee_id'];
$month = (int)$_GET['month'];
$year = (int)$_GET['year'];

try {
    $attendanceData = Attendance::get_employee_attendance_json($conn, $employee_id, $month, $year);
    header('Content-Type: application/json');
    echo json_encode($attendanceData);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}