<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);
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
<title>Messages | PawLix</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
body { font-family: 'Poppins', sans-serif; background: #e8dcc0; color: #2b2b2b; margin: 0; }
.user-menu-wrapper { position: relative; display: inline-block; }
.menu-icon-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 20px; background: #2b2b2b; color: white; border: none; font-size: 15px; font-weight: 600; }
.badge-count { background: #e63946; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
.user-dropdown-menu { display: none; position: absolute; right: 0; top: 48px; background-color: #ede1c6; min-width: 190px; box-shadow: 0px 8px 20px rgba(0,0,0,0.18); border-radius: 12px; overflow: hidden; z-index: 1000; border: 1px solid #ddccae; }
.user-dropdown-menu.show { display: block; }
.user-dropdown-menu a { color: #2b2b2b; padding: 12px 16px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
.user-dropdown-menu a:hover { background-color: #ddceac; }
.logout-link { color: #b3261e !important; }

.container { max-width: 850px; margin: 40px auto 60px; padding: 30px; background: #ede1c6; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #ddccae; }
.msg-card { background: #f2e6c9; padding: 20px; border-radius: 12px; border: 1px solid #ddccae; margin-bottom: 14px; }
</style>
</head>
<body>

  <header class="header">
    <div class="logo"><img src="assets/images/logo.png" alt="PawLix logo"></div>
    <nav class="nav">
      <a href="index.php">Home</a>
      <a href="browse.php">Browse Dogs ▾</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="report.php">Report a Dog</a>
    </nav>
    <div class="header-buttons">
      <div class="user-menu-wrapper">
        <button class="menu-icon-btn" id="userMenuBtn" onclick="toggleUserDropdown()">
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
  </header>

<div class="container">
  <h2>✉️ Messages from PawLix</h2>
  
  <div class="msg-card">
    <h4 style="margin:0 0 8px; font-size:16px;">Welcome to PawLix Shelter Community!</h4>
    <p style="margin:0; font-size:13px; color:#555; line-height:1.5;">Thank you for caring for animals across Nepal. Direct responses and notes from PawLix administrators regarding your adoption applications and stray dog rescue alerts will appear here.</p>
  </div>

  <div style="margin-top: 25px;">
    <a href="index.php" style="background:#1f6fd6; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">🏠 Back to Home</a>
  </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-columns">
            <div class="footer-col col-brand"><h4 class="col-title">PAWLIX</h4><p class="brand-text">Connecting dogs waiting for rescue with loving, permanent families across Nepal through a simple and secure platform.</p></div>
            <div class="footer-col"><h4 class="col-title">SERVICES</h4><p><a href="browse.php">Browse Dogs</a></p><p><a href="adopt.php">Apply for Adoption</a></p><p><a href="report.php">Report Stray / Injured</a></p><p><a href="contact.php">Support</a></p></div>
            <div class="footer-col"><h4 class="col-title">USEFUL LINKS</h4><p><a href="index.php">Home</a></p><p><a href="about.php">About Us</a></p><p><a href="contact.php">Contact Us</a></p></div>
            <div class="footer-col col-contact"><h4 class="col-title">CONTACT</h4><p><span class="icon">📍</span> Kathmandu, Nepal</p><p><span class="icon">✉</span> support@pawlix.org</p><p><span class="icon">📞</span> +977 9800000000</p></div>
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