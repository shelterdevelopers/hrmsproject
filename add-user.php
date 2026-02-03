<?php
session_start();
// Only Admin can add users (employee registration is admin responsibility)
if (isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    require_once "app/Model/RoleHelper.php";
    $is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id']);
    
    if ($is_admin) {
        include "app/Model/User.php";
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Add User · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
    </head>

    <body class="add-user-page">
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php"; ?>
        <div class="body">
            <?php include "inc/nav.php"; ?>
            <section class="section-1 add-user-section">
                <h4 class="title">Add Users</h4>
                <form class="form-1 add-user-form" method="POST" action="app/add-user.php">
                    <?php if (isset($_GET['error'])) { ?>
                        <div class="danger" role="alert">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php } ?>

                    <?php if (isset($_GET['success'])) { ?>
                        <div class="success" role="alert">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php } ?>

                    <div class="form-row">
                        <div class="input-holder">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="input-1" placeholder="First Name" required>
                        </div>
                        <div class="input-holder">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="input-1" placeholder="Last Name" required>
                        </div>
                    </div>

                    <div class="input-holder">
                        <label>Maiden Name</label>
                        <input type="text" name="maiden_name" class="input-1" placeholder="Maiden Name (if applicable)">
                    </div>

                    <div class="input-holder">
                        <label>ID Number</label>
                        <input type="text" name="id_no" class="input-1" placeholder="National ID/Passport Number" required>
                    </div>

                    <div class="input-holder">
                        <label>Username</label>
                        <input type="text" name="username" class="input-1" placeholder="Username" required>
                    </div>

                    <div class="input-holder">
                        <label>Password</label>
                        <input type="password" name="password" class="input-1" placeholder="Password" required>
                    </div>

                    <div class="input-holder">
                        <label>Email Address</label>
                        <input type="email" name="email_address" class="input-1" placeholder="Email Address" required>
                    </div>

                    <div class="input-holder">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="input-1" placeholder="Phone Number" required>
                    </div>

                    <div class="input-holder">
                        <label>Residential Address</label>
                        <textarea name="residential_address" class="input-1" placeholder="Current residential address"
                            required></textarea>
                    </div>



                    <div class="form-row">
                        <div class="input-holder">
                            <label>Next of Kin Name</label>
                            <input type="text" name="emergency_contact_name" class="input-1" placeholder="Full Name"
                                required>
                        </div>
                        <div class="input-holder">
                            <labelNext of Kin Contact Number</label>
                                <input type="text" name="emergency_contact_number" class="input-1" placeholder="Phone Number"
                                    required>
                        </div>
                    </div>
                    <!-- Add this field below the emergency contact information -->
                    <div class="input-holder">
                        <label>Next of Kin Relationship</label>
                        <input type="text" name="next_of_kin_relationship" class="input-1"
                            placeholder="Relationship to employee" required>
                    </div>

                    <div class="form-row">
                        <div class="input-holder">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="input-1" required>
                        </div>
                        <div class="input-holder">
                            <label>Gender</label>
                            <select name="gender" class="input-1" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>

                            </select>
                        </div>
                    </div>

                    <div class="input-holder">
                        <label>Passport Details</label>
                        <input type="text" name="passport_details" class="input-1"
                            placeholder="Passport Number (if applicable)">
                    </div>

                    <div class="input-holder">
                        <label>Driver's License No</label>
                        <input type="text" name="driver_license_no" class="input-1" placeholder="Driver's License Number">
                    </div>

                    <div class="input-holder">
                        <label>Marital Status</label>
                        <select name="marital_status" class="input-1">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>

                    <div class="input-holder" id="spouse-field" style="display:none;">
                        <label>Spouse's Name</label>
                        <input type="text" name="spouse_name" class="input-1" placeholder="Spouse's Full Name">
                    </div>

                    <div class="input-holder">
                        <label>Number of Dependents</label>
                        <input type="number" name="own_dependants" class="input-1" min="0" step="1" placeholder="e.g., 0">
                    </div>

                    <div class="input-holder">
                        <label>Number of Children</label>
                        <input type="number" name="own_children" class="input-1" min="0" step="1" placeholder="e.g., 0">
                    </div>

                    <div class="input-holder">
                        <label>Banking Details</label>
                        <textarea name="banking_details" class="input-1" placeholder="Bank Name, Account Number, Branch"
                            required></textarea>
                    </div>

                    <div class="input-holder">
                        <label>Job Title</label>
                        <input type="text" name="job_title" class="input-1" placeholder="Job Title" required>
                    </div>

                    <div class="input-holder">
                        <label>Department</label>
                        <select name="department" class="input-1" required>
                            <option value="">-- Select Department --</option>
                            <option value="OPERATIONS">OPERATIONS</option>
                            <option value="CORPORATE SERVICES">CORPORATE SERVICES</option>
                            <option value="SALES AND MARKETING">SALES AND MARKETING</option>
                            <option value="FINANCE AND ACCOUNTS">FINANCE AND ACCOUNTS</option>
                            <option value="ETOSHA">ETOSHA</option>
                        </select>
                    </div>

                    <div class="input-holder">
                        <label>Manager (Department Heads)</label>
                        <select name="manager_id" class="input-1">
                            <option value="">-- Select Manager --</option>
                            <?php
                            $managers = get_all_managers($conn);
                            if (!empty($managers)):
                                foreach ($managers as $manager):
                            ?>
                                    <option value="<?= $manager['employee_id'] ?>">
                                        <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?>
                                        (<?= htmlspecialchars($manager['job_title']) ?>)
                                    </option>
                            <?php endforeach;
                            endif; ?>
                        </select>
                    </div>
                    <div class="input-holder">
                        <label>Date of Hire</label>
                        <input type="date" name="date_of_hire" class="input-1" required>
                    </div>

                    <div class="input-holder">
                        <label>Employment Type</label>
                        <select name="employment_type" class="input-1" required>
                            <option value="">-- Select Employment Type --</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                        </select>
                    </div>

                    <div class="input-holder">
                        <label>Employment Status</label>
                        <select name="status" class="input-1" required>
                            <option value="">-- Select Status --</option>
                            <option value="Active">Active</option>
                            <option value="Terminated">Terminated</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>

                    <div class="input-holder">
                        <label>Work Location</label>
                        <select name="work_location" class="input-1" required>
                            <option value="">-- Select Work Location --</option>
                            <option value="Remote">Remote</option>
                            <option value="Onsite">Onsite</option>
                            <option value="Hq">Head Office</option>
                        </select>
                    </div>

                    <div class="input-holder">
                        <label>Role</label>
                        <select name="role" class="input-1" required>
                            <option value="employee">Employee</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="input-holder">
                        <label>OneDrive Documents Link</label>
                        <input type="text" name="document_url" class="input-1" placeholder="Insert Url" required>
                    </div>
                    
                    <div class="input-holder">
                        <label>Basic Salary ($)</label>
                        <input type="number" name="basic_salary" class="input-1" step="0.01" min="0" required>
                    </div>
                    <div class="input-holder">
                        <label>
                            <input type="checkbox" name="executive_member" value="1">
                            Executive Member
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Add User</button>
                        <a href="user.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </section>
        </div>

        <script>
            // Show/hide spouse name field based on marital status
            document.querySelector('[name="marital_status"]').addEventListener('change', function() {
                const spouseField = document.getElementById('spouse-field');
                spouseField.style.display = this.value === 'Married' ? 'block' : 'none';
            });

            // Set active nav item
            document.querySelector("#navList li:nth-child(2)").classList.add("active");
        </script>
    </body>

    </html>
<?php 
    } else {
        header("Location: login.php?error=Access+denied.+Only+System+Admin+can+register+employees");
        exit();
    }
} else {
    header("Location: login.php?error=Please+login+first");
    exit();
}
?>