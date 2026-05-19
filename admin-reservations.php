<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin - Reservations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .badge-pending{background:#fef3c7;color:#92400e;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-approved{background:#dcfce7;color:#16a34a;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-rejected{background:#fef2f2;color:#dc2626;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-checked-in{background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-completed{background:#e0e7ff;color:#4f46e5;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-expired{background:#f3f4f6;color:#6b7280;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .btn-approve{background:#16a34a;border:none;color:#fff;border-radius:6px;padding:.28rem .8rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-approve:hover{background:#15803d;}
    .btn-reject{background:#dc2626;border:none;color:#fff;border-radius:6px;padding:.28rem .8rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-reject:hover{background:#b91c1c;}
    .btn-cancel-fb{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-cancel-fb:hover{background:#d1d5db;}
    table.dataTable thead th{font-weight:700;color:var(--purple-deep);font-size:.88rem;}
    table.dataTable tbody td{font-size:.88rem;vertical-align:middle;}
    .filter-bar{background:#f8f5ff;border-radius:12px;padding:1rem;margin-bottom:1rem;display:flex;flex-wrap:wrap;gap:.8rem;align-items:end;}
    .filter-group{flex:1;min-width:150px;}
    .filter-group label{font-size:.75rem;font-weight:700;color:var(--purple-deep);margin-bottom:.2rem;display:block;}
    .filter-group input,.filter-group select{width:100%;border:2px solid var(--purple-pale);border-radius:8px;padding:.4rem .6rem;font-size:.85rem;font-family:'Nunito',sans-serif;}
    .filter-group input:focus,.filter-group select:focus{outline:none;border-color:var(--purple-main);}
    .bulk-actions-bar{background:#fff;border:2px solid var(--purple-pale);border-radius:10px;padding:.8rem;margin-bottom:1rem;display:none;align-items:center;justify-content:space-between;}
    .bulk-actions-bar.show{display:flex;}
    .bulk-actions-bar .selected-count{font-weight:700;color:var(--purple-deep);}
    .bulk-actions-bar .btn-group{display:flex;gap:.5rem;}
    .lab-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin:1rem 0;}
    .lab-card{background:linear-gradient(135deg,#f3f0ff 0%,#ede9fe 100%);border:2px solid var(--purple-pale);border-radius:14px;padding:1.2rem;text-align:center;cursor:pointer;transition:all .2s;}
    .lab-card:hover{border-color:var(--purple-main);transform:translateY(-2px);}
    .lab-card.selected{border-color:var(--purple-main);background:linear-gradient(135deg,var(--purple-pale) 0%,#ddd6fe 100%);}
    .lab-card-name{font-weight:700;color:var(--purple-deep);}
    .lab-card-status{font-size:.75rem;color:#666;margin-top:.2rem;}
    /* Lab reservation toggle panel */
    .lab-toggle-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.75rem;margin-bottom:1rem;}
    .lab-toggle-card{background:#fff;border:2px solid var(--purple-pale);border-radius:12px;padding:.85rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.6rem;transition:border-color .2s;}
    .lab-toggle-card.enabled{border-color:#bbf7d0;background:#f0fdf4;}
    .lab-toggle-card.disabled{border-color:#fecaca;background:#fef2f2;}
    .lab-toggle-name{font-weight:700;font-size:.9rem;color:var(--purple-deep);}
    .lab-toggle-status{font-size:.72rem;font-weight:700;margin-top:.1rem;}
    .lab-toggle-status.on{color:#16a34a;}
    .lab-toggle-status.off{color:#dc2626;}
    .lab-sw{position:relative;width:42px;height:23px;background:#ccc;border-radius:12px;cursor:pointer;transition:background .25s;flex-shrink:0;}
    .lab-sw.on{background:#22c55e;}
    .lab-sw::after{content:'';position:absolute;top:2px;left:2px;width:19px;height:19px;background:#fff;border-radius:50%;transition:transform .25s;}
    .lab-sw.on::after{transform:translateX(19px);}
    .pc-grid-container{display:none;margin:1.5rem 0;}
    .pc-grid-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}
    .pc-grid-title{font-weight:700;font-size:1rem;color:var(--purple-deep);}
    .pc-grid{display:grid;grid-template-columns:repeat(10,1fr);gap:.5rem;max-width:100%;margin:0 auto;}
    .pc-seat{background:#f8f5ff;border:2px solid #e5e7eb;border-radius:8px;padding:.6rem .3rem;text-align:center;}
    .pc-seat.available{background:#f0fdf4;border-color:#bbf7d0;}
    .pc-seat.occupied{background:#fef2f2;border-color:#fecaca;opacity:.7;}
    .pc-seat-icon{font-size:1.2rem;margin-bottom:.1rem;}
    .pc-seat-number{font-weight:700;font-size:.75rem;color:#333;}
    .pc-seat-status{font-size:.6rem;color:#666;}
    .pc-legend{display:flex;gap:1rem;font-size:.8rem;}
    .pc-legend-item{display:flex;align-items:center;gap:.3rem;}
    .pc-legend-dot{width:14px;height:14px;border-radius:4px;}
    .cinema-screen{background:linear-gradient(180deg,#e5e7eb 0%,#f3f4f6 100%);border-radius:8px;padding:.4rem;text-align:center;margin-bottom:1rem;font-size:.75rem;font-weight:600;color:#666;letter-spacing:.1em;text-transform:uppercase;}
    .custom-tab{background:var(--purple-pale);border:none;color:#555;border-radius:8px 8px 0 0;padding:.6rem 1.5rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;margin-right:.3rem;}
    .custom-tab.active{background:var(--purple-main);color:#fff;}
    .tab-content-section{display:none;}
    .tab-content-section.active{display:block;}
    .auto-expire-toggle{display:flex;align-items:center;gap:.5rem;background:#f8f5ff;border-radius:10px;padding:.6rem 1rem;}
    .auto-expire-toggle label{font-size:.85rem;font-weight:600;color:var(--purple-deep);cursor:pointer;}
    .toggle-switch{position:relative;width:44px;height:24px;background:#ccc;border-radius:12px;cursor:pointer;transition:background .3s;}
    .toggle-switch.active{background:#22c55e;}
    .toggle-switch::after{content:'';position:absolute;top:2px;left:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .3s;}
    .toggle-switch.active::after{transform:translateX(20px);}
    .detail-section{background:#f8f5ff;border-radius:10px;padding:1rem;margin-bottom:1rem;}
    .detail-section-title{font-weight:700;color:var(--purple-deep);margin-bottom:.5rem;font-size:.95rem;}
    .detail-row{display:flex;justify-content:space-between;padding:.3rem 0;font-size:.85rem;}
    .detail-label{font-weight:600;color:#666;}
    .detail-value{color:#333;}
    .log-entry{background:#fff;border-left:3px solid var(--purple-main);padding:.5rem .8rem;margin-bottom:.5rem;border-radius:4px;}
    .log-action{font-weight:700;font-size:.82rem;}
    .log-notes{font-size:.8rem;color:#666;margin-top:.2rem;}
    .log-time{font-size:.72rem;color:#999;margin-top:.2rem;}
    .history-item{background:#fff;border-radius:6px;padding:.5rem;margin-bottom:.4rem;font-size:.82rem;display:flex;justify-content:space-between;}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #toastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;flex-shrink:0;}
    .auto-refresh-indicator{font-size:.75rem;color:#6b7280;display:flex;align-items:center;gap:.3rem;}
    .auto-refresh-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;animation:pulse 2s infinite;}
    @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div class="main-wrap">
  <div class="container-fluid px-0">
    <div class="dash-card">
      <h2 class="page-title">Reservation Management</h2>

      <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center">
          <button class="btn" onclick="expireOldReservations()" style="background:#f59e0b;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;font-size:.85rem;">Expire Old</button>
          <div class="auto-expire-toggle">
            <div class="toggle-switch" id="autoExpireToggle" onclick="toggleAutoExpire()"></div>
            <label onclick="toggleAutoExpire()">Auto-Expire</label>
          </div>
          <div class="auto-refresh-indicator" id="autoRefreshIndicator">
            <div class="auto-refresh-dot"></div>
            <span id="autoRefreshLabel">Auto-refresh: 30s</span>
          </div>
        </div>
        <button class="btn" onclick="exportToCSV()" style="background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;font-size:.85rem;">Export CSV</button>
      </div>

      <!-- Per-Lab Reservation Toggles -->
      <div style="background:#f8f5ff;border-radius:12px;padding:.9rem 1rem;margin-bottom:1rem;">
        <div style="font-size:.78rem;font-weight:700;color:var(--purple-deep);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.7rem;">🔒 Lab Reservation Access</div>
        <div class="lab-toggle-grid" id="labToggleGrid">
          <!-- rendered by JS -->
        </div>
      </div>

      <div class="d-flex mb-3">
        <button class="custom-tab active" onclick="switchTab('reservations', this)">Reservations</button>
        <button class="custom-tab" onclick="switchTab('pcstatus', this)">PC Status</button>
        <button class="custom-tab" onclick="switchTab('notifications', this)">Notifications</button>
      </div>

      <div class="tab-content-section active" id="tab-reservations">
        <div class="filter-bar">
          <div class="filter-group" style="flex:2;"><label>Search</label><input type="text" id="filterSearch" placeholder="Student ID, name, purpose..." onkeyup="applyFilters()"/></div>
          <div class="filter-group"><label>Status</label>
            <select id="filterStatus" onchange="applyFilters()">
              <option value="">All</option><option value="pending">Pending</option><option value="approved">Approved</option>
              <option value="rejected">Rejected</option><option value="checked_in">Checked In</option>
              <option value="completed">Completed</option><option value="expired">Expired</option>
            </select>
          </div>
          <div class="filter-group"><label>Lab</label>
            <select id="filterLab" onchange="applyFilters()">
              <option value="">All Labs</option><option value="524">Lab 524</option><option value="526">Lab 526</option>
              <option value="528">Lab 528</option><option value="530">Lab 530</option><option value="542">Lab 542</option><option value="544">Lab 544</option>
            </select>
          </div>
          <div class="filter-group"><label>From Date</label><input type="date" id="filterDateFrom" onchange="applyFilters()"/></div>
          <div class="filter-group"><label>To Date</label><input type="date" id="filterDateTo" onchange="applyFilters()"/></div>
          <div class="filter-group" style="flex:0;"><label>&nbsp;</label><button class="btn" onclick="clearFilters()" style="background:#6b7280;color:#fff;border:none;border-radius:8px;padding:.4rem .8rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;font-size:.85rem;">Clear</button></div>
        </div>

        <div class="bulk-actions-bar" id="bulkActionsBar">
          <div class="selected-count"><span id="selectedCount">0</span> reservation(s) selected</div>
          <div class="btn-group">
            <button class="btn btn-approve" onclick="bulkAction('approve')">Approve Selected</button>
            <button class="btn btn-reject" onclick="bulkAction('reject')">Reject Selected</button>
            <button class="btn btn-cancel-fb" onclick="bulkAction('cancel')">Cancel Selected</button>
            <button class="btn btn-cancel-fb" onclick="clearSelections()">Clear</button>
          </div>
        </div>

        <div class="d-flex mb-3">
          <button class="custom-tab active" id="subtab-pending" onclick="switchResSubtab('pending')" style="font-size:.82rem;padding:.5rem 1.2rem;">Pending</button>
          <button class="custom-tab" id="subtab-all" onclick="switchResSubtab('all')" style="font-size:.82rem;padding:.5rem 1.2rem;">All Reservations</button>
        </div>

        <div id="res-pending" class="res-subtab active">
          <table id="pendingTable" class="table table-hover w-100">
            <thead><tr><th><input type="checkbox" id="selectAllPending" onchange="toggleSelectAll('pending')"/></th><th>ID</th><th>Student</th><th>Purpose</th><th>Lab</th><th>PC</th><th>Time</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="pendingTbody"></tbody>
          </table>
        </div>

        <div id="res-all" class="res-subtab" style="display:none;">
          <table id="allTable" class="table table-hover w-100">
            <thead><tr><th><input type="checkbox" id="selectAllAll" onchange="toggleSelectAll('all')"/></th><th>ID</th><th>Student</th><th>Purpose</th><th>Lab</th><th>PC</th><th>Time</th><th>Date</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody id="allTbody"></tbody>
          </table>
        </div>
      </div>

      <div class="tab-content-section" id="tab-pcstatus">
        <h5 style="font-weight:700;color:var(--purple-deep);margin-bottom:1rem;">Select Lab to View PC Status</h5>
        <div class="lab-grid" id="pcStatusLabGrid">
          <div class="lab-card" data-lab="524" onclick="selectPcStatusLab('524')"><div class="lab-card-name">Lab 524</div><div class="lab-card-status">Click to view PCs</div></div>
          <div class="lab-card" data-lab="526" onclick="selectPcStatusLab('526')"><div class="lab-card-name">Lab 526</div><div class="lab-card-status">Click to view PCs</div></div>
          <div class="lab-card" data-lab="528" onclick="selectPcStatusLab('528')"><div class="lab-card-name">Lab 528</div><div class="lab-card-status">Click to view PCs</div></div>
          <div class="lab-card" data-lab="530" onclick="selectPcStatusLab('530')"><div class="lab-card-name">Lab 530</div><div class="lab-card-status">Click to view PCs</div></div>
          <div class="lab-card" data-lab="542" onclick="selectPcStatusLab('542')"><div class="lab-card-name">Lab 542</div><div class="lab-card-status">Click to view PCs</div></div>
          <div class="lab-card" data-lab="544" onclick="selectPcStatusLab('544')"><div class="lab-card-name">Lab 544</div><div class="lab-card-status">Click to view PCs</div></div>
        </div>
        <div class="pc-grid-container" id="pcStatusGridContainer">
          <div class="pc-grid-header">
            <div class="pc-grid-title">PCs in Lab <span id="pcStatusLabName"></span></div>
            <div class="pc-legend">
              <div class="pc-legend-item"><div class="pc-legend-dot" style="background:#22c55e;"></div> Available</div>
              <div class="pc-legend-item"><div class="pc-legend-dot" style="background:#ef4444;"></div> Reserved</div>
            </div>
          </div>
          <div class="cinema-screen">FRONT OF LAB</div>
          <div class="pc-grid" id="pcStatusGrid"></div>
        </div>
      </div>

      <div class="tab-content-section" id="tab-notifications">
        <h5 style="font-weight:700;color:var(--purple-deep);margin-bottom:1rem;">Reservation Notifications</h5>
        <div class="filter-bar" style="margin-bottom:1rem;">
          <div class="filter-group" style="flex:1;"><label>Student ID Number</label><input type="text" id="notifStudentId" placeholder="Enter student ID number"/></div>
          <div class="filter-group" style="flex:0;"><label>&nbsp;</label><button class="btn" onclick="loadStudentNotifications()" style="background:var(--purple-main);color:#fff;border:none;border-radius:8px;padding:.5rem 1.2rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;">Load</button></div>
        </div>
        <div id="notifList" style="display:none;">
          <h6 style="font-weight:700;color:var(--purple-deep);margin-bottom:.8rem;">Notifications for <span id="notifStudentName"></span></h6>
          <div id="notifListContent"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="actionModalTitle">Process Reservation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="actionResId">
        <div id="actionResInfo" style="background:#f8f5ff;padding:.8rem;border-radius:10px;margin-bottom:1rem;font-size:.88rem;"></div>
        <label class="form-label" style="font-size:.85rem;">Notes (optional)</label>
        <textarea class="form-control" id="actionNotes" rows="3" placeholder="Add notes..." style="resize:none;"></textarea>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn btn-cancel-fb" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-reject" id="btnReject" onclick="processReservation('reject')">Reject</button>
        <button class="btn btn-approve" id="btnApprove" onclick="processReservation('approve')">Approve</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:700px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Reservation Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3" id="detailsModalBody"></div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2"><button class="btn btn-cancel-fb" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Cancel Approved Reservation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="cancelResId">
        <div id="cancelResInfo" style="background:#fef2f2;padding:.8rem;border-radius:10px;margin-bottom:1rem;font-size:.88rem;"></div>
        <label class="form-label" style="font-size:.85rem;">Reason (optional)</label>
        <textarea class="form-control" id="cancelNotes" rows="3" placeholder="Reason for cancellation..." style="resize:none;"></textarea>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn btn-cancel-fb" data-bs-dismiss="modal">Back</button>
        <button class="btn btn-reject" onclick="confirmCancelReservation()">Confirm Cancellation</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="bulkActionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="bulkActionModalTitle">Bulk Process Reservations</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <div id="bulkActionInfo" style="background:#f8f5ff;padding:.8rem;border-radius:10px;margin-bottom:1rem;font-size:.88rem;"></div>
        <label class="form-label" style="font-size:.85rem;">Notes (optional)</label>
        <textarea class="form-control" id="bulkActionNotes" rows="3" placeholder="Add notes..." style="resize:none;"></textarea>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn btn-cancel-fb" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-approve" id="bulkActionConfirmBtn" onclick="confirmBulkAction()">Confirm</button>
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

<div id="simsToast"><span id="simsToastMsg"></span><button id="toastClose" onclick="simsToastHide()">&#x2715;</button></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
var pendingTable, allTable;
var selectedPcStatusLab = null;
var pcStatusGridData = [];
var pendingReservations = [];
var allReservations = [];
var selectedReservations = {pending: [], all: []};
var autoExpireInterval = null;
var currentBulkAction = null;
var autoRefreshCountdown = 30;
var autoRefreshTimer = null;

function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,200));} });
}

function startAutoRefresh() {
  autoRefreshCountdown = 30;
  if (autoRefreshTimer) clearInterval(autoRefreshTimer);
  autoRefreshTimer = setInterval(function() {
    autoRefreshCountdown--;
    var label = document.getElementById('autoRefreshLabel');
    if (label) label.textContent = 'Auto-refresh: ' + autoRefreshCountdown + 's';
    if (autoRefreshCountdown <= 0) {
      autoRefreshCountdown = 30;
      loadPending(); loadAll();
      if (selectedPcStatusLab) {
        api({ action:'get_pc_status', lab:selectedPcStatusLab }).then(function(j) {
          if (j.success) { pcStatusGridData = j.data; renderPcStatusGrid(selectedPcStatusLab); }
        }).catch(function(){});
      }
    }
  }, 1000);
}

// FIX: accept the clicked button element directly instead of relying on global `event`
function switchTab(tab, btn) {
  // Deactivate top-level tabs (exclude sub-tabs which have id starting with "subtab-")
  document.querySelectorAll('.custom-tab').forEach(function(t){
    if (t.id && t.id.startsWith('subtab-')) return;
    t.classList.remove('active');
  });
  document.querySelectorAll('.tab-content-section').forEach(function(s){ s.classList.remove('active'); });
  if (btn) btn.classList.add('active');
  document.getElementById('tab-'+tab).classList.add('active');
  if (tab === 'reservations') { loadPending(); loadAll(); }
  if (tab === 'pcstatus' && selectedPcStatusLab) { renderPcStatusGrid(selectedPcStatusLab); }
}

function switchResSubtab(subtab) {
  document.querySelectorAll('[id^="subtab-"]').forEach(function(t){ t.classList.remove('active'); });
  document.querySelectorAll('.res-subtab').forEach(function(s){ s.style.display = 'none'; });
  document.getElementById('subtab-'+subtab).classList.add('active');
  document.getElementById('res-'+subtab).style.display = 'block';
  if (subtab === 'pending') loadPending(); else loadAll();
}

function applyFilters() { loadPending(); loadAll(); }

function clearFilters() {
  document.getElementById('filterSearch').value = '';
  document.getElementById('filterStatus').value = '';
  document.getElementById('filterLab').value = '';
  document.getElementById('filterDateFrom').value = '';
  document.getElementById('filterDateTo').value = '';
  loadPending(); loadAll();
}

function getFilterParams() {
  return { status:document.getElementById('filterStatus').value, lab:document.getElementById('filterLab').value, date_from:document.getElementById('filterDateFrom').value, date_to:document.getElementById('filterDateTo').value, search:document.getElementById('filterSearch').value };
}

function selectPcStatusLab(lab) {
  selectedPcStatusLab = lab;
  document.querySelectorAll('#pcStatusLabGrid .lab-card').forEach(function(c){ c.classList.remove('selected'); });
  document.querySelector('#pcStatusLabGrid .lab-card[data-lab="'+lab+'"]').classList.add('selected');
  document.getElementById('pcStatusLabName').textContent = lab;
  document.getElementById('pcStatusGridContainer').style.display = 'block';
  api({ action:'get_pc_status', lab:lab }).then(function(j) {
    if (!j.success) { simsToast(j.message, false); return; }
    pcStatusGridData = j.data; renderPcStatusGrid(lab);
  }).catch(function(e){ simsToast(e.message, false); });
}

function renderPcStatusGrid(lab) {
  var grid = document.getElementById('pcStatusGrid');
  grid.innerHTML = '';
  var dbMap = {};
  pcStatusGridData.forEach(function(pc) { dbMap[parseInt(pc.pc_number)] = pc; });
  for (var i = 1; i <= 50; i++) {
    var pc = dbMap[i] || { pc_number: i, is_available: 1 };
    var isAvailable = pc.is_available == true || pc.is_available == 1;
    var seat = document.createElement('div');
    seat.className = 'pc-seat ' + (isAvailable ? 'available' : 'occupied');
    seat.innerHTML = '<div class="pc-seat-icon">'+(isAvailable?'🖥️':'🔴')+'</div><div class="pc-seat-number">PC '+i+'</div><div class="pc-seat-status">'+(isAvailable?'Available':'Reserved')+'</div>';
    grid.appendChild(seat);
  }
}

function loadPending() {
  var params = getFilterParams(); params.action = 'get_reservations';
  api(params).then(function(j) {
    var tbody = document.getElementById('pendingTbody');
    tbody.innerHTML = '';
    pendingReservations = (j.data||[]).filter(function(r){ return r.status === 'pending'; });
    if (pendingReservations.length === 0) { tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No pending reservations.</td></tr>'; return; }
    pendingReservations.forEach(function(r) {
      var pcDisplay = r.pc_number ? 'PC ' + r.pc_number : '—';
      var isChecked = selectedReservations.pending.indexOf(r.id) !== -1;
      tbody.innerHTML += '<tr><td><input type="checkbox" class="res-checkbox" data-id="'+r.id+'" data-table="pending" '+(isChecked?'checked':'')+' onchange="updateSelections()"/></td>'+
        '<td>'+r.id+'</td><td>'+r.id_number+' - '+r.name+'</td><td>'+r.purpose+'</td><td>'+r.lab+'</td><td>'+pcDisplay+'</td><td>'+r.time_in+'</td><td>'+r.date+'</td>'+
        '<td><span class="badge-pending">Pending</span></td>'+
        '<td><button class="btn btn-approve" onclick="openActionModal('+r.id+',\''+escapeHtml(r.id_number)+' - '+escapeHtml(r.name)+'\',\''+escapeHtml(r.purpose)+'\',\''+escapeHtml(r.lab)+'\',\''+r.time_in+'\',\''+r.date+'\')" title="Process">Process</button> '+
        '<button class="btn" onclick="openDetailsModal('+r.id+')" style="background:#3b82f6;color:#fff;border:none;border-radius:6px;padding:.28rem .6rem;font-size:.82rem;font-weight:600;cursor:pointer;" title="Details">View</button></td></tr>';
    });
    if (pendingTable) pendingTable.destroy();
    pendingTable = $('#pendingTable').DataTable({ columnDefs:[{orderable:false,targets:[0,9]}], order:[[7,'desc']] });
    updateSelections();
  }).catch(function(e){ simsToast(e.message, false); });
}

function loadAll() {
  var params = getFilterParams(); params.action = 'get_reservations';
  api(params).then(function(j) {
    var tbody = document.getElementById('allTbody');
    tbody.innerHTML = '';
    allReservations = j.data||[];
    allReservations.forEach(function(r) {
      var badgeClass = '', statusText = r.status.charAt(0).toUpperCase() + r.status.slice(1);
      if (r.status==='pending'){badgeClass='badge-pending';statusText='Pending';}
      else if(r.status==='approved'){badgeClass='badge-approved';statusText='Approved';}
      else if(r.status==='rejected'){badgeClass='badge-rejected';statusText='Rejected';}
      else if(r.status==='checked_in'){badgeClass='badge-checked-in';statusText='Checked In';}
      else if(r.status==='completed'){badgeClass='badge-completed';statusText='Completed';}
      else if(r.status==='expired'){badgeClass='badge-expired';statusText='Expired';}
      var created = new Date(r.created_at).toLocaleString();
      var pcDisplay = r.pc_number ? 'PC ' + r.pc_number : '—';
      var isChecked = selectedReservations.all.indexOf(r.id) !== -1;
      var actionsHtml = '<button class="btn" onclick="openDetailsModal('+r.id+')" style="background:#3b82f6;color:#fff;border:none;border-radius:6px;padding:.28rem .6rem;font-size:.82rem;font-weight:600;cursor:pointer;">View</button> ';
      if (r.status==='approved') {
        actionsHtml += '<button class="btn" onclick="openCancelModal('+r.id+',\''+escapeHtml(r.id_number)+' - '+escapeHtml(r.name)+'\',\''+escapeHtml(r.lab)+'\',\''+r.time_in+'\')" style="background:#ef4444;color:#fff;border:none;border-radius:6px;padding:.28rem .6rem;font-size:.82rem;font-weight:600;cursor:pointer;">Cancel</button> ';
        actionsHtml += '<button class="btn" onclick="adminCheckIn('+r.id+',\''+escapeHtml(r.name)+'\')" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:.28rem .6rem;font-size:.82rem;font-weight:600;cursor:pointer;">Check In</button>';
      }
      tbody.innerHTML += '<tr><td><input type="checkbox" class="res-checkbox" data-id="'+r.id+'" data-table="all" '+(isChecked?'checked':'')+' onchange="updateSelections()"/></td>'+
        '<td>'+r.id+'</td><td>'+r.id_number+' - '+r.name+'</td><td>'+r.purpose+'</td><td>'+r.lab+'</td><td>'+pcDisplay+'</td><td>'+r.time_in+'</td><td>'+r.date+'</td>'+
        '<td><span class="'+badgeClass+'">'+statusText+'</span></td><td>'+created+'</td><td>'+actionsHtml+'</td></tr>';
    });
    if (allTable) allTable.destroy();
    allTable = $('#allTable').DataTable({ columnDefs:[{orderable:false,targets:[0,10]}], order:[[9,'desc']] });
    updateSelections();
  }).catch(function(e){ simsToast(e.message, false); });
}

function toggleSelectAll(table) {
  var checkbox = document.getElementById('selectAll'+table.charAt(0).toUpperCase()+table.slice(1));
  document.querySelectorAll('.res-checkbox[data-table="'+table+'"]').forEach(function(cb){ cb.checked = checkbox.checked; });
  updateSelections();
}

function updateSelections() {
  selectedReservations = {pending:[],all:[]};
  document.querySelectorAll('.res-checkbox:checked').forEach(function(cb){
    selectedReservations[cb.getAttribute('data-table')].push(parseInt(cb.getAttribute('data-id')));
  });
  var total = selectedReservations.pending.length + selectedReservations.all.length;
  document.getElementById('selectedCount').textContent = total;
  document.getElementById('bulkActionsBar').classList[total>0?'add':'remove']('show');
}

function clearSelections() {
  selectedReservations = {pending:[],all:[]};
  document.querySelectorAll('.res-checkbox').forEach(function(cb){ cb.checked=false; });
  document.querySelectorAll('[id^="selectAll"]').forEach(function(cb){ cb.checked=false; });
  updateSelections();
}

function bulkAction(actionType) {
  var allSelected = selectedReservations.pending.concat(selectedReservations.all);
  if (allSelected.length===0){ simsToast('No reservations selected.',false); return; }
  currentBulkAction = actionType;
  var label = actionType.charAt(0).toUpperCase()+actionType.slice(1);
  document.getElementById('bulkActionModalTitle').textContent = 'Bulk '+label;
  document.getElementById('bulkActionInfo').innerHTML = '<strong>Action:</strong> '+label+'<br><strong>Reservations:</strong> '+allSelected.length+' selected';
  document.getElementById('bulkActionNotes').value = '';
  new bootstrap.Modal(document.getElementById('bulkActionModal')).show();
}

function confirmBulkAction() {
  var allSelected = selectedReservations.pending.concat(selectedReservations.all);
  api({ action:'bulk_process_reservations', reservation_ids:JSON.stringify(allSelected), action_type:currentBulkAction, notes:document.getElementById('bulkActionNotes').value })
    .then(function(j){ simsToast(j.message,j.success); if(j.success){bootstrap.Modal.getInstance(document.getElementById('bulkActionModal')).hide();clearSelections();loadPending();loadAll();} })
    .catch(function(e){ simsToast(e.message,false); });
}

function openActionModal(id,student,purpose,lab,time,date) {
  document.getElementById('actionResId').value = id;
  document.getElementById('actionResInfo').innerHTML = '<strong>Student:</strong> '+student+'<br><strong>Purpose:</strong> '+purpose+'<br><strong>Lab:</strong> '+lab+'<br><strong>Time:</strong> '+time+'<br><strong>Date:</strong> '+date;
  document.getElementById('actionNotes').value = '';
  new bootstrap.Modal(document.getElementById('actionModal')).show();
}

function processReservation(action) {
  api({ action:action==='approve'?'approve_reservation':'reject_reservation', reservation_id:document.getElementById('actionResId').value, notes:document.getElementById('actionNotes').value })
    .then(function(j){ simsToast(j.message,j.success); if(j.success){bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();loadPending();loadAll();} })
    .catch(function(e){ simsToast(e.message,false); });
}

function openDetailsModal(rid) {
  document.getElementById('detailsModalBody').innerHTML = '<div class="text-center text-muted">Loading...</div>';
  new bootstrap.Modal(document.getElementById('detailsModal')).show();
  api({ action:'get_reservation_details', reservation_id:rid }).then(function(j) {
    if (!j.success){ simsToast(j.message,false); return; }
    var d=j.data, r=d.reservation;
    var statusBadge='';
    if(r.status==='pending') statusBadge='<span class="badge-pending">Pending</span>';
    else if(r.status==='approved') statusBadge='<span class="badge-approved">Approved</span>';
    else if(r.status==='rejected') statusBadge='<span class="badge-rejected">Rejected</span>';
    else if(r.status==='checked_in') statusBadge='<span class="badge-checked-in">Checked In</span>';
    else if(r.status==='completed') statusBadge='<span class="badge-completed">Completed</span>';
    else if(r.status==='expired') statusBadge='<span class="badge-expired">Expired</span>';
    var html='<div class="detail-section"><div class="detail-section-title">Reservation Information</div>'+
      '<div class="detail-row"><span class="detail-label">Reservation ID:</span><span class="detail-value">#'+r.id+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value">'+statusBadge+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Purpose:</span><span class="detail-value">'+escapeHtml(r.purpose)+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Lab:</span><span class="detail-value">'+escapeHtml(r.lab)+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">PC Number:</span><span class="detail-value">'+(r.pc_number?'PC '+r.pc_number:'Not assigned')+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Date & Time:</span><span class="detail-value">'+r.date+' at '+r.time_in+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Created:</span><span class="detail-value">'+new Date(r.created_at).toLocaleString()+'</span></div></div>'+
      '<div class="detail-section"><div class="detail-section-title">Student Information</div>'+
      '<div class="detail-row"><span class="detail-label">ID Number:</span><span class="detail-value">'+escapeHtml(r.id_number)+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value">'+escapeHtml(r.first_name)+' '+escapeHtml(r.last_name)+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Year Level:</span><span class="detail-value">'+r.year_level+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Course:</span><span class="detail-value">'+escapeHtml(r.course)+'</span></div>'+
      '<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">'+escapeHtml(r.email||'—')+'</span></div></div>';
    if(d.logs&&d.logs.length>0){
      html+='<div class="detail-section"><div class="detail-section-title">Action Logs ('+d.logs.length+')</div>';
      d.logs.forEach(function(log){ html+='<div class="log-entry"><div class="log-action">'+log.admin_name+' - '+log.action.toUpperCase()+'</div>'+(log.notes?'<div class="log-notes">'+escapeHtml(log.notes)+'</div>':'')+'<div class="log-time">'+new Date(log.created_at).toLocaleString()+'</div></div>'; });
      html+='</div>';
    }
    if(d.history&&d.history.length>0){
      html+='<div class="detail-section"><div class="detail-section-title">Recent Reservation History</div>';
      d.history.forEach(function(h){ html+='<div class="history-item"><span>'+escapeHtml(h.lab)+' - '+h.date+'</span><span class="'+(h.status==='approved'?'badge-approved':h.status==='rejected'?'badge-rejected':'badge-pending')+'">'+h.status.charAt(0).toUpperCase()+h.status.slice(1)+'</span></div>'; });
      html+='</div>';
    }
    document.getElementById('detailsModalBody').innerHTML = html;
  }).catch(function(e){ simsToast(e.message,false); });
}

function openCancelModal(rid,student,lab,time) {
  document.getElementById('cancelResId').value = rid;
  document.getElementById('cancelResInfo').innerHTML = '<strong>Student:</strong> '+student+'<br><strong>Lab:</strong> '+lab+'<br><strong>Time:</strong> '+time;
  document.getElementById('cancelNotes').value = '';
  new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function confirmCancelReservation() {
  api({ action:'cancel_reservation', reservation_id:document.getElementById('cancelResId').value, notes:document.getElementById('cancelNotes').value })
    .then(function(j){ simsToast(j.message,j.success); if(j.success){bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();loadPending();loadAll();} })
    .catch(function(e){ simsToast(e.message,false); });
}

function adminCheckIn(rid,studentName) {
  if(!confirm('Check in '+studentName+' for this reservation?')) return;
  api({ action:'admin_check_in', reservation_id:rid })
    .then(function(j){ simsToast(j.message,j.success); if(j.success){loadPending();loadAll();} })
    .catch(function(e){ simsToast(e.message,false); });
}

function toggleAutoExpire() {
  var toggle=document.getElementById('autoExpireToggle');
  if(toggle.classList.contains('active')){ toggle.classList.remove('active'); if(autoExpireInterval){clearInterval(autoExpireInterval);autoExpireInterval=null;} simsToast('Auto-expire disabled.',true); }
  else{ toggle.classList.add('active'); expireOldReservations(); autoExpireInterval=setInterval(function(){expireOldReservations();},60000); simsToast('Auto-expire enabled. Running every minute.',true); }
}

function expireOldReservations() {
  api({ action:'expire_old_reservations' }).then(function(j){ simsToast(j.message,j.success); if(j.success){loadPending();loadAll();} }).catch(function(e){ simsToast(e.message,false); });
}

function exportToCSV() {
  var params=getFilterParams(); params.action='get_reservations';
  api(params).then(function(j){
    if(!j.success){simsToast('Failed to load data.',false);return;}
    if(j.data.length===0){simsToast('No data to export.',false);return;}
    var csv='ID,Student ID,Student Name,Purpose,Lab,PC Number,Time In,Date,Status,Created At\n';
    j.data.forEach(function(r){ csv+=r.id+',"'+r.id_number+'","'+r.name+'","'+r.purpose+'","'+r.lab+'",'+(r.pc_number||'')+'","'+r.time_in+'","'+r.date+'","'+r.status+'","'+new Date(r.created_at).toLocaleString()+'"\n'; });
    var blob=new Blob([csv],{type:'text/csv'}), url=window.URL.createObjectURL(blob), a=document.createElement('a');
    a.href=url; a.download='reservations_'+new Date().toISOString().split('T')[0]+'.csv'; a.click();
    window.URL.revokeObjectURL(url); simsToast('CSV exported successfully!',true);
  }).catch(function(e){ simsToast(e.message,false); });
}

function loadStudentNotifications() {
  var studentId=document.getElementById('notifStudentId').value.trim();
  if(!studentId){simsToast('Please enter a student ID number.',false);return;}
  api({action:'get_reservations',search:studentId}).then(function(j){
    var reservations=j.data||[];
    if(reservations.length===0){simsToast('No student found with that ID.',false);return;}
    var student=reservations[0];
    api({action:'get_reservation_notifications',student_id:student.student_id}).then(function(j2){
      if(!j2.success){simsToast(j2.message,false);return;}
      document.getElementById('notifList').style.display='block';
      document.getElementById('notifStudentName').textContent=student.name;
      var notifs=j2.data;
      if(notifs.length===0){document.getElementById('notifListContent').innerHTML='<div class="text-center text-muted">No reservation notifications for this student.</div>';return;}
      var html='';
      notifs.forEach(function(n){
        var readBadge=n.is_read==1?'<span class="badge-completed">Read</span>':'<span class="badge-pending">Unread</span>';
        html+='<div class="log-entry" style="position:relative;"><div style="display:flex;justify-content:space-between;align-items:start;"><div class="log-action">'+escapeHtml(n.title)+'</div><button class="btn" onclick="resendNotification('+n.id+')" style="background:#f59e0b;color:#fff;border:none;border-radius:4px;padding:.2rem .5rem;font-size:.75rem;cursor:pointer;">Resend</button></div><div class="log-notes">'+escapeHtml(n.message)+'</div><div class="log-time">'+new Date(n.created_at).toLocaleString()+' '+readBadge+'</div></div>';
      });
      document.getElementById('notifListContent').innerHTML=html;
    }).catch(function(e){ simsToast(e.message,false); });
  }).catch(function(e){ simsToast(e.message,false); });
}

function resendNotification(notifId) {
  if(!confirm('Resend this notification?')) return;
  api({action:'resend_notification',notification_id:notifId}).then(function(j){ simsToast(j.message,j.success); if(j.success) loadStudentNotifications(); }).catch(function(e){ simsToast(e.message,false); });
}

function escapeHtml(text) {
  if (text === null || text === undefined) return '—';
  var div=document.createElement('div'); div.textContent=String(text); return div.innerHTML;
}

loadPending();
startAutoRefresh();
loadLabReservationStatuses();

var labStatuses = {};
var LABS = ['524','526','528','530','542','544'];

function loadLabReservationStatuses() {
  api({ action: 'get_lab_reservation_status' }).then(function(j) {
    if (j.success) {
      labStatuses = j.data;
      renderLabToggles();
    }
  }).catch(function() {});
}

function renderLabToggles() {
  var grid = document.getElementById('labToggleGrid');
  grid.innerHTML = '';
  LABS.forEach(function(lab) {
    var enabled = labStatuses[lab] !== false;
    var card = document.createElement('div');
    card.className = 'lab-toggle-card ' + (enabled ? 'enabled' : 'disabled');
    card.id = 'lab-card-' + lab;
    card.innerHTML =
      '<div>' +
        '<div class="lab-toggle-name">Lab ' + lab + '</div>' +
        '<div class="lab-toggle-status ' + (enabled ? 'on' : 'off') + '" id="lab-status-text-' + lab + '">' +
          (enabled ? '✓ Reservations Open' : '✗ Reservations Closed') +
        '</div>' +
      '</div>' +
      '<div class="lab-sw ' + (enabled ? 'on' : '') + '" id="lab-sw-' + lab + '" onclick="toggleLabReservation(\'' + lab + '\')"></div>';
    grid.appendChild(card);
  });
}

function toggleLabReservation(lab) {
  var currentlyEnabled = labStatuses[lab] !== false;
  var newState = currentlyEnabled ? 0 : 1;
  // Optimistic UI update
  labStatuses[lab] = newState === 1;
  updateLabToggleUI(lab, newState === 1);
  api({ action: 'toggle_lab_reservation', lab: lab, enable: newState })
    .then(function(j) {
      simsToast(j.message, j.success);
      if (!j.success) {
        // Revert on failure
        labStatuses[lab] = currentlyEnabled;
        updateLabToggleUI(lab, currentlyEnabled);
      }
    })
    .catch(function(e) {
      simsToast(e.message, false);
      labStatuses[lab] = currentlyEnabled;
      updateLabToggleUI(lab, currentlyEnabled);
    });
}

function updateLabToggleUI(lab, enabled) {
  var card = document.getElementById('lab-card-' + lab);
  var sw   = document.getElementById('lab-sw-' + lab);
  var txt  = document.getElementById('lab-status-text-' + lab);
  if (!card) return;
  card.className = 'lab-toggle-card ' + (enabled ? 'enabled' : 'disabled');
  sw.className   = 'lab-sw ' + (enabled ? 'on' : '');
  txt.className  = 'lab-toggle-status ' + (enabled ? 'on' : 'off');
  txt.textContent = enabled ? '✓ Reservations Open' : '✗ Reservations Closed';
}
</script>
</body>
</html>
