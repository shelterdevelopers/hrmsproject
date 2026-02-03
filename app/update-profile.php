<?php
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id']) && $_SESSION['role'] == 'Admin') {
    include "../DB_connection.php";
    include "Model/User.php";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        function validate_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        // Validate required fields
        $required_fields = [
            'first_name', 'last_name', 'user_name', 'email_address', 'phone_number',
            'address', 'emergency_contact_name', 'emergency_contact_number', 'date_of_birth',
            'gender', 'job_title', 'department', 'manager_id', 'date_of_hire',
            'employment_type', 'status', 'work_location', 'role', 'basic_salary', 'id', 'document_url'
        ];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $em = "Required field '$field' is missing";
                header("Location: ../edit-user.php?error=$em&id=".$_POST['id']);
                exit();
            }
        }

        // Prepare data array
        $data = [
            validate_input($_POST['first_name']),
            validate_input($_POST['last_name']),
            validate_input($_POST['date_of_birth']),
            validate_input($_POST['gender']),
            validate_input($_POST['email_address']),
            validate_input($_POST['phone_number']),
            validate_input($_POST['address']),
            validate_input($_POST['emergency_contact_name']),
            validate_input($_POST['emergency_contact_number']),
            validate_input($_POST['job_title']),
            validate_input($_POST['department']),
            validate_input($_POST['manager_id']),
            validate_input($_POST['date_of_hire']),
            validate_input($_POST['employment_type']),
            validate_input($_POST['status']),
            validate_input($_POST['work_location']),
            validate_input($_POST['user_name']),
            !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '',
            validate_input($_POST['role']),
            validate_input($_POST['basic_salary']),
            validate_input($_POST['id']),
            validate_input($_POST['document_url'])
        ];

        // Update user
        if (update_user($conn, $data)) {
            $sm = "User updated successfully";
            header("Location: ../edit-user.php?success=$sm&id=".$_POST['id']);
            exit();
        } else {
            $em = "Error updating user";
            header("Location: ../edit-user.php?error=$em&id=".$_POST['id']);
            exit();
        }
    }
} else {
    header("Location: login.php");
    exit();
}
?>