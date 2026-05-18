<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$id_number = trim($_POST['id_number'] ?? '');
$password  = $_POST['password']   ?? '';

if (!$id_number || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please enter your ID number and password.']);
    exit;
}

if ($id_number === 'admin' && $password === 'admin123') {
    $_SESSION['role']       = 'admin';
    $_SESSION['email']      = 'admin@ccs.com';
    $_SESSION['name']       = 'CCS Admin';
    $_SESSION['flash']      = 'Welcome back, CCS Admin!';
    $_SESSION['flash_type'] = 'success';
    echo json_encode(['success' => true, 'role' => 'admin']);
    exit;
}

$stmt = $conn->prepare("SELECT id, id_number, first_name, last_name, email, password FROM students WHERE id_number = ?");
$stmt->bind_param('s', $id_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID number or password.']);
    exit;
}

$user = $result->fetch_assoc();
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID number or password.']);
    exit;
}

$activeCheck = $conn->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active'");
$activeCheck->bind_param('i', $user['id']);
$activeCheck->execute();
$activeResult = $activeCheck->get_result();
$isSittingIn = $activeResult->num_rows > 0;
$activeCheck->close();

$_SESSION['role']        = 'student';
$_SESSION['student_id']  = $user['id'];
$_SESSION['id_number']   = $user['id_number'];
$_SESSION['name']        = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['email']       = $user['email'];
$_SESSION['flash']       = 'Welcome back, ' . $user['first_name'] . '!';
$_SESSION['flash_type']  = 'success';
$_SESSION['is_sitting_in'] = $isSittingIn;

echo json_encode(['success' => true, 'role' => 'student', 'is_sitting_in' => $isSittingIn]);
$stmt->close();
$conn->close();
