<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sit-in Records</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .badge-purpose{background:var(--purple-pale);color:var(--purple-main);border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-active{background:#dcfce7;color:#16a34a;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .badge-done{background:#f3f4f6;color:#6b7280;border-radius:20px;padding:.25rem .75rem;font-size:.8rem;font-weight:600;}
    .btn-timeout{background:#f59e0b;border:none;color:#fff;border-radius:6px;padding:.28rem .8rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-timeout:hover{background:#d97706;}
    table.dataTable thead th{font-weight:700;color:var(--purple-deep);font-size:.88rem;}
    table.dataTable tbody td{font-size:.88rem;vertical-align:middle;}
    .modal-content{border-radius:16px;border:none;box-shadow:0 8px 40px rgba(59,7,100,.18);}
    .modal-header{border-bottom:2px solid var(--purple-pale);padding:1.2rem 1.5rem;}
    .modal-title{font-weight:700;color:var(--purple-deep);font-size:1.1rem;}
    .btn-search-go{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.42rem 1.3rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;white-space:nowrap;}
    .btn-search-go:hover{background:var(--purple-deep);}
    .btn-sitin-confirm{background:#16a34a;border:none;color:#fff;border-radius:8px;padding:.42rem 1.6rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-sitin-confirm:hover{background:#15803d;}
    .btn-sitin-confirm:disabled{opacity:.6;cursor:not-allowed;}
    .btn-back-search{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.1rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-close-modal-plain{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .search-result-card{padding:.7rem 1rem;border-radius:10px;cursor:pointer;border:1.5px solid var(--purple-pale);margin-bottom:.5rem;transition:border-color .15s,background .15s;}
    .search-result-card:hover{background:var(--purple-pale);border-color:var(--purple-light);}
    .src-name{font-weight:700;color:var(--purple-deep);font-size:.93rem;}
    .src-meta{font-size:.8rem;color:#666;margin-top:.1rem;}
    .stu-panel{background:linear-gradient(135deg,var(--purple-pale) 0%,#f3f0ff 100%);border-radius:14px;padding:1.1rem 1.2rem;margin-bottom:1.1rem;border:1.5px solid var(--purple-light);}
    .stu-panel-name{font-size:1.08rem;font-weight:700;color:var(--purple-deep);margin-bottom:.55rem;}
    .stu-panel-row{display:flex;justify-content:space-between;align-items:center;padding:.2rem 0;font-size:.86rem;}
    .stu-panel-label{font-weight:700;color:#666;}
    .stu-panel-val{color:#333;}
    .sess-chip{display:inline-block;border-radius:20px;padding:.12rem .75rem;font-size:.82rem;font-weight:700;color:#fff;}
    .sess-chip.ok{background:var(--purple-main);}
    .sess-chip.low{background:#f59e0b;}
    .sess-chip.zero{background:#ef4444;}
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
      <h2 class="page-title">Sit-in Records</h2>
      <table id="recordsTable" class="table table-hover w-100">
        <thead><tr><th>ID Number</th><th>Name</th><th>Purpose</th><th>Lab</th><th>PC</th><th>Login</th><th>Logout</th><th>Date</th><th>Status</th><th>Type</th><th>Actions</th></tr></thead>
        <tbody id="recordsTbody"></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="searchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="searchModalTitle">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <div id="stepSearch">
          <div class="d-flex gap-2">
            <input type="text" class="form-control" id="searchInput" placeholder="ID number or name…" autocomplete="off">
            <button class="btn-search-go" id="doSearchBtn">Search</button>
          </div>
          <div id="searchResults" class="mt-3"></div>
        </div>
        <div id="stepSitin" style="display:none;">
          <div class="stu-panel" id="stuPanel"></div>
          <div class="mb-3">
            <label class="form-label" style="font-size:.85rem;font-weight:700;">Purpose <span class="text-danger">*</span></label>
            <select class="form-select" id="ss_purpose">
              <option value="">— Select Purpose —</option>
              <option>C Programming</option><option>Java Programming</option><option>C# Programming</option>
              <option>ASP.Net</option><option>PHP</option><option>Other</option>
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label" style="font-size:.85rem;font-weight:700;">Laboratory <span class="text-danger">*</span></label>
            <select class="form-select" id="ss_lab">
              <option value="">— Select Lab —</option>
              <option>524</option><option>526</option><option>528</option><option>530</option><option>542</option><option>544</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <div id="footerSearch" class="w-100 d-flex justify-content-end">
          <button class="btn-close-modal-plain" data-bs-dismiss="modal">Close</button>
        </div>
        <div id="footerSitin" class="w-100 d-flex justify-content-between" style="display:none!important;">
          <button class="btn-back-search" id="backBtn">&#8592; Back</button>
          <button class="btn-sitin-confirm" id="doSitinFromSearchBtn">Sit In</button>
        </div>
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

<div id="simsToast">
  <span id="simsToastMsg"></span>
  <button id="toastClose" onclick="simsToastHide()">&#x2715;</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
var table;
var selectedStu = null;

function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,200));} });
}

function loadRecords() {
  api({ action:'get_sitin_records' }).then(function(j){
    var tbody = document.getElementById('recordsTbody');
    tbody.innerHTML = '';
    (j.data||[]).forEach(function(r){
      var lf = r.login_time  ? r.login_time.substring(0,5)  : '—';
      var of = r.logout_time ? r.logout_time.substring(0,5) : '—';
      var badge  = r.status==='active' ? '<span class="badge-active">Active</span>' : '<span class="badge-done">Done</span>';
      var type = r.is_reservation ? '<span class="badge-purpose" style="background:#dbeafe;color:#1d4ed8;">📋 Reservation</span>' : '<span class="badge-purpose">Walk-in</span>';
      var pc = r.pc_number ? 'PC '+r.pc_number : '—';
      var action = r.status==='active' ? '<button class="btn btn-timeout" onclick="doTimeout('+r.id+')">Time Out</button>' : '—';
      tbody.innerHTML += '<tr><td>'+r.id_number+'</td><td>'+r.name+'</td><td><span class="badge-purpose">'+r.purpose+'</span></td><td>'+r.lab+'</td><td>'+pc+'</td><td>'+lf+'</td><td>'+of+'</td><td>'+r.date+'</td><td>'+badge+'</td><td>'+type+'</td><td>'+action+'</td></tr>';
    });
    if (table) table.destroy();
    table = $('#recordsTable').DataTable({ columnDefs:[{orderable:false,targets:10}], order:[[7,'desc']] });
  }).catch(function(e){ simsToast(e.message, false); });
}

function doTimeout(id) {
  api({ action:'timeout', sitin_id:id }).then(function(j){
    simsToast(j.message, j.success);
    if (j.success) loadRecords();
  }).catch(function(e){ simsToast(e.message, false); });
}

document.getElementById('searchModal').addEventListener('hidden.bs.modal', function() {
  selectedStu=null;
  document.getElementById('searchInput').value='';
  document.getElementById('searchResults').innerHTML='';
  document.getElementById('ss_purpose').value='';
  document.getElementById('ss_lab').value='';
  document.getElementById('stepSearch').style.display='';
  document.getElementById('stepSitin').style.display='none';
  document.getElementById('footerSearch').style.display='';
  document.getElementById('footerSitin').style.display='none';
  document.getElementById('searchModalTitle').textContent='Search Student';
});

function runSearch() {
  var q=document.getElementById('searchInput').value.trim();
  if(!q){simsToast('Please enter an ID number or name.',false);return;}
  api({action:'search_student',query:q}).then(function(j){
    var box=document.getElementById('searchResults'); box.innerHTML='';
    if(!j.data||!j.data.length){box.innerHTML='<p class="text-muted mt-1" style="font-size:.88rem;">No students found.</p>';return;}
    j.data.forEach(function(s){
      var div=document.createElement('div'); div.className='search-result-card';
      var sess=parseInt(s.remaining_session);
      var c=sess>5?'var(--purple-main)':sess>0?'#f59e0b':'#ef4444';
      div.innerHTML='<div class="src-name">'+s.id_number+' &mdash; '+s.name+'</div><div class="src-meta">'+s.course+' &bull; Year '+s.year_level+' &bull; <span style="color:'+c+';font-weight:700;">'+sess+' sessions left</span></div>';
      div.addEventListener('click',function(){showSitinStep(s);});
      box.appendChild(div);
    });
  }).catch(function(e){simsToast(e.message,false);});
}

document.getElementById('doSearchBtn').addEventListener('click',runSearch);
document.getElementById('searchInput').addEventListener('keydown',function(e){if(e.key==='Enter')runSearch();});

function showSitinStep(s){
  selectedStu=s;
  document.getElementById('searchModalTitle').textContent='Student Information';
  var sess=parseInt(s.remaining_session);
  var cc=sess>5?'ok':sess>0?'low':'zero';
  document.getElementById('stuPanel').innerHTML='<div class="stu-panel-name">'+s.name+'</div><div class="stu-panel-row"><span class="stu-panel-label">ID Number</span><span class="stu-panel-val">'+s.id_number+'</span></div><div class="stu-panel-row"><span class="stu-panel-label">Course</span><span class="stu-panel-val">'+s.course+'</span></div><div class="stu-panel-row"><span class="stu-panel-label">Year Level</span><span class="stu-panel-val">'+s.year_level+'</span></div><div class="stu-panel-row"><span class="stu-panel-label">Sessions Left</span><span class="stu-panel-val"><span class="sess-chip '+cc+'">'+sess+'</span></span></div>';
  document.getElementById('stepSearch').style.display='none';
  document.getElementById('stepSitin').style.display='';
  document.getElementById('footerSearch').style.display='none';
  document.getElementById('footerSitin').style.display='';
}

document.getElementById('backBtn').addEventListener('click',function(){
  selectedStu=null;
  document.getElementById('stepSearch').style.display='';
  document.getElementById('stepSitin').style.display='none';
  document.getElementById('footerSearch').style.display='';
  document.getElementById('footerSitin').style.display='none';
  document.getElementById('searchModalTitle').textContent='Search Student';
});

document.getElementById('doSitinFromSearchBtn').addEventListener('click',function(){
  if(!selectedStu)return;
  var purpose=document.getElementById('ss_purpose').value,lab=document.getElementById('ss_lab').value;
  if(!purpose){simsToast('Please select a purpose.',false);return;}
  if(!lab){simsToast('Please select a laboratory.',false);return;}
  var btn=this; btn.disabled=true; btn.textContent='Processing…';
  api({action:'sitin',id_number:selectedStu.id_number,purpose:purpose,lab:lab})
    .then(function(j){
      simsToast(j.message,j.success);
      if(j.success){bootstrap.Modal.getInstance(document.getElementById('searchModal')).hide();loadRecords();}
      else{btn.disabled=false;btn.textContent='Sit In';}
    }).catch(function(e){simsToast(e.message,false);btn.disabled=false;btn.textContent='Sit In';});
});

loadRecords();
</script>
</body>
</html>
