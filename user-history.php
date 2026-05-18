<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';
$sid=$_SESSION['student_id'];
$stmt=$conn->prepare("SELECT sr.id,s.id_number,CONCAT(s.first_name,' ',s.last_name) AS name,sr.purpose,sr.lab,sr.login_time,sr.logout_time,sr.date FROM sitin_records sr JOIN students s ON s.id=sr.student_id WHERE sr.student_id=? ORDER BY sr.date DESC,sr.login_time DESC");
$stmt->bind_param('i',$sid);$stmt->execute();
$records=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();

$feedbackStmt = $conn->prepare("SELECT sitin_id FROM feedback WHERE student_id=?");
$feedbackStmt->bind_param('i',$sid);
$feedbackStmt->execute();
$feedbackResult = $feedbackStmt->get_result();
$feedbackIds = [];
while ($row = $feedbackResult->fetch_assoc()) {
    $feedbackIds[] = $row['sitin_id'];
}
$feedbackStmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}
    .badge-purpose{background:var(--purple-pale);color:var(--purple-main);border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .btn-feedback{background:#16a34a;border:none;color:#fff;border-radius:6px;padding:.28rem .85rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-feedback:hover{background:#15803d;}
    .btn-feedback-done{background:#e5e7eb;color:#999;border:none;border-radius:6px;padding:.28rem .85rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:not-allowed;}
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .star-btn{background:none;border:none;font-size:1.6rem;color:#d1d5db;cursor:pointer;padding:0;}
    .star-btn.on{color:#f59e0b;}
    .btn-submit-fb{background:#16a34a;border:none;color:#fff;border-radius:8px;padding:.42rem 1.4rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-cancel-fb{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .modal-content{border-radius:16px;border:none;box-shadow:0 8px 40px rgba(59,7,100,.18);}
    .modal-header{border-bottom:2px solid var(--purple-pale);padding:1.2rem 1.5rem;}
    .modal-title{font-weight:700;color:var(--purple-deep);font-size:1.1rem;}
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
    <span class="nav-label ms-3 d-none d-lg-block">History</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav" style="border-color:rgba(255,255,255,0.3)"><span class="navbar-toggler-icon" style="filter:invert(1)"></span></button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="dashNav">
      <ul class="navbar-nav align-items-center gap-1 me-2">
        <li class="nav-item dropdown"><button class="btn notif-btn dropdown-toggle" data-bs-toggle="dropdown" id="notifBtn">Notifications <span id="notifBadge" class="badge bg-danger" style="display:none;font-size:.65rem;">0</span></button>
          <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown" style="min-width:340px;max-height:400px;overflow-y:auto;">
            <li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><span class="dropdown-item text-muted" style="font-size:.85rem;" id="notifLoading">Loading notifications...</span></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link-custom" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-history.php">History</a></li>
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
      <h2 class="page-title">History Information</h2>
      <table id="historyTable" class="table table-hover w-100">
        <thead><tr><th>ID Number</th><th>Name</th><th>Sit Purpose</th><th>Laboratory</th><th>Login</th><th>Logout</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($records as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['id_number'])?></td><td><?=htmlspecialchars($r['name'])?></td>
            <td><span class="badge-purpose"><?=htmlspecialchars($r['purpose'])?></span></td>
            <td><?=htmlspecialchars($r['lab'])?></td>
            <td><?=$r['login_time']?date('h:i:sa',strtotime($r['login_time'])):'—'?></td>
            <td><?=$r['logout_time']?date('h:i:sa',strtotime($r['logout_time'])):'—'?></td>
            <td><?=htmlspecialchars($r['date'])?></td>
            <td>
              <?php if(in_array($r['id'], $feedbackIds)): ?>
                <button class="btn-feedback-done" disabled>✓ Submitted</button>
              <?php else: ?>
                <button class="btn-feedback" data-sitin-id="<?=$r['id']?>" onclick="openFeedback(<?=$r['id']?>, this)">Feedback</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="feedbackModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Submit Feedback</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="feedbackSitinId">
        <label class="form-label" style="font-size:.85rem;">Rate your experience</label>
        <div class="d-flex gap-2 mb-3">
          <button class="star-btn" data-v="1">&#9733;</button><button class="star-btn" data-v="2">&#9733;</button>
          <button class="star-btn" data-v="3">&#9733;</button><button class="star-btn" data-v="4">&#9733;</button>
          <button class="star-btn" data-v="5">&#9733;</button>
        </div>
        <label class="form-label" style="font-size:.85rem;">Comments</label>
        <textarea class="form-control" id="feedbackComment" rows="4" placeholder="Write your feedback here..." style="resize:none;"></textarea>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn btn-cancel-fb" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-submit-fb" id="submitFbBtn" onclick="submitFeedback()">Submit</button>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
$(function(){ $('#historyTable').DataTable({columnDefs:[{orderable:false,targets:7}],order:[[6,'desc']]}); });

var rating = 0;
var currentFeedbackBtn = null;
var stars = document.querySelectorAll('.star-btn');

stars.forEach(function(s) {
  s.addEventListener('mouseover', function() { hl(+s.dataset.v); });
  s.addEventListener('mouseout', function() { hl(rating); });
  s.addEventListener('click', function() { rating = +s.dataset.v; hl(rating); });
});

function hl(v) {
  stars.forEach(function(s) { s.classList.toggle('on', +s.dataset.v <= v); });
}

function openFeedback(id, btnEl) {
  rating = 0;
  hl(0);
  document.getElementById('feedbackComment').value = '';
  document.getElementById('feedbackSitinId').value = id;
  currentFeedbackBtn = btnEl || null;
  var submitBtn = document.getElementById('submitFbBtn');
  submitBtn.disabled = false;
  submitBtn.textContent = 'Submit';
  new bootstrap.Modal(document.getElementById('feedbackModal')).show();
}

function submitFeedback() {
  if (!rating) { simsToast('Please select a star rating.', false); return; }

  var sitinId = document.getElementById('feedbackSitinId').value;
  var submitBtn = document.getElementById('submitFbBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';

  simsPost('php/save_feedback.php', {
    sitin_id: sitinId,
    rating: rating,
    comments: document.getElementById('feedbackComment').value
  }).then(function(json) {
    simsToast(json.message, json.success);
    if (json.success) {
      bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
      // Replace the clicked button with a "Submitted" indicator
      if (currentFeedbackBtn) {
        var doneBtn = document.createElement('button');
        doneBtn.className = 'btn-feedback-done';
        doneBtn.disabled = true;
        doneBtn.textContent = '✓ Submitted';
        currentFeedbackBtn.parentNode.replaceChild(doneBtn, currentFeedbackBtn);
        currentFeedbackBtn = null;
      }
    } else {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit';
    }
  }).catch(function(err) {
    simsToast(err.message, false);
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit';
  });
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
</script>
</body>
</html>
