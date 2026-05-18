<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin - Feedback Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .fb-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;}
    .fb-stat-card{background:#fff;border-radius:14px;padding:1.5rem;box-shadow:0 2px 12px rgba(124,58,237,.08);text-align:center;}
    .fb-stat-value{font-size:2.2rem;font-weight:700;color:var(--purple-main);margin-bottom:.3rem;}
    .fb-stat-label{font-size:.85rem;color:#666;font-weight:600;}
    .rating-display{color:#f59e0b;font-size:1.1rem;}
    .fb-card{background:#fff;border-radius:14px;padding:1.5rem;margin-bottom:1rem;box-shadow:0 2px 12px rgba(124,58,237,.08);}
    .fb-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;}
    .fb-student{font-weight:700;color:var(--purple-deep);font-size:1rem;}
    .fb-meta{font-size:.8rem;color:#999;}
    .fb-purpose{background:var(--purple-pale);color:var(--purple-main);border-radius:20px;padding:.2rem .7rem;font-size:.75rem;font-weight:600;}
    .fb-purpose.muted{background:#f3f4f6;color:#9ca3af;}
    .fb-comment{font-size:.9rem;color:#555;line-height:1.6;margin-top:.5rem;}
    .fb-lab{font-size:.82rem;color:#666;margin-top:.3rem;}
    .fb-tab{background:var(--purple-pale);border:none;color:#555;border-radius:8px;padding:.5rem 1.2rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;margin-right:.3rem;}
    .fb-tab.active{background:var(--purple-main);color:#fff;}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #toastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;flex-shrink:0;}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <div class="dash-card">
      <h2 class="page-title">Feedback Reports</h2>

      <div class="fb-stats" id="fbStats"></div>

      <div class="d-flex mb-3">
        <button class="fb-tab active" onclick="loadFeedback('all', this)">All Feedback</button>
        <button class="fb-tab" onclick="loadFeedback('recent', this)">Recent (This Week)</button>
        <button class="fb-tab" onclick="loadFeedback('low', this)">Low Ratings (1-2★)</button>
      </div>

      <div id="feedbackList"></div>
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

<div id="simsToast"><span id="simsToastMsg"></span><button id="toastClose" onclick="simsToastHide()">&#x2715;</button></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,200));} });
}

function renderStars(rating) {
  var stars = '';
  for (var i = 1; i <= 5; i++) { stars += i <= rating ? '★' : '☆'; }
  return stars;
}

function or(val, fallback) {
  return (val !== null && val !== undefined && val !== '') ? val : fallback;
}

function loadFeedback(filter, btnEl) {
  document.querySelectorAll('.fb-tab').forEach(function(t){ t.classList.remove('active'); });
  if (btnEl) { btnEl.classList.add('active'); }
  else { var fb = document.querySelector('.fb-tab'); if (fb) fb.classList.add('active'); }

  api({ action:'get_feedback_reports', filter:filter }).then(function(j) {
    if (!j.success) { simsToast(j.message, false); return; }
    var stats = document.getElementById('fbStats');
    stats.innerHTML =
      '<div class="fb-stat-card"><div class="fb-stat-value">'+j.data.stats.total+'</div><div class="fb-stat-label">Total Feedback</div></div>'+
      '<div class="fb-stat-card"><div class="fb-stat-value">'+or(j.data.stats.avg_rating, '—')+'★</div><div class="fb-stat-label">Average Rating</div></div>'+
      '<div class="fb-stat-card"><div class="fb-stat-value">'+j.data.stats.excellent+'</div><div class="fb-stat-label">Excellent (4-5★)</div></div>'+
      '<div class="fb-stat-card"><div class="fb-stat-value">'+j.data.stats.poor+'</div><div class="fb-stat-label">Poor (1-2★)</div></div>';

    var list = document.getElementById('feedbackList');
    list.innerHTML = '';
    if (!j.data.feedback || j.data.feedback.length === 0) {
      list.innerHTML = '<p class="text-center text-muted" style="padding:3rem;">No feedback yet.</p>';
      return;
    }
    j.data.feedback.forEach(function(fb) {
      var ratingBg = fb.rating >= 4 ? 'background:#dcfce7;color:#16a34a;' : fb.rating <= 2 ? 'background:#fef2f2;color:#dc2626;' : 'background:#fef3c7;color:#92400e;';
      var date = new Date(fb.created_at).toLocaleString();

      // Null-safe fields from the sit-in LEFT JOIN
      var purpose   = or(fb.purpose,     '');
      var lab       = or(fb.lab,         '');
      var loginTime = or(fb.login_time,  '');
      var logoutTime= or(fb.logout_time, '');

      // Build purpose/lab badges — show "No session linked" when both are absent
      var badgeHtml = '';
      if (purpose || lab) {
        if (purpose) badgeHtml += '<span class="fb-purpose">'+purpose+'</span>';
        if (lab)     badgeHtml += '<span class="fb-purpose">Lab '+lab+'</span>';
      } else {
        badgeHtml = '<span class="fb-purpose muted">No session linked</span>';
      }


      // Build session time row
      var sessionHtml = '';
      if (loginTime && logoutTime) {
        sessionHtml = '<div class="fb-lab">Session: '+loginTime+' – '+logoutTime+'</div>';
      } else if (loginTime) {
        sessionHtml = '<div class="fb-lab">Session started: '+loginTime+'</div>';
      } else {
        sessionHtml = '<div class="fb-lab" style="color:#bbb;">No session time recorded</div>';
      }

      list.innerHTML +=
        '<div class="fb-card">'+
          '<div class="fb-header">'+
            '<div><div class="fb-student">'+fb.student_name+'</div><div class="fb-meta">'+fb.student_id+' | '+date+'</div></div>'+
            '<div class="rating-display">'+renderStars(fb.rating)+' <span style="'+ratingBg+'padding:.2rem .6rem;border-radius:12px;font-size:.85rem;">'+fb.rating+'/5</span></div>'+
          '</div>'+
          '<div class="d-flex gap-2 mb-2 flex-wrap">'+badgeHtml+'</div>'+
          (fb.comments ? '<div class="fb-comment">'+fb.comments+'</div>' : '<div class="fb-comment text-muted" style="font-style:italic;">No comments provided.</div>')+
          sessionHtml+
        '</div>';
    });
  }).catch(function(e){ simsToast(e.message, false); });
}

document.addEventListener('DOMContentLoaded', function() {
  loadFeedback('all', document.querySelector('.fb-tab'));
});
</script>
</body>
</html>
