<?php

function employee_has_column($conn, $column_name)
{
    static $cache = [];
    if (array_key_exists($column_name, $cache)) {
        return $cache[$column_name];
    }

    // Use INFORMATION_SCHEMA to check existence against the current DB.
    $sql = "SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'employee'
              AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$column_name]);
    $cache[$column_name] = ((int)$stmt->fetchColumn() > 0);
    return $cache[$column_name];
}

function get_all_users($conn)
{
    $sql = "
        SELECT e.*, m.first_name AS manager_first_name, m.last_name AS manager_last_name
        FROM employee e
        LEFT JOIN employee m ON e.manager_id = m.employee_id
        WHERE e.role IN ('admin', 'Admin', 'employee', 'Employee', 'manager', 'Manager')
        ORDER BY 
            CASE WHEN LOWER(e.status) = 'pending' THEN 0 ELSE 1 END,
            e.last_name, 
            e.first_name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
function insert_user($conn, $data)
{
    // Set default values for employee signup
    $default_values = [
        'courses_completed' => 0,
        'leave_days_remaining' => 24,
        'marital_status' => null,
        'spouse_name' => null,
        'own_children' => null,
        'own_dependants' => null,
        'passport_details' => null,
        'driver_license_no' => null,
        'address' => null,
        'job_title' => 'New Employee', // Default for signups
        'department' => 'Pending Assignment',
        'manager_id' => null,
        'date_of_hire' => date('Y-m-d'), // Current date as default
        'employment_type' => 'Full-time',
        'status' => 'pending',
        'work_location' => 'Office',
        'role' => 'employee',
        'basic_salary' => 0,
        'banking_details' => null,
        'executive_member' => 0

    ];

    // Merge provided data with defaults (provided data overrides defaults)
    $data = array_merge($default_values, $data);

    $columns = [
        'first_name',
        'last_name',
        'maiden_name',
        'id_no',
        'date_of_birth',
        'gender',
        'email_address',
        'phone_number',
        'address',
        'residential_address',
        'emergency_contact_name',
        'emergency_contact_number',
        'next_of_kin_relationship',
        'passport_details',
        'driver_license_no',
        'marital_status',
        'spouse_name',
        'own_children',
        'own_dependants',
        'job_title',
        'department',
        'manager_id',
        'date_of_hire',
        'employment_type',
        'status',
        'work_location',
        'username',
        'password',
        'role',
        'executive_member',
        'basic_salary',
        'courses_completed',
        'leave_days_remaining',
        'banking_details',
        'document_url',
    ];

    // Some installations are missing certain columns (e.g. older schemas).
    // Remove missing columns to avoid "Unknown column" errors during registration.
    foreach ($columns as $idx => $col) {
        if (!employee_has_column($conn, $col)) {
            unset($columns[$idx]);
        }
    }
    $columns = array_values($columns);

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO employee (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";

    $values = [];
    foreach ($columns as $col) {
        $values[] = $data[$col] ?? null;
    }

    $stmt = $conn->prepare($sql);
    return $stmt->execute($values);

    /*
    Legacy column order (kept for reference):
    return $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['maiden_name'],
        $data['id_no'],
        $data['date_of_birth'],
        $data['gender'],
        $data['email_address'],
        $data['phone_number'],
        $data['address'],
        $data['residential_address'],
        $data['emergency_contact_name'],
        $data['emergency_contact_number'],
        $data['next_of_kin_relationship'],
        $data['passport_details'],
        $data['driver_license_no'],
        $data['marital_status'],
        $data['spouse_name'],
        $data['own_children'],
        $data['own_dependants'],
        $data['job_title'],
        $data['department'],
        $data['manager_id'],
        $data['date_of_hire'],
        $data['employment_type'],
        $data['status'],
        $data['work_location'],
        $data['username'],
        $data['password'],
        $data['role'],
        $data['executive_member'],
        $data['basic_salary'],
        $data['courses_completed'],
        $data['leave_days_remaining'],
        $data['banking_details'],
        $data['document_url']
    ]);
    */
}
function update_user($conn, $data)
{
    $sql = "UPDATE employee SET 
                first_name = ?, 
                last_name = ?,
                maiden_name = ?,
                id_no = ?,
                date_of_birth = ?, 
                gender = ?, 
                email_address = ?, 
                phone_number = ?, 
                address = ?,
                residential_address = ?,
                emergency_contact_name = ?,
                emergency_contact_number = ?,
                next_of_kin_relationship = ?,
                passport_details = ?,
                driver_license_no = ?,
                marital_status = ?,
                spouse_name = ?,
                own_children = ?,
                own_dependants = ?,
                job_title = ?, 
                department = ?, 
                manager_id = ?, 
                date_of_hire = ?, 
                employment_type = ?, 
                status = ?, 
                work_location = ?, 
                username = ?, 
                password = COALESCE(?, password), 
                role = ?,
                basic_salary = ?,
                banking_details = ?,
                document_url = ?,
                executive_member=?
            WHERE employee_id = ?";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($data);

    if (!$result) {
        error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
        return false;
    }

    return true;
}

function delete_user($conn, $employee_id)
{
    try {
        // Step 1: Update child records to nullify manager_id
        $sql = "UPDATE employee SET manager_id = NULL WHERE manager_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);

        // Step 2: Delete the employee record
        $sql = "DELETE FROM employee WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);

        // Check if any rows were affected
        if ($stmt->rowCount() === 0) {
            throw new Exception('No user was deleted. User may not exist.');
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        return false;
    }

    return true;
}

function get_user_by_id($conn, $id)
{
    $sql = "SELECT e.*, m.first_name as manager_first_name, m.last_name as manager_last_name
            FROM employee e
            LEFT JOIN employee m ON e.manager_id = m.employee_id
            WHERE e.employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0 ? $stmt->fetch() : null;
}

function update_profile($conn, $data)
{
    $sql = "UPDATE employee SET 
                first_name = ?, 
                last_name = ?,
                maiden_name = ?,
                id_no = ?,
                date_of_birth = ?, 
                gender = ?, 
                email_address = ?, 
                phone_number = ?, 
                address = ?,
                residential_address = ?,
                emergency_contact_name = ?,
                emergency_contact_number = ?,
                next_of_kin_relationship = ?,
                passport_details = ?,
                driver_license_no = ?,
                marital_status = ?,
                spouse_name = ?,
                own_children = ?,
                own_dependants = ?,
                job_title = ?, 
                department = ?, 
                manager_id = ?, 
                date_of_hire = ?, 
                employment_type = ?, 
                status = ?, 
                work_location = ?, 
                username = ?, 
                password = COALESCE(?, password), 
                role = ?,
                basic_salary = ?,
                banking_details = ?,
                document_url = ?
            WHERE employee_id = ?";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($data);

    if (!$result) {
        error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
    }

    return $result;
}


function count_users($conn)
{
    // Count ALL active employees (not just role='employee') - for HR and Admin views
    $sql = "SELECT COUNT(*) FROM employee WHERE status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([]);
    return (int)$stmt->fetchColumn();
}

function get_all_managers($conn)
{
    $sql = "SELECT employee_id, first_name, last_name, job_title 
    FROM employee 
    WHERE executive_member = 1 
    ORDER BY first_name, last_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $stmt->fetchAll() : [];
}

function get_pending_employees($conn)
{
    $sql = "SELECT * FROM employee WHERE status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_pending_employees($conn)
{
    $sql = "SELECT COUNT(*) FROM employee WHERE status = 'pending'";
    return $conn->query($sql)->fetchColumn();
}

function get_employee_documents($conn, $employeeId)
{
    $sql = "SELECT * FROM employee_documents WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$employeeId]);
    return $stmt->fetch();
}
function approve_pending_employee($conn, $employee_id, $admin_data)
{
    // basic_salary not set by approval form; keep existing value or 0
    $basic_salary = $admin_data['basic_salary'] ?? 0;
    $manager_id = !empty($admin_data['manager_id']) ? $admin_data['manager_id'] : null;

    $sql = "UPDATE employee SET 
            job_title = ?, 
            department = ?, 
            manager_id = ?, 
            date_of_hire = ?, 
            employment_type = ?, 
            status = 'active', 
            work_location = ?, 
            role = ?,
            basic_salary = ?,
            executive_member = ?     
            WHERE employee_id = ?";

    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $admin_data['job_title'],
        $admin_data['department'],
        $manager_id,
        $admin_data['date_of_hire'],
        $admin_data['employment_type'],
        $admin_data['work_location'],
        $admin_data['role'],
        $basic_salary,
        $admin_data['executive_member'],
        $employee_id
    ]);
}

function accrue_leave_days($conn)
{
    try {
        // Monthly accrual for normal leave (2.5 days per month)
        $sql = "UPDATE employee 
                SET leave_days_remaining = leave_days_remaining + 2.5 
                WHERE status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        // Annual reset for other leave types
        if (date('m-d') == '01-01') { // Run only on January 1st
            $sql = "UPDATE employee 
                    SET annual_leave_days = 60, 
                        sick_leave_days = 90,
                        maternity_leave_days = 90
                    WHERE status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error accruing leave days: " . $e->getMessage());
        return false;
    }
}
