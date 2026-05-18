<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sit In Monitoring System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    /* ── Leaderboard Section ── */
    .leaderboard-section {
      background: #f8f5ff;
      padding: 4rem 1rem 5rem;
    }
    .leaderboard-section .section-eyebrow {
      text-align: center;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--purple-main);
      margin-bottom: 0.5rem;
    }
    .leaderboard-section .section-heading {
      text-align: center;
      font-size: clamp(1.6rem, 4vw, 2.4rem);
      font-weight: 700;
      color: var(--purple-deep);
      margin-bottom: 0.5rem;
    }
    .leaderboard-section .section-sub {
      text-align: center;
      font-size: 0.95rem;
      color: #888;
      margin-bottom: 2.2rem;
    }
    .lb-filter-bar {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    .lb-filter-btn {
      background: var(--white);
      border: 1.5px solid #e5e0f5;
      color: #666;
      border-radius: 50px;
      padding: 0.38rem 1.2rem;
      font-size: 0.88rem;
      font-weight: 600;
      font-family: 'Nunito', sans-serif;
      cursor: pointer;
      transition: all 0.2s;
    }
    .lb-filter-btn:hover,
    .lb-filter-btn.active {
      background: var(--purple-main);
      border-color: var(--purple-main);
      color: var(--white);
    }
    .lb-card {
      background: var(--white);
      border-radius: 20px;
      box-shadow: 0 4px 30px rgba(124,58,237,0.09);
      overflow: hidden;
      max-width: 780px;
      margin: 0 auto;
    }
    .lb-podium {
      background: linear-gradient(135deg, var(--purple-deep) 0%, #4c1d95 50%, var(--purple-main) 100%);
      padding: 2rem 1rem 1.5rem;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 1rem;
    }
    .lb-podium-item {
      text-align: center;
      flex: 1;
      max-width: 160px;
    }
    .lb-podium-item.first { order: 2; }
    .lb-podium-item.second { order: 1; }
    .lb-podium-item.third { order: 3; }
    .lb-podium-avatar {
      width: 52px; height: 52px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; font-weight: 700; color: var(--white);
      margin: 0 auto 0.5rem;
      border: 3px solid rgba(255,255,255,0.3);
    }
    .lb-podium-item.first .lb-podium-avatar {
      width: 64px; height: 64px; font-size: 1.6rem;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border-color: #fbbf24;
      box-shadow: 0 0 20px rgba(251,191,36,0.4);
    }
    .lb-podium-item.second .lb-podium-avatar {
      background: linear-gradient(135deg, #94a3b8, #64748b);
      border-color: #cbd5e1;
    }
    .lb-podium-item.third .lb-podium-avatar {
      background: linear-gradient(135deg, #b45309, #92400e);
      border-color: #d97706;
    }
    .lb-podium-name {
      font-size: 0.8rem; font-weight: 700; color: rgba(255,255,255,0.92);
      line-height: 1.3; margin-bottom: 0.2rem;
    }
    .lb-podium-item.first .lb-podium-name { font-size: 0.88rem; }
    .lb-podium-course { font-size: 0.72rem; color: rgba(255,255,255,0.55); margin-bottom: 0.4rem; }
    .lb-podium-sessions {
      display: inline-block;
      background: rgba(255,255,255,0.15);
      border-radius: 50px;
      padding: 0.2rem 0.7rem;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--white);
    }
    .lb-podium-item.first .lb-podium-sessions {
      background: rgba(251,191,36,0.25);
      color: #fde68a;
    }
    .lb-podium-rank {
      font-size: 1.4rem;
      margin-bottom: 0.3rem;
    }
    .lb-podium-stand {
      margin-top: 0.6rem;
      border-radius: 8px 8px 0 0;
      opacity: 0.25;
    }
    .lb-podium-item.first .lb-podium-stand { height: 30px; background: #fbbf24; }
    .lb-podium-item.second .lb-podium-stand { height: 18px; background: #cbd5e1; }
    .lb-podium-item.third .lb-podium-stand { height: 12px; background: #d97706; }
    .lb-list {
      padding: 0.8rem 1.4rem 1.4rem;
    }
    .lb-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f3f0fb;
      transition: background 0.15s;
    }
    .lb-row:last-child { border-bottom: none; }
    .lb-row:hover { background: #faf8ff; border-radius: 10px; padding-left: 0.5rem; padding-right: 0.5rem; margin: 0 -0.5rem; }
    .lb-row-rank {
      font-size: 0.88rem;
      font-weight: 700;
      color: #aaa;
      min-width: 28px;
      text-align: center;
    }
    .lb-row-avatar {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, var(--purple-main), var(--purple-light));
      display: flex; align-items: center; justify-content: center;
      font-size: 0.95rem; font-weight: 700; color: var(--white);
      flex-shrink: 0;
    }
    .lb-row-info { flex: 1; min-width: 0; }
    .lb-row-name { font-size: 0.92rem; font-weight: 700; color: var(--purple-deep); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lb-row-meta { font-size: 0.78rem; color: #aaa; }
    .lb-row-stats { text-align: right; flex-shrink: 0; }
    .lb-row-sessions { font-size: 0.92rem; font-weight: 700; color: var(--purple-main); }
    .lb-row-hours { font-size: 0.75rem; color: #aaa; }
    .lb-empty {
      text-align: center;
      padding: 3rem 1rem;
      color: #aaa;
      font-size: 0.92rem;
    }
    .lb-loading {
      text-align: center;
      padding: 2.5rem;
      color: var(--purple-light);
      font-size: 0.92rem;
      font-weight: 600;
    }
  </style>
</head>
<body class="page-index">

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
      style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="mainNav">
      <ul class="navbar-nav align-items-center me-2">
        <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#leaderboard">Leaderboard</a></li>
      </ul>
      <a href="login.php" class="btn btn-nav-login me-2">Login</a>
      <a href="registration.php" class="btn btn-nav-register">Register</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="hero-content">
    <h1 class="hero-title">Sit-In Monitoring<br><span>System.</span></h1>
    <p class="hero-sub">A streamlined sit-in monitoring system designed to manage student attendance, sessions, and reservations with ease.</p>
    <div class="hero-actions">
      <a href="registration.php" class="btn-hero-primary">Get Started</a>
      <a href="login.php" class="btn-hero-outline">Sign In</a>
    </div>
  </div>
</section>

<!-- ══════════════ LEADERBOARD SECTION ══════════════ -->
<section class="leaderboard-section" id="leaderboard">
  <div class="container">
    <p class="section-eyebrow">🏆 Hall of Fame</p>
    <h2 class="section-heading">Top Sit-In Students</h2>
    <p class="section-sub">Students with the most completed sit-in sessions</p>

    <div class="lb-filter-bar">
      <button class="lb-filter-btn active" data-period="all">All Time</button>
      <button class="lb-filter-btn" data-period="month">This Month</button>
      <button class="lb-filter-btn" data-period="week">This Week</button>
    </div>

    <div class="lb-card">
      <div id="lbPodium" class="lb-podium"></div>
      <div id="lbList" class="lb-list"></div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  var currentPeriod = 'all';

  function getInitials(name) {
    var parts = name.trim().split(' ');
    return (parts[0][0] + (parts[parts.length-1][0] || '')).toUpperCase();
  }

  function loadLeaderboard(period) {
    var podium = document.getElementById('lbPodium');
    var list   = document.getElementById('lbList');
    podium.innerHTML = '<div class="lb-loading">Loading leaderboard…</div>';
    list.innerHTML   = '';

    var fd = new FormData();
    fd.append('action', 'get_leaderboard');
    fd.append('period', period);

    fetch('php/admin_actions.php', { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(j){
        var data = j.data || [];
        renderLeaderboard(data);
      })
      .catch(function(){
        podium.innerHTML = '';
        list.innerHTML = '<div class="lb-empty">Could not load leaderboard.</div>';
      });
  }

  var rankEmoji = ['🥇','🥈','🥉'];
  var podiumClass = ['first','second','third'];

  function renderLeaderboard(data) {
    var podium = document.getElementById('lbPodium');
    var list   = document.getElementById('lbList');
    podium.innerHTML = '';
    list.innerHTML   = '';

    if (!data.length) {
      podium.innerHTML = '';
      list.innerHTML   = '<div class="lb-empty">No sit-in records found for this period.</div>';
      return;
    }

    // Podium — top 3
    var top3 = data.slice(0, 3);
    // Reorder for podium display: 2nd, 1st, 3rd
    var podiumOrder = [top3[1], top3[0], top3[2]].filter(Boolean);
    var podiumClasses = top3.length >= 2 ? ['second','first','third'] : ['first'];

    podiumOrder.forEach(function(student, idx){
      var cls = podiumClasses[idx];
      var rank = data.indexOf(student) + 1;
      var div = document.createElement('div');
      div.className = 'lb-podium-item ' + cls;
      div.innerHTML =
        '<div class="lb-podium-rank">' + (rankEmoji[rank-1] || rank) + '</div>' +
        '<div class="lb-podium-avatar">' + getInitials(student.name) + '</div>' +
        '<div class="lb-podium-name">' + student.name + '</div>' +
        '<div class="lb-podium-course">' + student.course + ' · Y' + student.year_level + '</div>' +
        '<div class="lb-podium-sessions">' + student.total_sessions + ' sessions</div>' +
        '<div class="lb-podium-stand"></div>';
      podium.appendChild(div);
    });

    // List — rank 4 onwards
    var rest = data.slice(3);
    if (!rest.length) {
      list.style.display = 'none';
      return;
    }
    list.style.display = '';
    rest.forEach(function(student, idx){
      var rank = idx + 4;
      var row = document.createElement('div');
      row.className = 'lb-row';
      row.innerHTML =
        '<div class="lb-row-rank">#' + rank + '</div>' +
        '<div class="lb-row-avatar">' + getInitials(student.name) + '</div>' +
        '<div class="lb-row-info">' +
          '<div class="lb-row-name">' + student.name + '</div>' +
          '<div class="lb-row-meta">' + student.course + ' · Year ' + student.year_level + '</div>' +
        '</div>' +
        '<div class="lb-row-stats">' +
          '<div class="lb-row-sessions">' + student.total_sessions + ' sessions</div>' +
          '<div class="lb-row-hours">' + student.total_hours + ' hrs</div>' +
        '</div>';
      list.appendChild(row);
    });
  }

  // Filter buttons
  document.querySelectorAll('.lb-filter-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.lb-filter-btn').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      currentPeriod = btn.dataset.period;
      loadLeaderboard(currentPeriod);
    });
  });

  // Smooth scroll for nav link
  document.querySelectorAll('a[href="#leaderboard"]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      document.getElementById('leaderboard').scrollIntoView({ behavior:'smooth' });
    });
  });

  loadLeaderboard('all');
}());
</script>
</body>
</html>
