<?php
session_start();
require_once "DB_connection.php";
require_once "app/Model/User.php";


// Allow Admin and HR to approve pending employees
require_once "app/Model/RoleHelper.php";
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);
$is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id'] ?? 0);

if (!isset($_SESSION['employee_id']) || (!$is_admin && !$is_hr)) {
    header("Location: login.php?error=Access+denied");
    exit();
}

// Get pending employees
$pendingEmployees = get_pending_employees($conn);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        // Get form data (admin-only fields; signup data already in employee record)
        $admin_data = [
            'job_title' => $_POST['job_title'],
            'department' => $_POST['department'],
            'manager_id' => !empty($_POST['manager_id']) ? $_POST['manager_id'] : null,
            'date_of_hire' => $_POST['date_of_hire'],
            'employment_type' => $_POST['employment_type'],
            'work_location' => $_POST['work_location'],
            'role' => $_POST['role'],
            'executive_member' => isset($_POST['executive_member']) ? 1 : 0
        ];

        if (approve_pending_employee($conn, $_POST['employee_id'], $admin_data)) {
            // Notify HR whenever a new employee registration is approved
            require_once "app/Model/Notification.php";
            require_once "app/Model/RoleHelper.php";
            $hr_id = RoleHelper::get_hr_id($conn);
            if ($hr_id) {
                $emp = get_user_by_id($conn, $_POST['employee_id']);
                $name = $emp ? trim($emp['first_name'] . ' ' . $emp['last_name']) : 'New employee';
                $message = "New employee registration approved: {$name} ({$admin_data['job_title']}, {$admin_data['department']}).";
                create_notification($conn, $hr_id, $message, 'new_employee_registered');
            }
            $_SESSION['success'] = "Employee approved successfully. HR has been notified.";
        } else {
            $_SESSION['error'] = "Failed to approve employee";
        }
    } elseif (isset($_POST['reject'])) {
        // Existing rejection logic
    }

    header("Location: pending-employees.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Pending Registrations · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <input type="checkbox" id="checkbox">
    <?php include "inc/header.php" ?>
    <div class="body">
        <?php include "inc/nav.php" ?>
        <section class="section-1">
            <h2>Pending Employee Registrations</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (empty($pendingEmployees)): ?>
                <p>No pending employee approvals</p>
            <?php else: ?>
                <div class="employee-grid">
                    <?php foreach ($pendingEmployees as $employee): ?>
                        <div class="employee-card">
                            <h3><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h3>
                            <!-- From signup (read-only) -->
                            <div class="employee-details" style="background: #f0f8ff; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                                <p><strong>From signup:</strong></p>
                                <p><strong>ID:</strong> <?= htmlspecialchars($employee['id_no'] ?? '') ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($employee['email_address'] ?? '') ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($employee['phone_number'] ?? '') ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($employee['residential_address'] ?? '') ?></p>
                            </div>

                            <div class="document-links">
                                <a href="<?= htmlspecialchars($employee['document_url'] ?? '#') ?>" target="_blank">View Documents</a>
                            </div>

                            <form method="POST" class="approval-form">
                                <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">

                                <div class="admin-fields">
                                    <h4>Employment Details (admin only)</h4>

                                    <div class="form-group">
                                        <label>Job Title*</label>
                                        <input type="text" name="job_title" class="input-1" value="<?= htmlspecialchars($employee['job_title'] ?? '') ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Department*</label>
                                        <select name="department" class="input-1" required>
                                            <?php
                                            $depts = ['OPERATIONS', 'CORPORATE SERVICES', 'SALES AND MARKETING', 'FINANCE AND ACCOUNTS', 'ETOSHA'];
                                            $emp_dept = $employee['department'] ?? '';
                                            foreach ($depts as $d):
                                                $sel = (strtoupper(trim($emp_dept)) === $d) ? ' selected' : '';
                                            ?>
                                            <option value="<?= $d ?>"<?= $sel ?>><?= $d ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Manager</label>
                                        <select name="manager_id" class="input-1">
                                            <option value="">-- Select Manager --</option>
                                            <?php
                                            $emp_mgr = (int)($employee['manager_id'] ?? 0);
                                            foreach (get_all_managers($conn) as $manager): ?>
                                                <option value="<?= $manager['employee_id'] ?>"<?= $emp_mgr && $manager['employee_id'] == $emp_mgr ? ' selected' : '' ?>>
                                                    <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-holder">
                                        <label>
                                            <input type="checkbox" name="executive_member" value="1"
                                                <?= !empty($employee['executive_member']) ? 'checked' : '' ?>>
                                            Executive Member
                                        </label>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Date of Hire*</label>
                                            <input type="date" name="date_of_hire" class="input-1" value="<?= htmlspecialchars($employee['date_of_hire'] ?? '') ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Employment Type*</label>
                                            <select name="employment_type" class="input-1" required>
                                                <?php
                                                $types = ['Full-time', 'Part-time', 'Contract'];
                                                $emp_type = $employee['employment_type'] ?? '';
                                                foreach ($types as $t): ?>
                                                <option value="<?= $t ?>"<?= (trim($emp_type) === $t) ? ' selected' : '' ?>><?= $t ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Work Location*</label>
                                            <select name="work_location" class="input-1" required>
                                                <?php
                                                $locs = ['Remote' => 'Remote', 'Onsite' => 'Onsite', 'Hq' => 'Head Office', 'Office' => 'Office'];
                                                $emp_loc = trim($employee['work_location'] ?? '');
                                                foreach ($locs as $val => $label): ?>
                                                <option value="<?= $val ?>"<?= ($emp_loc === $val || $emp_loc === $label) ? ' selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Role*</label>
                                            <select name="role" class="input-1" required>
                                                <?php
                                                $emp_role = strtolower(trim($employee['role'] ?? 'employee'));
                                                ?>
                                                <option value="employee"<?= ($emp_role === 'employee') ? ' selected' : '' ?>>Employee</option>
                                                <option value="manager"<?= ($emp_role === 'manager') ? ' selected' : '' ?>>Manager</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">

                                        <div class="action-buttons">
                                            <button type="submit" name="approve" class="btn approve">Approve</button>
                                            <button type="submit" name="reject" class="btn reject">Reject</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>
    </div>
</body>

</html>