<?php
// View receives: $success, $error, $role, $employee_id
// $my_leave_applications, $my_loan_applications
// $pending_approvals, $responded_applications
?>
<!DOCTYPE html>
<html>

<head>
    <title>Applications · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/learning.css">
    <link rel="stylesheet" href="../css/aesthetic-improvements.css">
    <style>
        /* Keep all your previous styles for tabs, tables, statuses etc. */
        /* ... (Your existing CSS from the previous step) ... */
        .applications-container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            width: 100%;
        }
        
        /* Ensure section-1 has scrollbars */
        .section-1 {
            overflow-x: scroll !important;
            overflow-y: scroll !important;
            height: calc(100vh - 150px) !important;
            max-height: calc(100vh - 150px) !important;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .input-1,
        textarea.input-1,
        select.input-1 {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        /* Enlarge select dropdown for status */
        select.input-1[name="status"] {
            min-width: 200px;
            padding: 12px 15px;
            font-size: 15px;
            height: auto;
        }
        
        select.input-1[name="status"] option {
            padding: 10px;
            white-space: nowrap;
        }

        textarea.input-1 {
            min-height: 60px;
            resize: vertical;
        }

        .btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 1em;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85em;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .main-table th {
            background-color: #1884ffff;
            font-weight: bold;
        }

        .table-hover th {
            background-color: #1884ffff;
            font-weight: bold;
        }

        .main-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .success {
            padding: 15px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .danger {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .tab-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
        }

        .tab-buttons {
            display: flex;
            background-color: #e9ecef;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 18px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: 700;
            border-right: 1px solid #ddd;
            color: #007bff;
            flex-grow: 1;
            text-align: center;
        }

        .tab-button:last-child {
            border-right: none;
        }

        .tab-button.active {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }

        .tab-button:hover:not(.active) {
            background-color: #d6d8db;
        }

        .tab-content {
            display: none;
            padding: 25px;
            animation: fadeIn 0.5s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .leave-days-info {
            margin: 5px 0 15px;
            padding: 8px 12px;
            background: #f0f0f0;
            border-radius: 4px;
            display: inline-block;
            margin-right: 15px;
            font-size: 0.95em;
        }

        .leave-days-info strong {
            color: #333;
        }

        .loan-summary-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .loan-summary-row:hover {
            background-color: #e9f5ff;
        }

        .loan-details-row {
            background-color: #f8f9fa;
        }

        .loan-details-container {
            padding: 20px;
            border-top: 2px solid #007bff;
        }

        .loan-details h4,
        .payment-history h5 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #0056b3;
        }

        .loan-details .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.95em;
        }

        .loan-details .detail-item:last-of-type {
            border-bottom: none;
        }

        .loan-details .detail-item span:first-child {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }

        .payment-history table {
            width: 100%;
            font-size: 0.9em;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .payment-history th,
        .payment-history td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }

        .payment-history th {
            background-color: #007bff;
            /* Changed to blue */
            color: white;
            /* Added for text visibility */
            font-weight: 600;
        }

        .payment-form {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }

        .payment-form .form-group {
            margin-bottom: 10px;
        }

        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-denied,
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        .pending-applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .pending-application-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .pending-application-card .app-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pending-application-card h4 {
            margin: 0;
            font-size: 1.1em;
        }

        .pending-application-card .app-type {
            font-size: 0.9em;
            color: #6c757d;
            text-align: right;
        }

        .pending-application-card .app-details {
            padding: 15px;
            font-size: 0.95em;
            line-height: 1.5;
        }

        .pending-application-card .app-details p {
            margin: 0 0 8px 0;
        }

        .pending-application-card .app-details i {
            margin-right: 8px;
            color: #6c757d;
            width: 15px;
            text-align: center;
        }

        .pending-application-card .response-form {
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
        }

        .no-records {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
        }

        .no-records i {
            font-size: 2em;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "../inc/header.php"; ?>
    <div class="body">
        <?php include "../inc/nav.php"; ?>

        <div class="applications-container section-1">
            <h2><i class="fa fa-file-text-o"></i> Applications</h2>

            <?php if (!empty($success)): // Use !empty for cleaner check 
            ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="tab-buttons">
                <button class="tab-button active" data-tab="leaves">My Leaves</button>
                <button class="tab-button" data-tab="loans">My Loans</button>
                <?php
                // Show manager tabs if there's data for them - always show these tabs after My Leaves/My Loans
                // Check if user has approval capabilities (manager, HR, or MD)
                // These variables should be available from applications.php
                $user_is_manager = isset($is_manager) ? $is_manager : false;
                $user_is_hr = isset($is_hr) ? $is_hr : false;
                $user_is_md = isset($is_managing_director) ? $is_managing_director : false;
                
                $has_pending = !empty($pending_approvals);
                $has_responded = !empty($responded_applications);
                // For department managers (not Finance), filter to only leave applications
                if (isset($show_loans) && !$show_loans) {
                    $has_pending = !empty(array_filter($pending_approvals, fn($a) => $a['type'] === 'leave'));
                    $has_responded = !empty(array_filter($responded_applications, fn($a) => $a['type'] === 'leave'));
                }
                // Always show approval tabs for managers, HR, and MD (they have approval capabilities)
                $show_approval_tabs = ($has_pending || $has_responded || $user_is_manager || $user_is_hr || $user_is_md);
                if ($show_approval_tabs):
                ?>
                    <button class="tab-button" data-tab="approvals">Pending Application Approvals</button>
                    <button class="tab-button" data-tab="responded">Responded By Me</button>
                <?php endif; ?>
            </div>

            <div class="tab-container">
                <!-- Persistent Headers -->
                <div class="tab-headers" style="margin-bottom: 20px; border-bottom: 2px solid #2596be; padding-bottom: 10px;">
                    <h2 id="leaves-header" style="display: block; margin: 0; color: #2596be; font-size: 24px;"><i class="fa fa-calendar"></i> My Leaves</h2>
                    <h2 id="loans-header" style="display: none; margin: 0; color: #2596be; font-size: 24px;"><i class="fa fa-money"></i> My Loans</h2>
                    <h2 id="approvals-header" style="display: none; margin: 0; color: #2596be; font-size: 24px;"><i class="fa fa-clock-o"></i> Pending Application Approvals</h2>
                    <h2 id="responded-header" style="display: none; margin: 0; color: #2596be; font-size: 24px;"><i class="fa fa-check-circle"></i> Responded By Me</h2>
                </div>

                <div id="leaves-tab" class="tab-content active">
                    <h3>Apply for Leave</h3>
                    <form method="post" class="form-1">
                        <div class="form-group">
                            <label>Leave Type*</label>
                            <select name="leave_type" id="leave-type" class="input-1" required>
                                <option value="normal" selected>Normal Leave</option>
                                <option value="special">Special Case Leave (12 days)</option>
                                <option value="sick">Sick Leave (90 days)</option>
                                <option value="maternity">Maternity Leave (98 days)</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date*</label>
                                <input type="date" name="start_date" id="start-date" class="input-1" required>
                            </div>
                            <div class="form-group">
                                <label>End Date*</label>
                                <input type="date" name="end_date" id="end-date" class="input-1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Reason*</label>
                            <textarea name="reason" class="input-1" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <div class="leave-days-info">
                                <strong>Available:</strong> <span id="available-days">-</span>
                            </div>
                            <div class="leave-days-info">
                                <strong>Requesting:</strong> <span id="days-requested">0</span> days
                            </div>
                        </div>
                        <button type="submit" name="submit_leave" class="btn"><i class="fa fa-paper-plane"></i> Submit Leave Application</button>
                    </form>

                    <hr style="margin: 30px 0;">

                    <h3>My Leave History</h3>
                    <?php if (!empty($my_leave_applications)): ?>
                        <div class="table-responsive">
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Dates</th>
                                        <th>Days</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Manager Comment</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_leave_applications as $app): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucfirst($app['leave_type'])) ?></td>
                                            <td><?= htmlspecialchars($app['start_date']) ?> to <?= htmlspecialchars($app['end_date']) ?></td>
                                            <td><?= (int) $app['days_requested'] ?></td>
                                            <td><?= nl2br(htmlspecialchars($app['reason'])) ?></td>
                                            <td class="status-<?= htmlspecialchars($app['status']) ?>"><?= htmlspecialchars(ucfirst($app['status'])) ?></td>
                                            <td><?= $app['manager_comment'] ? nl2br(htmlspecialchars($app['manager_comment'])) : '-' ?></td>
                                            <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
                                            <td>
                                                <?php if (($app['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="delete_application.php" style="display:inline;" onsubmit="return confirm('Cancel this leave application?');">
                                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                        <button type="submit" class="btn btn-sm" style="background:#dc3545;">Cancel</button>
                                                    </form>
                                                <?php elseif (isset($is_hr) && $is_hr || isset($is_managing_director) && $is_managing_director): ?>
                                                    <form method="post" action="delete_application.php" style="display:inline;" onsubmit="return confirm('Delete this application from records?');">
                                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                        <button type="submit" class="btn btn-sm" style="background:#6c757d;">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-records"><i class="fa fa-info-circle"></i><br> No leave applications found.</div>
                    <?php endif; ?>
                </div>

                <?php if ($show_loans ?? true): ?>
                <div id="loans-tab" class="tab-content">
                    <h3>Apply for Loan</h3>
                    <form method="post" class="form-1">
                        <div class="form-group">
                            <label>Loan Amount ($)*</label>
                            <input type="number" name="amount" class="input-1" step="0.01" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Repayment Plan (in months)*</label>
                            <input type="number" name="repayment_plan" class="input-1" step="1" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Reason*</label>
                            <textarea name="loan_reason" class="input-1" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="submit_loan" class="btn"><i class="fa fa-paper-plane"></i> Submit Loan Application</button>
                    </form>

                    <hr style="margin: 30px 0;">

                    <h3>My Loan History</h3>
                    <?php if (!empty($my_loan_applications)): ?>
                        <div class="table-responsive">
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Outstanding</th>
                                        <th>Plan (Months)</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_loan_applications as $idx => $app): ?>
                                        <tr class="loan-summary-row" data-loan-id="<?= $app['id'] ?>" data-details-id="loan-details-<?= $idx ?>">
                                            <td>$<?= number_format($app['amount'], 2) ?></td>
                                            <td>$<?= number_format($app['outstanding_balance'] ?? $app['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($app['repayment_plan']) ?></td>
                                            <td class="status-<?= htmlspecialchars($app['status']) ?>"><?= htmlspecialchars(ucfirst($app['status'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm view-loan-details"><i class="fa fa-eye"></i> Details</button>
                                                <?php if (($app['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="delete_application.php" style="display:inline;" onsubmit="return confirm('Cancel this loan application?');">
                                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                        <button type="submit" class="btn btn-sm" style="background:#dc3545;">Cancel</button>
                                                    </form>
                                                <?php elseif (isset($is_hr) && $is_hr || isset($is_managing_director) && $is_managing_director): ?>
                                                    <form method="post" action="delete_application.php" style="display:inline;" onsubmit="return confirm('Delete this loan from records?');">
                                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                        <button type="submit" class="btn btn-sm" style="background:#6c757d;">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr class="loan-details-row" style="display: none;">
                                            <td colspan="6">
                                                <div id="loan-details-<?= $idx ?>" class="loan-details-container">
                                                    <h4><i class="fa fa-info-circle"></i> Loan Details & Repayment</h4>
                                                    <div class="loan-details">
                                                        <div class="detail-item"><span>Original Amount:</span> <span>$<?= number_format($app['amount'], 2) ?></span></div>
                                                        <div class="detail-item"><span>Disbursed Amount:</span> <span>$<?= number_format($app['disbursed_amount'] ?? 0, 2) ?></span></div>
                                                        <div class="detail-item"><span>Outstanding Balance:</span> <span>$<?= number_format($app['outstanding_balance'] ?? $app['amount'], 2) ?></span></div>
                                                        <div class="detail-item"><span>Repayment Plan:</span> <span><?= htmlspecialchars($app['repayment_plan']) ?> Months</span></div>
                                                        <div class="detail-item"><span>Reason:</span> <span><?= nl2br(htmlspecialchars($app['reason'])) ?></span></div>
                                                        <div class="detail-item"><span>Manager Comment:</span> <span><?= $app['manager_comment'] ? nl2br(htmlspecialchars($app['manager_comment'])) : '-' ?></span></div>
                                                        <?php if ($app['status'] == 'approved'): $installment_due = Attendance::calculate_due_amount($app); ?>
                                                            <div class="detail-item"><span>Next Payment Due:</span> <span><?= isset($app['next_payment_date']) ? date('M d, Y', strtotime($app['next_payment_date'])) : 'N/A' ?></span></div>
                                                            <div class="detail-item"><span>Monthly Installment:</span> <span>$<?= number_format($installment_due, 2) ?></span></div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($app['status'] == 'approved' && ($app['outstanding_balance'] ?? $app['amount']) > 0): ?>
                                                        <form method="post" action="record_repayment.php" class="payment-form">
                                                            <h5>Record Payment</h5>
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <input type="hidden" name="amount_paid" value="<?= $installment_due ?>">
                                                            <div class="form-group">
                                                                <label>Payment Method</label>
                                                                <select name="payment_method" class="input-1" required>
                                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                                    <option value="Cash">Cash</option>
                                                                    <option value="Mobile Money">Mobile Money</option>
                                                                    <option value="Payroll Deduction">Payroll Deduction</option>
                                                                    <option value="Other">Other</option>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn"><i class="fa fa-check"></i> Mark Payment of $<?= number_format($installment_due, 2) ?> as Paid</button>
                                                        </form>
                                                    <?php elseif ($app['status'] == 'approved' && ($app['outstanding_balance'] ?? $app['amount']) <= 0): ?>
                                                        <div class="success" style="margin-top:15px; text-align:center;"><i class="fa fa-check-circle"></i> Loan Fully Repaid</div>
                                                    <?php endif; ?>

                                                    <div class="payment-history">
                                                        <h5><i class="fa fa-history"></i> Payment History</h5>
                                                        <?php if (!empty($app['repayment_history'])): ?>
                                                            <table class="table-hover">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Amount Due</th>
                                                                        <th>Amount Paid</th>
                                                                        <th>Method</th>
                                                                        <th>Status</th>
                                                                        <th>Admin Comment</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($app['repayment_history'] as $payment): ?>
                                                                        <tr>
                                                                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                                                            <td>$<?= number_format($payment['amount_due'], 2) ?></td>
                                                                            <td>$<?= number_format($payment['amount_paid'], 2) ?></td>
                                                                            <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                                                            <td class="status-<?= $payment['payment_status'] ?>"><?= ucfirst($payment['payment_status']) ?></td>
                                                                            <td><?= htmlspecialchars($payment['admin_comment'] ?? '-') ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        <?php else: ?>
                                                            <p>No payment history recorded yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button class="btn btn-sm close-loan-details" style="margin-top: 20px; background-color: #6c757d;"><i class="fa fa-times"></i> Close Details</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-records"><i class="fa fa-info-circle"></i><br> No loan applications found.</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php 
                // Filter pending approvals: Department managers (except Finance) should only see leave applications
                $filtered_pending_approvals = $pending_approvals;
                if (isset($show_loans) && !$show_loans) {
                    // Department manager (not Finance) - filter out loans
                    $filtered_pending_approvals = array_filter($pending_approvals, function($app) {
                        return $app['type'] === 'leave';
                    });
                }
                ?>
                <div id="approvals-tab" class="tab-content">
                    <h3><i class="fa fa-clock-o"></i> Pending Application Approvals</h3>
                <?php if (!empty($filtered_pending_approvals)): // Conditional rendering based on data 
                ?>
                        <div class="pending-applications-grid">
                            <?php foreach ($filtered_pending_approvals as $app):
                                        $is_second = false;
                                        $first_approver_name = '';
                                        if ($app['type'] == 'leave') {
                                            if (!empty($app['manager_approval_status']) && $app['manager_approval_status'] == 'approved' && (strtolower($app['employee_role'] ?? '') == 'employee')) {
                                                $is_second = true;
                                                $first_approver_name = trim(($app['first_approver_first_name'] ?? '') . ' ' . ($app['first_approver_last_name'] ?? ''));
                                                if ($first_approver_name === '') $first_approver_name = 'Manager';
                                            } elseif (!empty($app['hr_approval_status']) && $app['hr_approval_status'] == 'approved') {
                                                $is_second = true;
                                                $first_approver_name = 'HR';
                                            }
                                        } elseif ($app['type'] == 'loan' && !empty($app['hr_approval_status']) && $app['hr_approval_status'] == 'approved') {
                                            $is_second = true;
                                            $first_approver_name = 'HR';
                                        }
                                    ?>
                                <div class="pending-application-card">
                                    <div class="app-header">
                                        <h4><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></h4>
                                        <span class="app-type">
                                            <?= htmlspecialchars(ucfirst($app['type'])) ?>
                                            <?php if ($app['type'] == 'leave' && isset($app['leave_type'])): ?>
                                                <br><small>(<?= htmlspecialchars($app['leave_type']) ?>)</small>
                                            <?php endif; ?>
                                            <br><strong class="approval-stage"><?= $is_second ? 'Second approval' : 'First approval' ?><?= ($is_second && $first_approver_name) ? ' (Approved by ' . htmlspecialchars($first_approver_name) . ' first)' : '' ?></strong>
                                        </span>
                                    </div>
                                    <div class="app-details">
                                        <?php if ($app['type'] == 'leave'): ?>
                                            <p><i class="fa fa-calendar"></i> <?= htmlspecialchars($app['start_date']) ?> to <?= htmlspecialchars($app['end_date']) ?></p>
                                            <p><i class="fa fa-clock-o"></i> <?= (int)$app['days_requested'] ?> days requested</p>
                                            <p><i class="fa fa-comment"></i> Reason: <?= nl2br(htmlspecialchars($app['reason'])) ?></p>
                                        <?php else: ?>
                                            <p><i class="fa fa-money"></i> Amount: $<?= number_format($app['amount'], 2) ?></p>
                                            <p><i class="fa fa-calendar-check-o"></i> Plan: <?= htmlspecialchars($app['repayment_plan']) ?> Months</p>
                                            <p><i class="fa fa-comment"></i> Reason: <?= nl2br(htmlspecialchars($app['reason'])) ?></p>
                                            <?php if (!empty($app['hr_approval_status']) && $app['hr_approval_status'] == 'approved'): ?>
                                                <p style="margin-top: 10px; padding: 8px; background-color: #d4edda; border-left: 3px solid #28a745; border-radius: 4px;">
                                                    <i class="fa fa-check-circle" style="color: #28a745;"></i> <strong>HR Approved</strong> - Awaiting Finance Manager approval
                                                </p>
                                                <?php if (!empty($app['hr_first_approved_at'])): ?>
                                                    <p style="margin-top: 8px; font-size: 13px; color: #555;">
                                                        <i class="fa fa-clock-o"></i>
                                                        First approval done by <strong>HR</strong> at
                                                        <strong><?= date('M d, Y H:i', strtotime($app['hr_first_approved_at'])) ?></strong>
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <form method="post" class="response-form"> <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                        <input type="hidden" name="respond_application" value="1">
                                        <div class="form-group"><label>Response</label><select name="status" class="input-1" style="min-width: 220px; padding: 12px 15px; font-size: 15px;">
                                                <option value="pending" selected>Keep Pending</option>
                                                <option value="approved">Approve</option>
                                                <option value="denied">Deny</option>
                                            </select></div>
                                        <div class="form-group"><label>Comment (Optional)</label><textarea name="comment" class="input-1" rows="2"></textarea></div>
                                        <button type="submit" class="btn">Submit Response</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-records">
                            <i class="fa fa-info-circle"></i><br>
                            No pending application approvals at this time.
                        </div>
                    <?php endif; ?>
                </div>

                <?php 
                // Filter responded applications: Department managers (except Finance) should only see leave applications
                $filtered_responded_applications = $responded_applications ?? [];
                if (isset($show_loans) && !$show_loans) {
                    // Department manager (not Finance) - filter out loans
                    $filtered_responded_applications = array_filter($responded_applications ?? [], function($app) {
                        return ($app['type'] ?? '') === 'leave';
                    });
                }
                ?>
                <?php if (!empty($filtered_responded_applications)): // Conditional rendering based on data 
                ?>
                    <div id="responded-tab" class="tab-content">
                        <h3><i class="fa fa-history"></i> Applications Responded By Me</h3>
                        <div class="table-responsive">
                            <table class="main-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Type</th>
                                        <th>Details</th>
                                        <th>Your Response</th>
                                        <th>Your Comment</th>
                                        <th>Date Responded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filtered_responded_applications as $app): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($app['type'])) ?> <?= $app['type'] == 'leave' ? '<small>(' . htmlspecialchars($app['leave_type']) . ')</small>' : '' ?></td>
                                            <td>
                                                <?php if ($app['type'] == 'leave'): ?><?= htmlspecialchars($app['start_date']) ?> to <?= htmlspecialchars($app['end_date']) ?> (<?= (int)$app['days_requested'] ?> days)
                                                <?php else: ?>$<?= number_format($app['amount'], 2) ?> / <?= htmlspecialchars($app['repayment_plan']) ?> Mo.
                                            <?php endif; ?><br><small>Reason: <?= substr(htmlspecialchars($app['reason']), 0, 100) ?></small>
                                            </td>
                                            <td class="status-<?= htmlspecialchars($app['status']) ?>">
                                                <?php 
                                                // Show the appropriate approval status based on who responded
                                                if (isset($is_hr) && $is_hr && !empty($app['hr_approval_status'])) {
                                                    echo htmlspecialchars(ucfirst($app['hr_approval_status']));
                                                } elseif (!empty($app['manager_approval_status'])) {
                                                    echo htmlspecialchars(ucfirst($app['manager_approval_status']));
                                                } elseif (!empty($app['finance_approval_status'])) {
                                                    echo htmlspecialchars(ucfirst($app['finance_approval_status']));
                                                } else {
                                                    echo htmlspecialchars(ucfirst($app['status']));
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Show the appropriate comment based on who responded
                                                if (isset($is_hr) && $is_hr && !empty($app['hr_comment'])) {
                                                    echo nl2br(htmlspecialchars($app['hr_comment']));
                                                } elseif (!empty($app['manager_comment'])) {
                                                    echo nl2br(htmlspecialchars($app['manager_comment']));
                                                } elseif (!empty($app['finance_comment'])) {
                                                    echo nl2br(htmlspecialchars($app['finance_comment']));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Show the date/time when the response was made
                                                if (!empty($app['updated_at'])) {
                                                    echo date('M d, Y H:i', strtotime($app['updated_at']));
                                                } elseif (!empty($app['created_at'])) {
                                                    echo date('M d, Y H:i', strtotime($app['created_at']));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Elements ---
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            const leaveTypeSelect = document.getElementById('leave-type');
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            const daysRequestedSpan = document.getElementById('days-requested');
            const availableDaysSpan = document.getElementById('available-days');
            const loanSummaryRows = document.querySelectorAll('.loan-summary-row');
            const loanDetailRows = document.querySelectorAll('.loan-details-row');

            let leaveData = {};

            // --- Functions ---
            function switchTab(targetTabId) {
                tabContents.forEach(content => content.classList.remove('active'));
                const targetContent = document.getElementById(targetTabId);
                if (targetContent) targetContent.classList.add('active'); // Add null check

                tabButtons.forEach(button => {
                    button.classList.remove('active');
                    if (button.getAttribute('data-tab') + '-tab' === targetTabId) {
                        button.classList.add('active');
                    }
                });
                
                // Show/hide persistent headers
                document.querySelectorAll('.tab-headers h2').forEach(header => {
                    header.style.display = 'none';
                });
                const headerMap = {
                    'leaves-tab': 'leaves-header',
                    'loans-tab': 'loans-header',
                    'approvals-tab': 'approvals-header',
                    'responded-tab': 'responded-header'
                };
                const headerId = headerMap[targetTabId];
                if (headerId) {
                    const header = document.getElementById(headerId);
                    if (header) header.style.display = 'block';
                }
                
                localStorage.setItem('activeApplicationTab', targetTabId);
            }

            function fetchAndDisplayLeaveInfo() {
                if (!availableDaysSpan) return; // Exit if leave elements aren't present
                fetch('get_leave_days.php')
                    .then(response => {
                        if (!response.ok) throw new Error('Network error');
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        leaveData = data;
                        updateAvailableDaysCount();
                    })
                    .catch(error => {
                        console.error('Error fetching leave data:', error);
                        availableDaysSpan.textContent = 'Error';
                    });
            }

            function updateAvailableDaysCount() {
                if (!leaveData || Object.keys(leaveData).length === 0 || !leaveTypeSelect) return;
                const selectedType = leaveTypeSelect.value;
                let available = 0;
                switch (selectedType) {
                    case 'special':
                        available = leaveData.special_leave_days_remaining;
                        break;
                    case 'sick':
                        available = leaveData.sick_leave_days_remaining;
                        break;
                    case 'maternity':
                        available = leaveData.maternity_leave_days_remaining;
                        break;
                    case 'normal':
                    default:
                        available = leaveData.normal_leave_days;
                        break;
                }
                availableDaysSpan.textContent = available + ' days';
            }

            function calculateRequestedDays() {
                if (!startDateInput || !endDateInput || !leaveTypeSelect || !daysRequestedSpan) return;
                const startDateValue = startDateInput.value;
                const selectedType = leaveTypeSelect.value;
                let calculatedEndDate = null;

                if (selectedType === 'maternity' && startDateValue) {
                    const start = new Date(startDateValue);
                    if (!isNaN(start.getTime())) {
                        let end = new Date(start);
                        end.setDate(start.getDate() + 97);
                        calculatedEndDate = end;
                        endDateInput.value = end.toISOString().split('T')[0]; // Simpler format
                        endDateInput.readOnly = true;
                    }
                } else {
                    endDateInput.readOnly = false;
                }

                const endDateValue = endDateInput.value;

                if (startDateValue && endDateValue) {
                    const start = new Date(startDateValue);
                    const end = calculatedEndDate instanceof Date ? calculatedEndDate : new Date(endDateValue);
                    if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end >= start) {
                        const diffTime = Math.abs(end - start);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                        daysRequestedSpan.textContent = diffDays;
                        endDateInput.setCustomValidity('');
                    } else {
                        daysRequestedSpan.textContent = 'Invalid';
                        if (!calculatedEndDate && endDateValue && new Date(endDateValue) < start) {
                            endDateInput.setCustomValidity('End date cannot be earlier.');
                        } else {
                            endDateInput.setCustomValidity('');
                        }
                    }
                } else {
                    daysRequestedSpan.textContent = '0';
                    endDateInput.setCustomValidity('');
                }
            }

            // REMOVED toggleFormFields function as it's not needed with separate forms per tab

            function showLoanDetails(detailsId) {
                loanDetailRows.forEach(row => row.style.display = 'none');
                const targetContainer = document.getElementById(detailsId);
                if (targetContainer) {
                    const parentRow = targetContainer.closest('.loan-details-row');
                    if (parentRow) parentRow.style.display = 'table-row';
                }
            }

            function hideAllLoanDetails() {
                loanDetailRows.forEach(row => row.style.display = 'none');
            }

            // --- Event Listeners ---
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab') + '-tab';
                    switchTab(tabId);
                });
            });

            // Leave form listeners (check if elements exist first)
            if (leaveTypeSelect) leaveTypeSelect.addEventListener('change', () => {
                updateAvailableDaysCount();
                calculateRequestedDays();
            });
            if (startDateInput) startDateInput.addEventListener('change', calculateRequestedDays);
            if (endDateInput) endDateInput.addEventListener('change', calculateRequestedDays);
            // Removed appTypeSelect listener

            // Loan Details Toggle Listeners
            loanSummaryRows.forEach(row => {
                const detailsButton = row.querySelector('.view-loan-details');
                const detailsId = row.getAttribute('data-details-id');
                if (detailsButton && detailsId) {
                    detailsButton.addEventListener('click', (e) => {
                        e.stopPropagation();
                        showLoanDetails(detailsId);
                    });
                }
            });
            document.querySelectorAll('.close-loan-details').forEach(button => {
                button.addEventListener('click', () => {
                    hideAllLoanDetails();
                });
            });

            // --- Initialization ---
            const lastTab = localStorage.getItem('activeApplicationTab');
            let defaultTabId = 'leaves-tab'; // Default for everyone
            if (tabButtons.length > 0 && document.getElementById(defaultTabId)) { // Make sure default tab exists
                // Check if manager tabs exist and should be the default (optional)
                // if((!empty($pending_approvals) || !empty($responded_applications)) && document.getElementById('approvals-tab')){
                //    defaultTabId = 'approvals-tab'; // Maybe default manager to approvals
                // }
            } else if (tabButtons.length > 0) {
                defaultTabId = tabButtons[0].getAttribute('data-tab') + '-tab'; // Fallback to first available tab
            }

            // Initialize header display
            const initialHeader = document.getElementById('leaves-header');
            if (initialHeader) initialHeader.style.display = 'block';
            
            if (lastTab && document.getElementById(lastTab)) {
                switchTab(lastTab);
            } else if (document.getElementById(defaultTabId)) {
                switchTab(defaultTabId);
            } // Switch to default if possible
            
            // Handle hash navigation (e.g., #approvals-tab)
            if (window.location.hash) {
                const hashTab = window.location.hash.substring(1); // Remove #
                if (document.getElementById(hashTab)) {
                    switchTab(hashTab);
                }
            }

            if (document.getElementById('leaves-tab')) {
                fetchAndDisplayLeaveInfo();
                calculateRequestedDays();
            }
            // Removed toggleFormFields() call - not needed anymore

        });
    </script>
</body>

</html>