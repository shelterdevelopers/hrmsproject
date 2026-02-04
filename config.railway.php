<?php
// Entrypoint writes env vars to file (PHP often can't read Railway's env directly)
$mysql_url = file_exists('/tmp/railway_mysql_url') ? trim(file_get_contents('/tmp/railway_mysql_url')) : null;
if (!$mysql_url) {
    $mysql_url = $_SERVER['MYSQL_PRIVATE_URL'] ?? $_SERVER['MYSQL_URL'] ?? $_ENV['MYSQL_PRIVATE_URL'] ?? $_ENV['MYSQL_URL'] ?? getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL');
}
if ($mysql_url) {
    $parsed = parse_url($mysql_url);
    $db_host = $parsed['host'] ?? 'localhost';
    $db_port = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
    $db_user = $parsed['user'] ?? 'root';
    $db_pass = $parsed['pass'] ?? '';
    $db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway';
} else {
    $e = fn($k, $d = '') => $_SERVER[$k] ?? $_ENV[$k] ?? getenv($k) ?: $d;
    $db_host = $e('MYSQLHOST', 'trolley.proxy.rlwy.net');
    $db_user = $e('MYSQLUSER', 'root');
    $db_pass = $e('MYSQLPASSWORD', '');
    $db_name = $e('MYSQLDATABASE', 'railway');
    $db_port = (int)$e('MYSQLPORT', '59231');
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    if (empty($db_pass) && !$mysql_url) {
        die("Database config missing: Add MYSQL_PRIVATE_URL or MYSQL_URL to your Railway service Variables (from the linked MySQL service).");
    }
    die("Database connection error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Required by app - config.php is gitignored so not present on Railway
define('BASE_URL', '/');
define('APP_TIMEZONE', 'Africa/Harare');
?>
