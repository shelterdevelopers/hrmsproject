<?php
session_start();
require_once "../DB_connection.php";
require_once "Model/Appraisal.php";
require_once "Model/RoleHelper.php";

if (!isset($_SESSION['employee_id']) || !isset($_GET['id'])) {
    header("Location: ../login.php");
    exit();
}

$form_id = (int)$_GET['id'];
$employee_id = $_SESSION['employee_id'];
$is_hr = RoleHelper::is_hr($conn, $employee_id);

$form = Appraisal::get_appraisal_form_details($conn, $form_id);
if (!$form) {
    header("Location: appraisal.php");
    exit();
}

$can_view = ($form['employee_id'] == $employee_id || $form['manager_id'] == $employee_id)
    || ($is_hr && ($form['appraisal_status'] ?? '') === 'completed');
if (!$can_view) {
    header("Location: appraisal.php");
    exit();
}

// Build combined_metrics for display (same order as form)
$combined_metrics = [];
$default_metrics = Appraisal::get_metrics();
if (!empty($form['metrics'])) {
    $metrics_data = json_decode($form['metrics'], true);
    if (is_array($metrics_data)) {
        foreach ($default_metrics as $category => $template) {
            $combined_metrics[$category] = [
                'manager_score' => $metrics_data[$category]['rating'] ?? null,
                'manager_comments' => $metrics_data[$category]['comments'] ?? null,
            ];
        }
    }
}
if (empty($combined_metrics)) {
    $combined_metrics = array_map(function($m) { return ['manager_score' => null, 'manager_comments' => null]; }, $default_metrics);
}

// Areas per your layout: 1–5 single, 6 = Stress Tolerance + Co-operation (5+5)
$eval_areas = [
    ['num' => 1, 'name' => 'PLANNING & ORGANISING', 'desc' => 'Ability to meet deadlines, monitor tasks and activities, set goals and priorities', 'weight' => 50, 'key' => 'PLANNING & ORGANISING'],
    ['num' => 2, 'name' => 'OUTPUT', 'desc' => "Volume of work relative to employee's experience. Ability to distribute effort over various tasks.", 'weight' => 10, 'key' => 'OUTPUT'],
    ['num' => 3, 'name' => 'CUSTOMER SERVICE', 'desc' => 'Responsiveness to client problems and needs, effectiveness in conveying information to both internal & external customer', 'weight' => 10, 'key' => 'CUSTOMER SERVICE'],
    ['num' => 4, 'name' => 'INITIATIVE/INNOVATIVENESS', 'desc' => 'Development of new ideas/voluntarily submits constructive ideas to improve efficiency', 'weight' => 10, 'key' => 'INITIATIVE/INNOVATIVENESS'],
    ['num' => 5, 'name' => 'DEPENDABILITY/EFFORT', 'desc' => "Compare performance related to the amount of supervision required. How reliable is the incumbent when called upon to do a job. What level of interest in the job is exhibited OR How hard the employee tries to get the job done", 'weight' => 10, 'key' => 'DEPENDABILITY/EFFORT'],
    ['num' => 6, 'name' => 'STRESS TOLERANCE/CO-OPERATION', 'desc' => "How well the employee copes/works under pressure. Level of co-operation with Supervisor and colleagues", 'weight1' => 5, 'weight2' => 5, 'key1' => 'STRESS TOLERANCE', 'key2' => 'CO-OPERATION'],
];
$total_score = 0;

$title = "Non-Managerial Performance Evaluation Form";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; color: #222; font-size: 14px; }
        .no-print { margin-bottom: 20px; }
        @media print {
            body { margin: 0; padding: 12px; font-size: 12px; }
            .no-print { display: none !important; }
        }
        h1 { font-size: 1.25em; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.03em; }
        .header-row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .header-row .field { flex: 1; min-width: 200px; margin-bottom: 6px; }
        .header-row .label { font-weight: 600; }
        .header-row .value { border-bottom: 1px solid #333; min-height: 18px; padding: 2px 4px; }
        .period-row .value { min-width: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 16px; }
        th, td { border: 1px solid #333; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-weight: 600; }
        td.num { text-align: center; width: 50px; }
        .area-name { font-weight: 600; }
        .area-desc { font-size: 0.9em; color: #444; margin-top: 2px; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .rating-line { margin: 16px 0; display: flex; flex-wrap: wrap; gap: 24px; align-items: center; }
        .rating-line .label { font-weight: 600; }
        .rating-line .value { border-bottom: 1px solid #333; min-height: 20px; min-width: 80px; }
        .hod-section { margin-top: 20px; }
        .hod-section .heading { font-weight: 600; margin-bottom: 10px; }
        .section-block { margin-bottom: 18px; }
        .section-block .num { font-weight: 600; display: inline-block; min-width: 24px; }
        .section-block .box { border: 1px solid #333; min-height: 52px; padding: 8px; margin-top: 4px; white-space: pre-wrap; }
        .hod-title { font-weight: 700; margin-top: 24px; margin-bottom: 10px; }
        .sign-row { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 40px; }
        .sign-row .item .label { font-weight: 600; }
        .sign-row .item .line { border-bottom: 1px solid #333; width: 240px; min-height: 20px; margin-top: 4px; }
        .staff-comments { margin-top: 24px; }
        .staff-comments .box { border: 1px solid #333; min-height: 60px; padding: 8px; margin-top: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Print</button>
        <span style="margin-left: 12px;">Use &ldquo;Print to PDF&rdquo; or &ldquo;Save as PDF&rdquo; to download.</span>
    </div>

    <h1>NON-MANAGERIAL PERFORMANCE EVALUATION FORM</h1>

    <div class="header-row">
        <div class="field">
            <span class="label">SURNAME:</span>
            <div class="value"><?= htmlspecialchars($form['employee_last_name']) ?></div>
        </div>
        <div class="field">
            <span class="label">FIRST NAMES:</span>
            <div class="value"><?= htmlspecialchars($form['employee_first_name']) ?></div>
        </div>
    </div>
    <div class="header-row">
        <div class="field">
            <span class="label">DESIGNATION:</span>
            <div class="value"><?= htmlspecialchars($form['job_title']) ?></div>
        </div>
        <div class="field">
            <span class="label">DEPARTMENT:</span>
            <div class="value"><?= htmlspecialchars($form['department']) ?></div>
        </div>
    </div>
    <div class="header-row period-row">
        <div class="field">
            <span class="label">PERIOD OF REVIEW</span>
            <div class="value"><?= date('d M Y', strtotime($form['period_start'])) ?> – <?= date('d M Y', strtotime($form['period_end'])) ?></div>
        </div>
    </div>
    <div class="header-row period-row">
        <div class="field">
            <span class="label">DATE OF PREVIOUS ASSESSMENT</span>
            <div class="value"><?= !empty($form['previous_assessment_date']) ? date('d M Y', strtotime($form['previous_assessment_date'])) : 'N/A' ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>AREAS OF APPRAISALS</th>
                <th class="num">WEIGHT</th>
                <th class="num">RATINGS</th>
                <th>COMMENTS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eval_areas as $area): ?>
                <?php
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
                    <td>
                        <span class="area-name"><?= $area['num'] ?>. <?= htmlspecialchars($area['name']) ?></span>
                        <div class="area-desc"><?= htmlspecialchars($area['desc']) ?></div>
                    </td>
                    <td class="num">
                        <?php if (isset($area['weight'])): ?>
                            <?= $area['weight'] ?>
                        <?php else: ?>
                            <?= $area['weight1'] ?><br><?= $area['weight2'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= ($score !== '' && $score !== ' / ') ? htmlspecialchars($score) : '&nbsp;' ?></td>
                    <td><?= $comments !== '' ? nl2br(htmlspecialchars($comments)) : '&nbsp;' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL SCORE</td>
                <td class="num">100</td>
                <td class="num"><?= $total_score ?></td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="rating-line">
        <span class="label">Rating</span>
        <div class="value">&nbsp;</div>
        <span class="label">By HOD</span>
        <div class="value">&nbsp;</div>
    </div>

    <div class="hod-section">
        <div class="heading">Comments by Head of Department:-</div>
        <div class="section-block">
            <span class="num">1.</span> KEY STRENGTHS
            <div class="box"><?= htmlspecialchars($form['manager_strengths'] ?? '') ?></div>
        </div>
        <div class="section-block">
            <span class="num">2.</span> AREAS OF IMPROVEMENT
            <div class="box"><?= htmlspecialchars($form['manager_improvement'] ?? '') ?></div>
        </div>
        <div class="section-block">
            <span class="num">3.</span> TRAINING/DEVELOPMENT RECOMMENDED
            <div class="box"><?= htmlspecialchars($form['manager_training'] ?? '') ?></div>
        </div>
    </div>

    <div class="hod-title">HEAD OF DEPARTMENT</div>
    <div class="sign-row">
        <div class="item">
            <span class="label">Name and Signature:</span>
            <div class="line">&nbsp;</div>
        </div>
        <div class="item">
            <span class="label">Date:</span>
            <div class="line">&nbsp;</div>
        </div>
    </div>
    <p style="font-size: 0.9em; margin-top: 2px; color: #444;">H.O.D: <?= htmlspecialchars(trim(($form['manager_first_name'] ?? '') . ' ' . ($form['manager_last_name'] ?? ''))) ?></p>

    <div class="staff-comments" style="margin-top: 28px;">
        <div class="heading">Comments by Member of Staff</div>
        <div class="box"><?= htmlspecialchars($form['employee_comments'] ?? '') ?></div>
        <div class="sign-row" style="margin-top: 16px;">
            <div class="item">
                <span class="label">Name and Signature:</span>
                <div class="line">&nbsp;</div>
            </div>
        </div>
    </div>

    <script>
        // if (window.opener) setTimeout(function() { window.print(); }, 500);
    </script>
</body>
</html>
