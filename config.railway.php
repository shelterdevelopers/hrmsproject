<?php
$db_host = getenv('MYSQLHOST') ?: 'trolley.proxy.rlwy.net';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: 59231;

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) die("Database connection error.");
$conn->set_charset("utf8mb4");
?>
