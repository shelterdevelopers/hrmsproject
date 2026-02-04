<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Notification.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user's department
$employee_id = $_SESSION['employee_id'];
$department = RoleHelper::get_department($conn, $employee_id);
$is_manager = RoleHelper::is_manager($conn, $employee_id);

// Check if posted_by column exists (for poster name and timestamp display)
$has_posted_by = false;
try {
    $chk = $conn->query("SHOW COLUMNS FROM notifications LIKE 'posted_by'");
    $has_posted_by = $chk && $chk->rowCount() > 0;
} catch (Throwable $e) {}

if ($has_posted_by) {
    $sql = "SELECT n.message, n.date, n.created_at, n.id, n.recipient, n.posted_by,
                   e.first_name AS recipient_first_name, e.last_name AS recipient_last_name,
                   p.first_name AS poster_first_name, p.last_name AS poster_last_name, p.role AS poster_role
            FROM notifications n
            JOIN employee e ON n.recipient = e.employee_id
            LEFT JOIN employee p ON n.posted_by = p.employee_id
            WHERE n.type = 'department_announcement'
            AND LOWER(TRIM(e.department)) = LOWER(?)
            ORDER BY n.date DESC, n.id DESC
            LIMIT 100";
} else {
    $sql = "SELECT n.message, n.date, n.created_at, n.id, n.recipient,
                   e.first_name AS recipient_first_name, e.last_name AS recipient_last_name
            FROM notifications n
            JOIN employee e ON n.recipient = e.employee_id
            WHERE n.type = 'department_announcement'
            AND LOWER(TRIM(e.department)) = LOWER(?)
            ORDER BY n.date DESC, n.id DESC
            LIMIT 100";
}

$stmt = $conn->prepare($sql);
$stmt->execute([$department]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dedupe: group by message content and date (same announcement sent to multiple people); keep poster from first row
$seen = [];
$announcements = [];
foreach ($rows as $r) {
    $message = $r['message'];
    if (preg_match('/^Department\s+Announcement:\s*(.+)$/i', $message, $matches)) {
        $message = trim($matches[1]);
    }
    $key = $message . "\n" . $r['date'];
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $ann = [
        'message' => $message,
        'date' => $r['date'],
        'created_at' => $r['created_at'] ?? null,
        'id' => $r['id'],
    ];
    if ($has_posted_by && (!empty($r['poster_first_name']) || !empty($r['poster_last_name']))) {
        $ann['poster_name'] = trim(($r['poster_first_name'] ?? '') . ' ' . ($r['poster_last_name'] ?? ''));
        $ann['poster_role'] = $r['poster_role'] ?? null;
    } else {
        $ann['poster_name'] = null;
        $ann['poster_role'] = null;
    }
    $announcements[] = $ann;
}
$announcements = array_slice($announcements, 0, 50);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Announcements · Shelter HRMS</title>
    <?php include __DIR__ . '/../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/aesthetic-improvements.css">
    <style>
        .announcements-container {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }
        .announcement-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2596be;
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .announcement-date {
            color: #666;
            font-size: 14px;
        }
        .announcement-message {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .no-announcements {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .department-badge {
            display: inline-block;
            background: #2596be;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <section class="section-1">
            <h2 class="title"><i class="fa fa-bullhorn"></i> Department Announcements</h2>
            
            <div class="announcements-container">
                <?php if (empty($announcements)): ?>
                    <div class="no-announcements">
                        <i class="fa fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                        <p>No department announcements available for <strong><?= htmlspecialchars($department) ?></strong> department.</p>
                    </div>
                <?php else: ?>
                    <div class="department-badge">
                        <i class="fa fa-building"></i> <?= htmlspecialchars($department) ?> Department
                    </div>
                    <?php foreach ($announcements as $announcement): 
                        $created_at = $announcement['created_at'] ?? $announcement['date'] ?? null;
                        $date_time = $created_at ? date('M d, Y \a\t g:i A', strtotime($created_at)) : date('M d, Y', strtotime($announcement['date'] ?? 'now'));
                        $poster_name = isset($announcement['poster_name']) ? trim((string)$announcement['poster_name']) : '';
                        $poster_role = isset($announcement['poster_role']) ? $announcement['poster_role'] : '';
                    ?>
                        <div class="announcement-card">
                            <div class="announcement-header">
                                <div>
                                    <strong>Department Announcement</strong>
                                    <?php if ($poster_name !== ''): ?>
                                        <span class="announcement-meta" style="color: #666; font-size: 13px; font-weight: normal;"> — Posted by <?= htmlspecialchars($poster_name) ?><?= $poster_role !== '' ? ' (' . htmlspecialchars($poster_role) . ')' : '' ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="announcement-date">
                                    <i class="fa fa-calendar"></i> 
                                    <span title="Date and time posted">Posted on <?= htmlspecialchars($date_time) ?></span>
                                </div>
                            </div>
                            <div class="announcement-message">
                                <?= nl2br(htmlspecialchars($announcement['message'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
