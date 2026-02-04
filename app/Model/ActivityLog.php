<?php

class ActivityLog
{
    /**
     * Log an activity in the system
     */
    public static function log($conn, $activity_type, $description, $user_id, $related_id = null, $metadata = null, $timestamp = null)
    {
        try {
            // Use provided timestamp or current time (ensure timezone is set)
            if (!defined('APP_TIMEZONE')) {
                define('APP_TIMEZONE', 'Africa/Harare');
            }
            if (function_exists('date_default_timezone_set')) {
                date_default_timezone_set(APP_TIMEZONE);
            }
            
            $created_at = $timestamp ?: date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO activity_logs (
                activity_type, 
                description, 
                user_id, 
                related_id, 
                metadata, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $metadata_json = $metadata ? json_encode($metadata) : null;
            
            return $stmt->execute([
                $activity_type,
                $description,
                $user_id,
                $related_id,
                $metadata_json,
                $created_at
            ]);
        } catch (PDOException $e) {
            error_log("Activity log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all activities (for Managing Director)
     */
    public static function get_all_activities($conn, $limit = 100, $offset = 0, $filters = [])
    {
        $sql = "SELECT al.*, 
                   e.first_name, 
                   e.last_name, 
                   e.role,
                   e.department
                FROM activity_logs al
                LEFT JOIN employee e ON al.user_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['activity_type'])) {
            $sql .= " AND al.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        
        if (!empty($filters['department'])) {
            $sql .= " AND e.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['exclude_activity_types']) && is_array($filters['exclude_activity_types'])) {
            $placeholders = implode(',', array_fill(0, count($filters['exclude_activity_types']), '?'));
            $sql .= " AND al.activity_type NOT IN ($placeholders)";
            $params = array_merge($params, $filters['exclude_activity_types']);
        }
        
        // LIMIT and OFFSET cannot be bound as parameters in MySQL, must be integers in SQL
        // Ensure they are positive integers
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        
        // Build SQL with LIMIT and OFFSET as direct integers (not placeholders)
        $sql .= " ORDER BY al.created_at DESC LIMIT " . $limit . " OFFSET " . $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get activity count for reports
     */
    public static function get_activity_count($conn, $filters = [])
    {
        $sql = "SELECT COUNT(*) 
                FROM activity_logs al
                LEFT JOIN employee e ON al.user_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['activity_type'])) {
            $sql .= " AND al.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        
        if (!empty($filters['department'])) {
            $sql .= " AND e.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Get activity statistics by department (normalized so "Sales" and "SALES AND MARKETING" count as one)
     */
    public static function get_activity_by_department($conn, $date_from = null, $date_to = null)
    {
        $dept_expr = "CASE WHEN TRIM(LOWER(COALESCE(e.department,''))) = 'sales' THEN 'SALES AND MARKETING' ELSE TRIM(COALESCE(e.department,'Unknown')) END";
        $sql = "SELECT 
                   $dept_expr AS department,
                   COUNT(*) as activity_count,
                   COUNT(DISTINCT al.user_id) as unique_users
                FROM activity_logs al
                LEFT JOIN employee e ON al.user_id = e.employee_id
                WHERE 1=1";
        
        $params = [];
        
        if ($date_from) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $sql .= " GROUP BY $dept_expr ORDER BY activity_count DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get activity statistics by type
     */
    public static function get_activity_by_type($conn, $date_from = null, $date_to = null)
    {
        $sql = "SELECT 
                   activity_type,
                   COUNT(*) as count
                FROM activity_logs
                WHERE 1=1";
        
        $params = [];
        
        if ($date_from) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $date_to;
        }
        
        $sql .= " GROUP BY activity_type ORDER BY count DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
