<?php
session_start();
require_once "../DB_connection.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    // Check if file was actually uploaded
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../edit_profile.php?error=File upload error");
        exit();
    }

    $employee_id = $_SESSION['employee_id'];
    $target_dir = "../img/";
    $target_file = $target_dir . "user" . $employee_id . ".png";
    $temp_file = $_FILES['profile_picture']['tmp_name'];

    // Validate the file is an image
    if (!getimagesize($temp_file)) {
        header("Location: ../edit_profile.php?error=File is not an image");
        exit();
    }

    // Check file size (2MB max)
    if ($_FILES['profile_picture']['size'] > 2000000) {
        header("Location: ../edit_profile.php?error=File is too large (max 2MB)");
        exit();
    }

    // Allow certain file formats
    $imageFileType = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
        header("Location: ../edit_profile.php?error=Only JPG, JPEG & PNG files are allowed");
        exit();
    }

    // Process the upload
    if (move_uploaded_file($temp_file, $target_file)) {
        // Convert to PNG if needed
        if (function_exists('imagecreatefromstring') && $imageFileType != 'png') {
            $image = imagecreatefromstring(file_get_contents($target_file));
            if ($image !== false) {
                imagepng($image, $target_file);
                imagedestroy($image);
            }
        }
        header("Location: ../profile.php?success=Profile picture updated");
    } else {
        header("Location: ../edit_profile.php?error=Error uploading file");
    }
    exit();
}

header("Location: ../edit_profile.php");
?>