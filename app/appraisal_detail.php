<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";
require_once "Model/RoleHelper.php";

// Check authentication and form ID
if (!isset($_SESSION['employee_id']) || !isset($_GET['id'])) {
    header("Location: ../login.php");
    exit();
}

$form_id = $_GET['id'];
$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];
$is_hr = RoleHelper::is_hr($conn, $employee_id);

// Get complete appraisal details, including previous assessment date
$form = Appraisal::get_appraisal_form_details($conn, $form_id);

// Permissions: employee or assigned manager can view; HR can view any completed appraisal (for file-keeping)
if (!$form) {
    header("Location: appraisal.php");
    exit();
}
$can_view = ($form['employee_id'] == $employee_id || $form['manager_id'] == $employee_id)
    || ($is_hr && ($form['appraisal_status'] ?? '') === 'completed');
if (!$can_view) {
    header("Location: appraisal.php");
    exit();
}

$is_manager = ($form['manager_id'] == $employee_id);
$is_employee = ($form['employee_id'] == $employee_id);

// Prepare metrics data (always use new structure)
$combined_metrics = [];
$default_metrics = Appraisal::get_metrics();

if ($form['metrics']) {
    $metrics_data = json_decode($form['metrics'], true);
    if (is_array($metrics_data)) {
        foreach ($default_metrics as $category => $template) {
            $combined_metrics[$category] = [
                'manager_score' => $metrics_data[$category]['rating'] ?? null,
                'manager_comments' => $metrics_data[$category]['comments'] ?? null,
            ];
        }
    } else {
        // fallback if structure is wrong
        $combined_metrics = array_map(function($m) { return ['manager_score'=>null,'manager_comments'=>null]; }, $default_metrics);
    }
} else {
    $combined_metrics = array_map(function($m) { return ['manager_score'=>null,'manager_comments'=>null]; }, $default_metrics);
}

// Handle POSTs
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['manager_update'])) {
        // Validate and prepare metrics from form
        $metrics_post = [];
        foreach ($default_metrics as $category => $template) {
            $score = isset($_POST['metrics'][$category]['rating']) ? (int)$_POST['metrics'][$category]['rating'] : 0;
            $comments = trim($_POST['metrics'][$category]['comments'] ?? '');
            $metrics_post[$category] = [
                'description' => $template['description'],
                'max_score' => $template['max_score'],
                'rating' => $score,
                'comments' => $comments,
            ];
        }
        $result = Appraisal::update_appraisal($conn, $form_id, $metrics_post, $employee_id);
        if ($result) {
            $_SESSION['success'] = "Appraisal updated!";
            header("Refresh:0");
            exit();
        }
    } elseif (isset($_POST['share_with_employee'])) {
        $result = Appraisal::share_with_employee($conn, $form_id);
        if ($result) {
            $_SESSION['success'] = "Appraisal shared with employee!";
            header("Refresh:0");
            exit();
        }
    } elseif (isset($_POST['acknowledge'])) {
        $result = Appraisal::record_acknowledgement(
            $conn,
            $form_id,
            $employee_id,
            $_POST['employee_comments'] ?? ''
        );
        if ($result) {
            $_SESSION['success'] = "Appraisal acknowledged!";
            header("Refresh:0");
            exit();
        }
    } elseif (isset($_POST['finalize_appraisal'])) {
        $result = Appraisal::finalize_appraisal($conn, $form_id);
        if ($result) {
            $_SESSION['success'] = "Appraisal finalized and completed!";
            header("Location: appraisal.php?tab=completed");
            exit();
        }
    } elseif (isset($_POST['save_self_assessment']) && $form['employee_id'] == $employee_id) {
        $areas = Appraisal::get_self_assessment_areas();
        $self_metrics = [];
        foreach ($areas as $key => $info) {
            $rating = isset($_POST['self_metrics'][$key]['rating']) ? (int)$_POST['self_metrics'][$key]['rating'] : 0;
            $comments = trim($_POST['self_metrics'][$key]['comments'] ?? '');
            $self_metrics[$key] = ['weight' => $info['weight'], 'rating' => $rating, 'comments' => $comments];
        }
        $appraisal_id = (int)($form['appraisal_id'] ?? $form_id);
        try {
            $result = Appraisal::save_self_assessment(
                $conn,
                $appraisal_id,
                $employee_id,
                $self_metrics,
                trim($_POST['self_goals'] ?? ''),
                trim($_POST['self_strengths'] ?? ''),
                trim($_POST['self_weaknesses'] ?? ''),
                trim($_POST['self_achievements'] ?? ''),
                trim($_POST['self_training'] ?? '')
            );
            if ($result) {
                $_SESSION['success'] = "Self assessment saved!";
                header("Refresh:0");
                exit();
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'self_') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $_SESSION['appraisal_error'] = "Self assessment could not be saved. Please contact HR – the self-assessment database update may need to be run.";
            }
        }
    }
}

if (isset($_SESSION['appraisal_error'])) {
    $error = $_SESSION['appraisal_error'];
    unset($_SESSION['appraisal_error']);
}

// Show view
include "views/appraisal_detail_view.php";