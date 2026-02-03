<?php
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    include "app/Model/User.php";
    $user = get_user_by_id($conn, $_SESSION['employee_id']);
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>My Profile · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/aesthetic-improvements.css">
    </head>

    <body>
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>
            <section class="section-1">
                <div class="profile-container">
                    <h2 class="title">My Profile</h2>

                    <div class="profile-card">
                        <div class="profile-header">
                            <!-- Replace the avatar section with this: -->
                            <div class="profile-header">
                                <div class="avatar">
                                    <?php if (file_exists("img/user" . $user['employee_id'] . ".png")): ?>
                                        <img src="/HRMS/img/user<?= $user['employee_id'] ?>.png?<?= time() ?>"
                                            alt="Profile Picture">
                                    <?php else: ?>
                                        <i class="fa fa-user-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                                <p><?= htmlspecialchars($user['job_title']) ?></p>
                                <a href="edit_profile.php" class="btn" style="margin-top: 15px; display: inline-block;">
                                    <i class="fa fa-pencil"></i> Edit Profile
                                </a>
                            </div>
                        </div>

                        <div class="profile-details">
                            <div class="detail-group">
                                <h4> Personal Information</h4>
                                <div class="detail-item">
                                    <span>First Name:</span>
                                    <span><?= htmlspecialchars($user['first_name']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Last Name:</span>
                                    <span><?= htmlspecialchars($user['last_name']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Maiden Name:</span>
                                    <span><?= $user['maiden_name'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Date of birth:</span>
                                    <span><?= $user['date_of_birth'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Email:</span>
                                    <span><?= $user['email_address'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Phone Number:</span>
                                    <span><?= $user['phone_number'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Address:</span>
                                    <span><?= htmlspecialchars($user['address'] ?? $user['residential_address'] ?? 'Not provided') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>ID Number:</span>
                                    <span><?= $user['id_no'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Passport Details:</span>
                                    <span><?= $user['passport_details'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Driver's License No:</span>
                                    <span><?= $user['driver_license_no'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Residential Address:</span>
                                    <span><?= $user['residential_address'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Documents:</span>
                                    <span><a href="<?= htmlspecialchars($user['document_url']) ?>" target="_blank">
                                            View Document
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-group">
                                <h4> Next Of Kin Information</h4>
                                <div class="detail-item">
                                    <span>Next of Kin:</span>
                                    <span><?= $user['emergency_contact_name'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Next of Kin Relationship to Employee:</span>
                                    <span><?= $user['next_of_kin_relationship'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Next of Kin Address & Contact Tel Nos.:</span>
                                    <span><?= $user['emergency_contact_number'] ?></span>
                                </div>
                            </div>

                            <div class="detail-group">
                                <h4> Family Information</h4>
                                <div class="detail-item">
                                    <span>Spouse Name:</span>
                                    <span><?= $user['spouse_name'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Own children:</span>
                                    <span><?= $user['own_children'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Own Dependants:</span>
                                    <span><?= $user['own_dependants'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Marital Status:</span>
                                    <span><?= $user['marital_status'] ?></span>
                                </div>
                            </div>
                            <div class="detail-group">
                                <h4> Finance Information</h4>
                                <div class="detail-item">
                                    <span>Banking Details:</span>
                                    <span><?= $user['banking_details'] ?></span>
                                </div>
                            </div>
                            <div class="detail-group">
                                <h4> Department Information</h4>
                                <div class="detail-item">
                                    <span>Job Title:</span>
                                    <span><?= $user['job_title'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Department:</span>
                                    <span><?= $user['department'] ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Manager:</span>
                                    <span>
                                        <?php
                                        $manager = get_user_by_id($conn, $user['manager_id']);
                                        echo $manager ? $manager['first_name'] . ' ' . $manager['last_name'] : 'None';
                                        ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>

       

    </html>
<?php } else {
    header("Location: login.php");
    exit();
} ?>