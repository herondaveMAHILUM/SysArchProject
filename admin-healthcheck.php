<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin - Health Check</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .admin-nav-link{color:rgba(255,255,255,.8)!important;font-weight:500;font-size:.88rem;padding:.38rem .75rem!important;text-decoration:none;}
    .admin-nav-link:hover{color:var(--purple-light)!important;}
    .btn-logout-admin{background:#ef4444;border:none;color:#fff;border-radius:8px;padding:.38rem 1.1rem;font-size:.9rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-logout-admin:hover{background:#dc2626;}
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .hc-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1rem;}
    .hc-stat-card{background:#fff;border-radius:14px;padding:1.2rem;box-shadow:0 2px 12px rgba(124,58,237,.08);text-align:center;}
    .hc-val{font-size:1.9rem;font-weight:700;color:var(--purple-main);line-height:1.1;}
    .hc-lbl{font-size:.82rem;color:#666;font-weight:700;margin-top:.2rem;}
    .btn-run{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.45rem 1.1rem;font-size:.88rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-run:hover{background:var(--purple-deep);}
    .sev{display:inline-block;border-radius:20px;padding:.2rem .6rem;font-size:.74rem;font-weight:700;}
    .sev-high{background:#fee2e2;color:#b91c1c;}
    .sev-medium{background:#fef3c7;color:#92400e;}
    .sev-low{background:#dbeafe;color:#1e3a8a;}
    .ok-box{background:#ecfdf5;border:2px solid #86efac;border-radius:12px;padding:1rem;color:#166534;font-weight:700;}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #toastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;flex-shrink:0;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="admin-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <img src="assets/uccccslogo.png" alt="CCS Logo" class="brand-ccs-img">
      <span class="brand-name">CCS Sit-In Monitoring System</span>
    </a>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-1" id="adminNav">
      <ul class="navbar-nav align-items-center gap-0 me-2">
        <li class="nav-item"><a class="admin-nav-link" href="admin-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="admin-nav-link" href="admin-students.php">Students</a></li>
        <li class="nav-item"><a class="admin-nav-link" href="admin-sitinrecords.php">View Sit-in Records</a></li>
        <li class="nav-item"><a class="admin-nav-link" href="admin-feedback.php">Feedback Reports</a></li>
        <li class="nav-item"><a class="admin-nav-link" href="admin-reservations.php">Reservations</a></li>
        <li class="nav-item"><a class="admin-nav-link" href="admin-healthcheck.php" style="color:var(--purple-light)!important;font-weight:700;">Health Check</a></li>
      </ul>
      <button class="btn btn-logout-admin" onclick="document.getElementById('logoutModal').style.display='flex'">Log out</button>
    </div>
  </div>
</nav>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <div class="dash-card">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="page-title mb-0 text-start" style="text-align:left;">System Health Check</h2>
        <button class="btn-run" id="runBtn" onclick="runHealthCheck()">Run Scan</button>
      </div>
      <div class="hc-stats" id="summaryCards"></div>
      <div id="scanMeta" style="font-size:.82rem;color:#666;font-weight:600;margin-bottom:1rem;"></div>
      <div id="issuesWrap"></div>
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
<script src="sims.js"></script>
<script>
function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,200));} });
}

function sevBadge(sev) {
  if (sev === 'high') return '<span class="sev sev-high">HIGH</span>';
  if (sev === 'medium') return '<span class="sev sev-medium">MEDIUM</span>';
  return '<span class="sev sev-low">LOW</span>';
}

function runHealthCheck() {
  var btn = document.getElementById('runBtn');
  btn.disabled = true;
  btn.textContent = 'Scanning...';
  api({ action:'get_system_health' }).then(function(j) {
    if (!j.success) { simsToast(j.message || 'Scan failed.', false); return; }
    var s = j.data.summary;
    document.getElementById('summaryCards').innerHTML =
      '<div class="hc-stat-card"><div class="hc-val">'+s.total_issues+'</div><div class="hc-lbl">Total Issues</div></div>' +
      '<div class="hc-stat-card"><div class="hc-val" style="color:#b91c1c;">'+s.high+'</div><div class="hc-lbl">High</div></div>' +
      '<div class="hc-stat-card"><div class="hc-val" style="color:#92400e;">'+s.medium+'</div><div class="hc-lbl">Medium</div></div>' +
      '<div class="hc-stat-card"><div class="hc-val" style="color:#1e3a8a;">'+s.low+'</div><div class="hc-lbl">Low</div></div>';
    document.getElementById('scanMeta').textContent = 'Last checked: ' + s.checked_at;

    var wrap = document.getElementById('issuesWrap');
    if (!j.data.issues || j.data.issues.length === 0) {
      wrap.innerHTML = '<div class="ok-box">No inconsistencies detected. System data looks healthy.</div>';
      return;
    }

    var html = '<div style="overflow-x:auto;"><table class="table table-hover w-100"><thead><tr><th style="width:120px;">Severity</th><th style="width:220px;">Issue</th><th>Details</th></tr></thead><tbody>';
    j.data.issues.forEach(function(issue) {
      html += '<tr><td>'+sevBadge(issue.severity)+'</td><td><strong>'+issue.label+'</strong></td><td>'+issue.details+'</td></tr>';
    });
    html += '</tbody></table></div>';
    wrap.innerHTML = html;
  }).catch(function(e) {
    simsToast(e.message, false);
  }).finally(function() {
    btn.disabled = false;
    btn.textContent = 'Run Scan';
  });
}

runHealthCheck();
</script>
</body>
</html>
