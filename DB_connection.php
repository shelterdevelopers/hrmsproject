<?php
// Load config (BASE_URL + timezone)
require_once __DIR__ . '/load_config.php';

if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', 'Africa/Harare');
if (function_exists('date_default_timezone_set')) date_default_timezone_set(APP_TIMEZONE);

// Use $conn from config if already set (e.g. config.railway.php uses mysqli)
// App uses PDO-style prepare/execute - convert mysqli to work, or create PDO
if (!isset($conn)) {
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
    if (!$db_host) {
        $is_local = !empty($_SERVER['HTTP_HOST']) && (
            $_SERVER['HTTP_HOST'] === 'localhost' ||
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
        );
        if ($is_local) {
            $db_host = 'db';
            $db_user = 'root';
            $db_pass = 'root';
            $db_name = 'hrms_database';
            $db_port = 3306;
        } else {
            die('Database config missing. Add MYSQL_URL to Railway web app Variables and redeploy.');
        }
    }
    $db_user = $db_user ?: 'root';
    $db_pass = $db_pass ?? '';
    $db_name = $db_name ?: 'railway';

    try {
        $conn = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
