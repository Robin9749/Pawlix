<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);
$reports = mysqli_query($conn, "SELECT * FROM report_dogs WHERE user_id = $user_id ORDER BY created_at DESC");
$adoptions = mysqli_query($conn, "SELECT adoption_application.*, dog.name AS dog_name FROM adoption_application JOIN dog ON adoption_application.dog_id = dog.dog_id WHERE user_id = $user_id ORDER BY application_date DESC");

$unreadCount = 0;
$r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $user_id AND status != 'Pending'");
$r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $user_id AND status != 'Pending'");
$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
$unreadCount = $c1 + $c2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - PawLix</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.user-menu-wrapper { position: relative; display: inline-block; }
.menu-icon-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 20px; background: #2b2b2b; color: white; border: none; font-size: 15px; font-weight: 600; }
.menu-icon-btn:hover { background: #444; }
.badge-count { background: #e63946; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
.user-dropdown-menu { display: none; position: absolute; right: 0; top: 48px; background-color: #ede1c6; min-width: 190px; box-shadow: 0px 8px 20px rgba(0,0,0,0.18); border-radius: 12px; overflow: hidden; z-index: 1000; border: 1px solid #ddccae; }
.user-dropdown-menu.show { display: block; }
.user-dropdown-menu a { color: #2b2b2b; padding: 12px 16px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
.user-dropdown-menu a:hover { background-color: #ddceac; }
.logout-link { color: #b3261e !important; }

.page-container { max-width: 900px; margin: 40px auto 60px; padding: 0 20px; }
.panel-card { background: #ede1c6; border-radius: 16px; padding: 30px; border: 1px solid #ddccae; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
.panel-card h2 { margin: 0 0 20px; font-size: 22px; font-weight: 700; color: #1a1a1a; }
.notif-item { background: #f2e6c9; border-radius: 12px; padding: 18px 20px; border: 1px solid #ddccae; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
.status-badge { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.badge-Approved, .badge-Rescued { background: #d1fae5; color: #065f46; }
.badge-Pending, .badge-Dispatched { background: #fef3c7; color: #92400e; }
.badge-Rejected, .badge-Closed { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

  <header class="header">
    <div class="logo">
      <img src="assets/images/logo.png" alt="PawLix logo">
    </div>
    <nav class="nav">
      <a href="index.php">Home</a>
      <a href="browse.php">Browse Dogs ▾</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="report.php">Report a Dog</a>
    </nav>
    <div class="header-buttons">
      <div class="user-menu-wrapper">
        <button class="menu-icon-btn" id="userMenuBtn" onclick="toggleUserDropdown()" aria-label="User Menu">
          <span>👤</span> ▾ <?php if ($unreadCount > 0): ?><span class="badge-count"><?php echo $unreadCount; ?></span><?php endif; ?>
        </button>
        <div class="user-dropdown-menu" id="userDropdownMenu">
          <a href="account.php"><span class="icon">👤</span> Account</a>
          <a href="messages.php"><span class="icon">✉️</span> Messages</a>
          <a href="notifications.php"><span class="icon">🔔</span> Notification</a>
          <a href="history.php"><span class="icon">📜</span> History</a>
          <a href="settings.php"><span class="icon">⚙️</span> Setting</a>
          <div style="height: 1px; background-color: #ddccae; margin: 4px 0;"></div>
          <a href="logout.php" class="logout-link"><span class="icon">🚪</span> Logout</a>
        </div>
      </div>
    </div>
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
  </header>

<hr style="background-color: white; height: 1px; border: none;">

<div class="page-container">
  <div class="panel-card">
    <h2>🔔 Notifications & Alerts</h2>

    <?php 
    $hasNotif = false;

    if ($reports && mysqli_num_rows($reports) > 0) {
      while($r = mysqli_fetch_assoc($reports)) {
        if ($r['status'] != 'Pending') {
          $hasNotif = true;
          echo "<div class='notif-item'>";
          echo "<div><strong style='font-size:15px;'>🚨 Rescue Report Alert Update</strong><p style='margin:4px 0 0; font-size:13px; color:#555;'>Report at <strong>".$r['location_area']."</strong> is now status: <strong>".$r['status']."</strong></p></div>";
          echo "<span class='status-badge badge-".$r['status']."'>".$r['status']."</span>";
          echo "</div>";
        }
      }
    }

    if ($adoptions && mysqli_num_rows($adoptions) > 0) {
      while($a = mysqli_fetch_assoc($adoptions)) {
        if ($a['status'] != 'Pending') {
          $hasNotif = true;
          echo "<div class='notif-item'>";
          echo "<div><strong style='font-size:15px;'>🐾 Adoption Request Update</strong><p style='margin:4px 0 0; font-size:13px; color:#555;'>Your request for <strong>".$a['dog_name']."</strong> has been <strong>".$a['status']."</strong>!</p></div>";
          echo "<span class='status-badge badge-".$a['status']."'>".$a['status']."</span>";
          echo "</div>";
        }
      }
    }

    if (!$hasNotif) {
      echo "<p style='color:#666; text-align:center; padding:20px;'>No new status updates at the moment.</p>";
    }
    ?>
  </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-columns">
            <div class="footer-col col-brand"><h4 class="col-title">PAWLIX</h4><p class="brand-text">Connecting dogs waiting for rescue with loving, permanent families across Nepal through a simple and secure platform.</p></div>
            <div class="footer-col"><h4 class="col-title">SERVICES</h4><p><a href="browse.php">Browse Dogs</a></p><p><a href="adopt.php">Apply for Adoption</a></p><p><a href="report.php">Report Stray / Injured</a></p><p><a href="contact.php">Support</a></p></div>
            <div class="footer-col"><h4 class="col-title">USEFUL LINKS</h4><p><a href="index.php">Home</a></p><p><a href="about.php">About Us</a></p><p><a href="contact.php">Contact Us</a></p></div>
            <div class="footer-col col-contact"><h4 class="col-title">CONTACT</h4><p><span class="icon">📍</span> Kathmandu, Nepal</p><p><span class="icon">✉</span> support@pawlix.org</p><p><span class="icon">📞</span> +977 9800000000</p><p><span class="icon">🐾</span> Emergency 24/7 Support</p></div>
        </div>
        <hr class="footer-hr">
        <div class="footer-bottom"><p class="copyright">© <?php echo date('Y'); ?> PawLix. All rights reserved.</p></div>
    </div>
</footer>

<script>
function toggleUserDropdown() {
  var menu = document.getElementById("userDropdownMenu");
  if (menu) { menu.classList.toggle("show"); }
}
window.addEventListener('click', function(e) {
  var btn = document.getElementById('userMenuBtn');
  var menu = document.getElementById('userDropdownMenu');
  if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
    menu.classList.remove('show');
  }
});
</script>
</body>
</html>