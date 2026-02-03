<?php
// /cron/yearly_leave_reset.php
include_once __DIR__ . '/../DB_connection.php';

try {
    // Reset remaining balances to their full entitlements at the start of the year
    $sql = "UPDATE employee 
            SET special_leave_days_remaining = 12, 
                sick_leave_days_remaining = 90, 
                maternity_leave_days_remaining = 98";
    $conn->exec($sql);
    echo "Yearly leave balances (Special, Sick, Maternity) have been reset for all employees on " . date('Y-m-d');
} catch (PDOException $e) {
    error_log("Yearly leave reset failed: " . $e->getMessage());
}