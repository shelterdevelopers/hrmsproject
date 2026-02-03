<?php

// This file is included from multiple places. Guard all functions to prevent
// "Cannot redeclare ..." fatals if it gets included more than once.

if (!function_exists('get_all_my_notifications')) {
function get_all_my_notifications($conn, $id, $exclude_attendance = false){
	$sql = "SELECT * FROM notifications WHERE recipient=?";
	if ($exclude_attendance) {
		$sql .= " AND type != 'attendance' AND type != 'team_attendance' AND type != 'activity'";
	}
	$sql .= " ORDER BY is_read ASC, date DESC, id DESC";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$id]);

	if($stmt->rowCount() > 0){
		$notifications = $stmt->fetchAll();
	}else $notifications = 0;

	return $notifications;
}
}

if (!function_exists('get_unread_notifications')) {
function get_unread_notifications($conn, $id, $exclude_attendance = false){
	$sql = "SELECT * FROM notifications WHERE recipient=? AND is_read=0";
	if ($exclude_attendance) {
		$sql .= " AND type != 'attendance' AND type != 'team_attendance' AND type != 'activity'";
	}
	$sql .= " ORDER BY date DESC, id DESC";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$id]);

	if($stmt->rowCount() > 0){
		$notifications = $stmt->fetchAll();
	}else $notifications = 0;

	return $notifications;
}
}

if (!function_exists('insert_quiz_result')) {
function insert_quiz_result($conn, $data){
    $sql = "INSERT INTO quiz_results (employee_id, score, total_questions, percentage) VALUES(?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}
}

if (!function_exists('get_quiz_result')) {
function get_quiz_result($conn, $employee_id){
    $sql = "SELECT * FROM quiz_results WHERE employee_id=? ORDER BY submitted_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$employee_id]);

    if($stmt->rowCount() > 0){
        $result = $stmt->fetch();
    } else $result = 0;

    return $result;
}
}

if (!function_exists('insert_feedback')) {
function insert_feedback($conn, $data){
    $sql = "INSERT INTO feedback (employee_id, feedback_text, rating) VALUES(?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}
}

if (!function_exists('count_notification')) {
function count_notification($conn, $id, $exclude_attendance = false){
$sql = "SELECT COUNT(id) AS count FROM notifications WHERE recipient=? AND is_read=0";
    if ($exclude_attendance) {
		$sql .= " AND type != 'attendance' AND type != 'team_attendance' AND type != 'activity'";
	}
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}
}

if (!function_exists('insert_notification')) {
function insert_notification($conn, $data){
	$sql = "INSERT INTO notifications (message, recipient, type) VALUES(?,?,?)";
	$stmt = $conn->prepare($sql);
	$stmt->execute($data);
}
}

/** Insert notification with optional poster (for company/department announcements). Run migrations/add_notifications_posted_by.sql first. */
if (!function_exists('insert_notification_with_poster')) {
function insert_notification_with_poster($conn, $message, $recipient_id, $type, $posted_by = null) {
	$sql = "INSERT INTO notifications (message, recipient, type, posted_by) VALUES (?, ?, ?, ?)";
	$stmt = $conn->prepare($sql);
	return $stmt->execute([$message, $recipient_id, $type, $posted_by]);
}
}

if (!function_exists('notification_make_read')) {
function notification_make_read($conn, $recipient_id, $notification_id){
	$sql = "UPDATE notifications SET is_read=1 WHERE id=? AND recipient=?";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$notification_id, $recipient_id]);
}
}

// Mark all notifications as read for a recipient (e.g. when they view the notifications page)
if (!function_exists('mark_all_notifications_read')) {
function mark_all_notifications_read($conn, $recipient_id, $exclude_attendance = false) {
	$sql = "UPDATE notifications SET is_read = 1 WHERE recipient = ?";
	if ($exclude_attendance) {
		$sql .= " AND type NOT IN ('attendance', 'team_attendance', 'activity')";
	}
	$stmt = $conn->prepare($sql);
	$stmt->execute([$recipient_id]);
	return $stmt->rowCount();
}
}

// Add this function to replace Notification::create
if (!function_exists('create_notification')) {
function create_notification($conn, $recipient_id, $message, $type) {
    $sql = "INSERT INTO notifications (message, recipient, type, date)
            VALUES (?, ?, ?, CURDATE())";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$message, $recipient_id, $type]);
}
}