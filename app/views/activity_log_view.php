<!DOCTYPE html>
<html>
<head>
    <title>Activity Log · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/aesthetic-improvements.css">
    <style>
        /* Shelter Zimbabwe Color Palette */
        :root {
            --shelter-dark-blue: #1e3a5f;
            --shelter-light-blue: #4a90c2;
            --shelter-dark-gray: #4a4a4a;
            --shelter-light-gray: #e8e8e8;
            --shelter-black: #1a1a1a;
            --shelter-white: #ffffff;
        }

        .insights-container {
            padding: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--shelter-black);
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header .subtitle {
            color: var(--shelter-dark-gray);
            font-size: 16px;
            margin: 0;
        }

        .period-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--shelter-light-gray);
            padding-bottom: 0;
        }

        .period-tab {
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--shelter-dark-gray);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: -2px;
        }

        .period-tab:hover {
            color: var(--shelter-dark-blue);
            background: var(--shelter-light-gray);
        }

        .period-tab.active {
            color: var(--shelter-dark-blue);
            border-bottom-color: var(--shelter-dark-blue);
            font-weight: 600;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: var(--shelter-white);
            border: 1px solid var(--shelter-light-gray);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .kpi-card:hover {
            box-shadow: 0 4px 16px rgba(30, 58, 95, 0.1);
            transform: translateY(-3px);
        }

        .kpi-card.alert {
            border-left: 4px solid #dc2626;
        }

        .kpi-card.success {
            border-left: 4px solid #16a34a;
        }

        .kpi-card.info {
            border-left: 4px solid var(--shelter-light-blue);
        }

        .kpi-label {
            font-size: 13px;
            color: var(--shelter-dark-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 42px;
            font-weight: 700;
            color: var(--shelter-black);
            margin: 0;
            line-height: 1;
        }

        .kpi-change {
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .kpi-change.positive {
            color: #16a34a;
        }

        .kpi-change.negative {
            color: #dc2626;
        }

        .insight-section {
            background: var(--shelter-white);
            border: 1px solid var(--shelter-light-gray);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .section-header {
            font-size: 20px;
            font-weight: 600;
            color: var(--shelter-black);
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--shelter-light-gray);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .summary-card {
            background: var(--shelter-light-gray);
            border: 1px solid var(--shelter-light-gray);
            border-radius: 8px;
            padding: 18px;
        }

        .summary-card h4 {
            margin: 0 0 10px 0;
            font-size: 15px;
            color: var(--shelter-black);
            font-weight: 600;
        }

        .summary-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--shelter-dark-blue);
        }

        .summary-card .label {
            font-size: 12px;
            color: var(--shelter-dark-gray);
            margin-top: 5px;
        }

        .daily-activity {
            display: flex;
            align-items: end;
            gap: 8px;
            height: 200px;
            margin-top: 20px;
            padding: 10px 0;
        }

        .day-bar {
            flex: 1;
            background: var(--shelter-light-blue);
            border-radius: 4px 4px 0 0;
            min-height: 10px;
            position: relative;
            transition: background 0.2s;
        }

        .day-bar:hover {
            background: var(--shelter-dark-blue);
        }

        .day-bar .bar-value {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            color: var(--shelter-dark-gray);
            font-weight: 600;
        }

        .day-bar .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            color: var(--shelter-dark-gray);
            white-space: nowrap;
        }

        .action-needed {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .action-needed h3 {
            margin: 0 0 15px 0;
            color: #dc2626;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .action-item {
            background: var(--shelter-white);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #fecaca;
        }

        .action-item .count {
            font-size: 32px;
            font-weight: 700;
            color: #dc2626;
            margin: 5px 0;
        }

        .action-item .desc {
            font-size: 13px;
            color: var(--shelter-dark-gray);
        }

        .insight-box {
            background: var(--shelter-light-gray);
            border-left: 4px solid var(--shelter-light-blue);
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .insight-box h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: var(--shelter-black);
            font-weight: 600;
        }

        .insight-box p {
            margin: 0;
            color: var(--shelter-dark-gray);
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        
        <div class="section-1">
            <div class="insights-container">
                <div class="page-header">
                    <h1>
                        <i class="fa fa-line-chart"></i>
                        Activity Log
                    </h1>
                </div>

                <!-- Period Selector -->
                <div class="period-tabs">
                    <a href="?period=today" class="period-tab <?= $period === 'today' ? 'active' : '' ?>">
                        Today
                    </a>
                    <a href="?period=7days" class="period-tab <?= $period === '7days' ? 'active' : '' ?>">
                        Last 7 Days
                    </a>
                    <a href="?period=30days" class="period-tab <?= $period === '30days' ? 'active' : '' ?>">
                        Last 30 Days
                    </a>
                </div>


                <!-- Activity Feed -->
                <div class="insight-section">
                    <h2 class="section-header">
                        <i class="fa fa-list-alt"></i>
                        Activity Feed
                    </h2>

                    <?php if (empty($recent_activities)): ?>
                        <p style="color: var(--shelter-dark-gray); margin: 0;">No activities found for this period.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="verification-table" style="width:100%; margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($a['created_at']))) ?></td>
                                            <td>
                                                <?= htmlspecialchars(trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?: ('User #' . ($a['user_id'] ?? ''))) ?>
                                            </td>
                                            <td><?= htmlspecialchars($a['role'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($a['department'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($a['activity_type'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($a['description'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Department Insights -->
                <?php if (!empty($top_depts)): ?>
                <div class="insight-section">
                    <h2 class="section-header">
                        <i class="fa fa-building"></i>
                        Department Activity Insights
                    </h2>
                    <div class="summary-grid">
                        <?php foreach ($top_depts as $dept): ?>
                        <div class="summary-card">
                            <h4><?= htmlspecialchars($dept['department'] ?: 'Unknown') ?></h4>
                            <div class="value"><?= $dept['activity_count'] ?></div>
                            <div class="label"><?= $dept['unique_users'] ?> active users</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Activity Type Insights -->
                <?php if (!empty($top_types)): ?>
                <div class="insight-section">
                    <h2 class="section-header">
                        <i class="fa fa-list"></i>
                        Activity Type Distribution
                    </h2>
                    <div class="summary-grid">
                        <?php foreach ($top_types as $type): ?>
                        <div class="summary-card">
                            <h4><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $type['activity_type']))) ?></h4>
                            <div class="value"><?= $type['count'] ?></div>
                            <div class="label">Total occurrences</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>
