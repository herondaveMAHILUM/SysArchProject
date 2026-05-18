<?php
require_once 'php/auth_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports &amp; Analytics - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    /* ── Metric Cards ───────────────────────────────────────────── */
    .metric-card{background:#fff;border-radius:18px;box-shadow:0 2px 20px rgba(124,58,237,.08);padding:1.4rem 1.6rem;display:flex;align-items:center;gap:1.1rem;height:100%;}
    .metric-val{font-size:1.8rem;font-weight:700;color:var(--purple-deep);line-height:1;}
    .metric-label{font-size:.8rem;color:#888;font-weight:600;margin-top:.2rem;}

    /* ── Period Buttons ─────────────────────────────────────────── */
    .period-btn{background:#f3f0ff;border:1.5px solid transparent;color:var(--purple-deep);border-radius:8px;padding:.3rem .9rem;font-size:.82rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;transition:all .2s;}
    .period-btn.active,.period-btn:hover{background:var(--purple-main);color:#fff;border-color:var(--purple-main);}

    /* ── Ranking List ───────────────────────────────────────────── */
    .score-badge{display:inline-block;border-radius:20px;padding:.18rem .75rem;font-size:.8rem;font-weight:700;color:#fff;}
    .rank-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-size:.85rem;font-weight:700;flex-shrink:0;}
    .rank-badge.gold-r{background:#fef08a;color:#92400e;}.rank-badge.silver-r{background:#e5e7eb;color:#374151;}.rank-badge.bronze-r{background:#fed7aa;color:#92400e;}.rank-badge.other-r{background:var(--purple-pale);color:var(--purple-deep);font-size:.75rem;}
    .bar-bg{background:#f3f0ff;border-radius:999px;height:7px;flex:1;overflow:hidden;}
    .bar-fill{height:100%;border-radius:999px;transition:width .9s ease;}
    .weight-pill{display:inline-block;border-radius:6px;padding:.12rem .55rem;font-size:.72rem;font-weight:700;}

    /* ── Loading Overlay ────────────────────────────────────────── */
    #loadingOverlay{position:fixed;inset:0;background:rgba(248,245,255,.9);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.8rem;}
    .spinner-ring{width:46px;height:46px;border:4px solid var(--purple-pale);border-top-color:var(--purple-main);border-radius:50%;animation:spin .8s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg)}}

    /* ── Reports Download Section ───────────────────────────────── */
    .reports-section-title{font-size:1.15rem;font-weight:700;color:var(--purple-deep);margin:0 0 1rem;}
    .report-card{background:#fff;border-radius:16px;box-shadow:0 2px 18px rgba(124,58,237,.09);padding:1.5rem;display:flex;flex-direction:column;gap:.9rem;height:100%;border:1.5px solid transparent;transition:border-color .2s,box-shadow .2s;}
    .report-card:hover{border-color:var(--purple-light);box-shadow:0 6px 28px rgba(124,58,237,.14);}
    .report-card-title{font-size:1rem;font-weight:700;color:var(--purple-deep);margin:0;}
    .report-card-desc{font-size:.82rem;color:#888;margin:0;line-height:1.5;}
    .btn-download-pdf{background:var(--purple-main);border:none;color:#fff;border-radius:10px;padding:.5rem 1.2rem;font-size:.87rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;display:flex;align-items:center;transition:background .2s;width:100%;justify-content:center;}
    .btn-download-pdf:hover{background:var(--purple-deep);}
    .btn-download-pdf.students-btn{background:#7c3aed;}
    .btn-download-pdf.students-btn:hover{background:#5b21b6;}
    .btn-download-pdf.sitin-btn{background:#1d4ed8;}
    .btn-download-pdf.sitin-btn:hover{background:#1e3a8a;}
    .btn-download-pdf.feedback-btn{background:#16a34a;}
    .btn-download-pdf.feedback-btn:hover{background:#14532d;}
    .btn-download-pdf:disabled{opacity:.6;cursor:not-allowed;}

    .section-divider{border:none;border-top:2px solid #f3f0ff;margin:2rem 0;}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div id="loadingOverlay">
  <div class="spinner-ring"></div>
  <span style="color:var(--purple-main);font-weight:700;font-size:.93rem;">Loading analytics…</span>
</div>

<div class="main-wrap">

  <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h1 style="font-size:1.55rem;font-weight:700;color:var(--purple-deep);margin:0;">Reports &amp; Analytics</h1>
      <p style="color:#888;font-size:.85rem;margin:.2rem 0 0;">
        Student performance scored by
        <span class="weight-pill" style="background:#fef9c3;color:#92400e;">Points Earned 50%</span>
        <span class="weight-pill ms-1" style="background:#eff6ff;color:#1d4ed8;">Hours Sit-in 30%</span>
        <span class="weight-pill ms-1" style="background:#f0fdf4;color:#166534;">Tasks Completed 20%</span>
      </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <button class="period-btn active" onclick="setPeriod('all',this)">All Time</button>
      <button class="period-btn" onclick="setPeriod('month',this)">This Month</button>
      <button class="period-btn" onclick="setPeriod('week',this)">This Week</button>
      <button id="exportAnalyticsPdfBtn" style="background:#0ea5e9;border:none;color:#fff;border-radius:8px;padding:.3rem 1rem;font-size:.82rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;">Export Analytics PDF</button>
    </div>
  </div>

  <!-- ══ METRIC CARDS ═════════════════════════════════════════════ -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="metric-card"><div><div class="metric-val" id="mTopScore">—</div><div class="metric-label">Highest Score</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="metric-card"><div><div class="metric-val" id="mTotalHrs">—</div><div class="metric-label">Total Lab Hours</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="metric-card"><div><div class="metric-val" id="mTotalTasks">—</div><div class="metric-label">Sessions Completed</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="metric-card"><div><div class="metric-val" id="mActiveStudents">—</div><div class="metric-label">Active Students</div></div></div></div>
  </div>

  <!-- ══ ANALYTICS CHARTS & RANKINGS ══════════════════════════════ -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="dash-card">
        <div class="card-title-bar">Student Performance Rankings</div>
        <div id="scoreList" style="min-height:220px;"><p class="text-muted text-center mt-4" style="font-size:.9rem;">Loading…</p></div>
      </div>
    </div>
    <div class="col-lg-5 d-flex flex-column gap-4">
      <div class="dash-card"><div class="card-title-bar">Most Visited Laboratory</div><canvas id="labChart" height="180"></canvas></div>
      <div class="dash-card"><div class="card-title-bar">Purpose Breakdown</div><canvas id="purposeChart" height="180"></canvas></div>
    </div>
  </div>

  <!-- ══ DIVIDER ══════════════════════════════════════════════════ -->
  <hr class="section-divider">

  <!-- ══ DOWNLOAD REPORTS SECTION ═════════════════════════════════ -->
  <div class="mb-4">
    <h2 class="reports-section-title mb-1">Download Reports</h2>
    <p style="color:#888;font-size:.85rem;margin:0 0 1.2rem;">Generate and download official PDF reports for records keeping.</p>

    <div class="row g-4">

      <!-- Student List -->
      <div class="col-12 col-md-4">
        <div class="report-card">
          <div>
            <p class="report-card-title">Student List</p>
            <p class="report-card-desc">Full list of all registered students with ID, name, course, year level, and remaining sessions.</p>
          </div>
          <button class="btn-download-pdf students-btn" id="dlStudentsBtn">Download PDF</button>
        </div>
      </div>

      <!-- Sit-in Records -->
      <div class="col-12 col-md-4">
        <div class="report-card">
          <div>
            <p class="report-card-title">Sit-in Records</p>
            <p class="report-card-desc">Complete sit-in history including student ID, name, purpose, lab, login/logout times, and status.</p>
          </div>
          <button class="btn-download-pdf sitin-btn" id="dlSitinBtn">Download PDF</button>
        </div>
      </div>

      <!-- Feedbacks -->
      <div class="col-12 col-md-4">
        <div class="report-card">
          <div>
            <p class="report-card-title">Feedbacks</p>
            <p class="report-card-desc">All student feedback submissions with ratings, comments, session details, and submission date.</p>
          </div>
          <button class="btn-download-pdf feedback-btn" id="dlFeedbackBtn">Download PDF</button>
        </div>
      </div>

    </div>
  </div>

</div><!-- /.main-wrap -->

<!-- ══ LOGOUT MODAL ═════════════════════════════════════════════ -->
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
/* ═══════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════ */
var currentPeriod = 'all', labChartInst = null, purposeChartInst = null;

function api(params) {
  var fd = new FormData();
  Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
  return fetch('php/admin_actions.php', { method:'POST', body:fd })
    .then(function(r){ return r.text(); })
    .then(function(t){ try{return JSON.parse(t);}catch(e){throw new Error(t.substring(0,300));} });
}

function setPeriod(p, btn) {
  currentPeriod = p;
  document.querySelectorAll('.period-btn').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  loadAnalytics();
}

function rankBadge(i) {
  if (i===0) return '<span class="rank-badge gold-r">1</span>';
  if (i===1) return '<span class="rank-badge silver-r">2</span>';
  if (i===2) return '<span class="rank-badge bronze-r">3</span>';
  return '<span class="rank-badge other-r">'+(i+1)+'</span>';
}
function scoreColor(s){ return s>=75?'#16a34a':s>=45?'#f59e0b':'#ef4444'; }

/* ═══════════════════════════════════════════════════════════════
   ANALYTICS LOADER
═══════════════════════════════════════════════════════════════ */
function loadAnalytics() {
  document.getElementById('loadingOverlay').style.display = 'flex';
  api({ action:'get_analytics', period:currentPeriod }).then(function(j) {
    document.getElementById('loadingOverlay').style.display = 'none';
    if (!j.success) return;
    var d = j.data;
    document.getElementById('mTopScore').textContent      = d.top_score !== null ? d.top_score : '—';
    document.getElementById('mTotalHrs').textContent      = d.total_hours + 'h';
    document.getElementById('mTotalTasks').textContent    = d.total_tasks;
    document.getElementById('mActiveStudents').textContent= d.active_students;

    var maxScore = d.students.length > 0 ? parseFloat(d.students[0].score) : 100;
    if (maxScore === 0) maxScore = 100;
    var html = '';
    d.students.forEach(function(s, i){
      var score = parseFloat(s.score), barW = Math.round((score/maxScore)*100), clr = scoreColor(score);
      html += '<div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f3f0fb;">'
            + rankBadge(i)
            + '<div style="flex:1;min-width:0;">'
            +   '<div style="font-weight:700;color:var(--purple-deep);font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+s.name+'</div>'
            +   '<div style="font-size:.73rem;color:#bbb;">'+s.id_number+' &bull; '+s.course+'</div>'
            +   '<div style="display:flex;align-items:center;gap:.5rem;margin-top:.25rem;"><div class="bar-bg"><div class="bar-fill" style="width:'+barW+'%;background:'+clr+';"></div></div></div>'
            + '</div>'
            + '<div style="text-align:right;flex-shrink:0;"><span class="score-badge" style="background:'+clr+';">'+score.toFixed(1)+'</span><div style="font-size:.7rem;color:#bbb;margin-top:.18rem;">'+s.hours+'h &bull; '+s.sessions+' sess</div></div>'
            + '</div>';
    });
    if (!html) html = '<p class="text-muted text-center mt-4" style="font-size:.9rem;">No data for this period.</p>';
    document.getElementById('scoreList').innerHTML = html;

    var labColors = ['#7c3aed','#a78bfa','#c4b5fd','#6d28d9','#ddd6fe','#4c1d95'];
    if (labChartInst) labChartInst.destroy();
    labChartInst = new Chart(document.getElementById('labChart').getContext('2d'),{
      type:'bar',
      data:{labels:d.labs.map(function(l){return'Lab '+l.lab;}),datasets:[{data:d.labs.map(function(l){return l.cnt;}),backgroundColor:labColors,borderRadius:8,borderSkipped:false}]},
      options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f3f0ff'}},x:{grid:{display:false}}}}
    });

    var purColors = ['#3b82f6','#ec4899','#f97316','#eab308','#14b8a6','#8b5cf6','#ef4444'];
    if (purposeChartInst) purposeChartInst.destroy();
    purposeChartInst = new Chart(document.getElementById('purposeChart').getContext('2d'),{
      type:'doughnut',
      data:{labels:d.purposes.map(function(p){return p.purpose;}),datasets:[{data:d.purposes.map(function(p){return p.cnt;}),backgroundColor:purColors,borderWidth:2,borderColor:'#fff'}]},
      options:{responsive:true,plugins:{legend:{position:'bottom',labels:{font:{family:'Nunito',size:11},padding:10}}}}
    });
  }).catch(function(){
    document.getElementById('loadingOverlay').style.display = 'none';
  });
}

loadAnalytics();

/* ═══════════════════════════════════════════════════════════════
   PDF HELPERS
═══════════════════════════════════════════════════════════════ */
function pdfHeader(doc, title) {
  var pageW = doc.internal.pageSize.getWidth();
  var now   = new Date();
  var dateStr = now.toLocaleDateString('en-US', {year:'numeric',month:'long',day:'numeric'});

  doc.setFillColor(109, 40, 217);
  doc.rect(0, 0, pageW, 32, 'F');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(16);
  doc.setTextColor(255, 255, 255);
  doc.text('CCS Sit-In Monitoring System', pageW / 2, 12, {align:'center'});

  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.text(title, pageW / 2, 21, {align:'center'});

  doc.setFontSize(8);
  doc.setTextColor(220, 200, 255);
  doc.text('Generated: ' + dateStr, pageW / 2, 28, {align:'center'});

  return { pageW:pageW, dateStr:dateStr };
}

function pdfFooter(doc) {
  var pageW  = doc.internal.pageSize.getWidth();
  var pageH  = doc.internal.pageSize.getHeight();
  var pgNum  = doc.internal.getCurrentPageInfo().pageNumber;
  var pgTot  = doc.internal.getNumberOfPages();
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(160, 160, 160);
  doc.text('Page ' + pgNum + ' of ' + pgTot + '  |  CCS Sit-In Monitoring System', pageW/2, pageH - 8, {align:'center'});
}

/* ═══════════════════════════════════════════════════════════════
   EXPORT: ANALYTICS PDF
═══════════════════════════════════════════════════════════════ */
document.getElementById('exportAnalyticsPdfBtn').addEventListener('click', function() {
  api({ action:'get_analytics', period:currentPeriod }).then(function(j) {
    if (!j.success || !j.data) { alert('No analytics data to export.'); return; }
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
    var pageW = doc.internal.pageSize.getWidth();
    var periodLabel = currentPeriod==='all'?'All Time':currentPeriod==='month'?'This Month':'This Week';
    var d = j.data;
    var hdr = pdfHeader(doc, 'Analytics Report — ' + periodLabel);

    doc.setFillColor(245, 240, 255);
    doc.roundedRect(14, 36, pageW-28, 12, 3, 3, 'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(109,40,217);
    doc.text('Top Score: '+(d.top_score||'—'), 20, 44);
    doc.text('Total Lab Hours: '+d.total_hours+'h', pageW/4+10, 44);
    doc.text('Sessions Completed: '+d.total_tasks, pageW/2, 44, {align:'center'});
    doc.text('Active Students: '+d.active_students, pageW-20, 44, {align:'right'});

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Student Performance Rankings', 14, 56);

    doc.autoTable({
      startY: 60,
      head: [['Rank','ID Number','Full Name','Course','Year','Sessions','Hours','Score']],
      body: d.students.map(function(s,i){
        return [i+1, s.id_number, s.name, s.course, s.year_level, s.sessions, s.hours+'h', parseFloat(s.score).toFixed(1)];
      }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:9,cellPadding:3,valign:'middle'},
      headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{0:{halign:'center',cellWidth:14},1:{halign:'center',cellWidth:32},2:{cellWidth:55},3:{halign:'center',cellWidth:28},4:{halign:'center',cellWidth:14},5:{halign:'center',cellWidth:22},6:{halign:'center',cellWidth:20},7:{halign:'center',cellWidth:20}},
      alternateRowStyles:{fillColor:[248,245,255]},
      didDrawPage: function(){ pdfFooter(doc); }
    });

    var finalY = doc.lastAutoTable.finalY + 10;
    if (finalY > doc.internal.pageSize.getHeight() - 40) { doc.addPage(); finalY = 20; }

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Laboratory Usage', 14, finalY);
    doc.autoTable({startY:finalY+4,head:[['Laboratory','Sit-in Count']],body:d.labs.map(function(l){return['Lab '+l.lab,l.cnt];}),theme:'grid',styles:{font:'helvetica',fontSize:9,cellPadding:3},headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},columnStyles:{0:{halign:'center'},1:{halign:'center'}},tableWidth:80,didDrawPage:function(){pdfFooter(doc);}});

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Purpose Breakdown', 110, finalY);
    doc.autoTable({startY:finalY+4,head:[['Purpose','Count']],body:d.purposes.map(function(p){return[p.purpose,p.cnt];}),theme:'grid',styles:{font:'helvetica',fontSize:9,cellPadding:3},headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},columnStyles:{0:{cellWidth:55},1:{halign:'center',cellWidth:25}},margin:{left:110},didDrawPage:function(){pdfFooter(doc);}});

    doc.save('SIMS_Analytics_'+currentPeriod+'_'+new Date().toISOString().slice(0,10)+'.pdf');
  }).catch(function(e){ alert('Export failed: '+e.message); });
});

/* ═══════════════════════════════════════════════════════════════
   DOWNLOAD: STUDENT LIST PDF
═══════════════════════════════════════════════════════════════ */
document.getElementById('dlStudentsBtn').addEventListener('click', function() {
  var btn = this;
  btn.disabled = true; btn.textContent = 'Generating…';

  api({ action:'get_students' }).then(function(j) {
    if (!j.success || !j.data || !j.data.length) {
      alert('No student data found.'); btn.disabled=false; btn.textContent='Download PDF'; return;
    }

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
    var hdr = pdfHeader(doc, 'Student List Report');
    var pageW = hdr.pageW;

    doc.setFillColor(245,240,255);
    doc.roundedRect(14,36,pageW-28,12,3,3,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(109,40,217);
    var total = j.data.length;
    var avgSess = (j.data.reduce(function(s,r){return s+parseInt(r.remaining_session||0);},0)/total).toFixed(1);
    doc.text('Total Students: '+total, 20, 44);
    doc.text('Avg. Sessions Remaining: '+avgSess, pageW/2, 44, {align:'center'});
    doc.text('Date: '+hdr.dateStr, pageW-20, 44, {align:'right'});

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(60,20,120);
    doc.text('Student Records', 14, 56);

    doc.autoTable({
      startY: 60,
      head: [['#','ID Number','Full Name','Year','Course','Sessions Left']],
      body: j.data.map(function(s,i){ return [i+1, s.id_number, s.name, s.year_level, s.course, s.remaining_session]; }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:9,cellPadding:3,valign:'middle'},
      headStyles:{fillColor:[109,40,217],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{0:{halign:'center',cellWidth:10},1:{halign:'center',cellWidth:30},2:{cellWidth:60},3:{halign:'center',cellWidth:16},4:{halign:'center',cellWidth:30},5:{halign:'center',cellWidth:28}},
      alternateRowStyles:{fillColor:[248,245,255]},
      didDrawPage:function(){pdfFooter(doc);}
    });

    doc.save('SIMS_StudentList_'+new Date().toISOString().slice(0,10)+'.pdf');
    btn.disabled=false; btn.textContent='Download PDF';
  }).catch(function(e){ alert('Failed: '+e.message); btn.disabled=false; btn.textContent='Download PDF'; });
});

/* ═══════════════════════════════════════════════════════════════
   DOWNLOAD: SIT-IN RECORDS PDF
═══════════════════════════════════════════════════════════════ */
document.getElementById('dlSitinBtn').addEventListener('click', function() {
  var btn = this;
  btn.disabled = true; btn.textContent = 'Generating…';

  api({ action:'get_sitin_records' }).then(function(j) {
    if (!j.success || !j.data || !j.data.length) {
      alert('No sit-in records found.'); btn.disabled=false; btn.textContent='Download PDF'; return;
    }

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
    var hdr = pdfHeader(doc, 'Sit-in Records Report');
    var pageW = hdr.pageW;

    doc.setFillColor(235,242,255);
    doc.roundedRect(14,36,pageW-28,12,3,3,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(29,78,216);
    var total = j.data.length;
    var active = j.data.filter(function(r){return r.status==='active';}).length;
    doc.text('Total Records: '+total, 20, 44);
    doc.text('Currently Active: '+active, pageW/2, 44, {align:'center'});
    doc.text('Date: '+hdr.dateStr, pageW-20, 44, {align:'right'});

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(29,78,216);
    doc.text('Sit-in Session Records', 14, 56);

    doc.autoTable({
      startY: 60,
      head: [['#','ID Number','Student Name','Purpose','Lab','PC','Login','Logout','Date','Status']],
      body: j.data.map(function(r,i){
        return [
          i+1,
          r.id_number,
          r.name,
          r.purpose,
          r.lab,
          r.pc_number ? 'PC '+r.pc_number : '—',
          r.login_time  ? r.login_time.substring(0,5)  : '—',
          r.logout_time ? r.logout_time.substring(0,5) : '—',
          r.date,
          r.status.charAt(0).toUpperCase()+r.status.slice(1)
        ];
      }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:8.5,cellPadding:3,valign:'middle'},
      headStyles:{fillColor:[29,78,216],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{
        0:{halign:'center',cellWidth:8},
        1:{halign:'center',cellWidth:26},
        2:{cellWidth:44},
        3:{cellWidth:32},
        4:{halign:'center',cellWidth:14},
        5:{halign:'center',cellWidth:16},
        6:{halign:'center',cellWidth:16},
        7:{halign:'center',cellWidth:16},
        8:{halign:'center',cellWidth:24},
        9:{halign:'center',cellWidth:18}
      },
      alternateRowStyles:{fillColor:[235,242,255]},
      didDrawCell:function(data){
        if(data.section==='body' && data.column.index===9){
          var val = String(data.cell.raw).toLowerCase();
          if(val==='active'){ doc.setTextColor(22,163,74); doc.setFont('helvetica','bold'); }
          else              { doc.setTextColor(107,114,128); doc.setFont('helvetica','normal'); }
          doc.setFontSize(8.5);
          doc.text(data.cell.raw, data.cell.x+data.cell.width/2, data.cell.y+data.cell.height/2+1, {align:'center'});
        }
      },
      didDrawPage:function(){pdfFooter(doc);}
    });

    doc.save('SIMS_SitinRecords_'+new Date().toISOString().slice(0,10)+'.pdf');
    btn.disabled=false; btn.textContent='Download PDF';
  }).catch(function(e){ alert('Failed: '+e.message); btn.disabled=false; btn.textContent='Download PDF'; });
});

/* ═══════════════════════════════════════════════════════════════
   DOWNLOAD: FEEDBACKS PDF
═══════════════════════════════════════════════════════════════ */
document.getElementById('dlFeedbackBtn').addEventListener('click', function() {
  var btn = this;
  btn.disabled = true; btn.textContent = 'Generating…';

  api({ action:'get_feedback_reports', filter:'all' }).then(function(j) {
    if (!j.success || !j.data || !j.data.feedback || !j.data.feedback.length) {
      alert('No feedback data found.'); btn.disabled=false; btn.textContent='Download PDF'; return;
    }

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
    var hdr = pdfHeader(doc, 'Feedback Report');
    var pageW = hdr.pageW;
    var stats = j.data.stats;
    var fb    = j.data.feedback;

    doc.setFillColor(220,252,231);
    doc.roundedRect(14,36,pageW-28,12,3,3,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(22,101,52);
    doc.text('Total Feedback: '+stats.total, 20, 44);
    doc.text('Average Rating: '+(stats.avg_rating||'—')+' / 5', pageW/3, 44);
    doc.text('Excellent (4-5 stars): '+stats.excellent, pageW*0.58, 44);
    doc.text('Poor (1-2 stars): '+stats.poor, pageW-50, 44);

    doc.setFont('helvetica','bold'); doc.setFontSize(10); doc.setTextColor(22,101,52);
    doc.text('Student Feedback Submissions', 14, 56);

    doc.autoTable({
      startY: 60,
      head: [['#','Student Name','Student ID','Rating','Purpose','Lab','Comments','Date']],
      body: fb.map(function(f,i){
        var dateStr = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : '—';
        return [
          i+1,
          f.student_name,
          f.student_id,
          f.rating+'/5',
          f.purpose || '—',
          f.lab ? 'Lab '+f.lab : '—',
          f.comments || 'No comments',
          dateStr
        ];
      }),
      theme:'grid',
      styles:{font:'helvetica',fontSize:8.5,cellPadding:3,valign:'middle'},
      headStyles:{fillColor:[22,163,74],textColor:[255,255,255],fontStyle:'bold',halign:'center'},
      columnStyles:{
        0:{halign:'center',cellWidth:8},
        1:{cellWidth:42},
        2:{halign:'center',cellWidth:26},
        3:{halign:'center',cellWidth:22},
        4:{cellWidth:30},
        5:{halign:'center',cellWidth:16},
        6:{cellWidth:80},
        7:{halign:'center',cellWidth:26}
      },
      alternateRowStyles:{fillColor:[220,252,231]},
      didDrawCell:function(data){
        if(data.section==='body' && data.column.index===3){
          var rating = parseInt(data.cell.raw);
          if(rating>=4)      doc.setTextColor(22,163,74);
          else if(rating<=2) doc.setTextColor(220,38,38);
          else               doc.setTextColor(217,119,6);
          doc.setFont('helvetica','bold'); doc.setFontSize(8.5);
          doc.text(data.cell.raw, data.cell.x+data.cell.width/2, data.cell.y+data.cell.height/2+1, {align:'center'});
        }
      },
      didDrawPage:function(){pdfFooter(doc);}
    });

    doc.save('SIMS_Feedbacks_'+new Date().toISOString().slice(0,10)+'.pdf');
    btn.disabled=false; btn.textContent='Download PDF';
  }).catch(function(e){ alert('Failed: '+e.message); btn.disabled=false; btn.textContent='Download PDF'; });
});
</script>
</body>
</html>
