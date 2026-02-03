<?php
ob_start();
session_start();
if (isset($_POST['user_name']) && isset($_POST['password'])) {
    include "../DB_connection.php";
    include "../app/Model/Attendance.php"; // Add this line

    function validate_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $user_name = validate_input($_POST['user_name']);
    $password = validate_input($_POST['password']);

    // Check for empty input
    if (empty($user_name)) {
        $em = "User name is required";
        header("Location: ../login.php?error=" . urlencode($em));
        exit();
    } else if (empty($password)) {
        $em = "Password is required";
        header("Location: ../login.php?error=" . urlencode($em));
        exit();
    } else {
        // Prepare and execute query
        $sql = "SELECT * FROM employee WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_name]);

        // Check if user exists
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            // Check if account is pending approval
            if (strtolower($user['status']) === 'pending') {
                $em = "Your account is pending admin approval";
                header("Location: ../login.php?error=" . urlencode($em));
                exit();
            }
            
            // Check if account is locked (locked until System Admin unlocks - no auto-expiry)
            $account_locked_until = $user['account_locked_until'] ?? null;
            if ($account_locked_until && strtotime($account_locked_until) > time()) {
                $em = "Account is locked due to multiple failed login attempts. Contact System Admin to unlock your account.";
                header("Location: ../login.php?error=" . urlencode($em));
                exit();
            }

            $usernameDb = $user['username'];
            $passwordDb = $user['password'];
            $role = $user['role'];
            $id = $user['employee_id'];
            $failed_attempts = (int)($user['failed_login_attempts'] ?? 0);
            
            // Maximum failed attempts before lockout (5 attempts)
            $MAX_FAILED_ATTEMPTS = 5;
            // Lock until System Admin unlocks (no auto-expiry) - use far-future date
            $LOCK_UNTIL_FURTHER_NOTICE = '2099-12-31 23:59:59';

            // Verify the password
            if (password_verify($password, $passwordDb)) {
                // Successful login - reset failed attempts
                if ($failed_attempts > 0) {
                    $sql_reset = "UPDATE employee SET failed_login_attempts = 0, account_locked_until = NULL, last_failed_login = NULL WHERE employee_id = ?";
                    $stmt_reset = $conn->prepare($sql_reset);
                    $stmt_reset->execute([$id]);
                }
                // Check for missing check-outs from previous day
                $sql = "SELECT attendance_id FROM attendance 
                    WHERE employee_id = ? 
                    AND date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                    AND check_out IS NULL";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    // Auto-checkout yesterday at their scheduled end time or default
                    $end_time = $user['work_hours_end'] ?? '17:00:00';
                    //force_check_out($conn, $stmt->fetchColumn(), $end_time);

                    // Optional: Add a notification
                    $_SESSION['warning'] = "You were automatically checked out yesterday at {$end_time}";
                }

                // Successful login - Record attendance
                $attendance_recorded = Attendance::check_in($conn, $id);

                // Log activity
                require_once "../app/Model/ActivityLog.php";
                ActivityLog::log(
                    $conn,
                    'attendance',
                    "User logged in and checked in",
                    $id,
                    null
                );

                // Set session variables
                //$_SESSION['role'] = ucfirst($role); // Makes 'admin' → 'Admin'
                $_SESSION['role'] = $role;
                $_SESSION['employee_id'] = $id;
                $_SESSION['username'] = $usernameDb;

                // Check if attendance was recorded successfully
                if ($attendance_recorded) {
                    $_SESSION['checked_in'] = true;
                } else {
                    // Check if already checked in today
                    $sql = "SELECT check_in FROM attendance 
                            WHERE employee_id = ? AND date = CURDATE()";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['checked_in'] = true;
                    }
                }

                header("Location: ../index.php");
                exit();
            } else {
                // Incorrect password - increment failed attempts
                $new_failed_attempts = $failed_attempts + 1;
                $lock_until = null;
                
                // Lock account if max attempts reached - locked until System Admin unlocks
                if ($new_failed_attempts >= $MAX_FAILED_ATTEMPTS) {
                    $lock_until = $LOCK_UNTIL_FURTHER_NOTICE;
                    $em = "Too many failed login attempts. Your account has been locked. Contact System Admin to unlock your account.";
                } else {
                    $remaining = $MAX_FAILED_ATTEMPTS - $new_failed_attempts;
                    $em = "Incorrect username or password. {$remaining} attempt(s) remaining before account lockout.";
                }
                
                // Update failed login attempts
                $sql_update = "UPDATE employee SET 
                              failed_login_attempts = ?,
                              account_locked_until = ?,
                              last_failed_login = NOW()
                              WHERE employee_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$new_failed_attempts, $lock_until, $id]);
                
                header("Location: ../login.php?error=" . urlencode($em));
                exit();
            }
        } else {
            // User not found
            $em = "Incorrect username or password";
            header("Location: ../login.php?error=" . urlencode($em));
            exit();
        }
    }
} else {
    $em = "Unknown error occurred";
    header("Location: ../login.php?error=" . urlencode($em));
    exit();
}