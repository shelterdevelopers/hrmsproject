<?php

require_once __DIR__ . "/RoleHelper.php";
require_once __DIR__ . "/ActivityLog.php";
require_once __DIR__ . "/Notification.php";

class ApplicationWorkflow
{
    /**
     * Update application with multi-level approval workflow
     */
    public static function update_application($conn, $application_id, $approver_id, $status, $comment)
    {
        try {
            $conn->beginTransaction();
            
            // Get application details
            $sql = "SELECT a.*, e.role as employee_role, e.department as employee_dept, 
                           e.first_name, e.last_name
                    FROM applications a
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$application_id]);
            $app = $stmt->fetch();
            
            if (!$app) {
                throw new Exception("Application not found");
            }
            
            $approver_role = RoleHelper::get_role($conn, $approver_id);
            $approver_dept = RoleHelper::get_department($conn, $approver_id);
            
            // Normalize employee role for comparison (handle 'hr_manager' as 'hr', '*_manager' as 'manager')
            $employee_role_normalized = strtolower($app['employee_role']);
            if ($employee_role_normalized === 'hr_manager') {
                $employee_role_normalized = RoleHelper::ROLE_HR;
            } elseif (strpos($employee_role_normalized, '_manager') !== false && $employee_role_normalized !== 'managing_director') {
                $employee_role_normalized = RoleHelper::ROLE_MANAGER;
            }
            
            // Determine approval level and update accordingly
            if ($app['type'] === 'leave') {
                // Leave approval workflow
                if ($employee_role_normalized === RoleHelper::ROLE_HR) {
                    // HR leave: only needs Managing Director approval
                    if (RoleHelper::is_managing_director($conn, $approver_id)) {
                        $sql = "UPDATE applications 
                                SET manager_approval_status = ?,
                                    manager_comment = ?,
                                    status = ?,
                                    updated_at = NOW()
                                WHERE id = ?";
                        $final_status = ($status === 'approved') ? 'approved' : 'denied';
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$status, $comment, $final_status, $application_id]);
                    } else {
                        throw new Exception("Only Managing Director can approve HR leave");
                    }
                } elseif ($employee_role_normalized === RoleHelper::ROLE_MANAGER) {
                    // Manager leave: HR Manager → MD (2 approvals)
                    // Check if this is Finance Manager (they have different workflow for loans, but same for leave)
                    $is_finance_manager = ($app['employee_dept'] === RoleHelper::DEPT_FINANCE);
                    
                    if (RoleHelper::is_hr($conn, $approver_id)) {
                        // First approval by HR Manager
                        $sql = "UPDATE applications 
                                SET hr_approval_status = ?,
                                    hr_comment = ?,
                                    updated_at = NOW()
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$status, $comment, $application_id]);
                        
                        if ($status === 'approved') {
                            // Notify employee that HR Manager has approved (first approval)
                            $message = "Your leave application has been approved by HR Manager. Awaiting Managing Director approval.";
                            create_notification($conn, $app['employee_id'], $message, 'application');
                            
                            // Notify MD for second approval
                            $md_id = RoleHelper::get_managing_director_id($conn);
                            $employee_name = $app['first_name'] . ' ' . $app['last_name'];
                            if ($md_id) {
                                $message = "Manager leave application from {$employee_name} requires your approval (HR Manager approved)";
                                create_notification($conn, $md_id, $message, 'application');
                            }
                        } else {
                            // Denied by HR Manager - final
                            $sql = "UPDATE applications SET status = 'denied' WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$application_id]);
                            // Notify employee that leave was denied by HR Manager
                            $message = "Your leave application has been denied by HR Manager.";
                            create_notification($conn, $app['employee_id'], $message, 'application');
                        }
                    } elseif (RoleHelper::is_managing_director($conn, $approver_id)) {
                        // Second approval by MD
                        if ($app['hr_approval_status'] !== 'approved') {
                            throw new Exception("HR Manager approval required before MD approval");
                        }
                        
                        $sql = "UPDATE applications 
                                SET md_approval_status = ?,
                                    md_comment = ?,
                                    status = ?,
                                    updated_at = NOW()
                                WHERE id = ?";
                        $final_status = ($status === 'approved') ? 'approved' : 'denied';
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$status, $comment, $final_status, $application_id]);
                        
                        // Notify employee of final decision
                        $status_text = ($status === 'approved') ? 'approved' : 'denied';
                        $message = "Your leave application has been {$status_text} by Managing Director.";
                        create_notification($conn, $app['employee_id'], $message, 'application');
                        
                        // Deduct leave days if approved
                        if ($status === 'approved') {
                            self::deduct_leave_days($conn, $app);
                        }
                    } else {
                        throw new Exception("Unauthorized to approve this leave application");
                    }
                } else {
                    // Regular employee leave: Manager → HR
                    if (RoleHelper::is_manager($conn, $approver_id) && 
                        $approver_dept === $app['employee_dept']) {
                        // First approval by department manager
                        $sql = "UPDATE applications 
                                SET manager_approval_status = ?,
                                    manager_comment = ?,
                                    updated_at = NOW()
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$status, $comment, $application_id]);
                        
                        if ($status === 'approved') {
                            // Notify employee that manager has approved (first approval)
                            $message = "Your leave application has been approved by your manager. Awaiting HR approval.";
                            create_notification($conn, $app['employee_id'], $message, 'application');
                            
                            // Notify HR for second approval
                            $hr_id = RoleHelper::get_hr_id($conn);
                            if ($hr_id) {
                                $employee_name = $app['first_name'] . ' ' . $app['last_name'];
                                $message = "Leave application from {$employee_name} requires your approval (Manager approved)";
                                create_notification($conn, $hr_id, $message, 'application');
                            }
                        } else {
                            // Denied by manager - final
                            $sql = "UPDATE applications SET status = 'denied' WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$application_id]);
                            // Notify employee that leave was denied by manager
                            $message = "Your leave application has been denied by your manager.";
                            create_notification($conn, $app['employee_id'], $message, 'application');
                        }
                    } elseif (RoleHelper::is_hr($conn, $approver_id)) {
                        // Second approval by HR
                        if ($app['manager_approval_status'] !== 'approved') {
                            throw new Exception("Manager approval required before HR approval");
                        }
                        
                        $sql = "UPDATE applications 
                                SET hr_approval_status = ?,
                                    hr_comment = ?,
                                    status = ?,
                                    updated_at = NOW()
                                WHERE id = ?";
                        $final_status = ($status === 'approved') ? 'approved' : 'denied';
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$status, $comment, $final_status, $application_id]);
                        
                        // Notify employee of final decision
                        $status_text = ($status === 'approved') ? 'approved' : 'denied';
                        $message = "Your leave application has been {$status_text} by HR.";
                        create_notification($conn, $app['employee_id'], $message, 'application');
                        
                        // Deduct leave days if approved
                        if ($status === 'approved') {
                            self::deduct_leave_days($conn, $app);
                        }
                    } else {
                        throw new Exception("Unauthorized to approve this leave application");
                    }
                }
            } elseif ($app['type'] === 'loan') {
                // Loan approval workflow
                // Check if applicant is Finance Manager
                $is_finance_manager_loan = ($app['employee_dept'] === RoleHelper::DEPT_FINANCE && 
                                           ($employee_role_normalized === RoleHelper::ROLE_MANAGER || 
                                            strpos(strtolower($app['employee_role']), 'finance') !== false));
                
                if (RoleHelper::is_hr($conn, $approver_id)) {
                    // First approval by HR
                    $sql = "UPDATE applications 
                            SET hr_approval_status = ?,
                                hr_comment = ?,
                                updated_at = NOW()
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$status, $comment, $application_id]);
                    
                    if ($status === 'approved') {
                        // Timestamped first-approval notification to applicant (Harare time)
                        $harareNow = (new DateTime('now', new DateTimeZone('Africa/Harare')))->format('M d, Y H:i');
                        $employee_name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')) ?: 'An employee';
                        $message = "Your loan application received first approval by HR on {$harareNow}. Awaiting Finance Manager final approval.";
                        create_notification($conn, $app['employee_id'], $message, 'application');
                        
                        if ($is_finance_manager_loan) {
                            // Finance Manager loan: notify MD for second approval
                            $md_id = RoleHelper::get_managing_director_id($conn);
                            if ($md_id) {
                                $message = "Finance Manager loan application from {$employee_name} requires your approval (HR approved)";
                                create_notification($conn, $md_id, $message, 'application');
                            }
                        } else {
                            // Regular employee/manager loan (not Finance Manager): notify Finance Manager for second approval
                            $finance_manager_id = RoleHelper::get_finance_manager_id($conn);
                            if ($finance_manager_id) {
                                $message = "Loan application from {$employee_name} requires your approval (HR approved on {$harareNow})";
                                create_notification($conn, $finance_manager_id, $message, 'application');
                            }
                        }
                    } else {
                        // Denied by HR - final
                        $sql = "UPDATE applications SET status = 'denied' WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$application_id]);
                        // Timestamped denial notification to applicant (Harare time)
                        $harareNow = (new DateTime('now', new DateTimeZone('Africa/Harare')))->format('M d, Y H:i');
                        $message = "Your loan application was denied by HR on {$harareNow}.";
                        create_notification($conn, $app['employee_id'], $message, 'application');
                    }
                } elseif ($is_finance_manager_loan && RoleHelper::is_managing_director($conn, $approver_id)) {
                    // Finance Manager loan: Second approval by MD
                    if ($app['hr_approval_status'] !== 'approved') {
                        throw new Exception("HR approval required before MD approval");
                    }
                    
                    $sql = "UPDATE applications 
                            SET md_approval_status = ?,
                                md_comment = ?,
                                status = ?,
                                disbursed_amount = ?,
                                outstanding_balance = ?,
                                next_payment_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
                                updated_at = NOW()
                            WHERE id = ?";
                    $final_status = ($status === 'approved') ? 'approved' : 'denied';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        $status, 
                        $comment, 
                        $final_status,
                        $app['amount'],
                        $app['amount'],
                        $application_id
                    ]);
                    
                    // Notify employee of final decision
                    $status_text = ($status === 'approved') ? 'approved' : 'denied';
                    $message = "Your loan application has been {$status_text} by Managing Director.";
                    create_notification($conn, $app['employee_id'], $message, 'application');
                    // Notify HR (different message)
                    $hr_ids = $conn->query("SELECT employee_id FROM employee WHERE LOWER(role) IN ('hr', 'hr_manager') AND status = 'active'");
                    if ($hr_ids) {
                        $employee_name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                        foreach ($hr_ids->fetchAll(PDO::FETCH_COLUMN) as $hr_id) {
                            if ($status === 'approved') {
                                $msg_hr = "Finance Manager loan from {$employee_name} was approved by MD. Please prepare disbursement.";
                            } else {
                                $msg_hr = "Finance Manager loan from {$employee_name} was denied by MD.";
                            }
                            create_notification($conn, (int)$hr_id, $msg_hr, 'application');
                        }
                    }
                } elseif (!$is_finance_manager_loan && RoleHelper::is_manager($conn, $approver_id) && 
                          $approver_dept === RoleHelper::DEPT_FINANCE) {
                    // Regular employee/manager loan: Second approval by Finance Manager
                    // Finance Manager can approve loans from regular employees and regular managers (not Finance Manager's own)
                    if ($app['hr_approval_status'] !== 'approved') {
                        throw new Exception("HR approval required before Finance Manager approval");
                    }
                    
                    // Ensure Finance Manager is not approving their own loan
                    if ($app['employee_id'] == $approver_id) {
                        throw new Exception("Finance Manager cannot approve their own loan application");
                    }
                    
                    $sql = "UPDATE applications 
                            SET finance_approval_status = ?,
                                finance_comment = ?,
                                status = ?,
                                disbursed_amount = ?,
                                outstanding_balance = ?,
                                next_payment_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
                                updated_at = NOW()
                            WHERE id = ?";
                    $final_status = ($status === 'approved') ? 'approved' : 'denied';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        $status, 
                        $comment, 
                        $final_status,
                        $app['amount'],
                        $app['amount'],
                        $application_id
                    ]);
                    
                    // Timestamped final decision notifications (Harare time)
                    $harareNow = (new DateTime('now', new DateTimeZone('Africa/Harare')))->format('M d, Y H:i');
                    $status_text = ($status === 'approved') ? 'approved' : 'denied';
                    
                    // Notify applicant
                    if ($status === 'approved') {
                        $message = "Your loan application was approved by Finance Manager on {$harareNow}. Please go and see HR to collect your money.";
                    } else {
                        $message = "Your loan application was denied by Finance Manager on {$harareNow}.";
                    }
                    create_notification($conn, $app['employee_id'], $message, 'application');

                    // Notify HR as well (final approval/denial)
                    $hr_id = RoleHelper::get_hr_id($conn);
                    if ($hr_id) {
                        $employee_name = $app['first_name'] . ' ' . $app['last_name'];
                        if ($status === 'approved') {
                            $message = "Final approval done: {$employee_name}'s loan was approved by Finance Manager on {$harareNow}. Please prepare disbursement.";
                        } else {
                            $message = "Final decision done: {$employee_name}'s loan was denied by Finance Manager on {$harareNow}.";
                        }
                        create_notification($conn, $hr_id, $message, 'application');
                    }
                } else {
                    throw new Exception("Unauthorized to approve this loan application");
                }
            }
            
            // Log activity
            $activity_type = $app['type'] . '_approval';
            $description = ucfirst($app['type']) . " application #{$application_id} {$status} by " . 
                          RoleHelper::get_role($conn, $approver_id);
            ActivityLog::log($conn, $activity_type, $description, $approver_id, $application_id);
            
            // Notify all parties (only for final status changes, intermediate notifications are handled above)
            // Check if this is a final status change (status is 'approved' or 'denied', not just an intermediate approval)
            $sql_check = "SELECT status FROM applications WHERE id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$application_id]);
            $final_status = $stmt_check->fetchColumn();
            
            // Only notify via notify_parties if status is final (approved/denied), not for intermediate approvals
            if ($final_status === 'approved' || $final_status === 'denied') {
                // Refresh app data to get latest status
                $sql_refresh = "SELECT a.*, e.role as employee_role, e.department as employee_dept, 
                                       e.first_name, e.last_name
                                FROM applications a
                                JOIN employee e ON a.employee_id = e.employee_id
                                WHERE a.id = ?";
                $stmt_refresh = $conn->prepare($sql_refresh);
                $stmt_refresh->execute([$application_id]);
                $app_refresh = $stmt_refresh->fetch();
                self::notify_parties($conn, $application_id, $app_refresh, $final_status, $approver_id);
            }
            
            $conn->commit();
            return ['success' => true, 'message' => "Application {$status} successfully"];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Application workflow error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Notify all parties involved in application
     * Note: Employee notifications are sent at each approval step above, so we skip duplicate employee notifications here
     */
    private static function notify_parties($conn, $application_id, $app, $status, $approver_id)
    {
        // Employee notifications are already sent at each approval step above, so we skip here to avoid duplicates
        
        // Notify Managing Director (sees all activity)
        $md_id = RoleHelper::get_managing_director_id($conn);
        if ($md_id && $md_id != $approver_id) {
            $status_text = ($status === 'approved') ? 'approved' : 'denied';
            $employee_name = $app['first_name'] . ' ' . $app['last_name'];
            $message = "{$app['type']} application from {$employee_name} has been {$status_text}";
            create_notification($conn, $md_id, $message, 'activity');
        }
        
        // Notify other approvers if applicable
        if ($app['type'] === 'leave' && $status === 'approved') {
            // If manager approved, notify HR (already done above)
            // If HR approved, notify manager
            if (RoleHelper::is_hr($conn, $approver_id) && $app['manager_id']) {
                $message = "Leave application you approved has been finalized by HR";
                create_notification($conn, $app['manager_id'], $message, 'application');
            }
        } elseif ($app['type'] === 'loan') {
            // Loan-related notifications are handled explicitly in the loan workflow above
        }
    }
    
    /**
     * Deduct leave days when approved
     */
    private static function deduct_leave_days($conn, $app)
    {
        $leave_type = $app['leave_type'];
        $employee_id = $app['employee_id'];
        $requested_days = (int)$app['days_requested'];
        $column_to_update = '';
        
        switch ($leave_type) {
            case 'special':
                $column_to_update = 'special_leave_days_remaining';
                break;
            case 'sick':
                $column_to_update = 'sick_leave_days_remaining';
                break;
            case 'maternity':
                $column_to_update = 'maternity_leave_days_remaining';
                break;
            case 'normal':
                $column_to_update = 'normal_leave_days';
                break;
        }
        
        if (!empty($column_to_update)) {
            $sql = "UPDATE employee 
                   SET {$column_to_update} = GREATEST(0, {$column_to_update} - ?) 
                   WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$requested_days, $employee_id]);
        }
        
        // Record leave days in attendance
        $start = new DateTime($app['start_date']);
        $end = new DateTime($app['end_date']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $sql_att = "INSERT INTO attendance (employee_id, date, status) 
                       VALUES (?, ?, 'leave') 
                       ON DUPLICATE KEY UPDATE status = 'leave'";
            $stmt_att = $conn->prepare($sql_att);
            $stmt_att->execute([$employee_id, $date->format('Y-m-d')]);
        }
    }
}
