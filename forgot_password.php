<?php
if (!defined('BASE_URL')) {
    require_once 'config.php';
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
            --shelter-dark-blue: #1e3a5f;
            --shelter-light-blue: #4a90c2;
            --shelter-light-blue-bright: #6ba8d4;
            --shelter-dark-gray: #4a4a4a;
        }
        .forgot-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(74, 144, 194, 0.3);
            border: 3px solid var(--shelter-light-blue);
        }
        .forgot-password-container h3 {
            color: var(--shelter-light-blue);
            font-weight: 700;
            margin-bottom: 20px;
        }
        .forgot-password-container .btn-primary {
            background: var(--shelter-light-blue);
            border-color: var(--shelter-light-blue);
        }
        .forgot-password-container .btn-primary:hover {
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
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-body">
    <div class="forgot-password-container">
        <h3 class="text-center">Forgot Password</h3>
        <p class="text-center text-muted mb-4">Enter your username or email address and we'll send you instructions to reset your password.</p>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['info'])): ?>
            <div class="alert alert-info" role="alert">
                <?= htmlspecialchars($_GET['info']) ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info" role="alert">
            <h5><i class="fa fa-info-circle"></i> Password Reset Assistance</h5>
            <p>If you've forgotten your password, please contact your <strong>HR Manager</strong> or <strong>System Administrator</strong> for assistance.</p>
            <p>They can reset your password and provide you with temporary login credentials.</p>
            <hr>
            <p class="mb-0"><strong>Contact Information:</strong></p>
            <p class="mb-0">- HR Department</p>
            <p class="mb-0">- System Administrator</p>
        </div>
        
        <form method="POST" action="app/request_password_reset.php">
            <div class="mb-3">
                <label for="username_email" class="form-label">Username or Email (Optional - for verification)</label>
                <input type="text" class="form-control" id="username_email" name="username_email" 
                       placeholder="Enter your username or email address">
                <small class="form-text text-muted">This helps verify your identity when contacting HR.</small>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Request Assistance</button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
