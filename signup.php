<?php
session_start();
require_once "DB_connection.php";
require_once "app/Model/User.php";

$error = '';
$success = '';
$old = $_POST ?? [];

function old_value($key, $default = '')
{
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function normalize_optional_int($value)
{
    if ($value === null) return null;
    $v = trim((string)$value);
    if ($v === '' || strtolower($v) === 'n/a' || strtolower($v) === 'na') return null;
    // Accept only non-negative integers
    if (!preg_match('/^\d+$/', $v)) return null;
    return (int)$v;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = [
        'first_name',
        'last_name',
        'id_no',
        'date_of_birth',
        'gender',
        'email_address',
        'phone_number',
        'residential_address',
        'emergency_contact_name',
        'emergency_contact_number',
        'next_of_kin_relationship',
        'username',
        'password',
        'document_url',
        'job_title',
        'date_of_hire'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = "All required fields must be filled";
            break;
        }
    }

    if (empty($error)) {
        $id_no = trim($_POST['id_no']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email_address']);
        $phone_number = trim($_POST['phone_number']);
        $password = $_POST['password'] ?? '';
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $date_of_hire = $_POST['date_of_hire'] ?? '';
        
        // Validate ID Number Format (Zimbabwe format: 24-2017358 V 26)
        if (!preg_match('/^\d{2}-\d{7} [A-Z] \d{2}$/', $id_no)) {
            $error = "ID number must be in the format: 24-2017358 V 26 (2 digits, hyphen, 7 digits, space, letter, space, 2 digits)";
        }
        
        // Validate Email Format
        if (empty($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        }
        
        // Validate Phone Number (Zimbabwe format: +263 or 0 followed by 9 digits)
        if (empty($error) && !preg_match('/^(\+263|0)[0-9]{9}$/', $phone_number)) {
            $error = "Phone number must be in Zimbabwe format: +263XXXXXXXXX or 0XXXXXXXXX (9 digits after country code or 0)";
        }
        
        // Validate Username (alphanumeric, 3-20 characters)
        if (empty($error) && !preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
            $error = "Username must be 3-20 characters long and contain only letters and numbers (no spaces or special characters).";
        }
        
        // Validate Password (minimum 8 characters, at least one letter and one number)
        if (empty($error) && (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password))) {
            $error = "Password must be at least 8 characters long and contain at least one letter and one number.";
        }
        
        // Validate Date of Birth (must be in the past and reasonable age)
        if (empty($error) && !empty($date_of_birth)) {
            $dob = new DateTime($date_of_birth);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($dob >= $today) {
                $error = "Date of birth must be in the past.";
            } elseif ($age < 16) {
                $error = "You must be at least 16 years old to register.";
            } elseif ($age > 100) {
                $error = "Please enter a valid date of birth.";
            }
        }
        
        // Date of Hire validation removed - can be in the past
        
        // Check for duplicate id_no, username, or email before attempting insert
        if (empty($error)) {
            $check_id = $conn->prepare("SELECT 1 FROM employee WHERE id_no = ? LIMIT 1");
            $check_id->execute([$id_no]);
            if ($check_id->fetch()) {
                $error = "This ID number is already registered. Please use a different ID or contact HR if you believe this is an error.";
            } else {
                $check_username = $conn->prepare("SELECT 1 FROM employee WHERE username = ? LIMIT 1");
                $check_username->execute([$username]);
                if ($check_username->fetch()) {
                    $error = "This username is already taken. Please choose a different username.";
                } else {
                    $check_email = $conn->prepare("SELECT 1 FROM employee WHERE email_address = ? LIMIT 1");
                    $check_email->execute([$email]);
                    if ($check_email->fetch()) {
                        $error = "This email address is already registered. Please use a different email or contact HR.";
                    }
                }
            }
        }
    }

    if (empty($error)) {
        try {
            // Prepare employee data with defaults for signup
            $data = [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'maiden_name' => $_POST['maiden_name'] ?? null,
                'id_no' => trim($_POST['id_no']),
                'date_of_birth' => $_POST['date_of_birth'],
                'gender' => $_POST['gender'],
                'email_address' => $_POST['email_address'],
                'phone_number' => $_POST['phone_number'],
                'address' => $_POST['address'] ?? null, // Postal address (optional)
                'residential_address' => $_POST['residential_address'],
                'emergency_contact_name' => $_POST['emergency_contact_name'],
                'emergency_contact_number' => $_POST['emergency_contact_number'],
                'next_of_kin_relationship' => $_POST['next_of_kin_relationship'],
                'passport_details' => $_POST['passport_details'] ?? null,
                'driver_license_no' => $_POST['driver_license_no'] ?? null,
                'marital_status' => $_POST['marital_status'] ?? null,
                'spouse_name' => $_POST['spouse_name'] ?? null,
                'own_children' => normalize_optional_int($_POST['own_children'] ?? null),
                'own_dependants' => normalize_optional_int($_POST['own_dependants'] ?? null),
                'banking_details' => $_POST['banking_details'] ?? 'Pending',
                'username' => $_POST['username'],
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'document_url' => $_POST['document_url'],
                'job_title' => $_POST['job_title'],
                'date_of_hire' => $_POST['date_of_hire'],
                // Default values that would normally be set by admin
                'employment_type' => 'Pending',
                'status' => 'pending',
                'work_location' => 'Pending',
                'role' => 'employee',
                'basic_salary' => 0
            ];

            // Store the data in session for admin approval
            $_SESSION['pending_user'] = $data;
            if (insert_user($conn, $data)) {
                $success = "Your application has been submitted for admin approval";
            } else {
                $error = "Failed to submit application";
            }

        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'id_no') !== false) {
                    $error = "This ID number is already registered. Please use a different ID or contact HR if you believe this is an error.";
                } elseif (strpos($e->getMessage(), 'username') !== false) {
                    $error = "This username is already taken. Please choose a different username.";
                } elseif (strpos($e->getMessage(), 'email_address') !== false) {
                    $error = "This email address is already registered. Please use a different email or contact HR.";
                } else {
                    $error = "This ID number, username, or email is already registered. Please use different details or contact HR.";
                }
            } else {
                $error = "Error during registration. Please try again or contact HR.";
            }
        } catch (Exception $e) {
            $error = "Error during registration. Please try again or contact HR.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/signup.css">
</head>

<body>
    <div class="welcome-banner">
        <h1>Welcome to Shelter Human Resources Management System</h1>
        <p>Create Your Account</p>
    </div>
    <div class="signup-container">
        <h2 class="title">Employee Signup</h2>

        <?php if ($error): ?>
            <div class="alert danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <p class="login-link">Return to <a href="login.php">Login page</a></p>
        <?php else: ?>
            <?php
                $terms_checked = isset($old['terms_agreement']) && $old['terms_agreement'] === 'agree';
                $marital_is_married = isset($old['marital_status']) && $old['marital_status'] === 'Married';
            ?>

            <form class="form-1" method="POST" action="signup.php">
                <div class="form-grid">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="input-holder">
                            <label>First Name*</label>
                            <input type="text" name="first_name" class="input-1" required value="<?= old_value('first_name') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Last Name*</label>
                            <input type="text" name="last_name" class="input-1" required value="<?= old_value('last_name') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Maiden Name (if applicable)</label>
                            <input type="text" name="maiden_name" class="input-1" value="<?= old_value('maiden_name') ?>">
                        </div>
                        <div class="input-holder">
                            <label>ID Number*</label>
                            <input type="text" name="id_no" id="id_no" class="input-1" 
                                   placeholder="24-2017358 V 26" 
                                   pattern="\d{2}-\d{7} [A-Z] \d{2}"
                                   title="Format: 24-2017358 V 26 (2 digits, hyphen, 7 digits, space, letter, space, 2 digits)"
                                   required value="<?= old_value('id_no') ?>">
                            <small class="form-help">Format: 24-2017358 V 26</small>
                        </div>
                        <div class="input-holder">
                            <label>Date of Birth*</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="input-1" 
                                   max="<?= date('Y-m-d', strtotime('-16 years')) ?>"
                                   required value="<?= old_value('date_of_birth') ?>">
                            <small class="form-help">Must be at least 16 years old</small>
                        </div>
                        <div class="input-holder">
                            <label>Gender*</label>
                            <select name="gender" class="input-1" required>
                                <option value="male" <?= (old_value('gender') === 'male') ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= (old_value('gender') === 'female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="input-holder">
                            <label>Job Title*</label>
                            <input type="text" name="job_title" class="input-1" placeholder="e.g., Sales Representative"
                                required value="<?= old_value('job_title') ?>">
                        </div>

                        <div class="input-holder">
                            <label>Date of Hire*</label>
                            <input type="date" name="date_of_hire" id="date_of_hire" class="input-1" 
                                   required value="<?= old_value('date_of_hire') ?>">
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section">
                        <h3>Contact Information</h3>
                        <div class="input-holder">
                            <label>Email Address*</label>
                            <input type="email" name="email_address" id="email_address" class="input-1" 
                                   placeholder="example@email.com" 
                                   required value="<?= old_value('email_address') ?>">
                            <small class="form-help">Enter a valid email address</small>
                        </div>
                        <div class="input-holder">
                            <label>Phone Number*</label>
                            <input type="text" name="phone_number" id="phone_number" class="input-1" 
                                   placeholder="+263771234567 or 0771234567" 
                                   pattern="(\+263|0)[0-9]{9}"
                                   title="Zimbabwe format: +263XXXXXXXXX or 0XXXXXXXXX"
                                   required value="<?= old_value('phone_number') ?>">
                            <small class="form-help">Format: +263XXXXXXXXX or 0XXXXXXXXX</small>
                        </div>
                        <div class="input-holder">
                            <label>Residential Address*</label>
                            <textarea name="residential_address" class="input-1" required><?= old_value('residential_address') ?></textarea>
                        </div>
                        <div class="input-holder">
                            <label>Postal Address</label>
                            <input type="text" name="address" class="input-1" value="<?= old_value('address') ?>">
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="form-section">
                        <h3>Emergency Contact</h3>
                        <div class="input-holder">
                            <label>Next of Kin Name*</label>
                            <input type="text" name="emergency_contact_name" class="input-1" required value="<?= old_value('emergency_contact_name') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Next of Kin Contact*</label>
                            <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="input-1" 
                                   placeholder="+263771234567 or 0771234567"
                                   pattern="(\+263|0)[0-9]{9}"
                                   title="Zimbabwe format: +263XXXXXXXXX or 0XXXXXXXXX"
                                   required value="<?= old_value('emergency_contact_number') ?>">
                            <small class="form-help">Format: +263XXXXXXXXX or 0XXXXXXXXX</small>
                        </div>
                        <div class="input-holder">
                            <label>Relationship*</label>
                            <input type="text" name="next_of_kin_relationship" class="input-1" required value="<?= old_value('next_of_kin_relationship') ?>">
                        </div>
                    </div>

                    <!-- Document Information -->
                    <div class="form-section">
                        <h3>Document Information</h3>
                        <div class="input-holder">
                            <label>Passport Details</label>
                            <input type="text" name="passport_details" class="input-1" placeholder="Passport Number" value="<?= old_value('passport_details') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Driver's License No</label>
                            <input type="text" name="driver_license_no" class="input-1"
                                placeholder="Driver's License Number" value="<?= old_value('driver_license_no') ?>">
                        </div>
                        <div class="input-holder">
                            <label>OneDrive Document Link*</label>
                            <input type="text" name="document_url" class="input-1" required value="<?= old_value('document_url') ?>">
                            <div class="tooltip">Please ensure that you submit copies of the following; Certified copies of
                                Academic and Professional certificates, National Identity Card or Passport, Driver's
                                License(if you have), Spouse's I.D or Marriage Certificate (if married), Children's Birth
                                Certificates/I.D. cards (if any), and or National I.D. Cards of Dependants where applicable.
                                Notify your Head of Department of any changes that may occur.</div>
                        </div>
                    </div>

                    <!-- Family Information -->
                    <div class="form-section">
                        <h3>Family Information</h3>
                        <div class="input-holder">
                            <label>Marital Status</label>
                            <select name="marital_status" class="input-1" id="marital-status">
                                <option value="">-- Select --</option>
                                <option value="Single" <?= (old_value('marital_status') === 'Single') ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= (old_value('marital_status') === 'Married') ? 'selected' : '' ?>>Married</option>
                                <option value="Divorced" <?= (old_value('marital_status') === 'Divorced') ? 'selected' : '' ?>>Divorced</option>
                                <option value="Widowed" <?= (old_value('marital_status') === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>
                        <div class="input-holder" id="spouse-field" style="<?= $marital_is_married ? 'display:block;' : 'display:none;' ?>">
                            <label>Spouse's Name</label>
                            <input type="text" name="spouse_name" class="input-1" value="<?= old_value('spouse_name') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Number of Dependents</label>
                            <input type="number" name="own_dependants" class="input-1" min="0" step="1"
                                placeholder="e.g., 0" value="<?= old_value('own_dependants') ?>">
                        </div>
                        <div class="input-holder">
                            <label>Number of Children</label>
                            <input type="number" name="own_children" class="input-1" min="0" step="1"
                                placeholder="e.g., 0" value="<?= old_value('own_children') ?>">
                        </div>
                    </div>

                    <!-- Banking Information -->
                    <div class="form-section">
                        <h3>Banking Information</h3>
                        <div class="input-holder">
                            <label>Banking Details</label>
                            <textarea name="banking_details" class="input-1"
                                placeholder="Bank Name, Account Number, Branch"><?= old_value('banking_details') ?></textarea>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="form-section">
                        <h3>Account Information</h3>
                        <div class="input-holder">
                            <label>Username*</label>
                            <input type="text" name="username" id="username" class="input-1" 
                                   placeholder="johndoe123" 
                                   pattern="[a-zA-Z0-9]{3,20}"
                                   title="3-20 characters, letters and numbers only"
                                   required value="<?= old_value('username') ?>">
                            <small class="form-help">3-20 characters, letters and numbers only (no spaces)</small>
                        </div>
                        <div class="input-holder">
                            <label>Password*</label>
                            <input type="password" name="password" id="password" class="input-1" 
                                   placeholder="Minimum 8 characters with letters and numbers"
                                   pattern=".{8,}"
                                   title="At least 8 characters with at least one letter and one number"
                                   required>
                            <small class="form-help">Minimum 8 characters, must contain at least one letter and one number</small>
                            <div id="password-strength" style="margin-top: 5px; font-size: 0.85rem;"></div>
                        </div>
                    </div>
                </div>
                <!-- Terms and Conditions Section -->
                <div class="form-section terms-section">
                    <h3>Declaration</h3>
                    <div class="terms-content">
                        <h4>SHELTER INCORPORATED (PVT) LIMITED</h4>
                        <h5>DECLARATION OF EMPLOYEES PERSONAL DETAILS FORM</h5>

                        <p>This form is to be completed by the employee. It is in the best interest of the employee to give
                            true and correct facts when completing the form.</p>
                        <p>The information given by the employee will be used by Management to verify, authenticate and
                            determine the welfare needs of the employee.</p>
                        <p>It shall be the responsibility of the employee to ensure that the record is updated at all times.
                        </p>

                        <div class="declaration-list">
                            <p>➤ I declare that the above information is true and correct and should it be proven false, I
                                accept that I will be disqualified from benefiting from all schemes designed to meet the
                                welfare needs of employees.</p>
                            <p>➤ Furthermore, I agree that appropriate disciplinary measures be taken against me in terms of
                                the Code of Conduct.</p>
                        </div>
                    </div>

                    <div class="terms-agreement">
                        <input type="checkbox" name="terms_agreement" id="terms_agreement" value="agree" required <?= $terms_checked ? 'checked' : '' ?>>
                        <label for="terms_agreement">I agree to the <a href="terms.html" target="_blank"
                                class="terms-link">Terms and Conditions</a></label>
                    </div>
                </div>

                <div id="submit-section" style="display: <?= $terms_checked ? 'block' : 'none' ?>;">
                    <button type="submit" class="btn-submit">Submit Application</button>
                </div>
                <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
            </form>

        <?php endif; ?>
    </div>

    <script>
        // Terms agreement checkbox
        document.getElementById('terms_agreement').addEventListener('change', function () {
            document.getElementById('submit-section').style.display = this.checked ? 'block' : 'none';
        });

        // Spouse field toggle
        document.getElementById('marital-status').addEventListener('change', function () {
            const spouseField = document.getElementById('spouse-field');
            spouseField.style.display = this.value === 'Married' ? 'block' : 'none';
        });

        // ID Number Auto-formatting (24-2017358 V 26)
        const idNoInput = document.getElementById('id_no');
        if (idNoInput) {
            idNoInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9A-Z-]/gi, '').toUpperCase();
                
                // Remove existing formatting
                value = value.replace(/-/g, '').replace(/\s/g, '');
                
                // Add formatting as user types
                if (value.length > 2 && value[2] !== '-') {
                    value = value.slice(0, 2) + '-' + value.slice(2);
                }
                if (value.length > 10 && value[10] !== ' ') {
                    value = value.slice(0, 10) + ' ' + value.slice(10);
                }
                if (value.length > 12 && value[12] !== ' ') {
                    value = value.slice(0, 12) + ' ' + value.slice(12);
                }
                
                // Limit to correct format length
                if (value.length > 15) {
                    value = value.slice(0, 15);
                }
                
                e.target.value = value;
            });
        }

        // Password Strength Indicator
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('password-strength');
        if (passwordInput && passwordStrength) {
            passwordInput.addEventListener('input', function(e) {
                const password = e.target.value;
                let strength = 0;
                let message = '';
                let className = '';
                
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                if (password.length === 0) {
                    message = '';
                    passwordStrength.style.display = 'none';
                } else if (strength <= 2) {
                    message = 'Weak password';
                    className = 'password-weak';
                    passwordStrength.style.display = 'block';
                } else if (strength <= 3) {
                    message = 'Medium password';
                    className = 'password-medium';
                    passwordStrength.style.display = 'block';
                } else {
                    message = 'Strong password';
                    className = 'password-strong';
                    passwordStrength.style.display = 'block';
                }
                
                passwordStrength.textContent = message;
                passwordStrength.className = className;
            });
        }

        // Real-time validation feedback
        const formInputs = document.querySelectorAll('.input-1[required]');
        formInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '';
                } else {
                    this.style.borderColor = 'var(--danger)';
                }
            });
        });

        // Email validation
        const emailInput = document.getElementById('email_address');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const email = this.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    this.setCustomValidity('Please enter a valid email address');
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.setCustomValidity('');
                    if (this.checkValidity()) {
                        this.style.borderColor = '';
                    }
                }
            });
        }

        // Phone number formatting
        const phoneInputs = document.querySelectorAll('#phone_number, #emergency_contact_number');
        phoneInputs.forEach(input => {
            if (input) {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9+]/g, '');
                    
                    // Auto-add +263 if user starts with 0
                    if (value.startsWith('0') && value.length === 10) {
                        value = '+263' + value.slice(1);
                    }
                    
                    e.target.value = value;
                });
            }
        });
    </script>
</body>

</html>