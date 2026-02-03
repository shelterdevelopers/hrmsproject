<?php
session_start();
if (isset($_SESSION['role'], $_SESSION['employee_id'])) {
    include "../DB_connection.php";
    include "Model/Notification.php";

    if (isset($_GET['notification_id'])) {
        $notification_id = $_GET['notification_id'];
        
        // Mark the notification as read in the database
        notification_make_read($conn, $_SESSION['employee_id'], $notification_id);
        
        // Correction: Remove the header() redirect and send a proper JSON response.
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    
    // If no notification_id is provided, send a failure response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Notification ID missing.']);
    exit();
} else { 
    // If the user is not logged in, send an unauthorized response
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}