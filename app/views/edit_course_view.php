<!DOCTYPE html>
<html>

<head>
    <title>Edit Course · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/learning.css">
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>

        <section class="section-1">
            
            <div class="learning-container">
                <h2><i class="fa fa-edit"></i> Edit Course</h2>

                <?php if (isset($success)): ?>
                <div class="success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                <div class="danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="post" class="admin-form" id="edit-course-form">

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="5"
                            required><?= htmlspecialchars($course['description']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration (hours)</label>
                            <input type="number" name="duration" min="1" value="<?= $course['duration'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" value="<?= htmlspecialchars($course['category']) ?>"
                                required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" <?= $course['is_active'] ? 'checked' : '' ?>>
                            Active Course
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Course</button>
                        <a href="learning_admin.php" class="btn cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</body>

</html>