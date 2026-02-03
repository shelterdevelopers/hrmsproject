<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Attendance.php";

if (!isset($_SESSION['employee_id']) || !isset($_POST['application_id'])) {
    header("Location: ../login.php");
    exit();
}

$application_id = $_POST['application_id'];
$payment_method = $_POST['payment_method'] ?? 'Unknown';
$employee_id = $_SESSION['employee_id'];

// Get the application to calculate due amount
$application = Attendance::get_application_by_id($conn, $application_id);
if (!$application) {
    header("Location: ../applications.php?error=Loan+application+not+found");
    exit();
}

// Calculate and record repayment
$due_amount = Attendance::calculate_due_amount($application);
$result = Attendance::record_repayment(
    $conn,
    $application_id,
    $due_amount,
    $payment_method,
    $employee_id
);

if ($result['success']) {
    header("Location: ../app/applications.php?success=Payment+recorded+awaiting+verification");
} else {
    header("Location: ../app/applications.php?error=" . urlencode($result['message']));
}
exit();
?>