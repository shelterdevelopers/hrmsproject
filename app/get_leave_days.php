<?php
// app/get_leave_days.php
session_start();
// Use require_once for critical files like DB connection
require_once "../DB_connection.php"; 
require_once "Model/User.php";      

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $employee = get_user_by_id($conn, $_SESSION['employee_id']);
    if (!$employee) {
        throw new Exception("Employee not found.");
    }

    // Return the remaining balances for each type
    $data = [
        'normal_leave_days'            => (int)($employee['normal_leave_days'] ?? 0),
        'special_leave_days_remaining' => (int)($employee['special_leave_days_remaining'] ?? 0),
        'sick_leave_days_remaining'    => (int)($employee['sick_leave_days_remaining'] ?? 0),
        'maternity_leave_days_remaining' => (int)($employee['maternity_leave_days_remaining'] ?? 0),
        // Keep entitlements if needed elsewhere, but JS now uses remaining days
        'entitlements' => [ 
            'special'   => 12,
            'sick'      => 90,
            'maternity' => 98
        ]
    ];

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(['error' => $e->getMessage()]);
}