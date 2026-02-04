<?php
// Railway provides MYSQL_URL or MYSQL_PRIVATE_URL when DB is linked - parse it for credentials
$mysql_url = getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL');
if ($mysql_url) {
    $parsed = parse_url($mysql_url);
    $db_host = $parsed['host'] ?? 'localhost';
    $db_port = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
    $db_user = $parsed['user'] ?? 'root';
    $db_pass = $parsed['pass'] ?? '';
    $db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway';
} else {
    $db_host = getenv('MYSQLHOST') ?: 'trolley.proxy.rlwy.net';
    $db_user = getenv('MYSQLUSER') ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';
    $db_port = (int)(getenv('MYSQLPORT') ?: 59231);
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) die("Database connection error.");
$conn->set_charset("utf8mb4");

// Required by app - config.php is gitignored so not present on Railway
define('BASE_URL', '/');
define('APP_TIMEZONE', 'Africa/Harare');
?>
