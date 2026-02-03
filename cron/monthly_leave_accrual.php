<?php
// /cron/monthly_leave_accrual.php
include_once __DIR__ . '/../DB_connection.php';

try {
    // Add 2 days to the normal_leave_days for every active employee
    $sql = "UPDATE employee SET normal_leave_days = normal_leave_days + 2 WHERE status = 'Active'";
    $conn->exec($sql);
    echo "Accrued 2 normal leave days for all active employees on " . date('Y-m-d');
} catch (PDOException $e) {
    error_log("Monthly leave accrual failed: " . $e->getMessage());
}