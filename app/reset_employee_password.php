<?php
/**
 * Admin/HR Password Reset Handler
 * Allows Admin/HR to reset employee passwords
 */
session_start();
require_once "../DB_connection.php";
require_once "Model/User.php";
require_once "Model/RoleHelper.php";
require_once "Model/ActivityLog.php";

// Check authentication - Admin only (password resets are admin responsibility)
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);

if (!isset($_SESSION['employee_id']) || !$is_admin) {
    header("Location: ../login.php?error=Access+denied.+Only+System+Admin+can+reset+passwords");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    if (empty($employee_id)) {
        header("Location: ../user.php?error=" . urlencode("Employee ID is required"));
        exit();
    }
    
    if (empty($new_password)) {
        header("Location: ../user.php?error=" . urlencode("New password is required"));
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        header("Location: ../user.php?error=" . urlencode("Passwords do not match"));
        exit();
    }
    
    if (strlen($new_password) < 8) {
        header("Location: ../user.php?error=" . urlencode("Password must be at least 8 characters long"));
        exit();
    }
    
    // Get employee details
    $employee = get_user_by_id($conn, $employee_id);
    if (!$employee) {
        header("Location: ../user.php?error=" . urlencode("Employee not found"));
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $sql = "UPDATE employee SET password = ? WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$hashed_password, $employee_id])) {
        // Log the activity
        $admin_name = get_user_by_id($conn, $_SESSION['employee_id'])['first_name'] . ' ' . 
                     get_user_by_id($conn, $_SESSION['employee_id'])['last_name'];
        $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
        
        ActivityLog::log(
            $conn,
            'password_reset',
            "Password reset for {$employee_name} by System Admin ({$admin_name})",
            $_SESSION['employee_id'],
            $employee_id
        );
        
        // Send notification to employee
        require_once "Model/Notification.php";
        $message = "Your password has been reset by System Administrator. Please login with your new password and change it immediately.";
        create_notification($conn, $employee_id, $message, 'password_reset');
        
        header("Location: ../user.php?success=" . urlencode("Password reset successfully for {$employee_name}. Temporary password: [hidden for security]"));
    } else {
        header("Location: ../user.php?error=" . urlencode("Failed to reset password"));
    }
    exit();
} else {
    header("Location: ../user.php");
    exit();
}
?>
