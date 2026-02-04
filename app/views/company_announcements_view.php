<!DOCTYPE html>
<html>
<head>
    <title>Company Announcements · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/style.css">
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
        .announcement-meta {
            color: #666;
            font-size: 13px;
            font-weight: normal;
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
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <div class="announcements-container">
            <h1><i class="fa fa-newspaper-o"></i> Company Announcements</h1>
            
            <?php if (empty($announcements)): ?>
                <div class="no-announcements">
                    <i class="fa fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <p>No company announcements available.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): 
                    $created_at = $announcement['created_at'] ?? $announcement['date'] ?? null;
                    $date_time = $created_at ? date('M d, Y \a\t g:i A', strtotime($created_at)) : date('M d, Y', strtotime($announcement['date'] ?? 'now'));
                    $poster_name = isset($announcement['poster_name']) ? trim((string)$announcement['poster_name']) : '';
                    $poster_role = isset($announcement['poster_role']) ? $announcement['poster_role'] : '';
                ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div>
                                <strong>Company Announcement</strong>
                                <?php if ($poster_name !== ''): ?>
                                    <span class="announcement-meta"> — Posted by <?= htmlspecialchars($poster_name) ?><?= $poster_role !== '' ? ' (' . htmlspecialchars($poster_role) . ')' : '' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="announcement-date">
                                <i class="fa fa-calendar"></i> 
                                <span title="Date and time posted">Posted on <?= htmlspecialchars($date_time) ?></span>
                            </div>
                        </div>
                        <div class="announcement-message">
                            <?= htmlspecialchars($announcement['message']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
