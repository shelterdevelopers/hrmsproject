<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/RoleHelper.php";
require_once "Model/Notification.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is a manager
$is_manager = RoleHelper::is_manager($conn, $_SESSION['employee_id']);
$department = RoleHelper::get_department($conn, $_SESSION['employee_id']);

if (!$is_manager) {
    header("Location: ../index.php?error=Access+denied");
    exit();
}

$success = null;
$error = null;

// Handle announcement submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_announcement'])) {
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        $error = "Message is required";
    } else {
        // Get all employees in the department
        $department_employees = RoleHelper::get_department_employees($conn, $department);
        
        if (empty($department_employees)) {
            $error = "No employees found in your department";
        } else {
            // Send notification to all department employees
            $sent_count = 0;
            foreach ($department_employees as $emp_id) {
                if ($emp_id != $_SESSION['employee_id']) { // Don't notify self
                    $notif_data = [
                        "Department Announcement: " . $message,
                        $emp_id,
                        'department_announcement'
                    ];
                    if (insert_notification($conn, $notif_data)) {
                        $sent_count++;
                    }
                }
            }
            
            if ($sent_count > 0) {
                $success = "Announcement sent to {$sent_count} employees in {$department} department";
            } else {
                $error = "Failed to send announcement";
            }
        }
    }
}

include "views/department_announcement_view.php";
