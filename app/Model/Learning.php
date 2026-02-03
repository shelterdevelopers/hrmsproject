<?php

class Learning {
    public static function get_all_courses($conn, $filter = '') {
        $sql = "SELECT * FROM learning_courses";
        if (!empty($filter)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR category LIKE ?)";
            $filter = "%$filter%";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($filter)) {
            $stmt->execute([$filter, $filter, $filter]);
        } else {
            $stmt->execute();
        }

        return $stmt->rowCount() > 0 ? $stmt->fetchAll() : 0;
    }

    public static function get_employee_enrollments($conn, $employee_id) {
        $sql = "SELECT e.*, c.title, c.description, c.duration 
                FROM learning_enrollments e
                JOIN learning_courses c ON e.course_id = c.course_id
                WHERE e.employee_id = ?
                ORDER BY e.enrolled_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);

        return $stmt->rowCount() > 0 ? $stmt->fetchAll() : 0;
    }

    public static function enroll_in_course($conn, $employee_id, $course_id) {
        // Check if already enrolled
        $sql = "SELECT * FROM learning_enrollments 
                WHERE employee_id = ? AND course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id, $course_id]);

        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Enroll
        $sql = "INSERT INTO learning_enrollments (employee_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$employee_id, $course_id]);
    }

    public static function submit_feedback($conn, $data) {
        $sql = "INSERT INTO learning_feedback 
                (employee_id, course_id, rating, feedback) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute($data);
    }

    public static function update_progress($conn, $enrollment_id, $progress) {
        // Map progress percentage to status
        $status = 'in_progress';
        if ($progress >= 100) {
            $status = 'completed';
        } elseif ($progress > 0) {
            $status = 'in_progress';
        } else {
            $status = 'enrolled';
        }
        
        $sql = "UPDATE learning_enrollments 
                SET status = ? 
                WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$status, $enrollment_id]);
    }

    public static function get_course_details($conn, $course_id) {
        $sql = "SELECT * FROM learning_courses WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);

        return $stmt->rowCount() > 0 ? $stmt->fetch() : 0;
    }

    
    public static function complete_course($conn, $enrollment_id, $employee_id) {
        // Update enrollment to completed status
        $sql = "UPDATE learning_enrollments 
                SET status = 'completed', completed_at = NOW() 
                WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$enrollment_id]);
    }

public static function verify_completion($conn, $enrollment_id) {
    // Mark as completed (verified)
    $sql = "UPDATE learning_enrollments 
            SET status = 'completed', completed_at = NOW() 
            WHERE enrollment_id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$enrollment_id]);
}

public static function get_completed_courses_count($conn, $employee_id) {
    $sql = "SELECT COUNT(*) FROM learning_enrollments 
            WHERE employee_id = ? AND status = 'completed' AND completed_at IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$employee_id]);
    return $stmt->fetchColumn();
}

// --- NEW METHODS FOR COURSE SUGGESTIONS ---

    /**
     * Submits a new course suggestion for HR Manager approval.
     * Suggestions go directly to HR Manager, not to employee's manager.
     */
    public static function suggest_course($conn, $employee_id, $title, $description, $duration, $category, $link = null) {
        // Get HR Manager ID
        require_once "RoleHelper.php";
        $hr_manager_id = RoleHelper::get_hr_id($conn);

        if (!$hr_manager_id) {
            return ['success' => false, 'message' => 'HR Manager not found in the system. Cannot submit suggestion.'];
        }

        $sql = "INSERT INTO learning_course_suggestions
                    (employee_id, manager_id, title, description, duration, category, link, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_manager')";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([
            $employee_id, $hr_manager_id, $title, $description, $duration, $category, $link
        ]);

        if ($success) {
            // Notify HR Manager (optional, requires Notification model integration)
            // require_once "Notification.php";
            // $emp_name = $_SESSION['first_name'] ?? 'Employee'; // Assuming name is in session
            // $message = "$emp_name suggested a new course for approval: '$title'";
            // create_notification($conn, $hr_manager_id, $message, 'course_suggestion', $conn->lastInsertId());
            return ['success' => true];
        } else {
            error_log("Suggest course failed: " . print_r($stmt->errorInfo(), true));
            return ['success' => false, 'message' => 'Database error during suggestion submission.'];
        }
    }

    /**
     * Gets all course suggestions submitted by a specific employee.
     */
    public static function get_my_suggestions($conn, $employee_id) {
        // Check if executive_id column exists
        $check_sql = "SELECT COUNT(*) as col_count 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'learning_course_suggestions' 
                      AND COLUMN_NAME = 'executive_id'";
        $check_stmt = $conn->query($check_sql);
        $has_executive_id = $check_stmt->fetch()['col_count'] > 0;
        
        // Check if manager_id column exists
        $check_sql2 = "SELECT COUNT(*) as col_count 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'learning_course_suggestions' 
                       AND COLUMN_NAME = 'manager_id'";
        $check_stmt2 = $conn->query($check_sql2);
        $has_manager_id = $check_stmt2->fetch()['col_count'] > 0;
        
        // Check if submitted_at column exists
        $check_sql3 = "SELECT COUNT(*) as col_count 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'learning_course_suggestions' 
                       AND COLUMN_NAME = 'submitted_at'";
        $check_stmt3 = $conn->query($check_sql3);
        $has_submitted_at = $check_stmt3->fetch()['col_count'] > 0;
        
        $order_by = $has_submitted_at ? "COALESCE(sugg.submitted_at, sugg.created_at)" : "sugg.created_at";
        
        if ($has_executive_id && $has_manager_id) {
            $sql = "SELECT sugg.*, mgr.first_name as manager_fname, mgr.last_name as manager_lname,
                           exec.first_name as exec_fname, exec.last_name as exec_lname
                    FROM learning_course_suggestions sugg
                    LEFT JOIN employee mgr ON sugg.manager_id = mgr.employee_id
                    LEFT JOIN employee exec ON sugg.executive_id = exec.employee_id
                    WHERE sugg.employee_id = ?
                    ORDER BY {$order_by} DESC";
        } else {
            // Fallback query without executive_id/manager_id joins
            $sql = "SELECT sugg.*, NULL as manager_fname, NULL as manager_lname,
                           NULL as exec_fname, NULL as exec_lname
                    FROM learning_course_suggestions sugg
                    WHERE sugg.employee_id = ?
                    ORDER BY {$order_by} DESC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }

    /**
     * Gets all course suggestions for this manager (pending their action OR forwarded).
     * FIX: Removed duplicate 'AND'.
     * NEW: Joins to get the name of the executive it was forwarded to.
     */
    public static function get_pending_manager_approvals($conn, $manager_id) {
        // Check if executive_id and manager_id columns exist
        $check_sql = "SELECT COUNT(*) as col_count 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'learning_course_suggestions' 
                      AND COLUMN_NAME IN ('executive_id', 'manager_id')";
        $check_stmt = $conn->query($check_sql);
        $has_columns = $check_stmt->fetch()['col_count'] >= 2;
        
        // Check if submitted_at column exists
        $check_sql3 = "SELECT COUNT(*) as col_count 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'learning_course_suggestions' 
                       AND COLUMN_NAME = 'submitted_at'";
        $check_stmt3 = $conn->query($check_sql3);
        $has_submitted_at = $check_stmt3->fetch()['col_count'] > 0;
        
        $order_by = $has_submitted_at ? "COALESCE(sugg.submitted_at, sugg.created_at)" : "sugg.created_at";
        
        if ($has_columns) {
            $sql = "SELECT sugg.*, 
                           emp.first_name as emp_fname, emp.last_name as emp_lname,
                           exec.first_name as exec_fname, exec.last_name as exec_lname
                    FROM learning_course_suggestions sugg
                    JOIN employee emp ON sugg.employee_id = emp.employee_id
                    LEFT JOIN employee exec ON sugg.executive_id = exec.employee_id
                    WHERE sugg.manager_id = ? 
                      AND sugg.status IN ('pending_manager', 'pending_executive')
                    ORDER BY {$order_by} ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$manager_id]);
        } else {
            // Fallback query without executive_id/manager_id joins
            // Note: Can't filter by manager_id if column doesn't exist, so return all pending
            $sql = "SELECT sugg.*, 
                           emp.first_name as emp_fname, emp.last_name as emp_lname,
                           NULL as exec_fname, NULL as exec_lname
                    FROM learning_course_suggestions sugg
                    JOIN employee emp ON sugg.employee_id = emp.employee_id
                    WHERE sugg.status IN ('pending', 'pending_manager', 'pending_executive')
                    ORDER BY {$order_by} ASC";
            $stmt = $conn->prepare($sql);
            $stmt->execute(); // No parameters needed for fallback
        }
        return $stmt->fetchAll();
    }
     /**
     * Gets pending course suggestions needing approval from ANY executive.
     */
    /**
     * Gets ALL pending executive approvals (for Admin/Executive view).
     * NEW: Joins to get assigned executive's name.
     */
   /**
     * Gets ALL pending executive approvals (for Admin/Executive view).
     * NEW: Joins to get assigned executive's name.
     */
    public static function get_pending_executive_approvals($conn) {
        // Check if executive_id and manager_id columns exist
        $check_sql = "SELECT COUNT(*) as col_count 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'learning_course_suggestions' 
                      AND COLUMN_NAME IN ('executive_id', 'manager_id')";
        $check_stmt = $conn->query($check_sql);
        $has_columns = $check_stmt->fetch()['col_count'] >= 2;
        
        // Check if submitted_at column exists
        $check_sql3 = "SELECT COUNT(*) as col_count 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'learning_course_suggestions' 
                       AND COLUMN_NAME = 'submitted_at'";
        $check_stmt3 = $conn->query($check_sql3);
        $has_submitted_at = $check_stmt3->fetch()['col_count'] > 0;
        
        $order_by = $has_submitted_at ? "COALESCE(sugg.submitted_at, sugg.created_at)" : "sugg.created_at";
        
        if ($has_columns) {
            $sql = "SELECT sugg.*, 
                           emp.first_name as emp_fname, emp.last_name as emp_lname,
                           mgr.first_name as mgr_fname, mgr.last_name as mgr_lname,
                           exec.first_name as exec_fname, exec.last_name as exec_lname
                    FROM learning_course_suggestions sugg
                    JOIN employee emp ON sugg.employee_id = emp.employee_id
                    LEFT JOIN employee mgr ON sugg.manager_id = mgr.employee_id
                    LEFT JOIN employee exec ON sugg.executive_id = exec.employee_id
                    WHERE sugg.status = 'pending_executive'
                    ORDER BY {$order_by} ASC";
        } else {
            // Fallback query without executive_id/manager_id joins
            $sql = "SELECT sugg.*, 
                           emp.first_name as emp_fname, emp.last_name as emp_lname,
                           NULL as mgr_fname, NULL as mgr_lname,
                           NULL as exec_fname, NULL as exec_lname
                    FROM learning_course_suggestions sugg
                    JOIN employee emp ON sugg.employee_id = emp.employee_id
                    WHERE sugg.status IN ('pending', 'pending_executive')
                    ORDER BY {$order_by} ASC";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates the status of a course suggestion (manager or executive action).
     * Returns true on success, false on failure.
     */
    /**
     * Helper function to get all active executive members.
     */
    public static function get_all_executives($conn) {
        $sql = "SELECT employee_id, first_name, last_name
                FROM employee
                WHERE executive_member = 1 AND status = 'Active'
                ORDER BY first_name ASC, last_name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    /**
     * Updates the status of a course suggestion (manager or executive action).
     * Now requires $executive_id when forwarding.
     * Returns true on success, false on failure.
     */
    /**
     * Updates the status of a course suggestion.
     * NEW: Handles 'forward_to_another_executive' action.
     */
    /**
     * Updates the status of a course suggestion.
     * FIX: Uses fetched $acting_user data instead of $_SESSION.
     * NEW: Handles 'forward_to_another_executive' action.
     */
    public static function update_suggestion_status(
        $conn,
        $suggestion_id,
        $new_status,        // 'pending_executive', 'approved', 'denied'
        $action,            // The button pressed: 'forward_to_executive', 'approve', 'deny', 'forward_to_another_executive'
        $acting_user_id,    // The ID of the person clicking the button
        $comment = null,
        $forward_to_executive_id = null // ID of the *next* executive
    ) {
         // Get suggestion details
         $sql_get = "SELECT * FROM learning_course_suggestions WHERE suggestion_id = ?";
         $stmt_get = $conn->prepare($sql_get);
         $stmt_get->execute([$suggestion_id]);
         $suggestion = $stmt_get->fetch();
         if (!$suggestion) return false;

         $current_status = $suggestion['status'];
         $employee_id = $suggestion['employee_id']; // The original suggester

         // FIX: Fetch first_name and last_name here for the comment
         $sql_user = "SELECT role, executive_member, first_name, last_name FROM employee WHERE employee_id = ?";
         $stmt_user = $conn->prepare($sql_user);
         $stmt_user->execute([$acting_user_id]);
         $acting_user = $stmt_user->fetch();
         if (!$acting_user) return false; // Safety check

         $is_manager = ($acting_user_id == $suggestion['manager_id']);
         $is_executive = ($acting_user && $acting_user['executive_member'] == 1);

         $sql_update = "";
         $params = [];
         // ... (notification flags) ...

         // === Manager Action: Approving & Forwarding ===
         if ($is_manager && $current_status == 'pending_manager' && $action == 'forward_to_executive') {
             if (empty($forward_to_executive_id)) return false; 
             $sql_update = "UPDATE learning_course_suggestions SET status = 'pending_executive', executive_id = ?, manager_comment = ? WHERE suggestion_id = ?";
             $params = [$forward_to_executive_id, $comment, $suggestion_id];
             // $notify_selected_executive = true;
         }
         // === Manager Action: Denying ===
         elseif ($is_manager && $current_status == 'pending_manager' && $action == 'deny') {
             $sql_update = "UPDATE learning_course_suggestions SET status = 'denied', manager_comment = ? WHERE suggestion_id = ?";
             $params = [$comment, $suggestion_id];
             // $notify_employee = true;
         }
         // === Executive Actions ===
         // User must be an executive AND be the one assigned
         elseif ($is_executive && $current_status == 'pending_executive' && $acting_user_id == $suggestion['executive_id']) {
             
             if ($action == 'approve') { // Final approval
                 $sql_update = "UPDATE learning_course_suggestions SET status = 'approved', executive_comment = ? WHERE suggestion_id = ?";
                 $params = [$comment, $suggestion_id];
                 self::add_approved_suggestion_to_courses($conn, $suggestion);
                 // $notify_employee = true; $notify_manager = true;

             } elseif ($action == 'deny') { // Final denial
                 $sql_update = "UPDATE learning_course_suggestions SET status = 'denied', executive_comment = ? WHERE suggestion_id = ?";
                 $params = [$comment, $suggestion_id];
                 // $notify_employee = true; $notify_manager = true;

             } elseif ($action == 'forward_to_another_executive') { // Forward to a different exec
                 if (empty($forward_to_executive_id)) return false;
                 
                 // FIX: Use the fetched $acting_user array
                 $comment_with_forward = "Forwarded by " . $acting_user['first_name'] . " " . $acting_user['last_name'] . ". \n\n" . $comment;
                 
                 $sql_update = "UPDATE learning_course_suggestions SET status = 'pending_executive', executive_id = ?, executive_comment = ? WHERE suggestion_id = ?";
                 $params = [$forward_to_executive_id, $comment_with_forward, $suggestion_id];
                 // $notify_selected_executive = true; $notify_manager = true;
             }
         }

         if (!empty($sql_update)) {
             $stmt_update = $conn->prepare($sql_update);
             $success = $stmt_update->execute($params);
             if ($success) { /* ... (Notification logic) ... */ return true; }
             error_log("Update suggestion status failed: " . print_r($stmt_update->errorInfo(), true));
         }
         return false; // No valid action or permission denied
    }

    /**
     * Helper to add an approved suggestion to the main learning_courses table.
     * FIX: Added correct column names for the learning_courses table.
     */
    private static function add_approved_suggestion_to_courses($conn, $suggestion_data) {
         // Ensure your learning_courses table has these columns
         $sql = "INSERT INTO learning_courses
                     (title, description, duration, category, link, is_active, suggested_by_employee_id, suggestion_id_source)
                 VALUES (?, ?, ?, ?, ?, 1, ?, ?)";
         $stmt = $conn->prepare($sql);
         return $stmt->execute([
             $suggestion_data['title'],
             $suggestion_data['description'],
             $suggestion_data['duration'],
             $suggestion_data['category'],
             $suggestion_data['link'],
             $suggestion_data['employee_id'],
             $suggestion_data['suggestion_id']
         ]);
    }
    
    

} // End Class Learning
?>