<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define BASE_URL if not already defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

// --- NEW: Get the current page filename ---
$current_page = basename($_SERVER['PHP_SELF']);

// --- Define navigation items arrays for cleaner logic ---
require_once __DIR__ . '/../app/Model/RoleHelper.php';

$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$employee_id = $_SESSION['employee_id'] ?? null;

// Get user's actual role and department from database
if ($employee_id) {
    include __DIR__ . '/../DB_connection.php';
    $actual_role = RoleHelper::get_role($conn, $employee_id);
    $department = RoleHelper::get_department($conn, $employee_id);
    $is_managing_director = RoleHelper::is_managing_director($conn, $employee_id);
    $is_hr = RoleHelper::is_hr($conn, $employee_id);
    $is_manager = RoleHelper::is_manager($conn, $employee_id);
    $is_admin = RoleHelper::is_admin($conn, $employee_id);
} else {
    $actual_role = $role;
    $department = '';
    $is_managing_director = false;
    $is_hr = false;
    $is_manager = false;
    $is_admin = false;
}

// Base navigation for all employees
$base_nav = [
    'index.php' => ['icon' => 'fa-tachometer', 'text' => 'Dashboard'],
    'app/applications.php' => ['icon' => 'fa-file-text', 'text' => 'Applications'],
    'app/attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'My Attendance'],
    'app/appraisal.php' => ['icon' => 'fa-star', 'text' => 'Appraisals'],
    'app/learning.php' => ['icon' => 'fa-graduation-cap', 'text' => 'Learning & Development'],
    'notifications.php' => ['icon' => 'fa-bell', 'text' => 'Notifications'],
];

// Admin navigation (has both admin functions AND employee functions)
if ($is_admin) {
    $nav_items = array_merge($base_nav, [
        'user.php' => ['icon' => 'fa-users', 'text' => 'Manage Users'],
        'add-user.php' => ['icon' => 'fa-user-plus', 'text' => 'Register Employee'],
        'app/all_attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'All Attendance'],
        'notifications.php' => ['icon' => 'fa-bell', 'text' => 'Notifications'],
    ]);
}
// Managing Director navigation (read-only, sees all activity, only approves)
elseif ($is_managing_director) {
    // MD doesn't apply for anything, only approves manager applications
    $nav_items = [
        'index.php' => ['icon' => 'fa-tachometer', 'text' => 'Dashboard'],
        'app/md_approvals.php' => ['icon' => 'fa-check-circle', 'text' => 'Pending Approvals'],
        'app/attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'My Attendance'],
        'app/appraisal.php' => ['icon' => 'fa-star', 'text' => 'Appraisals'],
        'notifications.php' => ['icon' => 'fa-bell', 'text' => 'Notifications'],
        'app/activity_log.php' => ['icon' => 'fa-history', 'text' => 'Activity Log'],
        'notify_all_form.php' => ['icon' => 'fa-bullhorn', 'text' => 'Company Announcements'],
        'app/company_announcements.php' => ['icon' => 'fa-newspaper-o', 'text' => 'View Announcements'],
    ];
}
// HR navigation
elseif ($is_hr) {
    // HR doesn't need regular Learning & Development tab (that's for employees)
    // HR has Learning Admin instead for managing courses
    $hr_base_nav = [
        'index.php' => ['icon' => 'fa-tachometer', 'text' => 'Dashboard'],
        'app/applications.php' => ['icon' => 'fa-file-text', 'text' => 'Applications'],
        'app/attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'My Attendance'],
        'app/appraisal.php' => ['icon' => 'fa-star', 'text' => 'Appraisals'],
        'notifications.php' => ['icon' => 'fa-bell', 'text' => 'Notifications'],
    ];
    
    $nav_items = array_merge($hr_base_nav, [
        'user.php' => ['icon' => 'fa-users', 'text' => 'Manage Users'],
        'app/all_attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'All Attendance'],
        'notify_all_form.php' => ['icon' => 'fa-bullhorn', 'text' => 'Company Announcements'],
        'app/company_announcements.php' => ['icon' => 'fa-newspaper-o', 'text' => 'View Announcements'],
        'app/learning_admin.php' => ['icon' => 'fa-graduation-cap', 'text' => 'Learning Admin'],
    ]);
}
// Manager navigation (including Finance Manager)
elseif ($is_manager) {
    // Check if Finance Manager (case-insensitive department check)
    $department_normalized = trim($department);
    $is_finance_manager = (strcasecmp($department_normalized, RoleHelper::DEPT_FINANCE) === 0);
    
    $nav_items = array_merge($base_nav, [
        'app/department_attendance.php' => ['icon' => 'fa-calendar-check-o', 'text' => 'Department Attendance'],
    ]);
    
    // Finance Manager gets applications tab for approvals
    if ($is_finance_manager) {
        $nav_items['app/applications.php'] = ['icon' => 'fa-file-text', 'text' => 'Applications'];
    }
    
    // Department managers: announcements (department + company-wide)
    $nav_items['app/department_announcement.php'] = ['icon' => 'fa-bullhorn', 'text' => 'Post Department Announcement'];
    $nav_items['app/department_announcements_view.php'] = ['icon' => 'fa-newspaper-o', 'text' => 'View Department Announcements'];
    $nav_items['app/company_announcements.php'] = ['icon' => 'fa-newspaper-o', 'text' => 'Company Announcements'];
}
// Regular employee navigation
else {
    $nav_items = array_merge($base_nav, [
        'app/department_announcements_view.php' => ['icon' => 'fa-newspaper-o', 'text' => 'Department Announcements'],
        'app/company_announcements.php' => ['icon' => 'fa-newspaper-o', 'text' => 'Company Announcements'],
    ]);
}

// Add logout at the end
$nav_items['logout.php'] = ['icon' => 'fa-sign-out', 'text' => 'Logout'];

?>
<nav class="side-bar">
    <div class="user-p">
        <a href="<?= BASE_URL ?>profile.php" style="text-decoration: none;">
            <?php
            // --- User Image Logic (Keep as is) ---
            $employee_id_nav = $_SESSION['employee_id']; // Use a different variable name to avoid conflicts if $employee_id is used elsewhere
            $user_specific_img_url_nav = BASE_URL . "img/user" . $employee_id_nav . ".png";
            $default_img_url_nav = BASE_URL . "img/user.png";
            $user_specific_img_filepath_nav = $_SERVER['DOCUMENT_ROOT'] . rtrim(BASE_URL, '/') . "/img/user" . $employee_id_nav . ".png"; // More robust path construction

            if (file_exists($user_specific_img_filepath_nav)) {
                $profile_image_to_display_nav = $user_specific_img_url_nav . "?" . time();
            } else {
                $profile_image_to_display_nav = $default_img_url_nav;
            }
            ?>
            <img src="<?= $profile_image_to_display_nav ?>" alt="Profile Picture">
            <h4>@<?= htmlspecialchars($_SESSION['username']) ?></h4>
        </a>
    </div>

    <ul id="navList"<?= (!empty($is_managing_director) || !empty($is_hr)) ? ' class="nav-md-scroll"' : '' ?>>
        <?php foreach ($nav_items as $url => $item): ?>
            <?php
            // --- NEW: Check if this item matches the current page ---
            // Extract filename from the URL key
            $nav_item_filename = basename($url);
            // Check if the current page filename matches this nav item's filename
            $is_active = ($current_page == $nav_item_filename);

            // Special case for index.php if current page is empty or just '/'
            if (($current_page == '' || $current_page == '/') && $nav_item_filename == 'index.php') {
                $is_active = true;
            }
            // Handle cases where files are in subdirectories like 'app/'
            // If the URL has a directory, check if the current script is in that directory
            if (strpos($url, '/') !== false) {
                $nav_dir = dirname($url); // e.g., 'app'
                // Get current script's directory relative to BASE_URL (more complex, might need refinement based on exact structure)
                // For simplicity, we just check the filename match for now.
                // A more robust check might involve comparing full paths.
            }

            $is_logout = (basename($url) === 'logout.php');
            ?>
            <li <?= $is_active ? 'class="active"' : '' ?><?= $is_logout ? ' style="position: sticky; bottom: 0; background: var(--white); border-top: 1px solid var(--border-dark); margin-top: auto; padding-top: 10px;"' : '' ?>>
                <a href="<?= BASE_URL . $url ?>"<?= $is_logout ? ' onclick="return confirm(\'Are you sure you want to log out?\');"' : '' ?>>
                    <i class="fa <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($item['text']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>