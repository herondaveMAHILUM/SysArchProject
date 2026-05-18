<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';

$sid  = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT id_number,first_name,last_name,middle_name,year_level,course,address,email,profile_pic,remaining_session FROM students WHERE id=?");
$stmt->bind_param('i',$sid); $stmt->execute();
$u = $stmt->get_result()->fetch_assoc(); $stmt->close();

$full_name = htmlspecialchars($u['first_name'].' '.$u['last_name']);
$initial   = strtoupper(substr($u['first_name'],0,1));
$pic       = $u['profile_pic'] ? 'uploads/'.htmlspecialchars($u['profile_pic']) : '';

$ann_res = $conn->query("SELECT message, created_at FROM announcements ORDER BY created_at DESC");
$anns = [];
while ($a = $ann_res->fetch_assoc()) $anns[] = $a;

$total_sitin = $conn->query("SELECT COUNT(*) AS c FROM sitin_records WHERE student_id=$sid")->fetch_assoc()['c'];
$currently   = $conn->query("SELECT COUNT(*) AS c FROM sitin_records WHERE student_id=$sid AND status='active'")->fetch_assoc()['c'];
$bk_res = $conn->query("SELECT purpose, COUNT(*) AS cnt FROM sitin_records WHERE student_id=$sid GROUP BY purpose");
$breakdown = [];
while ($r = $bk_res->fetch_assoc()) $breakdown[] = $r;

$flash      = $_SESSION['flash']      ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

$todayResStmt = $conn->prepare("SELECT id,lab,pc_number,time_in FROM reservations WHERE student_id=? AND status='approved' AND date=CURDATE() LIMIT 1");
$todayResStmt->bind_param('i',$sid); $todayResStmt->execute();
$todayRes = $todayResStmt->get_result()->fetch_assoc();
$todayResStmt->close();

$activeResStmt = $conn->prepare("SELECT sr.id,sr.lab,r.pc_number FROM sitin_records sr LEFT JOIN reservations r ON r.student_id=sr.student_id AND r.date=sr.date AND r.status='checked_in' WHERE sr.student_id=? AND sr.status='active' LIMIT 1");
$activeResStmt->bind_param('i',$sid); $activeResStmt->execute();
$activeRes = $activeResStmt->get_result()->fetch_assoc();
$activeResStmt->close();

$conn->close();

$isSittingIn = isset($_SESSION['is_sitting_in']) ? $_SESSION['is_sitting_in'] : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}
    .avatar-img{width:90px;height:90px;border-radius:50%;object-fit:cover;box-shadow:0 4px 16px rgba(124,58,237,.25);}
    .stat-pill{font-size:.95rem;font-weight:700;color:var(--purple-deep);margin-bottom:.3rem;}
    .stat-pill span{color:var(--purple-main);}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #simsToastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;flex-shrink:0;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="user-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <span class="nav-label ms-3 d-none d-lg-block">Dashboard</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav" style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
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
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-history.php">History</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-reservation.php">Reservation</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-software.php">Lab Software</a></li>
      </ul>
      <button class="btn btn-logout" onclick="document.getElementById('logoutModal').style.display='flex'">Logout</button>
    </div>
  </div>
</nav>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <div class="row g-4">
      <?php if($todayRes || $activeRes): ?>
      <div class="col-12">
        <div class="dash-card" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:2px solid #22c55e;">
          <div class="card-title-bar" style="color:#16a34a;">
            <?php if($activeRes): ?>
              🟢 Currently Sitting In (Reservation)
            <?php else: ?>
              📋 Approved Reservation Ready
            <?php endif; ?>
          </div>
          <div class="row align-items-center">
            <div class="col-md-8">
              <?php if($todayRes): ?>
                <div style="font-size:1.1rem;font-weight:700;color:#166534;margin-bottom:.5rem;">
                  Lab <?=htmlspecialchars($todayRes['lab'])?> <?php if($todayRes['pc_number']): ?> - PC <?=$todayRes['pc_number']?><?php endif; ?>
                </div>
                <div style="font-size:.9rem;color:#15803d;">
                  Scheduled Time: <?=date('h:i:sa',strtotime($todayRes['time_in']))?> |
                  Status: <strong style="color:#f59e0b;">Ready to Check-In</strong>
                  <span style="color:#15803d;margin-left:.5rem;">→ Go to <a href="user-reservation.php" style="color:#16a34a;font-weight:700;text-decoration:underline;">Reservation Page</a> to check in</span>
                </div>
              <?php endif; ?>
              <?php if($activeRes): ?>
                <div style="font-size:1.1rem;font-weight:700;color:#166534;margin-bottom:.5rem;">
                  Lab <?=htmlspecialchars($activeRes['lab'])?> <?php if($activeRes['pc_number']): ?> - PC <?=$activeRes['pc_number']?><?php endif; ?>
                </div>
                <div style="font-size:.9rem;color:#15803d;">
                  You are currently sitting in. Remember to log out when done!
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-4 text-end">
              <?php if($activeRes): ?>
                <button class="btn" onclick="checkOutReservation()" style="background:#ef4444;color:#fff;border:none;border-radius:10px;padding:.7rem 2rem;font-weight:700;font-size:1rem;font-family:'Nunito',sans-serif;cursor:pointer;">
                  🚪 Log Out
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="col-lg-3 col-md-5">
        <div class="dash-card">
          <div class="card-title-bar">Student Information</div>
          <div class="text-center mb-0">
            <?php if($pic): ?><img src="<?=$pic?>" class="avatar-img mb-2" alt="Profile">
            <?php else: ?><div class="student-avatar"><?=$initial?></div><?php endif; ?>
            <div class="student-name"><?=$full_name?></div>
          </div>
          <div class="student-info-divider"></div>
          <div class="student-detail-item"><span class="student-detail-label">ID Number</span><span class="student-detail-val"><?=htmlspecialchars($u['id_number'])?></span></div>
          <div class="student-detail-item"><span class="student-detail-label">Course</span><span class="student-detail-val"><?=htmlspecialchars($u['course'])?></span></div>
          <div class="student-detail-item"><span class="student-detail-label">Year</span><span class="student-detail-val"><?=htmlspecialchars($u['year_level'])?></span></div>
          <div class="student-detail-item"><span class="student-detail-label">Email</span><span class="student-detail-val" style="font-size:.8rem;"><?=htmlspecialchars($u['email'])?></span></div>
          <div class="student-detail-item"><span class="student-detail-label">Address</span><span class="student-detail-val"><?=htmlspecialchars($u['address'])?></span></div>
          <div class="student-detail-item"><span class="student-detail-label">Sessions Left</span><span class="student-detail-val"><?=intval($u['remaining_session'])?></span></div>
        </div>
      </div>
      <div class="col-lg-5 col-md-7">
        <div class="dash-card">
          <div class="card-title-bar">Announcements</div>
          <?php if(empty($anns)): ?>
            <p style="color:#aaa;font-size:.88rem;">No announcements yet.</p>
          <?php else: foreach($anns as $a): ?>
            <div style="padding:.7rem 0;border-bottom:1.5px solid var(--purple-pale);">
              <div style="font-size:.82rem;font-weight:700;color:var(--purple-main);margin-bottom:.25rem;">CCS Admin | <?=date('Y-M-d',strtotime($a['created_at']))?></div>
              <div style="font-size:.88rem;color:#555;line-height:1.5;"><?=htmlspecialchars($a['message'])?></div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="dash-card">
          <div class="card-title-bar">Rules and Regulations</div>
          <ol style="font-size:.88rem;color:#555;line-height:2;padding-left:1.2rem;">
            <li>Students must log in and out properly every session.</li>
            <li>Maximum of 30 sit-in sessions per semester.</li>
            <li>No food or drinks inside the laboratory.</li>
            <li>Keep the laboratory clean and tidy at all times.</li>
            <li>Use computers only for academic purposes.</li>
            <li>Report any damaged equipment to the lab admin immediately.</li>
            <li>Loud music and noise are strictly prohibited.</li>
            <li>Mobile phones must be set to silent mode.</li>
          </ol>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="dash-card">
          <div class="card-title-bar">My Sit-In Statistics</div>
          <div class="stat-pill">Total Sessions Used: <span><?= intval($total_sitin) ?></span></div>
          <div class="stat-pill">Currently Sitting In: <span><?= intval($currently) ?></span></div>
          <div class="stat-pill" style="margin-bottom:1.2rem;">Remaining Sessions: <span><?= intval($u['remaining_session']) ?></span></div>
          <?php if (!empty($breakdown)): ?>
            <canvas id="myChart" height="220"></canvas>
          <?php else: ?>
            <div style="text-align:center;padding:2rem;color:#aaa;font-size:.9rem;">No sit-in records yet. Your activity chart will appear here.</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="dash-card">
          <div class="card-title-bar">Session Usage</div>
          <div class="stat-pill" style="margin-bottom:1.2rem;">
            <span style="color:#7c3aed;"><?= intval($total_sitin) ?></span> used out of
            <span style="color:#7c3aed;"><?= intval($total_sitin + $u['remaining_session']) ?></span> total
          </div>
          <canvas id="sessionChart" height="220"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="simsToast">
  <span id="simsToastMsg"></span>
  <button id="simsToastClose" onclick="simsToastHide()">&#x2715;</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="sims.js"></script>
<script>
<?php if($flash): ?>
window.onload = function() {
  simsToast(<?=json_encode($flash)?>, <?=$flash_type==='success'?'true':'false'?>);
};
<?php endif; ?>

function loadNotifications() {
  simsPost('php/admin_actions.php', { action:'get_notifications' })
    .then(function(json) {
      if (!json.success) return;
      var dropdown = document.getElementById('notifDropdown');
      var badge = document.getElementById('notifBadge');
      dropdown.innerHTML = '<li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>';
      
      if (json.unread > 0) {
        badge.textContent = json.unread;
        badge.style.display = 'inline';
      } else {
        badge.style.display = 'none';
      }
      
      if (!json.data || json.data.length === 0) {
        dropdown.innerHTML += '<li><span class="dropdown-item text-muted" style="font-size:.85rem;">No new notifications</span></li>';
        return;
      }
      
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

function markRead(id) {
  simsPost('php/admin_actions.php', { action:'mark_notification_read', notification_id:id })
    .then(function() { loadNotifications(); });
}

function markAllRead() {
  simsPost('php/admin_actions.php', { action:'mark_all_notifications_read' })
    .then(function() { loadNotifications(); });
}

document.getElementById('notifBtn').addEventListener('shown.bs.dropdown', function() {
  loadNotifications();
});

setInterval(function() { loadNotifications(); }, 30000);

<?php if (!empty($breakdown)): ?>
var labels  = <?= json_encode(array_column($breakdown, 'purpose')) ?>;
var data    = <?= json_encode(array_column($breakdown, 'cnt')) ?>;
var colors  = ['#3b82f6','#ec4899','#f97316','#eab308','#14b8a6','#8b5cf6','#ef4444','#22c55e'];
new Chart(document.getElementById('myChart').getContext('2d'), {
  type: 'pie',
  data: { labels: labels, datasets: [{ data: data, backgroundColor: colors.slice(0, data.length), borderWidth: 2, borderColor: '#fff' }] },
  options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { family: 'Nunito', size: 12 }, padding: 14 } } } }
});
<?php endif; ?>

new Chart(document.getElementById('sessionChart').getContext('2d'), {
  type: 'pie',
  data: {
    labels: ['Sessions Used', 'Sessions Remaining'],
    datasets: [{ data: [<?= intval($total_sitin) ?>, <?= intval($u['remaining_session']) ?>], backgroundColor: ['#7c3aed', '#ede9fe'], borderWidth: 2, borderColor: '#fff' }]
  },
  options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { family: 'Nunito', size: 12 }, padding: 14 } } } }
});

function checkInReservation() {
  if (!confirm('Check-in for your reservation now?')) return;
  
  simsPost('php/admin_actions.php', { action:'check_in_reservation' })
    .then(function(json) {
      simsToast(json.message, json.success);
      if (json.success) {
        setTimeout(function() { location.reload(); }, 1500);
      }
    })
    .catch(function(err) { simsToast(err.message, false); });
}

function checkOutReservation() {
  if (!confirm('Log out and end your reservation session?')) return;
  
  simsPost('php/admin_actions.php', { action:'check_out_reservation' })
    .then(function(json) {
      simsToast(json.message, json.success);
      if (json.success) {
        setTimeout(function() { location.reload(); }, 1500);
      }
    })
    .catch(function(err) { simsToast(err.message, false); });
}
</script>
</body>
</html>
