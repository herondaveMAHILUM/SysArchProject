<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_lab_reservation_status') {
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL DEFAULT '1')");
    $labs = ['524','526','528','530','542','544'];
    $data = [];
    foreach ($labs as $lab) {
        $key = 'lab_reservation_' . $lab;
        $res = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key=? LIMIT 1");
        $res->bind_param('s', $key); $res->execute();
        $row = $res->get_result()->fetch_assoc(); $res->close();
        if ($row) {
            $data[$lab] = $row['setting_value'] === '1';
        } else {
            // Default enabled — insert the row
            $ins = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, '1')");
            $ins->bind_param('s', $key); $ins->execute(); $ins->close();
            $data[$lab] = true;
        }
    }
    echo json_encode(['success'=>true,'data'=>$data]); exit;
}

if ($action === 'toggle_lab_reservation') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit;
    }
    $lab    = trim($_POST['lab'] ?? '');
    $enable = intval($_POST['enable'] ?? 1);
    $allowed = ['524','526','528','530','542','544'];
    if (!in_array($lab, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid lab.']); exit;
    }
    $conn->query("CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL DEFAULT '1')");
    $key = 'lab_reservation_' . $lab;
    $val = $enable ? '1' : '0';
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
    $stmt->bind_param('sss', $key, $val, $val);
    if ($stmt->execute()) {
        $msg = $enable
            ? "Lab {$lab} reservations are now ENABLED."
            : "Lab {$lab} reservations are now DISABLED. Students cannot reserve in Lab {$lab}.";
        echo json_encode(['success'=>true,'message'=>$msg,'enabled'=>(bool)$enable,'lab'=>$lab]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Failed to update setting.']);
    }
    $stmt->close(); exit;
}

if ($action === 'get_pc_status') {
    $lab = trim($_POST['lab'] ?? $_GET['lab'] ?? '');
    if (!$lab) { echo json_encode(['success'=>false,'message'=>'Lab required.']); exit; }

    $res = $conn->prepare("SELECT pc_number,is_available FROM pc_status WHERE lab=? ORDER BY pc_number");
    $res->bind_param('s',$lab); $res->execute();
    $result = $res->get_result();
    $dbMap = [];
    while ($r = $result->fetch_assoc()) $dbMap[intval($r['pc_number'])] = $r;
    $res->close();

    // Always return all 50 PCs; fill missing ones as available
    $rows = [];
    for ($i = 1; $i <= 50; $i++) {
        if (isset($dbMap[$i])) {
            $rows[] = $dbMap[$i];
        } else {
            $rows[] = ['pc_number' => (string)$i, 'is_available' => 1];
        }
    }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_notifications') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }
    $sid = $_SESSION['student_id'];
    $res = $conn->prepare("SELECT id,title,message,type,is_read,created_at FROM notifications WHERE student_id=? ORDER BY created_at DESC");
    $res->bind_param('i',$sid); $res->execute();
    $result = $res->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $res->close();
    
    $unread = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE student_id=? AND is_read=0");
    $unread->bind_param('i',$sid); $unread->execute();
    $unreadCount = $unread->get_result()->fetch_assoc()['c'];
    $unread->close();
    
    echo json_encode(['success'=>true,'data'=>$rows,'unread'=>$unreadCount]); exit;
}

if ($action === 'mark_notification_read') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }
    $nid = intval($_POST['notification_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND student_id=?");
    $stmt->bind_param('ii',$nid,$_SESSION['student_id']);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Marked as read.'])
        : json_encode(['success'=>false,'message'=>'Failed.']);
    $stmt->close(); exit;
}

if ($action === 'mark_all_notifications_read') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE student_id=?");
    $stmt->bind_param('i',$_SESSION['student_id']);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'All marked as read.'])
        : json_encode(['success'=>false,'message'=>'Failed.']);
    $stmt->close(); exit;
}

if ($action === 'check_sitin_status') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }
    $sid = $_SESSION['student_id'];
    $chk = $conn->prepare("SELECT sr.id,sr.purpose,sr.lab,sr.login_time FROM sitin_records sr WHERE sr.student_id=? AND sr.status='active'");
    $chk->bind_param('i',$sid); $chk->execute();
    $result = $chk->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success'=>true,'is_sitting_in'=>true,'sitin_data'=>$row]);
    } else {
        echo json_encode(['success'=>true,'is_sitting_in'=>false]);
    }
    $chk->close(); exit;
}

if ($action === 'get_user_reservations') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }
    $sid = $_SESSION['student_id'];
    $res = $conn->prepare("SELECT id,purpose,lab,time_in,date,status,created_at FROM reservations WHERE student_id=? ORDER BY date DESC,time_in DESC");
    $res->bind_param('i',$sid); $res->execute();
    $result = $res->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $res->close();
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_my_reservations') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }

    $sid = $_SESSION['student_id'];
    $res = $conn->prepare("SELECT id,purpose,lab,pc_number,time_in,date,status,checked_in,created_at FROM reservations WHERE student_id=? ORDER BY date DESC,time_in DESC");
    $res->bind_param('i',$sid); $res->execute();
    $result = $res->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $res->close();

    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'check_in_reservation') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }

    $sid = $_SESSION['student_id'];

    $chk = $conn->prepare("SELECT r.id,r.lab,r.pc_number,r.time_in,r.date,s.remaining_session FROM reservations r JOIN students s ON s.id=r.student_id WHERE r.student_id=? AND r.status='approved' AND r.date=CURDATE() AND r.checked_in=0 LIMIT 1");
    $chk->bind_param('i',$sid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$res) {
        echo json_encode(['success'=>false,'message'=>'No approved reservation found for today.']);
        exit;
    }
    if (intval($res['remaining_session']) <= 0) {
        echo json_encode(['success'=>false,'message'=>'No remaining sessions left.']);
        exit;
    }

    $activeChk = $conn->prepare("SELECT id FROM sitin_records WHERE student_id=? AND status='active'");
    $activeChk->bind_param('i',$sid); $activeChk->execute(); $activeChk->store_result();
    if ($activeChk->num_rows > 0) {
        $activeChk->close();
        echo json_encode(['success'=>false,'message'=>'You are already sitting in.']);
        exit;
    }
    $activeChk->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO sitin_records (student_id,purpose,lab,login_time,date,status) VALUES (?,?,?,NOW(),CURDATE(),'active')");
        $purpose = "Reservation - " . $res['lab'];
        $stmt->bind_param('iss',$sid,$purpose,$res['lab']);
        $stmt->execute();
        $sitinId = $conn->insert_id;
        $stmt->close();

        $sessStmt = $conn->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id=? AND remaining_session > 0");
        $sessStmt->bind_param('i', $sid);
        $sessStmt->execute();
        if ($sessStmt->affected_rows < 1) {
            $sessStmt->close();
            throw new Exception('No remaining session.');
        }
        $sessStmt->close();

        $resStmt = $conn->prepare("UPDATE reservations SET status='checked_in', checked_in=1 WHERE id=?");
        $resStmt->bind_param('i',$res['id']); $resStmt->execute(); $resStmt->close();

        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=0 WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si',$res['lab'],$res['pc_number']); $pcStmt->execute(); $pcStmt->close();
        }

        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Checked in successfully!','sitin_id'=>$sitinId,'pc_number'=>$res['pc_number']]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to check in.']);
    }
    exit;
}

if ($action === 'check_out_reservation') {
    if (!isset($_SESSION['student_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated.']); exit; }

    $sid = $_SESSION['student_id'];

    $chk = $conn->prepare("SELECT sr.id,sr.lab,r.pc_number,r.id AS reservation_id FROM sitin_records sr LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status='checked_in' WHERE sr.student_id=? AND sr.status='active' ORDER BY sr.id DESC LIMIT 1");
    $chk->bind_param('i',$sid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$res) {
        echo json_encode(['success'=>false,'message'=>'No active sit-in record found.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE sitin_records SET logout_time=NOW(), status='done' WHERE id=?");
        $stmt->bind_param('i',$res['id']); $stmt->execute(); $stmt->close();

        if ($res['reservation_id']) {
            $resStmt = $conn->prepare("UPDATE reservations SET status='completed' WHERE id=?");
            $resStmt->bind_param('i',$res['reservation_id']); $resStmt->execute(); $resStmt->close();
        }

        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si',$res['lab'],$res['pc_number']); $pcStmt->execute(); $pcStmt->close();
        }

        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Checked out successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to check out.']);
    }
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($action === 'get_students') {
    $res  = $conn->query("SELECT id, id_number, CONCAT(first_name,' ',last_name) AS name, year_level, course, remaining_session FROM students ORDER BY id_number");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

if ($action === 'add_student') {
    $id_number  = trim($_POST['id_number']   ?? '');
    $last_name  = trim($_POST['last_name']   ?? '');
    $first_name = trim($_POST['first_name']  ?? '');
    $mid        = trim($_POST['middle_name'] ?? '');
    $year       = intval($_POST['year_level']?? 0);
    $course     = trim($_POST['course']      ?? '');
    $address    = trim($_POST['address']     ?? '');
    $email      = trim($_POST['email']       ?? '');
    $pw         = $_POST['password']         ?? 'password123';

    if (!$id_number||!$last_name||!$first_name||!$year||!$course||!$email) {
        echo json_encode(['success'=>false,'message'=>'Fill in all required fields.']); exit;
    }
    $chk = $conn->prepare("SELECT id FROM students WHERE email=? OR id_number=?");
    $chk->bind_param('ss',$email,$id_number); $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { echo json_encode(['success'=>false,'message'=>'Email or ID already exists.']); exit; }
    $chk->close();

    $hashed = password_hash($pw ?: 'password123', PASSWORD_DEFAULT);
    $stmt   = $conn->prepare("INSERT INTO students (id_number,last_name,first_name,middle_name,year_level,course,address,email,password) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssissss',$id_number,$last_name,$first_name,$mid,$year,$course,$address,$email,$hashed);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Student added successfully.'])
        : json_encode(['success'=>false,'message'=>'Failed to add student.']);
    $stmt->close(); exit;
}

if ($action === 'get_student') {
    $id   = intval($_GET['id'] ?? $_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT id,id_number,last_name,first_name,middle_name,year_level,course,address,email,remaining_session FROM students WHERE id=?");
    $stmt->bind_param('i',$id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if ($action === 'edit_student') {
    $id   = intval($_POST['id'] ?? 0);
    $idn  = trim($_POST['id_number']   ?? '');
    $ln   = trim($_POST['last_name']   ?? '');
    $fn   = trim($_POST['first_name']  ?? '');
    $mn   = trim($_POST['middle_name'] ?? '');
    $yr   = intval($_POST['year_level']?? 0);
    $co   = trim($_POST['course']      ?? '');
    $ad   = trim($_POST['address']     ?? '');
    $em   = trim($_POST['email']       ?? '');
    $sess = intval($_POST['remaining_session'] ?? 30);

    $stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,year_level=?,course=?,address=?,email=?,remaining_session=? WHERE id=?");
    $stmt->bind_param('ssssisssii',$idn,$ln,$fn,$mn,$yr,$co,$ad,$em,$sess,$id);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Student updated.'])
        : json_encode(['success'=>false,'message'=>'Update failed.']);
    $stmt->close(); exit;
}

if ($action === 'delete_student') {
    $id   = intval($_POST['id'] ?? 0);

    $stmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE reserved_by=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param('i',$id);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Student deleted.'])
        : json_encode(['success'=>false,'message'=>'Delete failed.']);
    $stmt->close(); exit;
}

if ($action === 'reset_sessions') {
    $conn->query("UPDATE students SET remaining_session = 30");
    echo json_encode(['success'=>true,'message'=>'All sessions reset to 30.']); exit;
}

if ($action === 'reset_student_session') {
    $id = intval($_POST['id'] ?? 0);
    $sessions = isset($_POST['sessions']) ? intval($_POST['sessions']) : 30;
    if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid student ID.']); exit; }
    if ($sessions < 0) { $sessions = 0; }
    $stmt = $conn->prepare("UPDATE students SET remaining_session=? WHERE id=?");
    $stmt->bind_param('ii', $sessions, $id);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Sessions reset to '.$sessions.' successfully.'])
        : json_encode(['success'=>false,'message'=>'Failed to reset sessions.']);
    $stmt->close(); exit;
}

if ($action === 'post_announcement') {
    $msg = trim($_POST['message'] ?? '');
    if (!$msg) { echo json_encode(['success'=>false,'message'=>'Announcement cannot be empty.']); exit; }
    $stmt = $conn->prepare("INSERT INTO announcements (message) VALUES (?)");
    $stmt->bind_param('s',$msg);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Announcement posted.'])
        : json_encode(['success'=>false,'message'=>'Failed to post.']);
    $stmt->close(); exit;
}

if ($action === 'delete_announcement') {
    $id = intval($_POST['announcement_id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid announcement ID.']); exit; }
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param('i',$id);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'Announcement deleted.'])
        : json_encode(['success'=>false,'message'=>'Failed to delete.']);
    $stmt->close(); exit;
}

if ($action === 'sitin') {
    $id_number = trim($_POST['id_number'] ?? '');
    $purpose   = trim($_POST['purpose']   ?? '');
    $lab       = trim($_POST['lab']       ?? '');
    $pc_number = intval($_POST['pc_number'] ?? 0);   // optional
    if (!$id_number||!$purpose||!$lab) { echo json_encode(['success'=>false,'message'=>'Fill in all fields.']); exit; }

    $s = $conn->prepare("SELECT id, remaining_session FROM students WHERE id_number=?");
    $s->bind_param('s',$id_number); $s->execute();
    $stu = $s->get_result()->fetch_assoc(); $s->close();

    if (!$stu) { echo json_encode(['success'=>false,'message'=>'Student not found.']); exit; }
    if ($stu['remaining_session'] <= 0) { echo json_encode(['success'=>false,'message'=>'No remaining sessions.']); exit; }

    $chk = $conn->prepare("SELECT id FROM sitin_records WHERE student_id=? AND status='active'");
    $chk->bind_param('i',$stu['id']); $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success'=>false,'message'=>'This student is already sitting in. They must log out first before sitting in again.']);
        exit;
    }
    $chk->close();

    // If a PC was chosen, verify it is still available
    if ($pc_number > 0) {
        $pcChk = $conn->prepare("SELECT is_available FROM pc_status WHERE lab=? AND pc_number=? LIMIT 1");
        $pcChk->bind_param('si', $lab, $pc_number); $pcChk->execute();
        $pcRow = $pcChk->get_result()->fetch_assoc(); $pcChk->close();
        // Row might not exist yet (means it has never been set → treat as available)
        if ($pcRow && !$pcRow['is_available']) {
            echo json_encode(['success'=>false,'message'=>"PC {$pc_number} in Lab {$lab} is already occupied. Please choose another PC."]);
            exit;
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO sitin_records (student_id,purpose,lab,login_time,date,status) VALUES (?,?,?,NOW(),CURDATE(),'active')");
        $stmt->bind_param('iss',$stu['id'],$purpose,$lab);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ".intval($stu['id']));

        // Mark the chosen PC as occupied
        if ($pc_number > 0) {
            $upsert = $conn->prepare("INSERT INTO pc_status (lab, pc_number, is_available) VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE is_available = 0, reserved_by = NULL, reservation_id = NULL");
            $upsert->bind_param('si', $lab, $pc_number);
            $upsert->execute();
            $upsert->close();
        }

        $conn->commit();
        $pcMsg = $pc_number > 0 ? " (PC {$pc_number})" : '';
        echo json_encode(['success'=>true,'message'=>'Student sat in successfully.'.$pcMsg]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Sit-in failed.']);
    }
    exit;
}

if ($action === 'search_student') {
    $q    = '%'.trim($_POST['query'] ?? '').'%';
    $stmt = $conn->prepare("SELECT id,id_number,CONCAT(first_name,' ',last_name) AS name,course,year_level,remaining_session FROM students WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? LIMIT 10");
    $stmt->bind_param('sss',$q,$q,$q); $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'timeout') {
    $id   = intval($_POST['sitin_id'] ?? 0);

    $chk = $conn->prepare("
        SELECT sr.id, sr.student_id, sr.lab, sr.date, r.id AS reservation_id, r.pc_number 
        FROM sitin_records sr 
        LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status='checked_in'
        WHERE sr.id=?
    ");
    $chk->bind_param('i', $id);
    $chk->execute();
    $res = $chk->get_result()->fetch_assoc();
    $chk->close();
    
    if (!$res) {
        echo json_encode(['success'=>false,'message'=>'Sit-in record not found.']); exit;
    }
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE sitin_records SET logout_time=NOW(), status='done' WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        if ($res['reservation_id']) {
            $resStmt = $conn->prepare("UPDATE reservations SET status='completed' WHERE id=?");
            $resStmt->bind_param('i', $res['reservation_id']);
            $resStmt->execute();
            $resStmt->close();
        }

        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si', $res['lab'], $res['pc_number']);
            $pcStmt->execute();
            $pcStmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Timed out successfully. PC '.$res['pc_number'].' is now available.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Timeout failed.']);
    }
    exit;
}

if ($action === 'get_sitin_records') {
    $res = $conn->query("
        SELECT 
            sr.id,
            s.id_number,
            CONCAT(s.first_name,' ',s.last_name) AS name,
            sr.purpose,
            sr.lab,
            sr.login_time,
            sr.logout_time,
            sr.date,
            sr.status,
            r.id AS reservation_id,
            r.pc_number,
            r.status AS reservation_status
        FROM sitin_records sr 
        JOIN students s ON s.id=sr.student_id 
        LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status IN ('checked_in','completed')
        ORDER BY sr.date DESC, sr.login_time DESC
    ");
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['is_reservation'] = !is_null($r['reservation_id']);
        $rows[] = $r;
    }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_stats') {
    $total  = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
    $active = $conn->query("SELECT COUNT(*) AS c FROM sitin_records WHERE status='active'")->fetch_assoc()['c'];
    $ttl    = $conn->query("SELECT COUNT(*) AS c FROM sitin_records")->fetch_assoc()['c'];
    $res    = $conn->query("SELECT purpose,COUNT(*) AS cnt FROM sitin_records GROUP BY purpose");
    $brkdn  = [];
    while ($r = $res->fetch_assoc()) $brkdn[] = $r;
    echo json_encode(['success'=>true,'total_students'=>$total,'currently_sitin'=>$active,'total_sitin'=>$ttl,'breakdown'=>$brkdn]); exit;
}

if ($action === 'get_reservations') {
    $status = $_POST['status'] ?? $_GET['status'] ?? '';
    $lab = $_POST['lab'] ?? $_GET['lab'] ?? '';
    $date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? '';
    $search = $_POST['search'] ?? $_GET['search'] ?? '';
    
    $sql = "SELECT r.id,r.student_id,s.id_number,CONCAT(s.first_name,' ',s.last_name) AS name,r.purpose,r.lab,r.pc_number,r.time_in,r.date,r.status,r.created_at FROM reservations r JOIN students s ON s.id=r.student_id WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($status) {
        $sql .= " AND r.status=?";
        $params[] = $status;
        $types .= 's';
    }
    if ($lab) {
        $sql .= " AND r.lab=?";
        $params[] = $lab;
        $types .= 's';
    }
    if ($date_from) {
        $sql .= " AND r.date>=?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to) {
        $sql .= " AND r.date<=?";
        $params[] = $date_to;
        $types .= 's';
    }
    if ($search) {
        $searchParam = "%$search%";
        $sql .= " AND (s.id_number LIKE ? OR CONCAT(s.first_name,' ',s.last_name) LIKE ? OR r.purpose LIKE ?)";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_reservation_details') {
    $rid = intval($_POST['reservation_id'] ?? $_GET['reservation_id'] ?? 0);
    
    $stmt = $conn->prepare("SELECT r.id,r.student_id,s.id_number,s.first_name,s.last_name,s.middle_name,s.year_level,s.course,s.email,CONCAT(s.first_name,' ',s.last_name) AS name,r.purpose,r.lab,r.pc_number,r.time_in,r.date,r.status,r.checked_in,r.created_at FROM reservations r JOIN students s ON s.id=r.student_id WHERE r.id=?");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$reservation) {
        echo json_encode(['success'=>false,'message'=>'Reservation not found.']); exit;
    }

    $logStmt = $conn->prepare("SELECT admin_name,action,notes,created_at FROM reservation_logs WHERE reservation_id=? ORDER BY created_at DESC");
    $logStmt->bind_param('i', $rid);
    $logStmt->execute();
    $logs = [];
    $logResult = $logStmt->get_result();
    while ($row = $logResult->fetch_assoc()) $logs[] = $row;
    $logStmt->close();
    
    $histStmt = $conn->prepare("SELECT id,lab,date,status FROM reservations WHERE student_id=? ORDER BY created_at DESC LIMIT 10");
    $histStmt->bind_param('i', $reservation['student_id']);
    $histStmt->execute();
    $history = [];
    $histResult = $histStmt->get_result();
    while ($row = $histResult->fetch_assoc()) $history[] = $row;
    $histStmt->close();

    echo json_encode([
        'success'=>true,
        'data'=>[
            'reservation'=>$reservation,
            'logs'=>$logs,
            'history'=>$history
        ]
    ]); exit;
}

if ($action === 'bulk_process_reservations') {
    $reservation_ids = json_decode($_POST['reservation_ids'] ?? '[]', true);
    $action_type = $_POST['action_type'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($reservation_ids)) {
        echo json_encode(['success'=>false,'message'=>'No reservations selected.']); exit;
    }
    
    if (!in_array($action_type, ['approve', 'reject', 'cancel'])) {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']); exit;
    }
    
    $success_count = 0;
    $error_count = 0;
    $adminName = $_SESSION['name'] ?? 'Admin';
    
    foreach ($reservation_ids as $rid) {
        $rid = intval($rid);
        if ($rid <= 0) continue;

        $chk = $conn->prepare("SELECT student_id,lab,pc_number,time_in,date,status FROM reservations WHERE id=?");
        $chk->bind_param('i',$rid); $chk->execute();
        $res = $chk->get_result()->fetch_assoc(); $chk->close();
        
        if (!$res) { $error_count++; continue; }
        
        $conn->begin_transaction();
        try {
            if ($action_type === 'approve') {
                if ($res['status'] !== 'pending') {
                    $conn->rollback();
                    $error_count++;
                    continue;
                }
                
                $stmt = $conn->prepare("UPDATE reservations SET status='approved' WHERE id=?");
                $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();
                
                if ($res['pc_number'] > 0) {
                    $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=0, reserved_by=?, reservation_id=?, reserved_date=?, reserved_time=? WHERE lab=? AND pc_number=?");
                    $pcStmt->bind_param('iisssi', $res['student_id'], $rid, $res['date'], $res['time_in'], $res['lab'], $res['pc_number']);
                    $pcStmt->execute(); $pcStmt->close();
                }
                
                $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,'admin','approved',?)");
                $logStmt->bind_param('is',$rid,$notes); $logStmt->execute(); $logStmt->close();
                
                $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,'Reservation Approved','Your reservation has been approved.','reservation')");
                $notifStmt->bind_param('i',$res['student_id']); $notifStmt->execute(); $notifStmt->close();
                
            } elseif ($action_type === 'reject') {
                if ($res['status'] !== 'pending') {
                    $conn->rollback();
                    $error_count++;
                    continue;
                }
                
                $stmt = $conn->prepare("UPDATE reservations SET status='rejected' WHERE id=?");
                $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();
                
                if ($res['pc_number'] > 0) {
                    $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
                    $pcStmt->bind_param('si',$res['lab'],$res['pc_number']);
                    $pcStmt->execute(); $pcStmt->close();
                }
                
                $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,'admin','rejected',?)");
                $logStmt->bind_param('is',$rid,$notes); $logStmt->execute(); $logStmt->close();
                
                $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,'Reservation Rejected',CONCAT('Your reservation has been rejected. Reason: ',?), 'reservation')");
                $notifStmt->bind_param('is',$res['student_id'],$notes); $notifStmt->execute(); $notifStmt->close();
                
            } elseif ($action_type === 'cancel') {
                if ($res['status'] !== 'approved') {
                    $conn->rollback();
                    $error_count++;
                    continue;
                }
                
                $stmt = $conn->prepare("UPDATE reservations SET status='rejected' WHERE id=?");
                $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();
                
                if ($res['pc_number'] > 0) {
                    $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
                    $pcStmt->bind_param('si',$res['lab'],$res['pc_number']);
                    $pcStmt->execute(); $pcStmt->close();
                }
                
                $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,'admin','cancelled',?)");
                $logStmt->bind_param('is',$rid,$notes); $logStmt->execute(); $logStmt->close();
                
                $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,'Reservation Cancelled','Your approved reservation has been cancelled by admin.','reservation')");
                $notifStmt->bind_param('i',$res['student_id']); $notifStmt->execute(); $notifStmt->close();
            }
            
            $conn->commit();
            $success_count++;
        } catch (Exception $e) {
            $conn->rollback();
            $error_count++;
        }
    }
    
    echo json_encode([
        'success'=>true,
        'message'=>"Processed $success_count reservation(s). $error_count failed.",
        'success_count'=>$success_count,
        'error_count'=>$error_count
    ]); exit;
}

if ($action === 'cancel_reservation') {
    $rid = intval($_POST['reservation_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    $chk = $conn->prepare("SELECT student_id,lab,pc_number,status FROM reservations WHERE id=?");
    $chk->bind_param('i',$rid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();
    
    if (!$res) { echo json_encode(['success'=>false,'message'=>'Reservation not found.']); exit; }
    if ($res['status'] !== 'approved') { echo json_encode(['success'=>false,'message'=>'Only approved reservations can be cancelled.']); exit; }
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE reservations SET status='rejected' WHERE id=?");
        $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();
        
        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si', $res['lab'], $res['pc_number']);
            $pcStmt->execute(); $pcStmt->close();
        }
        
        $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,'admin','cancelled',?)");
        $logStmt->bind_param('is',$rid,$notes); $logStmt->execute(); $logStmt->close();
        
        $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,'Reservation Cancelled','Your approved reservation has been cancelled by admin.','reservation')");
        $notifStmt->bind_param('i',$res['student_id']); $notifStmt->execute(); $notifStmt->close();
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Reservation cancelled.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to cancel reservation.']);
    }
    exit;
}

if ($action === 'admin_check_in') {
    $rid = intval($_POST['reservation_id'] ?? 0);
    
    $chk = $conn->prepare("SELECT r.id,r.student_id,r.lab,r.pc_number,r.date,r.status,s.id_number,s.remaining_session,CONCAT(s.first_name,' ',s.last_name) AS name FROM reservations r JOIN students s ON s.id=r.student_id WHERE r.id=?");
    $chk->bind_param('i',$rid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();
    
    if (!$res) { echo json_encode(['success'=>false,'message'=>'Reservation not found.']); exit; }
    if ($res['status'] !== 'approved') { echo json_encode(['success'=>false,'message'=>'Reservation must be approved first.']); exit; }
    if (intval($res['remaining_session']) <= 0) { echo json_encode(['success'=>false,'message'=>'Student has no remaining sessions.']); exit; }

    $activeChk = $conn->prepare("SELECT id FROM sitin_records WHERE student_id=? AND status='active'");
    $activeChk->bind_param('i',$res['student_id']); $activeChk->execute(); $activeChk->store_result();
    if ($activeChk->num_rows > 0) {
        $activeChk->close();
        echo json_encode(['success'=>false,'message'=>'Student is already sitting in.']); exit;
    }
    $activeChk->close();
    
    $conn->begin_transaction();
    try {
        $purpose = "Reservation - " . $res['lab'];
        $stmt = $conn->prepare("INSERT INTO sitin_records (student_id,purpose,lab,login_time,date,status) VALUES (?,?,?,NOW(),CURDATE(),'active')");
        $stmt->bind_param('iss',$res['student_id'],$purpose,$res['lab']);
        $stmt->execute(); $sitinId = $conn->insert_id; $stmt->close();

        $sessStmt = $conn->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id=? AND remaining_session > 0");
        $sessStmt->bind_param('i', $res['student_id']);
        $sessStmt->execute();
        if ($sessStmt->affected_rows < 1) {
            $sessStmt->close();
            throw new Exception('No remaining session.');
        }
        $sessStmt->close();
        
        $resStmt = $conn->prepare("UPDATE reservations SET status='checked_in', checked_in=1 WHERE id=?");
        $resStmt->bind_param('i',$res['id']); $resStmt->execute(); $resStmt->close();
        
        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=0 WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si',$res['lab'],$res['pc_number']); $pcStmt->execute(); $pcStmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>"{$res['name']} checked in successfully!",'sitin_id'=>$sitinId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to check in student.']);
    }
    exit;
}

if ($action === 'admin_check_out') {
    $sitin_id = intval($_POST['sitin_id'] ?? 0);
    
    $chk = $conn->prepare("SELECT sr.id,sr.student_id,sr.lab,sr.date,r.id AS reservation_id,r.pc_number FROM sitin_records sr LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status='checked_in' WHERE sr.id=?");
    $chk->bind_param('i',$sitin_id); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();
    
    if (!$res) { echo json_encode(['success'=>false,'message'=>'Sit-in record not found.']); exit; }
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE sitin_records SET logout_time=NOW(), status='done' WHERE id=?");
        $stmt->bind_param('i',$res['id']); $stmt->execute(); $stmt->close();
        
        if ($res['reservation_id']) {
            $resStmt = $conn->prepare("UPDATE reservations SET status='completed' WHERE id=?");
            $resStmt->bind_param('i',$res['reservation_id']); $resStmt->execute(); $resStmt->close();
        }
        
        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
            $pcStmt->bind_param('si',$res['lab'],$res['pc_number']); $pcStmt->execute(); $pcStmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Student checked out successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to check out student.']);
    }
    exit;
}

if ($action === 'get_reservation_stats') {
    $today = date('Y-m-d');

    $pending = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status='pending'")->fetch_assoc()['c'];

    $approvedToday = $conn->prepare("SELECT COUNT(*) AS c FROM reservations WHERE status='approved' AND date=?");
    $approvedToday->bind_param('s', $today); $approvedToday->execute();
    $approvedTodayCount = $approvedToday->get_result()->fetch_assoc()['c'];
    $approvedToday->close();

    $checkedIn = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status='checked_in'")->fetch_assoc()['c'];

    $expiredWeek = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status='expired' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['c'];

    $labQuery = $conn->query("SELECT lab, COUNT(*) AS cnt FROM reservations WHERE status IN ('approved','checked_in','completed') GROUP BY lab ORDER BY cnt DESC LIMIT 1");
    $mostUsedLab = $labQuery->fetch_assoc();
    $labQuery->free();

    $monthTotal = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['c'];

    $totalCompleted = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status='completed'")->fetch_assoc()['c'];
    $totalAll = $conn->query("SELECT COUNT(*) AS c FROM reservations")->fetch_assoc()['c'];
    $completionRate = $totalAll > 0 ? round(($totalCompleted / $totalAll) * 100, 1) : 0;
    
    echo json_encode([
        'success'=>true,
        'data'=>[
            'pending'=>$pending,
            'approved_today'=>$approvedTodayCount,
            'currently_checked_in'=>$checkedIn,
            'expired_this_week'=>$expiredWeek,
            'most_used_lab'=>$mostUsedLab ? $mostUsedLab['lab'] : 'N/A',
            'month_total'=>$monthTotal,
            'completion_rate'=>$completionRate
        ]
    ]); exit;
}

if ($action === 'get_reservation_notifications') {
    $student_id = $_POST['student_id'] ?? $_GET['student_id'] ?? '';
    
    if (!$student_id) {
        echo json_encode(['success'=>false,'message'=>'Student ID required.']); exit;
    }
    
    $stmt = $conn->prepare("SELECT id,title,message,type,is_read,created_at FROM notifications WHERE student_id=? AND type='reservation' ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'resend_notification') {
    $notification_id = intval($_POST['notification_id'] ?? 0);
    
    $stmt = $conn->prepare("SELECT student_id,title,message,type FROM notifications WHERE id=?");
    $stmt->bind_param('i', $notification_id);
    $stmt->execute();
    $notif = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$notif) {
        echo json_encode(['success'=>false,'message'=>'Notification not found.']); exit;
    }
    
    $newStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,?,?,?)");
    $newStmt->bind_param('isss', $notif['student_id'], $notif['title'], $notif['message'], $notif['type']);
    
    echo $newStmt->execute()
        ? json_encode(['success'=>true,'message'=>'Notification resent.'])
        : json_encode(['success'=>false,'message'=>'Failed to resend.']);
    $newStmt->close(); exit;
}

if ($action === 'update_reservation_pc') {
    $rid = intval($_POST['reservation_id'] ?? 0);
    $pc_number = intval($_POST['pc_number'] ?? 0);
    $lab = trim($_POST['lab'] ?? '');
    
    if (!$rid || !$pc_number || !$lab) {
        echo json_encode(['success'=>false,'message'=>'Missing required fields.']); exit;
    }
    
    $stmt = $conn->prepare("UPDATE reservations SET pc_number=? WHERE id=?");
    $stmt->bind_param('ii', $pc_number, $rid);
    
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'PC number updated.'])
        : json_encode(['success'=>false,'message'=>'Failed to update PC.']);
    $stmt->close(); exit;
}

if ($action === 'approve_reservation') {
    $rid = intval($_POST['reservation_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $chk = $conn->prepare("SELECT student_id,lab,pc_number,time_in,date FROM reservations WHERE id=?");
    $chk->bind_param('i',$rid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$res) { echo json_encode(['success'=>false,'message'=>'Reservation not found.']); exit; }

    $activeChk = $conn->prepare("SELECT id FROM sitin_records WHERE student_id=? AND status='active'");
    $activeChk->bind_param('i',$res['student_id']); $activeChk->execute(); $activeChk->store_result();
    if ($activeChk->num_rows > 0) {
        $activeChk->close();
        echo json_encode(['success'=>false,'message'=>'Student is currently sitting in. Cannot approve reservation until they log out.']);
        exit;
    }
    $activeChk->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE reservations SET status='approved' WHERE id=?");
        $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();

        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available = 0, reserved_by = ?, reservation_id = ?, reserved_date = ?, reserved_time = ? WHERE lab = ? AND pc_number = ?");
            $pcStmt->bind_param('iisssi', $res['student_id'], $rid, $res['date'], $res['time_in'], $res['lab'], $res['pc_number']);
            $pcStmt->execute();
            $pcStmt->close();
        }

        $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,?, 'approved', ?)");
        $adminName = $_SESSION['name'] ?? 'Admin';
        $logStmt->bind_param('iss',$rid,$adminName,$notes); $logStmt->execute(); $logStmt->close();

        $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,?,?, 'reservation')");
        $title = "Reservation Approved";
        $message = "Your reservation for Lab {$res['lab']} at {$res['time_in']} on {$res['date']} has been approved.";
        $notifStmt->bind_param('iss',$res['student_id'],$title,$message); $notifStmt->execute(); $notifStmt->close();
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Reservation approved.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to approve reservation.']);
    }
    exit;
}

if ($action === 'reject_reservation') {
    $rid = intval($_POST['reservation_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $chk = $conn->prepare("SELECT student_id,lab,pc_number FROM reservations WHERE id=?");
    $chk->bind_param('i',$rid); $chk->execute();
    $res = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$res) { echo json_encode(['success'=>false,'message'=>'Reservation not found.']); exit; }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE reservations SET status='rejected' WHERE id=?");
        $stmt->bind_param('i',$rid); $stmt->execute(); $stmt->close();

        if ($res['pc_number'] > 0) {
            $pcStmt = $conn->prepare("UPDATE pc_status SET is_available = 1, reserved_by = NULL, reservation_id = NULL, reserved_date = NULL, reserved_time = NULL WHERE lab = ? AND pc_number = ?");
            $pcStmt->bind_param('si', $res['lab'], $res['pc_number']);
            $pcStmt->execute();
            $pcStmt->close();
        }

        $logStmt = $conn->prepare("INSERT INTO reservation_logs (reservation_id,admin_name,action,notes) VALUES (?,?,'rejected',?)");
        $adminName = $_SESSION['name'] ?? 'Admin';
        $logStmt->bind_param('iss',$rid,$adminName,$notes); $logStmt->execute(); $logStmt->close();
        
        $notifStmt = $conn->prepare("INSERT INTO notifications (student_id,title,message,type) VALUES (?,?,?,'reservation')");
        $title = "Reservation Rejected";
        $message = "Your reservation has been rejected. Reason: {$notes}";
        $notifStmt->bind_param('iss',$res['student_id'],$title,$message); $notifStmt->execute(); $notifStmt->close();
        
        $conn->commit();
        echo json_encode(['success'=>true,'message'=>'Reservation rejected.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to reject reservation.']);
    }
    exit;
}

if ($action === 'get_reservation_logs') {
    $res = $conn->query("SELECT rl.id,rl.reservation_id,rl.admin_name,rl.action,rl.notes,rl.created_at,r.id_number,r.student_name FROM reservation_logs rl JOIN (SELECT r.id,CONCAT(s.id_number,' - ',s.first_name,' ',s.last_name) AS student_name,s.id_number FROM reservations r JOIN students s ON s.id=r.student_id) r ON r.id=rl.reservation_id ORDER BY rl.created_at DESC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_pc_status') {
    $lab = trim($_GET['lab'] ?? $_POST['lab'] ?? '');
    if (!$lab) { echo json_encode(['success'=>false,'message'=>'Lab required.']); exit; }

    $res = $conn->prepare("SELECT pc_number,is_available FROM pc_status WHERE lab=? ORDER BY pc_number");
    $res->bind_param('s',$lab); $res->execute();
    $result = $res->get_result();
    $dbMap = [];
    while ($r = $result->fetch_assoc()) $dbMap[intval($r['pc_number'])] = $r;
    $res->close();

    // Always return all 50 PCs; fill missing ones as available
    $rows = [];
    for ($i = 1; $i <= 50; $i++) {
        if (isset($dbMap[$i])) {
            $rows[] = $dbMap[$i];
        } else {
            $rows[] = ['pc_number' => (string)$i, 'is_available' => 1];
        }
    }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'update_pc_status') {
    $lab = trim($_POST['lab'] ?? '');
    $pc_number = intval($_POST['pc_number'] ?? 0);
    $is_available = intval($_POST['is_available'] ?? 1);

    $stmt = $conn->prepare("UPDATE pc_status SET is_available=? WHERE lab=? AND pc_number=?");
    $stmt->bind_param('isi',$is_available,$lab,$pc_number);
    echo $stmt->execute()
        ? json_encode(['success'=>true,'message'=>'PC status updated.'])
        : json_encode(['success'=>false,'message'=>'Failed to update PC status.']);
    $stmt->close(); exit;
}

if ($action === 'expire_old_reservations') {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id,lab,pc_number FROM reservations WHERE status='approved' AND date < CURDATE() AND checked_in=0");
        $stmt->execute();
        $expiredRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $upd = $conn->prepare("UPDATE reservations SET status='expired' WHERE status='approved' AND date < CURDATE() AND checked_in=0");
        $upd->execute();
        $affected = $upd->affected_rows;
        $upd->close();

        $pcStmt = $conn->prepare("UPDATE pc_status SET is_available=1, reserved_by=NULL, reservation_id=NULL, reserved_date=NULL, reserved_time=NULL WHERE lab=? AND pc_number=?");
        foreach ($expiredRows as $row) {
            if (intval($row['pc_number']) > 0) {
                $pcStmt->bind_param('si', $row['lab'], $row['pc_number']);
                $pcStmt->execute();
            }
        }
        $pcStmt->close();

        $conn->commit();
        echo json_encode(['success'=>true,'message'=>"Expired $affected old reservation(s)."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>'Failed to expire old reservations.']);
    }
    exit;
}

if ($action === 'get_feedback_reports') {
    $filter = $_POST['filter'] ?? 'all';

    $where = "1=1";
    if ($filter === 'recent') {
        $where = "f.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filter === 'low') {
        $where = "f.rating <= 2";
    }

    $statsQuery = "SELECT
        COUNT(*) AS total,
        ROUND(AVG(f.rating), 1) AS avg_rating,
        SUM(CASE WHEN f.rating >= 4 THEN 1 ELSE 0 END) AS excellent,
        SUM(CASE WHEN f.rating <= 2 THEN 1 ELSE 0 END) AS poor
        FROM feedback f WHERE $where";
    
    $statsRes = $conn->query($statsQuery);
    $stats = $statsRes->fetch_assoc();

    $fbQuery = "SELECT
        f.id, f.rating, f.comments, f.created_at,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.id_number,
        sr.purpose, sr.lab, sr.login_time, sr.logout_time, sr.date
        FROM feedback f
        JOIN students s ON s.id = f.student_id
        LEFT JOIN sitin_records sr ON sr.id = f.sitin_id
        WHERE $where
        ORDER BY f.created_at DESC";
    
    $fbRes = $conn->query($fbQuery);
    $feedback = [];
    while ($row = $fbRes->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'feedback' => $feedback
        ]
    ]); exit;
}

if ($action === 'get_system_health') {
    $issues = [];

    $q1 = $conn->query("
        SELECT r.id, r.lab, r.pc_number, r.status
        FROM reservations r
        LEFT JOIN pc_status p ON p.lab = r.lab AND p.pc_number = r.pc_number
        WHERE r.status IN ('approved','checked_in')
          AND r.pc_number IS NOT NULL
          AND r.pc_number > 0
          AND (p.id IS NULL OR p.is_available = 1)
    ");
    while ($row = $q1->fetch_assoc()) {
        $issues[] = [
            'type' => 'reservation_pc_mismatch',
            'severity' => 'high',
            'label' => 'Reserved slot marked available',
            'details' => "Reservation #{$row['id']} ({$row['status']}) in Lab {$row['lab']} PC {$row['pc_number']} is not locked in PC status."
        ];
    }

    $q2 = $conn->query("
        SELECT r.id, r.student_id, s.id_number, r.date, r.lab
        FROM reservations r
        JOIN students s ON s.id = r.student_id
        LEFT JOIN sitin_records sr
          ON sr.student_id = r.student_id
         AND sr.date = r.date
         AND sr.status = 'active'
        WHERE r.status = 'checked_in'
          AND sr.id IS NULL
    ");
    while ($row = $q2->fetch_assoc()) {
        $issues[] = [
            'type' => 'checkedin_without_active_sitin',
            'severity' => 'high',
            'label' => 'Checked-in reservation without active sit-in',
            'details' => "Reservation #{$row['id']} for student {$row['id_number']} (Lab {$row['lab']}, {$row['date']}) is checked_in but has no active sit-in."
        ];
    }

    $q3 = $conn->query("
        SELECT p.lab, p.pc_number
        FROM pc_status p
        LEFT JOIN reservations r
          ON r.lab = p.lab
         AND r.pc_number = p.pc_number
         AND r.status IN ('approved','checked_in')
        WHERE p.is_available = 0
          AND r.id IS NULL
    ");
    while ($row = $q3->fetch_assoc()) {
        $issues[] = [
            'type' => 'occupied_pc_without_reservation',
            'severity' => 'medium',
            'label' => 'Occupied PC without matching reservation',
            'details' => "Lab {$row['lab']} PC {$row['pc_number']} is occupied in PC status but no approved/checked_in reservation references it."
        ];
    }

    $q4 = $conn->query("
        SELECT id, id_number, remaining_session
        FROM students
        WHERE remaining_session < 0
    ");
    while ($row = $q4->fetch_assoc()) {
        $issues[] = [
            'type' => 'negative_sessions',
            'severity' => 'high',
            'label' => 'Negative remaining sessions',
            'details' => "Student {$row['id_number']} has invalid remaining_session = {$row['remaining_session']}."
        ];
    }

    $q5 = $conn->query("
        SELECT id, student_id, date, lab
        FROM sitin_records
        WHERE status = 'active' AND logout_time IS NOT NULL
    ");
    while ($row = $q5->fetch_assoc()) {
        $issues[] = [
            'type' => 'active_with_logout',
            'severity' => 'medium',
            'label' => 'Active sit-in has logout time',
            'details' => "Sit-in #{$row['id']} for student_id {$row['student_id']} ({$row['date']}, Lab {$row['lab']}) is active but already has logout_time."
        ];
    }

    $summary = [
        'total_issues' => count($issues),
        'high' => count(array_filter($issues, function($i){ return $i['severity'] === 'high'; })),
        'medium' => count(array_filter($issues, function($i){ return $i['severity'] === 'medium'; })),
        'low' => count(array_filter($issues, function($i){ return $i['severity'] === 'low'; })),
        'checked_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'issues' => $issues
        ]
    ]); exit;
}

if ($action === 'get_analytics') {
    $period = trim($_POST['period'] ?? 'all');

    $dateFilter = '';
    if ($period === 'week') {
        $dateFilter = "AND sr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($period === 'month') {
        $dateFilter = "AND MONTH(sr.date)=MONTH(CURDATE()) AND YEAR(sr.date)=YEAR(CURDATE())";
    }

    // Per-student raw stats
    $sql = "
        SELECT
            s.id,
            s.id_number,
            CONCAT(s.first_name,' ',s.last_name) AS name,
            s.course,
            s.year_level,
            COUNT(sr.id) AS sessions,
            SUM(CASE WHEN sr.status='done' AND sr.logout_time IS NOT NULL
                THEN TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time)
                ELSE 0 END) AS total_minutes
        FROM students s
        JOIN sitin_records sr ON sr.student_id = s.id
        WHERE sr.status = 'done' $dateFilter
        GROUP BY s.id
        HAVING sessions > 0
        ORDER BY sessions DESC, total_minutes DESC
    ";
    $res  = $conn->query($sql);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    // Compute scores: 50% points(sessions), 30% hours, 20% tasks
    $maxSessions = 0; $maxMinutes = 0;
    foreach ($rows as $r) {
        if ($r['sessions']      > $maxSessions) $maxSessions = $r['sessions'];
        if ($r['total_minutes'] > $maxMinutes)  $maxMinutes  = $r['total_minutes'];
    }

    $students = [];
    foreach ($rows as $r) {
        $ptScore   = $maxSessions > 0 ? ($r['sessions']      / $maxSessions) * 50 : 0;
        $hrScore   = $maxMinutes  > 0 ? ($r['total_minutes'] / $maxMinutes)  * 30 : 0;
        $taskScore = $maxSessions > 0 ? ($r['sessions']      / $maxSessions) * 20 : 0;
        $total     = round($ptScore + $hrScore + $taskScore, 1);
        $students[] = [
            'id_number' => $r['id_number'],
            'name'      => $r['name'],
            'course'    => $r['course'],
            'year_level'=> $r['year_level'],
            'sessions'  => intval($r['sessions']),
            'hours'     => round($r['total_minutes'] / 60, 1),
            'score'     => $total,
        ];
    }
    usort($students, function($a, $b){ return $b['score'] <=> $a['score']; });
    $topScore = count($students) > 0 ? $students[0]['score'] : null;

    // Summary metrics
    $dfSimple = $dateFilter ? ' ' . str_replace('AND sr.', 'AND ', $dateFilter) : '';
    $totalMinsQ = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE,login_time,logout_time)) AS m FROM sitin_records WHERE status='done' AND logout_time IS NOT NULL" . $dfSimple);
    $totalMins  = $totalMinsQ->fetch_assoc()['m'] ?? 0;
    $totalHrs   = round($totalMins / 60, 1);

    $totalTasksQ    = $conn->query("SELECT COUNT(*) AS c FROM sitin_records WHERE status='done'" . $dfSimple);
    $totalTasks     = $totalTasksQ->fetch_assoc()['c'];

    $activeStQ      = $conn->query("SELECT COUNT(DISTINCT student_id) AS c FROM sitin_records WHERE status='done'" . $dfSimple);
    $activeStudents = $activeStQ->fetch_assoc()['c'];

    // Most visited labs
    $labQ = $conn->query("SELECT lab, COUNT(*) AS cnt FROM sitin_records WHERE status='done'" . $dfSimple . " GROUP BY lab ORDER BY cnt DESC");
    $labs = [];
    while ($r = $labQ->fetch_assoc()) $labs[] = $r;

    // Purpose breakdown
    $purQ = $conn->query("SELECT purpose, COUNT(*) AS cnt FROM sitin_records WHERE status='done'" . $dfSimple . " GROUP BY purpose ORDER BY cnt DESC");
    $purposes = [];
    while ($r = $purQ->fetch_assoc()) $purposes[] = $r;

    echo json_encode([
        'success' => true,
        'data' => [
            'top_score'       => $topScore,
            'total_hours'     => $totalHrs,
            'total_tasks'     => $totalTasks,
            'active_students' => $activeStudents,
            'students'        => $students,
            'labs'            => $labs,
            'purposes'        => $purposes,
        ]
    ]); exit;
}

if ($action === 'get_leaderboard') {
    $period = trim($_POST['period'] ?? $_GET['period'] ?? 'all');

    $dateFilter = '';
    if ($period === 'week') {
        $dateFilter = "AND sr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($period === 'month') {
        $dateFilter = "AND MONTH(sr.date)=MONTH(CURDATE()) AND YEAR(sr.date)=YEAR(CURDATE())";
    }

    $sql = "
        SELECT
            s.id_number,
            CONCAT(s.first_name,' ',s.last_name) AS name,
            s.course,
            s.year_level,
            COUNT(sr.id) AS total_sessions,
            SUM(CASE WHEN sr.status='done' AND sr.logout_time IS NOT NULL
                THEN TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time)
                ELSE 0 END) AS total_minutes
        FROM students s
        JOIN sitin_records sr ON sr.student_id = s.id
        WHERE sr.status = 'done' $dateFilter
        GROUP BY s.id
        ORDER BY total_sessions DESC, total_minutes DESC
        LIMIT 10
    ";

    $res = $conn->query($sql);
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['total_hours'] = round($r['total_minutes'] / 60, 1);
        $rows[] = $r;
    }
    echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

if ($action === 'get_ai_recommendations') {
    $student_id = intval($_POST['student_id'] ?? 0);
    if (!$student_id) { echo json_encode(['success'=>false,'message'=>'Student ID required.']); exit; }

    // Fetch student profile
    $stmt = $conn->prepare("SELECT id_number,CONCAT(first_name,' ',last_name) AS name,course,year_level,remaining_session FROM students WHERE id=?");
    $stmt->bind_param('i',$student_id); $stmt->execute();
    $stu = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$stu) { echo json_encode(['success'=>false,'message'=>'Student not found.']); exit; }

    // Fetch sit-in history summary
    $hist = $conn->prepare("
        SELECT purpose, lab,
               COUNT(*) AS cnt,
               SUM(CASE WHEN logout_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,login_time,logout_time) ELSE 0 END) AS total_mins
        FROM sitin_records WHERE student_id=? AND status='done'
        GROUP BY purpose, lab ORDER BY cnt DESC
    ");
    $hist->bind_param('i',$student_id); $hist->execute();
    $histRows = $hist->get_result()->fetch_all(MYSQLI_ASSOC); $hist->close();

    // Total stats
    $totQ = $conn->prepare("SELECT COUNT(*) AS sessions, SUM(CASE WHEN logout_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,login_time,logout_time) ELSE 0 END) AS mins FROM sitin_records WHERE student_id=? AND status='done'");
    $totQ->bind_param('i',$student_id); $totQ->execute();
    $totals = $totQ->get_result()->fetch_assoc(); $totQ->close();

    // Build context string
    $histText = '';
    foreach ($histRows as $h) {
        $hrs = round($h['total_mins']/60,1);
        $histText .= "- {$h['purpose']} in Lab {$h['lab']}: {$h['cnt']} sessions, {$hrs}h total\n";
    }
    if (!$histText) $histText = 'No completed sit-in sessions yet.';

    $totalSessions = intval($totals['sessions']);
    $totalHours    = round($totals['mins']/60,1);
    $sessionsLeft  = intval($stu['remaining_session']);

    $prompt = "You are an academic advisor AI for a university computer laboratory monitoring system (CCS Sit-In Monitoring System).\n\nAnalyze the following student's lab usage data and provide exactly 5 personalized, actionable recommendations to help them improve their lab time, academic performance, and session efficiency.\n\nStudent Profile:\n- Name: {$stu['name']}\n- ID: {$stu['id_number']}\n- Course: {$stu['course']}, Year {$stu['year_level']}\n- Total Completed Sessions: {$totalSessions}\n- Total Lab Hours: {$totalHours}h\n- Remaining Sessions This Semester: {$sessionsLeft}\n\nSit-In History (purpose + lab + usage):\n{$histText}\n\nRespond ONLY with a valid JSON array of exactly 5 objects. Each object must have these exact keys:\n- \"title\": short recommendation title (5-8 words)\n- \"description\": actionable explanation (2-3 sentences, specific to this student's data)\n- \"tag\": one short category label (e.g. \"Time Management\", \"Lab Usage\", \"Skill Building\", \"Session Planning\", \"Academic Focus\")\n\nReturn ONLY the JSON array. No preamble, no markdown, no explanation.";

    // Call Claude API
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 1000,
        'messages'   => [['role'=>'user','content'=>$prompt]]
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01',
            'x-api-key: ' . (getenv('ANTHROPIC_API_KEY') ?: '')
        ]
    ]);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { echo json_encode(['success'=>false,'message'=>'API connection error: '.$curlErr]); exit; }

    $apiData = json_decode($response, true);
    if (!isset($apiData['content'][0]['text'])) {
        $errMsg = isset($apiData['error']['message']) ? $apiData['error']['message'] : 'Unexpected API response.';
        echo json_encode(['success'=>false,'message'=>$errMsg]); exit;
    }

    $text = trim($apiData['content'][0]['text']);
    // Strip markdown fences if present
    $text = preg_replace('/^```json\s*/i','',$text);
    $text = preg_replace('/```\s*$/','',$text);
    $recs = json_decode(trim($text), true);

    if (!is_array($recs)) { echo json_encode(['success'=>false,'message'=>'AI returned invalid data. Please try again.']); exit; }

    echo json_encode(['success'=>true,'recommendations'=>$recs]); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
$conn->close();
