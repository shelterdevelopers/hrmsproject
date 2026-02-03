<?php 
session_start();
if (isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ["admin", "hr", "managing_director"], true)) {
    include "../DB_connection.php";
    include "Model/Notification.php";
    include "Model/User.php";
    
    if (isset($_POST['message'])) {
        $message = $_POST['message'];
        // Company-wide announcement (HR/MD/Admin)
        $type = 'company_announcement';
        $posted_by = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : null;

        // Fetch all users
        $users = get_all_users($conn);
        foreach ($users as $user) {
            $sent = false;
            if (function_exists('insert_notification_with_poster') && $posted_by !== null) {
                try {
                    $sent = insert_notification_with_poster($conn, $message, (int) $user['employee_id'], $type, $posted_by);
                } catch (Throwable $e) {}
            }
            if (!$sent) {
                insert_notification($conn, [$message, $user['employee_id'], $type]);
            }
        }

        $em = "Notification sent to all users.";
        header("Location: ../notify_all_form.php");
        exit();
    }
} else { 
    $em = "First login";
    header("Location: login.php?error=$em");
    exit();
}
?>