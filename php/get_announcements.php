<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$result = $conn->query("SELECT id, message, created_at FROM announcements ORDER BY created_at DESC");
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
$conn->close();

echo json_encode(['success' => true, 'data' => $rows]);
