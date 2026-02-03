<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];
require_once "Model/RoleHelper.php";
$is_hr = RoleHelper::is_hr($conn, $employee_id);
$is_managing_director = RoleHelper::is_managing_director($conn, $employee_id);

// Check if user can create appraisals: manager (has direct reports), MD, or HR – not Admin (Admin is a regular employee with admin rights only)
if ($role == 'Admin' || (!Appraisal::is_manager($conn, $employee_id) && !$is_managing_director && !$is_hr)) {
    header("Location: appraisal.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_appraisal'])) {
    try {
        // Validate inputs
        $emp_id = $_POST['employee_id'];
        $start_date = $_POST['period_start'];
        $end_date = $_POST['period_end'];
        
        // HR appraises employees (HR is the appraiser). Creator is always the appraiser.
        $manager_id = $employee_id;
        
        // Create new appraisal form
        $sql = "INSERT INTO appraisal_forms (
            employee_id, 
            manager_id, 
            period_start, 
            period_end, 
            status
        ) VALUES (?, ?, ?, ?, 'draft')";
        
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([
            $emp_id,
            $manager_id,
            $start_date,
            $end_date
        ]);
        
        if ($success) {
            $_SESSION['success'] = "Appraisal created successfully!";
            header("Location: appraisal.php?tab=active");
            exit();
        } else {
            throw new Exception("Failed to create appraisal");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating appraisal: " . $e->getMessage();
        header("Location: appraisal.php?tab=new");
        exit();
    }
} else {
    header("Location: appraisal.php");
    exit();
}
?>