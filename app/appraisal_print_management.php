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

// Management form: only for managers (appraisee is manager/hr/executive)
$role_lower = strtolower($form['employee_role'] ?? '');
$is_manager_appraisee = in_array($role_lower, ['manager', 'hr', 'hr_manager', 'managing_director']) || !empty($form['executive_member']);
if (!$is_manager_appraisee) {
    header("Location: appraisal_detail.php?id=" . $form_id);
    exit();
}

// Management form areas (same as Self Assessment: 70+10+10+5+5 = 100)
$mgmt_areas = [
    ['num' => 1, 'name' => 'JOB EFFECTIVENESS', 'desc' => 'Achievement of results', 'weight' => 70],
    ['num' => 2, 'name' => 'LEADERSHIP/TEAM EFFECTIVENESS', 'desc' => 'Providing direction and effective management of subordinates and working as a team member', 'weight' => 10],
    ['num' => 3, 'name' => 'CUSTOMER SERVICE', 'desc' => 'Responsiveness to client problems and needs', 'weight' => 10],
    ['num' => 4, 'name' => 'INITIATIVE/INNOVATIVENESS', 'desc' => 'Development of new ideas', 'weight' => 5],
    ['num' => 5, 'name' => 'EFFECTIVE TIME MANAGEMENT', 'desc' => 'Punctuality, prioritization, management of meetings', 'weight' => 5],
];

$title = "Management Performance Evaluation Form";
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
        .rating-row { background: #fafafa; }
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

    <h1>MANAGEMENT PERFORMANCE EVALUATION FORM</h1>

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
            <span class="label">DIVISION:</span>
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
            <?php foreach ($mgmt_areas as $area): ?>
                <tr>
                    <td>
                        <span class="area-name"><?= $area['num'] ?>. <?= htmlspecialchars($area['name']) ?></span>
                        <div class="area-desc"><?= htmlspecialchars($area['desc']) ?></div>
                    </td>
                    <td class="num"><?= $area['weight'] ?></td>
                    <td class="num">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL SCORE</td>
                <td class="num">100</td>
                <td class="num">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <tr class="rating-row">
                <td>Rating</td>
                <td class="num">&nbsp;</td>
                <td class="num">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

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
            <div class="item">
                <span class="label">Date:</span>
                <div class="line">&nbsp;</div>
            </div>
        </div>
    </div>

    <script>
        // if (window.opener) setTimeout(function() { window.print(); }, 500);
    </script>
</body>
</html>
