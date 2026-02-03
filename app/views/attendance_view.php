<!DOCTYPE html>
<html>
<head>
    <title>Attendance · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/attendance.css">
    <link rel="stylesheet" href="../css/aesthetic-improvements.css">
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <section class="section-1">
            <div class="attendance-container">
                <h2><i class="fa fa-calendar-check-o"></i> Attendance</h2>
                
                <?php if (isset($success)): ?>
                <div class="success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                <div class="danger"><?= $error ?></div>
                <?php endif; ?>
                
                <div class="attendance-controls">
                    <div class="leave-info">
                        <span class="leave-balance">
                            <i class="fa fa-calendar"></i> Leave Days Remaining: <?= $leave_balance ?>
                        </span>
                    </div>
                    
                    <div class="clock-actions">
                        <?php if (!isset($_SESSION['checked_in'])): ?>
                        <form method="post">
                            <button type="submit" name="check_in" class="btn btn-primary">
                                <i class="fa fa-sign-in"></i> Check In
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="post">
                            <button type="submit" name="check_out" class="btn btn-danger">
                                <i class="fa fa-sign-out"></i> Check Out
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <form method="get" class="month-selector">
                        <select name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <select name="year">
                            <?php for ($y = date('Y')-1; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn">View</button>
                    </form>
                </div>
                
                
                
                <!-- Attendance Records -->
                <div class="attendance-records">
                    <h3><i class="fa fa-list"></i> Attendance History</h3>
                    <table>
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
                                <td><?= date('d M Y', strtotime($record['date'])) ?></td>
                                <td><?= $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-' ?></td>
                                <td><?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-' ?></td>
                                <td>
                                    <?php 
                                    // Show only Present or Absent (treat late as present)
                                    $display_status = ($record['status'] == 'late') ? 'present' : $record['status'];
                                    if ($display_status == 'present' || $display_status == 'absent' || $display_status == 'leave' || $display_status == 'holiday'):
                                    ?>
                                        <span class="status-badge <?= $display_status ?>">
                                            <?= ucfirst($display_status) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge absent">
                                            Absent
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</body>
</html>