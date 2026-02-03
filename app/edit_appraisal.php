<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";

if (!isset($_SESSION['employee_id']) || !isset($_GET['id'])) {
    header("Location: ../login.php");
    exit();
}

$form_id = $_GET['id'];
$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];

// Get appraisal details
$form = Appraisal::get_appraisal_form_details($conn, $form_id);

// Verify permissions - only the assigned manager can edit (Admin has no appraiser role)
if (!$form || $form['manager_id'] != $employee_id) {
    header("Location: appraisal.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appraisal'])) {
    $metrics = [];
    foreach (Appraisal::get_metrics() as $metric => $data) {
        $metrics[$metric] = [
            'rating' => $_POST['metrics'][$metric]['rating'] ?? 0,
            'comments' => $_POST['metrics'][$metric]['comments'] ?? ''
        ];
    }

    $manager_strengths   = $_POST['manager_strengths']   ?? '';
    $manager_improvement = $_POST['manager_improvement'] ?? '';
    $manager_training    = $_POST['manager_training']    ?? '';

    // Update the appraisal, passing the comments
    $result = Appraisal::update_appraisal(
        $conn,
        $form_id,
        $metrics,
        $employee_id,
        $manager_strengths,
        $manager_improvement,
        $manager_training
    );

    if ($result) {
        $_SESSION['success'] = "Appraisal updated successfully!";
        header("Location: appraisal_detail.php?id=$form_id");
        exit();
    } else {
        $error = "Failed to update appraisal";
    }
}

// Decode metrics or use defaults
$metrics_data = $form['metrics'] ? json_decode($form['metrics'], true) : Appraisal::get_metrics();

include "views/edit_appraisal_view.php";