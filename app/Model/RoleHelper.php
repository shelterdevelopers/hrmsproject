<?php

class RoleHelper
{
    // Role constants
    const ROLE_MANAGING_DIRECTOR = 'managing_director';
    const ROLE_HR = 'hr';
    const ROLE_MANAGER = 'manager';
    const ROLE_EMPLOYEE = 'employee';
    const ROLE_ADMIN = 'admin';
    
    // Department constants (used for logic e.g. Finance Manager checks)
    const DEPT_SALES = 'Sales';
    const DEPT_FINANCE = 'Finance';
    const DEPT_OPERATIONS = 'Operations';
    const DEPT_CORPORATE_SERVICES = 'Corporate Services';

    /** Canonical department list for dropdowns – single source of truth (no "Sales" vs "SALES AND MARKETING" split) */
    public static function get_canonical_departments()
    {
        return [
            'CORPORATE SERVICES',
            'FINANCE AND ACCOUNTS',
            'OPERATIONS',
            'SALES AND MARKETING',
            'ETOSHA',
        ];
    }

    /** Normalize department for display/grouping so "Sales" and "SALES AND MARKETING" count as one */
    public static function normalize_department($department)
    {
        if ($department === null || $department === '') return $department;
        $d = trim($department);
        if (strcasecmp($d, 'Sales') === 0) return 'SALES AND MARKETING';
        return $d;
    }
    
    /**
     * Get user's role
     */
    public static function get_role($conn, $employee_id)
    {
        $sql = "SELECT role FROM employee WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Get user's executive_member flag
     */
    public static function is_executive_member($conn, $employee_id)
    {
        $sql = "SELECT executive_member FROM employee WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return (int)($stmt->fetchColumn() ?? 0) === 1;
    }
    
    /**
     * Get user's department
     */
    public static function get_department($conn, $employee_id)
    {
        $sql = "SELECT department FROM employee WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Check if user is Managing Director
     */
    public static function is_managing_director($conn, $employee_id)
    {
        $role = self::get_role($conn, $employee_id);
        return strtolower($role) === self::ROLE_MANAGING_DIRECTOR;
    }
    
    /**
     * Check if user is HR
     */
    public static function is_hr($conn, $employee_id)
    {
        $role = self::get_role($conn, $employee_id);
        $role_lower = strtolower($role);
        // Handle both 'hr' and 'hr_manager' role names
        return ($role_lower === self::ROLE_HR || $role_lower === 'hr_manager');
    }
    
    /**
     * Check if user is System Admin
     */
    public static function is_admin($conn, $employee_id)
    {
        $role = self::get_role($conn, $employee_id);
        $role_lower = strtolower($role);
        return ($role_lower === self::ROLE_ADMIN || $role_lower === 'admin');
    }
    
    /**
     * Get System Admin ID
     */
    public static function get_admin_id($conn)
    {
        $sql = "SELECT employee_id FROM employee WHERE LOWER(role) = 'admin' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Check if user is a Manager
     */
    public static function is_manager($conn, $employee_id)
    {
        // In this codebase, "manager" can be represented in multiple ways:
        // - role column set to manager/Manager
        // - executive_member flag set to 1
        // - user has direct reports (someone has manager_id = this employee_id)
        $sql = "SELECT 
                    role,
                    executive_member,
                    (SELECT COUNT(*) FROM employee e2 WHERE e2.manager_id = e.employee_id) AS direct_reports
                FROM employee e
                WHERE e.employee_id = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return false;

        $role = strtolower((string)($row['role'] ?? ''));
        // Check if role is exactly 'manager' or ends with '_manager' (e.g., 'sales_manager', 'finance_manager')
        $is_role_manager = ($role === self::ROLE_MANAGER || strpos($role, '_manager') !== false);
        $is_exec = ((int)($row['executive_member'] ?? 0) === 1);
        $has_reports = ((int)($row['direct_reports'] ?? 0) > 0);

        return $is_role_manager || $is_exec || $has_reports;
    }
    
    /**
     * Get Managing Director ID
     */
    public static function get_managing_director_id($conn)
    {
        $sql = "SELECT employee_id FROM employee WHERE role = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([self::ROLE_MANAGING_DIRECTOR]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get HR ID
     */
    public static function get_hr_id($conn)
    {
        // Handle both 'hr' and 'hr_manager' role names
        $sql = "SELECT employee_id FROM employee WHERE LOWER(role) IN ('hr', 'hr_manager') LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Get Finance Manager ID
     */
    public static function get_finance_manager_id($conn)
    {
        // Handle both 'manager' and 'finance_manager' role names
        $sql = "SELECT employee_id FROM employee 
                WHERE department = ? 
                AND (LOWER(role) = 'manager' OR LOWER(role) = 'finance_manager' OR LOWER(role) LIKE '%_manager')
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([self::DEPT_FINANCE]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get department manager for a given department
     */
    public static function get_department_manager($conn, $department)
    {
        // Handle role names like 'sales_manager', 'operations_manager', etc.
        $sql = "SELECT employee_id FROM employee 
                WHERE department = ? 
                AND (LOWER(role) = 'manager' OR LOWER(role) LIKE '%_manager')
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$department]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get employees in a department (case-insensitive match so "Corporate Services" and "CORPORATE SERVICES" both match).
     * Used for department announcements: only these employees receive the manager's department announcement.
     */
    public static function get_department_employees($conn, $department)
    {
        $dept = trim((string) $department);
        if ($dept === '') {
            return [];
        }
        $sql = "SELECT employee_id FROM employee 
                WHERE LOWER(TRIM(department)) = LOWER(?) AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$dept]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Count department employees excluding managers (matches Department Attendance tab dropdown).
     * Used so dashboard "Department Employees" matches the count in the tab.
     */
    public static function get_department_employee_count_excluding_managers($conn, $department)
    {
        $ids = self::get_department_employees($conn, $department);
        if (empty($ids)) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT employee_id, role FROM employee 
                WHERE employee_id IN ($placeholders) 
                ORDER BY first_name, last_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute($ids);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        foreach ($all as $emp) {
            $role_lower = strtolower((string)($emp['role'] ?? ''));
            $is_manager_role = ($role_lower === 'manager' || strpos($role_lower, '_manager') !== false
                || $role_lower === 'finance_manager' || $role_lower === 'hr_manager' || $role_lower === 'managing_director');
            if ($is_manager_role) {
                continue;
            }
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM employee WHERE manager_id = ? AND status = 'active'");
            $stmt_check->execute([$emp['employee_id']]);
            if ($stmt_check->fetchColumn() > 0) {
                continue;
            }
            $count++;
        }
        return $count;
    }
    
    /**
     * Check if user can approve leave for employee
     */
    public static function can_approve_leave($conn, $approver_id, $employee_id)
    {
        $approver_role = self::get_role($conn, $approver_id);
        $approver_dept = self::get_department($conn, $approver_id);
        $employee_dept = self::get_department($conn, $employee_id);
        $employee_role = self::get_role($conn, $employee_id);
        $employee_manager = self::get_department_manager($conn, $employee_dept);
        
        // Normalize employee role
        $employee_role_normalized = strtolower($employee_role);
        if ($employee_role_normalized === 'hr_manager') {
            $employee_role_normalized = self::ROLE_HR;
        } elseif (strpos($employee_role_normalized, '_manager') !== false && $employee_role_normalized !== 'managing_director') {
            $employee_role_normalized = self::ROLE_MANAGER;
        }
        
        // HR can approve:
        // 1. Manager leave (first approval) - any manager including Finance Manager
        // 2. Regular employee leave (second approval, after department manager)
        if (self::is_hr($conn, $approver_id)) {
            // HR can approve manager leave as first approval
            if ($employee_role_normalized === self::ROLE_MANAGER) {
                return true;
            }
            // HR can approve regular employee leave as second approval (after department manager)
            if ($employee_role_normalized === 'employee') {
                return true;
            }
        }
        
        // Department manager can approve leave for their department (first approval for employees)
        if (strtolower($approver_role) === self::ROLE_MANAGER && 
            $approver_dept === $employee_dept && 
            $approver_id == $employee_manager &&
            $employee_role_normalized === 'employee') {
            return true;
        }
        
        // Managing Director can approve:
        // 1. HR leave (only approval)
        // 2. Manager leave (second approval, after HR)
        if (self::is_managing_director($conn, $approver_id)) {
            if ($employee_role_normalized === self::ROLE_HR) {
                return true;
            }
            if ($employee_role_normalized === self::ROLE_MANAGER) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user can approve loan
     */
    public static function can_approve_loan($conn, $approver_id, $application_id = null)
    {
        $approver_role = self::get_role($conn, $approver_id);
        $approver_dept = self::get_department($conn, $approver_id);
        
        // HR can approve loans (first approval) - handles both 'hr' and 'hr_manager' roles
        // BUT HR cannot approve HR Manager's own loans (those go to Finance Manager first)
        if (self::is_hr($conn, $approver_id)) {
            if ($application_id) {
                $sql = "SELECT e.employee_id, e.role 
                        FROM applications a
                        JOIN employee e ON a.employee_id = e.employee_id
                        WHERE a.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$application_id]);
                $app = $stmt->fetch();
                
                // If it's HR Manager's own loan, they can't approve it (goes to Finance Manager first)
                if ($app && $app['employee_id'] == $approver_id && 
                    (strtolower($app['role']) === 'hr_manager' || strtolower($app['role']) === 'hr')) {
                    return false; // HR Manager's own loan goes to Finance Manager first
                }
            }
            return true;
        }
        
        // Finance Manager can approve loans (second approval for regular managers, first approval for HR Manager)
        if (strtolower($approver_role) === self::ROLE_MANAGER && 
            $approver_dept === self::DEPT_FINANCE) {
            if ($application_id) {
                $sql = "SELECT e.employee_id, e.role, e.department, a.hr_approval_status, a.finance_approval_status
                        FROM applications a
                        JOIN employee e ON a.employee_id = e.employee_id
                        WHERE a.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$application_id]);
                $app = $stmt->fetch();
                
                if ($app) {
                    // If it's Finance Manager's own loan, they can't approve it
                    if ($app['employee_id'] == $approver_id && 
                        strcasecmp($app['department'], self::DEPT_FINANCE) === 0) {
                        return false; // Finance Manager's own loan goes to MD
                    }
                    
                    // Check if it's HR Manager's loan (Finance Manager can approve as first approver)
                    $applicant_role = strtolower($app['role'] ?? '');
                    $is_hr_manager = ($applicant_role === 'hr_manager' || $applicant_role === 'hr');
                    if ($is_hr_manager) {
                        // Finance Manager can approve HR Manager loans (first approval, no HR approval needed)
                        return true;
                    }
                    
                    // For regular loans, Finance Manager needs HR approval first
                    if ($app['hr_approval_status'] !== 'approved') {
                        return false;
                    }
                }
            }
            return true;
        }
        
        // Managing Director can approve Finance Manager's loans (after HR approval)
        if (self::is_managing_director($conn, $approver_id)) {
            if ($application_id) {
                $sql = "SELECT e.role, e.department, a.hr_approval_status
                        FROM applications a
                        JOIN employee e ON a.employee_id = e.employee_id
                        WHERE a.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$application_id]);
                $app = $stmt->fetch();
                
                // MD can approve Finance Manager's loans after HR approval
                if ($app && strcasecmp($app['department'], self::DEPT_FINANCE) === 0 &&
                    (strtolower($app['role']) === 'manager' || strpos(strtolower($app['role']), '_manager') !== false) &&
                    $app['hr_approval_status'] === 'approved') {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get approval level for application
     */
    public static function get_approval_level($conn, $application_id)
    {
        $sql = "SELECT 
                   a.type,
                   a.status,
                   a.manager_approval_status,
                   a.hr_approval_status,
                   a.finance_approval_status,
                   e.department,
                   e.role as employee_role
                FROM applications a
                JOIN employee e ON a.employee_id = e.employee_id
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$application_id]);
        $app = $stmt->fetch();
        
        if (!$app) return null;
        
        if ($app['type'] === 'leave') {
            // Leave: Manager → HR (or MD for HR employees)
            if ($app['employee_role'] === self::ROLE_HR) {
                // HR leave: only needs MD approval
                return [
                    'needs_approval' => !$app['manager_approval_status'] || $app['manager_approval_status'] === 'pending',
                    'approver_role' => self::ROLE_MANAGING_DIRECTOR,
                    'current_level' => $app['manager_approval_status'] ? 'approved' : 'pending'
                ];
            } else {
                // Regular leave: Manager → HR
                if (!$app['manager_approval_status'] || $app['manager_approval_status'] === 'pending') {
                    return [
                        'needs_approval' => true,
                        'approver_role' => self::ROLE_MANAGER,
                        'current_level' => 'first'
                    ];
                } elseif ($app['manager_approval_status'] === 'approved' && 
                          (!$app['hr_approval_status'] || $app['hr_approval_status'] === 'pending')) {
                    return [
                        'needs_approval' => true,
                        'approver_role' => self::ROLE_HR,
                        'current_level' => 'second'
                    ];
                }
            }
        } elseif ($app['type'] === 'loan') {
            // Loan: HR → Finance Manager
            if (!$app['hr_approval_status'] || $app['hr_approval_status'] === 'pending') {
                return [
                    'needs_approval' => true,
                    'approver_role' => self::ROLE_HR,
                    'current_level' => 'first'
                ];
            } elseif ($app['hr_approval_status'] === 'approved' && 
                      (!$app['finance_approval_status'] || $app['finance_approval_status'] === 'pending')) {
                return [
                    'needs_approval' => true,
                    'approver_role' => self::ROLE_MANAGER,
                    'approver_dept' => self::DEPT_FINANCE,
                    'current_level' => 'second'
                ];
            }
        }
        
        return ['needs_approval' => false];
    }
}
