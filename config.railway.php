<?php
// Entrypoint writes env vars to file (PHP can't read Railway's env)
$db_host = $db_user = $db_pass = $db_name = null;
$db_port = 3306;

if (file_exists('/tmp/railway_mysql_url')) {
    $mysql_url = trim(file_get_contents('/tmp/railway_mysql_url'));
    if ($mysql_url && strpos($mysql_url, 'mysql://') === 0) {
        $parsed = parse_url($mysql_url);
        $db_host = $parsed['host'] ?? null;
        $db_port = isset($parsed['port']) ? (int)$parsed['port'] : 3306;
        $db_user = $parsed['user'] ?? null;
        $db_pass = $parsed['pass'] ?? '';
        $db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : null;
    }
}
if (!$db_host && file_exists('/tmp/railway_db_vars')) {
    $vars = @parse_ini_string(file_get_contents('/tmp/railway_db_vars'));
    if ($vars && !empty($vars['host'])) {
        $db_host = $vars['host'];
        $db_user = $vars['user'] ?? 'root';
        $db_pass = $vars['pass'] ?? '';
        $db_name = $vars['db'] ?? 'railway';
        $db_port = (int)($vars['port'] ?? 3306);
    }
}

if (!$db_host || $db_host === 'localhost') {
    die('Database config missing. In Railway: Web app → Variables → + New Variable → Name: MYSQL_URL → Value: paste the connection string from MySQL service (Variables → MYSQL_URL → Reveal). Then Redeploy.');
}
$db_user = $db_user ?: 'root';
$db_pass = $db_pass ?? '';
$db_name = $db_name ?: 'railway';

try {
    $conn = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Required by app - config.php is gitignored so not present on Railway
define('BASE_URL', '/');
define('APP_TIMEZONE', 'Africa/Harare');
?>
