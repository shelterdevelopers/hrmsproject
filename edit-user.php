<?php
session_start();
// Load database connection first
include "DB_connection.php";

// Allow Admin and HR to edit users
require_once "app/Model/RoleHelper.php";
$is_admin = RoleHelper::is_admin($conn, $_SESSION['employee_id'] ?? 0);
$is_hr = RoleHelper::is_hr($conn, $_SESSION['employee_id'] ?? 0);

if (isset($_SESSION['employee_id']) && ($is_admin || $is_hr)) {
    include "app/Model/User.php";
    include "app/Model/Attendance.php";

    if (!isset($_GET['id'])) {
        header("Location: user.php");
        exit();
    }
    $id = $_GET['id'];
    $user = get_user_by_id($conn, $id);

    if ($user == 0) {
        header("Location: user.php");
        exit();
    }

    // Get attendance data for the current month
    // Get attendance data for the current month
    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');
    $attendance_data = Attendance::get_employee_attendance_json($conn, $id, $month, $year);
    $attendance_map = [];

    // Create attendance map from the fetched data
    foreach ($attendance_data['calendar'] as $week) {
        foreach ($week as $day) {
            if ($day && isset($day['attendance'])) {
                $attendance_map[$day['day']] = $day['attendance'];
            }
        }
    }
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Edit User · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/style.css">
        <style>
            /* Tab Styles */
            .tab-container {
                margin-top: 30px;
                border: 1px solid #ddd;
                border-radius: 5px;
                overflow: hidden;
            }

            .tabs {
                display: flex;
                background: #f8f9fa;
                border-bottom: 1px solid #ddd;
            }

            .tab-btn {
                padding: 10px 20px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 16px;
                border-right: 1px solid #ddd;
            }

            .tab-btn.active {
                background: #2596be;
                color: white;
            }

            .tab-content {
                display: none;
                padding: 20px;
            }

            .tab-content.active {
                display: block;
            }

            /* Calendar Styles */
            .calendar-navigation {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .attendance-calendar {
                width: 100%;
                border-collapse: collapse;
            }

            .attendance-calendar th {
                background: #2596be;
                color: white;
                padding: 10px;
                text-align: center;
            }

            .attendance-calendar td {
                border: 1px solid #ddd;
                height: 40px;
                text-align: center;
                position: relative;
                width: 14.28%;
            }

            .attendance-calendar td.empty {
                background: #f8f8f8;
            }

            .attendance-calendar td.day {
                cursor: pointer;
            }

            .attendance-calendar td.present {
                background: #d4edda;
            }

            .attendance-calendar td.absent {
                background: #f8d7da;
            }

            .attendance-calendar td.late {
                background: #fff3cd;
            }

            .attendance-calendar td.leave {
                background: #cce5ff;
            }

            .attendance-marker {
                position: absolute;
                bottom: 2px;
                left: 50%;
                transform: translateX(-50%);
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #2596be;
            }

            .attendance-stats {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
            }

            .attendance-stats h4 {
                margin-top: 0;
                color: #2596be;
            }
        </style>
    </head>

    <body>
        <input type="checkbox" id="checkbox">
        <?php include "inc/header.php" ?>
        <div class="body">
            <?php include "inc/nav.php" ?>
            <section class="section-1">
                <h4 class="title">Edit User <a href="user.php" class="btn">Back to Users</a></h4>

                <div class="tab-container">
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="profile">Employee Profile</button>
                        <button class="tab-btn" data-tab="attendance">Attendance</button>
                    </div>

                    <div class="tab-content active" id="profile-tab">
                        <form class="form-1" method="POST" action="app/update-user.php">
                            <input type="hidden" name="employee_id" value="<?= $user['employee_id'] ?>">

                            <?php if (isset($_GET['error'])) { ?>
                                <div class="danger"><?= htmlspecialchars($_GET['error']) ?></div>
                            <?php } ?>

                            <?php if (isset($_GET['success'])) { ?>
                                <div class="success"><?= htmlspecialchars($_GET['success']) ?></div>
                            <?php } ?>

                            <div class="form-row">
                                <div class="input-holder">
                                    <label>First Name</label>
                                    <input type="text" name="first_name"
                                        value="<?= htmlspecialchars($user['first_name']) ?>" class="input-1" required>
                                </div>
                                <div class="input-holder">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>"
                                        class="input-1" required>
                                </div>
                            </div>

                            <div class="input-holder">
                                <label>Maiden Name</label>
                                <input type="text" name="maiden_name" value="<?= htmlspecialchars($user['maiden_name']) ?>"
                                    class="input-1">
                            </div>

                            <div class="input-holder">
                                <label>ID Number</label>
                                <input type="text" name="id_no" value="<?= htmlspecialchars($user['id_no']) ?>"
                                    class="input-1" required>
                            </div>

                            <div class="form-row">
                                <div class="input-holder">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth"
                                        value="<?= htmlspecialchars($user['date_of_birth']) ?>" class="input-1" required>
                                </div>
                                <div class="input-holder">
                                    <label>Gender</label>
                                    <select name="gender" class="input-1" required>
                                        <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>Female
                                        </option>
                                        <option value="other" <?= $user['gender'] == 'other' ? 'selected' : '' ?>>Other
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="input-holder">
                                <label>Email Address</label>
                                <input type="email" name="email_address"
                                    value="<?= htmlspecialchars($user['email_address']) ?>" class="input-1" required>
                            </div>

                            <div class="input-holder">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number"
                                    value="<?= htmlspecialchars($user['phone_number']) ?>" class="input-1" required>
                            </div>

                            <div class="input-holder">
                                <label>Residential Address</label>
                                <textarea name="residential_address" class="input-1"
                                    required><?= htmlspecialchars($user['residential_address']) ?></textarea>
                            </div>

                            <div class="input-holder">
                                <label>Postal Address</label>
                                <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>"
                                    class="input-1">
                            </div>

                            <div class="form-row">
                                <div class="input-holder">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name"
                                        value="<?= htmlspecialchars($user['emergency_contact_name']) ?>" class="input-1"
                                        required>
                                </div>
                                <div class="input-holder">
                                    <label>Emergency Contact Number</label>
                                    <input type="text" name="emergency_contact_number"
                                        value="<?= htmlspecialchars($user['emergency_contact_number']) ?>" class="input-1"
                                        required>
                                </div>
                            </div>

                            <div class="input-holder">
                                <label>Passport Details</label>
                                <input type="text" name="passport_details"
                                    value="<?= htmlspecialchars($user['passport_details']) ?>" class="input-1">
                            </div>

                            <div class="input-holder">
                                <label>Driver's License No</label>
                                <input type="text" name="driver_license_no"
                                    value="<?= htmlspecialchars($user['driver_license_no']) ?>" class="input-1">
                            </div>

                            <div class="input-holder">
                                <label>Marital Status</label>
                                <select name="marital_status" class="input-1">
                                    <option value="">-- Select --</option>
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

                            <div class="input-holder" id="spouse-field"
                                style="display:<?= $user['marital_status'] == 'Married' ? 'block' : 'none' ?>;">
                                <label>Spouse's Name</label>
                                <input type="text" name="spouse_name" value="<?= htmlspecialchars($user['spouse_name']) ?>"
                                    class="input-1">
                            </div>

                            <div class="input-holder">
                                <label>Number of Dependents</label>
                                <input type="number" name="own_dependants" class="input-1" min="0" step="1"
                                    value="<?= htmlspecialchars((string)($user['own_dependants'] ?? '')) ?>">
                            </div>

                            <div class="input-holder">
                                <label>Number of Children</label>
                                <input type="number" name="own_children" class="input-1" min="0" step="1"
                                    value="<?= htmlspecialchars((string)($user['own_children'] ?? '')) ?>">
                            </div>

                            <div class="input-holder">
                                <label>Relationship</label>
                                <input type="text" name="next_of_kin_relationship"
                                    value="<?= htmlspecialchars($user['next_of_kin_relationship']) ?>" class="input-1"
                                    required>
                            </div>

                            <div class="input-holder">
                                <label>Banking Details</label>
                                <textarea name="banking_details" class="input-1"
                                    required><?= htmlspecialchars($user['banking_details']) ?></textarea>
                            </div>

                            <div class="input-holder">
                                <label>Job Title</label>
                                <input type="text" name="job_title" value="<?= htmlspecialchars($user['job_title']) ?>"
                                    class="input-1" required>
                            </div>

                            <div class="input-holder">
                                <label>Department</label>
                                <select name="department" class="input-1" required>
                                    <option value="OPERATIONS" <?= $user['department'] == 'OPERATIONS' ? 'selected' : '' ?>>
                                        OPERATIONS</option>
                                    <option value="CORPORATE SERVICES" <?= in_array($user['department'] ?? '', ['COOPERATE AFFAIRS', 'CORPORATE SERVICES']) ? 'selected' : '' ?>>CORPORATE SERVICES</option>
                                    <option value="SALES AND MARKETING" <?= $user['department'] == 'SALES AND MARKETING' ? 'selected' : '' ?>>SALES AND MARKETING</option>
                                    <option value="FINANCE AND ACCOUNTS" <?= $user['department'] == 'FINANCE AND ACCOUNTS' ? 'selected' : '' ?>>FINANCE AND ACCOUNTS</option>
                                    <option value="ETOSHA" <?= $user['department'] == 'ETOSHA' ? 'selected' : '' ?>>ETOSHA
                                    </option>
                                </select>
                            </div>

                            <div class="input-holder">
                                <label>Manager</label>
                                <select name="manager_id" class="input-1">
                                    <option value="">-- Select Manager --</option>
                                    <?php
                                    $managers = get_all_managers($conn);
                                    if (!empty($managers)):
                                        foreach ($managers as $manager):
                                    ?>
                                            <option value="<?= $manager['employee_id'] ?>"
                                                <?= $manager['employee_id'] == $user['manager_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?>
                                                (<?= htmlspecialchars($manager['job_title']) ?>)
                                            </option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>

                            <div class="input-holder">
                                <label>Date of Hire</label>
                                <input type="date" name="date_of_hire"
                                    value="<?= htmlspecialchars($user['date_of_hire']) ?>" class="input-1" required>
                            </div>

                            <div class="input-holder">
                                <label>Employment Type</label>
                                <select name="employment_type" class="input-1" required>
                                    <option value="Full-time" <?= $user['employment_type'] == 'Full-time' ? 'selected' : '' ?>>
                                        Full-time</option>
                                    <option value="Part-time" <?= $user['employment_type'] == 'Part-time' ? 'selected' : '' ?>>
                                        Part-time</option>
                                    <option value="Contract" <?= $user['employment_type'] == 'Contract' ? 'selected' : '' ?>>
                                        Contract</option>
                                </select>
                            </div>

                            <div class="input-holder">
                                <label>Status</label>
                                <select name="status" class="input-1" required>
                                    <option value="pending" <?= strtolower($user['status']) == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Active" <?= $user['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Terminated" <?= $user['status'] == 'Terminated' ? 'selected' : '' ?>>
                                        Terminated</option>
                                    <option value="On Leave" <?= $user['status'] == 'On Leave' ? 'selected' : '' ?>>On Leave
                                    </option>
                                </select>
                            </div>

                            <div class="input-holder">
                                <label>Work Location</label>
                                <select name="work_location" class="input-1" required>
                                    <option value="Remote" <?= $user['work_location'] == 'Remote' ? 'selected' : '' ?>>Remote
                                    </option>
                                    <option value="Onsite" <?= $user['work_location'] == 'Onsite' ? 'selected' : '' ?>>Onsite
                                    </option>
                                    <option value="Hq" <?= $user['work_location'] == 'Hq' ? 'selected' : '' ?>>Head Office
                                    </option>
                                </select>
                            </div>

                            <div class="input-holder">
                                <label>Username</label>
                                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                                    class="input-1" required>
                            </div>

                            <div class="input-holder">
                                <label>Password</label>
                                <input type="password" name="password" class="input-1"
                                    placeholder="Leave blank to keep current">
                            </div>

                            <div class="input-holder">
                                <label>Basic Salary ($)</label>
                                <input type="number" name="basic_salary"
                                    value="<?= htmlspecialchars($user['basic_salary']) ?>" class="input-1" step="0.01"
                                    min="0" required>
                            </div>

                            <div class="input-holder">
                                <label>Role</label>
                                <select name="role" class="input-1" required>
                                    <option value="employee" <?= $user['role'] == 'employee' ? 'selected' : '' ?>>Employee
                                    </option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="input-holder">
                                <label>
                                    <input type="hidden" name="executive_member" value="0">

                                    <input type="checkbox" name="executive_member" value="1" <?= ($user['executive_member'] == 1) ? 'checked' : '' ?>>
                                    Executive Member
                                </label>
                            </div>

                            <button type="submit" class="btn">Update User</button>
                        </form>
                    </div>

                    <div class="tab-content" id="attendance-tab">
                        <div class="calendar-navigation">
                            <a href="#" class="btn">Previous</a>
                            <h3><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></h3>
                            <a href="#" class="btn">Next</a>
                        </div>

                        <div class="calendar-view">
                            <table class="attendance-calendar">
                                <thead>
                                    <tr>
                                        <th>Sun</th>
                                        <th>Mon</th>
                                        <th>Tue</th>
                                        <th>Wed</th>
                                        <th>Thu</th>
                                        <th>Fri</th>
                                        <th>Sat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_data['calendar'] as $week): ?>
                                        <tr>
                                            <?php foreach ($week as $day): ?>
                                                <?php if ($day === null): ?>
                                                    <td class="empty"></td>
                                                <?php else: ?>
                                                    <td class="day <?= $day['attendance']['status'] ?? '' ?>"
                                                        title="<?= isset($day['attendance']) ? 'Check-in: ' . $day['attendance']['check_in'] . '\nCheck-out: ' . $day['attendance']['check_out'] : '' ?>">
                                                        <?= $day['day'] ?>
                                                        <?php if (isset($day['attendance'])): ?>
                                                            <div class="attendance-marker"></div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="attendance-stats">
                            <h4>Attendance Summary</h4>
                            <p>Days Present: <?= $attendance_data['stats']['present'] ?></p>
                            <p>Days Absent: <?= $attendance_data['stats']['absent'] ?></p>
                            <p>Days on Leave: <?= $attendance_data['stats']['leave'] ?></p>
                        </div>
                    </div>
                </div>
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

            // Tab functionality
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons and content
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    btn.classList.add('active');
                    const tabId = btn.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });

            // Attendance calendar navigation
            let currentMonth = <?= $month ?>;
            let currentYear = <?= $year ?>;
            const employeeId = <?= $id ?>;

            function loadAttendanceData(month, year) {
                // Show loading state
                const calendarBody = document.querySelector('.attendance-calendar tbody');
                calendarBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Loading...</td></tr>';

                fetch(`app/get_attendance.php?employee_id=${employeeId}&month=${month}&year=${year}`)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) throw new Error(data.error);

                        // Update month display
                        const monthTitle = document.querySelector('#attendance-tab h3');
                        if (monthTitle) monthTitle.textContent = data.month_name;

                        // Update calendar
                        calendarBody.innerHTML = '';

                        data.calendar.forEach(week => {
                            const row = document.createElement('tr');

                            week.forEach(day => {
                                const cell = document.createElement('td');

                                if (!day) {
                                    cell.className = 'empty';
                                } else {
                                    cell.className = 'day';
                                    if (day.attendance) {
                                        cell.classList.add(day.attendance.status || '');
                                        cell.title = `Check-in: ${day.attendance.check_in || 'N/A'}\nCheck-out: ${day.attendance.check_out || 'N/A'}`;
                                        cell.innerHTML = `${day.day}<div class="attendance-marker"></div>`;
                                    } else {
                                        cell.textContent = day.day;
                                    }
                                }

                                row.appendChild(cell);
                            });

                            calendarBody.appendChild(row);
                        });

                        // Safely update stats - check if elements exist first
                        const statsContainer = document.querySelector('.attendance-stats');
                        if (statsContainer) {
                            statsContainer.innerHTML = `
                    <h4>Attendance Summary</h4>
                    <p>Days Present: ${data.stats.present || 0}</p>
                    <p>Days Absent: ${data.stats.absent || 0}</p>
                    <p>Days Late: ${data.stats.late || 0}</p>
                    <p>Days on Leave: ${data.stats.leave || 0}</p>
                `;
                        }

                        currentMonth = month;
                        currentYear = year;
                    })
                    .catch(error => {
                        console.error('Error loading attendance data:', error);
                        calendarBody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">Error: ${error.message}</td></tr>`;
                    });
            }
            // Navigation button handlers
            document.querySelector('#attendance-tab .btn:nth-child(1)').addEventListener('click', function(e) {
                e.preventDefault();
                let newMonth = currentMonth - 1;
                let newYear = currentYear;
                if (newMonth <= 0) {
                    newMonth = 12;
                    newYear--;
                }
                loadAttendanceData(newMonth, newYear);
            });

            document.querySelector('#attendance-tab .btn:nth-child(3)').addEventListener('click', function(e) {
                e.preventDefault();
                let newMonth = currentMonth + 1;
                let newYear = currentYear;
                if (newMonth > 12) {
                    newMonth = 1;
                    newYear++;
                }
                loadAttendanceData(newMonth, newYear);
            });
        </script>
    </body>

    </html>
<?php } else {
    header("Location: login.php?error=First login");
    exit();
}
?>