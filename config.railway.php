<?php
// Entrypoint writes env vars to file (PHP can't read Railway's env)
$db_host = $db_user = $db_pass = $db_name = null;
$db_port = 3306;

if (file_exists('/tmp/railway_mysql_url')) {
    $mysql_url = trim(file_get_contents('/tmp/railway_mysql_url'));
    if ($mysql_url) {
        $parsed = parse_url($mysql_url);
        $db_host = $parsed['host'] ?? null;
        $db_port = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
        $db_user = $parsed['user'] ?? null;
        $db_pass = $parsed['pass'] ?? '';
        $db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : null;
    }
}
if (!$db_host && file_exists('/tmp/railway_db_vars')) {
    $vars = parse_ini_string(file_get_contents('/tmp/railway_db_vars'));
    if ($vars) {
        $db_host = $vars['host'] ?? null;
        $db_user = $vars['user'] ?? null;
        $db_pass = $vars['pass'] ?? '';
        $db_name = $vars['db'] ?? null;
        $db_port = (int)($vars['port'] ?? 3306);
    }
}
$db_host = $db_host ?: 'localhost';
$db_user = $db_user ?: 'root';
$db_name = $db_name ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    if (empty($db_pass)) {
        die("Database password missing. In Railway: select your WEB APP service → Variables → Add Variable Reference → choose MySQL → add MYSQL_URL. Or add MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT. Redeploy after adding.");
    }
    die("Database connection error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Required by app - config.php is gitignored so not present on Railway
define('BASE_URL', '/');
define('APP_TIMEZONE', 'Africa/Harare');
?>
