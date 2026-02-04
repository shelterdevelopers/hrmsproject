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
        
        // Debug: Log department and employee count
        error_log("Department announcement - Department: {$department}, Employees found: " . count($department_employees));
        
        if (empty($department_employees)) {
            $error = "No employees found in your department ({$department}). Please verify your department is set correctly.";
        } else {
            $posted_by = (int) $_SESSION['employee_id'];
            $full_message = "Department Announcement: " . $message;
            $sent_count = 0;
            $errors = [];
            $use_poster = function_exists('insert_notification_with_poster');
            foreach ($department_employees as $emp_id) {
                try {
                    $sent = false;
                    if ($use_poster) {
                        $sent = insert_notification_with_poster($conn, $full_message, (int) $emp_id, 'department_announcement', $posted_by);
                    }
                    if (!$sent) {
                        $sent = insert_notification($conn, [$full_message, (int) $emp_id, 'department_announcement']);
                    }
                    if ($sent) {
                        $sent_count++;
                    } else {
                        $errors[] = "Failed to send to employee ID: {$emp_id}";
                    }
                } catch (Exception $e) {
                    $errors[] = "Error sending to employee ID {$emp_id}: " . $e->getMessage();
                    error_log("Department announcement error: " . $e->getMessage());
                }
            }
            
            if ($sent_count > 0) {
                $success = "Announcement sent to {$sent_count} employee(s) in {$department} department";
                if (!empty($errors)) {
                    $success .= " (" . count($errors) . " failed)";
                }
            } else {
                $error = "Failed to send announcement. " . (implode("; ", array_slice($errors, 0, 3)));
            }
        }
    }
}

include "views/department_announcement_view.php";
