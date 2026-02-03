<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin' || !isset($_POST['repayment_id'])) {
    header("Location: ../login.php");
    exit();
}

$result = Attendance::verify_repayment(
    $conn,
    $_POST['repayment_id'],
    $_SESSION['employee_id'],
    $_POST['action'],
    $_POST['admin_comment']
);

if ($result['success']) {
    header("Location: ../index.php?success=".urlencode($result['message']));
} else {
    header("Location: ../index.php?error=".urlencode($result['message']));
}
exit();
?>