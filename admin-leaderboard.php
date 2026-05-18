<?php
require_once 'php/auth_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Leaderboard - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .period-btn{background:#f3f0ff;border:1.5px solid transparent;color:var(--purple-deep);border-radius:8px;padding:.32rem .95rem;font-size:.82rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;transition:all .2s;}
    .period-btn.active,.period-btn:hover{background:var(--purple-main);color:#fff;border-color:var(--purple-main);}
    .podium-wrap{display:flex;align-items:flex-end;justify-content:center;gap:1.2rem;margin:1.5rem 0 2rem;}
    .podium-slot{display:flex;flex-direction:column;align-items:center;gap:.5rem;}
    .podium-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;color:#fff;position:relative;flex-shrink:0;}
    .podium-avatar.gold  {background:linear-gradient(135deg,#f59e0b,#fbbf24);box-shadow:0 4px 18px rgba(245,158,11,.4);}
    .podium-avatar.silver{background:linear-gradient(135deg,#9ca3af,#d1d5db);box-shadow:0 4px 18px rgba(156,163,175,.4);}
    .podium-avatar.bronze{background:linear-gradient(135deg,#d97706,#fb923c);box-shadow:0 4px 18px rgba(217,119,6,.35);}
    .podium-crown{position:absolute;top:-14px;font-size:1.2rem;}
    .podium-name{font-size:.82rem;font-weight:700;color:var(--purple-deep);text-align:center;max-width:90px;line-height:1.2;}
    .podium-course{font-size:.7rem;color:#aaa;text-align:center;}
    .podium-stat{font-size:.75rem;font-weight:700;text-align:center;}
    .podium-base{border-radius:12px 12px 0 0;width:90px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;color:#fff;}
    .podium-base.gold  {background:linear-gradient(180deg,#fbbf24,#f59e0b);height:80px;}
    .podium-base.silver{background:linear-gradient(180deg,#d1d5db,#9ca3af);height:60px;}
    .podium-base.bronze{background:linear-gradient(180deg,#fb923c,#d97706);height:45px;}
    .lb-row{display:flex;align-items:center;gap:.9rem;padding:.75rem 1rem;border-radius:12px;transition:background .15s;margin-bottom:.3rem;}
    .lb-row:hover{background:#f8f5ff;}
    .lb-rank{font-size:1rem;font-weight:800;color:var(--purple-deep);width:28px;text-align:center;flex-shrink:0;}
    .lb-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--purple-main),var(--purple-light));display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0;}
    .lb-name{font-weight:700;color:var(--purple-deep);font-size:.9rem;line-height:1.2;}
    .lb-meta{font-size:.74rem;color:#bbb;}
    .lb-sessions{font-size:1rem;font-weight:800;color:var(--purple-main);}
    .lb-hours{font-size:.75rem;color:#aaa;}
    .stat-chip{display:inline-block;border-radius:20px;padding:.18rem .8rem;font-size:.78rem;font-weight:700;color:#fff;background:var(--purple-main);}
    .trophy-bg{background:linear-gradient(135deg,#3b0764 0%,#4c1d95 50%,#7c3aed 100%);border-radius:20px;padding:2rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden;}
    .trophy-bg::before{content:'';position:absolute;right:1.5rem;top:50%;transform:translateY(-50%);font-size:6rem;opacity:.12;}
    #loadingOverlay{position:fixed;inset:0;background:rgba(248,245,255,.9);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.8rem;}
    .spinner-ring{width:46px;height:46px;border:4px solid var(--purple-pale);border-top-color:var(--purple-main);border-radius:50%;animation:spin .8s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg)}}
    .empty-state{text-align:center;padding:3rem 1rem;color:#bbb;}
    .empty-state .icon{font-size:3rem;margin-bottom:.8rem;display:none;}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div id="loadingOverlay">
  <div class="spinner-ring"></div>
  <span style="color:var(--purple-main);font-weight:700;font-size:.93rem;">Loading leaderboard...</span>
</div>

<div class="main-wrap">
  <div class="trophy-bg mb-4">
    <div style="position:relative;z-index:1;">
      <div style="font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:.3rem;">CCS Sit-In Monitoring System</div>
      <h1 style="font-size:1.8rem;font-weight:800;margin:0 0 .3rem;">Student Leaderboard</h1>
      <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin:0;">Top performers ranked by total sit-in sessions &amp; lab hours.</p>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
      <button class="period-btn active" onclick="setPeriod('all',this)">All Time</button>
      <button class="period-btn" onclick="setPeriod('month',this)">This Month</button>
      <button class="period-btn" onclick="setPeriod('week',this)">This Week</button>
    </div>
    <span id="lastUpdated" style="font-size:.78rem;color:#bbb;font-weight:600;"></span>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="dash-card">
        <div class="card-title-bar">Top 10 Students</div>
        <div class="podium-wrap" id="podiumWrap"></div>
        <div id="lbList"></div>
        <div class="empty-state" id="emptyState" style="display:none;">
          <div class="icon">🏅</div>
          <div style="font-weight:700;color:var(--purple-deep);margin-bottom:.3rem;">No data yet</div>
          <div style="font-size:.85rem;">Students need completed sit-in sessions to appear here.</div>
        </div>
      </div>
    </div>
    <div class="col-lg-4 d-flex flex-column gap-4">
      <div class="dash-card">
        <div class="card-title-bar">Summary</div>
        <div id="summaryBlock"><p class="text-muted" style="font-size:.88rem;">Loading...</p></div>
      </div>
      <div class="dash-card">
        <div class="card-title-bar">Top Performer</div>
        <div id="topSpotlight"><p class="text-muted" style="font-size:.88rem;">Loading...</p></div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var currentPeriod = 'all';
function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd }).then(function(r){ return r.json(); });
}
function setPeriod(p, btn) {
  currentPeriod = p;
  document.querySelectorAll('.period-btn').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  loadLeaderboard();
}
function initials(name) {
  var parts = name.trim().split(' ');
  return (parts[0][0] + (parts[parts.length-1][0] || '')).toUpperCase();
}
function buildPodium(data) {
  var wrap = document.getElementById('podiumWrap');
  wrap.innerHTML = '';
  if (data.length === 0) return;
  var order = [];
  if (data.length >= 2) order.push({ item: data[1], rank: 2, cls: 'silver', label: '2' });
  if (data.length >= 1) order.push({ item: data[0], rank: 1, cls: 'gold',   label: '1' });
  if (data.length >= 3) order.push({ item: data[2], rank: 3, cls: 'bronze', label: '3' });
  order.forEach(function(o) {
    var s = o.item;
    var crown = o.rank === 1 ? '<span class="podium-crown"></span>' : '';
    wrap.innerHTML += '<div class="podium-slot"><div class="podium-avatar '+o.cls+'">'+crown+initials(s.name)+'</div><div class="podium-name">'+s.name+'</div><div class="podium-course">'+s.course+'</div><div class="podium-stat" style="color:var(--purple-main);">'+s.total_sessions+' sessions</div><div class="podium-stat" style="color:#888;">'+s.total_hours+'h lab time</div><div class="podium-base '+o.cls+'">'+o.label+'</div></div>';
  });
}
function buildList(data) {
  var list = document.getElementById('lbList');
  list.innerHTML = '';
  if (data.length <= 3) return;
  data.slice(3).forEach(function(s, i) {
    var rank = i + 4;
    list.innerHTML += '<div class="lb-row"><div class="lb-rank">'+rank+'</div><div class="lb-avatar">'+initials(s.name)+'</div><div style="flex:1;min-width:0;"><div class="lb-name">'+s.name+'</div><div class="lb-meta">'+s.id_number+' &bull; '+s.course+' Yr '+s.year_level+'</div></div><div style="text-align:right;"><div class="lb-sessions">'+s.total_sessions+'</div><div class="lb-hours">'+s.total_hours+'h</div></div></div>';
  });
}
function buildSummary(data) {
  var totalSessions = data.reduce(function(a,s){ return a + parseInt(s.total_sessions); }, 0);
  var totalHours    = data.reduce(function(a,s){ return a + parseFloat(s.total_hours);  }, 0).toFixed(1);
  var avgSessions   = data.length > 0 ? (totalSessions / data.length).toFixed(1) : 0;
  document.getElementById('summaryBlock').innerHTML = '<div style="display:flex;flex-direction:column;gap:.9rem;">'+summaryRow('Students Ranked',data.length)+summaryRow('Total Sessions',totalSessions)+summaryRow('Total Hours',totalHours+'h')+summaryRow('Avg Sessions / Student',avgSessions)+'</div>';
}
function summaryRow(label, val) {
  return '<div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f3f0fb;"><span style="font-size:.85rem;color:#666;">'+label+'</span><span style="font-weight:700;color:var(--purple-deep);">'+val+'</span></div>';
}
function buildSpotlight(top) {
  if (!top) { document.getElementById('topSpotlight').innerHTML = '<p class="text-muted" style="font-size:.88rem;">No data yet.</p>'; return; }
  document.getElementById('topSpotlight').innerHTML = '<div style="text-align:center;"><div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;margin:0 auto .8rem;box-shadow:0 4px 18px rgba(245,158,11,.35);">'+initials(top.name)+'</div><div style="font-size:1.05rem;font-weight:800;color:var(--purple-deep);">'+top.name+'</div><div style="font-size:.78rem;color:#aaa;margin:.15rem 0 .8rem;">'+top.id_number+' &bull; '+top.course+' Yr '+top.year_level+'</div><div style="display:flex;justify-content:center;gap:.6rem;flex-wrap:wrap;"><span class="stat-chip">'+top.total_sessions+' sessions</span><span class="stat-chip" style="background:#059669;">'+top.total_hours+'h</span></div></div>';
}
function loadLeaderboard() {
  document.getElementById('loadingOverlay').style.display = 'flex';
  api({ action: 'get_leaderboard', period: currentPeriod }).then(function(j) {
    document.getElementById('loadingOverlay').style.display = 'none';
    if (!j.success) return;
    var data = j.data || [];
    var isEmpty = data.length === 0;
    document.getElementById('podiumWrap').style.display = isEmpty ? 'none' : '';
    document.getElementById('emptyState').style.display = isEmpty ? ''     : 'none';
    if (!isEmpty) {
      buildPodium(data); buildList(data); buildSummary(data); buildSpotlight(data[0]);
    } else {
      document.getElementById('lbList').innerHTML = '';
      document.getElementById('summaryBlock').innerHTML = '<p class="text-muted" style="font-size:.88rem;">No data yet.</p>';
      buildSpotlight(null);
    }
    var now = new Date();
    document.getElementById('lastUpdated').textContent = 'Updated ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }).catch(function() { document.getElementById('loadingOverlay').style.display = 'none'; });
}
loadLeaderboard();
</script>
</body>
</html>
