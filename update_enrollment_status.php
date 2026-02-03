<?php
session_start();
require_once "DB_connection.php";
require_once "app/Model/Learning.php";

if (!isset($_SESSION['employee_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enrollment_id']) && isset($_POST['status'])) {
    if ($_POST['status'] == 'completed') {
        $success = Learning::complete_course($conn, $_POST['enrollment_id'], $_SESSION['employee_id']);
    } else {
        $success = Learning::update_progress($conn, $_POST['enrollment_id'], 99); // Set to just below completion
    }
    
    echo json_encode(['success' => $success]);
    exit();
}

header("HTTP/1.1 400 Bad Request");
?>