<?php
require_once 'php/auth_user.php';
require_once 'php/db.php';
$sid=$_SESSION['student_id'];
$stmt=$conn->prepare("SELECT id_number,first_name,last_name,middle_name,year_level,course,address,email,profile_pic FROM students WHERE id=?");
$stmt->bind_param('i',$sid);$stmt->execute();
$u=$stmt->get_result()->fetch_assoc();$stmt->close();$conn->close();
$full_name=htmlspecialchars($u['first_name'].' '.$u['last_name']);
$initial=strtoupper(substr($u['first_name'],0,1));
$pic=$u['profile_pic']?'uploads/'.htmlspecialchars($u['profile_pic']):'';
$courses=['BSA','BSBA','BSIT','BSCS','BSCpE','BS Crim','BSCE','BSEE','BSME','BSIE','BSC','BSHRM','BSTM','BSEEd','BSSecEd','BSCA','BSIP','BSREM','BSOA'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    .nav-link-custom.active-page{color:var(--purple-light)!important;font-weight:700;border-bottom:2px solid var(--purple-light);padding-bottom:.15rem;}
    .avatar-img,.avatar-preview{width:70px;height:70px;border-radius:50%;object-fit:cover;}
    .form-control[readonly],.form-select[disabled]{background:#f8f5ff!important;cursor:not-allowed;color:#666;}
    .btn-edit-toggle{background:var(--purple-main);border:none;color:#fff;border-radius:10px;padding:.55rem 1.6rem;font-weight:700;font-size:.95rem;font-family:'Nunito',sans-serif;}
    .btn-edit-toggle:hover{background:var(--purple-deep);color:#fff;}
    #simsToast{position:fixed;top:1.2rem;right:1.2rem;z-index:99999;min-width:300px;max-width:420px;padding:1rem 1.2rem;border-radius:12px;color:#fff;font-family:'Nunito',sans-serif;font-weight:600;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;gap:.8rem;box-shadow:0 8px 30px rgba(0,0,0,.18);opacity:0;transform:translateY(-12px);transition:opacity .3s,transform .3s;pointer-events:none;background:#16a34a;}
    #simsToastClose{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;padding:0;opacity:.8;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="user-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <span class="nav-label ms-3 d-none d-lg-block">Edit Profile</span>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#editNav" style="border-color:rgba(255,255,255,0.3)"><span class="navbar-toggler-icon" style="filter:invert(1)"></span></button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="editNav">
      <ul class="navbar-nav align-items-center gap-1 me-2">
        <li class="nav-item dropdown"><button class="btn notif-btn dropdown-toggle" data-bs-toggle="dropdown" id="notifBtn">Notifications <span id="notifBadge" class="badge bg-danger" style="display:none;font-size:.65rem;">0</span></button>
          <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown" style="min-width:340px;max-height:400px;overflow-y:auto;">
            <li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><span class="dropdown-item text-muted" style="font-size:.85rem;" id="notifLoading">Loading notifications...</span></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link-custom" href="user-dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link-custom active-page" href="user-editprofile.php">Edit Profile</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-history.php">History</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-reservation.php">Reservation</a></li>
        <li class="nav-item"><a class="nav-link-custom" href="user-software.php">Lab Software</a></li>
      </ul>
      <button class="btn btn-logout" onclick="document.getElementById('logoutModal').style.display='flex'">Logout</button>
    </div>
  </div>
</nav>

<div class="edit-page-wrap">
  <div class="container" style="max-width:520px">
    <div class="page-header">
      <div id="headerAvatarWrap">
        <?php if($pic): ?><img src="<?=$pic?>" class="avatar-img" alt="Profile">
        <?php else: ?><div class="page-header-avatar"><?=$initial?></div><?php endif; ?>
      </div>
      <div><h1>Edit Profile</h1><p>View your information. Click <strong>Edit</strong> to make changes.</p></div>
    </div>
    <div class="edit-form-card">
      <div class="section-label">Profile Photo</div>
      <div class="photo-change-wrap">
        <div id="photoCircleWrap">
          <?php if($pic): ?><img src="<?=$pic?>" class="avatar-preview" alt="Profile">
          <?php else: ?><div class="photo-circle"><?=$initial?></div><?php endif; ?>
        </div>
        <div class="photo-instructions"><strong><?=$full_name?></strong><br>JPG, PNG accepted. Max 2MB.</div>
        <input type="file" id="picInput" accept="image/*" style="display:none">
        <button type="button" class="btn btn-change-photo ms-auto" id="changePicBtn" disabled onclick="document.getElementById('picInput').click()">Change Photo</button>
      </div>
      <form id="profileForm" enctype="multipart/form-data">
        <div class="section-label">Personal Information</div>
        <div class="mb-3"><label class="form-label">ID Number</label><input type="text" class="form-control" name="id_number" value="<?=htmlspecialchars($u['id_number'])?>" readonly/></div>
        <div class="mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" value="<?=htmlspecialchars($u['last_name'])?>" readonly/></div>
        <div class="mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" value="<?=htmlspecialchars($u['first_name'])?>" readonly/></div>
        <div class="mb-3"><label class="form-label">Middle Name <span style="font-size:.78rem;font-weight:400;color:#aaa;">(Optional)</span></label><input type="text" class="form-control" name="middle_name" value="<?=htmlspecialchars($u['middle_name'])?>" readonly/></div>
        <div class="mb-3"><label class="form-label">Year Level</label>
          <select class="form-select" name="year_level" disabled>
            <?php foreach([1,2,3,4] as $y): ?><option value="<?=$y?>" <?=$u['year_level']==$y?'selected':''?>><?=$y?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Course</label>
          <select class="form-select" name="course" disabled>
            <?php foreach($courses as $c): ?><option <?=$u['course']===$c?'selected':''?>><?=$c?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Address</label><input type="text" class="form-control" name="address" value="<?=htmlspecialchars($u['address'])?>" readonly/></div>
        <div class="section-label">Account Credentials</div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?=htmlspecialchars($u['email'])?>" readonly/></div>
        <div class="mb-3"><label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label><input type="password" class="form-control" name="password" readonly placeholder="••••••••"/></div>
        <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="confirm_password" readonly placeholder="••••••••"/></div>
        <div class="d-flex gap-3 mt-4 justify-content-end">
          <button type="button" class="btn btn-edit-toggle" id="editBtn">Edit</button>
          <a href="user-dashboard.php" class="btn-cancel-inline" id="cancelBtn" style="display:none">Cancel</a>
          <button type="submit" class="btn btn-save" id="saveBtn" style="display:none">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="simsToast"><span id="simsToastMsg"></span><button id="simsToastClose" onclick="simsToastHide()">&#x2715;</button></div>

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
<script src="sims.js"></script>
<script>
var allInputs=document.querySelectorAll('#profileForm input,#profileForm select');
var editBtn=document.getElementById('editBtn'),cancelBtn=document.getElementById('cancelBtn'),saveBtn=document.getElementById('saveBtn'),changePicBtn=document.getElementById('changePicBtn'),picInput=document.getElementById('picInput');
function setEditing(on){allInputs.forEach(function(el){if(el.tagName==='SELECT')el.disabled=!on;else el.readOnly=!on;});changePicBtn.disabled=!on;editBtn.style.display=on?'none':'';cancelBtn.style.display=on?'':'none';saveBtn.style.display=on?'':'none';}
editBtn.addEventListener('click',function(){setEditing(true);});
cancelBtn.addEventListener('click',function(){setEditing(false);});
picInput.addEventListener('change',function(){
  if(!this.files[0])return;
  var r=new FileReader();
  r.onload=function(e){
    document.getElementById('photoCircleWrap').innerHTML='<img src="'+e.target.result+'" class="avatar-preview" alt="Profile">';
    document.getElementById('headerAvatarWrap').innerHTML='<img src="'+e.target.result+'" class="avatar-img" alt="Profile">';
  };
  r.readAsDataURL(this.files[0]);
});
document.getElementById('profileForm').addEventListener('submit',function(e){
  e.preventDefault();
  saveBtn.disabled=true;saveBtn.textContent='Saving...';
  var fd=new FormData(this);
  if(picInput.files[0])fd.set('profile_pic',picInput.files[0]);
  simsPost('php/update_profile.php',fd)
    .then(function(json){
      simsToast(json.message,json.success);
      if(json.success)setTimeout(function(){window.location.href='user-dashboard.php';},1500);
      else{saveBtn.disabled=false;saveBtn.textContent='Save Changes';}
    })
    .catch(function(err){simsToast(err.message,false);saveBtn.disabled=false;saveBtn.textContent='Save Changes';});
});

function loadNotifications() {
  simsPost('php/admin_actions.php', { action:'get_notifications' })
    .then(function(json) {
      if (!json.success) return;
      var dropdown = document.getElementById('notifDropdown');
      var badge = document.getElementById('notifBadge');
      dropdown.innerHTML = '<li><h6 class="dropdown-header" style="color:var(--purple-main);font-weight:700;">Notifications</h6></li>';
      if (json.unread > 0) { badge.textContent = json.unread; badge.style.display = 'inline'; } else { badge.style.display = 'none'; }
      if (!json.data || json.data.length === 0) { dropdown.innerHTML += '<li><span class="dropdown-item text-muted" style="font-size:.85rem;">No new notifications</span></li>'; return; }
      json.data.forEach(function(n) {
        var icon = '📢';
        if (n.type === 'reservation') icon = n.is_read ? '✅' : '📋';
        var readClass = n.is_read ? '' : 'fw-bold';
        var time = new Date(n.created_at).toLocaleString();
        dropdown.innerHTML += '<li><a class="dropdown-item '+readClass+'" style="font-size:.83rem;padding:.5rem 1rem;cursor:pointer;" onclick="markRead('+n.id+')">'+icon+' <strong>'+n.title+'</strong><br><span style="color:#666;font-size:.78rem;">'+n.message+'</span><br><span style="color:#999;font-size:.72rem;">'+time+'</span></a></li><li><hr class="dropdown-divider" style="margin:0;"></li>';
      });
      dropdown.innerHTML += '<li><a class="dropdown-item text-center" style="font-size:.8rem;color:var(--purple-main);cursor:pointer;padding:.5rem;" onclick="markAllRead()">Mark all as read</a></li>';
    })
    .catch(function(err) { console.error('Failed to load notifications:', err); });
}

function markRead(id) { simsPost('php/admin_actions.php', { action:'mark_notification_read', notification_id:id }).then(function() { loadNotifications(); }); }
function markAllRead() { simsPost('php/admin_actions.php', { action:'mark_all_notifications_read' }).then(function() { loadNotifications(); }); }
document.getElementById('notifBtn').addEventListener('shown.bs.dropdown', function() { loadNotifications(); });
setInterval(function() { loadNotifications(); }, 30000);
</script>
</body>
</html>
