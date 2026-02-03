<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Learning.php";
require_once "Model/LearningAdmin.php"; // Include Admin model
require_once "Model/User.php";

// Admin and HR access
require_once "Model/RoleHelper.php";
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id']);
$is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id']);

if (!$is_admin && !$is_hr) {
    header("Location: ../login.php?error=Access+denied");
    exit();
}

// --- FIX: Define variables ---
$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role']; // <-- This was missing
$user_info = get_user_by_id($conn, $employee_id);
$is_executive = ($user_info && $user_info['executive_member'] == 1);


// --- Form Submission Handling ---
$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Admin adding course directly ---
    if (isset($_POST['add_course'])) {
        if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['duration']) || empty($_POST['category'])) {
            $error = "All fields are required to add a course.";
        } else {
            $data = [
                $_POST['title'],
                $_POST['description'],
                $_POST['duration'],
                $_POST['category'],
                $_POST['link'] ?? null
            ];
            if (LearningAdmin::add_course($conn, $data)) {
                $success = "Course added successfully!";
            } else { $error = "Failed to add course."; }
        }
    }
    
    // --- Admin updating existing course ---
    // NOTE: This logic was broken in your file. It's assumed this is handled
    // on edit_course.php, so I've removed the broken 'else' block.
    // If you update courses from this page, that logic needs to be fixed separately.


    // --- Admin suggesting a course (as an employee) ---
    elseif (isset($_POST['suggest_course'])) {
         if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['duration']) || empty($_POST['category'])) {
            $error = "Please fill in all required fields for the course suggestion.";
        } else {
            $result = Learning::suggest_course(
                $conn, $employee_id, $_POST['title'], $_POST['description'],
                $_POST['duration'], $_POST['category'], $_POST['link'] ?? null
            );
            if ($result['success']) { $success = "Course suggestion submitted for manager approval!"; }
            else { $error = $result['message'] ?? "Failed to submit course suggestion."; }
        }
    }
    
    // --- Admin responding to suggestion (as Manager or Exec) ---
     elseif (isset($_POST['respond_suggestion'])) {
         if (empty($_POST['suggestion_id']) || empty($_POST['action'])) {
              $error = "Missing info for response.";
         } else {
             $suggestion_id = $_POST['suggestion_id'];
             $action = $_POST['action']; // 'approve', 'deny', 'forward_to_executive', 'forward_to_another_executive'
             $comment = $_POST['comment'] ?? null;
             $selected_executive_id = $_POST['executive_id'] ?? null;
             $new_status = '';

             if ($action == 'forward_to_executive' || $action == 'forward_to_another_executive') {
                 $new_status = 'pending_executive';
                 if (empty($selected_executive_id) || !ctype_digit((string)$selected_executive_id)) {
                     $error = "Please select a valid executive to forward to."; $new_status = '';
                 }
             } elseif ($action == 'approve') {
                 $new_status = 'approved'; 
             } elseif ($action == 'deny') {
                 $new_status = 'denied';
             }

             if (!empty($new_status) && empty($error)) {
                 if (Learning::update_suggestion_status($conn, $suggestion_id, $new_status, $action, $employee_id, $comment, $selected_executive_id)) {
                     $success = "Suggestion status updated!";
                 } else { $error = "Failed to update status. Check permissions or state."; }
             } elseif (empty($error)) { $error = "Invalid action."; }
         }
     }
} // End POST handling


// --- Fetch Data for Admin Display ---
$courses = Learning::get_all_courses($conn); // Get all courses
$enrollments = LearningAdmin::get_all_enrollments($conn); // All enrollments

// Admin's own suggestions (as employee)
$my_suggestions = Learning::get_my_suggestions($conn, $employee_id);

// Approvals Admin needs to action (as manager or executive)
$pending_manager_approvals = Learning::get_pending_manager_approvals($conn, $employee_id); // If admin manages others
$pending_executive_approvals = Learning::get_pending_executive_approvals($conn); // Admin sees ALL executive requests

// Data for dropdowns
$all_executives = Learning::get_all_executives($conn);

// --- Include View ---
include "views/learning_admin_view.php";
?>