<!DOCTYPE html>
<html>

<head>
    <title>Edit Appraisal · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '../' ?>css/style.css">
    <style>
        body {
            background: linear-gradient(120deg, #e0eafc 0%, #cfdef3 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        .appraisal-edit-container {
            max-width: 860px;
            margin: 35px auto 0 auto;
            padding: 0 18px 50px 18px;
        }
        /* Scrollable main content for easy navigation */
        .edit-appraisal-section {
            overflow-y: auto;
            overflow-x: auto;
            height: calc(100vh - 120px);
            max-height: calc(100vh - 120px);
            scrollbar-width: auto;
            scrollbar-color: #2596be #f1f1f1;
            padding-right: 12px;
        }
        .edit-appraisal-section::-webkit-scrollbar { width: 14px; height: 14px; }
        .edit-appraisal-section::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 6px; }
        .edit-appraisal-section::-webkit-scrollbar-thumb { background: #2596be; border-radius: 6px; }
        .edit-appraisal-section::-webkit-scrollbar-thumb:hover { background: #1e7a9e; }

        .appraisal-form {
            background: white;
            padding: 38px 28px 28px 28px;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(47, 171, 234, 0.11), 0 2px 6px rgba(0, 0, 0, 0.07);
            margin-bottom: 30px;
            transition: box-shadow .25s;
            border: 1px solid #e0eafc;
        }

        .appraisal-form:hover {
            box-shadow: 0 10px 40px rgba(47, 171, 234, 0.17), 0 2px 6px rgba(0, 0, 0, 0.09);
        }

        h2,
        h3,
        h4 {
            color: #2176ae;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        h2 i,
        h3 i {
            color: #ffba08;
            margin-right: 5px;
        }

        .form-row {
            display: flex;
            gap: 35px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 500;
            color: #444;
            margin-bottom: 4px;
        }

        .input-1,
        textarea,
        input[type="date"],
        input[type="number"] {
            border: 1.5px solid #d0e3f6;
            border-radius: 7px;
            padding: 8px 11px;
            font-size: 1em;
            transition: border-color .21s, box-shadow .21s;
            background: #f7fbff;
        }

        .input-1:focus,
        textarea:focus,
        input[type="number"]:focus,
        input[type="date"]:focus {
            border-color: #38a3a5;
            box-shadow: 0 0 0 2px #c5f6fa66;
            background: #fff;
            outline: none;
        }

        .metric-section {
            margin-bottom: 33px;
            padding: 24px 20px 18px 20px;
            background: linear-gradient(104deg, #e8f7fa 80%, #f8f9fa 100%);
            border-radius: 12px;
            border-left: 6px solid #38a3a5;
            box-shadow: 0 2px 10px rgba(56, 163, 165, 0.09);
            transition: box-shadow .19s;
        }

        .metric-section:hover {
            box-shadow: 0 6px 18px rgba(56, 163, 165, 0.13);
        }

        .metric-section h4 {
            color: #38a3a5;
            font-size: 1.18em;
            margin-bottom: 3px;
        }

        .metric-section p {
            color: #595959;
            margin-bottom: 8px;
            font-size: 0.98em;
        }

        .rating-input {
            width: 88px;
            font-size: 1.1em;
            padding: 6px 9px;
            text-align: center;
            border-radius: 5px;
            border: 1.5px solid #c6e6e8;
            background: #f7fafb;
            margin: 0 6px;
            transition: border-color .18s;
        }

        .rating-input:focus {
            border-color: #38a3a5;
        }

        .total-score {
            font-size: 1.27em;
            font-weight: bold;
            color: #2176ae;
            margin-top: 26px;
            background: linear-gradient(90deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 8px;
            padding: 10px 0 10px 0;
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

        .back-button-container {
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 22px;
            background: linear-gradient(90deg, #38a3a5 30%, #2176ae 100%);
            color: #fff;
            border-radius: 7px;
            font-size: 1.06em;
            font-weight: 600;
            letter-spacing: 0.2px;
            text-decoration: none;
            margin-right: 13px;
            margin-bottom: 7px;
            border: none;
            box-shadow: 0 2px 10px rgba(56, 163, 165, 0.09);
            transition: background .19s, box-shadow .19s, transform .15s;
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
        @media (max-width: 700px) {

            .appraisal-form,
            .appraisal-edit-container {
                padding: 10px 3vw 15vw 3vw !important;
                max-width: 99vw;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .metric-section,
            .manager-comments-section {
                padding: 13px 6px 11px 10px;
            }
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php" ?>
    <div class="body">
        <?php include "../inc/nav.php" ?>
        <section class="section-1 edit-appraisal-section">
        <div class="appraisal-edit-container">
            <?php if (isset($error)): ?>
                <div class="danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="back-button-container">
                <a href="appraisal.php?tab=active" class="btn">
                    <i class="fa fa-arrow-left"></i> Back to Active Appraisals
                </a>
            </div>

            <h2>
                <i class="fa fa-edit"></i> Edit Appraisal for
                <?= htmlspecialchars($form['employee_first_name'] . ' ' . $form['employee_last_name']) ?>
            </h2>

            <form method="post" class="appraisal-form" id="appraisal-form">
                <input type="hidden" name="form_id" value="<?= $form['form_id'] ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Period Start</label>
                        <input type="date" name="period_start" class="input-1"
                            value="<?= htmlspecialchars($form['period_start']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Period End</label>
                        <input type="date" name="period_end" class="input-1"
                            value="<?= htmlspecialchars($form['period_end']) ?>" readonly>
                    </div>
                </div>

                <h3>Performance Metrics</h3>

                <?php
                $metrics_list = [
                    'PLANNING & ORGANISING' => ['max' => 50],
                    'OUTPUT' => ['max' => 10],
                    'CUSTOMER SERVICE' => ['max' => 10],
                    'INITIATIVE/INNOVATIVENESS' => ['max' => 10],
                    'DEPENDABILITY/EFFORT' => ['max' => 10],
                    'STRESS TOLERANCE' => ['max' => 5],
                    'CO-OPERATION' => ['max' => 5],
                ];
                ?>

                <?php foreach ($metrics_list as $metric => $info): ?>
                    <?php $data = $metrics_data[$metric] ?? []; ?>
                    <div class="metric-section">
                        <h4><?= htmlspecialchars($metric) ?></h4>
                        <p><?= htmlspecialchars($data['description'] ?? '') ?></p>

                        <div class="form-group">
                            <label>
                                Rating (0 - <?= $info['max'] ?>):
                                <input
                                    type="number"
                                    class="rating-input"
                                    name="metrics[<?= htmlspecialchars($metric) ?>][rating]"
                                    min="0"
                                    max="<?= $info['max'] ?>"
                                    step="1"
                                    value="<?= htmlspecialchars($data['rating'] ?? 0) ?>"
                                    required
                                    oninput="calculateTotalScore();">
                                / <?= $info['max'] ?>
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Comments:</label>
                            <textarea name="metrics[<?= htmlspecialchars($metric) ?>][comments]"
                                class="input-1" rows="3"><?= htmlspecialchars($data['comments'] ?? '') ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="total-score" id="totalScoreDisplay">
                    Total Score: <span id="totalScore">0</span> / 100
                </div>

                <div class="manager-comments-section">
                    <h3>Comments by The Head</h3>
                    <div class="form-group">
                        <label>1. KEY STRENGTHS</label>
                        <textarea name="manager_strengths" class="input-1" rows="3"><?= htmlspecialchars($form['manager_strengths'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>2. AREAS OF IMPROVEMENT</label>
                        <textarea name="manager_improvement" class="input-1" rows="3"><?= htmlspecialchars($form['manager_improvement'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>3. TRAINING/DEVELOPMENT RECOMMENDED</label>
                        <textarea name="manager_training" class="input-1" rows="3"><?= htmlspecialchars($form['manager_training'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" name="update_appraisal" class="btn">
                    <i class="fa fa-save"></i> Update Appraisal
                </button>
                <a href="appraisal_detail.php?id=<?= $form['form_id'] ?>" class="btn">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </form>
        </div>
        </section>
    </div>
    <script>
        function calculateTotalScore() {
            var total = 0;
            var maxTotal = 100;
            var fields = document.querySelectorAll('.rating-input');
            fields.forEach(function(input) {
                var value = parseInt(input.value) || 0;
                var max = parseInt(input.max);
                // Clamp to the allowed range
                if (value > max) value = max;
                if (value < 0) value = 0;
                total += value;
            });
            document.getElementById('totalScore').textContent = total;
        }
        // Initial calculation on page load
        window.onload = calculateTotalScore;
    </script>
</body>

</html>