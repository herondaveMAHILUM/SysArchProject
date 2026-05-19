<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// Ensure system_settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL DEFAULT '1')");

// Check global reservation toggle
$settingRes = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='reservation_enabled' LIMIT 1");
if ($settingRes && $settingRes->num_rows > 0) {
    $settingRow = $settingRes->fetch_assoc();
    if ($settingRow['setting_value'] !== '1') {
        echo json_encode(['success' => false, 'message' => 'Reservations are currently disabled by the administrator. Please try again later.']);
        exit;
    }
}

$activeChk = $conn->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active'");
$activeChk->bind_param('i', $_SESSION['student_id']);
$activeChk->execute();
$activeChk->store_result();
if ($activeChk->num_rows > 0) {
    $activeChk->close();
    echo json_encode(['success' => false, 'message' => 'You are currently sitting in. Please log out first before making a reservation.']);
    exit;
}
$activeChk->close();

$sid       = $_SESSION['student_id'];
$purpose   = trim($_POST['purpose']   ?? '');
$lab       = trim($_POST['lab']       ?? '');
$time_in   = trim($_POST['time_in']   ?? '');
$date      = trim($_POST['date']      ?? '');
$pc_number = intval($_POST['pc_number'] ?? 0);

if (!$purpose || !$lab || !$time_in || !$date) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all reservation fields.']);
    exit;
}

$allowedLabs = ['524', '526', '528', '530', '542', '544'];
if (!in_array($lab, $allowedLabs, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid laboratory selected.']);
    exit;
}

// ── Per-lab reservation toggle check ──────────────────────────────────────────
$labKey     = 'lab_reservation_' . $lab;
$labSetting = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key=? LIMIT 1");
$labSetting->bind_param('s', $labKey);
$labSetting->execute();
$labRow = $labSetting->get_result()->fetch_assoc();
$labSetting->close();

// Default is enabled (true) if no row exists yet
if ($labRow && $labRow['setting_value'] === '0') {
    echo json_encode(['success' => false, 'message' => "Reservations for Lab {$lab} are currently closed by the administrator. Please choose a different lab or try again later."]);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

if ($pc_number <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid PC number.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation date.']);
    exit;
}
if (!preg_match('/^\d{2}:\d{2}$/', $time_in)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation time.']);
    exit;
}
if ($date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Reservation date cannot be in the past.']);
    exit;
}

$selfDup = $conn->prepare("SELECT id FROM reservations WHERE student_id=? AND date=? AND time_in=? AND status IN ('pending','approved','checked_in') LIMIT 1");
$selfDup->bind_param('iss', $sid, $date, $time_in);
$selfDup->execute();
$selfDup->store_result();
if ($selfDup->num_rows > 0) {
    $selfDup->close();
    echo json_encode(['success' => false, 'message' => 'You already have a reservation at this date and time.']);
    exit;
}
$selfDup->close();

$slotDup = $conn->prepare("SELECT id FROM reservations WHERE lab=? AND pc_number=? AND date=? AND time_in=? AND status IN ('pending','approved','checked_in') LIMIT 1");
$slotDup->bind_param('siss', $lab, $pc_number, $date, $time_in);
$slotDup->execute();
$slotDup->store_result();
if ($slotDup->num_rows > 0) {
    $slotDup->close();
    echo json_encode(['success' => false, 'message' => 'Selected PC is already reserved for that timeslot.']);
    exit;
}
$slotDup->close();

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO reservations (student_id, purpose, lab, pc_number, time_in, date) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ississ', $sid, $purpose, $lab, $pc_number, $time_in, $date);
    $stmt->execute();
    $reservationId = $conn->insert_id;
    $stmt->close();

    $notifStmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, type) VALUES (?, 'New Reservation', CONCAT('You have a new reservation request for Lab ', ?, ' at ', ?, ' on ', ?), 'reservation')");
    $notifStmt->bind_param('isss', $sid, $lab, $time_in, $date);
    $notifStmt->execute();
    $notifStmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation submitted successfully! Waiting for admin approval.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to submit reservation.']);
}
$conn->close();
