<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Learning.php";

if ($_SESSION['role'] != "Admin") {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enrollment_id'])) {
    $success = Learning::verify_completion($conn, $_POST['enrollment_id']);
    echo json_encode(['success' => $success]);
    exit();
}

header("HTTP/1.1 400 Bad Request");