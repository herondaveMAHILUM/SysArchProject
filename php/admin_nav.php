<?php
// Admin Navigation Bar — included on every admin page.
// To add/remove nav links, edit ONLY this file.
$current = basename($_SERVER['PHP_SELF']);
function admin_nav_link($href, $label, $current) {
    $active = basename($href) === $current ? ' active-nav' : '';
    return '<li class="nav-item"><a class="admin-nav-link'.$active.'" href="'.$href.'">'.$label.'</a></li>';
}
$reportsPages = ['admin-leaderboard.php','admin-analytics.php','admin-ai-recommendations.php','admin-manage-software.php'];
$reportsActive = in_array($current, $reportsPages) ? ' active-nav' : '';
?>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="admin-dashboard.php">
      <img src="assets/ucmainlogo.png" alt="UC Logo" class="brand-logo-img">
      <img src="assets/uccccslogo.png" alt="CCS Logo" class="brand-ccs-img">
      <span class="brand-name">CCS Sit-In Monitoring System</span>
    </a>
    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" style="border-color:rgba(255,255,255,0.3)">
      <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end gap-1" id="adminNav">
      <ul class="navbar-nav align-items-center gap-0 me-2">
        <?= admin_nav_link('admin-dashboard.php',    'Home',               $current) ?>
        <li class="nav-item"><a class="admin-nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">Search</a></li>
        <?= admin_nav_link('admin-students.php',     'Students',           $current) ?>
        <?= admin_nav_link('admin-sitinrecords.php', 'View Sit-in Records',$current) ?>
        <?= admin_nav_link('admin-feedback.php',     'Feedback Reports',   $current) ?>
        <?= admin_nav_link('admin-reservations.php', 'Reservations',       $current) ?>

        <!-- Reports & Tools Dropdown -->
        <li class="nav-item dropdown">
          <a class="admin-nav-link dropdown-toggle<?= $reportsActive ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
             style="cursor:pointer;">
            Reports &amp; Tools
          </a>
          <ul class="dropdown-menu dropdown-menu-end admin-dropdown">
            <li>
              <a class="admin-dropdown-item<?= $current==='admin-leaderboard.php' ? ' active' : '' ?>"
                 href="admin-leaderboard.php">Leaderboard</a>
            </li>
            <li>
              <a class="admin-dropdown-item<?= $current==='admin-analytics.php' ? ' active' : '' ?>"
                 href="admin-analytics.php">Reports &amp; Analytics</a>
            </li>
            <li>
              <a class="admin-dropdown-item<?= $current==='admin-ai-recommendations.php' ? ' active' : '' ?>"
                 href="admin-ai-recommendations.php">AI Recommendations</a>
            </li>
            <li><hr class="dropdown-divider" style="border-color:rgba(124,58,237,.15);margin:.3rem .8rem;"></li>
            <li>
              <a class="admin-dropdown-item<?= $current==='admin-manage-software.php' ? ' active' : '' ?>"
                 href="admin-manage-software.php">Manage Software</a>
            </li>
          </ul>
        </li>
      </ul>
      <button class="btn btn-logout-admin" onclick="document.getElementById('logoutModal').style.display='flex'">Log out</button>
    </div>
  </div>
</nav>

<style>
.admin-nav-link{color:rgba(255,255,255,.8)!important;font-weight:500;font-size:.88rem;padding:.38rem .75rem!important;transition:color .2s;text-decoration:none;}
.admin-nav-link:hover,.admin-nav-link.active-nav{color:var(--purple-light)!important;}
.admin-nav-link.dropdown-toggle::after{border-color:rgba(255,255,255,.6) transparent transparent;}
.btn-logout-admin{background:#ef4444;border:none;color:#fff;border-radius:8px;padding:.38rem 1.1rem;font-size:.9rem;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;}
.btn-logout-admin:hover{background:#dc2626;}
.admin-dropdown{background:#3b0764;border:1.5px solid rgba(167,139,250,.25);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.35);padding:.4rem;min-width:200px;}
.admin-dropdown-item{display:block;padding:.5rem .9rem;border-radius:8px;color:rgba(255,255,255,.82)!important;font-size:.85rem;font-weight:500;text-decoration:none;transition:background .15s,color .15s;}
.admin-dropdown-item:hover,.admin-dropdown-item.active{background:rgba(167,139,250,.2);color:var(--purple-light)!important;}
</style>
