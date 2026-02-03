<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/User.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and prepare data
    $employee_id = $_SESSION['employee_id'];
    
    // Get current user data
    $current_user = get_user_by_id($conn, $employee_id);
    if (!$current_user) {
        header("Location: ../login.php?error=User not found");
        exit();
    }
    
    // Validate password change if new password is provided
    $password_error = null;
    if (!empty($_POST['new_password'])) {
        // Require current password to change password
        if (empty($_POST['password'])) {
            $password_error = "Current password is required to change password";
        } elseif (!password_verify($_POST['password'], $current_user['password'])) {
            $password_error = "Current password is incorrect";
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $password_error = "New passwords do not match";
        } elseif (strlen($_POST['new_password']) < 8) {
            $password_error = "New password must be at least 8 characters long";
        }
        
        if ($password_error) {
            header("Location: ../edit_profile.php?error=" . urlencode($password_error));
            exit();
        }
    }
    
    // Prepare data - employees CANNOT edit banking_details (security restriction)
    $normalize_optional_int = function ($value) {
        if ($value === null) return null;
        $v = trim((string)$value);
        if ($v === '' || strtolower($v) === 'n/a' || strtolower($v) === 'na') return null;
        if (!preg_match('/^\d+$/', $v)) return null;
        return (int)$v;
    };

    $data = [
        htmlspecialchars($_POST['first_name']),
        htmlspecialchars($_POST['last_name']),
        htmlspecialchars($_POST['maiden_name'] ?? ''),
        htmlspecialchars($_POST['date_of_birth'] ?? ''),
        htmlspecialchars($_POST['gender'] ?? ''),
        htmlspecialchars($_POST['email_address']),
        htmlspecialchars($_POST['phone_number']),
        htmlspecialchars($_POST['address'] ?? ''),
        htmlspecialchars($_POST['residential_address'] ?? ''),
        htmlspecialchars($_POST['emergency_contact_name'] ?? ''),
        htmlspecialchars($_POST['emergency_contact_number'] ?? ''),
        htmlspecialchars($_POST['passport_details'] ?? ''),
        htmlspecialchars($_POST['driver_license_no'] ?? ''),
        htmlspecialchars($_POST['marital_status'] ?? ''),
        htmlspecialchars($_POST['spouse_name'] ?? ''),
        $normalize_optional_int($_POST['own_children'] ?? null),
        $normalize_optional_int($_POST['own_dependants'] ?? null),
        htmlspecialchars($_POST['next_of_kin'] ?? ''),
        htmlspecialchars($_POST['next_of_kin_relationship'] ?? ''),
        $current_user['banking_details'], // Keep existing banking details - employees cannot edit
        htmlspecialchars($_POST['document_url'] ?? ''), // Allow employees to update document URL
        !empty($_POST['new_password']) ? password_hash($_POST['new_password'], PASSWORD_DEFAULT) : '',
        $employee_id
    ];

    // Update basic profile info - employees CANNOT edit banking_details
    $sql = "UPDATE employee SET 
    first_name = ?,
    last_name = ?,
    maiden_name = ?,
    date_of_birth = ?,
    gender = ?,
    email_address = ?,
    phone_number = ?,
    address = ?,
    residential_address = ?,
    emergency_contact_name = ?,
    emergency_contact_number = ?,
    passport_details = ?,
    driver_license_no = ?,
    marital_status = ?,
    spouse_name = ?,
    own_children = ?,
    own_dependants = ?,
    next_of_kin = ?,
    next_of_kin_relationship = ?,
    banking_details = ?,
    document_url = ?,
    password = COALESCE(?, password)
    WHERE employee_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt->execute($data)) {
        // Notify HR and Admin when employee updates their profile
        require_once "Model/Notification.php";
        require_once "Model/RoleHelper.php";
        
        $employee_name = $current_user['first_name'] . ' ' . $current_user['last_name'];
        $message = "Employee {$employee_name} has updated their profile information. Please review the changes.";
        
        // Notify HR
        $hr_id = RoleHelper::get_hr_id($conn);
        if ($hr_id) {
            create_notification($conn, $hr_id, $message, 'profile_updated');
        }
        
        // Notify Admin
        $admin_id = RoleHelper::get_admin_id($conn);
        if ($admin_id && $admin_id != $employee_id) { // Don't notify admin if admin is updating their own profile
            create_notification($conn, $admin_id, $message, 'profile_updated');
        }
        
        $success = "Profile updated successfully";
        header("Location: ../profile.php?success=$success");
    } else {
        $error = "Failed to update profile";
        header("Location: ../edit_profile.php?error=$error");
    }
    exit();
}
?>