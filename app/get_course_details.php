<?php
require_once "../DB_connection.php";
require_once "Model/Learning.php";

if (!isset($_GET['course_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$course_id = $_GET['course_id'];
$course = Learning::get_course_details($conn, $course_id);

header('Content-Type: application/json');
echo json_encode($course);
?>