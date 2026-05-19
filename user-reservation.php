<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';
$sid = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT id_number,first_name,last_name,remaining_session FROM students WHERE id=?");
$stmt->bind_param('i',$sid); $stmt->execute();
$u = $stmt->get_result()->fetch_assoc(); $stmt->close();

$todayResStmt = $conn->prepare("SELECT id,lab,pc_number,time_in FROM reservations WHERE student_id=? AND status='approved' AND date=CURDATE() LIMIT 1");
$todayResStmt->bind_param('i',$sid); $todayResStmt->execute();
$todayRes = $todayResStmt->get_result()->fetch_assoc();
$todayResStmt->close();

$activeResStmt = $conn->prepare("SELECT sr.id,sr.lab,r.pc_number FROM sitin_records sr LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status='checked_in' WHERE sr.student_id=? AND sr.status='active' LIMIT 1");
$activeResStmt->bind_param('i',$sid); $activeResStmt->execute();
$activeRes = $activeResStmt->get_result()->fetch_assoc();
$activeResStmt->close();

$activeSitinStmt = $conn->prepare("SELECT id FROM sitin_records WHERE student_id=? AND status='active' LIMIT 1");
$activeSitinStmt->bind_param('i',$sid); $activeSitinStmt->execute();
$isActiveSitin = $activeSitinStmt->get_result()->num_rows > 0;
$activeSitinStmt->close();

// Global reservation toggle
$settingRes = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='reservation_enabled' LIMIT 1");
$reservationsEnabled = true;
if ($settingRes && $settingRes->num_rows > 0) {
    $row = $settingRes->fetch_assoc();
    $reservationsEnabled = ($row['setting_value'] === '1');
}

// Per-lab reservation toggle statuses
$allLabs = ['524','526','528','530','542','544'];
$labOpen = [];
foreach ($allLabs as $labNum) {
    $key = 'lab_reservation_' . $labNum;
    $r = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='" . $conn->real_escape_string($key) . "' LIMIT 1");
    if ($r && $r->num_rows > 0) {
        $row = $r->fetch_assoc();
        $labOpen[$labNum] = ($row['setting_value'] === '1');
    } else {
        $labOpen[$labNum] = true; // default open
    }
}

$conn->close();
$full_name = htmlspecialchars($u['first_name'].' '.$u['last_name']);
$isSittingIn = isset($_SESSION['is_sitting_in']) ? $_SESSION['is_sitting_in'] : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reservation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.8rem;}
    .reservation-card{background:var(--white);border-radius:18px;box-shadow:0 2px 20px rgba(124,58,237,.07);padding:2.2rem 2.5rem;max-width:900px;margin:0 auto;}
    .btn-reserve{background:var(--purple-main);border:none;color:#fff;border-radius:10px;padding:.7rem 2.4rem;font-weight:700;font-size:1rem;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-reserve:hover{background:var(--purple-deep);}
    .btn-reserve:disabled{opacity:.5;cursor:not-allowed;}

    .lab-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:1.5rem 0;}

    /* Open lab card */
    .lab-card{background:linear-gradient(135deg,#f3f0ff 0%,#ede9fe 100%);border:2px solid var(--purple-pale);border-radius:14px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s;position:relative;}
    .lab-card:hover{border-color:var(--purple-main);transform:translateY(-2px);box-shadow:0 4px 12px rgba(124,58,237,.15);}
    .lab-card.selected{border-color:var(--purple-main);background:linear-gradient(135deg,var(--purple-pale) 0%,#ddd6fe 100%);box-shadow:0 4px 16px rgba(124,58,237,.25);}

    /* Closed lab card */
    .lab-card.lab-closed{background:linear-gradient(135deg,#fff1f2 0%,#ffe4e6 100%);border-color:#fecdd3;cursor:not-allowed;pointer-events:none;}
    .lab-card.lab-closed:hover{transform:none;box-shadow:none;}
    .lab-closed-badge{position:absolute;top:.5rem;right:.5rem;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;border-radius:6px;padding:.15rem .45rem;letter-spacing:.03em;text-transform:uppercase;}

    .lab-card-icon{font-size:2.5rem;margin-bottom:.5rem;}
    .lab-card-name{font-weight:700;font-size:1.2rem;color:var(--purple-deep);}
    .lab-card-status{font-size:.8rem;margin-top:.3rem;font-weight:600;}
    .lab-card-status.open{color:#16a34a;}
    .lab-card-status.closed{color:#dc2626;}

    .pc-grid-container{margin:1.5rem 0;display:none;}
    .pc-grid-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}
    .pc-grid-title{font-weight:700;font-size:1.1rem;color:var(--purple-deep);}
    .pc-legend{display:flex;gap:1rem;font-size:.8rem;}
    .pc-legend-item{display:flex;align-items:center;gap:.3rem;}
    .pc-legend-dot{width:14px;height:14px;border-radius:4px;}
    .pc-legend-dot.available{background:#22c55e;}
    .pc-legend-dot.occupied{background:#ef4444;}
    .pc-legend-dot.selected{background:#7c3aed;}
    .pc-grid{display:grid;grid-template-columns:repeat(10,1fr);gap:.5rem;max-width:100%;margin:0 auto;}
    .pc-seat{background:#f8f5ff;border:2px solid #e5e7eb;border-radius:8px;padding:.6rem .3rem;text-align:center;cursor:pointer;transition:all .15s;position:relative;}
    .pc-seat:hover:not(.occupied){border-color:var(--purple-main);background:#ede9fe;transform:scale(1.05);}
    .pc-seat.available{background:#f0fdf4;border-color:#bbf7d0;}
    .pc-seat.available:hover{background:#dcfce7;border-color:#22c55e;}
    .pc-seat.occupied{background:#fef2f2;border-color:#fecaca;cursor:not-allowed;opacity:.7;}
    .pc-seat.selected{background:#ede9fe;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.2);}
    .pc-seat-icon{font-size:1.2rem;margin-bottom:.1rem;}
    .pc-seat-number{font-weight:700;font-size:.75rem;color:#333;}
    .pc-seat-status{font-size:.6rem;color:#666;margin-top:.1rem;}
    .cinema-screen{background:linear-gradient(180deg,#e5e7eb 0%,#f3f4f6 100%);border-radius:8px;padding:.5rem;text-align:center;margin-bottom:1.5rem;font-size:.8rem;font-weight:600;color:#666;letter-spacing:.1em;text-transform:uppercase;}
    .cinema-screen::before{content:'';display:block;width:80%;height:3px;background:linear-gradient(90deg,transparent,#9ca3af,transparent);margin:0 auto .3rem;}
    .res-form-section{margin-top:1.5rem;padding-top:1.5rem;border-top:2px solid var(--purple-pale);}
    .res-field-label{font-size:.87rem;font-weight:700;color:#555;margin-bottom:.3rem;}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #simsToastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;}
    .alert-box{background:#fef3c7;border:2px solid #f59e0b;border-radius:10px;padding:1rem;margin-bottom:1.5rem;display:none;}
    .alert-box.show{display:block;}
    .alert-box-title{font-weight:700;color:#92400e;font-size:.9rem;margin-bottom:.3rem;}
    .alert-box-text{color:#78350f;font-size:.85rem;}
    .lab-legend{display:flex;gap:1.2rem;font-size:.8rem;margin-bottom:.5rem;flex-wrap:wrap;}
    .lab-legend-item{display:flex;align-items:center;gap:.4rem;font-weight:600;}
    .lab-legend-dot{width:12px;height:12px;border-radius:3px;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="user-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <span class="nav-label ms-3 d-none d-lg-block">Reservation</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav" style="border-color:rgba(255,255,255,0.3)"><span class="navbar-toggler-icon" style="filter:invert(1)"></span></button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="dashNav">
      <ul class="navbar-nav align-items-center gap-1 me-2">
        <li class="nav-item dropdown">
          <button class="btn notif-btn dropdown-toggle" data-bs-toggle="dropdown" id="notifBtn">Notifications <span id="notifBadge" class="badge bg-danger" style="display:none;font-size:.65rem;">0</span></button>
          <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown" style="min-width:340px;max-height:400px;overflow-y:auto;">
            <li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><span class="dropdown-item text-muted" style="font-size:.85rem;" id="notifLoading">Loading notifications...</span></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link-custom" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-history.php">History</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-sitin-sessions.php">Sit-In Sessions</a></li>
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-reservation.php">Reservation</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-software.php">Lab Software</a></li>
      </ul>
      <button class="btn btn-logout" onclick="document.getElementById('logoutModal').style.display='flex'">Logout</button>
    </div>
  </div>
</nav>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <?php if($todayRes || $activeRes): ?>
    <div class="reservation-card mb-4" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:2px solid #22c55e;">
      <h2 class="page-title" style="color:#16a34a;margin-bottom:1rem;">
        <?php if($activeRes): ?>🟢 Currently Sitting In (Reservation)
        <?php else: ?>📋 Approved Reservation Ready<?php endif; ?>
      </h2>
      <div class="row align-items-center">
        <div class="col-md-8">
          <?php if($todayRes): ?>
            <div style="font-size:1.1rem;font-weight:700;color:#166534;margin-bottom:.5rem;">
              Lab <?=htmlspecialchars($todayRes['lab'])?><?php if($todayRes['pc_number']): ?> - PC <?=$todayRes['pc_number']?><?php endif; ?>
            </div>
            <div style="font-size:.9rem;color:#15803d;">Scheduled Time: <?=date('h:i:sa',strtotime($todayRes['time_in']))?> | Status: <strong style="color:#f59e0b;">Ready to Check-In</strong></div>
          <?php endif; ?>
          <?php if($activeRes): ?>
            <div style="font-size:1.1rem;font-weight:700;color:#166534;margin-bottom:.5rem;">
              Lab <?=htmlspecialchars($activeRes['lab'])?><?php if($activeRes['pc_number']): ?> - PC <?=$activeRes['pc_number']?><?php endif; ?>
            </div>
            <div style="font-size:.9rem;color:#15803d;">You are currently sitting in. Remember to log out when done!</div>
          <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
          <?php if($todayRes && !$activeRes): ?>
            <button class="btn" onclick="checkInReservation()" style="background:#16a34a;color:#fff;border:none;border-radius:10px;padding:.7rem 2rem;font-weight:700;font-size:1rem;font-family:'Nunito',sans-serif;cursor:pointer;">✓ Check-In Now</button>
          <?php elseif($activeRes): ?>
            <button class="btn" onclick="checkOutReservation()" style="background:#ef4444;color:#fff;border:none;border-radius:10px;padding:.7rem 2rem;font-weight:700;font-size:1rem;font-family:'Nunito',sans-serif;cursor:pointer;">🚪 Log Out</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="reservation-card">
      <h2 class="page-title">Reserve a PC</h2>

      <?php if (!$reservationsEnabled): ?>
      <div style="background:#fef2f2;border:2px solid #fecaca;border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">🚫</div>
        <div style="font-weight:700;font-size:1.1rem;color:#dc2626;margin-bottom:.5rem;">Reservations are Currently Disabled</div>
        <div style="color:#7f1d1d;font-size:.9rem;">The administrator has temporarily disabled the reservation system. Please check back later or contact the lab administrator.</div>
      </div>
      <?php endif; ?>

      <div class="alert-box" id="sittingInAlert"<?= $isActiveSitin ? ' style="display:block;"' : '' ?>>
        <div class="alert-box-title">⚠️ You are currently sitting in</div>
        <div class="alert-box-text">You cannot make a reservation while you are still logged in a lab session. Please log out first before reserving.</div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="res-field-label">ID Number:</div><div style="background:#f8f5ff;padding:.5rem;border-radius:8px;"><?=htmlspecialchars($u['id_number'])?></div></div>
        <div class="col-md-6"><div class="res-field-label">Student Name:</div><div style="background:#f8f5ff;padding:.5rem;border-radius:8px;"><?=$full_name?></div></div>
        <div class="col-md-6"><div class="res-field-label">Remaining Sessions:</div><div style="background:#f8f5ff;padding:.5rem;border-radius:8px;"><?=intval($u['remaining_session'])?></div></div>
      </div>

      <div id="step1LabSelection"<?= ($isActiveSitin || !$reservationsEnabled) ? ' style="opacity:.5;pointer-events:none;"' : '' ?>>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin:1.5rem 0 .5rem;">
          <h5 style="font-weight:700;color:var(--purple-deep);margin:0;">Step 1: Select a Laboratory</h5>
          <div class="lab-legend">
            <div class="lab-legend-item"><div class="lab-legend-dot" style="background:#a78bfa;border:2px solid #7c3aed;"></div> Open</div>
            <div class="lab-legend-item"><div class="lab-legend-dot" style="background:#fca5a5;border:2px solid #ef4444;"></div> Closed</div>
          </div>
        </div>
        <div class="lab-grid" id="labGrid">
          <?php foreach ($allLabs as $labNum):
            $isOpen = $labOpen[$labNum];
          ?>
          <div class="lab-card<?= !$isOpen ? ' lab-closed' : '' ?>"
               data-lab="<?= $labNum ?>"
               <?= $isOpen ? "onclick=\"selectLab('{$labNum}')\"" : '' ?>>
            <?php if (!$isOpen): ?>
              <span class="lab-closed-badge">Closed</span>
            <?php endif; ?>
            <div class="lab-card-icon"><?= $isOpen ? '🖥️' : '🚫' ?></div>
            <div class="lab-card-name">Lab <?= $labNum ?></div>
            <div class="lab-card-status <?= $isOpen ? 'open' : 'closed' ?>">
              <?= $isOpen ? '✓ Reservations Open' : '✗ Reservations Closed' ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pc-grid-container" id="step2PcSelection" style="display:none;<?= $isActiveSitin ? 'display:none!important;' : '' ?>">
        <div class="pc-grid-header">
          <div class="pc-grid-title">Step 2: Select a PC in <span id="selectedLabName">Lab</span></div>
          <div class="pc-legend">
            <div class="pc-legend-item"><div class="pc-legend-dot available"></div> Available</div>
            <div class="pc-legend-item"><div class="pc-legend-dot occupied"></div> Occupied</div>
            <div class="pc-legend-item"><div class="pc-legend-dot selected"></div> Selected</div>
          </div>
        </div>
        <div class="cinema-screen">FRONT OF LAB</div>
        <div class="pc-grid" id="pcGrid"></div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-cancel" onclick="backToLabSelection()">← Back to Labs</button>
        </div>
      </div>

      <div class="res-form-section" id="step3ReservationForm" style="display:none;<?= $isActiveSitin ? 'display:none!important;' : '' ?>">
        <h5 style="font-weight:700;color:var(--purple-deep);margin-bottom:1rem;">Step 3: Reservation Details</h5>
        <div class="row g-3">
          <div class="col-12">
            <div class="res-field-label">Selected PC:</div>
            <div id="selectedPcDisplay" style="background:#f0fdf4;padding:.5rem;border-radius:8px;font-weight:700;color:#16a34a;"></div>
          </div>
          <div class="col-12"><label class="res-field-label">Purpose: <span class="text-danger">*</span></label>
            <select class="form-select" id="res_purpose"><option value="">-- Select Purpose --</option>
              <option>C Programming</option><option>Java Programming</option><option>C# Programming</option>
              <option>ASP.Net</option><option>PHP</option><option>Other</option>
            </select>
          </div>
          <div class="col-md-6"><label class="res-field-label">Date: <span class="text-danger">*</span></label><input type="date" class="form-control" id="res_date"></div>
          <div class="col-md-6"><label class="res-field-label">Time In: <span class="text-danger">*</span></label><input type="time" class="form-control" id="res_time"></div>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-4">
          <a href="user-dashboard.php" class="btn btn-cancel">Cancel</a>
          <button type="button" class="btn btn-reserve" id="reserveBtn" onclick="doReserve()">Submit Reservation</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:2rem;max-width:360px;width:90%;box-shadow:0 8px 40px rgba(59,7,100,.18);">
    <h5 style="font-weight:700;color:#3b0764;margin-bottom:.8rem;">Confirm Logout</h5>
    <p style="color:#555;font-size:.92rem;margin-bottom:1.5rem;">Are you sure you want to log out?</p>
    <div style="display:flex;gap:.8rem;justify-content:flex-end;">
      <button onclick="document.getElementById('logoutModal').style.display='none'" style="background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;">Cancel</button>
      <a href="php/logout.php" style="background:#ef4444;color:#fff;border-radius:8px;padding:.42rem 1.4rem;font-weight:700;font-family:'Nunito',sans-serif;text-decoration:none;">Yes, Logout</a>
    </div>
  </div>
</div>

<div id="simsToast"><span id="simsToastMsg"></span><button id="simsToastClose" onclick="simsToastHide()">&#x2715;</button></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="sims.js"></script>
<script>
var selectedLab = null, selectedPc = null, pcStatusData = [];

function checkSitinStatus() {
  simsPost('php/admin_actions.php', { action:'check_sitin_status' })
    .then(function(json) {
      if (json.success && json.is_sitting_in) {
        document.getElementById('sittingInAlert').classList.add('show');
        document.getElementById('step1LabSelection').style.opacity = '.5';
        document.getElementById('step1LabSelection').style.pointerEvents = 'none';
        document.getElementById('step2PcSelection').style.display = 'none';
        document.getElementById('step3ReservationForm').style.display = 'none';
        var reserveBtn = document.getElementById('reserveBtn');
        if (reserveBtn) { reserveBtn.disabled = true; reserveBtn.textContent = 'Cannot Reserve While Sitting In'; }
      }
    })
    .catch(function(err) { console.error('Failed to check sit-in status:', err); });
}

function loadPcStatus(lab) {
  simsPost('php/admin_actions.php', { action:'get_pc_status', lab:lab })
    .then(function(json) {
      if (!json.success) { simsToast('Failed to load PC status.', false); return; }
      pcStatusData = json.data;
      renderPcGrid(lab);
    })
    .catch(function(err) { simsToast(err.message, false); });
}

function selectLab(lab) {
  selectedLab = lab;
  selectedPc = null;
  document.querySelectorAll('.lab-card').forEach(function(c) { c.classList.remove('selected'); });
  var card = document.querySelector('.lab-card[data-lab="'+lab+'"]');
  if (card) card.classList.add('selected');
  document.getElementById('selectedLabName').textContent = 'Lab ' + lab;
  document.getElementById('step2PcSelection').style.display = 'block';
  document.getElementById('step3ReservationForm').style.display = 'none';
  loadPcStatus(lab);
  document.getElementById('step2PcSelection').scrollIntoView({ behavior:'smooth', block:'start' });
}

function backToLabSelection() {
  selectedLab = null; selectedPc = null;
  document.querySelectorAll('.lab-card').forEach(function(c) { c.classList.remove('selected'); });
  document.getElementById('step2PcSelection').style.display = 'none';
  document.getElementById('step3ReservationForm').style.display = 'none';
  document.getElementById('step1LabSelection').scrollIntoView({ behavior:'smooth', block:'start' });
}

function renderPcGrid(lab) {
  var grid = document.getElementById('pcGrid');
  grid.innerHTML = '';
  var dbMap = {};
  pcStatusData.forEach(function(pc) { dbMap[parseInt(pc.pc_number)] = pc; });
  for (var i = 1; i <= 50; i++) {
    var pcData = dbMap[i] || { pc_number: i, is_available: true };
    var isAvailable = pcData.is_available == true || pcData.is_available == 1;
    var seat = document.createElement('div');
    seat.className = 'pc-seat ' + (isAvailable ? 'available' : 'occupied');
    seat.dataset.pc = i;
    seat.innerHTML = '<div class="pc-seat-icon">'+(isAvailable?'🖥️':'🔴')+'</div>'+
                     '<div class="pc-seat-number">PC '+i+'</div>'+
                     '<div class="pc-seat-status">'+(isAvailable?'Available':'Reserved')+'</div>';
    if (isAvailable) {
      (function(pcNum) { seat.addEventListener('click', function() { selectPc(pcNum); }); })(i);
    }
    grid.appendChild(seat);
  }
}

function selectPc(pcNumber) {
  selectedPc = pcNumber;
  document.querySelectorAll('.pc-seat').forEach(function(s) { s.classList.remove('selected'); });
  var targetSeat = document.querySelector('.pc-seat[data-pc="'+pcNumber+'"]');
  if (targetSeat) targetSeat.classList.add('selected');
  document.getElementById('selectedPcDisplay').textContent = 'Lab ' + selectedLab + ' - PC ' + pcNumber;
  document.getElementById('step3ReservationForm').style.display = 'block';
  document.getElementById('step3ReservationForm').scrollIntoView({ behavior:'smooth', block:'start' });
}

function doReserve() {
  if (!selectedLab || !selectedPc) { simsToast('Please select a lab and PC.', false); return; }
  var purpose = document.getElementById('res_purpose').value;
  var date = document.getElementById('res_date').value;
  var time = document.getElementById('res_time').value;
  var btn = document.getElementById('reserveBtn');
  if (!purpose) { simsToast('Please select a purpose.', false); return; }
  if (!date) { simsToast('Please select a date.', false); return; }
  if (!time) { simsToast('Please select a time.', false); return; }
  btn.disabled = true; btn.textContent = 'Submitting...';
  simsPost('php/save_reservation.php', { purpose:purpose, lab:selectedLab, pc_number:selectedPc, time_in:time, date:date })
    .then(function(json) {
      simsToast(json.message, json.success);
      if (json.success) { setTimeout(function() { window.location.href = 'user-dashboard.php'; }, 2000); }
      else { btn.disabled = false; btn.textContent = 'Submit Reservation'; }
    })
    .catch(function(err) { simsToast(err.message, false); btn.disabled = false; btn.textContent = 'Submit Reservation'; });
}

function loadNotifications() {
  simsPost('php/admin_actions.php', { action:'get_notifications' })
    .then(function(json) {
      if (!json.success) return;
      var dropdown = document.getElementById('notifDropdown');
      var badge = document.getElementById('notifBadge');
      dropdown.innerHTML = '<li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>';
      if (json.unread > 0) { badge.textContent = json.unread; badge.style.display = 'inline'; } else { badge.style.display = 'none'; }
      if (!json.data || json.data.length === 0) { dropdown.innerHTML += '<li><span class="dropdown-item text-muted" style="font-size:.85rem;">No new notifications</span></li>'; return; }
      json.data.forEach(function(n) {
        var icon = '📢';
        if (n.type === 'reservation') icon = n.is_read ? '✅' : '📋';
        var readClass = n.is_read ? '' : 'fw-bold';
        var time = new Date(n.created_at).toLocaleString();
        dropdown.innerHTML += '<li><a class="dropdown-item '+readClass+'" style="font-size:.83rem;padding:.5rem 1rem;cursor:pointer;" onclick="markRead('+n.id+')">'+icon+' <strong>'+n.title+'</strong><br><span style="color:#666;font-size:.78rem;">'+n.message+'</span><br><span style="color:#999;font-size:.72rem;">'+time+'</span></a></li><li><hr class="dropdown-divider" style="margin:0;"></li>';
      });
      dropdown.innerHTML += '<li><a class="dropdown-item text-center" style="font-size:.8rem;color:var(--purple-main);cursor:pointer;padding:.5rem;" onclick="markAllRead()">Mark all as read</a></li>';
    })
    .catch(function(err) { console.error('Failed to load notifications:', err); });
}
function markRead(id) { simsPost('php/admin_actions.php', { action:'mark_notification_read', notification_id:id }).then(function() { loadNotifications(); }); }
function markAllRead() { simsPost('php/admin_actions.php', { action:'mark_all_notifications_read' }).then(function() { loadNotifications(); }); }
document.getElementById('notifBtn').addEventListener('shown.bs.dropdown', function() { loadNotifications(); });
setInterval(function() { loadNotifications(); }, 30000);

document.addEventListener('DOMContentLoaded', function() {
  var today = new Date().toISOString().split('T')[0];
  document.getElementById('res_date').setAttribute('min', today);
  checkSitinStatus();
});

function checkInReservation() {
  if (!confirm('Check-in for your reservation now?')) return;
  simsPost('php/admin_actions.php', { action:'check_in_reservation' })
    .then(function(json) { simsToast(json.message, json.success); if (json.success) { setTimeout(function() { location.reload(); }, 1500); } })
    .catch(function(err) { simsToast(err.message, false); });
}
function checkOutReservation() {
  if (!confirm('Log out and end your reservation session?')) return;
  simsPost('php/admin_actions.php', { action:'check_out_reservation' })
    .then(function(json) { simsToast(json.message, json.success); if (json.success) { setTimeout(function() { location.reload(); }, 1500); } })
    .catch(function(err) { simsToast(err.message, false); });
}
</script>
</body>
</html>
