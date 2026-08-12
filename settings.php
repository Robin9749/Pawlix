<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);
$user_res = mysqli_query($conn, "SELECT * FROM user WHERE user_id=$user_id");
$user = mysqli_fetch_assoc($user_res);

$success = "";
$error = "";

if (isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));

    $sql = "UPDATE user SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' WHERE user_id=$user_id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['user_name'] = $first_name . " " . $last_name;
        $success = "Profile updated successfully!";
        $user['first_name'] = $first_name;
        $user['last_name'] = $last_name;
        $user['email'] = $email;
        $user['phone'] = $phone;
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
}

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
<title>Settings - PawLix</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.user-menu-wrapper { position: relative; display: inline-block; }
.menu-icon-btn { cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 20px; background: #2b2b2b; color: white; border: none; font-size: 15px; font-weight: 600; }
.badge-count { background: #e63946; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
.user-dropdown-menu { display: none; position: absolute; right: 0; top: 48px; background-color: #ede1c6; min-width: 190px; box-shadow: 0px 8px 20px rgba(0,0,0,0.18); border-radius: 12px; overflow: hidden; z-index: 1000; border: 1px solid #ddccae; }
.user-dropdown-menu.show { display: block; }
.user-dropdown-menu a { color: #2b2b2b; padding: 12px 16px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
.user-dropdown-menu a:hover { background-color: #ddceac; }
.logout-link { color: #b3261e !important; }

.page-container { max-width: 700px; margin: 40px auto 60px; padding: 0 20px; }
.panel-card { background: #ede1c6; border-radius: 16px; padding: 30px; border: 1px solid #ddccae; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
.panel-card h2 { margin: 0 0 20px; font-size: 22px; font-weight: 700; color: #1a1a1a; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / 3; }
.form-group label { font-size: 13px; font-weight: 600; color: #2b2b2b; }
.form-group input { padding: 12px 14px; border: 1px solid #ddccae; border-radius: 10px; background: #ffffff; font-family: inherit; font-size: 14px; }
.form-group input:focus { outline: none; border-color: #f2932b; box-shadow: 0 0 5px rgba(242, 147, 43, 0.3); }
.btn-primary { background: #f2932b; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; font-family: inherit; cursor: pointer; transition: background 0.2s; width: 100%; }
.btn-primary:hover { background: #e08420; }
.alert-msg { padding: 12px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
@media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .form-group.full { grid-column: 1; } }
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
    <h2>⚙️ Account Settings</h2>

    <?php if ($success != "") { echo "<div class='alert-msg alert-success'>$success</div>"; } ?>
    <?php if ($error != "") { echo "<div class='alert-msg alert-error'>$error</div>"; } ?>

    <form method="POST" action="settings.php" class="form-grid">
      <div class="form-group">
        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
      </div>
      <div class="form-group full">
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
      </div>
      <div class="form-group full">
        <label>Phone Contact Number</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
      </div>
      <div class="form-group full" style="margin-top:10px;">
        <button type="submit" name="update_profile" class="btn-primary">Save Settings</button>
      </div>
    </form>
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