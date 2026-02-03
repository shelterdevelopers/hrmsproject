<?php 
session_start();

// Record check-out time on logout (updates throughout the day each time they log out; no notifications)
if (isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    include "app/Model/Attendance.php";
    include "app/Model/ActivityLog.php";
    
    Attendance::record_checkout_on_logout($conn, $_SESSION['employee_id']);
    
    ActivityLog::log(
        $conn,
        'attendance',
        "User logged out",
        $_SESSION['employee_id'],
        null
    );
}

// Clear session
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>