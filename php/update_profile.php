<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$id          = $_SESSION['student_id'];
$id_number   = trim($_POST['id_number']   ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$year_level  = intval($_POST['year_level'] ?? 0);
$course      = trim($_POST['course']      ?? '');
$address     = trim($_POST['address']     ?? '');
$email       = trim($_POST['email']       ?? '');
$password    = $_POST['password']         ?? '';
$confirm     = $_POST['confirm_password'] ?? '';

if (!$id_number || !$last_name || !$first_name || !$year_level || !$course || !$address || !$email) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$chk = $conn->prepare("SELECT id FROM students WHERE (email = ? OR id_number = ?) AND id != ?");
$chk->bind_param('ssi', $email, $id_number, $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email or ID Number already used by another account.']);
    exit;
}
$chk->close();

$pic_param = '';
if (!empty($_FILES['profile_pic']['name'])) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES['profile_pic']['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP images allowed.']);
        exit;
    }
    if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image must be under 2MB.']);
        exit;
    }
    $ext      = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . $id . '_' . time() . '.' . $ext;
    $dest     = __DIR__ . '/../uploads/' . $filename;
    move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest);
    $pic_param = $filename;
}

if (!empty($password)) {
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    if ($pic_param) {
        $stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,year_level=?,course=?,address=?,email=?,password=?,profile_pic=? WHERE id=?");
        $stmt->bind_param('ssssisssssi', $id_number,$last_name,$first_name,$middle_name,$year_level,$course,$address,$email,$hashed,$pic_param,$id);
    } else {
        $stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,year_level=?,course=?,address=?,email=?,password=? WHERE id=?");
        $stmt->bind_param('ssssissssi', $id_number,$last_name,$first_name,$middle_name,$year_level,$course,$address,$email,$hashed,$id);
    }
} else {
    if ($pic_param) {
        $stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,year_level=?,course=?,address=?,email=?,profile_pic=? WHERE id=?");
        $stmt->bind_param('ssssissssi', $id_number,$last_name,$first_name,$middle_name,$year_level,$course,$address,$email,$pic_param,$id);
    } else {
        $stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,year_level=?,course=?,address=?,email=? WHERE id=?");
        $stmt->bind_param('ssssisssi', $id_number,$last_name,$first_name,$middle_name,$year_level,$course,$address,$email,$id);
    }
}

if ($stmt->execute()) {
    $_SESSION['name'] = $first_name . ' ' . $last_name;
    $_SESSION['flash']      = 'Profile updated successfully!';
    $_SESSION['flash_type'] = 'success';
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed. Please try again.']);
}
$stmt->close();
$conn->close();
