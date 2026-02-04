<!DOCTYPE html>
<html>
<head>
    <title>All Attendance · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <style>
        .attendance-container {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }
        .filter-section {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .employee-card {
            background: white;
            padding: 30px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 100%;
        }
        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2596be;
            gap: 10px;
        }
        .employee-header .meta {
            color: #666;
            font-size: 13px;
            text-align: right;
        }
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .attendance-table th { background: #2596be; color: white; padding: 10px; text-align: left; }
        .attendance-table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .attendance-table tr:hover { background: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-present { background: #d4edda; color: #155724; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-leave { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>

        <div class="attendance-container">
            <h1><i class="fa fa-calendar-check-o"></i> All Attendance (HR)</h1>

            <div class="filter-section">
                <form method="GET" class="filter-row">
                    <div class="form-group" style="min-width: 500px; flex: 1;">
                        <label>Department</label>
                        <select name="department" class="input-1" onchange="this.form.submit()" style="min-width: 500px; padding: 10px 12px; font-size: 14px;">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>" <?= ($selected_department === $d) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="min-width: 500px; flex: 1;">
                        <label>Employee</label>
                        <select name="employee_id" class="input-1" onchange="this.form.submit()" style="min-width: 500px; padding: 10px 12px; font-size: 14px;">
                            <option value="">All Employees</option>
                            <?php foreach ($employee_list as $emp): ?>
                                <option value="<?= (int)$emp['employee_id'] ?>" <?= ((string)$selected_employee === (string)$emp['employee_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= htmlspecialchars($emp['department'] ?? 'N/A') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Month</label>
                        <input type="month" name="month_year"
                               value="<?= (int)$year ?>-<?= str_pad((int)$month, 2, '0', STR_PAD_LEFT) ?>"
                               onchange="this.form.submit()" class="input-1">
                    </div>
                </form>
            </div>

            <?php if (empty($employee_list)): ?>
                <div class="employee-card">
                    <p>No employees found for the selected filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($employee_list as $emp): ?>
                    <?php
                        $emp_id = (int)$emp['employee_id'];
                        if ($selected_employee !== '' && (int)$selected_employee !== $emp_id) continue;
                        $attendance = $attendance_data[$emp_id] ?? [];
                        $emp_stats = $stats[$emp_id] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
                    ?>
                    <div class="employee-card">
                        <div class="employee-header">
                            <h3><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></h3>
                            <div class="meta">
                                <?= htmlspecialchars($emp['department'] ?? 'N/A') ?><br>
                                <?= htmlspecialchars($emp['job_title'] ?? '') ?>
                            </div>
                        </div>

                        <?php if (!empty($attendance)): ?>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('M d, Y', strtotime($record['date']))) ?></td>
                                            <td><?= $record['check_in'] ? htmlspecialchars(date('h:i A', strtotime($record['check_in']))) : 'N/A' ?></td>
                                            <td><?= $record['check_out'] ? htmlspecialchars(date('h:i A', strtotime($record['check_out']))) : 'N/A' ?></td>
                                            <td>
                                                <?php 
                                                // Show only Present or Absent (treat late as present)
                                                $st = $record['status'] ?? '';
                                                $display_status = ($st == 'late') ? 'present' : $st;
                                                if ($display_status == 'present' || $display_status == 'absent' || $display_status == 'leave' || $display_status == 'holiday'):
                                                ?>
                                                    <span class="status-badge status-<?= htmlspecialchars($display_status) ?>">
                                                        <?= htmlspecialchars(ucfirst((string)$display_status)) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge status-absent">
                                                        Absent
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #666; padding: 20px;">No attendance records for this period.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

