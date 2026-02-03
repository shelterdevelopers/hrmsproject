<!DOCTYPE html>
<html>

<head>
    <title>Learning & Development · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/learning.css">
    <link rel="stylesheet" href="../css/aesthetic-improvements.css">
    <style>
        /* Add styles for suggestion cards/tables and approval forms if needed */
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

        /* For Forward/Approve to Exec */
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php"; ?>
    <div class="body">
        <?php include "../inc/nav.php"; ?>

        <section class="section-1">
            <div class="learning-container">
                <h2><i class="fa fa-graduation-cap"></i> Learning & Development</h2>

                <?php if (!empty($success)): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if (!empty($error)): ?><div class="danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="suggestion-form">
                    <h3>Suggest a New Course</h3>
                    <form method="post" class="form-1">
                        <div class="form-group">
                            <label for="sugg-title">Course Title*</label>
                            <input type="text" id="sugg-title" name="title" class="input-1" required>
                        </div>
                        <div class="form-group">
                            <label for="sugg-desc">Description*</label>
                            <textarea id="sugg-desc" name="description" class="input-1" rows="3" required></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sugg-duration">Duration (hours)*</label>
                                <input type="number" id="sugg-duration" name="duration" class="input-1" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="sugg-category">Category*</label>
                                <input type="text" id="sugg-category" name="category" class="input-1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sugg-link">Link (Optional)</label>
                            <input type="url" id="sugg-link" name="link" class="input-1" placeholder="https://example.com/course">
                        </div>
                        <button type="submit" name="suggest_course" class="btn"><i class="fa fa-lightbulb-o"></i> Submit Suggestion</button>
                    </form>
                </div>

                <div class="my-suggestions">
                    <h3>My Course Suggestions</h3>
                    <?php if (!empty($my_suggestions)): ?>
                        <?php foreach ($my_suggestions as $suggestion): ?>
                            <div class="suggestion-card">
                                <div class="suggestion-header">
                                    <strong><?= htmlspecialchars($suggestion['title']) ?></strong>
                                    <span class="status-tag status-<?= $suggestion['status'] ?>">
                                        <?= str_replace('_', ' ', $suggestion['status']) ?>
                                    </span>
                                </div>
                                <div class="suggestion-body">
                                    <p><strong>Category:</strong> <?= htmlspecialchars($suggestion['category']) ?></p>
                                    <p><strong>Duration:</strong> <?= htmlspecialchars($suggestion['duration']) ?> hours</p>
                                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($suggestion['description'])) ?></p>
                                    <?php if ($suggestion['link']): ?>
                                        <p><strong>Link:</strong> <a href="<?= htmlspecialchars($suggestion['link']) ?>" target="_blank" rel="noopener noreferrer">View Link</a></p>
                                    <?php endif; ?>
                                    <p><strong>Submitted:</strong> <?= date('M d, Y', strtotime($suggestion['submitted_at'])) ?></p>
                                    <?php if ($suggestion['manager_comment']): ?>
                                        <p><strong>Manager Comment:</strong> <?= nl2br(htmlspecialchars($suggestion['manager_comment'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($suggestion['executive_comment']): ?>
                                        <p><strong>Executive Comment:</strong> <?= nl2br(htmlspecialchars($suggestion['executive_comment'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>You haven't suggested any courses yet.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($pending_manager_approvals)): ?>
                    <div class="approval-section">
                        <h3><i class="fa fa-user-secret"></i> Course Suggestions Pending Approval</h3>
                        <?php foreach ($pending_manager_approvals as $suggestion): ?>
                            <div class="approval-card">

                                <div class="approval-header">
                                    <strong><?= htmlspecialchars($suggestion['title']) ?></strong>
                                    <span class="status-tag status-<?= htmlspecialchars($suggestion['status']) ?>">
                                        <?= str_replace('_', ' ', htmlspecialchars($suggestion['status'])) ?>
                                    </span>
                                </div>

                                <div class="approval-body">
                                    <p><strong>Suggested By:</strong> <?= htmlspecialchars($suggestion['emp_fname'] . ' ' . $suggestion['emp_lname']) ?></p>
                                    <p><strong>Submitted:</strong> <?= date('M d, Y', strtotime($suggestion['submitted_at'])) ?></p>
                                    <p><strong>Category:</strong> <?= htmlspecialchars($suggestion['category']) ?></p>
                                    <p><strong>Duration:</strong> <?= htmlspecialchars($suggestion['duration']) ?> hours</p>
                                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($suggestion['description'])) ?></p>
                                    <?php if (!empty($suggestion['link'])): ?>
                                        <p><strong>Link:</strong> <a href="<?= htmlspecialchars($suggestion['link']) ?>" target="_blank" rel="noopener noreferrer">View Course Link</a></p>
                                    <?php endif; ?>
                                </div>

                                <?php // --- NEW LOGIC: Show form only if pending MY action --- 
                                ?>
                                <?php if ($suggestion['status'] == 'pending_manager'): ?>
                                    <form method="post" class="approval-actions">
                                        <input type="hidden" name="suggestion_id" value="<?= $suggestion['suggestion_id'] ?>">
                                        <input type="hidden" name="respond_suggestion" value="1">
                                        <div class="form-group">
                                            <label for="mgr_comment_<?= $suggestion['suggestion_id'] ?>">Comment (Optional)</label>
                                            <textarea id="mgr_comment_<?= $suggestion['suggestion_id'] ?>" name="comment" class="input-1" rows="2"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="exec_select_<?= $suggestion['suggestion_id'] ?>">Forward To Executive (Optional)</label>
                                            <select name="executive_id" id="exec_select_<?= $suggestion['suggestion_id'] ?>" class="input-1">
                                                <option value="">-- Select Executive (Optional) --</option>
                                                <?php foreach ($all_executives as $executive): ?>
                                                    <option value="<?= $executive['employee_id'] ?>">
                                                        <?= htmlspecialchars($executive['first_name'] . ' ' . $executive['last_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small style="color: var(--text-secondary); display: block; margin-top: 5px;">Leave empty to approve directly, or select an executive to forward for review.</small>
                                        </div>
                                        <div class="action-buttons">
                                            <button type="submit" name="action" value="deny" class="btn btn-danger"><i class="fa fa-times"></i> Deny</button>
                                            <button type="submit" name="action" value="approve" class="btn btn-success"><i class="fa fa-check"></i> Approve</button>
                                            <button type="submit" name="action" value="forward_to_executive" class="btn btn-info"><i class="fa fa-share"></i> Forward to Executive</button>
                                        </div>
                                    </form>
                                <?php elseif ($suggestion['status'] == 'pending_executive'): ?>
                                    <div class="approval-actions">
                                        <p>
                                            <strong>Status:</strong> Forwarded to executive (<strong><?= htmlspecialchars($suggestion['exec_fname'] . ' ' . $suggestion['exec_lname']) ?></strong>) for final approval.
                                        </p>
                                        <?php if (!empty($suggestion['manager_comment'])): ?>
                                            <p style="margin-top: 10px; font-style: italic;"><strong>Your Comment:</strong> <?= nl2br(htmlspecialchars($suggestion['manager_comment'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_executive && !empty($pending_executive_approvals)): ?>
                    <div class="approval-section">
                        <h3><i class="fa fa-users"></i> Course Suggestions Pending Executive Approval</h3>
                        <?php foreach ($pending_executive_approvals as $suggestion):
                            // Only show if assigned to THIS executive OR if no executive assigned yet (fallback?)
                            if ($suggestion['executive_id'] == $employee_id || empty($suggestion['executive_id'])):
                        ?>
                                <div class="approval-card">
                                    <div class="approval-header">...</div>
                                    <div class="approval-body">...</div>

                                    <form method="post" class="approval-actions">
                                        <input type="hidden" name="suggestion_id" value="<?= $suggestion['suggestion_id'] ?>">
                                        <input type="hidden" name="respond_suggestion" value="1">
                                        <div class="form-group">
                                            <label for="exec_comment_<?= $suggestion['suggestion_id'] ?>">Comment (Optional)</label>
                                            <textarea id="exec_comment_<?= $suggestion['suggestion_id'] ?>" name="comment" class="input-1" rows="2"></textarea>
                                        </div>
                                        <div class="action-buttons">
                                            <button type="submit" name="action" value="deny" class="btn btn-danger"><i class="fa fa-times"></i> Deny</button>
                                            <button type="submit" name="action" value="approve" class="btn btn-success"><i class="fa fa-check"></i> Approve & Add</button>
                                        </div>
                                    </form>
                                </div>
                        <?php endif;
                        endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="learning-section">
                    <h3><i class="fa fa-book"></i> Available Courses</h3>
                    <div class="course-grid">
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <?php if ($course['is_active']): ?>

                                    <div class="course-card">
                                        <div class="course-header">
                                            <h4><?= htmlspecialchars($course['title']) ?></h4><span class="course-category"><?= $course['category'] ?></span>
                                        </div>
                                        <div class="course-body">
                                            <p><?= htmlspecialchars($course['description']) ?></p>
                                            <div class="course-meta"><span><i class="fa fa-clock-o"></i> <?= $course['duration'] ?> hours</span></div>
                                            <?php if (!empty($course['link'])): ?>
                                                <a href="<?= htmlspecialchars($course['link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="margin-top: 10px;"><i class="fa fa-external-link"></i> View Course</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="course-footer">
                                            <form method="post">
                                                <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                                <button type="submit" name="enroll" class="btn enroll-btn"><i class="fa fa-plus-circle"></i> Enroll</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No courses match your search or none are available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="learning-section">
                    <h3><i class="fa fa-tasks"></i> My Learning Progress</h3>
                    <?php if (!empty($enrollments)): ?>
                        <div class="progress-table table-responsive">
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($enrollment['title']) ?></td>
                                            <td>
                                                <div class="progress-container">
                                                    <div class="progress-bar" style="width: <?= (int)$enrollment['progress'] ?>%"><?= (int)$enrollment['progress'] ?>%</div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($enrollment['progress'] == 100 && $enrollment['verified_at']): ?>
                                                    <span class="status-badge status-approved" style="color:#ffffff;">Verified</span>
                                                <?php elseif ($enrollment['progress'] == 100): ?><span class="status-badge status-pending">Pending Verification</span>
                                                <?php else: ?><span class="status-badge status-inprogress">In Progress</span><?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (!$enrollment['completed_at']): ?>
                                                        <form method="post" class="progress-form" style="display:inline-block;">
                                                            <input type="hidden" name="enrollment_id" value="<?= $enrollment['enrollment_id'] ?>">
                                                            <label for="prog_<?= $enrollment['enrollment_id'] ?>" class="sr-only">Update Progress</label> <select name="progress" id="prog_<?= $enrollment['enrollment_id'] ?>" onchange="this.form.submit()" class="input-1" style="width: auto; padding: 5px 8px;">
                                                                <option value="0" <?= $enrollment['progress'] == 0 ? 'selected' : '' ?>>0%</option>
                                                                <option value="25" <?= $enrollment['progress'] == 25 ? 'selected' : '' ?>>25%</option>
                                                                <option value="50" <?= $enrollment['progress'] == 50 ? 'selected' : '' ?>>50%</option>
                                                                <option value="75" <?= $enrollment['progress'] == 75 ? 'selected' : '' ?>>75%</option>
                                                                <option value="100" <?= $enrollment['progress'] == 100 ? 'selected' : '' ?>>100%</option>
                                                            </select>
                                                            <input type="hidden" name="update_progress" value="1">
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($enrollment['progress'] == 100): ?>
                                                    <?php endif; ?>
                                                    <?php $course_details = Learning::get_course_details($conn, $enrollment['course_id']);
                                                    if ($course_details && !empty($course_details['link'])): ?>
                                                        <a href="<?= htmlspecialchars($course_details['link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm"><i class="fa fa-external-link"></i> View</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-records"><i class="fa fa-info-circle"></i><br>You are not enrolled in any courses yet.</div>
                    <?php endif; ?>
                </div>

            </div>
        </section>
    </div>

    y
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
        // Star rating functionality
        document.querySelectorAll('.rating-stars i').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating-input').value = rating;

                // Update star display
                document.querySelectorAll('.rating-stars i').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                document.querySelectorAll('.rating-stars i').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });

            star.addEventListener('mouseout', function() {
                document.querySelectorAll('.rating-stars i').forEach(s => {
                    s.classList.remove('hover');
                });
            });
        });
    </script>
</body>

</html>