<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/User.php";

// Only Admin can add users (password resets and employee registration are admin responsibilities)
require_once "Model/RoleHelper.php";
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);

if (!isset($_SESSION['employee_id']) || !$is_admin) {
    header("Location: ../login.php?error=Access+denied.+Only+System+Admin+can+register+employees");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    function normalize_optional_int($value)
    {
        if ($value === null) return null;
        $v = trim((string)$value);
        if ($v === '' || strtolower($v) === 'n/a' || strtolower($v) === 'na') return null;
        if (!preg_match('/^\d+$/', $v)) return null;
        return (int)$v;
    }

    // Validate required fields
    $required_fields = [
        'first_name',
        'last_name',
        'id_no',
        'date_of_birth',
        'gender',
        'email_address',
        'phone_number',
        'residential_address',
        'emergency_contact_name',
        'emergency_contact_number',
        'next_of_kin_relationship',
        'job_title',
        'department',
        'date_of_hire',
        'employment_type',
        'status',
        'work_location',
        'username',
        'password',
        'role',
        'basic_salary',
        'banking_details',
        'document_url'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../add-user.php?error=All+fields+are+required");
            exit();
        }
    }

    // Set default values for optional fields
    $data = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'maiden_name' => $_POST['maiden_name'] ?? null,
        'id_no' => $_POST['id_no'],
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'email_address' => $_POST['email_address'],
        'phone_number' => $_POST['phone_number'],
        'address' => $_POST['address'] ?? null,
        'residential_address' => $_POST['residential_address'],
        'emergency_contact_name' => $_POST['emergency_contact_name'],
        'emergency_contact_number' => $_POST['emergency_contact_number'],
        'next_of_kin_relationship' => $_POST['next_of_kin_relationship'],
        'passport_details' => $_POST['passport_details'] ?? null,
        'driver_license_no' => $_POST['driver_license_no'] ?? null,
        'marital_status' => $_POST['marital_status'] ?? null,
        'spouse_name' => $_POST['spouse_name'] ?? null,
        'own_children' => normalize_optional_int($_POST['own_children'] ?? null),
        'own_dependants' => normalize_optional_int($_POST['own_dependants'] ?? null),
        'job_title' => $_POST['job_title'],
        'department' => $_POST['department'],
        'manager_id' => $_POST['manager_id'] ?? null,
        'date_of_hire' => $_POST['date_of_hire'],
        'employment_type' => $_POST['employment_type'],
        'status' => $_POST['status'],
        'work_location' => $_POST['work_location'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'executive_member' => isset($_POST['executive_member']) ? 1 : 0,  // <-- ADD THIS LINE
        'role' => $_POST['role'],
        'basic_salary' => $_POST['basic_salary'],
        'banking_details' => $_POST['banking_details'],
        'document_url' => $_POST['document_url']
    ];

    if (insert_user($conn, $data)) {
        // Notify HR when admin creates a new employee
        require_once "Model/Notification.php";
        $hr_id = RoleHelper::get_hr_id($conn);
        if ($hr_id) {
            $new_employee_name = $data['first_name'] . ' ' . $data['last_name'];
            $admin_name = get_user_by_id($conn, $_SESSION['employee_id'])['first_name'] . ' ' . 
                         get_user_by_id($conn, $_SESSION['employee_id'])['last_name'];
            $message = "System Admin ({$admin_name}) has registered a new employee: {$new_employee_name} ({$data['job_title']}, {$data['department']}). Please review the employee details.";
            create_notification($conn, $hr_id, $message, 'new_employee_registered');
        }
        
        header("Location: ../user.php?success=User+added+successfully.+HR+has+been+notified");
    } else {
        header("Location: ../add-user.php?error=Failed+to+add+user");
    }
    exit();
}
