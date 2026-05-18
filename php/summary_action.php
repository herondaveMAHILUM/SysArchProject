<?php
// get_sitin_summary handler — included by admin_actions.php
// Called when action === 'get_sitin_summary'

$dateFrom  = trim($_POST['date_from']  ?? '');
$dateTo    = trim($_POST['date_to']    ?? '');
$labF      = trim($_POST['lab']        ?? '');
$purposeF  = trim($_POST['purpose']    ?? '');
$statusF   = trim($_POST['status']     ?? '');

$where  = '1=1';
$params = [];
$types  = '';

if ($dateFrom) { $where .= ' AND sr.date >= ?';    $params[] = $dateFrom; $types .= 's'; }
if ($dateTo)   { $where .= ' AND sr.date <= ?';    $params[] = $dateTo;   $types .= 's'; }
if ($labF)     { $where .= ' AND sr.lab = ?';      $params[] = $labF;     $types .= 's'; }
if ($purposeF) { $where .= ' AND sr.purpose = ?';  $params[] = $purposeF; $types .= 's'; }
if ($statusF)  { $where .= ' AND sr.status = ?';   $params[] = $statusF;  $types .= 's'; }

// Records
$recSql = "SELECT sr.id, s.id_number,
    CONCAT(s.first_name,' ',s.last_name) AS name,
    sr.purpose, sr.lab, sr.login_time, sr.logout_time, sr.date, sr.status
    FROM sitin_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE $where
    ORDER BY sr.date DESC, sr.login_time DESC";
$recStmt = $conn->prepare($recSql);
if (!empty($params)) $recStmt->bind_param($types, ...$params);
$recStmt->execute();
$records = $recStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recStmt->close();

// Stats
$statSql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN sr.status='done'   THEN 1 ELSE 0 END) AS done,
    SUM(CASE WHEN sr.status='active' THEN 1 ELSE 0 END) AS active,
    ROUND(AVG(CASE WHEN sr.status='done' AND sr.logout_time IS NOT NULL
        THEN TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time)
        ELSE NULL END), 1) AS avg_duration_min,
    COUNT(DISTINCT sr.student_id) AS unique_students
    FROM sitin_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE $where";
$stStmt = $conn->prepare($statSql);
if (!empty($params)) $stStmt->bind_param($types, ...$params);
$stStmt->execute();
$stats = $stStmt->get_result()->fetch_assoc();
$stStmt->close();

// By Purpose
$purpSql = "SELECT sr.purpose, COUNT(*) AS cnt
    FROM sitin_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE $where
    GROUP BY sr.purpose ORDER BY cnt DESC";
$purpStmt = $conn->prepare($purpSql);
if (!empty($params)) $purpStmt->bind_param($types, ...$params);
$purpStmt->execute();
$byPurpose = $purpStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$purpStmt->close();

// By Lab
$labSql = "SELECT sr.lab, COUNT(*) AS cnt
    FROM sitin_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE $where
    GROUP BY sr.lab ORDER BY sr.lab";
$labStmt = $conn->prepare($labSql);
if (!empty($params)) $labStmt->bind_param($types, ...$params);
$labStmt->execute();
$byLab = $labStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$labStmt->close();

// Daily Trend
$trendWhere  = $where;
$trendParams = $params;
$trendTypes  = $types;
if (!$dateFrom && !$dateTo) {
    $trendWhere .= ' AND sr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
}
$daySql = "SELECT sr.date AS day, COUNT(*) AS cnt
    FROM sitin_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE $trendWhere
    GROUP BY sr.date ORDER BY sr.date ASC";
$dayStmt = $conn->prepare($daySql);
if (!empty($trendParams)) $dayStmt->bind_param($trendTypes, ...$trendParams);
$dayStmt->execute();
$byDay = $dayStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dayStmt->close();

echo json_encode([
    'success' => true,
    'data'    => [
        'records'    => $records,
        'stats'      => $stats,
        'by_purpose' => $byPurpose,
        'by_lab'     => $byLab,
        'by_day'     => $byDay
    ]
]);
exit;
