<?php
// In app/learning.php
session_start();
require_once "../DB_connection.php";
require_once "Model/Learning.php";
require_once "Model/User.php";

if (!isset($_SESSION['employee_id'])) { // Simple auth check
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];
$user_info = get_user_by_id($conn, $employee_id);
$is_executive = ($user_info && $user_info['executive_member'] == 1);

$success = null;
$error = null;

// --- Form Submission Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Suggest New Course ---
    if (isset($_POST['suggest_course'])) {
        if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['duration']) || empty($_POST['category'])) {
            $error = "Please fill in all required fields for the course suggestion.";
        } else {
            $result = Learning::suggest_course(
                $conn, $employee_id, $_POST['title'], $_POST['description'],
                $_POST['duration'], $_POST['category'], $_POST['link'] ?? null
            );
            if ($result['success']) { $success = "Course suggestion submitted for manager approval!"; }
            else { $error = $result['message'] ?? "Failed to submit suggestion."; }
        }
    }

    // --- Manager/Executive Responding to Suggestion ---
    elseif (isset($_POST['respond_suggestion'])) {
        if (empty($_POST['suggestion_id']) || empty($_POST['action'])) {
             $error = "Missing required information for response.";
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
                // HR Manager can approve directly
                $new_status = 'approved'; 
            } elseif ($action == 'deny') {
                $new_status = 'denied';
            }

            if (!empty($new_status) && empty($error)) {
                if (Learning::update_suggestion_status($conn, $suggestion_id, $new_status, $action, $employee_id, $comment, $selected_executive_id)) {
                    $success = "Suggestion status updated successfully!";
                } else { $error = "Failed to update suggestion status. Check permissions or state."; }
            } elseif (empty($error)) { $error = "Invalid action specified."; }
        }
    }

    // --- Enroll in Course ---
    elseif (isset($_POST['enroll'])) {
        $course_id = $_POST['course_id'];
        if (Learning::enroll_in_course($conn, $employee_id, $course_id)) {
            $success = "Successfully enrolled in course!";
        } else { $error = "Already enrolled in this course!"; }
    }
    
    // --- Submit Feedback ---
    elseif (isset($_POST['submit_feedback'])) {
        $data = [ $employee_id, $_POST['course_id'], $_POST['rating'], $_POST['feedback'] ];
        if (Learning::submit_feedback($conn, $data)) { $success = "Thank you for your feedback!"; }
        else { $error = "Failed to submit feedback!"; }
    }
    
    // --- Update Progress ---
    elseif (isset($_POST['update_progress'])) {
        $enrollment_id = $_POST['enrollment_id'];
        $progress = $_POST['progress'];
        if (Learning::update_progress($conn, $enrollment_id, $progress)) {
            $success = "Progress updated!";
        } else { $error = "Failed to update progress!"; } // This 'else' was misplaced
        
        if ($progress == 100) {
            Learning::complete_course($conn, $enrollment_id, $employee_id);
            header("Location: learning.php?completed=1"); // Redirect on 100%
            exit();
        }
    }
} // End POST handling


// --- Fetch Data for Display ---
$search_filter = $_GET['search'] ?? '';
$courses = Learning::get_all_courses($conn, $search_filter);
$enrollments = Learning::get_employee_enrollments($conn, $employee_id);
$my_suggestions = Learning::get_my_suggestions($conn, $employee_id);

// Fetch data for HR Manager approval section
$pending_manager_approvals = [];
$pending_executive_approvals = [];
$all_executives = [];

// Check if current user is HR Manager - only HR Manager sees pending suggestions
require_once "Model/RoleHelper.php";
$is_hr_manager = RoleHelper::is_hr($conn, $employee_id);

if ($is_hr_manager) {
    // HR Manager gets all pending suggestions (since they all go to HR)
    $pending_manager_approvals = Learning::get_pending_manager_approvals($conn, $employee_id);
}

// Executives can still see executive-level approvals (if workflow includes executives)
if ($is_executive) {
    $pending_executive_approvals = Learning::get_pending_executive_approvals($conn);
}

// Fetch executives if needed by any approval section
if (!empty($pending_manager_approvals) || !empty($pending_executive_approvals)) {
    $all_executives = Learning::get_all_executives($conn);
}

// --- Include View ---
include "views/learning_view.php";
?>