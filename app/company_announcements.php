<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Notification.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

/**
 * Normalize company announcement message so we can dedupe: strip any
 * "Company Announcement - Department" prefix (from old or alternate code paths).
 */
function normalize_company_announcement_message($message) {
    $msg = trim((string) $message);
    if (preg_match('/^Company\s+Announcement\s*-\s*[^\n]+\s*\n?/i', $msg, $m)) {
        $msg = trim(substr($msg, strlen($m[0])));
    }
    return $msg === '' ? $message : $msg;
}

// Check if posted_by column exists (migrations/add_notifications_posted_by.sql).
$has_posted_by = false;
try {
    $chk = $conn->query("SHOW COLUMNS FROM notifications LIKE 'posted_by'");
    $has_posted_by = $chk && $chk->rowCount() > 0;
} catch (Throwable $e) {}

if ($has_posted_by) {
    $sql = "SELECT n.message, n.date, n.created_at, n.id, n.posted_by,
                   e.first_name AS poster_first_name, e.last_name AS poster_last_name, e.role AS poster_role
            FROM notifications n
            LEFT JOIN employee e ON n.posted_by = e.employee_id
            WHERE n.type IN ('General Notification', 'company_announcement')
            ORDER BY n.date DESC, n.id DESC
            LIMIT 500";
} else {
    $sql = "SELECT n.message, n.date, n.created_at, n.id
            FROM notifications n
            WHERE n.type IN ('General Notification', 'company_announcement')
            ORDER BY n.date DESC, n.id DESC
            LIMIT 500";
}
$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dedupe: one card per unique (normalized message body, date). Keep poster and created_at from first row.
$seen = [];
$announcements = [];
foreach ($rows as $r) {
    $body = normalize_company_announcement_message($r['message']);
    $key = $body . "\n" . $r['date'];
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $announcements[] = [
        'message'       => $body,
        'date'          => $r['date'],
        'created_at'    => $r['created_at'] ?? null,
        'id'            => $r['id'],
        'poster_name'   => null,
        'poster_role'   => null,
    ];
    if (!empty($r['poster_first_name'] ?? '') || !empty($r['poster_last_name'] ?? '')) {
        $announcements[array_key_last($announcements)]['poster_name'] = trim(($r['poster_first_name'] ?? '') . ' ' . ($r['poster_last_name'] ?? ''));
        $announcements[array_key_last($announcements)]['poster_role'] = $r['poster_role'] ?? null;
    }
}
$announcements = array_slice($announcements, 0, 50);

include "views/company_announcements_view.php";
