<?php
require_once 'php/auth_admin.php';
require_once 'php/db.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'get_software') {
        $lab = $_POST['lab'] ?? '';
        $pc  = intval($_POST['pc'] ?? 0);
        $where = 'WHERE 1=1';
        $params = []; $types = '';
        if ($lab) { $where .= ' AND lab=?'; $params[] = $lab; $types .= 's'; }
        if ($pc)  { $where .= ' AND pc_number=?'; $params[] = $pc; $types .= 'i'; }
        $stmt = $conn->prepare("SELECT * FROM lab_software $where ORDER BY category, software");
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    if ($action === 'save_software') {
        $lab      = trim($_POST['lab']      ?? '');
        $pc       = intval($_POST['pc']      ?? 0);
        $software = trim($_POST['software'] ?? '');
        $version  = trim($_POST['version']  ?? '');
        $category = trim($_POST['category'] ?? '');
        $notes    = trim($_POST['notes']    ?? '');
        $edit_id  = intval($_POST['edit_id'] ?? 0);
        if (!$lab || !$pc || !$software) { echo json_encode(['success'=>false,'message'=>'Lab, PC, and software name are required.']); exit; }
        if ($edit_id) {
            $stmt = $conn->prepare("UPDATE lab_software SET software=?,version=?,category=?,notes=? WHERE id=?");
            $stmt->bind_param('ssssi',$software,$version,$category,$notes,$edit_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO lab_software (lab,pc_number,software,version,category,notes) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE version=VALUES(version),category=VALUES(category),notes=VALUES(notes)");
            $stmt->bind_param('sissss',$lab,$pc,$software,$version,$category,$notes);
        }
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok,'message'=>$ok?'Software saved.':'Database error.']);
        exit;
    }

    if ($action === 'delete_software') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM lab_software WHERE id=?");
        $stmt->bind_param('i',$id);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok,'message'=>$ok?'Deleted.':'Error.']);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action.']);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Software - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .sw-hero{background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#3b0764 100%);border-radius:20px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden;}

    /* Lab selector */
    .lab-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
    .lab-card{background:#fff;border:2px solid var(--purple-pale);border-radius:14px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .2s;}
    .lab-card:hover{border-color:var(--purple-main);transform:translateY(-2px);box-shadow:0 4px 12px rgba(124,58,237,.15);}
    .lab-card.selected{border-color:var(--purple-main);background:linear-gradient(135deg,#faf8ff,#ede9fe);box-shadow:0 4px 16px rgba(124,58,237,.22);}
    .lab-card-icon{font-size:2rem;margin-bottom:.4rem;}
    .lab-card-name{font-weight:800;font-size:1rem;color:var(--purple-deep);}
    .lab-card-sub{font-size:.75rem;color:#aaa;margin-top:.2rem;}

    /* PC Grid */
    .pc-section{display:none;background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(124,58,237,.07);padding:1.4rem;margin-bottom:1.5rem;}
    .pc-section.visible{display:block;}
    .pc-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;}
    .pc-section-title{font-weight:800;font-size:1rem;color:var(--purple-deep);}

    .cinema-screen{background:linear-gradient(180deg,#e5e7eb,#f3f4f6);border-radius:8px;padding:.45rem;text-align:center;margin-bottom:1.2rem;font-size:.75rem;font-weight:700;color:#888;letter-spacing:.12em;text-transform:uppercase;}
    .cinema-screen::before{content:'';display:block;width:70%;height:3px;background:linear-gradient(90deg,transparent,#9ca3af,transparent);margin:0 auto .3rem;}

    .pc-grid{display:grid;grid-template-columns:repeat(10,1fr);gap:.5rem;}
    .pc-seat{background:#f8f5ff;border:2px solid #e5e7eb;border-radius:8px;padding:.55rem .25rem;text-align:center;cursor:pointer;transition:all .15s;}
    .pc-seat:hover{border-color:var(--purple-main);background:#ede9fe;transform:scale(1.05);}
    .pc-seat.has-data{background:#f0fdf4;border-color:#bbf7d0;}
    .pc-seat.has-data:hover{background:#dcfce7;border-color:#22c55e;}
    .pc-seat.selected{background:#ede9fe;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.2);}
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
    .dot-selected{background:#ede9fe;border:2px solid #7c3aed;}

    /* Category badges */
    .cat-badge{display:inline-block;border-radius:6px;padding:.1rem .52rem;font-size:.7rem;font-weight:700;}
    .cat-os    {background:#fef9c3;color:#92400e;}
    .cat-ide   {background:#eff6ff;color:#1e40af;}
    .cat-browser{background:#f0fdf4;color:#166534;}
    .cat-util  {background:#fdf2f8;color:#9d174d;}
    .cat-other {background:var(--purple-pale);color:var(--purple-deep);}

    /* Modal software table */
    .sw-table{width:100%;border-collapse:collapse;font-size:.85rem;}
    .sw-table th{font-size:.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.07em;padding:.45rem .6rem;border-bottom:2px solid var(--purple-pale);text-align:left;}
    .sw-table td{padding:.5rem .6rem;border-bottom:1px solid #f3f0fb;vertical-align:middle;}
    .sw-table tr:last-child td{border-bottom:none;}
    .sw-table tr:hover td{background:#faf8ff;}
    .btn-edit-sw{background:none;border:none;color:var(--purple-main);font-size:.82rem;font-weight:700;cursor:pointer;padding:.2rem .5rem;border-radius:6px;font-family:'Nunito',sans-serif;}
    .btn-edit-sw:hover{background:var(--purple-pale);}
    .btn-del-sw{background:none;border:none;color:#ef4444;font-size:.82rem;font-weight:700;cursor:pointer;padding:.2rem .5rem;border-radius:6px;font-family:'Nunito',sans-serif;}
    .btn-del-sw:hover{background:#fef2f2;}

    .modal-content{border-radius:16px;border:none;box-shadow:0 8px 40px rgba(59,7,100,.18);}
    .modal-header{border-bottom:2px solid var(--purple-pale);padding:1.1rem 1.4rem;}
    .modal-title{font-weight:800;color:var(--purple-deep);}
    .btn-save-sw{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.45rem 1.5rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-save-sw:hover{background:var(--purple-deep);}
    .btn-cancel-sw{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.45rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}

    .pc-modal-header{background:linear-gradient(135deg,#3b0764,var(--purple-main));padding:1rem 1.4rem;color:#fff;border-radius:14px 14px 0 0;}
    .pc-modal-title{font-weight:800;font-size:1rem;}
    .pc-modal-sub{font-size:.78rem;color:rgba(255,255,255,.65);margin-top:.15rem;}

    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div class="main-wrap">

  <!-- Hero -->
  <div class="sw-hero mb-4">
    <div style="position:relative;z-index:1;">
      <div style="font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.4rem;">CCS Sit-In Monitoring System</div>
      <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 .3rem;">Manage Lab Software</h1>
      <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin:0;">Select a laboratory, then click a PC to manage its installed software.</p>
    </div>
  </div>

  <!-- Step 1: Lab Selection -->
  <div style="font-weight:800;color:var(--purple-deep);font-size:.95rem;margin-bottom:.8rem;">Select a Laboratory</div>
  <div class="lab-grid">
    <?php foreach(['524','526','528','530','542','544'] as $lab): ?>
    <div class="lab-card" data-lab="<?= $lab ?>" onclick="selectLab('<?= $lab ?>')">
      <div class="lab-card-icon">🖥️</div>
      <div class="lab-card-name">Lab <?= $lab ?></div>
      <div class="lab-card-sub" id="lab-sub-<?= $lab ?>">Click to view PCs</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Step 2: PC Grid -->
  <div class="pc-section" id="pcSection">
    <div class="pc-section-header">
      <div>
        <div class="pc-section-title">Select a PC in <span id="selectedLabLabel">Lab</span></div>
        <div class="pc-legend mt-1">
          <div class="pc-legend-item"><div class="pc-legend-dot dot-nodata"></div>No data</div>
          <div class="pc-legend-item"><div class="pc-legend-dot dot-hasdata"></div>Has software</div>
          <div class="pc-legend-item"><div class="pc-legend-dot dot-selected"></div>Selected</div>
        </div>
      </div>
      <button class="btn-cancel-sw" onclick="backToLabs()">← Back</button>
    </div>
    <div class="cinema-screen">Front of Lab</div>
    <div class="pc-grid" id="pcGrid"></div>
  </div>

</div>

<!-- PC Software Modal -->
<div class="modal fade" id="pcModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="pc-modal-header">
        <div class="pc-modal-title" id="pcModalTitle">PC Software</div>
        <div class="pc-modal-sub" id="pcModalSub"></div>
      </div>
      <div class="modal-body p-0">
        <!-- Software List -->
        <div style="padding:1rem 1.4rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
            <span style="font-size:.82rem;color:#888;" id="pcSwCount"></span>
            <button class="btn-save-sw" style="padding:.32rem .9rem;font-size:.82rem;" onclick="openAddForm()">+ Add Software</button>
          </div>
          <div id="pcSwTableWrap">
            <table class="sw-table">
              <thead><tr><th>Software</th><th>Version</th><th>Category</th><th>Notes</th><th></th></tr></thead>
              <tbody id="pcSwTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pb-3 px-4">
        <button class="btn-cancel-sw" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Software Modal -->
<div class="modal fade" id="swFormModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="swFormTitle">Add Software</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="sw_edit_id">
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size:.85rem;">Software Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="sw_name" placeholder="e.g. Visual Studio Code">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size:.85rem;">Version</label>
          <input type="text" class="form-control" id="sw_version" placeholder="e.g. 1.89.1">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size:.85rem;">Category</label>
          <select class="form-control" id="sw_category">
            <option value="">— Select Category —</option>
            <optgroup label="Operating System">
              <option value="Windows 11">Windows 11</option>
              <option value="Windows 10">Windows 10</option>
            </optgroup>
            <optgroup label="Development">
              <option value="IDE">IDE</option>
              <option value="Compiler">Compiler</option>
              <option value="Runtime">Runtime</option>
            </optgroup>
            <optgroup label="Tools">
              <option value="Browser">Browser</option>
              <option value="Database">Database</option>
              <option value="Server">Server</option>
              <option value="Utility">Utility</option>
              <option value="Other">Other</option>
            </optgroup>
          </select>
        </div>
        <div class="mb-1">
          <label class="form-label fw-bold" style="font-size:.85rem;">Notes</label>
          <textarea class="form-control" id="sw_notes" rows="2" placeholder="Optional notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn-cancel-sw" onclick="closeSwForm()">Cancel</button>
        <button class="btn-save-sw" onclick="saveSoftware()">Save</button>
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

<div id="simsToast">
  <span id="simsToastMsg"></span>
  <button onclick="hideToast()" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;opacity:.8;">&#x2715;</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var currentLab = null;
var currentPc  = null;
var pcSwCache  = {}; // lab-pc -> array of software rows
var pcCountCache = {}; // lab -> {pc: count}
var pcModal, swFormModal;

function api(params) {
  params.ajax = '1';
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('admin-manage-software.php', { method:'POST', body:fd }).then(function(r){ return r.json(); });
}

function showToast(msg, ok) {
  var t = document.getElementById('simsToast');
  document.getElementById('simsToastMsg').textContent = msg;
  t.style.background = ok ? '#16a34a' : '#ef4444';
  t.classList.add('show');
  setTimeout(function(){ t.classList.remove('show'); }, 3200);
}
function hideToast(){ document.getElementById('simsToast').classList.remove('show'); }

function catBadge(cat) {
  var map = {'Windows 11':'cat-os','Windows 10':'cat-os','IDE':'cat-ide','Browser':'cat-browser','Utility':'cat-util'};
  var cls = map[cat] || (cat && cat.startsWith('Windows') ? 'cat-os' : 'cat-other');
  return cat ? '<span class="cat-badge '+cls+'">'+esc(cat)+'</span>' : '<span style="color:#ccc;">—</span>';
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── LAB SELECTION ─────────────────────────────────────────
function selectLab(lab) {
  currentLab = lab;
  document.querySelectorAll('.lab-card').forEach(function(c){ c.classList.remove('selected'); });
  document.querySelector('.lab-card[data-lab="'+lab+'"]').classList.add('selected');
  document.getElementById('selectedLabLabel').textContent = 'Lab ' + lab;
  document.getElementById('pcSection').classList.add('visible');
  document.getElementById('pcSection').scrollIntoView({ behavior:'smooth', block:'start' });
  loadPcGrid(lab);
}

function backToLabs() {
  currentLab = null; currentPc = null;
  document.querySelectorAll('.lab-card').forEach(function(c){ c.classList.remove('selected'); });
  document.getElementById('pcSection').classList.remove('visible');
  window.scrollTo({ top:0, behavior:'smooth' });
}

// ── PC GRID ───────────────────────────────────────────────
function loadPcGrid(lab) {
  // Load software counts for all PCs in this lab
  api({ action:'get_software', lab:lab }).then(function(j) {
    var counts = {};
    (j.data || []).forEach(function(row) {
      counts[row.pc_number] = (counts[row.pc_number] || 0) + 1;
    });
    pcCountCache[lab] = counts;
    renderPcGrid(lab, counts);
    // Update lab card subtitle
    var totalPcs = Object.keys(counts).length;
    var totalSw  = Object.values(counts).reduce(function(a,b){ return a+b; }, 0);
    var sub = document.getElementById('lab-sub-'+lab);
    if (sub) sub.textContent = totalPcs + ' PCs · ' + totalSw + ' packages';
  });
}

function renderPcGrid(lab, counts) {
  var grid = document.getElementById('pcGrid');
  grid.innerHTML = '';
  for (var i = 1; i <= 50; i++) {
    var count    = counts[i] || 0;
    var hasData  = count > 0;
    var seat = document.createElement('div');
    seat.className = 'pc-seat' + (hasData ? ' has-data' : '');
    seat.id = 'seat-' + lab + '-' + i;
    seat.dataset.pc = i;
    seat.innerHTML =
      '<div class="pc-seat-icon">🖥️</div>' +
      '<div class="pc-seat-num">PC ' + i + '</div>' +
      '<div class="pc-seat-count">' + (hasData ? count + ' sw' : 'empty') + '</div>';
    (function(pcNum){ seat.addEventListener('click', function(){ openPcModal(lab, pcNum); }); })(i);
    grid.appendChild(seat);
  }
}

// ── PC MODAL ──────────────────────────────────────────────
function openPcModal(lab, pc) {
  currentLab = lab; currentPc = pc;

  // Mark selected seat
  document.querySelectorAll('.pc-seat').forEach(function(s){ s.classList.remove('selected'); });
  var seat = document.getElementById('seat-'+lab+'-'+pc);
  if (seat) seat.classList.add('selected');

  document.getElementById('pcModalTitle').textContent = 'Lab ' + lab + ' — PC ' + pc;
  document.getElementById('pcSwTableBody').innerHTML = '<tr><td colspan="5" style="text-align:center;color:#aaa;padding:1.5rem;">Loading...</td></tr>';
  document.getElementById('pcSwCount').textContent = '';

  if (!pcModal) pcModal = new bootstrap.Modal(document.getElementById('pcModal'));
  pcModal.show();

  loadPcSoftware(lab, pc);
}

function loadPcSoftware(lab, pc) {
  api({ action:'get_software', lab:lab, pc:pc }).then(function(j) {
    var rows = j.data || [];
    var key  = lab + '-' + pc;
    pcSwCache[key] = rows;
    renderPcSwTable(rows);
    document.getElementById('pcModalSub').textContent = rows.length + ' software installed';
    document.getElementById('pcSwCount').textContent  = rows.length + ' package' + (rows.length!==1?'s':'') + ' installed';
  });
}

function renderPcSwTable(rows) {
  var tbody = document.getElementById('pcSwTableBody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#ccc;padding:2rem;">No software yet. Click "+ Add Software" to get started.</td></tr>';
    return;
  }
  var html = '';
  rows.forEach(function(sw) {
    html += '<tr>';
    html += '<td style="font-weight:700;color:var(--purple-deep);">'+esc(sw.software)+'</td>';
    html += '<td style="color:#666;font-size:.82rem;">'+(sw.version?esc(sw.version):'<span style="color:#ddd;">—</span>')+'</td>';
    html += '<td>'+catBadge(sw.category)+'</td>';
    html += '<td style="color:#888;font-size:.8rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="'+esc(sw.notes||'')+'">'+esc(sw.notes||'—')+'</td>';
    html += '<td style="white-space:nowrap;">';
    html += '<button class="btn-edit-sw" onclick="openEditForm('+sw.id+')">Edit</button>';
    html += '<button class="btn-del-sw" onclick="deleteSw('+sw.id+',\''+esc(sw.software)+'\')">Delete</button>';
    html += '</td>';
    html += '</tr>';
  });
  tbody.innerHTML = html;
}

// ── ADD / EDIT FORM ───────────────────────────────────────
function openAddForm() {
  document.getElementById('swFormTitle').textContent = 'Add Software to PC ' + currentPc;
  document.getElementById('sw_edit_id').value  = '';
  document.getElementById('sw_name').value     = '';
  document.getElementById('sw_version').value  = '';
  document.getElementById('sw_category').value = '';
  document.getElementById('sw_notes').value    = '';
  if (!swFormModal) swFormModal = new bootstrap.Modal(document.getElementById('swFormModal'));
  swFormModal.show();
}

function openEditForm(id) {
  var key  = currentLab + '-' + currentPc;
  var rows = pcSwCache[key] || [];
  var sw   = rows.find(function(r){ return r.id == id; });
  if (!sw) return;

  document.getElementById('swFormTitle').textContent = 'Edit Software';
  document.getElementById('sw_edit_id').value  = sw.id;
  document.getElementById('sw_name').value     = sw.software;
  document.getElementById('sw_version').value  = sw.version || '';
  document.getElementById('sw_category').value = sw.category || '';
  document.getElementById('sw_notes').value    = sw.notes || '';
  if (!swFormModal) swFormModal = new bootstrap.Modal(document.getElementById('swFormModal'));
  swFormModal.show();
}

function closeSwForm() {
  if (swFormModal) swFormModal.hide();
  if (pcModal) pcModal.show();
}

function saveSoftware() {
  var name     = document.getElementById('sw_name').value.trim();
  var version  = document.getElementById('sw_version').value.trim();
  var category = document.getElementById('sw_category').value;
  var notes    = document.getElementById('sw_notes').value.trim();
  var editId   = document.getElementById('sw_edit_id').value;

  if (!name) { showToast('Software name is required.', false); return; }

  api({
    action:    'save_software',
    lab:       currentLab,
    pc:        currentPc,
    software:  name,
    version:   version,
    category:  category,
    notes:     notes,
    edit_id:   editId
  }).then(function(j) {
    showToast(j.message, j.success);
    if (j.success) {
      if (swFormModal) swFormModal.hide();
      // Refresh PC modal table + grid counts
      loadPcSoftware(currentLab, currentPc);
      loadPcGrid(currentLab);
      if (pcModal) setTimeout(function(){ pcModal.show(); }, 300);
    }
  });
}

function deleteSw(id, name) {
  if (!confirm('Delete "' + name + '" from PC ' + currentPc + '?')) return;
  api({ action:'delete_software', id:id }).then(function(j) {
    showToast(j.message, j.success);
    if (j.success) {
      loadPcSoftware(currentLab, currentPc);
      loadPcGrid(currentLab);
    }
  });
}
</script>
</body>
</html>
