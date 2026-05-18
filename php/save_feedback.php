<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$sitin_id   = intval($_POST['sitin_id'] ?? 0);
$rating     = intval($_POST['rating']   ?? 0);
$comments   = trim($_POST['comments']   ?? '');
$student_id = $_SESSION['student_id'];

if ($sitin_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session record.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a rating (1–5 stars).']);
    exit;
}

// Verify the sitin record belongs to this student (any status — 'done', 'completed', etc.)
$verify = $conn->prepare("SELECT id FROM sitin_records WHERE id=? AND student_id=?");
$verify->bind_param('ii', $sitin_id, $student_id);
$verify->execute();
$verify->store_result();
if ($verify->num_rows === 0) {
    $verify->close();
    echo json_encode(['success' => false, 'message' => 'Session record not found or not yours.']);
    exit;
}
$verify->close();

// Check for duplicate feedback
$chk = $conn->prepare("SELECT id FROM feedback WHERE student_id=? AND sitin_id=?");
$chk->bind_param('ii', $student_id, $sitin_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'You have already submitted feedback for this session.']);
    exit;
}
$chk->close();

$stmt = $conn->prepare(
    "INSERT INTO feedback (student_id, sitin_id, rating, comments) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('iiis', $student_id, $sitin_id, $rating, $comments);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted! Thank you.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback. Please try again.']);
}
$stmt->close();
$conn->close();
