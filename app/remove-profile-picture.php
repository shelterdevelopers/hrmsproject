<?php
session_start();
require_once "../DB_connection.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$target_file = "../img/user" . $employee_id . ".png";

// Remove the profile picture file if it exists
if (file_exists($target_file)) {
    if (unlink($target_file)) {
        header("Location: ../edit_profile.php?success=Profile picture removed successfully");
    } else {
        header("Location: ../edit_profile.php?error=Error removing profile picture");
    }
} else {
    header("Location: ../edit_profile.php?error=No profile picture to remove");
}
exit();
?>
