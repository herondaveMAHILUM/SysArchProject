<?php
// Admin Navigation Bar — included on every admin page.
$current = basename($_SERVER['PHP_SELF']);
function admin_nav_link($href, $label, $current) {
    $active = basename($href) === $current ? ' active-nav' : '';
    return '<li class="nav-item"><a class="admin-nav-link'.$active.'" href="'.$href.'">'.$label.'</a></li>';
}
$reportsPages = ['admin-leaderboard.php','admin-analytics.php','admin-ai-recommendations.php','admin-manage-software.php'];
$reportsActive = in_array($current, $reportsPages) ? ' active-nav' : '';
?>
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
        <?= admin_nav_link('admin-dashboard.php',    'Home',               $current) ?>
        <li class="nav-item"><a class="admin-nav-link" href="#" data-bs-toggle="modal" data-bs-target="#globalSearchModal">Search</a></li>
        <?= admin_nav_link('admin-students.php',     'Students',           $current) ?>
        <?= admin_nav_link('admin-sitinrecords.php', 'View Sit-in Records',$current) ?>
        <?= admin_nav_link('admin-feedback.php',     'Feedback Reports',   $current) ?>
        <?= admin_nav_link('admin-reservations.php', 'Reservations',       $current) ?>
        <li class="nav-item dropdown">
          <a class="admin-nav-link dropdown-toggle<?= $reportsActive ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
            Reports &amp; Tools
          </a>
          <ul class="dropdown-menu dropdown-menu-end admin-dropdown">
            <li><a class="admin-dropdown-item<?= $current==='admin-leaderboard.php' ? ' active' : '' ?>" href="admin-leaderboard.php">Leaderboard</a></li>
            <li><a class="admin-dropdown-item<?= $current==='admin-analytics.php' ? ' active' : '' ?>" href="admin-analytics.php">Reports &amp; Analytics</a></li>
            <li><a class="admin-dropdown-item<?= $current==='admin-ai-recommendations.php' ? ' active' : '' ?>" href="admin-ai-recommendations.php">AI Recommendations</a></li>
            <li><hr class="dropdown-divider" style="border-color:rgba(124,58,237,.15);margin:.3rem .8rem;"></li>
            <li><a class="admin-dropdown-item<?= $current==='admin-manage-software.php' ? ' active' : '' ?>" href="admin-manage-software.php">Manage Software</a></li>
          </ul>
        </li>
      </ul>
      <button class="btn btn-logout-admin" onclick="document.getElementById('logoutModal').style.display='flex'">Log out</button>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════════════════════════════════════
     GLOBAL SEARCH / SIT-IN MODAL  —  available on every admin page
     ══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="globalSearchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom:2px solid var(--purple-pale);padding:1.2rem 1.5rem;">
        <h5 class="modal-title" id="gsmTitle" style="font-weight:700;color:var(--purple-deep);font-size:1.1rem;">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3" style="max-height:72vh;overflow-y:auto;">

        <!-- Step 1 — Search -->
        <div id="gsmStepSearch">
          <div class="d-flex gap-2">
            <input type="text" class="form-control" id="gsmSearchInput" placeholder="ID number or name…" autocomplete="off">
            <button class="gsm-btn-go" id="gsmDoSearchBtn">Search</button>
          </div>
          <div id="gsmSearchResults" class="mt-3"></div>
        </div>

        <!-- Step 2 — Student info + Purpose + Lab -->
        <div id="gsmStepSitin" style="display:none;">
          <div class="gsm-stu-panel" id="gsmStuPanel"></div>
          <div class="mb-3">
            <label class="form-label" style="font-size:.85rem;font-weight:700;">Purpose <span class="text-danger">*</span></label>
            <select class="form-select" id="gsmPurpose">
              <option value="">— Select Purpose —</option>
              <option>C Programming</option><option>Java Programming</option><option>C# Programming</option>
              <option>ASP.Net</option><option>PHP</option><option>Other</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label" style="font-size:.85rem;font-weight:700;">Laboratory <span class="text-danger">*</span></label>
            <select class="form-select" id="gsmLab" onchange="gsmOnLabChange()">
              <option value="">— Select Lab —</option>
              <option>524</option><option>526</option><option>528</option>
              <option>530</option><option>542</option><option>544</option>
            </select>
          </div>
        </div>

        <!-- Step 3 — PC picker (appears after lab selected) -->
        <div id="gsmStepPc" style="display:none;margin-top:1rem;padding-top:1rem;border-top:1.5px solid var(--purple-pale);">
          <div style="font-size:.85rem;font-weight:700;color:var(--purple-deep);margin-bottom:.4rem;">
            Select PC in Lab <span id="gsmPcLabName"></span>
            <span style="font-size:.75rem;font-weight:500;color:#888;">(optional)</span>
          </div>
          <div style="display:flex;gap:.8rem;font-size:.75rem;font-weight:600;margin-bottom:.45rem;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:10px;border-radius:3px;background:#bbf7d0;border:1.5px solid #22c55e;display:inline-block;"></span> Available</span>
            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:10px;border-radius:3px;background:#fecaca;border:1.5px solid #ef4444;display:inline-block;"></span> Occupied</span>
            <span style="display:flex;align-items:center;gap:.3rem;"><span style="width:10px;height:10px;border-radius:3px;background:#ddd6fe;border:1.5px solid #7c3aed;display:inline-block;"></span> Selected</span>
          </div>
          <div style="font-size:.72rem;font-weight:600;color:#888;letter-spacing:.08em;text-align:center;background:#f3f4f6;border-radius:6px;padding:.25rem;margin-bottom:.4rem;">▲ FRONT OF LAB</div>
          <div id="gsmPcGrid" style="display:grid;grid-template-columns:repeat(10,1fr);gap:.3rem;margin:.3rem 0;">
            <div style="grid-column:1/-1;text-align:center;color:#aaa;font-size:.82rem;padding:.5rem;">Loading PCs…</div>
          </div>
          <div id="gsmSelectedPcInfo" style="min-height:1.6rem;margin-top:.3rem;"></div>
        </div>

      </div><!-- /modal-body -->

      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <div id="gsmFooterSearch" class="w-100 d-flex justify-content-end">
          <button class="gsm-btn-cancel" data-bs-dismiss="modal">Close</button>
        </div>
        <div id="gsmFooterSitin" class="w-100 d-flex justify-content-between" style="display:none!important;">
          <button class="gsm-btn-back" id="gsmBackBtn">&#8592; Back</button>
          <button class="gsm-btn-confirm" id="gsmDoSitinBtn">Sit In</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ── Nav ── */
.admin-nav-link{color:rgba(255,255,255,.8)!important;font-weight:500;font-size:.88rem;padding:.38rem .75rem!important;transition:color .2s;text-decoration:none;}
.admin-nav-link:hover,.admin-nav-link.active-nav{color:var(--purple-light)!important;}
.admin-nav-link.dropdown-toggle::after{border-color:rgba(255,255,255,.6) transparent transparent;}
.btn-logout-admin{background:#ef4444;border:none;color:#fff;border-radius:8px;padding:.38rem 1.1rem;font-size:.9rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
.btn-logout-admin:hover{background:#dc2626;}
.admin-dropdown{background:#3b0764;border:1.5px solid rgba(167,139,250,.25);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.35);padding:.4rem;min-width:200px;}
.admin-dropdown-item{display:block;padding:.5rem .9rem;border-radius:8px;color:rgba(255,255,255,.82)!important;font-size:.85rem;font-weight:500;text-decoration:none;transition:background .15s,color .15s;}
.admin-dropdown-item:hover,.admin-dropdown-item.active{background:rgba(167,139,250,.2);color:var(--purple-light)!important;}

/* ── Global Search Modal buttons ── */
.gsm-btn-go{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.42rem 1.3rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;white-space:nowrap;}
.gsm-btn-go:hover{background:var(--purple-deep);}
.gsm-btn-cancel{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
.gsm-btn-back{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.1rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
.gsm-btn-confirm{background:#16a34a;border:none;color:#fff;border-radius:8px;padding:.42rem 1.6rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
.gsm-btn-confirm:hover{background:#15803d;}
.gsm-btn-confirm:disabled{opacity:.6;cursor:not-allowed;}

/* ── Search result cards ── */
.gsm-result-card{padding:.7rem 1rem;border-radius:10px;cursor:pointer;border:1.5px solid var(--purple-pale);margin-bottom:.5rem;transition:border-color .15s,background .15s;}
.gsm-result-card:hover{background:var(--purple-pale);border-color:var(--purple-light);}
.gsm-result-name{font-weight:700;color:var(--purple-deep);font-size:.93rem;}
.gsm-result-meta{font-size:.8rem;color:#666;margin-top:.1rem;}

/* ── Student info panel ── */
.gsm-stu-panel{background:linear-gradient(135deg,var(--purple-pale) 0%,#f3f0ff 100%);border-radius:14px;padding:1.1rem 1.2rem;margin-bottom:1.1rem;border:1.5px solid var(--purple-light);}
.gsm-stu-name{font-size:1.08rem;font-weight:700;color:var(--purple-deep);margin-bottom:.55rem;}
.gsm-stu-row{display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;font-size:.86rem;}
.gsm-stu-label{font-weight:700;color:#666;}
.gsm-sess-chip{display:inline-block;border-radius:20px;padding:.12rem .75rem;font-size:.82rem;font-weight:700;color:#fff;}
.gsm-sess-chip.ok{background:var(--purple-main);}
.gsm-sess-chip.low{background:#f59e0b;}
.gsm-sess-chip.zero{background:#ef4444;}

/* ── PC grid seats ── */
.gsm-pc-seat{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:6px;padding:.35rem .1rem;text-align:center;cursor:pointer;transition:all .12s;}
.gsm-pc-seat:hover{border-color:#7c3aed;background:#ede9fe;transform:scale(1.06);}
.gsm-pc-seat.occupied{background:#fef2f2;border-color:#fecaca;cursor:not-allowed;opacity:.65;}
.gsm-pc-seat.selected{background:#ede9fe;border-color:#7c3aed;box-shadow:0 0 0 2px rgba(124,58,237,.25);}
.gsm-pc-num{font-weight:700;font-size:.65rem;color:#333;line-height:1;}
.gsm-pc-icon{font-size:.9rem;line-height:1.2;}
.gsm-selected-badge{display:inline-block;background:#7c3aed;color:#fff;border-radius:8px;padding:.2rem .7rem;font-size:.8rem;font-weight:700;}
</style>

<script>
/* ══════════════════════════════════════════════════════════
   Global Search Modal logic — runs on every admin page
   ══════════════════════════════════════════════════════════ */
(function () {
  var _stu = null;   // selected student object
  var _pc  = null;   // selected PC number

  function gsmApi(params) {
    var fd = new FormData();
    Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
    return fetch('php/admin_actions.php', { method:'POST', body:fd })
      .then(function(r){ return r.text(); })
      .then(function(t){ try{ return JSON.parse(t); }catch(e){ throw new Error(t.substring(0,200)); } });
  }

  function gsmReset() {
    _stu = null; _pc = null;
    document.getElementById('gsmSearchInput').value   = '';
    document.getElementById('gsmSearchResults').innerHTML = '';
    document.getElementById('gsmPurpose').value = '';
    document.getElementById('gsmLab').value     = '';
    document.getElementById('gsmPcGrid').innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#aaa;font-size:.82rem;padding:.5rem;">Loading PCs…</div>';
    document.getElementById('gsmSelectedPcInfo').innerHTML = '';
    document.getElementById('gsmStepSearch').style.display = '';
    document.getElementById('gsmStepSitin').style.display  = 'none';
    document.getElementById('gsmStepPc').style.display     = 'none';
    document.getElementById('gsmFooterSearch').style.display = '';
    document.getElementById('gsmFooterSitin').style.display  = 'none';
    document.getElementById('gsmTitle').textContent = 'Search Student';
  }

  document.getElementById('globalSearchModal')
    .addEventListener('hidden.bs.modal', gsmReset);

  /* Search */
  function gsmRunSearch() {
    var q = document.getElementById('gsmSearchInput').value.trim();
    if (!q) { simsToast('Please enter an ID number or name.', false); return; }
    gsmApi({ action:'search_student', query:q }).then(function(j) {
      var box = document.getElementById('gsmSearchResults');
      box.innerHTML = '';
      if (!j.data || !j.data.length) {
        box.innerHTML = '<p class="text-muted mt-1" style="font-size:.88rem;">No students found.</p>';
        return;
      }
      j.data.forEach(function(s) {
        var div = document.createElement('div');
        div.className = 'gsm-result-card';
        var sess = parseInt(s.remaining_session);
        var clr  = sess > 5 ? 'var(--purple-main)' : sess > 0 ? '#f59e0b' : '#ef4444';
        div.innerHTML =
          '<div class="gsm-result-name">'+s.id_number+' &mdash; '+s.name+'</div>'+
          '<div class="gsm-result-meta">'+s.course+' &bull; Year '+s.year_level+
          ' &bull; <span style="color:'+clr+';font-weight:700;">'+sess+' sessions left</span></div>';
        div.addEventListener('click', function(){ gsmShowSitin(s); });
        box.appendChild(div);
      });
    }).catch(function(e){ simsToast(e.message, false); });
  }

  document.getElementById('gsmDoSearchBtn').addEventListener('click', gsmRunSearch);
  document.getElementById('gsmSearchInput').addEventListener('keydown', function(e){
    if (e.key === 'Enter') gsmRunSearch();
  });

  /* Show sit-in step */
  function gsmShowSitin(s) {
    _stu = s; _pc = null;
    document.getElementById('gsmTitle').textContent = 'Student Information';
    var sess = parseInt(s.remaining_session);
    var cc   = sess > 5 ? 'ok' : sess > 0 ? 'low' : 'zero';
    document.getElementById('gsmStuPanel').innerHTML =
      '<div class="gsm-stu-name">'+s.name+'</div>'+
      '<div class="gsm-stu-row"><span class="gsm-stu-label">ID Number</span><span>'+s.id_number+'</span></div>'+
      '<div class="gsm-stu-row"><span class="gsm-stu-label">Course</span><span>'+s.course+'</span></div>'+
      '<div class="gsm-stu-row"><span class="gsm-stu-label">Year Level</span><span>'+s.year_level+'</span></div>'+
      '<div class="gsm-stu-row"><span class="gsm-stu-label">Sessions Left</span>'+
        '<span class="gsm-sess-chip '+cc+'">'+sess+'</span></div>';
    document.getElementById('gsmStepSearch').style.display = 'none';
    document.getElementById('gsmStepSitin').style.display  = '';
    document.getElementById('gsmStepPc').style.display     = 'none';
    document.getElementById('gsmFooterSearch').style.display = 'none';
    document.getElementById('gsmFooterSitin').style.display  = '';
  }

  /* Lab dropdown → load PC grid */
  window.gsmOnLabChange = function() {
    var lab = document.getElementById('gsmLab').value;
    _pc = null;
    document.getElementById('gsmSelectedPcInfo').innerHTML = '';
    if (!lab) { document.getElementById('gsmStepPc').style.display = 'none'; return; }
    document.getElementById('gsmPcLabName').textContent = lab;
    document.getElementById('gsmStepPc').style.display  = '';
    document.getElementById('gsmPcGrid').innerHTML =
      '<div style="grid-column:1/-1;text-align:center;color:#aaa;font-size:.82rem;padding:.5rem;">Loading PCs…</div>';
    gsmApi({ action:'get_pc_status', lab:lab }).then(function(j) {
      if (!j.success) {
        document.getElementById('gsmPcGrid').innerHTML =
          '<div style="grid-column:1/-1;text-align:center;color:#ef4444;font-size:.82rem;padding:.5rem;">Failed to load PCs.</div>';
        return;
      }
      gsmRenderPcGrid(j.data, lab);
    }).catch(function(e) {
      document.getElementById('gsmPcGrid').innerHTML =
        '<div style="grid-column:1/-1;text-align:center;color:#ef4444;font-size:.82rem;padding:.5rem;">'+e.message+'</div>';
    });
  };

  function gsmRenderPcGrid(pcData, lab) {
    var grid = document.getElementById('gsmPcGrid');
    grid.innerHTML = '';
    var dbMap = {};
    pcData.forEach(function(pc){ dbMap[parseInt(pc.pc_number)] = pc; });
    for (var i = 1; i <= 50; i++) {
      var pc  = dbMap[i] || { pc_number:i, is_available:1 };
      var avail = (pc.is_available == 1 || pc.is_available === true);
      var seat = document.createElement('div');
      seat.className  = 'gsm-pc-seat' + (avail ? '' : ' occupied');
      seat.dataset.pc = i;
      seat.innerHTML  =
        '<div class="gsm-pc-icon">'+(avail?'🖥️':'🔴')+'</div>'+
        '<div class="gsm-pc-num">'+i+'</div>';
      if (avail) {
        (function(n){ seat.addEventListener('click', function(){ gsmSelectPc(n, lab); }); })(i);
      } else {
        seat.title = 'PC '+i+' is occupied';
      }
      grid.appendChild(seat);
    }
  }

  function gsmSelectPc(pcNum, lab) {
    _pc = pcNum;
    document.querySelectorAll('.gsm-pc-seat').forEach(function(s){ s.classList.remove('selected'); });
    var s = document.querySelector('.gsm-pc-seat[data-pc="'+pcNum+'"]');
    if (s) s.classList.add('selected');
    document.getElementById('gsmSelectedPcInfo').innerHTML =
      '<span class="gsm-selected-badge">✓ PC '+pcNum+' selected (Lab '+lab+')</span>';
  }

  /* Back */
  document.getElementById('gsmBackBtn').addEventListener('click', function() {
    _stu = null; _pc = null;
    document.getElementById('gsmStepSearch').style.display = '';
    document.getElementById('gsmStepSitin').style.display  = 'none';
    document.getElementById('gsmStepPc').style.display     = 'none';
    document.getElementById('gsmFooterSearch').style.display = '';
    document.getElementById('gsmFooterSitin').style.display  = 'none';
    document.getElementById('gsmTitle').textContent = 'Search Student';
    document.getElementById('gsmLab').value     = '';
    document.getElementById('gsmPurpose').value = '';
    document.getElementById('gsmSelectedPcInfo').innerHTML = '';
  });

  /* Confirm sit-in */
  document.getElementById('gsmDoSitinBtn').addEventListener('click', function() {
    if (!_stu) return;
    var purpose = document.getElementById('gsmPurpose').value;
    var lab     = document.getElementById('gsmLab').value;
    if (!purpose) { simsToast('Please select a purpose.', false); return; }
    if (!lab)     { simsToast('Please select a laboratory.', false); return; }
    var btn = this;
    btn.disabled    = true;
    btn.textContent = 'Processing…';
    var payload = { action:'sitin', id_number:_stu.id_number, purpose:purpose, lab:lab };
    if (_pc) payload.pc_number = _pc;
    gsmApi(payload).then(function(j) {
      simsToast(j.message, j.success);
      if (j.success) {
        bootstrap.Modal.getInstance(document.getElementById('globalSearchModal')).hide();
        // If the current page has a reload function, call it
        if (typeof loadRecords   === 'function') loadRecords();
        if (typeof loadStudents  === 'function') loadStudents();
        if (typeof loadStats     === 'function') loadStats();
      } else {
        btn.disabled    = false;
        btn.textContent = 'Sit In';
      }
    }).catch(function(e) {
      simsToast(e.message, false);
      btn.disabled    = false;
      btn.textContent = 'Sit In';
    });
  });
})();
</script>
