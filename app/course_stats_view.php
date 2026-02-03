<!DOCTYPE html>
<html>
<head>
    <title>Course Statistics · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/learning.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <section class="section-1">
            <div class="learning-container">
                <div class="learning-header">
                    <h2>
                        <i class="fa fa-bar-chart"></i> 
                        <?= htmlspecialchars($course['title']) ?> - Statistics
                        <a href="learning_admin.php" class="back-btn">
                            <i class="fa fa-arrow-left"></i> Back to Courses
                        </a>
                    </h2>
                </div>

                <!-- Course Summary -->
                <div class="stats-summary">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_enrollments'] ?></div>
                        <div class="stat-label">Total Enrollments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['completed'] ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['avg_rating'] ?: 'N/A' ?></div>
                        <div class="stat-label">Avg Rating</div>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div class="feedback-section">
                    <h3><i class="fa fa-comments"></i> Student Feedback</h3>
                    
                    <?php if ($feedback != 0): ?>
                        <div class="feedback-grid">
                            <?php foreach ($feedback as $item): ?>
                            <div class="feedback-card">
                                <div class="feedback-header">
                                    <div class="user-info">
                                        <strong><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></strong>
                                        <small>@<?= $item['username'] ?></small>
                                    </div>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa fa-star <?= $i <= $item['rating'] ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="feedback-body">
                                    <p><?= htmlspecialchars($item['feedback']) ?></p>
                                </div>
                                <div class="feedback-footer">
                                    <?= date('M d, Y', strtotime($item['submitted_at'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No feedback submitted for this course yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</body>
</html>