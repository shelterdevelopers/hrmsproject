<!DOCTYPE html>
<html>
<head>
    <title>Department Announcement · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <div class="section-1" style="overflow-y: auto; max-height: calc(100vh - 200px);">
            <div class="title-2">
                <h2><i class="fa fa-bullhorn"></i> Post Department Announcement</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-1" onsubmit="return confirm('Are you sure you want to post this announcement to your department only?');">
                <div class="input-holder">
                    <label for="message">Announcement Message</label>
                    <textarea id="message" name="message" required class="input-1" rows="8" 
                              placeholder="Enter your announcement for <?= htmlspecialchars($department) ?> department..."></textarea>
                </div>
                
                <div class="input-holder">
                    <p style="color: #666; font-size: 14px;">
                        <i class="fa fa-info-circle"></i> 
                        This announcement will be sent to all employees in the <strong><?= htmlspecialchars($department) ?></strong> department.
                    </p>
                </div>
                
                <button type="submit" name="submit_announcement" class="submit-btn">
                    <i class="fa fa-paper-plane"></i> Send Announcement
                </button>
            </form>
        </div>
    </div>
</body>
</html>
