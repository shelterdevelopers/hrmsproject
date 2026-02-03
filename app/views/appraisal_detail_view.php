<!DOCTYPE html>
<html>

<head>
    <title>Appraisal Details · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #f9fafc 0%, #e0eafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .appraisal-detail-section {
            overflow-y: auto;
            overflow-x: auto;
            height: calc(100vh - 120px);
            max-height: calc(100vh - 120px);
            scrollbar-width: auto;
            scrollbar-color: #2596be #f1f1f1;
            padding-right: 12px;
        }
        .appraisal-detail-section::-webkit-scrollbar { width: 14px; height: 14px; }
        .appraisal-detail-section::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 6px; }
        .appraisal-detail-section::-webkit-scrollbar-thumb { background: #2596be; border-radius: 6px; }
        .appraisal-detail-section::-webkit-scrollbar-thumb:hover { background: #1e7a9e; }
        .appraisal-detail-container {
            max-width: 900px;
            margin: 32px auto 0 auto;
            padding: 0 18px 50px 18px;
        }

        .appraisal-header {
            background: linear-gradient(90deg, #e3ffe7 0%, #d9e7ff 100%);
            padding: 28px 28px 18px 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 6px 28px rgba(33, 118, 174, 0.09);
            border: 1.5px solid #cbe9f6;
        }

        .employee-details {
            display: flex;
            flex-wrap: wrap;
            gap: 36px;
            margin-bottom: 14px;
            font-size: 1.03em;
        }

        .employee-details div {
            color: #24577a;
            font-weight: 500;
            padding: 3px 0;
        }

        h2,
        h3,
        h4 {
            color: #2176ae;
            letter-spacing: 0.5px;
        }

        h3 i,
        h2 i {
            color: #ffba08;
            margin-right: 8px;
        }

        .appraisal-metrics {
            margin-bottom: 30px;
        }

        .metric-category {
            margin-bottom: 26px;
            border: 1.5px solid #bfeaf5;
            border-radius: 12px;
            padding: 22px 24px 18px 20px;
            background: linear-gradient(100deg, #f1fcff 90%, #e3f7fa 100%);
            box-shadow: 0 1px 10px rgba(33, 118, 174, 0.07);
            transition: box-shadow .17s;
        }

        .metric-category:hover {
            box-shadow: 0 6px 22px rgba(33, 118, 174, 0.17);
        }

        .metric-category h4 {
            color: #38a3a5;
            font-size: 1.15em;
            margin-bottom: 3px;
        }

        .metric-category>div {
            font-size: 0.98em;
            color: #555;
            margin-bottom: 10px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0 5px 0;
        }

        .metric-score {
            flex: 1;
            text-align: center;
            font-size: 1.08em;
            color: #2176ae;
        }

        .metric-comments {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #bfeaf5;
            color: #444;
            font-size: 0.97em;
            background: #f7fafd;
            border-radius: 0 0 7px 7px;
            transition: background .15s;
        }

        .metric-comments strong {
            color: #38a3a5;
            font-weight: 600;
            margin-right: 4px;
        }

        .total-score {
            font-size: 1.22em;
            font-weight: bold;
            color: #2176ae;
            margin-top: 16px;
            background: linear-gradient(90deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 8px;
            padding: 10px 0;
            text-align: center;
            box-shadow: 0 1px 7px rgba(252, 182, 159, 0.09);
            letter-spacing: 0.2px;
        }

        .manager-comments-section {
            border: 1.5px solid #bfeaf5;
            background: linear-gradient(104deg, #f1fcff 80%, #e3f7fa 100%);
            border-radius: 10px;
            padding: 25px 20px 17px 20px;
            margin-top: 37px;
            margin-bottom: 16px;
            box-shadow: 0 1px 8px rgba(56, 163, 165, 0.05);
        }

        .manager-comments-section h3 {
            color: #38a3a5;
            margin-bottom: 11px;
            font-size: 1.16em;
        }

        .manager-comments-section .form-group {
            margin-bottom: 15px;
            color: #293b4d;
            font-size: 1em;
        }

        .manager-comments-section strong {
            color: #26a69a;
        }

        .feedback-section {
            background: linear-gradient(90deg, #e3f6fc 0%, #f9f9ff 100%);
            padding: 24px 22px 16px 22px;
            border-radius: 10px;
            margin-top: 34px;
            box-shadow: 0 2px 13px rgba(56, 163, 165, 0.09);
            border: 1.5px solid #bfeaf5;
        }

        .feedback-section h3 {
            color: #2176ae;
            margin-bottom: 10px;
        }

        .feedback-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1f5ec;
        }

        .feedback-item:last-child {
            border-bottom: none;
        }

        .manager-actions,
        .employee-actions {
            margin: 20px 0;
            padding: 18px 15px 13px 15px;
            background: linear-gradient(90deg, #f8f9fa 80%, #e7f2fa 100%);
            border-radius: 7px;
            border: 1px solid #d6f2fc;
            box-shadow: 0 1px 6px rgba(33, 118, 174, 0.08);
        }

        .back-button-container {
            margin-bottom: 24px;
        }

        .btn {
            display: inline-block;
            padding: 13px 22px;
            background: linear-gradient(90deg, #38a3a5 30%, #2176ae 100%);
            color: #fff;
            border-radius: 8px;
            font-size: 1.06em;
            font-weight: 600;
            letter-spacing: 0.2px;
            text-decoration: none;
            border: none;
            margin-right: 13px;
            margin-bottom: 7px;
            box-shadow: 0 2px 10px rgba(56, 163, 165, 0.09);
            transition: background .19s, box-shadow .19s, transform .12s;
            cursor: pointer;
        }

        .btn i {
            margin-right: 7px;
        }

        .btn:hover,
        .btn:focus {
            background: linear-gradient(90deg, #38a3a5 10%, #ffffffff 100%);
            color: #222;
            box-shadow: 0 5px 16px rgba(56, 163, 165, 0.17);
            transform: translateY(-2px) scale(1.03);
            text-shadow: 0 1px 2px #fff8;
        }

        /* Responsive styles */
        @media (max-width: 800px) {

            .appraisal-detail-container,
            .appraisal-header {
                padding: 10px 3vw 15vw 3vw !important;
                max-width: 99vw;
            }

            .employee-details {
                flex-direction: column;
                gap: 8px;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .metric-category,
            .manager-comments-section,
            .feedback-section {
                padding: 12px 6px 11px 10px;
            }
        }

        /* HR form view – looks like the official form */
        .hr-form-view { max-width: 800px; margin: 0 auto; padding: 20px; font-size: 14px; color: #222; }
        .hr-form-view .form-title { font-size: 1.25em; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 2px solid #2596be; padding-bottom: 8px; }
        .hr-form-view .form-header-row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .hr-form-view .form-header-row .field { flex: 1; min-width: 200px; margin-bottom: 6px; }
        .hr-form-view .form-header-row .label { font-weight: 600; }
        .hr-form-view .form-header-row .value { border-bottom: 1px solid #333; min-height: 18px; padding: 2px 4px; }
        .hr-form-view .form-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .hr-form-view .form-table th, .hr-form-view .form-table td { border: 1px solid #333; padding: 8px 6px; text-align: left; vertical-align: top; }
        .hr-form-view .form-table th { background: #f5f5f5; font-weight: 600; }
        .hr-form-view .form-table td.num { text-align: center; width: 50px; }
        .hr-form-view .area-name { font-weight: 600; }
        .hr-form-view .area-desc { font-size: 0.9em; color: #444; margin-top: 2px; }
        .hr-form-view .form-total-row { font-weight: bold; background: #f0f0f0; }
        .hr-form-view .form-hod-section { margin-top: 20px; }
        .hr-form-view .form-hod-section .heading { font-weight: 600; margin-bottom: 10px; }
        .hr-form-view .form-section-block { margin-bottom: 18px; }
        .hr-form-view .form-section-block .num { font-weight: 600; display: inline-block; min-width: 24px; }
        .hr-form-view .form-section-block .box { border: 1px solid #333; min-height: 52px; padding: 8px; margin-top: 4px; white-space: pre-wrap; }
        .hr-form-view .form-hod-title { font-weight: 700; margin-top: 24px; margin-bottom: 10px; }
        .hr-form-view .form-sign-row { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 40px; }
        .hr-form-view .form-sign-row .item .label { font-weight: 600; }
        .hr-form-view .form-sign-row .item .line { border-bottom: 1px solid #333; width: 240px; min-height: 20px; margin-top: 4px; }
        .hr-form-view .form-staff-comments { margin-top: 24px; }
        .hr-form-view .form-staff-comments .box { border: 1px solid #333; min-height: 60px; padding: 8px; margin-top: 4px; white-space: pre-wrap; }
        .hr-form-view .form-print-actions { margin-bottom: 20px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }

        /* Print: hide app chrome, show only form (for HR form view) */
        @media print {
            .header, .header * { display: none !important; }
            body .body > *:not(.section-1) { display: none !important; }
            body * { visibility: hidden; }
            .section-1, .section-1 *, .hr-form-view, .hr-form-view * { visibility: visible; }
            .section-1 { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; padding: 12px; margin: 0; background: #fff; }
            .no-print, .no-print * { display: none !important; visibility: hidden !important; }
            .appraisal-detail-container { max-width: 100%; }
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        <section class="section-1 appraisal-detail-section">
        <div class="appraisal-detail-container">
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php
            $hr_form_view = (isset($is_hr) && $is_hr && ($form['appraisal_status'] ?? '') === 'completed');
            if ($hr_form_view) {
                $emp_role_lower = strtolower($form['employee_role'] ?? '');
                $is_manager_appraisee = in_array($emp_role_lower, ['manager', 'hr', 'hr_manager', 'managing_director']) || !empty($form['executive_member']);
            }
            ?>

            <!-- Back Button (hidden when printing) -->
            <div class="back-button-container no-print">
                <a href="appraisal.php?tab=<?= (($form['appraisal_status'] ?? '') === 'completed') ? 'completed' : 'active' ?>" class="btn">
                    <i class="fa fa-arrow-left"></i> Back to Appraisals
                </a>
            </div>

            <?php if ($hr_form_view): ?>
                <!-- HR: Print both forms for filing (Self Assessment + Evaluation form) -->
                <div class="no-print form-print-actions" style="margin-bottom: 20px; padding: 16px 20px; background: #e8f4f8; border: 2px solid #2596be; border-radius: 8px;">
                    <p style="margin: 0 0 12px 0; font-weight: 600; color: #0c5460;"><i class="fa fa-print"></i> Print for filing</p>
                    <p style="margin: 0 0 12px 0; color: #555;">Print both forms (Self Assessment and <?= $is_manager_appraisee ? 'Management Performance Evaluation' : 'Non-Managerial Performance Evaluation' ?>), then sign and file.</p>
                    <button type="button" class="btn" style="background:#2596be;" onclick="window.open('appraisal_print_self.php?id=<?= (int)$form_id ?>'); window.open('<?= $is_manager_appraisee ? 'appraisal_print_management.php' : 'appraisal_print_evaluation.php' ?>?id=<?= (int)$form_id ?>');">
                        <i class="fa fa-print"></i> Print both forms for filing
                    </button>
                    <span style="margin-left: 12px; color: #666;">or print individually:</span>
                    <a href="appraisal_print_self.php?id=<?= (int)$form_id ?>" target="_blank" class="btn" style="margin-left: 8px;"><i class="fa fa-print"></i> Self Assessment</a>
                    <?php if ($is_manager_appraisee): ?>
                        <a href="appraisal_print_management.php?id=<?= (int)$form_id ?>" target="_blank" class="btn"><i class="fa fa-print"></i> Management form</a>
                    <?php else: ?>
                        <a href="appraisal_print_evaluation.php?id=<?= (int)$form_id ?>" target="_blank" class="btn"><i class="fa fa-print"></i> Non-Managerial form</a>
                    <?php endif; ?>
                    <span style="margin-left: 12px; color: #666;">or</span>
                    <button type="button" class="btn" onclick="window.print();" style="margin-left: 8px;"><i class="fa fa-print"></i> Print this page (form view)</button>
                </div>
                <div class="hr-form-view" id="hr-form-print-area">
                    <?php if ($is_manager_appraisee): ?>
                        <!-- Management Performance Evaluation Form -->
                        <h1 class="form-title">MANAGEMENT PERFORMANCE EVALUATION FORM</h1>
                        <div class="form-header-row">
                            <div class="field"><span class="label">SURNAME:</span><div class="value"><?= htmlspecialchars($form['employee_last_name']) ?></div></div>
                            <div class="field"><span class="label">FIRST NAMES:</span><div class="value"><?= htmlspecialchars($form['employee_first_name']) ?></div></div>
                        </div>
                        <div class="form-header-row">
                            <div class="field"><span class="label">DESIGNATION:</span><div class="value"><?= htmlspecialchars($form['job_title']) ?></div></div>
                            <div class="field"><span class="label">DIVISION:</span><div class="value"><?= htmlspecialchars($form['department']) ?></div></div>
                        </div>
                        <div class="form-header-row"><div class="field"><span class="label">PERIOD OF REVIEW</span><div class="value"><?= date('d M Y', strtotime($form['period_start'])) ?> – <?= date('d M Y', strtotime($form['period_end'])) ?></div></div></div>
                        <?php
                        $mgmt_areas = [
                            ['num' => 1, 'name' => 'JOB EFFECTIVENESS', 'desc' => 'Achievement of results', 'weight' => 70],
                            ['num' => 2, 'name' => 'LEADERSHIP/TEAM EFFECTIVENESS', 'desc' => 'Providing direction and effective management of subordinates and working as a team member', 'weight' => 10],
                            ['num' => 3, 'name' => 'CUSTOMER SERVICE', 'desc' => 'Responsiveness to client problems and needs', 'weight' => 10],
                            ['num' => 4, 'name' => 'INITIATIVE/INNOVATIVENESS', 'desc' => 'Development of new ideas', 'weight' => 5],
                            ['num' => 5, 'name' => 'EFFECTIVE TIME MANAGEMENT', 'desc' => 'Punctuality, prioritization, management of meetings', 'weight' => 5],
                        ];
                        ?>
                        <table class="form-table">
                            <thead><tr><th>AREAS OF APPRAISALS</th><th class="num">WEIGHT</th><th class="num">RATINGS</th><th>COMMENTS</th></tr></thead>
                            <tbody>
                                <?php foreach ($mgmt_areas as $a): ?><tr><td><span class="area-name"><?= $a['num'] ?>. <?= htmlspecialchars($a['name']) ?></span><div class="area-desc"><?= htmlspecialchars($a['desc']) ?></div></td><td class="num"><?= $a['weight'] ?></td><td class="num">&nbsp;</td><td>&nbsp;</td></tr><?php endforeach; ?>
                                <tr class="form-total-row"><td>TOTAL SCORE</td><td class="num">100</td><td class="num">&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td>Rating</td><td class="num">&nbsp;</td><td class="num">&nbsp;</td><td>&nbsp;</td></tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Non-Managerial Performance Evaluation Form -->
                        <h1 class="form-title">NON-MANAGERIAL PERFORMANCE EVALUATION FORM</h1>
                        <div class="form-header-row">
                            <div class="field"><span class="label">SURNAME:</span><div class="value"><?= htmlspecialchars($form['employee_last_name']) ?></div></div>
                            <div class="field"><span class="label">FIRST NAMES:</span><div class="value"><?= htmlspecialchars($form['employee_first_name']) ?></div></div>
                        </div>
                        <div class="form-header-row">
                            <div class="field"><span class="label">DESIGNATION:</span><div class="value"><?= htmlspecialchars($form['job_title']) ?></div></div>
                            <div class="field"><span class="label">DEPARTMENT:</span><div class="value"><?= htmlspecialchars($form['department']) ?></div></div>
                        </div>
                        <div class="form-header-row"><div class="field"><span class="label">PERIOD OF REVIEW</span><div class="value"><?= date('d M Y', strtotime($form['period_start'])) ?> – <?= date('d M Y', strtotime($form['period_end'])) ?></div></div></div>
                        <div class="form-header-row"><div class="field"><span class="label">DATE OF PREVIOUS ASSESSMENT</span><div class="value"><?= !empty($form['previous_assessment_date']) ? date('d M Y', strtotime($form['previous_assessment_date'])) : 'N/A' ?></div></div></div>
                        <?php
                        $eval_areas = [
                            ['num' => 1, 'name' => 'PLANNING & ORGANISING', 'desc' => 'Ability to meet deadlines, monitor tasks and activities, set goals and priorities', 'weight' => 50, 'key' => 'PLANNING & ORGANISING'],
                            ['num' => 2, 'name' => 'OUTPUT', 'desc' => "Volume of work relative to employee's experience. Ability to distribute effort over various tasks.", 'weight' => 10, 'key' => 'OUTPUT'],
                            ['num' => 3, 'name' => 'CUSTOMER SERVICE', 'desc' => 'Responsiveness to client problems and needs, effectiveness in conveying information to both internal & external customer', 'weight' => 10, 'key' => 'CUSTOMER SERVICE'],
                            ['num' => 4, 'name' => 'INITIATIVE/INNOVATIVENESS', 'desc' => 'Development of new ideas/voluntarily submits constructive ideas to improve efficiency', 'weight' => 10, 'key' => 'INITIATIVE/INNOVATIVENESS'],
                            ['num' => 5, 'name' => 'DEPENDABILITY/EFFORT', 'desc' => "Compare performance related to the amount of supervision required. How reliable is the incumbent when called upon to do a job. What level of interest in the job is exhibited OR How hard the employee tries to get the job done", 'weight' => 10, 'key' => 'DEPENDABILITY/EFFORT'],
                            ['num' => 6, 'name' => 'STRESS TOLERANCE/CO-OPERATION', 'desc' => "How well the employee copes/works under pressure. Level of co-operation with Supervisor and colleagues", 'weight1' => 5, 'weight2' => 5, 'key1' => 'STRESS TOLERANCE', 'key2' => 'CO-OPERATION'],
                        ];
                        $total_score = 0;
                        ?>
                        <table class="form-table">
                            <thead><tr><th>AREAS OF APPRAISALS</th><th class="num">WEIGHT</th><th class="num">RATINGS</th><th>COMMENTS</th></tr></thead>
                            <tbody>
                                <?php foreach ($eval_areas as $area):
                                    if (isset($area['key'])) {
                                        $m = $combined_metrics[$area['key']] ?? ['manager_score' => null, 'manager_comments' => null];
                                        $score = isset($m['manager_score']) && is_numeric($m['manager_score']) ? (int)$m['manager_score'] : '';
                                        $comments = !empty($m['manager_comments']) ? $m['manager_comments'] : '';
                                        $total_score += is_numeric($score) ? $score : 0;
                                    } else {
                                        $m1 = $combined_metrics[$area['key1']] ?? ['manager_score' => null, 'manager_comments' => null];
                                        $m2 = $combined_metrics[$area['key2']] ?? ['manager_score' => null, 'manager_comments' => null];
                                        $s1 = isset($m1['manager_score']) && is_numeric($m1['manager_score']) ? (int)$m1['manager_score'] : '';
                                        $s2 = isset($m2['manager_score']) && is_numeric($m2['manager_score']) ? (int)$m2['manager_score'] : '';
                                        $score = trim($s1 . ' / ' . $s2);
                                        $comments = trim(($m1['manager_comments'] ?? '') . "\n" . ($m2['manager_comments'] ?? ''));
                                        $total_score += (is_numeric($s1) ? $s1 : 0) + (is_numeric($s2) ? $s2 : 0);
                                    }
                                ?>
                                <tr>
                                    <td><span class="area-name"><?= $area['num'] ?>. <?= htmlspecialchars($area['name']) ?></span><div class="area-desc"><?= htmlspecialchars($area['desc']) ?></div></td>
                                    <td class="num"><?= isset($area['weight']) ? $area['weight'] : $area['weight1'] . '<br>' . $area['weight2'] ?></td>
                                    <td class="num"><?= ($score !== '' && $score !== ' / ') ? htmlspecialchars($score) : '&nbsp;' ?></td>
                                    <td><?= $comments !== '' ? nl2br(htmlspecialchars($comments)) : '&nbsp;' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="form-total-row"><td>TOTAL SCORE</td><td class="num">100</td><td class="num"><?= $total_score ?></td><td>&nbsp;</td></tr>
                            </tbody>
                        </table>
                        <div class="form-hod-section" style="margin-top: 16px;"><span class="label">Rating</span> <span class="label">By HOD</span></div>
                    <?php endif; ?>
                    <!-- Comments by Head of Department (same for both forms) -->
                    <div class="form-hod-section">
                        <div class="heading">Comments by Head of Department:-</div>
                        <div class="form-section-block"><span class="num">1.</span> KEY STRENGTHS<div class="box"><?= htmlspecialchars($form['manager_strengths'] ?? '') ?></div></div>
                        <div class="form-section-block"><span class="num">2.</span> AREAS OF IMPROVEMENT<div class="box"><?= htmlspecialchars($form['manager_improvement'] ?? '') ?></div></div>
                        <div class="form-section-block"><span class="num">3.</span> TRAINING/DEVELOPMENT RECOMMENDED<div class="box"><?= htmlspecialchars($form['manager_training'] ?? '') ?></div></div>
                    </div>
                    <div class="form-hod-title">HEAD OF DEPARTMENT</div>
                    <div class="form-sign-row">
                        <div class="item"><span class="label">Name and Signature:</span><div class="line">&nbsp;</div></div>
                        <div class="item"><span class="label">Date:</span><div class="line">&nbsp;</div></div>
                    </div>
                    <p style="font-size: 0.9em; margin-top: 2px; color: #444;">H.O.D: <?= htmlspecialchars(trim(($form['manager_first_name'] ?? '') . ' ' . ($form['manager_last_name'] ?? ''))) ?></p>
                    <div class="form-staff-comments" style="margin-top: 28px;">
                        <div class="heading">Comments by Member of Staff</div>
                        <div class="box"><?= htmlspecialchars($form['employee_comments'] ?? '') ?></div>
                        <div class="form-sign-row" style="margin-top: 16px;">
                            <div class="item"><span class="label">Name and Signature:</span><div class="line">&nbsp;</div></div>
                            <div class="item"><span class="label">Date:</span><div class="line">&nbsp;</div></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
            <!-- Normal view (employee / manager / or HR viewing non-completed) -->
            <?php
            $self_areas = Appraisal::get_self_assessment_areas();
            $self_metrics_decoded = !empty($form['self_metrics']) ? json_decode($form['self_metrics'], true) : [];
            $status_lower = strtolower(trim($form['appraisal_status'] ?? ''));
            $can_self_assess = $is_employee && in_array($status_lower, ['draft', 'shared', 'employee_review']);
            ?>
            <?php if ($can_self_assess): ?>
                <!-- Self Assessment FIRST so employees see it immediately -->
                <div class="manager-comments-section" style="margin-top: 0; margin-bottom: 24px; border: 2px solid #2596be; background: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);">
                    <h3 style="color: #2176ae; margin-bottom: 16px;"><i class="fa fa-user"></i> Self Assessment – Fill out your self-assessment below</h3>
                    <p style="margin-bottom: 16px; color: #555;">Rate yourself on each area and complete the sections. Click <strong>Save Self Assessment</strong> when done. You can update this until you acknowledge the appraisal.</p>
                    <form method="post">
                        <table class="appraisal-table" style="margin-bottom: 20px; width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 10px; border: 1px solid #bfeaf5;">Area</th>
                                    <th style="text-align: center; width: 70px; padding: 10px; border: 1px solid #bfeaf5;">Weight</th>
                                    <th style="text-align: center; width: 80px; padding: 10px; border: 1px solid #bfeaf5;">Your rating</th>
                                    <th style="text-align: left; padding: 10px; border: 1px solid #bfeaf5;">Your comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($self_areas as $key => $info):
                                    $s = $self_metrics_decoded[$key] ?? ['rating' => '', 'comments' => ''];
                                ?>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #bfeaf5;">
                                            <strong><?= htmlspecialchars($key) ?></strong><br>
                                            <span style="font-size: 0.9em; color: #555;"><?= htmlspecialchars($info['desc']) ?></span>
                                        </td>
                                        <td style="text-align: center; padding: 10px; border: 1px solid #bfeaf5;"><?= $info['weight'] ?></td>
                                        <td style="text-align: center; padding: 8px; border: 1px solid #bfeaf5;">
                                            <input type="number" class="self-rating-input" name="self_metrics[<?= htmlspecialchars($key) ?>][rating]" min="0" max="<?= $info['weight'] ?>" value="<?= htmlspecialchars($s['rating'] ?? '') ?>" data-max="<?= (int)$info['weight'] ?>" style="width: 60px; padding: 6px;">
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #bfeaf5;">
                                            <textarea name="self_metrics[<?= htmlspecialchars($key) ?>][comments]" rows="2" style="width: 100%; padding: 6px;" placeholder="Comments"><?= htmlspecialchars($s['comments'] ?? '') ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #e0f2fe; font-weight: bold;">
                                    <td style="padding: 10px; border: 1px solid #bfeaf5;">TOTAL SCORE</td>
                                    <td style="text-align: center; padding: 10px; border: 1px solid #bfeaf5;">100</td>
                                    <td id="self-total-cell" colspan="2" style="padding: 10px; border: 1px solid #bfeaf5; text-align: center;">— / 100</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label><strong>1. Goals and objectives for the quarter/year</strong></label>
                            <textarea name="self_goals" class="input-1" rows="3" style="width: 100%;"><?= htmlspecialchars($form['self_goals'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label><strong>2. Key strengths</strong></label>
                            <textarea name="self_strengths" class="input-1" rows="3" style="width: 100%;"><?= htmlspecialchars($form['self_strengths'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label><strong>3. Weaknesses / challenges</strong></label>
                            <textarea name="self_weaknesses" class="input-1" rows="3" style="width: 100%;"><?= htmlspecialchars($form['self_weaknesses'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label><strong>4. Achievements and accomplishments</strong></label>
                            <textarea name="self_achievements" class="input-1" rows="3" style="width: 100%;"><?= htmlspecialchars($form['self_achievements'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 18px;">
                            <label><strong>5. Training and development recommended</strong></label>
                            <textarea name="self_training" class="input-1" rows="3" style="width: 100%;"><?= htmlspecialchars($form['self_training'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="save_self_assessment" class="btn">
                            <i class="fa fa-save"></i> Save Self Assessment
                        </button>
                    </form>
                <script>
                (function() {
                    var inputs = document.querySelectorAll('.self-rating-input');
                    var totalCell = document.getElementById('self-total-cell');
                    if (!totalCell || !inputs.length) return;
                    function updateTotal() {
                        var sum = 0;
                        inputs.forEach(function(inp) {
                            var v = parseInt(inp.value, 10);
                            var max = parseInt(inp.getAttribute('data-max') || inp.max, 10) || 0;
                            if (!isNaN(v) && v >= 0) sum += Math.min(v, max);
                        });
                        totalCell.textContent = sum + ' / 100';
                    }
                    inputs.forEach(function(inp) {
                        inp.addEventListener('input', updateTotal);
                        inp.addEventListener('change', updateTotal);
                    });
                    updateTotal();
                })();
                </script>
                </div>
            <?php endif; ?>

            <div class="appraisal-header">

                <div class="employee-details" style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 20px;">
                    <div>
                        <strong>SURNAME:</strong> <?= htmlspecialchars($form['employee_last_name']) ?>
                    </div>
                    <div>
                        <strong>FIRST NAMES:</strong> <?= htmlspecialchars($form['employee_first_name']) ?>
                    </div>
                    <div>
                        <strong>DESIGNATION:</strong> <?= htmlspecialchars($form['job_title']) ?>
                    </div>
                    <div>
                        <strong>DEPARTMENT:</strong> <?= htmlspecialchars($form['department']) ?>
                    </div>
                    <div>
                        <strong>PERIOD OF REVIEW:</strong>
                        <?= date('M d, Y', strtotime($form['period_start'])) ?> -
                        <?= date('M d, Y', strtotime($form['period_end'])) ?>
                    </div>
                    <div>
                        <strong>DATE OF PREVIOUS ASSESSMENT:</strong>
                        <?= !empty($form['previous_assessment_date']) ? date('M d, Y', strtotime($form['previous_assessment_date'])) : 'N/A' ?>
                    </div>
                </div>
            </div>

            <?php if ($is_employee && !$can_self_assess && (!empty($form['self_strengths']) || !empty($form['self_goals']) || !empty($form['self_metrics']))): ?>
                <!-- Employee viewing their own appraisal: show read-only self-assessment if already filled -->
                <div class="manager-comments-section" style="margin-top: 20px; margin-bottom: 24px; border: 1px solid #bfeaf5;">
                    <h3 style="color: #2176ae;"><i class="fa fa-user"></i> Your Self Assessment</h3>
                    <?php if (!empty($self_metrics_decoded)): ?>
                        <p style="margin-bottom: 10px;"><strong>Your ratings:</strong></p>
                        <ul style="margin-bottom: 14px;">
                            <?php foreach ($self_areas as $key => $info): $s = $self_metrics_decoded[$key] ?? []; ?>
                                <li><?= htmlspecialchars($key) ?>: <?= (int)($s['rating'] ?? 0) ?> / <?= $info['weight'] ?><?= !empty($s['comments']) ? ' – ' . htmlspecialchars($s['comments']) : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($form['self_goals'])): ?><p><strong>1. Goals and objectives:</strong><br><?= nl2br(htmlspecialchars($form['self_goals'])) ?></p><?php endif; ?>
                    <?php if (!empty($form['self_strengths'])): ?><p><strong>2. Key strengths:</strong><br><?= nl2br(htmlspecialchars($form['self_strengths'])) ?></p><?php endif; ?>
                    <?php if (!empty($form['self_weaknesses'])): ?><p><strong>3. Weaknesses / challenges:</strong><br><?= nl2br(htmlspecialchars($form['self_weaknesses'])) ?></p><?php endif; ?>
                    <?php if (!empty($form['self_achievements'])): ?><p><strong>4. Achievements:</strong><br><?= nl2br(htmlspecialchars($form['self_achievements'])) ?></p><?php endif; ?>
                    <?php if (!empty($form['self_training'])): ?><p><strong>5. Training recommended:</strong><br><?= nl2br(htmlspecialchars($form['self_training'])) ?></p><?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ...rest of your code above... -->

            <div class="appraisal-metrics">
                <h3><i class="fa fa-star"></i> Performance Metrics</h3>
                <?php
                $metrics_list = [
                    'PLANNING & ORGANISING' => 50,
                    'OUTPUT' => 10,
                    'CUSTOMER SERVICE' => 10,
                    'INITIATIVE/INNOVATIVENESS' => 10,
                    'DEPENDABILITY/EFFORT' => 10,
                    'STRESS TOLERANCE' => 5,
                    'CO-OPERATION' => 5,
                ];
                $total_score = 0;
                ?>
                <?php if (!empty($combined_metrics)): ?>
                    <?php foreach ($metrics_list as $category => $max_score): ?>
                        <?php $metric = $combined_metrics[$category] ?? ['manager_score' => null, 'manager_comments' => null]; ?>
                        <div class="metric-category" style="margin-bottom: 18px;">
                            <h4 style="margin-bottom: 0;"><?= htmlspecialchars($category) ?></h4>
                            <div style="font-size: 0.96em;color:#555;">
                                <?= htmlspecialchars(Appraisal::get_metrics()[$category]['description'] ?? '') ?>
                            </div>
                            <div class="metric-row" style="margin-top: 6px;">
                                <span style="display:inline-block; width: 170px;">H.O.D's Rating</span>
                                <span class="metric-score" style="font-weight:bold;">
                                    <?php
                                    if (isset($metric['manager_score']) && is_numeric($metric['manager_score'])) {
                                        $total_score += $metric['manager_score'];
                                        echo htmlspecialchars($metric['manager_score']) . " / $max_score";
                                    } else {
                                        echo "Not scored";
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($metric['manager_comments'])): ?>
                                <div class="metric-comments" style="margin-top:4px;">
                                    <strong>H.O.D's Comments:</strong>
                                    <?= htmlspecialchars($metric['manager_comments']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="total-score" style="font-size:1.2em;font-weight:bold;color:#2596be;margin-top:8px;">
                        Total Score: <?= $total_score ?> / 100
                    </div>
                <?php else: ?>
                    <p>No performance metrics have been added to this appraisal yet.</p>
                <?php endif; ?>
            </div>

            <!-- Manager Overall Comments Section -->
            <?php if (!empty($form['manager_strengths']) || !empty($form['manager_improvement']) || !empty($form['manager_training'])): ?>
                <div class="manager-comments-section" style="border:1px solid #e1f5ec;background:#f8f9fa;border-radius:8px;padding:18px;margin-top:30px;margin-bottom:10px;">
                    <h3>Comments by The H.O.D</h3>
                    <?php if (!empty($form['manager_strengths'])): ?>
                        <div class="form-group"><strong>1. KEY STRENGTHS</strong><br>
                            <?= nl2br(htmlspecialchars($form['manager_strengths'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($form['manager_improvement'])): ?>
                        <div class="form-group"><strong>2. AREAS OF IMPROVEMENT</strong><br>
                            <?= nl2br(htmlspecialchars($form['manager_improvement'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($form['manager_training'])): ?>
                        <div class="form-group"><strong>3. TRAINING/DEVELOPMENT RECOMMENDED</strong><br>
                            <?= nl2br(htmlspecialchars($form['manager_training'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- rest of your file unchanged -->

            <!-- HR: Download & Print (only in normal view; when HR form view, Print is at top) -->
            <?php if (isset($is_hr) && $is_hr && ($form['appraisal_status'] ?? '') === 'completed' && !$hr_form_view): ?>
                <?php
                $emp_role_lower = strtolower($form['employee_role'] ?? '');
                $is_manager_appraisee = in_array($emp_role_lower, ['manager', 'hr', 'hr_manager', 'managing_director']) || !empty($form['executive_member']);
                ?>
                <div class="manager-actions" style="margin-top: 28px;">
                    <h3 style="margin-bottom: 14px; color: #2176ae;"><i class="fa fa-download"></i> Download &amp; Print (for filing)</h3>
                    <p style="margin-bottom: 14px; color: #555;">Print the forms, sign, and file. Use your browser&rsquo;s &ldquo;Print to PDF&rdquo; or &ldquo;Save as PDF&rdquo; to download.</p>
                    <a href="appraisal_print_self.php?id=<?= (int)$form_id ?>" target="_blank" class="btn" style="margin-right: 10px;">
                        <i class="fa fa-print"></i> Print Self Assessment Form
                    </a>
                    <?php if ($is_manager_appraisee): ?>
                        <a href="appraisal_print_management.php?id=<?= (int)$form_id ?>" target="_blank" class="btn">
                            <i class="fa fa-print"></i> Print Management Performance Evaluation Form
                        </a>
                    <?php else: ?>
                        <a href="appraisal_print_evaluation.php?id=<?= (int)$form_id ?>" target="_blank" class="btn">
                            <i class="fa fa-print"></i> Print Non-Managerial Performance Evaluation Form
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Manager Update Section -->
            <!-- Manager Actions -->
            <?php if ($is_manager): ?>
                <div class="manager-actions">

                    <?php if ($form['appraisal_status'] == 'draft'): ?>
                        <form method="post">
                            <button type="submit" name="share_with_employee" class="btn btn-info">
                                <i class="fa fa-share"></i> Share with Employee
                            </button>
                        </form>
                    <?php elseif ($form['appraisal_status'] == 'shared' && $form['is_acknowledged']): ?>
                        <form method="post">
                            <button type="submit" name="finalize_appraisal" class="btn btn-success"
                                onclick="return confirm('Finalize this appraisal? This cannot be undone.')">
                                <i class="fa fa-lock"></i> Finalize Appraisal
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Employee Feedback Section -->
            <?php if (in_array($form['appraisal_status'], ['shared', 'completed'])): ?> <div class="feedback-section">
                    <h3><i class="fa fa-comments"></i>Employee Feedback</h3>
                    <!-- Existing feedback display -->

                    <?php if ($is_employee && !$form['is_acknowledged']): ?>
                        <form method="post">
                            <div class="form-group">
                                <label>Your Comments</label>
                                <textarea name="employee_comments" class="input-1" rows="5" required></textarea>
                            </div>


                            <button type="submit" name="acknowledge" class="btn btn-success"
                                onclick="return confirm('Are you sure you want to acknowledge and submit this appraisal?')">
                                <i class="fa fa-check-circle"></i> Submit and Acknowledge
                            </button>
                        </form>
                    <?php elseif ($form['is_acknowledged']): ?>
                        <div class="metric-comments">
                            <strong>Employee Comments:</strong> <?= htmlspecialchars($form['employee_comments']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php endif; ?>
            <!-- end normal view (else from hr_form_view) -->
        </div>
        </section>
    </div>
</body>

</html>