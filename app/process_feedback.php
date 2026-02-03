<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "../DB_connection.php";
    include "Model/Notification.php";

    if (isset($_POST['feedback']) && isset($_POST['rating'])) {
        $feedback = trim($_POST['feedback']);
        $rating = (int)$_POST['rating'];

        if (empty($feedback)) {
            $em = "Feedback text is required";
            header("Location: ../company_info.php?error=$em#feedback");
            exit();
        } elseif ($rating < 1 || $rating > 5) {
            $em = "Please provide a valid rating";
            header("Location: ../company_info.php?error=$em#feedback");
            exit();
        }

        // Store feedback in database
        $data = array($_SESSION['employee_id'], $feedback, $rating);
        insert_feedback($conn, $data);

        // Return success message
        $em = "Thank you for your feedback!";
        header("Location: ../company_info.php?success=$em#feedback");
        exit();
    } else {
        $em = "Please provide both feedback and rating";
        header("Location: ../company_info.php?error=$em#feedback");
        exit();
    }
} else { 
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}
?>