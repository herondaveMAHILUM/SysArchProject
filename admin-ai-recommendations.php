<?php
require_once 'php/auth_admin.php';
require_once 'php/db.php';

$students_res = $conn->query("
    SELECT s.id, s.id_number, CONCAT(s.first_name,' ',s.last_name) AS name,
           s.course, s.year_level,
           COUNT(sr.id) AS total_sessions,
           SUM(CASE WHEN sr.logout_time IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,sr.login_time,sr.logout_time) ELSE 0 END) AS total_minutes
    FROM students s
    LEFT JOIN sitin_records sr ON sr.student_id=s.id AND sr.status='done'
    GROUP BY s.id
    ORDER BY total_sessions DESC
");
$all_students = [];
while ($r = $students_res->fetch_assoc()) $all_students[] = $r;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Recommendations - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .ai-hero{background:linear-gradient(135deg,#1e1b4b 0%,#3b0764 50%,#4c1d95 100%);border-radius:20px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden;}
    .ai-hero::before{content:'🤖';position:absolute;right:2rem;top:50%;transform:translateY(-50%);font-size:7rem;opacity:.1;}
    .ai-hero-badge{display:inline-block;background:rgba(167,139,250,.25);border:1px solid rgba(167,139,250,.4);color:#c4b5fd;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.3rem 1rem;border-radius:50px;margin-bottom:.6rem;}
    .student-card{background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(124,58,237,.07);padding:1.2rem 1.4rem;cursor:pointer;transition:all .2s;border:2px solid transparent;}
    .student-card:hover{border-color:var(--purple-light);box-shadow:0 4px 24px rgba(124,58,237,.14);}
    .student-card.selected{border-color:var(--purple-main);background:#faf8ff;}
    .student-card-name{font-weight:700;color:var(--purple-deep);font-size:.92rem;}
    .student-card-meta{font-size:.75rem;color:#aaa;margin-top:.15rem;}
    .student-card-badge{font-size:.72rem;font-weight:700;background:var(--purple-pale);color:var(--purple-deep);border-radius:6px;padding:.12rem .55rem;}
    .rec-box{background:#fff;border-radius:18px;box-shadow:0 2px 20px rgba(124,58,237,.08);padding:2rem;min-height:340px;position:relative;}
    .rec-empty{text-align:center;padding:3rem 1rem;color:#ccc;}
    .rec-empty .icon{font-size:3.5rem;margin-bottom:.8rem;}
    .rec-item{display:flex;gap:.9rem;padding:.9rem 0;border-bottom:1px solid #f3f0fb;}
    .rec-item:last-child{border-bottom:none;}
    .rec-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .rec-title{font-weight:700;color:var(--purple-deep);font-size:.9rem;margin-bottom:.15rem;}
    .rec-desc{font-size:.82rem;color:#666;line-height:1.5;}
    .rec-tag{display:inline-block;border-radius:6px;padding:.1rem .55rem;font-size:.7rem;font-weight:700;margin-right:.3rem;}
    .btn-generate{background:linear-gradient(135deg,var(--purple-main),#4c1d95);border:none;color:#fff;border-radius:10px;padding:.55rem 1.4rem;font-weight:700;font-size:.88rem;font-family:'Nunito',sans-serif;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.5rem;}
    .btn-generate:hover{opacity:.88;transform:translateY(-1px);}
    .btn-generate:disabled{opacity:.5;cursor:not-allowed;transform:none;}
    .thinking-dots span{display:inline-block;animation:blink 1.2s infinite;margin:0 1px;}
    .thinking-dots span:nth-child(2){animation-delay:.2s;}
    .thinking-dots span:nth-child(3){animation-delay:.4s;}
    @keyframes blink{0%,80%,100%{opacity:0;}40%{opacity:1;}}
    .stream-box{font-size:.88rem;color:#444;line-height:1.8;white-space:pre-wrap;font-family:'Nunito',sans-serif;}
    #loadingOverlay{position:fixed;inset:0;background:rgba(248,245,255,.9);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.8rem;}
    .spinner-ring{width:46px;height:46px;border:4px solid var(--purple-pale);border-top-color:var(--purple-main);border-radius:50%;animation:spin .8s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>

<?php include 'php/admin_nav.php'; ?>

<div class="main-wrap">
  <div class="ai-hero mb-4">
    <div style="position:relative;z-index:1;">
      <div class="ai-hero-badge">Powered by Claude AI</div>
      <h1 style="font-size:1.7rem;font-weight:800;margin:.2rem 0 .3rem;">AI Recommendation System</h1>
      <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin:0;">Select a student to generate personalized lab usage recommendations based on their sit-in history.</p>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="dash-card" style="max-height:75vh;overflow-y:auto;">
        <div class="card-title-bar">Select a Student</div>
        <input type="text" id="studentSearch" class="form-control mb-3" placeholder="Search by name or ID..." oninput="filterStudents()" style="font-size:.85rem;">
        <div id="studentList">
          <?php foreach($all_students as $s): ?>
          <div class="student-card mb-2"
               data-id="<?= $s['id'] ?>"
               data-name="<?= htmlspecialchars($s['name']) ?>"
               data-idnum="<?= htmlspecialchars($s['id_number']) ?>"
               data-course="<?= htmlspecialchars($s['course']) ?>"
               data-year="<?= $s['year_level'] ?>"
               data-sessions="<?= $s['total_sessions'] ?>"
               data-minutes="<?= $s['total_minutes'] ?>"
               onclick="selectStudent(this)">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="student-card-name"><?= htmlspecialchars($s['name']) ?></div>
                <div class="student-card-meta"><?= htmlspecialchars($s['id_number']) ?> &bull; <?= htmlspecialchars($s['course']) ?> Yr<?= $s['year_level'] ?></div>
              </div>
              <span class="student-card-badge"><?= $s['total_sessions'] ?> sess</span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($all_students)): ?>
          <p class="text-muted text-center" style="font-size:.88rem;">No students found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="rec-box">
        <div class="rec-empty" id="recEmpty">
          <div class="icon">🤖</div>
          <div style="font-weight:700;color:var(--purple-deep);margin-bottom:.3rem;">No student selected</div>
          <div style="font-size:.85rem;">Choose a student from the list to generate AI-powered recommendations.</div>
        </div>
        <div id="recHeader" style="display:none;">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3">
              <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--purple-main),var(--purple-light));display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:#fff;" id="recAvatar">–</div>
              <div>
                <div style="font-weight:800;color:var(--purple-deep);font-size:1.05rem;" id="recName">–</div>
                <div style="font-size:.78rem;color:#aaa;" id="recMeta">–</div>
              </div>
            </div>
            <button class="btn-generate" id="generateBtn" onclick="generateRecommendations()">
              <span>✨</span> Generate Recommendations
            </button>
          </div>
          <div class="d-flex gap-2 flex-wrap mb-3" id="recStats"></div>
          <div id="recOutput">
            <div class="rec-empty" style="padding:2rem 1rem;">
              <div class="icon" style="font-size:2.5rem;">✨</div>
              <div style="font-size:.88rem;color:#bbb;">Click "Generate Recommendations" to get AI insights for this student.</div>
            </div>
          </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var selectedStudentId = null, selectedStudentData = null;
function filterStudents() {
  var q = document.getElementById('studentSearch').value.toLowerCase();
  document.querySelectorAll('.student-card').forEach(function(card) {
    var name=card.dataset.name.toLowerCase(), idnum=card.dataset.idnum.toLowerCase();
    card.style.display=(name.includes(q)||idnum.includes(q))?'':'none';
  });
}
function initials(name){ var p=name.trim().split(' '); return (p[0][0]+(p[p.length-1][0]||'')).toUpperCase(); }
function statChip(icon,label,val,bg){ return '<span style="background:'+bg+';border-radius:8px;padding:.35rem .8rem;font-size:.78rem;font-weight:700;display:inline-flex;align-items:center;gap:.35rem;">'+icon+' <span style="color:#444;">'+label+':</span> <strong style="color:var(--purple-deep);">'+val+'</strong></span>'; }
function selectStudent(card) {
  document.querySelectorAll('.student-card').forEach(function(c){ c.classList.remove('selected'); });
  card.classList.add('selected');
  selectedStudentId=card.dataset.id;
  selectedStudentData={id:card.dataset.id,name:card.dataset.name,idnum:card.dataset.idnum,course:card.dataset.course,year:card.dataset.year,sessions:parseInt(card.dataset.sessions),minutes:parseInt(card.dataset.minutes)};
  document.getElementById('recEmpty').style.display='none';
  document.getElementById('recHeader').style.display='';
  document.getElementById('recAvatar').textContent=initials(selectedStudentData.name);
  document.getElementById('recName').textContent=selectedStudentData.name;
  document.getElementById('recMeta').textContent=selectedStudentData.idnum+' · '+selectedStudentData.course+' Year '+selectedStudentData.year;
  var hours=(selectedStudentData.minutes/60).toFixed(1);
  document.getElementById('recStats').innerHTML=statChip('Sessions',selectedStudentData.sessions,'#f3f0ff')+statChip('Lab Hours',hours+'h','#eff6ff')+statChip('Course',selectedStudentData.course,'#f0fdf4');
  document.getElementById('recOutput').innerHTML='<div class="rec-empty" style="padding:2rem 1rem;"><div style="font-size:.88rem;color:#bbb;">Click "Generate Recommendations" to get AI insights for this student.</div></div>';
}
function generateRecommendations() {
  if(!selectedStudentId) return;
  var btn=document.getElementById('generateBtn');
  btn.disabled=true; btn.innerHTML='<span class="thinking-dots"><span>.</span><span>.</span><span>.</span></span> Thinking';
  document.getElementById('recOutput').innerHTML='<div style="padding:1.5rem;text-align:center;color:var(--purple-main);"><div style="font-weight:700;margin-bottom:.3rem;">Claude AI is analyzing...</div><div style="font-size:.83rem;color:#aaa;">Reviewing sit-in history, lab usage, and patterns...</div></div>';
  var fd=new FormData(); fd.append('action','get_ai_recommendations'); fd.append('student_id',selectedStudentId);
  fetch('php/admin_actions.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(j){
    btn.disabled=false; btn.innerHTML='Regenerate';
    if(!j.success){ document.getElementById('recOutput').innerHTML='<div style="padding:1.5rem;text-align:center;color:#ef4444;"><div style="font-weight:700;">Failed to generate recommendations</div><div style="font-size:.83rem;color:#aaa;margin-top:.3rem;">'+(j.message||'Please try again.')+'</div></div>'; return; }
    renderRecommendations(j.recommendations);
  }).catch(function(){ btn.disabled=false; btn.innerHTML='Regenerate'; document.getElementById('recOutput').innerHTML='<div style="padding:1.5rem;text-align:center;color:#ef4444;"><div style="font-weight:700;">Connection error. Please try again.</div></div>'; });
}
function renderRecommendations(recs) {
  var colors=['#f3f0ff','#eff6ff','#f0fdf4','#fef9c3','#fdf2f8'], icons=['','','','','','','',''];
  var html='<div style="margin-bottom:.8rem;font-size:.78rem;font-weight:700;color:#aaa;letter-spacing:.08em;text-transform:uppercase;">AI-Generated Recommendations</div>';
  recs.forEach(function(rec,i){
    var bg=colors[i%colors.length],ico=icons[i%icons.length];
    html+='<div class="rec-item"><div class="rec-icon" style="background:'+bg+';">'+ico+'</div><div style="flex:1;"><div class="rec-title">'+rec.title+'</div><div class="rec-desc">'+rec.description+'</div>'+(rec.tag?'<span class="rec-tag" style="background:'+bg+';color:var(--purple-deep);margin-top:.4rem;display:inline-block;">'+rec.tag+'</span>':'')+'</div></div>';
  });
  html+='<div style="margin-top:1rem;font-size:.72rem;color:#ccc;text-align:right;">Generated by Claude AI · '+new Date().toLocaleString()+'</div>';
  document.getElementById('recOutput').innerHTML=html;
}
</script>
</body>
</html>
