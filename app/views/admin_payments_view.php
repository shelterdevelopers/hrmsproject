<!DOCTYPE html>
<html>
<head>
    <title>Payment Verifications · Shelter HRMS</title>
    <?php include __DIR__ . '/../../inc/head_common.php'; ?>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include "../inc/admin_header.php" ?>
    
    <div class="container">
        <h2>Pending Payment Verifications</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($pending_payments)): ?>
            <table class="verification-table">
                <!-- Similar structure to repayment history but with action buttons -->
                <tr>
                    <!-- ... other columns ... -->
                    <td>
                        <form method="post" action="admin_verify_payment.php">
                            <input type="hidden" name="repayment_id" value="<?= $payment['id'] ?>">
                            <select name="action" class="input-1">
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                            </select>
                            <textarea name="comment" placeholder="Comments..." class="input-1"></textarea>
                            <button type="submit" class="btn">Submit</button>
                        </form>
                    </td>
                </tr>
            </table>
        <?php else: ?>
            <p>No pending payments to verify</p>
        <?php endif; ?>
    </div>
</body>
</html>