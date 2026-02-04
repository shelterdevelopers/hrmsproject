<?php
session_start();
if (!defined('BASE_URL')) require_once 'config.php';
require_once "app/Model/RoleHelper.php";
if (isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    $can_post = RoleHelper::is_admin($conn, $_SESSION['employee_id'])
        || RoleHelper::is_hr($conn, $_SESSION['employee_id'])
        || RoleHelper::is_managing_director($conn, $_SESSION['employee_id']);
}
if (!empty($can_post)) {
    include "app/Model/User.php";
    $users = get_all_users($conn);
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Send Notification · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>

    <body>
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>

            <div class="section-1">

                <div class="title-2">
                    <h2>Send Company Announcement</h2>
                </div>

                <form method="POST" action="app/notify_all.php" class="form-1" onsubmit="return confirm('Are you sure you want to send this company announcement to everyone?');">
                    <div class="input-holder">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required class="input-1" rows="6"></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Send Announcement</button>
                </form>

            </div>
        </div>
    </body>

    </html>
<?php } else {
    header("Location: login.php");
    exit();
} ?>