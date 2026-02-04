<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only serve this endpoint for AJAX requests (prevents stray HTML if accidentally included)
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    // Fail silently – this script is meant to be used via the header bell AJAX only
    return;
}

if (isset($_SESSION['role'], $_SESSION['employee_id'])) {
    if (!defined('BASE_URL')) require_once __DIR__ . '/../load_config.php';
    include "../DB_connection.php";
    include "Model/Notification.php";
    require_once "Model/RoleHelper.php";

    // Exclude attendance/team_attendance/activity for MD, HR, and Finance Manager
    $emp_id = (int)$_SESSION['employee_id'];
    $is_managing_director = RoleHelper::is_managing_director($conn, $emp_id);
    $is_hr = RoleHelper::is_hr($conn, $emp_id);
    $is_finance_manager = (strcasecmp(trim(RoleHelper::get_department($conn, $emp_id) ?? ''), RoleHelper::DEPT_FINANCE) === 0 && RoleHelper::is_manager($conn, $emp_id));
    $exclude_attendance = $is_managing_director || $is_hr || $is_finance_manager;

    // Bell dropdown shows only UNREAD notifications
    $notifications = get_unread_notifications($conn, $_SESSION['employee_id'], $exclude_attendance);

    if ($notifications == 0) { ?>
        <li>
            <a href="#">
                You have no new notifications.
            </a>
        </li>
    <?php } else {
        foreach ($notifications as $notification) {
            $msg = $notification['message'];
            // Fix old notifications that had a blank applicant name ("Loan application from  requires...")
            if (preg_match('/Loan application from\s+requires/i', $msg)) {
                $msg = preg_replace('/Loan application from\s+requires/i', 'Loan application from (applicant name unknown) requires', $msg);
            }
            ?>
            <li>
                <a href="<?= BASE_URL ?>app/notification-read.php?notification_id=<?= $notification['id'] ?>">
                    <mark><?= htmlspecialchars($notification['type']) ?></mark>:
                    <?= htmlspecialchars($msg) ?>
                    &nbsp;&nbsp;<small><?= htmlspecialchars($notification['date']) ?></small>
                </a>
            </li>
        <?php }
    }
}
?>