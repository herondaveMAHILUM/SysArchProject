<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';
$sid = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT
        sr.id,
        sr.date,
        sr.login_time,
        sr.logout_time,
        sr.purpose,
        sr.lab,
        sr.status,
        COALESCE(r.pc_number, NULL) AS pc_number,
        CASE
            WHEN sr.logout_time IS NOT NULL
            THEN TIMESTAMPDIFF(MINUTE, sr.login_time, sr.logout_time)
            ELSE NULL
        END AS duration_minutes
    FROM sitin_records sr
    LEFT JOIN reservations r
        ON r.student_id = sr.student_id
        AND r.date = sr.date
        AND r.status IN ('checked_in','completed')
    WHERE sr.student_id = ?
    ORDER BY sr.date DESC, sr.login_time DESC
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalSessions  = count($sessions);
$doneSessions   = array_filter($sessions, fn($s) => $s['status'] === 'done');
$activeSessions = array_filter($sessions, fn($s) => $s['status'] === 'active');
$allMins        = array_sum(array_column(array_filter($sessions, fn($s) => $s['duration_minutes'] !== null), 'duration_minutes'));
$totalHrs       = round($allMins / 60, 1);
$avgMins        = count($doneSessions) > 0 ? round($allMins / count($doneSessions)) : 0;

$conn->close();

function fmtDuration($mins) {
    if ($mins === null) return '—';
    $h = floor($mins / 60);
    $m = $mins % 60;
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sit-In Sessions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:.4rem;}
    .page-subtitle{text-align:center;color:#888;font-size:.92rem;margin-bottom:1.8rem;}

    .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.8rem;}
    .stat-card{background:linear-gradient(135deg,#f3f0ff 0%,#ede9fe 100%);border:2px solid var(--purple-pale);border-radius:14px;padding:1.2rem 1rem;text-align:center;}
    .stat-card.green{background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border-color:#bbf7d0;}
    .stat-card.blue{background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border-color:#bfdbfe;}
    .stat-card.amber{background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border-color:#fde68a;}
    .stat-number{font-size:2rem;font-weight:800;color:var(--purple-deep);line-height:1;}
    .stat-card.green .stat-number{color:#16a34a;}
    .stat-card.blue .stat-number{color:#2563eb;}
    .stat-card.amber .stat-number{color:#d97706;}
    .stat-label{font-size:.75rem;font-weight:700;color:#888;margin-top:.35rem;text-transform:uppercase;letter-spacing:.05em;}

    .badge-active{background:#dcfce7;color:#16a34a;border-radius:20px;padding:.22rem .75rem;font-size:.78rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;}
    .badge-done{background:#e0e7ff;color:#4f46e5;border-radius:20px;padding:.22rem .75rem;font-size:.78rem;font-weight:700;}
    .badge-purpose{background:var(--purple-pale);color:var(--purple-main);border-radius:20px;padding:.22rem .7rem;font-size:.78rem;font-weight:600;}

    .duration-pill{display:inline-flex;align-items:center;gap:.3rem;background:#f8f5ff;border:1.5px solid var(--purple-pale);border-radius:20px;padding:.2rem .65rem;font-size:.8rem;font-weight:700;color:var(--purple-deep);}
    .duration-pill.is-active{background:#f0fdf4;border-color:#bbf7d0;color:#16a34a;}

    .pc-badge{display:inline-block;background:var(--purple-main);color:#fff;border-radius:7px;padding:.15rem .6rem;font-size:.78rem;font-weight:700;}
    .pc-badge.none{background:#e5e7eb;color:#9ca3af;}

    table.dataTable thead th{font-weight:700;color:var(--purple-deep);font-size:.86rem;}
    table.dataTable tbody td{font-size:.85rem;vertical-align:middle;}

    .live-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:livepulse 1.4s infinite;margin-right:3px;vertical-align:middle;}
    @keyframes livepulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.8);}}

    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #simsToastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="user-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <span class="nav-label ms-3 d-none d-lg-block">Sit-In Sessions</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav" style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="dashNav">
      <ul class="navbar-nav align-items-center gap-1 me-2">
        <li class="nav-item dropdown">
          <button class="btn notif-btn dropdown-toggle" data-bs-toggle="dropdown" id="notifBtn">
            Notifications <span id="notifBadge" class="badge bg-danger" style="display:none;font-size:.65rem;">0</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown" style="min-width:340px;max-height:400px;overflow-y:auto;">
            <li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><span class="dropdown-item text-muted" style="font-size:.85rem;">Loading notifications...</span></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link-custom" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-history.php">History</a></li>
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-sitin-sessions.php">Sit-In Sessions</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-reservation.php">Reservation</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-software.php">Lab Software</a></li>
      </ul>
      <button class="btn btn-logout" onclick="document.getElementById('logoutModal').style.display='flex'">Logout</button>
    </div>
  </div>
</nav>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <div class="dash-card">

      <h2 class="page-title">My Sit-In Sessions</h2>
      <p class="page-subtitle">Complete record of your laboratory sit-in history</p>

      <!-- Summary Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-number"><?= $totalSessions ?></div>
          <div class="stat-label">Total Sessions</div>
        </div>
        <div class="stat-card green">
          <div class="stat-number"><?= count($activeSessions) ?></div>
          <div class="stat-label">Currently Active</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-number"><?= $totalHrs ?>h</div>
          <div class="stat-label">Total Hours</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-number"><?= fmtDuration($avgMins) ?></div>
          <div class="stat-label">Avg. Duration</div>
        </div>
      </div>

      <!-- Sessions Table -->
      <table id="sessionsTable" class="table table-hover w-100">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th>Date</th>
            <th>Time-In</th>
            <th>Time-Out</th>
            <th>Duration</th>
            <th>Lab</th>
            <th>PC No.</th>
            <th>Purpose</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sessions)): ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">
              <div style="font-size:2.5rem;margin-bottom:.5rem;">🖥️</div>
              <div style="font-weight:600;">No sit-in sessions yet.</div>
              <div style="font-size:.85rem;">Your sessions will appear here once you sit in at a lab.</div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($sessions as $i => $s): ?>
          <?php
            $isActive = $s['status'] === 'active';
            $timeIn   = $s['login_time']  ? date('h:i:s A', strtotime($s['login_time']))  : '—';
            $timeOut  = $s['logout_time'] ? date('h:i:s A', strtotime($s['logout_time'])) : '—';
            $dateStr  = $s['date'] ? date('M d, Y', strtotime($s['date'])) : '—';
          ?>
          <tr>
            <td style="color:#bbb;font-size:.8rem;font-weight:600;"><?= $i + 1 ?></td>
            <td style="font-weight:700;color:#333;"><?= $dateStr ?></td>
            <td style="font-family:monospace;font-size:.85rem;"><?= $timeIn ?></td>
            <td style="font-family:monospace;font-size:.85rem;">
              <?php if ($isActive): ?>
                <span style="color:#16a34a;font-weight:700;font-size:.82rem;">
                  <span class="live-dot"></span>In Progress
                </span>
              <?php else: ?>
                <?= $timeOut ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isActive): ?>
                <span class="duration-pill is-active">🟢 Active</span>
              <?php elseif ($s['duration_minutes'] !== null): ?>
                <span class="duration-pill">⏱ <?= fmtDuration((int)$s['duration_minutes']) ?></span>
              <?php else: ?>
                <span style="color:#ccc;font-size:.85rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="font-weight:700;color:var(--purple-deep);">Lab <?= htmlspecialchars($s['lab']) ?></td>
            <td>
              <?php if (!empty($s['pc_number'])): ?>
                <span class="pc-badge">PC <?= htmlspecialchars($s['pc_number']) ?></span>
              <?php else: ?>
                <span class="pc-badge none">Walk-In</span>
              <?php endif; ?>
            </td>
            <td><span class="badge-purpose"><?= htmlspecialchars($s['purpose']) ?></span></td>
            <td>
              <?php if ($isActive): ?>
                <span class="badge-active"><span class="live-dot" style="margin-right:2px;"></span> Active</span>
              <?php else: ?>
                <span class="badge-done">✓ Done</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>

<!-- Logout Modal -->
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
$(function() {
  $('#sessionsTable').DataTable({
    columnDefs: [
      { orderable: false, targets: [4, 6, 7] }
    ],
    order: [[1, 'desc']],
    pageLength: 15,
    language: { emptyTable: 'No sit-in sessions found.' }
  });
});

function loadNotifications() {
  simsPost('php/admin_actions.php', { action: 'get_notifications' })
    .then(function(json) {
      if (!json.success) return;
      var dropdown = document.getElementById('notifDropdown');
      var badge    = document.getElementById('notifBadge');
      dropdown.innerHTML = '<li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>';
      if (json.unread > 0) { badge.textContent = json.unread; badge.style.display = 'inline'; } else { badge.style.display = 'none'; }
      if (!json.data || json.data.length === 0) {
        dropdown.innerHTML += '<li><span class="dropdown-item text-muted" style="font-size:.85rem;">No new notifications</span></li>';
        return;
      }
      json.data.forEach(function(n) {
        var icon = n.type === 'reservation' ? (n.is_read ? '&#x2705;' : '&#x1F4CB;') : '&#x1F4E2;';
        var readClass = n.is_read ? '' : 'fw-bold';
        var time = new Date(n.created_at).toLocaleString();
        dropdown.innerHTML += '<li><a class="dropdown-item ' + readClass + '" style="font-size:.83rem;padding:.5rem 1rem;cursor:pointer;" onclick="markRead(' + n.id + ')">' + icon + ' <strong>' + n.title + '</strong><br><span style="color:#666;font-size:.78rem;">' + n.message + '</span><br><span style="color:#999;font-size:.72rem;">' + time + '</span></a></li><li><hr class="dropdown-divider" style="margin:0;"></li>';
      });
      dropdown.innerHTML += '<li><a class="dropdown-item text-center" style="font-size:.8rem;color:var(--purple-main);cursor:pointer;padding:.5rem;" onclick="markAllRead()">Mark all as read</a></li>';
    })
    .catch(function() {});
}

function markRead(id) {
  simsPost('php/admin_actions.php', { action: 'mark_notification_read', notification_id: id }).then(loadNotifications);
}
function markAllRead() {
  simsPost('php/admin_actions.php', { action: 'mark_all_notifications_read' }).then(loadNotifications);
}
document.getElementById('notifBtn').addEventListener('shown.bs.dropdown', loadNotifications);
setInterval(loadNotifications, 30000);
</script>
</body>
</html>
