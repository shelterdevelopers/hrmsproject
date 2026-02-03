<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $repayment_id = $_POST['repayment_id'];
    $action = $_POST['action'];
    $comment = $_POST['comment'];
    
    $result = Attendance::verify_repayment(
        $conn,
        $repayment_id,
        $_SESSION['employee_id'],
        $action,
        $comment
    );
    
    if ($result['success']) {
        header("Location: admin_payments.php?success=".urlencode($result['message']));
    } else {
        header("Location: admin_payments.php?error=".urlencode($result['message']));
    }
    exit();
}

// Get pending payments
$pending_payments = Attendance::get_pending_repayments($conn);
include "../admin_payments_view.php";
?>