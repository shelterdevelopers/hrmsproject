<?php
class Appraisal
{
    // Fetch complete form details plus previous assessment date
    public static function get_appraisal_form_details($conn, $form_id)
    {
        $sql_with_self = "SELECT f.*,
           e.first_name AS employee_first_name, 
           e.last_name AS employee_last_name,
           e.job_title,
           e.department,
           e.role AS employee_role,
           e.executive_member,
           m.first_name AS manager_first_name,
           m.last_name AS manager_last_name,
           a.metrics,
           a.is_acknowledged,
           a.requires_reacknowledgement,
           a.status AS appraisal_status,
           a.id AS appraisal_id,
           a.employee_comments,
           a.self_metrics,
           a.self_goals,
           a.self_strengths,
           a.self_weaknesses,
           a.self_achievements,
           a.self_training,
           a.manager_strengths,
           a.manager_improvement,
           a.manager_training
        FROM appraisal_forms f
        JOIN employee e ON f.employee_id = e.employee_id
        LEFT JOIN employee m ON f.manager_id = m.employee_id
        LEFT JOIN appraisals a ON f.form_id = a.id
        WHERE f.form_id = ?";
        $sql_without_self = "SELECT f.*,
           e.first_name AS employee_first_name, 
           e.last_name AS employee_last_name,
           e.job_title,
           e.department,
           e.role AS employee_role,
           e.executive_member,
           m.first_name AS manager_first_name,
           m.last_name AS manager_last_name,
           a.metrics,
           a.is_acknowledged,
           a.requires_reacknowledgement,
           a.status AS appraisal_status,
           a.id AS appraisal_id,
           a.employee_comments,
           a.manager_strengths,
           a.manager_improvement,
           a.manager_training
        FROM appraisal_forms f
        JOIN employee e ON f.employee_id = e.employee_id
        LEFT JOIN employee m ON f.manager_id = m.employee_id
        LEFT JOIN appraisals a ON f.form_id = a.id
        WHERE f.form_id = ?";

        try {
            $stmt = $conn->prepare($sql_with_self);
            $stmt->execute([$form_id]);
            $result = $stmt->fetch();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'self_') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $conn->prepare($sql_without_self);
                $stmt->execute([$form_id]);
                $result = $stmt->fetch();
                if ($result) {
                    $result['self_metrics'] = null;
                    $result['self_goals'] = null;
                    $result['self_strengths'] = null;
                    $result['self_weaknesses'] = null;
                    $result['self_achievements'] = null;
                    $result['self_training'] = null;
                }
            } else {
                throw $e;
            }
        }

        // 2. If no result, return null/false as before
        if (!$result) {
            return null;
        }

        // 3. Ensure metrics field always has the new structure
        if (!isset($result['metrics']) || empty($result['metrics'])) {
            $result['metrics'] = json_encode(Appraisal::get_metrics());
        }

        // 4. Get previous assessment date for this employee
        // Only if current period_start exists (should always be set)
        $result['previous_assessment_date'] = null;
        if (!empty($result['employee_id']) && !empty($result['period_start'])) {
            $sql_prev = "SELECT MAX(f.period_end) AS previous_date
            FROM appraisal_forms f
            INNER JOIN appraisals a ON f.form_id = a.id
            WHERE f.employee_id = ?
              AND a.status = 'completed'
              AND f.period_end < ?
              AND f.form_id <> ?";
            $stmt_prev = $conn->prepare($sql_prev);
            $stmt_prev->execute([
                $result['employee_id'],
                $result['period_start'],
                $form_id
            ]);
            $row_prev = $stmt_prev->fetch();
            if ($row_prev && $row_prev['previous_date']) {
                $result['previous_assessment_date'] = $row_prev['previous_date'];
            }
        }

        return $result;
    }

    // The new metrics structure
    public static function get_metrics()
    {
        return [
            'PLANNING & ORGANISING' => [
                'description' => 'Ability to meet deadlines, monitor tasks and activities, set goals and priorities',
                'max_score' => 50,
                'rating' => 0,
                'comments' => ''
            ],
            'OUTPUT' => [
                'description' => 'Volume of work relative to employee\'s experience. Ability to distribute effort over various tasks.',
                'max_score' => 10,
                'rating' => 0,
                'comments' => ''
            ],
            'CUSTOMER SERVICE' => [
                'description' => 'Responsiveness to client problems and needs, effectiveness in conveying information to both internal & external customers',
                'max_score' => 10,
                'rating' => 0,
                'comments' => ''
            ],
            'INITIATIVE/INNOVATIVENESS' => [
                'description' => 'Development of new ideas/voluntarily submits constructive ideas to improve efficiency',
                'max_score' => 10,
                'rating' => 0,
                'comments' => ''
            ],
            'DEPENDABILITY/EFFORT' => [
                'description' => 'Compare performance related to the amount of supervision required. Reliability and interest in the job.',
                'max_score' => 10,
                'rating' => 0,
                'comments' => ''
            ],
            'STRESS TOLERANCE' => [
                'description' => 'How well the employee copes/works under pressure',
                'max_score' => 5,
                'rating' => 0,
                'comments' => ''
            ],
            'CO-OPERATION' => [
                'description' => 'Level of co-operation with Supervisor and colleagues',
                'max_score' => 5,
                'rating' => 0,
                'comments' => ''
            ]
        ];
    }

    /** Self-assessment areas (same structure as Self Assessment print form: 5 areas, total weight 100) */
    public static function get_self_assessment_areas()
    {
        return [
            'JOB EFFECTIVENESS' => ['desc' => 'Achievement of results', 'weight' => 70],
            'LEADERSHIP/TEAM EFFECTIVENESS' => ['desc' => 'Providing direction and effective management of subordinates and working as a team member', 'weight' => 10],
            'CUSTOMER SERVICE' => ['desc' => 'Responsiveness to client problems and needs', 'weight' => 10],
            'INITIATIVE/INNOVATIVENESS' => ['desc' => 'Development of new ideas', 'weight' => 5],
            'EFFECTIVE TIME MANAGEMENT' => ['desc' => 'Punctuality, prioritization, management of meetings', 'weight' => 5],
        ];
    }

    /** Save employee self-assessment (only the appraisee can save) */
    public static function save_self_assessment($conn, $appraisal_id, $employee_id, $self_metrics, $self_goals, $self_strengths, $self_weaknesses, $self_achievements, $self_training)
    {
        try {
            $sql = "UPDATE appraisals 
                SET self_metrics = ?,
                    self_goals = ?,
                    self_strengths = ?,
                    self_weaknesses = ?,
                    self_achievements = ?,
                    self_training = ?,
                    updated_at = NOW()
                WHERE id = ? AND employee_id = ?";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                is_array($self_metrics) ? json_encode($self_metrics) : $self_metrics,
                $self_goals,
                $self_strengths,
                $self_weaknesses,
                $self_achievements,
                $self_training,
                $appraisal_id,
                $employee_id
            ]);
        } catch (PDOException $e) {
            error_log("Database error in save_self_assessment: " . $e->getMessage());
            return false;
        }
    }

    // Get most recent completed appraisal period_end BEFORE the current one for this employee
    public static function get_previous_appraisal_date($conn, $employee_id, $current_period_start = null, $exclude_form_id = null)
    {
        $sql = "SELECT MAX(f.period_end) as previous_date
                FROM appraisal_forms f
                INNER JOIN appraisals a ON f.form_id = a.id
                WHERE f.employee_id = ?
                  AND a.status = 'completed'";

        $params = [$employee_id];

        if ($current_period_start) {
            $sql .= " AND f.period_end < ?";
            $params[] = $current_period_start;
        }
        if ($exclude_form_id) {
            $sql .= " AND f.form_id <> ?";
            $params[] = $exclude_form_id;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row && $row['previous_date'] ? $row['previous_date'] : null;
    }

    // Update appraisal metrics
    public static function update_appraisal($conn, $appraisal_id, $metrics, $manager_id, $manager_strengths, $manager_improvement, $manager_training)
    {
        try {
            $sql = "UPDATE appraisals 
                SET metrics = ?,
                    manager_strengths = ?,
                    manager_improvement = ?,
                    manager_training = ?,
                    requires_reacknowledgement = 1,
                    last_updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                json_encode($metrics),
                $manager_strengths,
                $manager_improvement,
                $manager_training,
                $manager_id,
                $appraisal_id
            ]);
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }



    public static function final_submit($conn, $appraisal_id)
    {
        try {
            $sql = "UPDATE appraisals 
                    SET status = 'completed',
                        completed_at = NOW()
                    WHERE id = ? AND is_acknowledged = 1";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([$appraisal_id]);
        } catch (PDOException $e) {
            error_log("Database error in final_submit: " . $e->getMessage());
            return false;
        }
    }

    public static function get_active_appraisal_forms($conn, $employee_id, $role, $department = null)
    {
        if ($role == 'Admin') {
            $sql = "SELECT f.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name,
               a.status AS appraisal_status,
               a.is_acknowledged,
               a.requires_reacknowledgement
            FROM appraisal_forms f
            JOIN employee e ON f.employee_id = e.employee_id
            LEFT JOIN employee m ON f.manager_id = m.employee_id
            JOIN appraisals a ON f.form_id = a.id
            WHERE a.status IN ('draft', 'shared', 'employee_review')
            ORDER BY a.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        } elseif ($department) {
            // Finance Manager - get appraisals for department employees
            $sql = "SELECT f.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name,
               a.status AS appraisal_status,
               a.is_acknowledged,
               a.requires_reacknowledgement
            FROM appraisal_forms f
            JOIN employee e ON f.employee_id = e.employee_id
            LEFT JOIN employee m ON f.manager_id = m.employee_id
            JOIN appraisals a ON f.form_id = a.id
            WHERE e.department = ? AND a.status IN ('draft', 'shared', 'employee_review')
            ORDER BY a.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$department]);
        } else {
            $sql = "SELECT f.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name,
               a.status AS appraisal_status,
               a.is_acknowledged,
               a.requires_reacknowledgement
            FROM appraisal_forms f
            JOIN employee e ON f.employee_id = e.employee_id
            LEFT JOIN employee m ON f.manager_id = m.employee_id
            JOIN appraisals a ON f.form_id = a.id
            WHERE (f.employee_id = ? OR f.manager_id = ?)
            AND a.status IN ('draft', 'shared', 'employee_review')
            ORDER BY a.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$employee_id, $employee_id]);
        }
        return $stmt->fetchAll();
    }

    public static function get_completed_appraisal_forms($conn, $employee_id, $role, $department = null)
    {
        if ($role == 'Admin') {
            $sql = "SELECT f.*, 
                   e.first_name AS employee_first_name, 
                   e.last_name AS employee_last_name,
                   m.first_name AS manager_first_name,
                   m.last_name AS manager_last_name
                FROM appraisal_forms f
                JOIN employee e ON f.employee_id = e.employee_id
                LEFT JOIN employee m ON f.manager_id = m.employee_id
                WHERE f.status = 'completed'
                ORDER BY f.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        } elseif ($department) {
            // Finance Manager - get completed appraisals for department employees
            $sql = "SELECT f.*, 
                   e.first_name AS employee_first_name, 
                   e.last_name AS employee_last_name,
                   m.first_name AS manager_first_name,
                   m.last_name AS manager_last_name
                FROM appraisal_forms f
                JOIN employee e ON f.employee_id = e.employee_id
                LEFT JOIN employee m ON f.manager_id = m.employee_id
                WHERE e.department = ? AND f.status = 'completed'
                ORDER BY f.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$department]);
        } else {
            $sql = "SELECT f.*, 
                   e.first_name AS employee_first_name, 
                   e.last_name AS employee_last_name,
                   m.first_name AS manager_first_name,
                   m.last_name AS manager_last_name
                FROM appraisal_forms f
                JOIN employee e ON f.employee_id = e.employee_id
                LEFT JOIN employee m ON f.manager_id = m.employee_id
                WHERE (f.employee_id = ? OR f.manager_id = ?)
                AND f.status = 'completed'
                ORDER BY f.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$employee_id, $employee_id]);
        }
        return $stmt->fetchAll();
    }



    public static function is_manager($conn, $employee_id)
    {
        $sql = "SELECT COUNT(*) FROM employee WHERE manager_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchColumn() > 0;
    }



    public static function get_pending_appraisals($conn, $manager_id, $department = null)
    {
        // If department is provided (for Finance Manager), get appraisals for department employees
        if ($department) {
            $sql = "SELECT a.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name
            FROM appraisals a
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee m ON a.manager_id = m.employee_id
            WHERE e.department = ? AND a.status = 'pending'
            ORDER BY a.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$department]);
        } else {
            // Regular manager - get appraisals for direct reports
            $sql = "SELECT a.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name
            FROM appraisals a
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee m ON a.manager_id = m.employee_id
            WHERE a.manager_id = ? AND a.status = 'pending'
            ORDER BY a.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$manager_id]);
        }
        return $stmt->fetchAll();
    }
    /**
     * Get pending appraisals for all managers (for Managing Director to review)
     */
    public static function get_pending_appraisals_for_managers($conn)
    {
        // Get all appraisals where employee is a manager (role = 'manager' or executive_member = 1)
        $sql = "SELECT f.*, 
                   e.first_name AS employee_first_name, 
                   e.last_name AS employee_last_name,
                   e.role as employee_role,
                   e.department,
                   a.status AS appraisal_status,
                   a.is_acknowledged
                FROM appraisal_forms f
                JOIN employee e ON f.employee_id = e.employee_id
                JOIN appraisals a ON f.form_id = a.id
                WHERE (e.role = 'manager' OR e.executive_member = 1)
                AND a.status IN ('draft', 'shared', 'employee_review')
                ORDER BY a.updated_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function get_pending_appraisals_admin($conn)
    {
        $sql = "SELECT a.*, 
               e.first_name AS employee_first_name, 
               e.last_name AS employee_last_name,
               m.first_name AS manager_first_name,
               m.last_name AS manager_last_name
            FROM appraisals a
            JOIN employee e ON a.employee_id = e.employee_id
            LEFT JOIN employee m ON a.manager_id = m.employee_id
            WHERE a.status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function get_completed_appraisals($conn, $manager_id, $department = null)
    {
        // If department is provided (for Finance Manager), get appraisals for department employees
        if ($department) {
            $sql = "SELECT a.*, e.first_name, e.last_name 
                    FROM appraisals a
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE e.department = ? AND a.status = 'completed'
                    ORDER BY a.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$department]);
        } else {
            // Regular manager - get appraisals for direct reports
            $sql = "SELECT a.*, e.first_name, e.last_name 
                    FROM appraisals a
                    JOIN employee e ON a.employee_id = e.employee_id
                    WHERE a.manager_id = ? AND a.status = 'completed'
                    ORDER BY a.updated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$manager_id]);
        }
        return $stmt->fetchAll();
    }

    public static function get_employee_appraisals($conn, $employee_id)
    {
        $sql = "SELECT a.*, 
                   e.first_name AS manager_first_name, 
                   e.last_name AS manager_last_name
                FROM appraisals a
                LEFT JOIN employee e ON a.manager_id = e.employee_id
                WHERE a.employee_id = ? AND a.status = 'shared'
                ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll();
    }



    public static function get_completed_appraisals_admin($conn)
    {
        $sql = "SELECT a.*, 
           e.first_name AS employee_first_name, 
           e.last_name AS employee_last_name,
           m.first_name AS manager_first_name,
           m.last_name AS manager_last_name
        FROM appraisals a
        JOIN employee e ON a.employee_id = e.employee_id
        LEFT JOIN employee m ON a.manager_id = m.employee_id
        WHERE a.status = 'completed'
        ORDER BY a.updated_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all completed appraisal forms for HR (file-keeping). HR can view/print any completed appraisal.
     * Includes employee_role and executive_member so HR list can show correct print forms (Non-Managerial vs Management).
     */
    public static function get_all_completed_for_hr($conn)
    {
        $sql = "SELECT f.*, 
                   a.completed_at,
                   e.first_name AS employee_first_name, 
                   e.last_name AS employee_last_name,
                   e.job_title,
                   e.department,
                   e.role AS employee_role,
                   e.executive_member,
                   m.first_name AS manager_first_name,
                   m.last_name AS manager_last_name
                FROM appraisal_forms f
                JOIN appraisals a ON f.form_id = a.id
                JOIN employee e ON f.employee_id = e.employee_id
                LEFT JOIN employee m ON f.manager_id = m.employee_id
                WHERE a.status = 'completed'
                ORDER BY a.completed_at DESC, f.updated_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }



    //  public static function update_appraisal($conn, $appraisal_id, $metrics, $manager_id) {
    //     try {
    //         $conn->beginTransaction();

    //         // Update appraisals table
    //         $sql = "UPDATE appraisals 
    //                 SET metrics = ?,
    //                     requires_reacknowledgement = 1,
    //                     last_updated_by = ?,
    //                     updated_at = NOW()
    //                 WHERE id = ?";
    //         $stmt = $conn->prepare($sql);
    //         $stmt->execute([json_encode($metrics), $manager_id, $appraisal_id]);

    //         // Update appraisal_forms table
    //         $sql = "UPDATE appraisal_forms 
    //                 SET updated_at = NOW()
    //                 WHERE form_id = ?";
    //         $stmt = $conn->prepare($sql);
    //         $stmt->execute([$appraisal_id]);

    //         $conn->commit();
    //         return true;
    //     } catch (PDOException $e) {
    //         $conn->rollBack();
    //         error_log("Database error in update_appraisal: " . $e->getMessage());
    //         return false;
    //     }
    // }

    public static function acknowledge_appraisal($conn, $appraisal_id, $employee_comments)
    {
        try {
            $conn->beginTransaction();

            // Update appraisals table
            $sql = "UPDATE appraisals 
                    SET employee_comments = ?,
                        acknowledged_at = NOW(),
                        is_acknowledged = 1,
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$employee_comments, $appraisal_id]);

            // Update appraisal_forms table
            $sql = "UPDATE appraisal_forms 
                    SET updated_at = NOW()
                    WHERE form_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id]);

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in acknowledge_appraisal: " . $e->getMessage());
            return false;
        }
    }
    public static function submit_feedback($conn, $form_id, $employee_id, $comments)
    {
        try {
            // First check if feedback already exists
            $existing = $conn->query("SELECT * FROM appraisal_feedback 
                                WHERE form_id = $form_id")->fetch();

            if ($existing) {
                // Update existing feedback
                $sql = "UPDATE appraisal_feedback 
                   SET employee_comments = ?,
                       submitted_at = NOW(),
                       acknowledged = 0,
                       acknowledged_at = NULL
                   WHERE form_id = ?";
            } else {
                // Create new feedback
                $sql = "INSERT INTO appraisal_feedback (
                   form_id, employee_comments, submitted_at
                   ) VALUES (?, ?, NOW())";
            }

            $stmt = $conn->prepare($sql);
            $params = $existing ? [$comments, $form_id] : [$form_id, $comments];
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in submit_feedback: " . $e->getMessage());
            return false;
        }
    }

    public static function record_acknowledgement($conn, $appraisal_id, $employee_id, $comments)
    {
        try {
            $conn->beginTransaction();

            // 1. Record in acknowledgements table
            $sql = "INSERT INTO appraisal_acknowledgements (
               appraisal_id, acknowledged_at, acknowledged_by, comments
               ) VALUES (?, NOW(), ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id, $employee_id, $comments]);

            // 2. Update appraisal status
            $sql = "UPDATE appraisals 
               SET is_acknowledged = 1,
                   requires_reacknowledgement = 0,
                   acknowledged_at = NOW(),
                   employee_comments = ?,
                   updated_at = NOW()
               WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$comments, $appraisal_id]);

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in record_acknowledgement: " . $e->getMessage());
            return false;
        }
    }


    public static function share_appraisal($conn, $appraisal_id)
    {
        try {
            $sql = "UPDATE appraisals 
                    SET status = 'shared', 
                        shared_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([$appraisal_id]);
        } catch (PDOException $e) {
            error_log("Database error in share_appraisal: " . $e->getMessage());
            return false;
        }
    }

    public static function save_appraisal($conn, $appraisal_id)
    {
        try {
            $sql = "UPDATE appraisals 
                    SET status = 'completed', 
                        completed_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([$appraisal_id]);
        } catch (PDOException $e) {
            error_log("Database error in save_appraisal: " . $e->getMessage());
            return false;
        }
    }

    public static function share_with_employee($conn, $appraisal_id)
    {
        try {
            $conn->beginTransaction();

            // Update appraisals table
            $sql = "UPDATE appraisals 
                    SET status = 'shared',
                        shared_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id]);

            // Update appraisal_forms table
            $sql = "UPDATE appraisal_forms 
                    SET status = 'shared',
                        updated_at = NOW()
                    WHERE form_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id]);

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in share_with_employee: " . $e->getMessage());
            return false;
        }
    }

    public static function finalize_appraisal($conn, $appraisal_id)
    {
        try {
            $conn->beginTransaction();

            // Update appraisals table
            $sql = "UPDATE appraisals 
                    SET status = 'completed',
                        completed_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND is_acknowledged = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id]);

            // Update appraisal_forms table
            $sql = "UPDATE appraisal_forms 
                    SET status = 'completed',
                        updated_at = NOW()
                    WHERE form_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$appraisal_id]);

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in finalize_appraisal: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an appraisal and its form. Caller must check permissions.
     * Deletes appraisals row (appraisal_acknowledgements cascade) then appraisal_forms.
     */
    public static function delete_appraisal($conn, $form_id)
    {
        $form_id = (int) $form_id;
        if ($form_id <= 0) return false;
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("DELETE FROM appraisals WHERE id = ?");
            $stmt->execute([$form_id]);
            $stmt = $conn->prepare("DELETE FROM appraisal_forms WHERE form_id = ?");
            $stmt->execute([$form_id]);
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error in delete_appraisal: " . $e->getMessage());
            return false;
        }
    }
}
