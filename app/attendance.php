<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";
require_once "Model/User.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['check_in'])) {
        $success = Attendance::check_in($conn, $_SESSION['employee_id']);
    } elseif (isset($_POST['check_out'])) {
        $success = Attendance::check_out($conn, $_SESSION['employee_id']);
    } elseif (isset($_POST['submit_leave'])) {
        $result = Attendance::apply_leave(
            $conn, 
            $_SESSION['employee_id'], 
            $_POST['start_date'], 
            $_POST['reason'],
            $_POST['leave_type'],
            $_POST['end_date']
        );
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get attendance records
$attendance = Attendance::get_attendance($conn, $_SESSION['employee_id'], $month, $year);

// Check if user is Managing Director (MD doesn't apply for leaves/loans)
require_once "Model/RoleHelper.php";
$is_managing_director = RoleHelper::is_managing_director($conn, $_SESSION['employee_id']);

// Get leave balance (only for non-MD users)
$leave_balance = null;
if (!$is_managing_director) {
    $sql = "SELECT normal_leave_days FROM employee WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['employee_id']]);
    $leave_balance = $stmt->fetchColumn();
}

include "views/attendance_view.php";