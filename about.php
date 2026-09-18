<?php
session_start();
require_once 'config/config.php';

$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    
    $r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $uid AND status != 'Pending'");
    $r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $uid AND status != 'Pending'");
    
    $c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
    $c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
    
    $unreadCount = $c1 + $c2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PawLix - About Us</title>
<link rel="stylesheet" href="assets/css/about.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

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
  margin-left: 2px;
}

.user-dropdown-menu {
  display: none;
  position: absolute;
  right: 0;
  top: 48px;
  background-color: #ede1c6;
  min-width: 200px;
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
  transition: background 0.2s;
}

.user-dropdown-menu a:hover {
  background-color: #ddceac;
}

.user-dropdown-menu a .icon {
  font-size: 16px;
  width: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.dropdown-divider {
  height: 1px;
  background-color: #ddccae;
  margin: 4px 0;
}

.logout-link {
  color: #b3261e !important;
}

.logout-link:hover {
  background-color: #f8d7da !important;
}

.badge-sub {
  margin-left: auto;
  background: #e63946;
  color: white;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 10px;
}

.why-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}

.flow-circle {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

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
    box-shadow: 0 15px 35px rgba(0,0,0,0.30);
    border: 1px solid rgba(255,255,255,0.6);
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
    box-shadow: 0 4px 12px rgba(179,38,30,0.3);
}

.logout-confirm:hover {
    background: #961e17;
}

.socials a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.col-contact .icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
}
</style>
</head>
<body>

  <header class="header">
    <div class="logo">
      <img src="assets/images/logo.png" alt="PawLix logo">
    </div>
    <nav class="nav">
      <a href="index.php">Home</a>
      <a href="browse.php">Browse Dogs <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 2px;"></i></a>
      <a href="about.php" class="active">About</a>
      <a href="contact.php">Contact</a>
      <a href="report.php">Report a Dog</a>
    </nav>

    <div class="header-buttons">
      <?php if (isset($_SESSION['user_id'])): ?>
        
        <div class="user-menu-wrapper">
          <button class="menu-icon-btn" id="userMenuBtn" onclick="toggleUserDropdown()" aria-label="User Menu">
            <i class="fa-solid fa-user"></i>
            <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
            <?php if ($unreadCount > 0): ?><span class="badge-count"><?php echo $unreadCount; ?></span><?php endif; ?>
          </button>

          <div class="user-dropdown-menu" id="userDropdownMenu">
            <a href="account.php">
              <span class="icon"><i class="fa-solid fa-user"></i></span> Account
            </a>
            <a href="messages.php">
              <span class="icon"><i class="fa-solid fa-envelope"></i></span> Messages
            </a>
            <a href="notifications.php">
              <span class="icon"><i class="fa-solid fa-bell"></i></span> Notification <?php if ($unreadCount > 0): ?><span class="badge-sub"><?php echo $unreadCount; ?></span><?php endif; ?>
            </a>
            <a href="history.php">
              <span class="icon"><i class="fa-solid fa-clock-rotate-left"></i></span> History
            </a>
            <a href="settings.php">
              <span class="icon"><i class="fa-solid fa-gear"></i></span> Setting
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="logout-trigger logout-link">
              <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span> Logout
            </a>
          </div>
        </div>

      <?php else: ?>

        <a href="signup.php" class="btn btn-outline" style="text-decoration:none;">Sign Up</a>
        <a href="login.php" class="btn btn-dark" style="text-decoration:none;">Login</a>

      <?php endif; ?>
    </div>

    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
  </header>

<hr style="background-color: white; height: 1px; border: none;">

  <section class="about-hero">
    <div class="about-hero-content">
      <div class="about-hero-text">
        <h1>About Our PawLix</h1>
        <p>Connecting dogs with caring families through a simple, secure, and organized adoption platform.</p>
        <a href="browse.php" class="btn-hero-browse">Browse Dogs</a>
      </div>
      <div class="about-hero-image">
        <img src="assets/images/aboutHero.png" alt="Cute dog in sweater">
      </div>
    </div>
  </section>

  <main class="about-main">
    
    <section class="story-section">
      <div class="story-container">
        <div class="story-image">
          <img src="assets/images/ourStory.png" alt="Woman hugging golden retriever">
        </div>
        <div class="story-text">
          <h2>Our Story</h2>
          <p>PawLix was created with the goal of making dog adoption more accessible and organized. Many people struggle to find reliable information about dogs available for adoption, while administrators often face difficulties managing adoption records manually.</p>
          <p>Our platform brings everything together in one place. Users can browse available dogs, view detailed information, and submit adoption requests online. At the same time, administrators can manage dog records, review applications, and keep adoption information updated through a secure management system.</p>
          <p>By simplifying each step of the process, PawLix aims to create a better adoption experience for both adopters and administrators while helping more dogs find caring and permanent homes.</p>
        </div>
      </div>
    </section>

    <section class="mission-vision-section">
      <div class="mission-vision-card">
        <div class="mv-block">
          <div class="mv-img">
            <img src="assets/images/MissionDog.png" alt="Mission puppy">
          </div>
          <div class="mv-text">
            <h3>Our Mission</h3>
            <p>To simplify the dog adoption process by providing an easy-to-use online platform that connects caring adopters with dogs waiting for a loving home.</p>
          </div>
        </div>

        <div class="mv-divider"></div>

        <div class="mv-block">
          <div class="mv-text">
            <h3>Our Vision</h3>
            <p>To build a community where every dog has the opportunity to find a safe, caring, and permanent family.</p>
          </div>
          <div class="mv-img">
            <img src="assets/images/VisionDog.png" alt="Vision puppy">
          </div>
        </div>
      </div>
    </section>

    <section class="why-choose-section">
      <h2>Why Choose PawLix?</h2>
      
      <div class="why-grid">
        <div class="why-card">
          <div class="why-icon icon-yellow">
            <i class="fa-solid fa-paw"></i>
          </div>
          <div class="why-info">
            <h3>Browse Available Dogs</h3>
            <p>Explore detailed profiles of dogs looking for a forever home.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-pink">
            <i class="fa-solid fa-heart"></i>
          </div>
          <div class="why-info">
            <h3>Simple Adoption</h3>
            <p>Submit adoption requests in a few easy steps.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-blue">
            <i class="fa-solid fa-clipboard-check"></i>
          </div>
          <div class="why-info">
            <h3>Track your application</h3>
            <p>Check the status of your adoption request anytime.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-green">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div class="why-info">
            <h3>Safe & Secure</h3>
            <p>Your information is protected with secure user authentication.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="how-works-section">
      <h2>How Adoption Works</h2>

      <div class="steps-flow">
        <div class="flow-step">
          <div class="flow-circle circle-green">
            <i class="fa-solid fa-user-plus"></i>
          </div>
          <span class="step-num">Step 1</span>
          <h4 class="step-title">Create Account</h4>
        </div>

        <div class="flow-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="flow-step">
          <div class="flow-circle circle-orange">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>
          <span class="step-num">Step 2</span>
          <h4 class="step-title">Browse Dogs</h4>
        </div>

        <div class="flow-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="flow-step">
          <div class="flow-circle circle-blue">
            <i class="fa-solid fa-paper-plane"></i>
          </div>
          <span class="step-num">Step 3</span>
          <h4 class="step-title">Submit Request</h4>
        </div>

        <div class="flow-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="flow-step">
          <div class="flow-circle circle-purple">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <span class="step-num">Step 4</span>
          <h4 class="step-title">Admin Review</h4>
        </div>

        <div class="flow-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="flow-step">
          <div class="flow-circle circle-lightgreen">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <span class="step-num">Step 5</span>
          <h4 class="step-title">Adoption Complete</h4>
        </div>
      </div>
    </section>

  </main>

<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
        <h2 id="logoutTitle">Log Out?</h2>
        <p>Are you sure you want to log out?</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-cancel" id="cancelLogout">Cancel</button>
            <a href="logout.php?confirm=true" class="logout-confirm">Log Out</a>
        </div>
    </div>
</div>

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
                <p><span class="icon"><i class="fa-solid fa-location-dot"></i></span> Kathmandu, Nepal</p>
                <p><span class="icon"><i class="fa-solid fa-envelope"></i></span> support@pawlix.org</p>
                <p><span class="icon"><i class="fa-solid fa-phone"></i></span> +977 9800000000</p>
                <p><span class="icon"><i class="fa-solid fa-paw"></i></span> Emergency 24/7 Support</p>
            </div>

        </div>

        <hr class="footer-hr">

        <div class="footer-bottom">
            <p class="copyright">© <?php echo date('Y'); ?> PawLix. All rights reserved.</p>

            <div class="footer-bottom-right">
                <div class="socials">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>

                <button class="scroll-top-btn" id="scrollTopBtn" type="button" aria-label="Back to top">
                    <i class="fa-solid fa-arrow-up"></i> Back to Top
                </button>
            </div>
        </div>

    </div>
</footer>

  <script>
  function toggleUserDropdown() {
    var menu = document.getElementById("userDropdownMenu");
    if (menu) {
      menu.classList.toggle("show");
    }
  }

  window.addEventListener('click', function(e) {
    var btn = document.getElementById('userMenuBtn');
    var menu = document.getElementById('userDropdownMenu');
    if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('show');
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
      const btn = document.getElementById('scrollTopBtn');
      if (btn) {
          btn.addEventListener('click', function() {
              window.scrollTo({ top: 0, behavior: 'smooth' });
          });
      }

      const logoutModal = document.getElementById("logoutModal");
      const cancelLogout = document.getElementById("cancelLogout");
      const logoutTrigger = document.querySelector(".logout-trigger");

      if (logoutTrigger) {
          logoutTrigger.addEventListener("click", function(e) {
              e.preventDefault();
              logoutModal.classList.add("show");
              const dropdown = document.getElementById("userDropdownMenu");
              if (dropdown) {
                  dropdown.classList.remove("show");
              }
          });
      }

      if (cancelLogout) {
          cancelLogout.addEventListener("click", function() {
              logoutModal.classList.remove("show");
          });
      }

      if (logoutModal) {
          logoutModal.addEventListener("click", function(e) {
              if (e.target === logoutModal) {
                  logoutModal.classList.remove("show");
              }
          });
      }

      document.addEventListener("keydown", function(e) {
          if (e.key === "Escape" && logoutModal && logoutModal.classList.contains("show")) {
              logoutModal.classList.remove("show");
          }
      });
  });
  </script>
  <script src="assets/js/script.js"></script>
</body>
</html>