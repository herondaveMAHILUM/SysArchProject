<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.4rem;}
    .btn-add{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.42rem 1.2rem;font-weight:700;font-size:.9rem;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-add:hover{background:var(--purple-deep);}
    .btn-reset-session{background:#ef4444;border:none;color:#fff;border-radius:8px;padding:.42rem 1.2rem;font-weight:700;font-size:.9rem;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-reset-session:hover{background:#dc2626;}
    .btn-export-pdf{background:#0ea5e9;border:none;color:#fff;border-radius:8px;padding:.42rem 1.2rem;font-weight:700;font-size:.9rem;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-export-pdf:hover{background:#0284c7;}
    .btn-edit{background:var(--purple-main);border:none;color:#fff;border-radius:6px;padding:.3rem .85rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-edit:hover{background:var(--purple-deep);}
    .btn-delete{background:#ef4444;border:none;color:#fff;border-radius:6px;padding:.3rem .85rem;font-size:.82rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-delete:hover{background:#dc2626;}
    table.dataTable thead th{font-weight:700;color:var(--purple-deep);font-size:.88rem;}
    table.dataTable tbody td{font-size:.88rem;vertical-align:middle;}
    .modal-content{border-radius:16px;border:none;box-shadow:0 8px 40px rgba(59,7,100,.18);}
    .modal-header{border-bottom:2px solid var(--purple-pale);padding:1.2rem 1.5rem;}
    .modal-title{font-weight:700;color:var(--purple-deep);font-size:1.1rem;}
    .btn-modal-save{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.42rem 1.4rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
    .btn-modal-save:hover{background:var(--purple-deep);}
    .btn-modal-cancel{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1.2rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;}
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
      <h2 class="page-title">Students Information</h2>
      <div class="d-flex gap-2 mb-3">
        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Students</button>
        <button class="btn btn-reset-session" id="resetAllBtn">Reset All Session</button>
        <button class="btn-export-pdf" id="exportPdfBtn">&#128196; Export PDF</button>
      </div>
      <table id="studentsTable" class="table table-hover w-100">
        <thead><tr><th>ID Number</th><th>Name</th><th>Year Level</th><th>Course</th><th>Remaining Session</th><th>Actions</th></tr></thead>
        <tbody id="studentsTbody"></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">ID Number *</label><input type="text" class="form-control" id="add_idnum"></div>
          <div class="col-6"><label class="form-label">First Name *</label><input type="text" class="form-control" id="add_fname"></div>
          <div class="col-6"><label class="form-label">Last Name *</label><input type="text" class="form-control" id="add_lname"></div>
          <div class="col-12"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="add_mname"></div>
          <div class="col-6"><label class="form-label">Year Level *</label>
            <select class="form-select" id="add_year"><option value="">--</option><option>1</option><option>2</option><option>3</option><option>4</option></select>
          </div>
          <div class="col-6"><label class="form-label">Course *</label>
            <select class="form-select" id="add_course"><option value="">--</option>
              <option>BSA</option><option>BSBA</option><option>BSIT</option><option>BSCS</option><option>BSCpE</option>
              <option>BS Crim</option><option>BSCE</option><option>BSEE</option><option>BSME</option><option>BSIE</option>
            </select>
          </div>
          <div class="col-12"><label class="form-label">Address</label><input type="text" class="form-control" id="add_address"></div>
          <div class="col-12"><label class="form-label">Email *</label><input type="email" class="form-control" id="add_email"></div>
          <div class="col-12"><label class="form-label">Password <small class="text-muted">(default: password123)</small></label><input type="text" class="form-control" id="add_pw" placeholder="password123"></div>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-modal-save" id="saveAddBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4 py-3">
        <input type="hidden" id="edit_id">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">ID Number</label><input type="text" class="form-control" id="edit_idnum"></div>
          <div class="col-6"><label class="form-label">First Name</label><input type="text" class="form-control" id="edit_fname"></div>
          <div class="col-6"><label class="form-label">Last Name</label><input type="text" class="form-control" id="edit_lname"></div>
          <div class="col-12"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="edit_mname"></div>
          <div class="col-6"><label class="form-label">Year Level</label>
            <select class="form-select" id="edit_year"><option>1</option><option>2</option><option>3</option><option>4</option></select>
          </div>
          <div class="col-6"><label class="form-label">Course</label>
            <select class="form-select" id="edit_course">
              <option>BSA</option><option>BSBA</option><option>BSIT</option><option>BSCS</option><option>BSCpE</option>
              <option>BS Crim</option><option>BSCE</option><option>BSEE</option><option>BSME</option><option>BSIE</option>
            </select>
          </div>
          <div class="col-12"><label class="form-label">Address</label><input type="text" class="form-control" id="edit_address"></div>
          <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" id="edit_email"></div>
          <div class="col-12"><label class="form-label">Remaining Session</label><input type="number" class="form-control" id="edit_sessions"></div>
        </div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-modal-save" id="saveEditBtn">Save Changes</button>
      </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
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

function loadStudents() {
  api({ action:'get_students' }).then(function(j) {
    var tbody = document.getElementById('studentsTbody');
    tbody.innerHTML = '';
    (j.data||[]).forEach(function(s){
      tbody.innerHTML += '<tr><td>'+s.id_number+'</td><td>'+s.name+'</td><td>'+s.year_level+'</td><td>'+s.course+'</td><td>'+s.remaining_session+'</td><td><button class="btn btn-edit me-1" onclick="openEdit('+s.id+')">Edit</button><button class="btn btn-delete" onclick="deleteStudent('+s.id+')">Delete</button></td></tr>';
    });
    if (table) table.destroy();
    table = $('#studentsTable').DataTable({ columnDefs:[{orderable:false,targets:5}] });
  }).catch(function(e){ simsToast(e.message, false); });
}

document.getElementById('saveAddBtn').addEventListener('click', function() {
  api({ action:'add_student', id_number:document.getElementById('add_idnum').value, first_name:document.getElementById('add_fname').value, last_name:document.getElementById('add_lname').value, middle_name:document.getElementById('add_mname').value, year_level:document.getElementById('add_year').value, course:document.getElementById('add_course').value, address:document.getElementById('add_address').value, email:document.getElementById('add_email').value, password:document.getElementById('add_pw').value||'password123' })
  .then(function(j){ simsToast(j.message,j.success); if(j.success){bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();loadStudents();} })
  .catch(function(e){ simsToast(e.message,false); });
});

function openEdit(id) {
  api({ action:'get_student', id:id }).then(function(j){
    if (!j.success) return; var s=j.data;
    document.getElementById('edit_id').value=s.id; document.getElementById('edit_idnum').value=s.id_number;
    document.getElementById('edit_fname').value=s.first_name; document.getElementById('edit_lname').value=s.last_name;
    document.getElementById('edit_mname').value=s.middle_name; document.getElementById('edit_year').value=s.year_level;
    document.getElementById('edit_course').value=s.course; document.getElementById('edit_address').value=s.address;
    document.getElementById('edit_email').value=s.email; document.getElementById('edit_sessions').value=s.remaining_session;
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
  });
}

document.getElementById('saveEditBtn').addEventListener('click', function() {
  api({ action:'edit_student', id:document.getElementById('edit_id').value, id_number:document.getElementById('edit_idnum').value, first_name:document.getElementById('edit_fname').value, last_name:document.getElementById('edit_lname').value, middle_name:document.getElementById('edit_mname').value, year_level:document.getElementById('edit_year').value, course:document.getElementById('edit_course').value, address:document.getElementById('edit_address').value, email:document.getElementById('edit_email').value, remaining_session:document.getElementById('edit_sessions').value })
  .then(function(j){ simsToast(j.message,j.success); if(j.success){bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();loadStudents();} })
  .catch(function(e){ simsToast(e.message,false); });
});

function deleteStudent(id) {
  if (!confirm('Delete this student? This cannot be undone.')) return;
  api({ action:'delete_student', id:id }).then(function(j){ simsToast(j.message,j.success); if(j.success) loadStudents(); });
}

document.getElementById('resetAllBtn').addEventListener('click', function() {
  if (!confirm('Reset ALL student sessions to 30?')) return;
  api({ action:'reset_sessions' }).then(function(j){ simsToast(j.message,j.success); if(j.success) loadStudents(); });
});

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
  if (!q){simsToast('Please enter an ID number or name.',false);return;}
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

document.getElementById('doSearchBtn').addEventListener('click', runSearch);
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
      if(j.success){bootstrap.Modal.getInstance(document.getElementById('searchModal')).hide();loadStudents();}
      else{btn.disabled=false;btn.textContent='Sit In';}
    }).catch(function(e){simsToast(e.message,false);btn.disabled=false;btn.textContent='Sit In';});
});

document.getElementById('exportPdfBtn').addEventListener('click', function() {
  api({ action: 'get_students' }).then(function(j) {
    if (!j.success || !j.data || !j.data.length) {
      simsToast('No student data to export.', false);
      return;
    }

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    var pageW = doc.internal.pageSize.getWidth();
    var now   = new Date();
    var dateStr = now.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });

    // ── Header block ────────────────────────────────────────────────
    // Purple banner
    doc.setFillColor(109, 40, 217);
    doc.rect(0, 0, pageW, 30, 'F');

    // System title
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.setTextColor(255, 255, 255);
    doc.text('CCS Sit-In Monitoring System', pageW / 2, 11, { align: 'center' });

    // Subtitle
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text('Student List Report', pageW / 2, 19, { align: 'center' });

    // Date generated
    doc.setFontSize(8);
    doc.setTextColor(220, 200, 255);
    doc.text('Generated: ' + dateStr, pageW / 2, 26, { align: 'center' });

    // ── Stats row ────────────────────────────────────────────────────
    doc.setFillColor(245, 240, 255);
    doc.roundedRect(14, 34, pageW - 28, 14, 3, 3, 'F');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(109, 40, 217);
    doc.text('Total Students: ' + j.data.length, 20, 43);

    var totalSessions = j.data.reduce(function(sum, s){ return sum + parseInt(s.remaining_session || 0); }, 0);
    var avgSessions   = j.data.length ? (totalSessions / j.data.length).toFixed(1) : 0;
    doc.text('Avg. Sessions Remaining: ' + avgSessions, pageW / 2, 43, { align: 'center' });
    doc.text('Date: ' + dateStr, pageW - 20, 43, { align: 'right' });

    // ── Table ─────────────────────────────────────────────────────────
    var rows = j.data.map(function(s, i) {
      return [
        i + 1,
        s.id_number,
        s.name,
        s.year_level,
        s.course,
        s.remaining_session
      ];
    });

    doc.autoTable({
      startY: 52,
      head: [['#', 'ID Number', 'Full Name', 'Year', 'Course', 'Sessions Left']],
      body: rows,
      theme: 'grid',
      styles: {
        font: 'helvetica',
        fontSize: 9,
        cellPadding: 3,
        valign: 'middle'
      },
      headStyles: {
        fillColor: [109, 40, 217],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        halign: 'center'
      },
      columnStyles: {
        0: { halign: 'center', cellWidth: 10 },
        1: { halign: 'center', cellWidth: 30 },
        2: { cellWidth: 60 },
        3: { halign: 'center', cellWidth: 16 },
        4: { halign: 'center', cellWidth: 30 },
        5: { halign: 'center', cellWidth: 28 }
      },
      alternateRowStyles: { fillColor: [248, 245, 255] },
      didDrawCell: function(data) {
        // Colour-code Sessions Left column (index 5)
        if (data.section === 'body' && data.column.index === 5) {
          var val = parseInt(data.cell.raw);
          if (val <= 0) {
            doc.setTextColor(220, 38, 38);
          } else if (val <= 5) {
            doc.setTextColor(217, 119, 6);
          } else {
            doc.setTextColor(22, 163, 74);
          }
          doc.setFont('helvetica', 'bold');
          doc.setFontSize(9);
          doc.text(
            String(val),
            data.cell.x + data.cell.width / 2,
            data.cell.y + data.cell.height / 2 + 1,
            { align: 'center' }
          );
          // Return false would skip default draw, but autoTable doesn't support it here;
          // the colour override still applies visually via the text rewrite above
        }
      },
      // ── Footer on every page ──────────────────────────────────────
      didDrawPage: function(data) {
        var pageCount = doc.internal.getNumberOfPages();
        var pageNum   = doc.internal.getCurrentPageInfo().pageNumber;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(
          'Page ' + pageNum + ' of ' + pageCount + '  |  CCS Sit-In Monitoring System',
          pageW / 2,
          doc.internal.pageSize.getHeight() - 8,
          { align: 'center' }
        );
      }
    });

    doc.save('SIMS_Students_' + now.toISOString().slice(0,10) + '.pdf');
    simsToast('PDF exported successfully!', true);
  }).catch(function(e){ simsToast('Export failed: ' + e.message, false); });
});

loadStudents();
</script>
</body>
</html>
