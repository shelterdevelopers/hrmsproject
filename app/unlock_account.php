<?php
/**
 * Admin Account Unlock Handler
 * Allows Admin to unlock locked user accounts
 */
session_start();
require_once "../DB_connection.php";
require_once "Model/User.php";
require_once "Model/RoleHelper.php";
require_once "Model/Notification.php";

// Check authentication - Admin only
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);

if (!isset($_SESSION['employee_id']) || !$is_admin) {
    header("Location: ../login.php?error=Access+denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    
    if (empty($employee_id)) {
        header("Location: ../user.php?error=" . urlencode("Employee ID is required"));
        exit();
    }
    
    // Get employee details
    $employee = get_user_by_id($conn, $employee_id);
    if (!$employee) {
        header("Location: ../user.php?error=" . urlencode("Employee not found"));
        exit();
    }
    
    // Unlock account
    $sql = "UPDATE employee SET 
            failed_login_attempts = 0,
            account_locked_until = NULL,
            last_failed_login = NULL
            WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$employee_id])) {
        // Notify employee
        $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
        $admin_name = get_user_by_id($conn, $_SESSION['employee_id'])['first_name'] . ' ' . 
                     get_user_by_id($conn, $_SESSION['employee_id'])['last_name'];
        $message = "Your account has been unlocked by System Admin ({$admin_name}). You can now log in again.";
        create_notification($conn, $employee_id, $message, 'account_unlocked');
        
        header("Location: ../user.php?success=" . urlencode("Account unlocked successfully for {$employee_name}"));
    } else {
        header("Location: ../user.php?error=" . urlencode("Failed to unlock account"));
    }
    exit();
} else {
    header("Location: ../user.php");
    exit();
}
?>
