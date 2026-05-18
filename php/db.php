<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/config.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sims_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');
