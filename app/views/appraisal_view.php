<!DOCTYPE html>
<html>

<head>
    <title>Appraisals · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/aesthetic-improvements.css">
    <style>
        .appraisal-tab.active {
            background: #2596be;
            color: white;
            border-color: #ddd;
        }

        .appraisal-tab:hover:not(.active) {
            background: #f0f0f0;
        }

        .appraisal-tab-content {
            display: none;
        }

        .appraisal-tab-content.active {
            display: block;
        }

        /* Table styling */
        .appraisal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .appraisal-table th {
            background: #2596be;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .appraisal-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e1f5ec;
        }

        .appraisal-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .appraisal-table tr:hover {
            background-color: #e1f5ec;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-review {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        /* Tab styling */
        .appraisal-tabs {
            width: 100%;

            display: flex;
            /* Align to the left */
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            padding-left: 0;
            /* Reset any padding */
            margin-left: 0;
            /* Reset any margin */
        }
        

        .appraisal-tab {

            padding: 12px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
            font-size: 1.15rem;
            font-weight: 700;
        }
        .appraisal-section-title {
            margin-top: 24px;
            margin-bottom: 10px;
            color: #0c5460;
            font-size: 1.1rem;
        }
        .appraisal-section-title:first-of-type { margin-top: 12px; }
        .appraisal-section-empty {
            color: #666;
            margin-bottom: 16px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        <section class="section-1">
            <div class="applications-container">
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h2><i class="fa fa-file-text"></i> Performance Appraisals</h2>

            <!-- Tab Navigation -->
            <!-- Tab Navigation -->
            <div class="appraisal-tabs" style="justify-content: flex-start;">
                <div class="appraisal-tab <?= $active_tab == 'active' ? 'active' : '' ?>"
                    onclick="window.location.href='appraisal.php?tab=active'">
                    Active Appraisals
                </div>
                <div class="appraisal-tab <?= $active_tab == 'completed' ? 'active' : '' ?>"
                    onclick="window.location.href='appraisal.php?tab=completed'">
                    Completed Appraisals
                </div>
                <?php if (isset($can_create_appraisal) && $can_create_appraisal): ?>
                    <div class="appraisal-tab <?= $active_tab == 'new' ? 'active' : '' ?>"
                        onclick="window.location.href='appraisal.php?tab=new'">
                        Create New Appraisal
                    </div>
                <?php endif; ?>
            </div>


            <!-- Active Appraisals Tab -->
            <div class="appraisal-tab-content <?= $active_tab == 'active' ? 'active' : '' ?>">
                <h3><i class="fa fa-clock-o"></i> Active Appraisals</h3>
                <?php
                $active_conducting = array_filter($active_forms ?? [], function($f) use ($employee_id) { return (int)($f['manager_id'] ?? 0) === (int)$employee_id; });
                $active_receiving = array_filter($active_forms ?? [], function($f) use ($employee_id) { return (int)($f['employee_id'] ?? 0) === (int)$employee_id; });
                ?>
                <?php if (empty($active_forms)): ?>
                    <p>You don't have any active appraisals at this time.</p>
                <?php else: ?>
                    <?php if (isset($can_conduct_appraisal) && $can_conduct_appraisal): ?>
                    <!-- Appraisals you're conducting (MD and managers only – not shown to HR or average employees) -->
                    <h4 class="appraisal-section-title"><i class="fa fa-user-plus"></i> Appraisals you're conducting</h4>
                    <?php if (empty($active_conducting)): ?>
                        <p class="appraisal-section-empty">None. Create one from the <strong>Create New Appraisal</strong> tab.</p>
                    <?php else: ?>
                        <table class="appraisal-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Acknowledgement</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_conducting as $form): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($form['employee_first_name'] . ' ' . $form['employee_last_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($form['period_start'])) ?> – <?= date('M d, Y', strtotime($form['period_end'])) ?></td>
                                        <td><span class="status-badge status-<?= $form['appraisal_status'] ?>"><?= ucfirst(str_replace('_', ' ', $form['appraisal_status'])) ?></span></td>
                                        <td><?php if ($form['is_acknowledged'] ?? 0): ?><span class="status-badge status-completed"><i class="fa fa-check"></i> Acknowledged</span><?php else: ?><span class="status-badge status-review">Pending Acknowledgement</span><?php endif; ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($form['updated_at'])) ?></td>
                                        <td>
                                            <a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-eye"></i> View</a>
                                            <?php if (!($form['is_acknowledged'] ?? 0)): ?><a href="edit_appraisal.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-edit"></i> Edit</a><?php endif; ?>
                                            <?php
                                            $can_del = (isset($is_hr) && $is_hr) || (isset($is_managing_director) && $is_managing_director) || ((int)($form['manager_id'] ?? 0) === (int)$employee_id && in_array($form['appraisal_status'] ?? '', ['draft', 'shared', 'employee_review'], true));
                                            if ($can_del): ?>
                                                <a href="delete_appraisal.php?id=<?= $form['form_id'] ?>" class="btn" style="background:#dc3545;" onclick="return confirm('Delete this appraisal?');"><i class="fa fa-trash"></i> Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Appraisals for you (you are the employee being appraised) -->
                    <h4 class="appraisal-section-title"><i class="fa fa-user"></i> Appraisals for you</h4>
                    <?php if (empty($active_receiving)): ?>
                        <p class="appraisal-section-empty">None at this time. When your manager or HR shares an appraisal with you, it will appear here and you can open it to complete your Self Assessment.</p>
                    <?php else: ?>
                        <?php $first_receiving = reset($active_receiving); $first_form_id = (int)($first_receiving['form_id'] ?? 0); ?>
                        <div style="margin-bottom: 20px; padding: 20px 24px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border: 2px solid #2596be; border-radius: 12px;">
                            <p style="margin: 0 0 16px 0; color: #0c5460; font-size: 1.05em;">
                                <strong><i class="fa fa-edit"></i> Complete your Self Assessment</strong><br>
                                Fill in your self-assessment (ratings and the five sections), then save and acknowledge when ready.
                            </p>
                            <a href="appraisal_detail.php?id=<?= $first_form_id ?>" class="btn" style="display: inline-block; padding: 14px 28px; font-size: 1.1em;">
                                <i class="fa fa-edit"></i> Open Self Assessment form
                            </a>
                        </div>
                        <p style="margin-bottom: 14px; color: #555; font-size: 0.95em;">Or open a specific appraisal from the table below:</p>
                        <table class="appraisal-table">
                            <thead>
                                <tr>
                                    <th>Appraiser</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Acknowledgement</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_receiving as $form): ?>
                                    <tr>
                                        <td><?= isset($form['manager_first_name'], $form['manager_last_name']) ? htmlspecialchars($form['manager_first_name'] . ' ' . $form['manager_last_name']) : '—' ?></td>
                                        <td><?= date('M d, Y', strtotime($form['period_start'])) ?> – <?= date('M d, Y', strtotime($form['period_end'])) ?></td>
                                        <td><span class="status-badge status-<?= $form['appraisal_status'] ?>"><?= ucfirst(str_replace('_', ' ', $form['appraisal_status'])) ?></span></td>
                                        <td><?php if ($form['is_acknowledged'] ?? 0): ?><span class="status-badge status-completed"><i class="fa fa-check"></i> Acknowledged</span><?php else: ?><span class="status-badge status-review">Pending Acknowledgement</span><?php endif; ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($form['updated_at'])) ?></td>
                                        <td><a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-eye"></i> View &amp; complete Self Assessment</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Completed Appraisals Tab -->
            <div class="appraisal-tab-content <?= $active_tab == 'completed' ? 'active' : '' ?>">
                <h3><i class="fa fa-check-circle"></i> Completed Appraisals</h3>
                <?php
                $completed_conducting = array_filter($completed_forms ?? [], function($f) use ($employee_id) { return (int)($f['manager_id'] ?? 0) === (int)$employee_id; });
                $completed_receiving = array_filter($completed_forms ?? [], function($f) use ($employee_id) { return (int)($f['employee_id'] ?? 0) === (int)$employee_id; });
                $show_hr_filing = isset($is_hr) && $is_hr;
                ?>
                <?php if (empty($completed_forms)): ?>
                    <p>You don't have any completed appraisals.</p>
                <?php else: ?>
                    <?php if ($show_hr_filing): ?>
                        <!-- HR: single list of all completed appraisals for file-keeping (print both forms, sign, file) -->
                        <h4 class="appraisal-section-title"><i class="fa fa-folder-open"></i> Completed appraisals (for filing)</h4>
                        <div class="appraisal-section-empty" style="margin-bottom: 16px; padding: 14px 18px; background: #e8f4f8; border-left: 4px solid #2596be; border-radius: 6px;">
                            <p style="margin: 0 0 8px 0; font-weight: 600; color: #0c5460;">Flow:</p>
                            <p style="margin: 0 0 6px 0;"><strong>Average employees</strong> fill in Self Assessment first; when completed it goes to their manager. The manager appraises them using the <strong>Non-Managerial Performance Evaluation</strong> form. When done, both forms appear here for you to print and file.</p>
                            <p style="margin: 0;"><strong>Managers (including HR)</strong> also fill Self Assessment; that goes to the MD. The MD appraises them using the <strong>Management Performance Evaluation</strong> form. When the MD is done, both forms appear here for you to print and file.</p>
                        </div>
                        <table class="appraisal-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Period</th>
                                    <th>Completed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_forms as $form):
                                    $emp_role_lower = strtolower($form['employee_role'] ?? '');
                                    $is_manager_appraisee = in_array($emp_role_lower, ['manager', 'hr', 'hr_manager', 'managing_director']) || !empty($form['executive_member']);
                                    $eval_print_file = $is_manager_appraisee ? 'appraisal_print_management.php' : 'appraisal_print_evaluation.php';
                                    $fid = (int)$form['form_id'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($form['employee_first_name'] . ' ' . $form['employee_last_name']) ?></td>
                                        <td><?= htmlspecialchars($form['department'] ?? '') ?></td>
                                        <td><?= date('M d, Y', strtotime($form['period_start'])) ?> – <?= date('M d, Y', strtotime($form['period_end'])) ?></td>
                                        <td><?= !empty($form['completed_at']) ? date('M d, Y', strtotime($form['completed_at'])) : date('M d, Y', strtotime($form['updated_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn" style="background:#2596be;" onclick="window.open('appraisal_print_self.php?id=<?= $fid ?>'); window.open('<?= htmlspecialchars($eval_print_file, ENT_QUOTES, 'UTF-8') ?>?id=<?= $fid ?>');"><i class="fa fa-print"></i> Print for filing</button>
                                            <a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-eye"></i> View</a>
                                            <a href="delete_appraisal.php?id=<?= $form['form_id'] ?>" class="btn" style="background:#dc3545;" onclick="return confirm('Delete this appraisal from records?');"><i class="fa fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <?php if (isset($can_conduct_appraisal) && $can_conduct_appraisal): ?>
                        <!-- Completed appraisals you conducted (MD and managers only) -->
                        <h4 class="appraisal-section-title"><i class="fa fa-user-plus"></i> Completed appraisals you conducted</h4>
                        <?php if (empty($completed_conducting)): ?>
                            <p class="appraisal-section-empty">None.</p>
                        <?php else: ?>
                            <table class="appraisal-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Period</th>
                                        <th>Completed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_conducting as $form): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($form['employee_first_name'] . ' ' . $form['employee_last_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($form['period_start'])) ?> – <?= date('M d, Y', strtotime($form['period_end'])) ?></td>
                                            <td><?= !empty($form['completed_at']) ? date('M d, Y', strtotime($form['completed_at'])) : date('M d, Y', strtotime($form['updated_at'])) ?></td>
                                            <td>
                                                <a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-eye"></i> View</a>
                                                <?php if ((isset($is_hr) && $is_hr) || (isset($is_managing_director) && $is_managing_director)): ?>
                                                    <a href="delete_appraisal.php?id=<?= $form['form_id'] ?>" class="btn" style="background:#dc3545;" onclick="return confirm('Delete this completed appraisal?');"><i class="fa fa-trash"></i> Delete</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- Your completed appraisals (you were the employee) -->
                        <h4 class="appraisal-section-title"><i class="fa fa-user"></i> Your completed appraisals</h4>
                        <?php if (empty($completed_receiving)): ?>
                            <p class="appraisal-section-empty">None.</p>
                        <?php else: ?>
                            <table class="appraisal-table">
                                <thead>
                                    <tr>
                                        <th>Appraiser</th>
                                        <th>Period</th>
                                        <th>Completed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_receiving as $form): ?>
                                        <tr>
                                            <td><?= isset($form['manager_first_name'], $form['manager_last_name']) ? htmlspecialchars($form['manager_first_name'] . ' ' . $form['manager_last_name']) : '—' ?></td>
                                            <td><?= date('M d, Y', strtotime($form['period_start'])) ?> – <?= date('M d, Y', strtotime($form['period_end'])) ?></td>
                                            <td><?= !empty($form['completed_at']) ? date('M d, Y', strtotime($form['completed_at'])) : date('M d, Y', strtotime($form['updated_at'])) ?></td>
                                            <td><a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn"><i class="fa fa-eye"></i> View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- New Appraisal Tab (for managers/admins) -->
            <?php if (isset($can_create_appraisal) && $can_create_appraisal): ?>
                <div class="appraisal-tab-content <?= $active_tab == 'new' ? 'active' : '' ?>">
                    <h3><i class="fa fa-plus-circle"></i> Create New Appraisal</h3>
                    <p style="margin-bottom: 15px; color: #555;">Select the employee, set the appraisal period, then click Create Appraisal. The new appraisal will appear under <strong>Active Appraisals</strong> where you can fill in scores and share it with the employee.</p>
                    <form method="post" action="<?= htmlspecialchars(BASE_URL ?? '/') ?>app/appraisal.php" id="create-appraisal-form">
                        <div class="form-group">
                            <label>Employee</label>
                            <select name="employee_id" class="input-1" required>
                                <?php
                                // Admin does not appraise anyone. MD appraises all managers including HR. Employees are appraised by departmental managers or HR.
                                require_once __DIR__ . '/../Model/RoleHelper.php';
                                $md_id = RoleHelper::get_managing_director_id($conn);
                                if (isset($is_managing_director) && $is_managing_director) {
                                    // MD appraises all managers including HR (never the MD)
                                    $sql = "SELECT employee_id, first_name, last_name, department 
                                            FROM employee 
                                            WHERE status = 'active' 
                                            AND employee_id != ?
                                            AND (LOWER(role) IN ('manager', 'hr', 'hr_manager') OR executive_member = 1)";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute([$employee_id]);
                                } elseif (isset($is_hr) && $is_hr) {
                                    // HR can appraise all (regular) employees – employees can be appraised by both departmental managers and HR
                                    $sql = "SELECT employee_id, first_name, last_name FROM employee 
                                            WHERE status = 'active' AND LOWER(role) = 'employee'";
                                    if ($md_id) {
                                        $sql .= " AND employee_id != ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute([$md_id]);
                                    } else {
                                        $stmt = $conn->prepare($sql);
                                        $stmt->execute();
                                    }
                                } elseif (isset($is_finance_manager) && $is_finance_manager) {
                                    // Finance Manager (departmental manager) appraises their direct reports in Finance
                                    $sql = "SELECT employee_id, first_name, last_name FROM employee 
                                            WHERE department = ? AND status = 'active' AND manager_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute([RoleHelper::DEPT_FINANCE, $employee_id]);
                                } else {
                                    // Departmental managers appraise their direct reports
                                    $sql = "SELECT employee_id, first_name, last_name FROM employee 
                                            WHERE manager_id = ? AND status = 'active'";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute([$employee_id]);
                                }
                                $employees = $stmt->fetchAll();

                                if (empty($employees)): ?>
                                    <option value="">No employees available</option>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['employee_id'] ?>">
                                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            <?php if (isset($is_managing_director) && $is_managing_director && isset($emp['department'])): ?>
                                                (<?= htmlspecialchars($emp['department']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Appraisal Period Start</label>
                                <input type="date" name="period_start" class="input-1" required>
                            </div>
                            <div class="form-group">
                                <label>Appraisal Period End</label>
                                <input type="date" name="period_end" class="input-1" required>
                            </div>
                        </div>

                        <button type="submit" name="create_appraisal" class="btn">
                            <i class="fa fa-paper-plane"></i> Create Appraisal
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            </div>
        </section>
    </div>
</body>

</html>