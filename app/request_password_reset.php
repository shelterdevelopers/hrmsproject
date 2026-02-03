<?php
session_start();
require_once "../DB_connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_email = trim($_POST['username_email'] ?? '');
    
    if (empty($username_email)) {
        header("Location: ../forgot_password.php?error=" . urlencode("Please enter your username or email"));
        exit();
    }
    
    // Find user by username or email
    $sql = "SELECT employee_id, username, email_address, first_name, last_name FROM employee 
            WHERE (username = ? OR email_address = ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username_email, $username_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if user exists (security best practice)
        header("Location: ../forgot_password.php?success=" . urlencode("If an account exists with that username/email, a password reset link has been sent."));
        exit();
    }
    
    // Password reset is handled by Admin/HR - direct user to contact them
    // This is more secure and doesn't require email setup
    header("Location: ../forgot_password.php?info=" . urlencode("Please contact your HR Manager or System Administrator to reset your password. They can assist you with password recovery."));
    exit();
} else {
    header("Location: ../forgot_password.php");
    exit();
}
?>
