<?php
/**
 * Quick User Approval Handler
 * Allows admin to quickly approve or reject pending users
 */

session_start();
require_once "../DB_connection.php";
require_once "Model/User.php";
require_once "Model/Notification.php";

// Check authentication and authorization - Allow Admin and HR
require_once "Model/RoleHelper.php";
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);
$is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id'] ?? 0);

if (!isset($_SESSION['employee_id']) || (!$is_admin && !$is_hr)) {
    header("Location: ../login.php?error=Unauthorized+access");
    exit();
}

// Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['employee_id']) || !isset($_POST['action'])) {
    header("Location: ../user.php?error=Invalid+request");
    exit();
}

$employee_id = (int)$_POST['employee_id'];
$action = $_POST['action']; // 'approve' or 'reject'

// Get user details to verify they exist and are pending
$user = get_user_by_id($conn, $employee_id);

if (!$user) {
    header("Location: ../user.php?error=User+not+found");
    exit();
}

// Only process if user is pending
if (strtolower($user['status']) !== 'pending') {
    header("Location: ../user.php?error=User+is+not+pending+approval");
    exit();
}

try {
    if ($action === 'approve') {
        // Quick approval: Set status to Active
        // Note: For full approval with job details, use edit-user.php
        $sql = "UPDATE employee SET status = 'Active' WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$employee_id]);
        
        if ($success) {
            // Send notification to user
            $message = "Your account has been approved. You can now log in to the system.";
            insert_notification($conn, [$message, $employee_id, 'Account Approved']);
            
            header("Location: ../user.php?success=User+approved+successfully");
        } else {
            header("Location: ../user.php?error=Failed+to+approve+user");
        }
    } elseif ($action === 'reject') {
        // Reject user: Set status to Terminated
        $sql = "UPDATE employee SET status = 'Terminated' WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$employee_id]);
        
        if ($success) {
            // Send notification
            $message = "Your account application has been rejected. Please contact HR for more information.";
            insert_notification($conn, [$message, $employee_id, 'Account Rejected']);
            
            header("Location: ../user.php?success=User+rejected+successfully");
        } else {
            header("Location: ../user.php?error=Failed+to+reject+user");
        }
    } else {
        header("Location: ../user.php?error=Invalid+action");
    }
} catch (PDOException $e) {
    error_log("Error in approve-user.php: " . $e->getMessage());
    header("Location: ../user.php?error=Database+error+occurred");
}

exit();

