<?php
session_start();
// Load database connection first
include "DB_connection.php";

// Allow Admin and HR to manage users (but only Admin can reset passwords and register employees)
require_once "app/Model/RoleHelper.php";
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);
$is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id'] ?? 0);

if (isset($_SESSION['employee_id']) && ($is_admin || $is_hr)) {
    include "app/Model/User.php";

    $users = get_all_users($conn);
    $is_pending = function($status) {
        return strtolower($status) === 'pending';
    };
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Manage Users · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/aesthetic-improvements.css">
        <style>
            /* Page-level scrollbars - both vertical and horizontal */
            .section-1 {
                overflow-x: scroll !important;
                overflow-y: scroll !important;
                width: 100% !important;
                height: calc(100vh - 150px) !important;
                max-height: calc(100vh - 150px) !important;
                position: relative;
                -webkit-overflow-scrolling: touch;
                /* Firefox scrollbars */
                scrollbar-width: auto !important;
                scrollbar-color: #2596be #f1f1f1 !important;
            }
            
            /* Webkit browsers - Horizontal scrollbar for page */
            .section-1::-webkit-scrollbar:horizontal {
                height: 18px !important;
                display: block !important;
            }
            
            /* Webkit browsers - Vertical scrollbar for page */
            .section-1::-webkit-scrollbar:vertical {
                width: 18px !important;
                display: block !important;
            }
            
            /* Webkit browsers - All scrollbars for page */
            .section-1::-webkit-scrollbar {
                height: 18px !important;
                width: 18px !important;
                display: block !important;
                -webkit-appearance: none;
            }
            
            /* Scrollbar track for page */
            .section-1::-webkit-scrollbar-track {
                background: #f1f1f1 !important;
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            
            /* Scrollbar thumb for page */
            .section-1::-webkit-scrollbar-thumb {
                background: #2596be !important;
                border-radius: 4px;
                border: 2px solid #f1f1f1;
            }
            
            .section-1::-webkit-scrollbar-thumb:hover {
                background: #1e7a9e !important;
            }
            
            .section-1::-webkit-scrollbar-thumb:active {
                background: #155a7a !important;
            }
            
            /* Scrollbar corner for page */
            .section-1::-webkit-scrollbar-corner {
                background: #f1f1f1;
                border: 1px solid #ddd;
            }
            
            .status-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-pending {
                background-color: #ffc107;
                color: #000;
            }
            .status-active {
                background-color: #28a745;
                color: #fff;
            }
            .status-terminated {
                background-color: #dc3545;
                color: #fff;
            }
            .status-on-leave {
                background-color: #17a2b8;
                color: #fff;
            }
            .pending-row {
                background-color: #fff3cd;
            }
            /* Table container - no scrollbars here, page handles scrolling */
            .table-responsive {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                border: none !important;
                border-radius: 0;
                position: relative;
                display: block;
                padding: 0;
            }
            
            .table-responsive table.main-table {
                min-width: 1500px !important;
                width: auto !important;
                margin: 0 !important;
                table-layout: auto;
            }
            
            /* Add User Button - Make text visible */
            .title .btn {
                background-color: #2596be !important;
                color: white !important;
                padding: 10px 20px !important;
                text-decoration: none !important;
                border-radius: 4px !important;
                font-weight: 600 !important;
                display: inline-block !important;
                margin-left: 15px !important;
                border: none !important;
                transition: background-color 0.3s;
            }
            
            .title .btn:hover {
                background-color: #1e7a9e !important;
                color: white !important;
            }
            
            /* Dropdown Menu Styles */
            .action-dropdown {
                position: relative;
                display: inline-block;
            }
            
            .dropdown-toggle {
                background-color: #2596be;
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 40px;
                transition: background-color 0.3s;
            }
            
            .dropdown-toggle:hover {
                background-color: #1e7a9e;
            }
            
            .dropdown-menu {
                display: none;
                position: absolute;
                right: 0;
                top: 100%;
                background-color: white;
                min-width: 180px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1000;
                border-radius: 4px;
                overflow: hidden;
                margin-top: 5px;
                border: 1px solid #ddd;
            }
            
            .dropdown-menu.show {
                display: block;
            }
            
            .dropdown-item {
                display: block;
                padding: 10px 15px;
                text-decoration: none;
                color: #333;
                border: none;
                width: 100%;
                text-align: left;
                background: none;
                cursor: pointer;
                font-size: 14px;
                transition: background-color 0.2s;
            }
            
            .dropdown-item:hover {
                background-color: #f5f5f5;
            }
            
            .dropdown-item i {
                width: 20px;
                margin-right: 8px;
                text-align: center;
            }
            
            .dropdown-item.approve {
                color: #28a745;
            }
            
            .dropdown-item.approve:hover {
                background-color: #d4edda;
            }
            
            .dropdown-item.reject {
                color: #dc3545;
            }
            
            .dropdown-item.reject:hover {
                background-color: #f8d7da;
            }
            
            .dropdown-item.edit {
                color: #2596be;
            }
            
            .dropdown-item.edit:hover {
                background-color: #e7f3f6;
            }
            
            .dropdown-item.delete {
                color: #dc3545;
            }
            
            .dropdown-item.delete:hover {
                background-color: #f8d7da;
            }
            
            .dropdown-divider {
                height: 1px;
                margin: 5px 0;
                overflow: hidden;
                background-color: #e9ecef;
            }
            
            /* Close dropdown when clicking outside */
            .dropdown-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999;
                display: none;
            }
            
            .dropdown-overlay.show {
                display: block;
            }
        </style>
    </head>

    <body>
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>
            <section class="section-1">
                <h4 class="title">Manage Users <?php if ($is_admin): ?><a href="add-user.php" class="btn">Add User</a><?php endif; ?></h4>

                <?php if (isset($_GET['success'])) { ?>
                    <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
                <?php } ?>

                <?php if (isset($_GET['error'])) { ?>
                    <div class="danger"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php } ?>

                <?php if (!empty($users)) { ?>
                    <div class="table-responsive">
                        <table class="main-table" style="min-width: 1500px !important; width: auto !important; margin: 0 !important;">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Job Title</th>
                                    <th>Department</th>
                                    <th>Status & Account Status</th>
                                    <th>Manager</th>
                                    <th>Documents</th>
                                    <th>Date of Hire</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user):
                                    $manager = get_user_by_id($conn, $user['manager_id']);
                                    $manager_name = $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : 'None';
                                    $user_status = $user['status'] ?? 'Unknown';
                                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $user_status));
                                    $is_user_pending = $is_pending($user_status);
                                    ?>
                                    <tr class="<?= $is_user_pending ? 'pending-row' : '' ?>">
                                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email_address']) ?></td>
                                        <td><?= htmlspecialchars($user['phone_number']) ?></td>
                                        <td><?= htmlspecialchars($user['job_title'] ?: 'Not Assigned') ?></td>
                                        <td><?= htmlspecialchars($user['department'] ?: 'Not Assigned') ?></td>
                                        <td>
                                            <?php 
                                            // Combine Status and Account Status into one column
                                            $account_locked = !empty($user['account_locked_until']) && strtotime($user['account_locked_until']) > time();
                                            $failed_attempts = (int)($user['failed_login_attempts'] ?? 0);
                                            
                                            // Show employee status (Active, Pending, etc.)
                                            ?>
                                            <span class="status-badge <?= $status_class ?>">
                                                <?= htmlspecialchars($user_status) ?>
                                            </span>
                                            <br>
                                            <?php
                                            // Show account security status below
                                            if ($account_locked): 
                                                $lock_time = date('M d, H:i', strtotime($user['account_locked_until']));
                                            ?>
                                                <span class="status-badge" style="background-color: #dc3545; color: white; margin-top: 4px; display: inline-block;">
                                                    <i class="fa fa-lock"></i> Locked until <?= $lock_time ?>
                                                </span>
                                            <?php elseif ($failed_attempts > 0): ?>
                                                <span class="status-badge" style="background-color: #ffc107; color: #000; margin-top: 4px; display: inline-block;">
                                                    <i class="fa fa-exclamation-triangle"></i> <?= $failed_attempts ?> failed attempt(s)
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background-color: #28a745; color: white; margin-top: 4px; display: inline-block;">
                                                    <i class="fa fa-check"></i> Account Active
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($manager_name) ?></td>
                                        <td>
                                            <?php if (!empty($user['document_url'])): ?>
                                                <a href="<?= htmlspecialchars($user['document_url']) ?>" target="_blank" class="btn">
                                                    <i class="fa fa-file"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #999;">No documents</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $user['date_of_hire'] ? date('M d, Y', strtotime($user['date_of_hire'])) : 'N/A' ?></td>
                                        <td>
                                            <div class="action-dropdown">
                                                <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu" id="menu-<?= $user['employee_id'] ?>">
                                                    <?php if ($is_user_pending): ?>
                                                        <form method="POST" action="app/approve-user.php" style="display: contents;">
                                                            <input type="hidden" name="employee_id" value="<?= $user['employee_id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="dropdown-item approve" 
                                                                    onclick="return confirm('Approve this user? They will be able to log in immediately.')">
                                                                <i class="fa fa-check"></i> Approve User
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="app/approve-user.php" style="display: contents;">
                                                            <input type="hidden" name="employee_id" value="<?= $user['employee_id'] ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="dropdown-item reject" 
                                                                    onclick="return confirm('Reject this user? This will terminate their account.')">
                                                                <i class="fa fa-times"></i> Reject User
                                                            </button>
                                                        </form>
                                                        <div class="dropdown-divider"></div>
                                                    <?php endif; ?>
                                                    <a href="edit-user.php?id=<?= $user['employee_id'] ?>" class="dropdown-item edit">
                                                        <i class="fa fa-edit"></i> Edit User
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <?php if ($is_admin): // Only Admin can reset passwords ?>
                                                    <button type="button" class="dropdown-item" 
                                                            onclick="openPasswordResetModal(<?= $user['employee_id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?>')"
                                                            style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; color: #333;">
                                                        <i class="fa fa-key"></i> Reset Password
                                                    </button>
                                                    <?php 
                                                    // Check if account is locked
                                                    $account_locked = !empty($user['account_locked_until']) && strtotime($user['account_locked_until']) > time();
                                                    if ($account_locked): ?>
                                                    <form method="POST" action="app/unlock_account.php" style="display: contents;">
                                                        <input type="hidden" name="employee_id" value="<?= $user['employee_id'] ?>">
                                                        <button type="submit" class="dropdown-item" 
                                                                onclick="return confirm('Unlock account for <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES) ?>?')"
                                                                style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; color: #28a745;">
                                                            <i class="fa fa-unlock"></i> Unlock Account
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <?php endif; ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a href="delete-user.php?id=<?= $user['employee_id'] ?>" 
                                                       class="dropdown-item delete" 
                                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <i class="fa fa-trash"></i> Delete User
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="no-records">
                        <i class="fa fa-users"></i>
                        <p>No employees found</p>
                    </div>
                <?php } ?>
            </section>
        </div>

        <!-- Overlay to close dropdown when clicking outside -->
        <div class="dropdown-overlay" id="dropdownOverlay"></div>

        <!-- Password Reset Modal -->
        <div id="passwordResetModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #2596be;"><i class="fa fa-key"></i> Reset Password</h3>
                    <button onclick="closePasswordResetModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
                </div>
                <form method="POST" action="app/reset_employee_password.php" id="passwordResetForm">
                    <input type="hidden" name="employee_id" id="reset_employee_id">
                    <div style="margin-bottom: 15px;">
                        <p><strong>Employee:</strong> <span id="reset_employee_name"></span></p>
                        <p style="color: #666; font-size: 14px;">Enter a new password for this employee. They will be notified and should change it on first login.</p>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">New Password *</label>
                        <input type="password" name="new_password" id="reset_new_password" class="input-1" required 
                               minlength="8" placeholder="Minimum 8 characters" style="width: 100%;">
                        <small style="color: #666; font-size: 12px;">Password must be at least 8 characters long</small>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="reset_confirm_password" class="input-1" required 
                               minlength="8" placeholder="Confirm new password" style="width: 100%;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closePasswordResetModal()" 
                                style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" 
                                style="padding: 10px 20px; background: #2596be; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fa fa-key"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-overlay" id="passwordResetModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>

        <script>
            // Toggle dropdown menu
            function toggleDropdown(button) {
                const dropdown = button.nextElementSibling;
                const overlay = document.getElementById('dropdownOverlay');
                const isOpen = dropdown.classList.contains('show');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                overlay.classList.remove('show');
                
                // Toggle current dropdown
                if (!isOpen) {
                    dropdown.classList.add('show');
                    overlay.classList.add('show');
                }
            }
            
            // Close dropdown when clicking outside
            document.getElementById('dropdownOverlay').addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                this.classList.remove('show');
            });
            
            // Close dropdown when clicking on a dropdown item (except forms)
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Don't close if it's a form button (let form handle it)
                    if (this.tagName === 'BUTTON' && this.closest('form')) {
                        return;
                    }
                    // Close dropdown after a short delay to allow navigation
                    setTimeout(() => {
                        const dropdown = this.closest('.dropdown-menu');
                        if (dropdown) {
                            dropdown.classList.remove('show');
                            document.getElementById('dropdownOverlay').classList.remove('show');
                        }
                    }, 100);
                });
            });
            
            // Close dropdown on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    document.getElementById('dropdownOverlay').classList.remove('show');
                    closePasswordResetModal();
                }
            });
            
            // Password Reset Modal Functions
            function openPasswordResetModal(employeeId, employeeName) {
                document.getElementById('reset_employee_id').value = employeeId;
                document.getElementById('reset_employee_name').textContent = employeeName;
                document.getElementById('passwordResetModal').style.display = 'block';
                document.getElementById('passwordResetModalOverlay').style.display = 'block';
                // Close dropdown
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                document.getElementById('dropdownOverlay').classList.remove('show');
            }
            
            function closePasswordResetModal() {
                document.getElementById('passwordResetModal').style.display = 'none';
                document.getElementById('passwordResetModalOverlay').style.display = 'none';
                document.getElementById('passwordResetForm').reset();
            }
            
            // Close modal when clicking overlay
            document.getElementById('passwordResetModalOverlay').addEventListener('click', closePasswordResetModal);
            
            // Validate password match before submit
            document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
                const newPassword = document.getElementById('reset_new_password').value;
                const confirmPassword = document.getElementById('reset_confirm_password').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
            });
        </script>
    </body>

    </html>
<?php } else {
    header("Location: login.php");
    exit();
} ?>