<?php 
session_start();
if (!defined('BASE_URL')) require_once __DIR__ . '/../load_config.php';
require_once __DIR__ . "/Model/RoleHelper.php";
if (isset($_SESSION['employee_id'])) {
    include "../DB_connection.php";
    $can_post = RoleHelper::is_admin($conn, $_SESSION['employee_id'])
        || RoleHelper::is_hr($conn, $_SESSION['employee_id'])
        || RoleHelper::is_managing_director($conn, $_SESSION['employee_id']);
}
if (!empty($can_post)) {
    include "Model/Notification.php";
    include "Model/User.php";
    
    if (isset($_POST['message'])) {
        $message = $_POST['message'];
        // Company-wide announcement (HR/MD/Admin)
        $type = 'company_announcement';
        $posted_by = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : null;

        // Fetch all users (including the poster so they can see their own announcement)
        $users = get_all_users($conn);
        foreach ($users as $user) {
            $sent = false;
            if (function_exists('insert_notification_with_poster') && $posted_by !== null) {
                try {
                    $sent = insert_notification_with_poster($conn, $message, (int) $user['employee_id'], $type, $posted_by);
                } catch (Throwable $e) {
                    error_log("Error sending company announcement with poster: " . $e->getMessage());
                }
            }
            if (!$sent) {
                try {
                    insert_notification($conn, [$message, $user['employee_id'], $type]);
                } catch (Exception $e) {
                    error_log("Error sending company announcement: " . $e->getMessage());
                }
            }
        }

        $em = "Notification sent to all users.";
        header("Location: ../notify_all_form.php");
        exit();
    }
} else { 
    $em = "First login";
    header("Location: ../login.php?error=" . urlencode($em));
    exit();
}
?>