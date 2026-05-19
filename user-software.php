<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';

$res = $conn->query("SELECT * FROM lab_software ORDER BY lab, pc_number, category, software");
$all = [];
if ($res) while ($r = $res->fetch_assoc()) $all[] = $r;
$conn->close();

// Group by lab -> pc_number -> items
$byLab = [];
foreach ($all as $row) {
    $byLab[$row['lab']][$row['pc_number']][] = $row;
}
ksort($byLab);

// Remove Mac Lab
unset($byLab['Mac Lab']);

$labs = ['524','526','528','530','542','544'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lab Software - Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}

    .sw-hero{background:linear-gradient(135deg,#0f172a 0%,#3b0764 60%,#4c1d95 100%);border-radius:20px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.5rem;}

    /* Lab selector cards */
    .lab-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem;}
    .lab-card{background:#fff;border:2px solid var(--purple-pale);border-radius:14px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .2s;}
    .lab-card:hover{border-color:var(--purple-main);transform:translateY(-2px);box-shadow:0 4px 12px rgba(124,58,237,.15);}
    .lab-card.selected{border-color:var(--purple-main);background:linear-gradient(135deg,#faf8ff,#ede9fe);box-shadow:0 4px 16px rgba(124,58,237,.22);}
    .lab-card-icon{font-size:2rem;margin-bottom:.4rem;}
    .lab-card-name{font-weight:800;font-size:1rem;color:var(--purple-deep);}
    .lab-card-sub{font-size:.75rem;color:#aaa;margin-top:.2rem;}

    /* PC Grid Section */
    .pc-section{display:none;background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(124,58,237,.07);padding:1.4rem;margin-bottom:1.5rem;}
    .pc-section.visible{display:block;}
    .pc-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;}
    .pc-section-title{font-weight:800;font-size:1rem;color:var(--purple-deep);}

    .cinema-screen{background:linear-gradient(180deg,#e5e7eb,#f3f4f6);border-radius:8px;padding:.45rem;text-align:center;margin-bottom:1.2rem;font-size:.75rem;font-weight:700;color:#888;letter-spacing:.12em;text-transform:uppercase;}
    .cinema-screen::before{content:'';display:block;width:70%;height:3px;background:linear-gradient(90deg,transparent,#9ca3af,transparent);margin:0 auto .3rem;}

    /* PC Grid */
    .pc-grid{display:grid;grid-template-columns:repeat(10,1fr);gap:.5rem;}
    .pc-seat{background:#f8f5ff;border:2px solid #e5e7eb;border-radius:8px;padding:.55rem .25rem;text-align:center;cursor:pointer;transition:all .15s;}
    .pc-seat:hover{border-color:var(--purple-main);background:#ede9fe;transform:scale(1.05);}
    .pc-seat.has-data{background:#f0fdf4;border-color:#bbf7d0;}
    .pc-seat.has-data:hover{background:#dcfce7;border-color:#22c55e;}
    .pc-seat.no-data{opacity:.5;cursor:default;}
    .pc-seat.no-data:hover{transform:none;background:#f8f5ff;border-color:#e5e7eb;}
    .pc-seat-icon{font-size:1.1rem;line-height:1;}
    .pc-seat-num{font-weight:700;font-size:.7rem;color:#333;margin-top:.15rem;}
    .pc-seat-count{font-size:.58rem;color:#aaa;margin-top:.05rem;}
    .pc-seat.has-data .pc-seat-count{color:#16a34a;font-weight:700;}

    /* Legend */
    .pc-legend{display:flex;gap:1rem;font-size:.78rem;align-items:center;flex-wrap:wrap;}
    .pc-legend-item{display:flex;align-items:center;gap:.3rem;}
    .pc-legend-dot{width:12px;height:12px;border-radius:3px;}
    .dot-nodata{background:#f8f5ff;border:2px solid #e5e7eb;}
    .dot-hasdata{background:#f0fdf4;border:2px solid #bbf7d0;}

    /* Category badges */
    .cat-badge{display:inline-block;border-radius:6px;padding:.1rem .52rem;font-size:.7rem;font-weight:700;}
    .cat-os    {background:#fef9c3;color:#92400e;}
    .cat-ide   {background:#eff6ff;color:#1e40af;}
    .cat-browser{background:#f0fdf4;color:#166534;}
    .cat-util  {background:#fdf2f8;color:#9d174d;}
    .cat-other {background:var(--purple-pale);color:var(--purple-deep);}

    /* Modal */
    .modal-content{border-radius:16px;border:none;box-shadow:0 8px 40px rgba(59,7,100,.18);}
    .pc-modal-header{background:linear-gradient(135deg,#3b0764,var(--purple-main));padding:1rem 1.4rem;color:#fff;border-radius:14px 14px 0 0;}
    .pc-modal-title{font-weight:800;font-size:1rem;}
    .pc-modal-sub{font-size:.78rem;color:rgba(255,255,255,.65);margin-top:.15rem;}

    .sw-table{width:100%;border-collapse:collapse;font-size:.85rem;}
    .sw-table th{font-size:.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.07em;padding:.45rem .6rem;border-bottom:2px solid var(--purple-pale);text-align:left;}
    .sw-table td{padding:.55rem .6rem;border-bottom:1px solid #f3f0fb;vertical-align:middle;}
    .sw-table tr:last-child td{border-bottom:none;}
    .sw-table tbody tr:hover td{background:#faf8ff;}
    .btn-close-modal{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="user-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <span class="nav-label ms-3 d-none d-lg-block">Lab Software</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav" style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="dashNav">
      <ul class="navbar-nav align-items-center gap-1 me-2">
        <li class="nav-item"><a class="nav-link-custom" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-history.php">History</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-sitin-sessions.php">Sit-In Sessions</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-reservation.php">Reservation</a></li>
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-software.php">Lab Software</a></li>
      </ul>
      <button class="btn btn-logout" onclick="document.getElementById('logoutModal').style.display='flex'">Logout</button>
    </div>
  </div>
</nav>

<div class="main-wrap">

  <div class="sw-hero mb-4">
    <div style="position:relative;z-index:1;">
      <div style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.4rem;">CCS Sit-In Monitoring System</div>
      <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 .3rem;">Lab Software Directory</h1>
      <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin:0;">Select a laboratory, then click any PC to view its installed software.</p>
    </div>
  </div>

  <!-- Lab Selection -->
  <div style="font-weight:800;color:var(--purple-deep);font-size:.95rem;margin-bottom:.8rem;">Select a Laboratory</div>
  <div class="lab-grid">
    <?php foreach($labs as $lab):
      $hasSw = isset($byLab[$lab]);
      $swCount = $hasSw ? array_sum(array_map('count', $byLab[$lab])) : 0;
      $pcCount = $hasSw ? count($byLab[$lab]) : 0;
    ?>
    <div class="lab-card" data-lab="<?= $lab ?>" onclick="selectLab('<?= $lab ?>')">
      <div class="lab-card-icon">🖥️</div>
      <div class="lab-card-name">Lab <?= $lab ?></div>
      <div class="lab-card-sub"><?= $hasSw ? $pcCount.' PCs · '.$swCount.' packages' : 'Click to view' ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- PC Grid -->
  <div class="pc-section" id="pcSection">
    <div class="pc-section-header">
      <div>
        <div class="pc-section-title">PCs in <span id="selectedLabLabel">Lab</span></div>
        <div class="pc-legend mt-1">
          <div class="pc-legend-item"><div class="pc-legend-dot dot-nodata"></div>No data</div>
          <div class="pc-legend-item"><div class="pc-legend-dot dot-hasdata"></div>Has software</div>
        </div>
      </div>
      <button class="btn-close-modal" onclick="backToLabs()">← Back</button>
    </div>
    <div class="cinema-screen">Front of Lab</div>
    <div class="pc-grid" id="pcGrid"></div>
  </div>

</div>

<!-- Software Detail Modal -->
<div class="modal fade" id="pcModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="pc-modal-header">
        <div class="pc-modal-title" id="pcModalTitle">PC Software</div>
        <div class="pc-modal-sub" id="pcModalSub"></div>
      </div>
      <div class="modal-body" style="padding:1.2rem 1.4rem;">
        <div id="pcSwContent">
          <table class="sw-table">
            <thead><tr><th>Software</th><th>Version</th><th>Category</th><th>Notes</th></tr></thead>
            <tbody id="pcSwTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pb-3 px-4">
        <button class="btn-close-modal" data-bs-dismiss="modal">Close</button>
      </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// All software data embedded from PHP
var swData = <?= json_encode(array_map(function($pcs) {
  $out = [];
  foreach ($pcs as $pc => $items) {
    $out[$pc] = array_map(function($sw) {
      return [
        'software' => $sw['software'],
        'version'  => $sw['version']  ?: '',
        'category' => $sw['category'] ?: '',
        'notes'    => $sw['notes']    ?: '',
      ];
    }, $items);
  }
  return $out;
}, $byLab), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

var currentLab = null;
var pcModal    = null;

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function catBadge(cat) {
  var map = {'Windows 11':'cat-os','Windows 10':'cat-os','IDE':'cat-ide','Browser':'cat-browser','Utility':'cat-util'};
  var cls = map[cat] || (cat && cat.startsWith('Windows') ? 'cat-os' : 'cat-other');
  return cat ? '<span class="cat-badge '+cls+'">'+esc(cat)+'</span>' : '<span style="color:#ddd;">—</span>';
}

function selectLab(lab) {
  currentLab = lab;
  document.querySelectorAll('.lab-card').forEach(function(c){ c.classList.remove('selected'); });
  document.querySelector('.lab-card[data-lab="'+lab+'"]').classList.add('selected');
  document.getElementById('selectedLabLabel').textContent = 'Lab ' + lab;
  document.getElementById('pcSection').classList.add('visible');
  renderPcGrid(lab);
  document.getElementById('pcSection').scrollIntoView({ behavior:'smooth', block:'start' });
}

function backToLabs() {
  currentLab = null;
  document.querySelectorAll('.lab-card').forEach(function(c){ c.classList.remove('selected'); });
  document.getElementById('pcSection').classList.remove('visible');
  window.scrollTo({ top:0, behavior:'smooth' });
}

function renderPcGrid(lab) {
  var grid    = document.getElementById('pcGrid');
  var labData = swData[lab] || {};
  grid.innerHTML = '';

  for (var i = 1; i <= 50; i++) {
    var items   = labData[i] || [];
    var hasData = items.length > 0;
    var seat    = document.createElement('div');
    seat.className = 'pc-seat' + (hasData ? ' has-data' : ' no-data');
    seat.innerHTML =
      '<div class="pc-seat-icon">🖥️</div>' +
      '<div class="pc-seat-num">PC ' + i + '</div>' +
      '<div class="pc-seat-count">' + (hasData ? items.length + ' sw' : 'no data') + '</div>';
    if (hasData) {
      (function(pcNum){ seat.addEventListener('click', function(){ openPcModal(lab, pcNum); }); })(i);
    }
    grid.appendChild(seat);
  }
}

function openPcModal(lab, pc) {
  var items = (swData[lab] && swData[lab][pc]) ? swData[lab][pc] : [];
  document.getElementById('pcModalTitle').textContent = 'Lab ' + lab + ' — PC ' + pc;
  document.getElementById('pcModalSub').textContent   = items.length + ' software installed';

  var tbody = document.getElementById('pcSwTableBody');
  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#ccc;padding:2rem;">No software data for this PC.</td></tr>';
  } else {
    var html = '';
    items.forEach(function(sw) {
      html += '<tr>';
      html += '<td style="font-weight:700;color:var(--purple-deep);">'+esc(sw.software)+'</td>';
      html += '<td style="color:#666;font-size:.82rem;">'+(sw.version ? esc(sw.version) : '<span style="color:#ddd;">—</span>')+'</td>';
      html += '<td>'+catBadge(sw.category)+'</td>';
      html += '<td style="color:#888;font-size:.8rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="'+esc(sw.notes||'')+'">'+(sw.notes ? esc(sw.notes) : '<span style="color:#ddd;">—</span>')+'</td>';
      html += '</tr>';
    });
    tbody.innerHTML = html;
  }

  if (!pcModal) pcModal = new bootstrap.Modal(document.getElementById('pcModal'));
  pcModal.show();
}
</script>
</body>
</html>
