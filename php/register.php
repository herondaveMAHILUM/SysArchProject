<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$id_number   = trim($_POST['id_number']   ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$year_level  = intval($_POST['year_level'] ?? 0);
$course      = trim($_POST['course']      ?? '');
$address     = trim($_POST['address']     ?? '');
$email       = trim($_POST['email']       ?? '');
$email       = ($email === '') ? null : $email;   // store NULL if blank
$password    = $_POST['password']         ?? '';
$confirm     = $_POST['confirm_password'] ?? '';

if (!$id_number || !$last_name || !$first_name || !$year_level || !$course || !$address || !$password) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Check for duplicate ID number
$chk = $conn->prepare("SELECT id FROM students WHERE id_number = ?");
$chk->bind_param('s', $id_number);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'ID Number is already registered.']);
    exit;
}
$chk->close();

// Check for duplicate email only if an email was provided
if ($email !== null) {
    $chkEmail = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $chkEmail->bind_param('s', $email);
    $chkEmail->execute();
    $chkEmail->store_result();
    if ($chkEmail->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email address is already registered.']);
        exit;
    }
    $chkEmail->close();
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt   = $conn->prepare(
    "INSERT INTO students (id_number, last_name, first_name, middle_name, year_level, course, address, email, password)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ssssissss', $id_number, $last_name, $first_name, $middle_name, $year_level, $course, $address, $email, $hashed);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful! Redirecting to login...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
$stmt->close();
$conn->close();
