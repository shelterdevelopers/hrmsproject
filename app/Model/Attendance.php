<?php

if (!class_exists('Attendance')) {
class Attendance
{


    public static function get_admin_id($conn)
    {
        $sql = "SELECT employee_id FROM employee WHERE role = 'admin' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public static function record_repayment($conn, $application_id, $amount, $payment_method, $employee_id)
    {
        try {
            $conn->beginTransaction();

            // 1. Verify application
            $sql = "SELECT id, outstanding_balance, employee_id FROM applications 
                    WHERE id = ? AND employee_id = ? AND status = 'approved' FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$application_id, $employee_id]);
            $app = $stmt->fetch();

            if (!$app) {
                throw new Exception("Invalid loan application");
            }

            // 2. Insert repayment record
            $sql = "INSERT INTO loan_repayments 
                    (application_id, payment_date, amount_due, amount_paid, payment_method, payment_status, employee_comment)
                    VALUES (?, CURDATE(), ?, ?, ?, 'pending', 'Payment awaiting verification')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$application_id, $amount, $amount, $payment_method]);

            // 3. Create admin notification
            require_once "Notification.php";
            $admin_id = self::get_admin_id($conn);
            $message = "Loan payment awaiting verification for application #$application_id";
            create_notification($conn, $admin_id, $message, 'loan_payment_verification');

            $conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function verify_repayment($conn, $repayment_id, $admin_id, $action, $admin_comment)
    {
        try {
            $conn->beginTransaction();

            // Get repayment details
            $sql = "SELECT lr.*, a.employee_id, a.outstanding_balance 
                    FROM loan_repayments lr
                    JOIN applications a ON lr.application_id = a.id
                    WHERE lr.id = ? AND lr.payment_status = 'pending' FOR UPDATE";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$repayment_id]);
            $repayment = $stmt->fetch();

            if (!$repayment) {
                throw new Exception("Repayment not found or already processed");
            }

            // Update repayment status
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $sql = "UPDATE loan_repayments 
                    SET payment_status = ?, 
                        admin_comment = ?,
                        admin_id = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $admin_comment, $admin_id, $repayment_id]);

            if ($action === 'approve') {
                // Update loan balance
                $new_balance = $repayment['outstanding_balance'] - $repayment['amount_due'];
                $sql = "UPDATE applications 
                        SET outstanding_balance = ?,
                            next_payment_date = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_balance, $repayment['application_id']]);
            }

            // Create notification
            require_once "Notification.php";
            $message = "Your payment of $" . number_format($repayment['amount_due'], 2) . " has been $status";
            create_notification($conn, $repayment['employee_id'], $message, 'loan_payment');

            $conn->commit();
            return ['success' => true, 'message' => "Payment $status successfully"];
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Repayment verification error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function get_repayment_history($conn, $application_id)
    {
        $sql = "SELECT * FROM loan_repayments 
                WHERE application_id = ? 
                ORDER BY payment_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$application_id]);
        return $stmt->fetchAll();
    }



    // Record check-in
    public static function check_in($conn, $employee_id)
    {
        // Ensure timezone is set to Harare
        if (!defined('APP_TIMEZONE')) {
            define('APP_TIMEZONE', 'Africa/Harare');
        }
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set(APP_TIMEZONE);
        }
        
        $date = date('Y-m-d');
        $time = date('H:i:s');

        // Check if already checked in today
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $date]);

        if ($stmt->rowCount() > 0) {
            return false; // Already checked in
        }

        // Record attendance
        $sql = "INSERT INTO attendance (employee_id, date, check_in, status) 
                VALUES (?, ?, ?, 'present')";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$employee_id, $date, $time]);

        // Notify manager + MD about team check-in (best-effort)
        if ($ok) {
            try {
                require_once __DIR__ . "/Notification.php";
                require_once __DIR__ . "/RoleHelper.php";

                $sqlEmp = "SELECT first_name, last_name, manager_id FROM employee WHERE employee_id = ?";
                $stmtEmp = $conn->prepare($sqlEmp);
                $stmtEmp->execute([$employee_id]);
                $emp = $stmtEmp->fetch(\PDO::FETCH_ASSOC);
                $empName = ($emp && ($emp['first_name'] ?? null)) ? ($emp['first_name'] . ' ' . ($emp['last_name'] ?? '')) : "Employee #{$employee_id}";

                if (!empty($emp['manager_id'])) {
                    create_notification($conn, (int)$emp['manager_id'], "{$empName} checked in at {$time}", 'team_attendance');
                }

                $md_id = RoleHelper::get_managing_director_id($conn);
                if ($md_id) {
                    create_notification($conn, (int)$md_id, "{$empName} checked in at {$time}", 'activity');
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }
        }

        return $ok;
    }


    // Record check-out
    public static function check_out($conn, $employee_id)
    {
        // Ensure timezone is set to Harare
        if (!defined('APP_TIMEZONE')) {
            define('APP_TIMEZONE', 'Africa/Harare');
        }
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set(APP_TIMEZONE);
        }
        
        $date = date('Y-m-d');
        $time = date('H:i:s');

        $sql = "UPDATE attendance SET check_out = ? 
                WHERE employee_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$time, $employee_id, $date]);

        // Notify manager + MD about team check-out (best-effort)
        if ($ok) {
            try {
                require_once __DIR__ . "/Notification.php";
                require_once __DIR__ . "/RoleHelper.php";

                $sqlEmp = "SELECT first_name, last_name, manager_id FROM employee WHERE employee_id = ?";
                $stmtEmp = $conn->prepare($sqlEmp);
                $stmtEmp->execute([$employee_id]);
                $emp = $stmtEmp->fetch(\PDO::FETCH_ASSOC);
                $empName = ($emp && ($emp['first_name'] ?? null)) ? ($emp['first_name'] . ' ' . ($emp['last_name'] ?? '')) : "Employee #{$employee_id}";

                if (!empty($emp['manager_id'])) {
                    create_notification($conn, (int)$emp['manager_id'], "{$empName} checked out at {$time}", 'team_attendance');
                }

                $md_id = RoleHelper::get_managing_director_id($conn);
                if ($md_id) {
                    create_notification($conn, (int)$md_id, "{$empName} checked out at {$time}", 'activity');
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }
        }

        return $ok;
    }

    /**
     * Record check-out time on logout (no notifications). Used when user logs out;
     * check_out keeps updating to last logout time. Only updates if they have today's row with check_in.
     */
    public static function record_checkout_on_logout($conn, $employee_id)
    {
        if (!defined('APP_TIMEZONE')) {
            define('APP_TIMEZONE', 'Africa/Harare');
        }
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set(APP_TIMEZONE);
        }
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $sql = "UPDATE attendance SET check_out = ? WHERE employee_id = ? AND date = ? AND check_in IS NOT NULL";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$time, $employee_id, $date]);
    }
    
    /**
     * Auto check-out all employees at 5:00 PM (Harare time).
     * Only updates rows where check_out IS NULL (keeps previous logout time if they already logged out during the day).
     */
    public static function auto_checkout_all($conn)
    {
        // Ensure timezone is set to Harare
        if (!defined('APP_TIMEZONE')) {
            define('APP_TIMEZONE', 'Africa/Harare');
        }
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set(APP_TIMEZONE);
        }
        
        $checkout_time = '17:00:00'; // 5:00 PM Harare time
        $date = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Only auto-checkout if it's 5:00 PM or later
        if ($current_time < $checkout_time) {
            return ['success' => false, 'message' => 'Auto checkout only runs at 5:00 PM or later'];
        }
        
        // Get all employees who are checked in but not checked out today
        $sql = "SELECT a.attendance_id, a.employee_id, e.first_name, e.last_name
                FROM attendance a
                JOIN employee e ON a.employee_id = e.employee_id
                WHERE a.date = ?
                AND a.check_in IS NOT NULL
                AND a.check_out IS NULL
                AND e.status = 'active'";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$date]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $checked_out_count = 0;
        
        foreach ($employees as $employee) {
            // Auto check-out at 5pm
            $sql_update = "UPDATE attendance 
                           SET check_out = ?, auto_checked_out = TRUE
                           WHERE attendance_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if ($stmt_update->execute([$checkout_time, $employee['attendance_id']])) {
                $checked_out_count++;
                
                // Log activity if ActivityLog class exists
                try {
                    require_once __DIR__ . "/ActivityLog.php";
                    if (class_exists('ActivityLog')) {
                        ActivityLog::log(
                            $conn,
                            'attendance_auto_checkout',
                            "Auto check-out at 5:00 PM for {$employee['first_name']} {$employee['last_name']}",
                            $employee['employee_id'],
                            $employee['attendance_id']
                        );
                    }
                } catch (\Throwable $e) {
                    // Ignore if ActivityLog doesn't exist
                }
                
                // Create notification
                try {
                    require_once __DIR__ . "/Notification.php";
                    $message = "You were logged out at 5pm.";
                    create_notification($conn, $employee['employee_id'], $message, 'attendance');
                } catch (\Throwable $e) {
                    // Ignore notification errors
                }
            }
        }
        
        return [
            'success' => true,
            'count' => $checked_out_count,
            'message' => "Auto check-out completed. Checked out {$checked_out_count} employees."
        ];
    }

    // Get attendance records
    public static function get_attendance($conn, $employee_id, $month, $year)
    {
        $sql = "SELECT * FROM attendance 
                WHERE employee_id = ? 
                AND MONTH(date) = ? 
                AND YEAR(date) = ?
                ORDER BY date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $month, $year]);
        return $stmt->fetchAll();
    }

    // In Attendance.php
    public static function apply_leave(
        $conn,
        $employee_id,
        $date_or_start_date,
        $reason,
        $leave_type,
        $end_date = null
    ) {
        try {
            // Handle both single-day and multi-day leaves
            $start_date = $date_or_start_date;
            $end_date = $end_date ?: $date_or_start_date; // If no end_date, use start_date

            // Validate dates using strtotime (avoid ${} interpolation)
            $start_ts = strtotime($start_date);
            $end_ts   = strtotime($end_date);

            if ($start_ts === false || $end_ts === false) {
                $msg = sprintf(
                    'Invalid date format: start_date=%s end_date=%s',
                    $start_date,
                    $end_date
                );
                return ['success' => false, 'message' => $msg];
            }

            // Normalize and ensure start <= end
            $start = new DateTimeImmutable('@' . $start_ts);
            $end   = new DateTimeImmutable('@' . $end_ts);
            if ($start > $end) {
                return ['success' => false, 'message' => 'Start date must be on or before end date'];
            }

            // Calculate days requested (inclusive)
            $days_requested = $start->diff($end)->days + 1;

            // Get manager_id
            $sql = "SELECT manager_id FROM employee WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$employee_id]);
            $manager_id = $stmt->fetchColumn();

            if (!$manager_id) {
                return ['success' => false, 'message' => 'No manager assigned'];
            }

            // Insert application with leave_type
            $sql = "INSERT INTO applications (
            employee_id,
            manager_id,
            type,
            leave_type,
            start_date,
            end_date,
            days_requested,
            reason,
            status
        ) VALUES (?, ?, 'leave', ?, ?, ?, ?, ?, 'pending')";

            $stmt = $conn->prepare($sql);
            $success = $stmt->execute([
                $employee_id,
                $manager_id,
                $leave_type,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $days_requested,
                $reason
            ]);

            if ($success) {
                // Notify HR and the relevant manager (applies to all accounts)
                try {
                    require_once __DIR__ . "/Notification.php";
                    require_once __DIR__ . "/RoleHelper.php";
                    $emp = $conn->prepare("SELECT first_name, last_name FROM employee WHERE employee_id = ?");
                    $emp->execute([$employee_id]);
                    $emp = $emp->fetch(PDO::FETCH_ASSOC);
                    $applicant_name = $emp ? trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) : "Employee #{$employee_id}";
                    $msg = $applicant_name . " applied for leave (" . $start->format('d M Y') . " - " . $end->format('d M Y') . ", " . $leave_type . ") - pending approval";
                    create_notification($conn, (int)$manager_id, $msg, 'application');
                    $hrs = $conn->query("SELECT employee_id FROM employee WHERE LOWER(role) IN ('hr', 'hr_manager') AND status = 'active'");
                    if ($hrs) {
                        while ($row = $hrs->fetch(PDO::FETCH_ASSOC)) {
                            if (!empty($row['employee_id']) && (int)$row['employee_id'] !== (int)$manager_id) {
                                create_notification($conn, (int)$row['employee_id'], $msg, 'application');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("Leave notification error: " . $e->getMessage());
                }
                return ['success' => true, 'message' => 'Leave application submitted successfully'];
            } else {
                error_log("Insert failed: " . print_r($stmt->errorInfo(), true));
                return ['success' => false, 'message' => 'Database insert failed'];
            }
        } catch (PDOException $e) {
            error_log("Database error in apply_leave: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    // In app/Model/Attendance.php
    // In app/Model/Attendance.php
public static function update_application($conn, $application_id, $status, $comment)
{
    // First, get the application details
    $sql = "SELECT * FROM applications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$application_id]);
    $app = $stmt->fetch();

    if (!$app) return false;

    // Update the application status itself
    $sql = "UPDATE applications SET status = ?, manager_comment = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([$status, $comment, $application_id]);

    // --- UPDATED DEDUCTION LOGIC ---
    if ($success && $status == 'approved' && $app['type'] == 'leave') {
        $leave_type = $app['leave_type'];
        $employee_id = $app['employee_id'];
        $requested_days = (int)$app['days_requested'];
        $column_to_update = '';

        // Determine which remaining balance column to decrease
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

        // If a valid leave type was found, perform the deduction
        if (!empty($column_to_update)) {
            $sql_deduct = "UPDATE employee 
                           SET {$column_to_update} = GREATEST(0, {$column_to_update} - ?) 
                           WHERE employee_id = ?";
            $stmt_deduct = $conn->prepare($sql_deduct);
            $stmt_deduct->execute([$requested_days, $employee_id]);
        }

        // Record each day in attendance (this part remains the same)
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

    return $success;
}
    public static function get_admin_applications($conn)
    {
        $sql = "SELECT a.*, 
                   e.first_name, 
                   e.last_name,
                   e1.first_name AS manager_first_name,
                   e1.last_name AS manager_last_name
            FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee e1 ON a.manager_id = e1.employee_id
            WHERE a.type = 'loan' AND a.status = 'pending'
            ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all pending applications that HR Manager needs to approve
     * - Other managers' leave applications (first approval)
     * - Regular employees' leave applications (second approval, after department manager)
     * - All loan applications (first approval)
     */
    public static function get_hr_pending_applications($conn)
    {
        // Check if md_approval_status column exists
        $check_md_column = "SHOW COLUMNS FROM applications LIKE 'md_approval_status'";
        $stmt_check = $conn->query($check_md_column);
        $md_column_exists = $stmt_check->rowCount() > 0;
        
        $applications = [];
        
        // 1. Other managers' leave applications (needs HR Manager first approval)
        if ($md_column_exists) {
            $sql_mgr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                             FROM applications a
                             JOIN employee e ON a.employee_id = e.employee_id
                             WHERE a.type = 'leave' 
                             AND a.status = 'pending'
                             AND (e.role = 'manager' OR e.role LIKE '%_manager')
                             AND e.role != 'hr_manager'
                             AND e.role != 'hr'
                             AND (a.hr_approval_status IS NULL OR a.hr_approval_status = 'pending')";
        } else {
            $sql_mgr_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                             FROM applications a
                             JOIN employee e ON a.employee_id = e.employee_id
                             WHERE a.type = 'leave' 
                             AND a.status = 'pending'
                             AND (e.role = 'manager' OR e.role LIKE '%_manager')
                             AND e.role != 'hr_manager'
                             AND e.role != 'hr'
                             AND a.hr_approval_status IS NULL";
        }
        $stmt = $conn->prepare($sql_mgr_leave);
        $stmt->execute();
        $manager_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 2. Regular employees' leave applications (needs HR Manager second approval, after department manager)
        $sql_emp_leave = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department,
                         m.first_name AS first_approver_first_name, m.last_name AS first_approver_last_name
                         FROM applications a
                         JOIN employee e ON a.employee_id = e.employee_id
                         LEFT JOIN employee m ON a.manager_id = m.employee_id
                         WHERE a.type = 'leave' 
                         AND a.status = 'pending'
                         AND e.role = 'employee'
                         AND a.manager_approval_status = 'approved'
                         AND (a.hr_approval_status IS NULL OR a.hr_approval_status = 'pending')";
        $stmt = $conn->prepare($sql_emp_leave);
        $stmt->execute();
        $employee_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 3. All loan applications (needs HR Manager first approval)
        $sql_loans = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                     FROM applications a
                     JOIN employee e ON a.employee_id = e.employee_id
                     WHERE a.type = 'loan' 
                     AND a.status = 'pending'
                     AND (a.hr_approval_status IS NULL OR a.hr_approval_status = 'pending')";
        $stmt = $conn->prepare($sql_loans);
        $stmt->execute();
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        return array_merge($manager_leaves, $employee_leaves, $loans);
    }

    public static function get_hr_approved_loan_applications($conn)
    {
        // Finance Manager final approval queue:
        // - application is a loan
        // - HR has approved at the first level
        // - overall status is still pending (not yet finalized by Finance Manager)
        // - EXCLUDE Finance Manager's own loans (those go to MD, not Finance Manager)
        // - Include regular employee loans AND regular manager loans (from all departments)
        require_once __DIR__ . '/RoleHelper.php';
        $sql = "SELECT a.*,
                   e.first_name,
                   e.last_name,
                   e.role as employee_role,
                   e.department as employee_department,
                   e1.first_name AS manager_first_name,
                   e1.last_name AS manager_last_name,
                   (
                       SELECT al.created_at
                       FROM activity_logs al
                       WHERE al.related_id = a.id
                         AND al.activity_type = 'loan_approval'
                         AND (
                             al.description LIKE '%approved by hr%'
                             OR al.description LIKE '%approved by hr_manager%'
                         )
                       ORDER BY al.created_at DESC
                       LIMIT 1
                   ) AS hr_first_approved_at
            FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee e1 ON a.manager_id = e1.employee_id
            WHERE a.type = 'loan'
              AND a.status = 'pending'
              AND a.hr_approval_status = 'approved'
              AND (
                  -- Include regular employees (role = 'employee')
                  e.role = 'employee'
                  OR
                  -- Include regular managers (not Finance Manager)
                  (
                      (e.role = 'manager' OR e.role LIKE '%_manager')
                      AND NOT (
                          LOWER(TRIM(e.department)) = LOWER(?)
                          AND (LOWER(e.role) LIKE '%finance%' OR e.role = 'finance_manager')
                      )
                  )
              )
            ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([RoleHelper::DEPT_FINANCE]);
        return $stmt->fetchAll();
    }

    /**
     * Get all pending applications that need MD approval.
     * Same logic as md_approvals.php so dashboard "Pending Approvals" matches the tab.
     * Returns: HR leaves + other managers' leaves (excl. hr_manager) + Finance Manager loans.
     */
    public static function get_md_pending_applications($conn)
    {
        $check = "SHOW COLUMNS FROM applications LIKE 'md_approval_status'";
        $md_column_exists = $conn->query($check)->rowCount() > 0;
        $applications = [];

        // 1. HR Manager leave applications (needs MD only)
        if ($md_column_exists) {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'leave' AND a.status = 'pending'
                    AND (e.role = 'hr' OR e.role = 'hr_manager')
                    AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')
                    ORDER BY a.created_at DESC";
        } else {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'leave' AND a.status = 'pending'
                    AND (e.role = 'hr' OR e.role = 'hr_manager')
                    AND a.manager_approval_status IS NULL ORDER BY a.created_at DESC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $hr_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 2. Other managers' leave applications (after HR approval, needs MD) – exclude hr_manager
        if ($md_column_exists) {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'leave' AND a.status = 'pending'
                    AND (e.role = 'manager' OR e.role LIKE '%_manager') AND e.role != 'hr_manager' AND e.role != 'hr'
                    AND a.hr_approval_status = 'approved'
                    AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')
                    ORDER BY a.created_at DESC";
        } else {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'leave' AND a.status = 'pending'
                    AND (e.role = 'manager' OR e.role LIKE '%_manager') AND e.role != 'hr_manager' AND e.role != 'hr'
                    AND a.hr_approval_status = 'approved' AND a.md_approval_status IS NULL
                    ORDER BY a.created_at DESC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $mgr_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 3. Finance Manager loan applications (after HR approval, needs MD)
        // Finance Manager is identified by: being in Finance department with manager role
        // OR having role = 'finance_manager'
        // Simplified query to catch all Finance Manager loans
        if ($md_column_exists) {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a 
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'loan' 
                    AND a.status = 'pending'
                    AND a.hr_approval_status = 'approved'
                    AND (a.md_approval_status IS NULL OR a.md_approval_status = 'pending')
                    AND (
                        LOWER(TRIM(e.role)) = 'finance_manager'
                        OR LOWER(TRIM(e.role)) LIKE '%finance%manager%'
                        OR (LOWER(TRIM(e.department)) = 'finance' 
                            AND (LOWER(TRIM(e.role)) LIKE '%manager%' 
                                 OR LOWER(TRIM(e.role)) = 'manager'
                                 OR LOWER(TRIM(e.role)) LIKE '%_manager'))
                    )
                    ORDER BY a.created_at DESC";
        } else {
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                    FROM applications a 
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.type = 'loan' 
                    AND a.status = 'pending'
                    AND a.hr_approval_status = 'approved'
                    AND a.md_approval_status IS NULL
                    AND (
                        LOWER(TRIM(e.role)) = 'finance_manager'
                        OR LOWER(TRIM(e.role)) LIKE '%finance%manager%'
                        OR (LOWER(TRIM(e.department)) = 'finance' 
                            AND (LOWER(TRIM(e.role)) LIKE '%manager%' 
                                 OR LOWER(TRIM(e.role)) = 'manager'
                                 OR LOWER(TRIM(e.role)) LIKE '%_manager'))
                    )
                    ORDER BY a.created_at DESC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $fm_loans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_merge($hr_leaves, $mgr_leaves, $fm_loans);
    }

    public static function get_pending_applications($conn, $manager_id, $department = null)
    {
        if ($department) {
            // For managers: get pending leave applications from their department
            // These are applications that need the manager's first approval
            // (status is pending and manager_approval_status is NULL or pending)
            $sql = "SELECT a.*, e.first_name, e.last_name, e.role as employee_role, e.department
                FROM applications a
                JOIN employee e ON a.employee_id = e.employee_id
                WHERE a.type = 'leave' 
                AND e.department = ?
                AND a.status = 'pending'
                AND (a.manager_approval_status IS NULL OR a.manager_approval_status = 'pending')
                ORDER BY a.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$department]);
        } else {
            // Original logic for other cases
        $sql = "SELECT a.*, e.first_name, e.last_name 
            FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE (a.manager_id = ? OR (a.type = 'loan' AND ? IN (
                SELECT employee_id FROM employee WHERE role = 'admin'
            )))
            AND a.status = 'pending'
            ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$manager_id, $manager_id]);
        }
        return $stmt->fetchAll();
    }



    public static function get_employee_applications($conn, $employee_id)
    {
        $sql = "SELECT a.*, 
                   e1.first_name AS manager_first_name, 
                   e1.last_name AS manager_last_name
            FROM applications a
            LEFT JOIN employee e1 ON a.manager_id = e1.employee_id
            WHERE a.employee_id = ?
            ORDER BY a.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }


    public static function apply_loan($conn, $employee_id, $amount, $repayment_plan, $reason)
    {
        error_log("Attempting to apply loan for employee $employee_id");

        try {
            // Get admin ID (assuming admin has role 'admin')
            $sql = "SELECT employee_id FROM employee WHERE role = 'admin' LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $admin_id = $stmt->fetchColumn();

            if (!$admin_id) {
                return ['success' => false, 'message' => 'No admin found to approve loan'];
            }

            // Create loan application
            $sql = "INSERT INTO applications (
                employee_id, 
                manager_id, 
                type, 
                amount, 
                repayment_plan, 
                reason,
                status
            ) VALUES (?, ?, 'loan', ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $success = $stmt->execute([
                $employee_id,
                $admin_id,
                $amount,
                $repayment_plan,
                $reason
            ]);

            if ($success) {
                // Notify HR and the relevant manager (applies to all accounts)
                try {
                    require_once __DIR__ . "/Notification.php";
                    require_once __DIR__ . "/RoleHelper.php";
                    $emp = $conn->prepare("SELECT first_name, last_name, manager_id FROM employee WHERE employee_id = ?");
                    $emp->execute([$employee_id]);
                    $emp = $emp->fetch(PDO::FETCH_ASSOC);
                    $applicant_name = $emp ? trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) : "Employee #{$employee_id}";
                    $msg = $applicant_name . " applied for a loan - pending approval";
                    if (!empty($emp['manager_id'])) {
                        create_notification($conn, (int)$emp['manager_id'], $msg, 'application');
                    }
                    $hrs = $conn->query("SELECT employee_id FROM employee WHERE LOWER(role) IN ('hr', 'hr_manager') AND status = 'active'");
                    if ($hrs) {
                        while ($row = $hrs->fetch(PDO::FETCH_ASSOC)) {
                            if (!empty($row['employee_id'])) {
                                create_notification($conn, (int)$row['employee_id'], $msg, 'application');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("Loan notification error: " . $e->getMessage());
                }

                error_log("Loan application submitted successfully");
                return ['success' => true];
            }
        } catch (PDOException $e) {
            error_log("Database error in apply_loan: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public static function get_responded_applications($conn, $manager_id, $department = null, $filter_loans = false, $is_hr = false)
    {
        require_once __DIR__ . '/RoleHelper.php';
        
        // For HR: check hr_approval_status instead of manager_id
        if ($is_hr) {
            if ($filter_loans) {
                // HR should see all applications they've responded to (both leave and loans)
                // But if filter_loans is true, only show loans
                $sql = "SELECT a.*, e.first_name, e.last_name 
                    FROM applications a
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.hr_approval_status IS NOT NULL 
                    AND a.hr_approval_status != 'pending'
                    AND a.type = 'loan'
                    ORDER BY a.updated_at DESC";
            } else {
                // HR sees all applications they've responded to (both leave and loans)
                $sql = "SELECT a.*, e.first_name, e.last_name 
                    FROM applications a
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.hr_approval_status IS NOT NULL 
                    AND a.hr_approval_status != 'pending'
                    ORDER BY a.updated_at DESC";
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        
        // For department managers (except Finance), only show leave applications
        if ($department && $filter_loans) {
            $sql = "SELECT a.*, e.first_name, e.last_name 
                FROM applications a
                JOIN employee e ON a.employee_id = e.employee_id
                WHERE a.manager_id = ? 
                AND a.status != 'pending'
                AND a.type = 'leave'
                ORDER BY a.updated_at DESC";
        } else {
        $sql = "SELECT a.*, e.first_name, e.last_name 
            FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.manager_id = ? AND a.status != 'pending'
            ORDER BY a.updated_at DESC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([$manager_id]);
        return $stmt->fetchAll();
    }

    public static function get_application_by_id($conn, $application_id)
    {
        $sql = "SELECT a.*, e.manager_id 
            FROM applications a
            JOIN employee e ON a.employee_id = e.employee_id
            WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$application_id]);
        return $stmt->fetch();
    }

    /**
     * Delete an application (leave or loan). Caller must check permissions.
     * loan_repayments cascade; notifications.application_id set null.
     */
    public static function delete_application($conn, $application_id)
    {
        $application_id = (int) $application_id;
        if ($application_id <= 0) return false;
        try {
            $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
            return $stmt->execute([$application_id]);
        } catch (PDOException $e) {
            error_log("Database error in delete_application: " . $e->getMessage());
            return false;
        }
    }

    public static function get_admin_responded_applications($conn)
    {
        $sql = "SELECT a.*, 
               e.first_name, 
               e.last_name,
               e1.first_name AS manager_first_name,
               e1.last_name AS manager_last_name
        FROM applications a
        JOIN employee e ON a.employee_id = e.employee_id
        LEFT JOIN employee e1 ON a.manager_id = e1.employee_id
        WHERE a.type = 'loan' AND a.status != 'pending'
        ORDER BY a.updated_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // In Attendance.php, enhance the record_repayment method:
    public static function get_pending_repayments($conn)
    {
        $sql = "SELECT lr.*, 
                   a.employee_id, 
                   a.amount as loan_amount,
                   a.disbursed_amount,
                   a.outstanding_balance,
                   a.repayment_plan,
                   a.type, 
                   e.first_name, 
                   e.last_name,
                   m.first_name as manager_first_name,
                   m.last_name as manager_last_name
            FROM loan_repayments lr
            JOIN applications a ON lr.application_id = a.id
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee m ON a.manager_id = m.employee_id
            WHERE lr.payment_status = 'pending'
            ORDER BY lr.payment_date ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public static function calculate_due_amount($loan_data)
    {
        if (!isset($loan_data['outstanding_balance']) || !isset($loan_data['repayment_plan'])) {
            return 0;
        }

        return ($loan_data['disbursed_amount'] / $loan_data['repayment_plan']);
    }


    public static function get_employee_attendance($conn, $employee_id, $month, $year)
    {
        $sql = "SELECT * FROM attendance 
            WHERE employee_id = ? 
            AND MONTH(date) = ? 
            AND YEAR(date) = ?
            ORDER BY date";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $month, $year]);
        return $stmt->fetchAll();
    }

    public static function get_employee_attendance_json($conn, $employee_id, $month, $year)
    {
        $attendance = self::get_employee_attendance($conn, $employee_id, $month, $year);

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        $calendar = [];
        $current_day = 1;

        $attendance_map = [];
        foreach ($attendance as $record) {
            $day = date('j', strtotime($record['date']));
            $attendance_map[$day] = [
                'status' => $record['status'],
                'check_in' => $record['check_in'] ? date('h:i A', strtotime($record['check_in'])) : 'N/A',
                'check_out' => $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : 'N/A'
            ];
        }

        for ($i = 0; $i < 6; $i++) {
            $week = [];
            for ($j = 0; $j < 7; $j++) {
                if (($i == 0 && $j < $day_of_week) || $current_day > $days_in_month) {
                    $week[] = null;
                } else {
                    $day_data = [
                        'day' => $current_day,
                        'attendance' => $attendance_map[$current_day] ?? null
                    ];
                    $week[] = $day_data;
                    $current_day++;
                }
            }
            $calendar[] = $week;
        }

        // Calculate stats - absent should exclude days with any attendance record (present, late, leave, holiday)
        $present_count = count(array_filter($attendance, fn($a) => $a['status'] == 'present'));
        $late_count = count(array_filter($attendance, fn($a) => $a['status'] == 'late'));
        $leave_count = count(array_filter($attendance, fn($a) => $a['status'] == 'leave'));
        $holiday_count = count(array_filter($attendance, fn($a) => $a['status'] == 'holiday'));
        // Absent = total days - days with any attendance record (present, late, leave, holiday)
        $absent_count = $days_in_month - ($present_count + $late_count + $leave_count + $holiday_count);

        return [
            'month_name' => date('F Y', $first_day),
            'calendar' => $calendar,
            'stats' => [
                'present' => $present_count,
                'absent' => max(0, $absent_count), // Ensure non-negative
                'late' => $late_count,
                'leave' => $leave_count
            ]
        ];
    }
    } // End of class Attendance
} // End of class_exists check
