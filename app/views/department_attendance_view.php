<!DOCTYPE html>
<html>
<head>
    <title>Department Attendance · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .attendance-container {
            padding: 20px;
            width: 100%;
            max-width: 100%;
            overflow-y: auto;
            max-height: calc(100vh - 150px);
        }
        .filter-section {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .employee-selector {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .employee-selector select {
            min-width: 500px;
            width: auto;
            max-width: 100%;
            padding: 10px 12px;
            font-size: 14px;
        }
        .employee-selector select option {
            white-space: nowrap;
            padding: 8px;
            font-size: 14px;
        }
        .employee-card {
            background: white;
            padding: 25px;
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
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2596be;
        }
        .employee-header h3 {
            margin: 0;
            color: #2596be;
            font-size: 22px;
            font-weight: 600;
        }
        .employee-header .job-title {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }
        .attendance-table-container {
            overflow-x: auto;
            overflow-y: visible;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        .attendance-table thead {
            background: #2596be;
            color: white;
        }
        .attendance-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        .attendance-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }
        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-present { background: #d4edda; color: #155724; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-leave { background: #d1ecf1; color: #0c5460; }
        .status-holiday { background: #e2e3e5; color: #383d41; }
        .no-records {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        /* Scrollbar styling */
        .attendance-container::-webkit-scrollbar {
            width: 10px;
        }
        .attendance-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        .attendance-container::-webkit-scrollbar-thumb {
            background: #2596be;
            border-radius: 5px;
        }
        .attendance-container::-webkit-scrollbar-thumb:hover {
            background: #1e7a9e;
        }
        .attendance-table-container::-webkit-scrollbar {
            height: 8px;
        }
        .attendance-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }
        .attendance-table-container::-webkit-scrollbar-thumb {
            background: #2596be;
            border-radius: 5px;
        }
        .attendance-table-container::-webkit-scrollbar-thumb:hover {
            background: #1e7a9e;
        }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <section class="section-1">
            <h2 class="title"><i class="fa fa-calendar-check-o"></i> <?= htmlspecialchars($department) ?> Department Attendance</h2>
            
            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="employee-selector">
                    <div class="form-group" style="flex: 1;">
                        <label><strong>Select Employee</strong></label>
                        <select name="employee_id" class="input-1" onchange="this.form.submit()" style="min-width: 500px; padding: 10px 12px; font-size: 14px;">
                            <option value="">All Employees</option>
                            <?php foreach ($employee_list as $emp): ?>
                                <option value="<?= $emp['employee_id'] ?>" <?= $selected_employee == $emp['employee_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> - <?= htmlspecialchars($emp['job_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Month & Year</strong></label>
                        <input type="month" name="month_year" value="<?= $year ?>-<?= str_pad($month, 2, '0', STR_PAD_LEFT) ?>" 
                               onchange="this.form.submit()" class="input-1">
                    </div>
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                </form>
            </div>
            
            <div class="attendance-container">
                <?php if (empty($employee_list)): ?>
                    <div class="no-records">
                        <p><i class="fa fa-info-circle"></i> No employees found in <?= htmlspecialchars($department) ?> department.</p>
                    </div>
                <?php else: ?>
                    <!-- Employee Attendance Cards -->
                    <?php foreach ($employee_list as $emp): ?>
                        <?php 
                        $emp_id = $emp['employee_id'];
                        
                        // If employee is selected, only show their card
                        if ($selected_employee && $selected_employee != $emp_id) {
                            continue;
                        }
                        
                        $attendance = $attendance_data[$emp_id] ?? [];
                        $emp_stats = $stats[$emp_id] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
                        ?>
                        <div class="employee-card">
                            <div class="employee-header">
                                <div>
                                    <h3><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></h3>
                                    <div class="job-title"><?= htmlspecialchars($emp['job_title']) ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($attendance)): ?>
                                <div class="attendance-table-container">
                                    <table class="attendance-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Check In Time</th>
                                                <th>Check Out Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Sort attendance by date (newest first)
                                            usort($attendance, function($a, $b) {
                                                return strtotime($b['date']) - strtotime($a['date']);
                                            });
                                            ?>
                                            <?php foreach ($attendance as $record): ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                                    <td><?= $record['check_in'] ? date('h:i A', strtotime($record['check_in'])) : '<span style="color: #999;">N/A</span>' ?></td>
                                                    <td><?= $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : '<span style="color: #999;">N/A</span>' ?></td>
                                                    <td>
                                                        <?php 
                                                        // Show only Present or Absent (treat late as present)
                                                        $display_status = ($record['status'] == 'late') ? 'present' : $record['status'];
                                                        if ($display_status == 'present' || $display_status == 'absent' || $display_status == 'leave' || $display_status == 'holiday'):
                                                        ?>
                                                            <span class="status-badge status-<?= $display_status ?>">
                                                                <?= ucfirst($display_status) ?>
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
                                </div>
                            <?php else: ?>
                                <div class="no-records">
                                    <p><i class="fa fa-calendar-times-o"></i> No attendance records for <?= date('F Y', strtotime("$year-$month-01")) ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
