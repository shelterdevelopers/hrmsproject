<!DOCTYPE html>
<html>
<head>
    <title>Activity Reports · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-container {
            padding: 20px;
        }
        .date-filter {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .date-filter form {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        .date-filter .form-group {
            flex: 1;
        }
        .date-filter label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .date-filter input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-container canvas {
            max-height: 400px;
        }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <div class="dashboard-container">
            <h1><i class="fa fa-bar-chart"></i> Activity Reports</h1>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET" action="">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">Generate Report</button>
                        <a href="?date_from=<?= date('Y-m-d', strtotime('-30 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn" style="background: #6c757d;">Last 30 Days</a>
                    </div>
                </form>
            </div>
            
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Activities</h3>
                    <p style="font-size: 32px; margin: 10px 0; color: #007bff;"><?= number_format($total_activities) ?></p>
                    <small>From <?= date('M d, Y', strtotime($date_from)) ?> to <?= date('M d, Y', strtotime($date_to)) ?></small>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="chart-container">
                <h3>Activity by Department</h3>
                <canvas id="deptChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Activity by Type</h3>
                <canvas id="typeChart"></canvas>
            </div>
            
            <!-- Detailed Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Activity by Department</h3>
                    <table style="width: 100%; margin-top: 15px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="text-align: left; padding: 8px;">Department</th>
                                <th style="text-align: right; padding: 8px;">Activities</th>
                                <th style="text-align: right; padding: 8px;">Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_by_dept as $stat): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px;"><?= htmlspecialchars($stat['department'] ?: 'Unknown') ?></td>
                                    <td style="text-align: right; padding: 8px;"><?= number_format($stat['activity_count']) ?></td>
                                    <td style="text-align: right; padding: 8px;"><?= number_format($stat['unique_users']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="stat-card">
                    <h3>Activity by Type</h3>
                    <table style="width: 100%; margin-top: 15px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="text-align: left; padding: 8px;">Type</th>
                                <th style="text-align: right; padding: 8px;">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_by_type as $stat): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $stat['activity_type']))) ?></td>
                                    <td style="text-align: right; padding: 8px;"><?= number_format($stat['count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Department Chart
        const deptCtx = document.getElementById('deptChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($s) { return "'" . addslashes($s['department'] ?: 'Unknown') . "'"; }, $stats_by_dept)) ?>],
                datasets: [{
                    label: 'Activities',
                    data: [<?= implode(',', array_column($stats_by_dept, 'activity_count')) ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'pie',
            data: {
                labels: [<?= implode(',', array_map(function($s) { return "'" . addslashes(ucfirst(str_replace('_', ' ', $s['activity_type']))) . "'"; }, $stats_by_type)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($stats_by_type, 'count')) ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    </script>
</body>
</html>
