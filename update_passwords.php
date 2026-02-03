<?php
/**
 * Password Update Script
 * Run this once to update all test user passwords to "password123"
 * Usage: php update_passwords.php
 * 
 * Access via browser: http://localhost:8080/update_passwords.php
 * Or via command line: php update_passwords.php
 */

require_once 'DB_connection.php';

$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<!DOCTYPE html><html><head><title>Update Passwords</title></head><body><pre>";
echo "Updating all user passwords to: password123\n";
echo "Generated Hash: $hashed_password\n\n";

try {
    // Update all employees
    $sql = "UPDATE employee SET password = ? WHERE employee_id > 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$hashed_password]);
    
    $affected = $stmt->rowCount();
    echo "✓ Successfully updated $affected user passwords.\n\n";
    
    // Verify the hash works
    if (password_verify($password, $hashed_password)) {
        echo "✓ Password hash verified successfully!\n\n";
    } else {
        echo "✗ ERROR: Password hash verification failed!\n\n";
    }
    
    echo "Test credentials (all use password: password123):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Managing Director: mdirector / password123\n";
    echo "HR Manager:        hrmanager / password123\n";
    echo "Sales Manager:     salesmanager / password123\n";
    echo "Finance Manager:   financemanager / password123\n";
    echo "Operations Mgr:    opsmanager / password123\n";
    echo "Sales Employee:    alice / password123\n";
    echo "Sales Employee:    robert / password123\n";
    echo "Finance Employee: lisa / password123\n";
    echo "Finance Employee: james / password123\n";
    echo "Operations Emp:    maria / password123\n";
    echo "Operations Emp:    william / password123\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre></body></html>";
