<?php require_once 'php/auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sit-in Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .page-title{font-size:1.8rem;font-weight:700;color:var(--purple-deep);text-align:center;margin-bottom:1.6rem;}

    /* Summary stat cards */
    .summary-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.8rem;}
    .sum-card{background:#fff;border-radius:14px;padding:1.2rem 1.1rem;box-shadow:0 2px 16px rgba(59,7,100,.07);border-left:4px solid var(--purple-main);text-align:center;}
    .sum-card .sum-val{font-size:2rem;font-weight:800;color:var(--purple-deep);line-height:1.1;}
    .sum-card .sum-label{font-size:.8rem;font-weight:600;color:#888;margin-top:.25rem;text-transform:uppercase;letter-spacing:.04em;}
    .sum-card.green{border-left-color:#16a34a;} .sum-card.green .sum-val{color:#16a34a;}
    .sum-card.amber{border-left-color:#f59e0b;} .sum-card.amber .sum-val{color:#d97706;}
    .sum-card.blue{border-left-color:#3b82f6;} .sum-card.blue .sum-val{color:#2563eb;}
    .sum-card.red{border-left-color:#ef4444;} .sum-card.red .sum-val{color:#dc2626;}

    /* Filter bar */
    .filter-bar{background:#fff;border-radius:14px;padding:1.1rem 1.3rem;margin-bottom:1.4rem;box-shadow:0 2px 12px rgba(59,7,100,.06);display:flex;flex-wrap:wrap;gap:.7rem;align-items:flex-end;}
    .filter-bar label{font-size:.8rem;font-weight:700;color:#666;margin-bottom:.2rem;display:block;}
    .filter-bar .form-control,.filter-bar .form-select{font-size:.86rem;border-radius:8px;border:1.5px solid var(--purple-pale);}
    .btn-filter{background:var(--purple-main);border:none;color:#fff;border-radius:8px;padding:.42rem 1.3rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;height:38px;}
    .btn-filter:hover{background:var(--purple-deep);}
    .btn-reset{background:#e5e7eb;border:none;color:#555;border-radius:8px;padding:.42rem 1rem;font-weight:600;font-family:'Nunito',sans-serif;cursor:pointer;height:38px;}
    .btn-reset:hover{background:#d1d5db;}

    /* Charts */
    .charts-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-bottom:1.4rem;}
    @media(max-width:768px){.charts-row{grid-template-columns:1fr;}}
    .chart-card{background:#fff;border-radius:14px;padding:1.3rem 1.3rem 1rem;box-shadow:0 2px 16px rgba(59,7,100,.07);}
    .chart-card-title{font-size:.95rem;font-weight:700;color:var(--purple-deep);margin-bottom:1rem;}

    /* Table */
    .badge-purpose{background:var(--purple-pale);color:var(--purple-main);border-radius:20px;padding:.22rem .7rem;font-size:.78rem;font-weight:600;}
    .badge-lab{background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:.22rem .7rem;font-size:.78rem;font-weight:600;}
    .badge-active{background:#dcfce7;color:#16a34a;border-radius:20px;padding:.22rem .7rem;font-size:.78rem;font-weight:600;}
    .badge-done{background:#f3f4f6;color:#6b7280;border-radius:20px;padding:.22rem .7rem;font-size:.78rem;font-weight:600;}
    table.dataTable thead th{font-weight:700;color:var(--purple-deep);font-size:.85rem;}
    table.dataTable tbody td{font-size:.84rem;vertical-align:middle;}

    /* Toast */
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToast.show{opacity:1;transform:translateY(0);pointer-events:auto;}
    #toastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div class="main-wrap">
  <div class="container-fluid px-0">

    <h2 class="page-title">Sit-in Summary</h2>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div>
        <label>Date From</label>
        <input type="date" class="form-control" id="filterFrom" style="width:155px;">
      </div>
      <div>
        <label>Date To</label>
        <input type="date" class="form-control" id="filterTo" style="width:155px;">
      </div>
      <div>
        <label>Laboratory</label>
        <select class="form-select" id="filterLab" style="width:130px;">
          <option value="">All Labs</option>
          <option>524</option><option>526</option><option>528</option>
          <option>530</option><option>542</option><option>544</option>
        </select>
      </div>
      <div>
        <label>Purpose</label>
        <select class="form-select" id="filterPurpose" style="width:180px;">
          <option value="">All Purposes</option>
          <option>C Programming</option><option>Java Programming</option>
          <option>C# Programming</option><option>ASP.Net</option>
          <option>PHP</option><option>Other</option>
        </select>
      </div>
      <div>
        <label>Status</label>
        <select class="form-select" id="filterStatus" style="width:130px;">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="done">Done</option>
        </select>
      </div>
      <div style="display:flex;gap:.5rem;padding-top:1.35rem;">
        <button class="btn-filter" id="applyFilterBtn">Apply</button>
        <button class="btn-reset" id="resetFilterBtn">Reset</button>
        <button id="exportSummaryPdfBtn" style="background:#0ea5e9;border:none;color:#fff;border-radius:8px;padding:.42rem 1.1rem;font-weight:700;font-size:.86rem;font-family:'Nunito',sans-serif;cursor:pointer;height:38px;">&#128196; Export PDF</button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
      <div class="sum-card blue"><div class="sum-val" id="cardTotal">—</div><div class="sum-label">Total Sit-ins</div></div>
      <div class="sum-card green"><div class="sum-val" id="cardDone">—</div><div class="sum-label">Completed</div></div>
      <div class="sum-card amber"><div class="sum-val" id="cardActive">—</div><div class="sum-label">Currently Active</div></div>
      <div class="sum-card"><div class="sum-val" id="cardAvgMin">—</div><div class="sum-label">Avg Duration (min)</div></div>
      <div class="sum-card red"><div class="sum-val" id="cardUnique">—</div><div class="sum-label">Unique Students</div></div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-card-title">📊 Sit-ins by Purpose</div>
        <canvas id="purposeChart" height="220"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-card-title">🏫 Sit-ins by Laboratory</div>
        <canvas id="labChart" height="220"></canvas>
      </div>
    </div>

    <!-- Daily Trend Chart -->
    <div class="chart-card" style="margin-bottom:1.4rem;">
      <div class="chart-card-title">📅 Daily Sit-in Trend</div>
      <canvas id="trendChart" height="110"></canvas>
    </div>

    <!-- Sit-in Records Table -->
    <div class="dash-card">
      <div class="card-title-bar" style="margin-bottom:1rem;">Sit-in Records</div>
      <table id="summaryTable" class="table table-hover w-100">
        <thead>
          <tr>
            <th>ID Number</th>
            <th>Name</th>
            <th>Purpose</th>
            <th>Lab</th>
            <th>Date</th>
            <th>Login</th>
            <th>Logout</th>
            <th>Duration</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="summaryTbody"></tbody>
      </table>
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
  <button id="toastClose" onclick="simsToastHide()">&#x2715;</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="sims.js"></script>
<script>
var dtTable = null;
var purposeChart = null, labChart = null, trendChart = null;

function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,200));} });
}

function getFilters() {
  return {
    date_from : document.getElementById('filterFrom').value,
    date_to   : document.getElementById('filterTo').value,
    lab       : document.getElementById('filterLab').value,
    purpose   : document.getElementById('filterPurpose').value,
    status    : document.getElementById('filterStatus').value
  };
}

function fmtDuration(loginTime, logoutTime) {
  if (!loginTime || !logoutTime) return '—';
  var a = new Date('1970-01-01T' + loginTime);
  var b = new Date('1970-01-01T' + logoutTime);
  var diff = Math.round((b - a) / 60000);
  if (isNaN(diff) || diff < 0) return '—';
  if (diff < 60) return diff + ' min';
  return Math.floor(diff/60) + 'h ' + (diff%60) + 'm';
}

function fmtTime(t) {
  if (!t) return '—';
  return t.substring(0,5);
}

function loadSummary() {
  var f = getFilters();
  api(Object.assign({ action:'get_sitin_summary' }, f)).then(function(j){
    if (!j.success) { simsToast(j.message || 'Failed to load summary.', false); return; }

    var records = j.data.records || [];
    var stats   = j.data.stats   || {};
    var byPurp  = j.data.by_purpose || [];
    var byLab   = j.data.by_lab     || [];
    var byDay   = j.data.by_day     || [];

    // Cards
    document.getElementById('cardTotal').textContent   = stats.total   || 0;
    document.getElementById('cardDone').textContent    = stats.done    || 0;
    document.getElementById('cardActive').textContent  = stats.active  || 0;
    document.getElementById('cardAvgMin').textContent  = stats.avg_duration_min ? Math.round(stats.avg_duration_min) : '—';
    document.getElementById('cardUnique').textContent  = stats.unique_students || 0;

    // Purpose Chart
    if (purposeChart) purposeChart.destroy();
    purposeChart = new Chart(document.getElementById('purposeChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: byPurp.map(function(b){ return b.purpose; }),
        datasets: [{
          data: byPurp.map(function(b){ return b.cnt; }),
          backgroundColor: ['#7c3aed','#3b82f6','#ec4899','#f97316','#14b8a6','#eab308'],
          borderWidth: 2, borderColor: '#fff'
        }]
      },
      options: { responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{family:'Nunito',size:11}, padding:10 } } } }
    });

    // Lab Chart
    if (labChart) labChart.destroy();
    labChart = new Chart(document.getElementById('labChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: byLab.map(function(b){ return 'Lab '+b.lab; }),
        datasets: [{
          label: 'Sit-ins',
          data: byLab.map(function(b){ return b.cnt; }),
          backgroundColor: '#7c3aed', borderRadius: 6
        }]
      },
      options: {
        responsive:true,
        plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1, font:{family:'Nunito'} } }, x:{ ticks:{ font:{family:'Nunito'} } } }
      }
    });

    // Trend Chart
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: byDay.map(function(d){ return d.day; }),
        datasets: [{
          label: 'Sit-ins',
          data: byDay.map(function(d){ return d.cnt; }),
          borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.1)',
          borderWidth: 2.5, fill: true, tension: 0.4,
          pointBackgroundColor: '#7c3aed', pointRadius: 4
        }]
      },
      options: {
        responsive:true,
        plugins:{ legend:{ display:false } },
        scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1, font:{family:'Nunito'} } }, x:{ ticks:{ font:{family:'Nunito', size:11} } } }
      }
    });

    // Table
    var tbody = document.getElementById('summaryTbody');
    tbody.innerHTML = '';
    records.forEach(function(r){
      var dur    = fmtDuration(r.login_time, r.logout_time);
      var badge  = r.status === 'active'
        ? '<span class="badge-active">Active</span>'
        : '<span class="badge-done">Done</span>';
      tbody.innerHTML +=
        '<tr>' +
        '<td>'+r.id_number+'</td>' +
        '<td>'+r.name+'</td>' +
        '<td><span class="badge-purpose">'+r.purpose+'</span></td>' +
        '<td><span class="badge-lab">'+r.lab+'</span></td>' +
        '<td>'+r.date+'</td>' +
        '<td>'+fmtTime(r.login_time)+'</td>' +
        '<td>'+fmtTime(r.logout_time)+'</td>' +
        '<td>'+dur+'</td>' +
        '<td>'+badge+'</td>' +
        '</tr>';
    });

    if (dtTable) dtTable.destroy();
    dtTable = $('#summaryTable').DataTable({ order:[[4,'desc']], pageLength:15 });

  }).catch(function(e){ simsToast(e.message, false); });
}

document.getElementById('applyFilterBtn').addEventListener('click', loadSummary);
document.getElementById('resetFilterBtn').addEventListener('click', function(){
  document.getElementById('filterFrom').value    = '';
  document.getElementById('filterTo').value      = '';
  document.getElementById('filterLab').value     = '';
  document.getElementById('filterPurpose').value = '';
  document.getElementById('filterStatus').value  = '';
  loadSummary();
});

loadSummary();

document.getElementById('exportSummaryPdfBtn').addEventListener('click', function() {
  var f = getFilters();
  api(Object.assign({ action:'get_sitin_summary' }, f)).then(function(j) {
    if (!j.success || !j.data) { alert('No data to export.'); return; }
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
    var pageW = doc.internal.pageSize.getWidth();
    var dateStr = new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'});
    var records = j.data.records || [];
    var stats   = j.data.stats   || {};
    var byPurp  = j.data.by_purpose || [];
    var byLab   = j.data.by_lab     || [];

    // Build filter label
    var filterParts = [];
    if (f.date_from) filterParts.push('From: '+f.date_from);
    if (f.date_to)   filterParts.push('To: '+f.date_to);
    if (f.lab)       filterParts.push('Lab: '+f.lab);
    if (f.purpose)   filterParts.push('Purpose: '+f.purpose);
    if (f.status)    filterParts.push('Status: '+f.status);
    var filterLabel = filterParts.length ? filterParts.join(' | ') : 'All Records';

    // Banner
    doc.setFillColor(109,40,217);
    doc.rect(0,0,pageW,28,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(16); doc.setTextColor(255,255,255);
    doc.text('CCS Sit-In Monitoring System', pageW/2, 11, {align:'center'});
    doc.setFontSize(10); doc.setFont('helvetica','normal');
    doc.text('Sit-in Summary Report', pageW/2, 19, {align:'center'});
    doc.setFontSize(8); doc.setTextColor(220,200,255);
    doc.text('Generated: '+dateStr+' | Filter: '+filterLabel, pageW/2, 25, {align:'center'});

    // Stats strip
    doc.setFillColor(245,240,255);
    doc.roundedRect(14,32,pageW-28,12,3,3,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(109,40,217);
    doc.text('Total: '+(stats.total||0), 20, 40);
    doc.text('Completed: '+(stats.done||0), pageW*0.22, 40);
    doc.text('Active: '+(stats.active||0), pageW*0.38, 40);
    doc.text('Avg Duration: '+(stats.avg_duration_min?Math.round(stats.avg_duration_min)+'min':'—'), pageW*0.54, 40);
    doc.text('Unique Students: '+(stats.unique_students||0), pageW-20, 40, {align:'right'});

    // Records table
    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Sit-in Records', 14, 52);

    var rows = records.map(function(r){
      var dur = '—';
      if (r.login_time && r.logout_time) {
        var a = new Date('1970-01-01T'+r.login_time), b = new Date('1970-01-01T'+r.logout_time);
        var diff = Math.round((b-a)/60000);
        dur = diff < 60 ? diff+'min' : Math.floor(diff/60)+'h '+(diff%60)+'m';
      }
      return [
        r.id_number, r.name, r.purpose, r.lab, r.date,
        r.login_time  ? r.login_time.substring(0,5)  : '—',
        r.logout_time ? r.logout_time.substring(0,5) : '—',
        dur,
        r.status === 'active' ? 'Active' : 'Done'
      ];
    });

    doc.autoTable({
      startY: 56,
      head: [['ID Number','Name','Purpose','Lab','Date','Login','Logout','Duration','Status']],
      body: rows,
      theme: 'grid',
      styles:{font:'helvetica',fontSize:8,cellPadding:2.5,valign:'middle'},
      headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{
        0:{halign:'center',cellWidth:28},
        1:{cellWidth:45},
        2:{cellWidth:36},
        3:{halign:'center',cellWidth:18},
        4:{halign:'center',cellWidth:24},
        5:{halign:'center',cellWidth:18},
        6:{halign:'center',cellWidth:18},
        7:{halign:'center',cellWidth:22},
        8:{halign:'center',cellWidth:18}
      },
      alternateRowStyles:{fillColor:[248,245,255]},
      didDrawCell: function(data) {
        if (data.section==='body' && data.column.index===8) {
          var val = data.cell.raw;
          doc.setFont('helvetica','bold');
          doc.setFontSize(8);
          doc.setTextColor(val==='Active' ? 22 : 107, val==='Active' ? 163 : 114, val==='Active' ? 74 : 128);
          doc.text(val, data.cell.x+data.cell.width/2, data.cell.y+data.cell.height/2+1, {align:'center'});
        }
      },
      didDrawPage: function() {
        var pg = doc.internal.getCurrentPageInfo().pageNumber;
        var tot = doc.internal.getNumberOfPages();
        doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(160,160,160);
        doc.text('Page '+pg+' of '+tot+' | CCS Sit-In Monitoring System — Sit-in Summary', pageW/2, doc.internal.pageSize.getHeight()-8, {align:'center'});
      }
    });

    // Lab + Purpose tables on new section
    var finalY = doc.lastAutoTable.finalY + 10;
    if (finalY > doc.internal.pageSize.getHeight() - 50) { doc.addPage(); finalY = 20; }

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Laboratory Breakdown', 14, finalY);
    doc.autoTable({
      startY: finalY+4,
      head: [['Laboratory','Count']],
      body: byLab.map(function(l){ return ['Lab '+l.lab, l.cnt]; }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:9,cellPadding:3},
      headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{0:{halign:'center'},1:{halign:'center'}},
      tableWidth:70
    });

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Purpose Breakdown', 100, finalY);
    doc.autoTable({
      startY: finalY+4,
      head: [['Purpose','Count']],
      body: byPurp.map(function(p){ return [p.purpose, p.cnt]; }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:9,cellPadding:3},
      headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{0:{cellWidth:50},1:{halign:'center',cellWidth:22}},
      margin:{left:100}
    });

    doc.save('SIMS_SitinSummary_'+new Date().toISOString().slice(0,10)+'.pdf');
  }).catch(function(e){ alert('Export failed: '+e.message); });
});
</script>
</body>
</html>
