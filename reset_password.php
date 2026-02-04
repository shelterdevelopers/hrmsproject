<?php
session_start();
if (!defined('BASE_URL')) require_once __DIR__ . '/load_config.php';
require_once "DB_connection.php";

$token = $_GET['token'] ?? '';
$error = '';
$valid_token = false;
$user_id = null;

if (!empty($token)) {
    // Verify token exists and is not expired
    $sql = "SELECT employee_id, first_name, last_name FROM employee 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW() 
            AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $valid_token = true;
        $user_id = $user['employee_id'];
    } else {
        $error = "Invalid or expired reset token. Please request a new password reset.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Update password and clear reset token
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE employee SET 
                password = ?,
                password_reset_token = NULL,
                password_reset_expires = NULL
                WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            header("Location: login.php?success=" . urlencode("Password reset successfully. Please login with your new password."));
            exit();
        } else {
            $error = "Failed to reset password. Please try again or contact HR.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        :root {
            --shelter-light-blue: #4a90c2;
            --shelter-light-blue-bright: #6ba8d4;
        }
        .reset-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(74, 144, 194, 0.3);
            border: 3px solid var(--shelter-light-blue);
        }
        .reset-password-container h3 {
            color: var(--shelter-light-blue);
            font-weight: 700;
            margin-bottom: 20px;
        }
        .reset-password-container .btn-primary {
            background: var(--shelter-light-blue);
            border-color: var(--shelter-light-blue);
        }
        .reset-password-container .btn-primary:hover {
            background: var(--shelter-light-blue-bright);
            border-color: var(--shelter-light-blue-bright);
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: var(--shelter-light-blue);
            text-decoration: none;
        }
    </style>
</head>
<body class="login-body">
    <div class="reset-password-container">
        <h3 class="text-center">Reset Password</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($valid_token): ?>
            <p class="text-center text-muted mb-4">Enter your new password below.</p>
            <form method="POST">
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required 
                           minlength="8" placeholder="Minimum 8 characters">
                    <small class="form-text text-muted">Password must be at least 8 characters long</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                           minlength="8" placeholder="Confirm your new password">
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <p>This password reset link is invalid or has expired.</p>
                <p class="mb-0"><a href="forgot_password.php">Request a new password reset</a></p>
            </div>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
