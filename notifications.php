<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);

/* ================= FETCH STRAY / RESCUE REPORTS ================= */
$reports = mysqli_query(
    $conn,
    "SELECT * FROM report_dogs 
     WHERE user_id = $user_id 
     ORDER BY created_at DESC"
);

/* ================= FETCH ADOPTION APPLICATIONS ================= */
$adoptions = mysqli_query(
    $conn,
    "SELECT adoption_application.*, dog.name AS dog_name 
     FROM adoption_application 
     JOIN dog ON adoption_application.dog_id = dog.dog_id 
     WHERE user_id = $user_id 
     ORDER BY application_date DESC"
);

/* ================= FETCH ADMIN DIRECT MESSAGES ================= */
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL DEFAULT 1,
    sender_type ENUM('user', 'admin') NOT NULL,
    subject VARCHAR(255) DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$user_messages = mysqli_query(
    $conn,
    "SELECT * FROM messages 
     WHERE user_id = $user_id 
     AND sender_type = 'admin' 
     ORDER BY created_at DESC"
);

/* ================= CALCULATE UNREAD NOTIFICATION BADGE ================= */
$unreadCount = 0;

$r1 = @mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM report_dogs 
     WHERE user_id = $user_id 
     AND status != 'Pending'"
);

$r2 = @mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM adoption_application 
     WHERE user_id = $user_id 
     AND status != 'Pending'"
);

$r3 = @mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM messages 
     WHERE user_id = $user_id 
     AND sender_type = 'admin' 
     AND is_read = 0"
);

$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
$c3 = ($r3) ? mysqli_fetch_assoc($r3)['total'] : 0;

$unreadCount = $c1 + $c2 + $c3;
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notifications - PawLix</title>

<link rel="stylesheet" href="assets/css/style.css">

<style>

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

:root {
  --cream: #e9d9b8;
  --tan-light: #ecdfc3;
  --pale-yellow: #f3ecd5;
  --tan-card: #f8eac9;
  --maroon: #7a1f1f;
  --dark-brown: #4a3223;
  --orange: #ff7f11;
  --orange-dark: #e06600;
  --navy: #0e1524;
  --border-color: #dfcfb0;
}

body {
  font-family: 'Poppins', sans-serif;
  background-color: var(--pale-yellow);
  color: #222;
  margin: 0;
  padding: 0;
}


/* ================= USER MENU ================= */

.user-menu-wrapper {
  position: relative;
  display: inline-block;
}

.menu-icon-btn {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 20px;
  background: #f0e4c7;
  color: black;
  border: none;
  font-size: 16px;
  transition: background 0.2s;
}

.menu-icon-btn:hover {
  background: #dccfad;
}

.badge-count {
  background: #e63946;
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 10px;
}

.user-dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 48px;
  background-color: #ede1c6;
  min-width: 190px;
  box-shadow: 0px 8px 20px rgba(0,0,0,0.18);
  border-radius: 12px;
  overflow: hidden;
  z-index: 1000;
  border: 1px solid #ddccae;
}

.user-dropdown-menu.show {
  display: block;
}

.user-dropdown-menu a {
  color: #2b2b2b;
  padding: 12px 16px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
}

.user-dropdown-menu a:hover {
  background-color: #ddceac;
}

.logout-link {
  color: #b3261e !important;
}


/* ================= PAGE & PANEL ================= */

.page-container {
  max-width: 860px;
  margin: 45px auto 65px;
  padding: 0 20px;
  min-height: calc(100vh - 250px);
}

.panel-card {
  background: var(--tan-card);
  border-radius: 20px;
  padding: 35px 40px;
  border: 1px solid var(--border-color);
  box-shadow: 0 10px 30px rgba(0,0,0,0.06);
}

.panel-card h2 {
  margin: 0 0 24px;
  font-size: 24px;
  font-weight: 800;
  color: var(--dark-brown);
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 2px solid var(--border-color);
  padding-bottom: 16px;
}


/* ================= NOTIFICATION ITEM ================= */

.notif-item {
  background: var(--pale-yellow);
  border-radius: 14px;
  padding: 20px 22px;
  border: 1px solid var(--border-color);
  margin-bottom: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.notif-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.04);
}

.notif-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--dark-brown);
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-desc {
  margin: 5px 0 0;
  font-size: 13.5px;
  color: #554436;
  line-height: 1.5;
}

.notif-desc strong {
  color: var(--dark-brown);
}


/* ================= STATUS BADGES ================= */

.status-badge {
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 12.5px;
  font-weight: 700;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.badge-Approved,
.badge-Rescued {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.badge-Pending,
.badge-Dispatched {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.badge-Rejected,
.badge-Closed {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.no-notif-msg {
  color: #665444;
  text-align: center;
  padding: 35px 20px;
  font-size: 14.5px;
  font-weight: 500;
  background: var(--pale-yellow);
  border-radius: 14px;
  border: 1px dashed var(--border-color);
}


/* ================= LOGOUT CONFIRMATION MODAL ================= */

.logout-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.50);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  z-index: 99999;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.logout-modal-overlay.show {
  display: flex;
}

.logout-modal {
  width: 100%;
  max-width: 400px;
  background: #ede1c6;
  border-radius: 16px;
  padding: 32px 28px;
  text-align: center;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.30);
  border: 1px solid rgba(255, 255, 255, 0.6);
  animation: logoutPopup 0.25s ease-out;
}

@keyframes logoutPopup {
  from {
    transform: scale(0.85);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.logout-modal h2 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.logout-modal p {
  font-size: 14px;
  color: #555;
  margin-bottom: 26px;
  line-height: 1.5;
}

.logout-modal-actions {
  display: flex;
  gap: 12px;
}

.logout-cancel,
.logout-confirm {
  flex: 1;
  padding: 13px;
  border-radius: 10px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.logout-cancel {
  background: #ffffff;
  color: #2b2b2b;
  border: 1px solid #ddccae;
}

.logout-cancel:hover {
  background: #f5ecda;
}

.logout-confirm {
  background: #b3261e;
  color: #ffffff;
  border: none;
  box-shadow: 0 4px 12px rgba(179, 38, 30, 0.3);
}

.logout-confirm:hover {
  background: #961e17;
}


/* ================= RESPONSIVE ================= */

@media (max-width: 600px) {

  .panel-card {
    padding: 25px 20px;
  }

  .notif-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .status-badge {
    align-self: flex-start;
  }

}

</style>

</head>

<body>


<!-- HEADER -->
<header class="header">

  <div class="logo">
    <a href="index.php">
      <img src="assets/images/logo.png" alt="PawLix logo">
    </a>
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
      <button
        class="menu-icon-btn"
        id="userMenuBtn"
        onclick="toggleUserDropdown()"
        aria-label="User Menu"
      >
        <span>👤</span> ▾
        <?php if ($unreadCount > 0): ?>
          <span class="badge-count">
            <?php echo $unreadCount; ?>
          </span>
        <?php endif; ?>
      </button>

      <div class="user-dropdown-menu" id="userDropdownMenu">
        <a href="account.php">
          <span class="icon">👤</span> Account
        </a>
        <a href="messages.php">
          <span class="icon">✉️</span> Messages
        </a>
        <a href="notifications.php">
          <span class="icon">🔔</span> Notification
        </a>
        <a href="history.php">
          <span class="icon">📜</span> History
        </a>
        <a href="settings.php">
          <span class="icon">⚙️</span> Setting
        </a>
        <div style="height: 1px; background-color: #ddccae; margin: 4px 0;"></div>
        <a href="#" class="logout-link logout-trigger">
          <span class="icon">🚪</span> Logout
        </a>
      </div>
    </div>
  </div>

  <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
    ☰
  </button>

</header>

<hr style="background-color: white; height: 1px; border: none;">


<!-- MAIN NOTIFICATIONS PANEL -->
<div class="page-container">

  <div class="panel-card">

    <h2>
      🔔 Notifications & Status Alerts
    </h2>

    <?php
    $hasNotif = false;

    /* ================= 1. ADMIN DIRECT MESSAGES NOTIFICATIONS ================= */
    if ($user_messages && mysqli_num_rows($user_messages) > 0) {
      while ($m = mysqli_fetch_assoc($user_messages)) {
        $hasNotif = true;
        $msgSubject = htmlspecialchars(!empty($m['subject']) ? $m['subject'] : 'Shelter Support Message');
        $msgPreview = htmlspecialchars(substr($m['message'], 0, 110)) . (strlen($m['message']) > 110 ? '...' : '');
        $isUnread = ($m['is_read'] == 0);

        echo "<div class='notif-item'>";
        echo "
          <div>
            <div class='notif-title'>
              ✉️ Direct Support Message
            </div>
            <p class='notif-desc'>
              Subject: <strong>".$msgSubject."</strong><br>
              \"".$msgPreview."\"
            </p>
          </div>
        ";
        echo "
          <a href='messages.php' class='status-badge badge-Dispatched' style='text-decoration:none;'>
            ".($isUnread ? 'New Message' : 'View Chat')."
          </a>
        ";
        echo "</div>";
      }
    }

    /* ================= 2. RESCUE REPORT NOTIFICATIONS ================= */
    if ($reports && mysqli_num_rows($reports) > 0) {
      while ($r = mysqli_fetch_assoc($reports)) {
        if ($r['status'] != 'Pending') {
          $hasNotif = true;
          $loc = htmlspecialchars($r['location_area'] ?? 'Reported Location');
          $st = htmlspecialchars($r['status']);

          echo "<div class='notif-item'>";
          echo "
            <div>
              <div class='notif-title'>
                🚨 Rescue Report Update
              </div>
              <p class='notif-desc'>
                Report at
                <strong>".$loc."</strong>
                is now status:
                <strong>".$st."</strong>
              </p>
            </div>
          ";
          echo "
            <span class='status-badge badge-".$st."'>
              ".$st."
            </span>
          ";
          echo "</div>";
        }
      }
    }

    /* ================= 3. ADOPTION APPLICATION NOTIFICATIONS ================= */
    if ($adoptions && mysqli_num_rows($adoptions) > 0) {
      while ($a = mysqli_fetch_assoc($adoptions)) {
        if ($a['status'] != 'Pending') {
          $hasNotif = true;
          $dogName = htmlspecialchars($a['dog_name'] ?? 'Dog');
          $st = htmlspecialchars($a['status']);

          echo "<div class='notif-item'>";
          echo "
            <div>
              <div class='notif-title'>
                🐾 Adoption Request Update
              </div>
              <p class='notif-desc'>
                Your adoption application for
                <strong>".$dogName."</strong>
                has been
                <strong>".$st."</strong>!
              </p>
            </div>
          ";
          echo "
            <span class='status-badge badge-".$st."'>
              ".$st."
            </span>
          ";
          echo "</div>";
        }
      }
    }

    /* ================= NO NOTIFICATIONS ================= */
    if (!$hasNotif) {
      echo "
        <div class='no-notif-msg'>
          No new status updates or support messages at the moment.
          All rescue, adoption, and direct messages will appear here.
        </div>
      ";
    }
    ?>

  </div>

</div>


<!-- LOGOUT CONFIRMATION MODAL -->
<div class="logout-modal-overlay" id="logoutModal">
  <div class="logout-modal">
    <h2>Log Out?</h2>
    <p>Are you sure you want to log out?</p>
    <div class="logout-modal-actions">
      <button type="button" class="logout-cancel" id="cancelLogout">Cancel</button>
      <a href="logout.php?confirm=true" class="logout-confirm">Log Out</a>
    </div>
  </div>
</div>


<!-- FOOTER -->
<?php
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
?>
<footer class="footer">
  <div class="footer-container">
    <div class="footer-columns">
      <div class="footer-col col-brand">
        <h4 class="col-title">PAWLIX</h4>
        <p class="brand-text">
          Connecting dogs waiting for rescue with loving, permanent families across Nepal through a simple and secure platform.
        </p>
      </div>

      <div class="footer-col">
        <h4 class="col-title">SERVICES</h4>
        <p><a href="browse.php">Browse Dogs</a></p>
        <p><a href="adopt.php">Apply for Adoption</a></p>
        <p><a href="report.php">Report Stray / Injured</a></p>
        <p><a href="contact.php">Support</a></p>
      </div>

      <div class="footer-col">
        <h4 class="col-title">USEFUL LINKS</h4>
        <p><a href="index.php">Home</a></p>
        <p><a href="about.php">About Us</a></p>
        <p><a href="contact.php">Contact Us</a></p>
      </div>

      <div class="footer-col col-contact">
        <h4 class="col-title">CONTACT</h4>
        <p><span class="icon">📍</span> Kathmandu, Nepal</p>
        <p><span class="icon">✉</span> support@pawlix.org</p>
        <p><span class="icon">📞</span> +977 9800000000</p>
        <p><span class="icon">🐾</span> Emergency 24/7 Support</p>
      </div>
    </div>

    <hr class="footer-hr">

    <div class="footer-bottom">
      <p class="copyright">
        © <?php echo date('Y'); ?> PawLix. All rights reserved.
      </p>
    </div>
  </div>
</footer>
<?php } ?>


<!-- JAVASCRIPT -->
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

document.addEventListener("DOMContentLoaded", function () {
    const logoutModal = document.getElementById("logoutModal");
    const cancelLogout = document.getElementById("cancelLogout");
    const logoutTriggers = document.querySelectorAll(".logout-trigger");

    logoutTriggers.forEach(function (trigger) {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            logoutModal.classList.add("show");
        });
    });

    if (cancelLogout) {
        cancelLogout.addEventListener("click", function () {
            logoutModal.classList.remove("show");
        });
    }

    if (logoutModal) {
        logoutModal.addEventListener("click", function (e) {
            if (e.target === logoutModal) {
                logoutModal.classList.remove("show");
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && logoutModal) {
            logoutModal.classList.remove("show");
        }
    });
});
</script>

<script src="assets/js/script.js"></script>

</body>

</html>