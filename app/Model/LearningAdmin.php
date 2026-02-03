<?php

class LearningAdmin {
    public static function add_course($conn, $data) {
        $sql = "INSERT INTO learning_courses 
                (title, description, duration, category, link) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute($data);
    }

    public static function update_course($conn, $data) {
        $sql = "UPDATE learning_courses 
                SET title = ?, description = ?, duration = ?, 
                    category = ?, link = ?, is_active = ?
                WHERE course_id = ?";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * CORRECTION: Changed 'JOIN users u' to 'JOIN employee u'
     */
    public static function get_all_enrollments($conn, $filter = '') {
        $sql = "SELECT e.*, c.title, u.username, u.first_name, u.last_name 
                FROM learning_enrollments e
                JOIN learning_courses c ON e.course_id = c.course_id
                JOIN employee u ON e.employee_id = u.employee_id"; // <-- Fixed table name
        
        if (!empty($filter)) {
            $sql .= " WHERE (c.title LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $filter = "%$filter%";
        }
        $sql .= " ORDER BY e.enrolled_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($filter)) {
            $stmt->execute([$filter, $filter, $filter, $filter]);
        } else {
            $stmt->execute();
        }

        return $stmt->rowCount() > 0 ? $stmt->fetchAll() : 0;
    }

    public static function mark_completed($conn, $enrollment_id) {
        $sql = "UPDATE learning_enrollments 
                SET status = 'completed', completed_at = CURRENT_TIMESTAMP 
                WHERE enrollment_id = ?";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$enrollment_id]);
    }

    /**
     * CORRECTION: Changed 'JOIN users u' to 'JOIN employee u'
     */
    public static function get_course_feedback($conn, $course_id) {
        $sql = "SELECT f.*, u.username, u.first_name, u.last_name 
                FROM learning_feedback f
                JOIN employee u ON f.employee_id = u.employee_id"; // <-- Fixed table name
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);

        return $stmt->rowCount() > 0 ? $stmt->fetchAll() : 0;
    }

    public static function get_course_stats($conn, $course_id) {
        $stats = [];
        
        // Total enrollments
        $sql = "SELECT COUNT(*) as total FROM learning_enrollments WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);
        $stats['total_enrollments'] = $stmt->fetch()['total'];

        // Completed count
        $sql = "SELECT COUNT(*) as completed FROM learning_enrollments 
                WHERE course_id = ? AND completed_at IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);
        $stats['completed'] = $stmt->fetch()['completed'];

        // Average rating
        $sql = "SELECT AVG(rating) as avg_rating FROM learning_feedback WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);
        $stats['avg_rating'] = round($stmt->fetch()['avg_rating'], 1);

        return $stats;
    }
}