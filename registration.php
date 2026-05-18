<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    #toast-box {
      position: fixed; top: 1.2rem; right: 1.2rem; z-index: 99999;
      min-width: 300px; max-width: 420px; padding: 1rem 1.2rem;
      border-radius: 12px; color: #fff; font-family: 'Nunito', sans-serif;
      font-weight: 600; font-size: .95rem; display: flex;
      align-items: center; justify-content: space-between; gap: .8rem;
      box-shadow: 0 8px 30px rgba(0,0,0,.18); opacity: 0;
      transform: translateY(-12px); transition: opacity .3s, transform .3s;
      pointer-events: none;
    }
    #toast-box.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    #toast-close { background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; padding:0; opacity:.8; }
    .opt-label { font-size:.78rem; font-weight:400; color:#aaa; margin-left:.3rem; }
    .field-error { border-color: #dc2626 !important; box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <span class="brand-name">Sit In Monitoring System</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
      style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-2" id="mainNav">
      <ul class="navbar-nav align-items-center me-2">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
      </ul>
      <a href="login.php" class="btn btn-nav-login me-2">Login</a>
      <a href="registration.php" class="btn btn-nav-register">Register</a>
    </div>
  </div>
</nav>

<div class="page-wrap">
  <div class="form-card">
    <div class="form-card-header">
      <h2>Create Account</h2>
      <p>Fill in all required fields to register your student account.</p>
    </div>

    <div class="section-label">Personal Information</div>
    <div class="mb-3">
      <label class="form-label">ID Number <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="f_id_number" placeholder="e.g. 2024-00001"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Last Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="f_last_name"/>
    </div>
    <div class="mb-3">
      <label class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="f_first_name"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Middle Name <span class="opt-label">(Optional)</span></label>
      <input type="text" class="form-control" id="f_middle_name"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Course Level / Year <span class="text-danger">*</span></label>
      <select class="form-select" id="f_year_level">
        <option value="">-- Select Year --</option>
        <option value="1">1</option><option value="2">2</option>
        <option value="3">3</option><option value="4">4</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Course <span class="text-danger">*</span></label>
      <select class="form-select" id="f_course">
        <option value="">-- Select Course --</option>
        <option value="BS Accountancy">BS Accountancy</option>
        <option value="BS Business Administration">BS Business Administration</option>
        <option value="BS Computer Science">BS Computer Science</option>
        <option value="BS Information Technology">BS Information Technology</option>
        <option value="BS Computer Engineering">BS Computer Engineering</option>
        <option value="BS Criminology">BS Criminology</option>
        <option value="BS Civil Engineering">BS Civil Engineering</option>
        <option value="BS Electrical Engineering">BS Electrical Engineering</option>
        <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
        <option value="BS Industrial Engineering">BS Industrial Engineering</option>
        <option value="BS Commerce">BS Commerce</option>
        <option value="BS Hotel & Restaurant Management">BS Hotel & Restaurant Management</option>
        <option value="BS Tourism Management">BS Tourism Management</option>
        <option value="BS Elementary Education">BS Elementary Education</option>
        <option value="BS Secondary Education">BS Secondary Education</option>
        <option value="BS Customs Administration">BS Customs Administration</option>
        <option value="BS Industrial Psychology">BS Industrial Psychology</option>
        <option value="BS Real Estate Management">BS Real Estate Management</option>
        <option value="BS Office Administration">BS Office Administration</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Address <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="f_address"/>
    </div>

    <div class="section-label">Account Credentials</div>
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" class="form-control" id="f_email" placeholder="e.g. student@email.com"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Password <span class="text-danger">*</span></label>
      <input type="password" class="form-control" id="f_password" placeholder="Min. 6 characters"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
      <input type="password" class="form-control" id="f_confirm_password"/>
    </div>

    <button type="button" class="btn btn-signup" id="submitBtn" onclick="doRegister()">Sign Up</button>
    <a href="index.php" class="btn-cancel">Cancel</a>
    <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
  </div>
</div>

<div id="toast-box">
  <span id="toast-msg"></span>
  <button id="toast-close" onclick="hideToast()">&#x2715;</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var toastTimer = null;
function showToast(msg, ok) {
  var box = document.getElementById('toast-box');
  box.style.background = ok ? '#16a34a' : '#dc2626';
  document.getElementById('toast-msg').textContent = msg;
  box.classList.add('show');
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(hideToast, 4000);
}
function hideToast() { document.getElementById('toast-box').classList.remove('show'); }

function fieldError(id, msg) {
  var el = document.getElementById(id);
  el.classList.add('field-error');
  el.focus();
  showToast(msg, false);
  el.addEventListener('input', function(){ el.classList.remove('field-error'); }, { once: true });
  el.addEventListener('change', function(){ el.classList.remove('field-error'); }, { once: true });
}

function doRegister() {
  var id_number        = document.getElementById('f_id_number').value.trim();
  var last_name        = document.getElementById('f_last_name').value.trim();
  var first_name       = document.getElementById('f_first_name').value.trim();
  var middle_name      = document.getElementById('f_middle_name').value.trim();
  var year_level       = document.getElementById('f_year_level').value;
  var course           = document.getElementById('f_course').value;
  var address          = document.getElementById('f_address').value.trim();
  var email            = document.getElementById('f_email').value.trim();
  var password         = document.getElementById('f_password').value;
  var confirm_password = document.getElementById('f_confirm_password').value;
  var btn              = document.getElementById('submitBtn');

  if (!id_number)              { fieldError('f_id_number',       'ID Number is required.');                   return; }
  if (!last_name)              { fieldError('f_last_name',        'Last Name is required.');                   return; }
  if (!first_name)             { fieldError('f_first_name',       'First Name is required.');                  return; }
  if (!year_level)             { fieldError('f_year_level',       'Please select your Year Level.');           return; }
  if (!course)                 { fieldError('f_course',           'Please select your Course.');               return; }
  if (!address)                { fieldError('f_address',          'Address is required.');                     return; }
  if (!password)               { fieldError('f_password',         'Password is required.');                    return; }
  if (password.length < 6)     { fieldError('f_password',         'Password must be at least 6 characters.'); return; }
  if (!confirm_password)       { fieldError('f_confirm_password', 'Please confirm your password.');            return; }
  if (password !== confirm_password) { fieldError('f_confirm_password', 'Passwords do not match.');           return; }

  btn.disabled = true; btn.textContent = 'Registering...';
  var fd = new FormData();
  fd.append('id_number', id_number); fd.append('last_name', last_name);
  fd.append('first_name', first_name); fd.append('middle_name', middle_name);
  fd.append('year_level', year_level); fd.append('course', course);
  fd.append('address', address); fd.append('email', email);
  fd.append('password', password); fd.append('confirm_password', confirm_password);

  fetch('php/register.php', { method: 'POST', body: fd })
    .then(function(res) { return res.text(); })
    .then(function(text) {
      var json;
      try { json = JSON.parse(text); }
      catch(e) { showToast('Server error: ' + text.substring(0, 120), false); btn.disabled = false; btn.textContent = 'Sign Up'; return; }
      showToast(json.message, json.success);
      if (json.success) {
        setTimeout(function() { window.location.href = 'login.php'; }, 2000);
      } else { btn.disabled = false; btn.textContent = 'Sign Up'; }
    })
    .catch(function(err) { showToast('Network error: ' + err.message, false); btn.disabled = false; btn.textContent = 'Sign Up'; });
}
</script>
</body>
</html>
