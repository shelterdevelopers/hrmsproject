<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/LearningAdmin.php";
require_once "Model/User.php";

if (!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../login.php");

    exit();
}

if (!isset($_GET['course_id'])) {
    header("Location: learning_admin.php");
    exit();
}

$course_id = $_GET['course_id'];
$course = Learning::get_course_details($conn, $course_id);
$stats = LearningAdmin::get_course_stats($conn, $course_id);
$feedback = LearningAdmin::get_course_feedback($conn, $course_id);

include "views/course_stats_view.php";
?>