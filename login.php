<?php
session_start();
$flash      = $_SESSION['flash']      ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
  <style>
    #toast-box {
      position: fixed; top: 1.2rem; right: 1.2rem; z-index: 99999;
      min-width: 300px; max-width: 400px; padding: 1rem 1.2rem;
      border-radius: 12px; color: #fff; font-family: 'Nunito', sans-serif;
      font-weight: 600; font-size: .95rem; display: flex;
      align-items: center; justify-content: space-between; gap: .8rem;
      box-shadow: 0 8px 30px rgba(0,0,0,.18); opacity: 0;
      transform: translateY(-12px); transition: opacity .3s, transform .3s;
      pointer-events: none;
    }
    #toast-box.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    #toast-close { background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; padding:0; opacity:.8; }
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

<div class="login-page-wrap">
  <div class="login-card">
    <div class="login-card-header"><h2>Log In</h2></div>
    <div class="mb-3">
      <label class="form-label">ID Number</label>
      <input type="text" class="form-control" id="loginIdNumber" placeholder="Enter your ID number"/>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" class="form-control" id="loginPassword" placeholder="Enter your password"/>
    </div>
    <button type="button" class="btn btn-login" id="loginBtn" onclick="doLogin()">Log In</button>
    <div class="divider"></div>
    <div class="register-prompt">
      Don't have an account? <a href="registration.php">Register Here</a>
    </div>
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

<?php if ($flash): ?>
window.onload = function() {
  showToast(<?= json_encode($flash) ?>, <?= $flash_type === 'success' ? 'true' : 'false' ?>);
};
<?php endif; ?>

function doLogin() {
  var idNumber = document.getElementById('loginIdNumber').value.trim();
  var password = document.getElementById('loginPassword').value;
  var btn      = document.getElementById('loginBtn');
  if (!idNumber) { showToast('Please enter your ID number.', false); return; }
  if (!password) { showToast('Please enter your password.', false); return; }
  btn.disabled = true; btn.textContent = 'Logging in...';
  var fd = new FormData();
  fd.append('id_number', idNumber); fd.append('password', password);
  fetch('php/login.php', { method: 'POST', body: fd })
    .then(function(res) { return res.text(); })
    .then(function(text) {
      var json;
      try { json = JSON.parse(text); }
      catch(e) { showToast('Server error: ' + text.substring(0, 120), false); btn.disabled = false; btn.textContent = 'Log In'; return; }
      if (json.success) {
        showToast('Login successful! Redirecting...', true);
        setTimeout(function() { window.location.href = json.role === 'admin' ? 'admin-dashboard.php' : 'user-dashboard.php'; }, 1200);
      } else {
        showToast(json.message, false); btn.disabled = false; btn.textContent = 'Log In';
      }
    })
    .catch(function(err) { showToast('Network error: ' + err.message, false); btn.disabled = false; btn.textContent = 'Log In'; });
}
document.getElementById('loginPassword').addEventListener('keydown', function(e) { if (e.key === 'Enter') doLogin(); });
</script>
</body>
</html>
