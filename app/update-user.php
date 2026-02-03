<?php
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id']) && $_SESSION['role'] == 'Admin') {
    include "../DB_connection.php";
    include "Model/User.php";

    function normalize_optional_int($value)
    {
        if ($value === null) return null;
        $v = trim((string)$value);
        if ($v === '' || strtolower($v) === 'n/a' || strtolower($v) === 'na') return null;
        if (!preg_match('/^\d+$/', $v)) return null;
        return (int)$v;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // --- CORRECTION 1: REMOVED 'executive_member' FROM REQUIRED CHECK ---
        // Since the hidden input guarantees 'executive_member' is always sent (as 0 or 1),
        // we remove it from this validation loop that uses empty().
        $required_fields = [
            'employee_id', 'first_name', 'last_name', 'id_no',
            'date_of_birth', 'gender', 'email_address', 'phone_number',
            'residential_address', 'emergency_contact_name', 'emergency_contact_number',
            'next_of_kin_relationship', 'job_title', 'department',
            'date_of_hire', 'employment_type', 'status',
            'work_location', 'username', 'role', 'basic_salary', 'banking_details'
        ];

        foreach ($required_fields as $field) {
            // This check is fine for all other fields.
            if (!isset($_POST[$field]) || (empty($_POST[$field]) && $_POST[$field] !== '0')) {
                $em = "Required field '$field' is missing or empty";
                header("Location: ../edit-user.php?error=$em&id=" . $_POST['employee_id']);
                exit();
            }
        }

        // --- CORRECTION 2: REORDERED THE $data ARRAY ---
        // This array MUST match the exact order of the '?' placeholders in your update_user() function's SQL.
        $data = [
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['maiden_name'] ?? null,
            $_POST['id_no'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['email_address'],
            $_POST['phone_number'],
            $_POST['address'] ?? null,
            $_POST['residential_address'],
            $_POST['emergency_contact_name'],
            $_POST['emergency_contact_number'],
            $_POST['next_of_kin_relationship'],
            $_POST['passport_details'] ?? null,
            $_POST['driver_license_no'] ?? null,
            $_POST['marital_status'] ?? null,
            $_POST['spouse_name'] ?? null,
            normalize_optional_int($_POST['own_children'] ?? null),
            normalize_optional_int($_POST['own_dependants'] ?? null),
            $_POST['job_title'],
            $_POST['department'],
            $_POST['manager_id'] ?? null, // manager_id can be optional
            $_POST['date_of_hire'],
            $_POST['employment_type'],
            $_POST['status'],
            $_POST['work_location'],
            $_POST['username'],
            !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null,
            $_POST['role'],
            $_POST['basic_salary'],
            $_POST['banking_details'],
            $_POST['document_url'] ?? null,
            $_POST['executive_member'], // Now in the correct position
            $_POST['employee_id']  // This must be last for the WHERE clause
        ];

        if (update_user($conn, $data)) {
            $sm = "User updated successfully";
            header("Location: ../edit-user.php?success=$sm&id=" . $_POST['employee_id']);
            exit();
        } else {
            $em = "Error updating user";
            header("Location: ../edit-user.php?error=$em&id=" . $_POST['employee_id']);
            exit();
        }
    }
} else {
    header("Location: ../login.php");
    exit();
}