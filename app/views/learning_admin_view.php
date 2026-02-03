<!DOCTYPE html>
<html>

<head>
    <title>Learning Admin · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/learning.css">
    <style>
        /* Add styles from learning_view.php if needed */
        /* ... styles for suggestion form, cards, approvals ... */
        .suggestion-form,
        .my-suggestions,
        .approval-section {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .suggestion-card,
        .approval-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }

        .suggestion-header,
        .approval-header {
            padding: 10px 15px;
            background: #e9ecef;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-body,
        .approval-body {
            padding: 15px;
        }

        .suggestion-body p,
        .approval-body p {
            margin: 5px 0;
        }

        .suggestion-body strong,
        .approval-body strong {
            color: #495057;
            min-width: 80px;
            display: inline-block;
        }

        .status-tag {
            padding: 3px 8px;
            border-radius: 12px;
            color: white;
            font-size: 0.9em;
            text-transform: capitalize;
        }

        .status-pending_manager {
            background-color: #ffc107;
            color: #333;
        }

        .status-pending_executive {
            background-color: #17a2b8;
        }

        .status-approved {
            background-color: #28a745;
        }

        .status-denied {
            background-color: #dc3545;
        }

        .approval-actions {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
            margin-top: 15px;
        }

        .approval-actions textarea {
            margin-bottom: 10px;
        }

        .approval-actions .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .approval-actions .btn-success {
            background-color: #28a745;
        }

        .approval-actions .btn-danger {
            background-color: #dc3545;
        }

        .approval-actions .btn-info {
            background-color: #17a2b8;
        }

        hr {
            border: none;
            border-top: 1px solid #dee2e6;
            margin: 30px 0;
        }

        h3 {
            margin-bottom: 20px;
            color: #343a40;
        }

        .admin-form h4,
        .admin-table h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #0056b3;
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include '../inc/header.php'; ?>
    <div class="body">
        <?php include "../inc/nav.php"; ?>

        <section class="section-1">
            <div class="learning-container">
                <h2><i class="fa fa-cogs"></i> Learning & Development Admin</h2>

                <?php if (isset($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if (isset($error)): ?><div class="danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="learning-section">
                    <h3><i class="fa fa-book"></i> Course Catalog Management</h3>
                    <div class="admin-form">
                        <h4>Add Course Directly</h4>
                        <form method="post" id="add-course-form">
                            <div class="form-group"><label>Title*</label><input type="text" name="title" class="input-1" required></div>
                            <div class="form-group"><label>Description*</label><textarea name="description" class="input-1" rows="3" required></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Duration (hrs)*</label><input type="number" name="duration" class="input-1" min="1" required></div>
                                <div class="form-group"><label>Category*</label><input type="text" name="category" class="input-1" required></div>
                            </div>
                            <div class="form-group"><label>Link (Optional)</label><input type="url" name="link" class="input-1" placeholder="https://..."></div>
                            <button type="submit" name="add_course" class="btn"><i class="fa fa-plus"></i> Add Course to Catalog</button>
                        </form>
                    </div>

                    <div class="admin-table table-responsive">
                        <h4>Manage Existing Courses</h4>
                        <?php if (!empty($courses)): ?>
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($course['title']) ?></td>
                                            <td><?= htmlspecialchars($course['category']) ?></td>
                                            <td><?= htmlspecialchars($course['duration']) ?> hours</td>
                                            <td><?= $course['is_active'] ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>' ?></td>
                                            <td>
                                                <a href="edit_course.php?id=<?= $course['course_id'] ?>" class="btn btn-sm" style="background-color: #ffc107; color: #333;"><i class="fa fa-pencil"></i> Edit</a>
                                                <a href="toggle_course.php?id=<?= $course['course_id'] ?>" class="btn btn-sm" style="background-color: <?= $course['is_active'] ? '#dc3545' : '#28a745'; ?>;">
                                                    <i class="fa <?= $course['is_active'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i> <?= $course['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </a>
                                                <!--<a href="course_stats_view.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm" style="background-color: #17a2b8;"><i class="fa fa-bar-chart"></i> Stats</a>-->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-records"><i class="fa fa-book"></i><br> No courses found in the catalog.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <div class="learning-section">
                    <h3>Enrollment Management & Verification</h3>
                    <div class="admin-table table-responsive">
                        <?php if (!empty($enrollments)): ?>
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Course</th>
                                        <th>Enrolled</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></td>
                                            <td><?= htmlspecialchars($enrollment['title']) ?></td>
                                            <td><?= date('M d, Y', strtotime($enrollment['enrolled_at'])) ?></td>
                                            <td>
                                                <div class="progress-container">
                                                    <div class="progress-bar" style="width:<?= (int)$enrollment['progress'] ?>%"><?= (int)$enrollment['progress'] ?>%</div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($enrollment['progress'] == 100 && $enrollment['verified_at']): ?><span class="status-badge status-approved" style="color:#ffffff;">Verified</span>
                                                <?php elseif ($enrollment['progress'] == 100): ?><span class="status-badge status-pending">Pending Verification</span>
                                                <?php else: ?><span class="status-badge">In Progress</span><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($enrollment['progress'] == 100 && !$enrollment['verified_at']): ?>
                                                    <button class="btn btn-sm verify-btn" style="background-color:#28a745;" data-enrollment="<?= $enrollment['enrollment_id'] ?>"><i class="fa fa-check"></i> Verify</button>
                                                <?php elseif ($enrollment['verified_at']): ?>
                                                    <span>Verified on <?= date('M d, Y', strtotime($enrollment['verified_at'])) ?></span>
                                                <?php else: echo '-';
                                                endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-records"><i class="fa fa-users"></i><br> No enrollments found.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <script>
        // Keep existing JS for verification, status dropdowns (if any remain), feedback modal
        document.addEventListener('DOMContentLoaded', function() {
            // Verification button AJAX
            document.querySelectorAll('.verify-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const enrollmentId = this.getAttribute('data-enrollment');
                    if (confirm('Verify completion for enrollment ID ' + enrollmentId + '?')) {
                        fetch('verify_completion.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'enrollment_id=' + enrollmentId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Verification failed!');
                                }
                            })
                            .catch(err => {
                                console.error('Verify error:', err);
                                alert('An error occurred.');
                            });
                    }
                });
            });
        });

        // Verification functionality
        document.querySelectorAll('.verify-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const enrollmentId = this.getAttribute('data-enrollment');
                if (confirm('Verify this course completion?')) {
                    fetch('verify_completion.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'enrollment_id=' + enrollmentId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                }
            });
        });

        // Show completion alert when reaching 100%
        <?php if (isset($_GET['completed']) && $_GET['completed'] == '1'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'completion-alert';
                alert.innerHTML = 'Course marked as completed! Waiting for admin verification.';
                document.body.appendChild(alert);
                setTimeout(() => alert.style.display = 'block', 500);
                setTimeout(() => alert.style.display = 'none', 5000);
            });
        <?php endif; ?>
    </script>
    <script>
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                const enrollmentId = this.getAttribute('data-enrollment');
                const status = this.value;

                fetch('update_enrollment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `enrollment_id=${enrollmentId}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            });
        });
    </script>
    <script>
        // Modal functionality
        const modal = document.getElementById('feedbackModal');
        const modalContent = document.getElementById('feedbackContent');
        const closeBtn = document.querySelector('.close-modal');

        document.querySelectorAll('.view-feedback').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                modalContent.textContent = this.getAttribute('data-feedback');
                modal.style.display = 'block';
            });
        });

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target == modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>

</html>