<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);
$user_query = mysqli_query($conn, "SELECT * FROM user WHERE user_id=$user_id");
$user = ($user_query) ? mysqli_fetch_assoc($user_query) : [];

$unreadCount = 0;
$r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $user_id AND status != 'Pending'");
$r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $user_id AND status != 'Pending'");
$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
$unreadCount = $c1 + $c2;

$first_name = htmlspecialchars($user['first_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages | PawLix</title>
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

/* User Menu Header Dropdown */
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
  font-size: 11px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 12px;
  margin-left: 2px;
}

.user-dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 50px;
  background-color: var(--cream);
  min-width: 210px;
  box-shadow: 0px 10px 25px rgba(0,0,0,0.15);
  border-radius: 14px;
  overflow: hidden;
  z-index: 1000;
  border: 1px solid var(--border-color);
}

.user-dropdown-menu.show {
  display: block;
}

.user-dropdown-menu a {
  color: var(--dark-brown);
  padding: 12px 18px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  font-weight: 600;
  transition: background 0.2s ease, color 0.2s ease;
}

.user-dropdown-menu a:hover {
  background-color: #dfcfb0;
  color: var(--maroon);
}

.user-dropdown-menu a .icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.user-dropdown-menu .divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 4px 0;
}

.user-dropdown-menu a.logout-link {
  color: var(--maroon);
}

.user-dropdown-menu a.logout-link:hover {
  background-color: #f8d7da;
}


/* =====================================================
   LOGOUT CONFIRMATION MODAL
   ===================================================== */

.logout-modal-overlay {
  display: none;

  position: fixed;
  inset: 0;

  width: 100%;
  height: 100%;

  background: rgba(0, 0, 0, 0.50);

  /* Blur the actual current page */
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
  color: #555555;
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


/* Messages Wrapper Container */
.messages-wrapper {
  min-height: calc(100vh - 250px);
  padding: 50px 20px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.messages-card {
  max-width: 820px;
  width: 100%;
  background: var(--tan-card);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.06);
  border: 1px solid var(--border-color);
}

.messages-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  padding-bottom: 20px;
  border-bottom: 2px solid var(--border-color);
}

.messages-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  color: var(--dark-brown);
  display: flex;
  align-items: center;
  gap: 10px;
}

.unread-badge-pill {
  background: var(--orange);
  color: #fff;
  font-size: 12.5px;
  font-weight: 700;
  padding: 4px 14px;
  border-radius: 20px;
}

/* Single Message Card */
.msg-item {
  background: var(--pale-yellow);
  padding: 22px;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  margin-bottom: 18px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.msg-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.04);
}

.msg-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.msg-sender {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 15px;
  color: var(--dark-brown);
}

.msg-sender-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--dark-brown);
  color: var(--cream);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.msg-time {
  font-size: 12.5px;
  color: #8c735d;
  font-weight: 500;
}

.msg-title {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--dark-brown);
}

.msg-body {
  margin: 0;
  font-size: 14px;
  color: #4a3c31;
  line-height: 1.6;
}

/* Actions */
.action-bar {
  margin-top: 30px;
  display: flex;
  justify-content: flex-start;
  gap: 14px;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 26px;
  border-radius: 50px;
  font-size: 14.5px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  background: var(--dark-brown);
  color: #fff;
  box-shadow: 0 4px 12px rgba(74, 50, 35, 0.2);
}

.btn-action:hover {
  background: var(--orange);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 127, 17, 0.35);
}

@media (max-width: 600px) {

  .messages-card {
    padding: 25px 20px;
  }

  .messages-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .action-bar {
    flex-direction: column;
  }

  .btn-action {
    width: 100%;
    justify-content: center;
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
          aria-label="User Menu">

          <span>👤</span> ▾

          <?php if ($unreadCount > 0): ?>

            <span class="badge-count">
              <?php echo $unreadCount; ?>
            </span>

          <?php endif; ?>

        </button>


        <div
          class="user-dropdown-menu"
          id="userDropdownMenu">

          <a href="account.php">
            <span class="icon">👤</span>
            Account Profile
          </a>

          <a href="messages.php">
            <span class="icon">✉️</span>
            Messages

            <?php if ($unreadCount > 0): ?>

              <span class="badge-count">
                <?php echo $unreadCount; ?>
              </span>

            <?php endif; ?>

          </a>

          <a href="notifications.php">
            <span class="icon">🔔</span>
            Notifications
          </a>

          <a href="history.php">
            <span class="icon">📜</span>
            Adoption History
          </a>

          <a href="settings.php">
            <span class="icon">⚙️</span>
            Settings
          </a>

          <div class="divider"></div>

          <!-- CHANGED: Logout no longer redirects immediately -->
          <a
            href="#"
            class="logout-link logout-trigger">

            <span class="icon">🚪</span>
            Logout

          </a>

        </div>

      </div>

    </div>

    <button
      class="menu-toggle"
      id="menuToggle"
      aria-label="Toggle menu">

      ☰

    </button>

  </header>


  <hr style="background-color: white; height: 1px; border: none;">


  <!-- MAIN CONTENT -->
  <main class="messages-wrapper">

    <div class="messages-card">

      <!-- Header -->
      <div class="messages-header">

        <h2>
          ✉️ Messages & Updates
        </h2>

        <?php if ($unreadCount > 0): ?>

          <span class="unread-badge-pill">
            <?php echo $unreadCount; ?> New Updates
          </span>

        <?php else: ?>

          <span
            class="unread-badge-pill"
            style="background:#4a3223;">

            All Messages Read

          </span>

        <?php endif; ?>

      </div>


      <!-- Messages List -->
      <div class="msg-item">

        <div class="msg-top-bar">

          <div class="msg-sender">

            <span class="msg-sender-icon">
              🐾
            </span>

            PawLix Shelter Team

          </div>

          <span class="msg-time">
            Just now
          </span>

        </div>


        <h4 class="msg-title">
          Welcome to PawLix Shelter Community!
        </h4>

        <p class="msg-body">
          Thank you for caring for animals across Nepal.
          Direct responses, rescue updates, and adoption
          application notes from PawLix shelter administrators
          will appear here.
        </p>

      </div>


      <!-- Action Button -->
      <div class="action-bar">

        <a
          href="index.php"
          class="btn-action">

          <span>🏠</span>
          Back to Home

        </a>

        <a
          href="browse.php"
          class="btn-action"
          style="background:var(--orange);">

          <span>🐶</span>
          Browse Available Dogs

        </a>

      </div>

    </div>

  </main>


  <!-- =====================================================
       LOGOUT CONFIRMATION MODAL
       ===================================================== -->

  <div
    class="logout-modal-overlay"
    id="logoutModal">

    <div
      class="logout-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="logoutTitle">

      <h2 id="logoutTitle">
        Log Out?
      </h2>

      <p>
        Are you sure you want to log out?
      </p>

      <div class="logout-modal-actions">

        <button
          type="button"
          class="logout-cancel"
          id="cancelLogout">

          Cancel

        </button>

        <a
          href="logout.php?confirm=true"
          class="logout-confirm">

          Log Out

        </a>

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

                  <h4 class="col-title">
                    PAWLIX
                  </h4>

                  <p class="brand-text">
                    Connecting dogs waiting for rescue with loving,
                    permanent families across Nepal through a simple
                    and secure platform.
                  </p>

              </div>


              <div class="footer-col">

                  <h4 class="col-title">
                    SERVICES
                  </h4>

                  <p>
                    <a href="browse.php">
                      Browse Dogs
                    </a>
                  </p>

                  <p>
                    <a href="adopt.php">
                      Apply for Adoption
                    </a>
                  </p>

                  <p>
                    <a href="report.php">
                      Report Stray / Injured
                    </a>
                  </p>

                  <p>
                    <a href="contact.php">
                      Support
                    </a>
                  </p>

              </div>


              <div class="footer-col">

                  <h4 class="col-title">
                    USEFUL LINKS
                  </h4>

                  <p>
                    <a href="index.php">
                      Home
                    </a>
                  </p>

                  <p>
                    <a href="about.php">
                      About Us
                    </a>
                  </p>

                  <p>
                    <a href="contact.php">
                      Contact Us
                    </a>
                  </p>

              </div>


              <div class="footer-col col-contact">

                  <h4 class="col-title">
                    CONTACT
                  </h4>

                  <p>
                    <span class="icon">📍</span>
                    Kathmandu, Nepal
                  </p>

                  <p>
                    <span class="icon">✉</span>
                    support@pawlix.org
                  </p>

                  <p>
                    <span class="icon">📞</span>
                    +977 9800000000
                  </p>

              </div>

          </div>


          <hr class="footer-hr">


          <div class="footer-bottom">

              <p class="copyright">
                © <?php echo date('Y'); ?> PawLix.
                All rights reserved.
              </p>

          </div>

      </div>

  </footer>

  <?php } ?>


  <!-- ================= JAVASCRIPT ================= -->

  <script>

  function toggleUserDropdown() {

    var menu =
      document.getElementById("userDropdownMenu");

    if (menu) {

      menu.classList.toggle("show");

    }

  }


  /* Close dropdown when clicking outside */

  window.addEventListener('click', function(e) {

    var btn =
      document.getElementById('userMenuBtn');

    var menu =
      document.getElementById('userDropdownMenu');

    if (
      menu &&
      btn &&
      !btn.contains(e.target) &&
      !menu.contains(e.target)
    ) {

      menu.classList.remove('show');

    }

  });


  /* =====================================================
     LOGOUT MODAL
     ===================================================== */

  document.addEventListener("DOMContentLoaded", function() {

    const logoutModal =
      document.getElementById("logoutModal");

    const cancelLogout =
      document.getElementById("cancelLogout");

    const logoutTrigger =
      document.querySelector(".logout-trigger");


    /* Open logout popup */

    if (logoutTrigger) {

      logoutTrigger.addEventListener("click", function(e) {

        e.preventDefault();

        logoutModal.classList.add("show");


        /*
         * Close the user dropdown
         * before showing the modal.
         */

        const dropdown =
          document.getElementById("userDropdownMenu");

        if (dropdown) {

          dropdown.classList.remove("show");

        }

      });

    }


    /* Cancel logout */

    if (cancelLogout) {

      cancelLogout.addEventListener("click", function() {

        logoutModal.classList.remove("show");

      });

    }


    /* Click outside modal */

    if (logoutModal) {

      logoutModal.addEventListener("click", function(e) {

        if (e.target === logoutModal) {

          logoutModal.classList.remove("show");

        }

      });

    }


    /* ESC key */

    document.addEventListener("keydown", function(e) {

      if (
        e.key === "Escape" &&
        logoutModal &&
        logoutModal.classList.contains("show")
      ) {

        logoutModal.classList.remove("show");

      }

    });

  });

  </script>


  <script src="assets/js/script.js"></script>

</body>
</html>