<?php
// Shared config loader - works locally (config.php) and on Railway (config.railway.php)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/config.railway.php')) {
    require_once __DIR__ . '/config.railway.php';
}
if (!defined('BASE_URL')) define('BASE_URL', '/');
if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', 'Africa/Harare');
