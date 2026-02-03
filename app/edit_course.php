<?php
session_start();
require_once __DIR__ . "/../DB_connection.php";
require_once "Model/Learning.php";

// Admin access only
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != "Admin") {
    header("Location: ../../login.php");

    exit();
}

if (!isset($_GET['id'])) {
    header("Location: learning_admin.php");
    exit();
}

$course_id = $_GET['id'];
$course = Learning::get_course_details($conn, $course_id);

if ($course == 0) {
    header("Location: learning_admin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        $_POST['title'],
        $_POST['description'],
        $_POST['duration'],
        $_POST['category'],
        isset($_POST['is_active']) ? 1 : 0,
        $course_id
    ];
    
    $sql = "UPDATE learning_courses SET 
            title = ?, description = ?, duration = ?, 
            category = ?, is_active = ? 
            WHERE course_id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt->execute($data)) {
        $success = "Course updated successfully!";
        $course = Learning::get_course_details($conn, $course_id); // Refresh data
    } else {
        $error = "Failed to update course!";
    }
}

include "views/edit_course_view.php";
?>