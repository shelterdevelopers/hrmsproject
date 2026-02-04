<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/../load_config.php';

// Add department-specific header styling (e.g., Sales Manager can have a different blue)
$header_class = '';
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['employee_id']) && isset($conn)) {
        require_once __DIR__ . '/../app/Model/RoleHelper.php';
        $dept = RoleHelper::get_department($conn, $_SESSION['employee_id']);
        $dept_norm = strtolower(trim((string)$dept));
        if ($dept_norm === 'sales') $header_class = 'header--sales';
        elseif ($dept_norm === 'finance') $header_class = 'header--finance';
        elseif ($dept_norm === 'operations') $header_class = 'header--operations';
        elseif ($dept_norm === 'corporate services') $header_class = 'header--corporate';
    }
} catch (Throwable $e) {
    // non-fatal: header styling only
}
?>
<header class="header <?= $header_class ?>">
    <div class="header-left">
        <img src="<?= BASE_URL ?>img/shelter_logo.png" alt="Shelter Logo" class="header-logo">
        <h2 class="u-name">Shelter HRMS</h2>
    </div>
    <div class="header-actions">
        <label for="checkbox" class="header-menu-toggle" title="Toggle menu / full screen">
            <i id="navbtn" class="fa fa-bars" aria-hidden="true"></i>
        </label>
        <a class="header-logout" href="<?= BASE_URL ?>logout.php" title="Logout" onclick="return confirm('Are you sure you want to log out?');">
            <i class="fa fa-sign-out" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
        <span class="notification" id="notificationBtn" title="View notifications">
            <i class="fa fa-bell" aria-hidden="true"></i>
            <span id="notificationNum" title="Unread count"></span>
        </span>
    </div>
</header>

<script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>

<script type="text/javascript">
    // Pass the PHP constant to a JavaScript variable
    const baseURL = "<?= BASE_URL ?>";

    // Basic UI setup (date inputs etc.)
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure all date inputs are properly configured
        const dateInputs = document.querySelectorAll('input[type=\"date\"]');
        dateInputs.forEach(function(input) {
            if (input.hasAttribute('readonly') && input.readOnly) {
                if (!input.closest('form') || !input.id || input.id !== 'end-date') {
                    input.removeAttribute('readonly');
                    input.readOnly = false;
                }
            }

            input.style.pointerEvents = 'auto';
            input.style.cursor = 'pointer';

            input.addEventListener('click', function() {
                this.focus();
                if (this.showPicker) {
                    try {
                        this.showPicker();
                    } catch (err) {
                        console.log('showPicker not supported');
                    }
                }
            });
        });
    });

    // Load notification count into the bell badge and navigate to Notifications page on click
    $(document).ready(function() {
        const countURL = baseURL + "app/notification-count.php";

        function loadNotificationCount() {
            $("#notificationNum").load(countURL, function(response) {
                const count = parseInt(response) || 0;
                if (count > 0) {
                    $("#notificationNum").addClass('notification-badge').attr('title', count === 1 ? '1 unread notification' : count + ' unread notifications');
                } else {
                    $("#notificationNum").removeClass('notification-badge').attr('title', 'No unread notifications');
                }
            });
        }

        // Initial load + periodic refresh
        loadNotificationCount();
        setInterval(loadNotificationCount, 30000);

        // Clicking the bell now simply takes you to the full Notifications page
        $('#notificationBtn').on('click', function() {
            window.location.href = baseURL + "notifications.php";
        });
    });
</script>