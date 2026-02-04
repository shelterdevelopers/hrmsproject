<?php
session_start();
if (isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    include "app/Model/User.php";
    $user = get_user_by_id($conn, $_SESSION['employee_id']);
    if (!$user) {
        header("Location: login.php");
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Edit Profile · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
    </head>

    <body>
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>
            <section class="section-1">
                <div class="profile-container">
                    <h2 class="title">Edit Profile <a href="profile.php" class="btn">Back to Profile</a></h2>

                    <?php if (isset($_GET['error'])) { ?>
                        <div class="danger"><?= htmlspecialchars($_GET['error']) ?></div>
                    <?php } ?>

                    <?php if (isset($_GET['success'])) { ?>
                        <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
                    <?php } ?>
                    <!-- Separate Picture Upload Form -->
                    <!-- Replace the picture upload form section with this: -->
                    <form method="POST" action="app/update-profile-picture.php" enctype="multipart/form-data"
                        class="picture-form" id="pictureForm">
                        <div class="form-section">
                            <h3>Profile Picture</h3>
                            <div class="picture-upload-wrapper">
                                <div class="current-avatar">
                                    <div class="avatar">
                                        <?php if (file_exists("img/user" . $user['employee_id'] . ".png")): ?>
                                            <img src="/HRMS/img/user<?= $user['employee_id'] ?>.png?<?= time() ?>"
                                                alt="Current Profile Picture">
                                        <?php else: ?>
                                            <i class="fa fa-user-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (file_exists("img/user" . $user['employee_id'] . ".png")): ?>
                                        <a href="app/remove-profile-picture.php" 
                                           class="btn" 
                                           style="margin-top: 10px; display: inline-block; background: #dc3545; color: white;"
                                           onclick="return confirm('Are you sure you want to remove your profile picture?');">
                                            <i class="fa fa-trash"></i> Remove Picture
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="file-upload">
                                    <label class="file-upload-label">
                                        <i class="fa fa-camera"></i> Choose New Picture
                                        <input type="file" name="profile_picture" id="profilePicture"
                                            accept="image/png, image/jpeg, image/jpg" class="input-1">
                                    </label>
                                    <div class="file-info">Max size: 2MB (PNG, JPG/JPEG only)</div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="edit-btn" id="updatePictureBtn" style="display: none;">Update Picture</button>
                    </form>

                    <!-- Profile Info Form -->
                    <form class="profile-form" method="POST" action="app/update-employee-profile.php">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="input-1"
                                    value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="input-1"
                                    value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Maiden Name</label>
                                <input type="text" name="maiden_name" value="<?= $user['maiden_name'] ?>" class="input-1">
                            </div>
                            
                            <div class="form-group">
                                <label>Date of birth</label>
                                <input type="date" name="date_of_birth" value="<?= $user['date_of_birth'] ?>" class="input-1">
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" name="email_address" value="<?= $user['email_address'] ?>" class="input-1">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number" value="<?= $user['phone_number'] ?>" class="input-1">
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" value="<?= $user['address'] ?>" class="input-1">
                            </div>
                            
                            
                            <div class="form-group">
                                <label>Next of Kin</label>
                                <input type="text" name="emergency_contact_name" value="<?= $user['emergency_contact_name'] ?>" class="input-1">
                            </div>
                            
                            
                            <div class="form-group">
                                <label>Next of Kin Relationship to Employee</label>
                                <input type="text" name="next_of_kin_relationship" value="<?= $user['next_of_kin_relationship'] ?>" class="input-1">
                            </div>
                            
                            
                            <div class="form-group">
                                <label>Next of Kin Address & Contact Tel Nos.</label>
                                <input type="text" name="emergency_contact_number" value="<?= $user['emergency_contact_number'] ?>" class="input-1">
                            </div>
                            <!-- Other personal info fields -->


                            <!-- Add these fields to the form -->
                            <div class="form-group">
                                <label>ID Number</label>
                                <input type="text" name="id_no" value="<?= $user['id_no'] ?>" class="input-1" readonly>
                            </div>

                            <div class="form-group">
                                <label>Passport Details</label>
                                <input type="text" name="passport_details" value="<?= $user['passport_details'] ?>"
                                    class="input-1">
                            </div>

                            <div class="form-group">
                                <label>Driver's License No</label>
                                <input type="text" name="driver_license_no" value="<?= $user['driver_license_no'] ?>"
                                    class="input-1">
                            </div>

                            <div class="form-group">
                                <label>Residential Address</label>
                                <textarea name="residential_address"
                                    class="input-1"><?= $user['residential_address'] ?></textarea>
                            </div>

                            

                            <div class="form-group">
                                <label>Spouse Name</label>
                                <textarea name="spouse_name"
                                    class="input-1"><?= $user['spouse_name'] ?></textarea>
                            </div>

                            

                            <div class="form-group">
                                <label>Number of Children</label>
                                <input type="number" name="own_children" class="input-1" min="0" step="1"
                                    value="<?= htmlspecialchars((string)($user['own_children'] ?? '')) ?>">
                            </div>

                            

                            <div class="form-group">
                                <label>Number of Dependents</label>
                                <input type="number" name="own_dependants" class="input-1" min="0" step="1"
                                    value="<?= htmlspecialchars((string)($user['own_dependants'] ?? '')) ?>">
                            </div>

                            

                            <div class="form-group">
                                <label>Banking Details</label>
                                <textarea name="banking_details" class="input-1" readonly 
                                    title="Banking details can only be updated by HR. Please contact HR if you need to update this information.">
                                    <?= htmlspecialchars($user['banking_details'] ?? 'Not provided') ?>
                                </textarea>
                                <small style="color: #666; font-style: italic;">
                                    <i class="fa fa-lock"></i> Banking details can only be updated by HR. Contact HR for changes.
                                </small>
                            </div>

                            <div class="form-group">
                                <label>OneDrive Documents Link</label>
                                <input type="url" name="document_url" 
                                    class="input-1" 
                                    placeholder="https://onedrive.live.com/..."
                                    value="<?= htmlspecialchars($user['document_url'] ?? '') ?>">
                                <small style="color: #666; font-size: 12px;">
                                    <i class="fa fa-info-circle"></i> Enter your OneDrive document folder link
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Marital Status</label>
                                <select name="marital_status" class="input-1">
                                    <option value="Single" <?= $user['marital_status'] == 'Single' ? 'selected' : '' ?>>Single
                                    </option>
                                    <option value="Married" <?= $user['marital_status'] == 'Married' ? 'selected' : '' ?>>
                                        Married</option>
                                    <option value="Divorced" <?= $user['marital_status'] == 'Divorced' ? 'selected' : '' ?>>
                                        Divorced</option>
                                    <option value="Widowed" <?= $user['marital_status'] == 'Widowed' ? 'selected' : '' ?>>
                                        Widowed</option>
                                </select>
                            </div>

                            <!-- Add similar fields for spouse_name, own_children, own_dependants, next_of_kin etc. -->

                            <!-- Readonly fields for job info -->
                            <div class="form-group">
                                <label>Job Title</label>
                                <input type="text" value="<?= $user['job_title'] ?>" class="input-1" readonly>
                            </div>

                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" value="<?= $user['department'] ?>" class="input-1" readonly>
                            </div>

                            <div class="form-group">
                                <label>Manager</label>
                                <?php
                                $manager = get_user_by_id($conn, $user['manager_id']);
                                $manager_name = $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : 'None';
                                ?>
                                <input type="text" value="<?= $manager_name ?>" class="input-1" readonly>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Change Password</h3>
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="password" class="input-1" placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="input-1" placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="input-1"
                                    placeholder="Confirm new password">
                            </div>
                        </div>

                        <button type="submit" class="btn submit-btn">Save Changes</button>
                    </form>


                </div>
            </section>
        </div>

        <script type="text/javascript">

            // Show update button only when file is selected
            document.getElementById('profilePicture').addEventListener('change', function (e) {
                const form = document.getElementById('pictureForm');
                const updateBtn = document.getElementById('updatePictureBtn');
                if (this.files.length > 0) {
                    form.classList.add('has-file');
                    document.querySelector('.file-info').textContent = this.files[0].name;
                    updateBtn.style.display = 'inline-block';
                } else {
                    form.classList.remove('has-file');
                    document.querySelector('.file-info').textContent = 'Max size: 2MB (PNG, JPG/JPEG only)';
                    updateBtn.style.display = 'none';
                }
            });
            // Password validation
            document.querySelector('.profile-form').addEventListener('submit', function (e) {
                const newPassword = document.querySelector('[name="new_password"]').value;
                const confirmPassword = document.querySelector('[name="confirm_password"]').value;

                if (newPassword && newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                }
            });
        </script>
    </body>

    </html>
<?php } else {
    header("Location: login.php");
    exit();
} ?>