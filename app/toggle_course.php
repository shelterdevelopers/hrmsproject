<?php
session_start();
require_once __DIR__ . "/../DB_connection.php";

// Admin access only
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != "Admin") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: learning_admin.php");
    exit();
}

$course_id = $_GET['id'];

// Get current status
$stmt = $conn->prepare("SELECT is_active FROM learning_courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$current_status = $stmt->fetch()['is_active'];

// Toggle status
$new_status = $current_status ? 0 : 1;

$stmt = $conn->prepare("UPDATE learning_courses SET is_active = ? WHERE course_id = ?");
if ($stmt->execute([$new_status, $course_id])) {
    $message = $new_status ? "Course activated successfully!" : "Course deactivated successfully!";
    header("Location: learning_admin.php?success=" . urlencode($message));
} else {
    header("Location: learning_admin.php?error=Failed to update course status");
}
exit();
?>