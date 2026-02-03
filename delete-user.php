<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id']) && $_SESSION['role'] == "Admin") {
    include "DB_connection.php";
    include "app/Model/User.php";
    
    if (!isset($_GET['id'])) {
        header("Location: user.php");
        exit();
    }
    
    $id = $_GET['id'];
    $user = get_user_by_id($conn, $id);

    if ($user == 0) {
        header("Location: user.php");
        exit();
    }

    // Attempt to delete the user
    if (delete_user($conn, $id)) {
        $sm = "Deleted Successfully";
        header("Location: user.php?success=$sm");
    } else {
        $em = "Failed to delete user. Please try again.";
        header("Location: user.php?error=$em");
    }
    exit();

} else { 
    $em = "First login";
    header("Location: login.php?error=$em");
    exit();
}
 ?>