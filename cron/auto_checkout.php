<?php
/**
 * Auto Check-out Cron Job
 * Run this script daily at 5:00 PM (Harare time) to automatically check out employees
 * Add to crontab: 0 17 * * * php /path/to/cron/auto_checkout.php
 * 
 * Note: This script uses Africa/Harare timezone
 */

// Ensure timezone is set
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Africa/Harare');
}
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(APP_TIMEZONE);
}

require_once __DIR__ . '/../DB_connection.php';
require_once __DIR__ . '/../app/Model/Attendance.php';

// Use the centralized auto_checkout_all method
$result = Attendance::auto_checkout_all($conn);

if ($result['success']) {
    echo $result['message'] . "\n";
} else {
    echo $result['message'] . "\n";
}
