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
$is_md = RoleHelper::is_managing_director($conn, $employee_id);

$form = Appraisal::get_appraisal_form_details($conn, $form_id);
if (!$form) {
    header("Location: appraisal.php");
    exit();
}

// Self Assessment form: available to everyone except the MD (MD does not view/print self-assessment)
$can_view = !$is_md && (
    ($form['employee_id'] == $employee_id || $form['manager_id'] == $employee_id)
    || ($is_hr && ($form['appraisal_status'] ?? '') === 'completed')
);
if (!$can_view) {
    header("Location: appraisal.php");
    exit();
}

$title = "Self Assessment Form";

$self_areas = Appraisal::get_self_assessment_areas();
$self_metrics_decoded = !empty($form['self_metrics']) ? json_decode($form['self_metrics'], true) : [];
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
        h1 { font-size: 1.35em; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
        .header-row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .header-row .field { flex: 1; min-width: 200px; margin-bottom: 6px; }
        .header-row .label { font-weight: 600; }
        .header-row .value { border-bottom: 1px solid #333; min-height: 18px; padding: 2px 4px; }
        .period-row .value { min-width: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 16px; }
        th, td { border: 1px solid #333; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-weight: 600; }
        td.num { text-align: center; width: 60px; }
        .area-name { font-weight: 600; }
        .area-desc { font-size: 0.9em; color: #444; margin-top: 2px; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .rating-line { margin: 16px 0; }
        .rating-line .label { font-weight: 600; }
        .section-block { margin-bottom: 18px; }
        .section-block .num { font-weight: 600; display: inline-block; min-width: 24px; }
        .section-block .box { border: 1px solid #333; min-height: 52px; padding: 8px; margin-top: 4px; white-space: pre-wrap; }
        .sign-row { margin-top: 32px; display: flex; flex-wrap: wrap; gap: 40px; }
        .sign-row .item .label { font-weight: 600; }
        .sign-row .item .line { border-bottom: 1px solid #333; width: 240px; min-height: 20px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Print</button>
        <span style="margin-left: 12px;">Use &ldquo;Print to PDF&rdquo; or &ldquo;Save as PDF&rdquo; to download.</span>
    </div>

    <h1><?= htmlspecialchars($title) ?></h1>

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
            <?php $i = 0; foreach ($self_areas as $key => $info): $i++; $s = $self_metrics_decoded[$key] ?? []; ?>
                <tr>
                    <td>
                        <span class="area-name"><?= $i ?>. <?= htmlspecialchars($key) ?></span>
                        <div class="area-desc"><?= htmlspecialchars($info['desc']) ?></div>
                    </td>
                    <td class="num"><?= $info['weight'] ?></td>
                    <td class="num"><?= isset($s['rating']) && $s['rating'] !== '' ? (int)$s['rating'] : '&nbsp;' ?></td>
                    <td><?= !empty($s['comments']) ? htmlspecialchars($s['comments']) : '&nbsp;' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL SCORE</td>
                <td class="num">100</td>
                <td class="num">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="rating-line">
        <span class="label">Rating</span>
        <div class="value" style="border-bottom: 1px solid #333; min-height: 20px; margin-top: 4px; width: 200px;">&nbsp;</div>
    </div>

    <div class="section-block">
        <span class="num">1.</span> GOALS AND OBJECTIVES FOR THE QUARTER/YEAR
        <div class="box"><?= htmlspecialchars($form['self_goals'] ?? '') ?></div>
    </div>
    <div class="section-block">
        <span class="num">2.</span> KEY STRENGTHS
        <div class="box"><?= htmlspecialchars($form['self_strengths'] ?? $form['employee_comments'] ?? '') ?></div>
    </div>
    <div class="section-block">
        <span class="num">3.</span> WEAKNESSES/CHALLENGES
        <div class="box"><?= htmlspecialchars($form['self_weaknesses'] ?? '') ?></div>
    </div>
    <div class="section-block">
        <span class="num">4.</span> ACHIEVEMENTS AND ACCOMPLISHMENTS
        <div class="box"><?= htmlspecialchars($form['self_achievements'] ?? '') ?></div>
    </div>
    <div class="section-block">
        <span class="num">5.</span> TRAINING AND DEVELOPMENT RECOMMENDED
        <div class="box"><?= htmlspecialchars($form['self_training'] ?? '') ?></div>
    </div>

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

    <script>
        // if (window.opener) setTimeout(function() { window.print(); }, 500);
    </script>
</body>
</html>
