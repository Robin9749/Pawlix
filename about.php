<?php
session_start();
require_once 'config/config.php';

// Unread Notifications Count
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

<style>
/* Header Menu Icon Dropdown Styles */
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

.dropdown-divider {
  height: 1px;
  background-color: #ddccae;
  margin: 4px 0;
}

.logout-link {
  color: #b3261e !important;
}

.badge-sub {
  margin-left: auto;
  background: #e63946;
  color: white;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 10px;
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
      <a href="browse.php">Browse Dogs ▾</a>
      <a href="about.php" class="active">About</a>
      <a href="contact.php">Contact</a>
      <a href="report.php">Report a Dog</a>
    </nav>

    <!-- Header Action Buttons -->
    <div class="header-buttons">
      <?php if (isset($_SESSION['user_id'])): ?>
        
        <!-- LOGGED IN: MENU ICON DROPDOWN -->
        <div class="user-menu-wrapper">
          <button class="menu-icon-btn" id="userMenuBtn" onclick="toggleUserDropdown()" aria-label="User Menu">
            <span>👤</span> ▾ <?php if ($unreadCount > 0): ?><span class="badge-count"><?php echo $unreadCount; ?></span><?php endif; ?>
          </button>

          <div class="user-dropdown-menu" id="userDropdownMenu">
            <a href="account.php"><span class="icon">👤</span> Account</a>
            <a href="messages.php"><span class="icon">✉️</span> Messages</a>
            <a href="notifications.php"><span class="icon">🔔</span> Notification <?php if ($unreadCount > 0): ?><span class="badge-sub"><?php echo $unreadCount; ?></span><?php endif; ?></a>
            <a href="history.php"><span class="icon">📜</span> History</a>
            <a href="settings.php"><span class="icon">⚙️</span> Setting</a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="logout-link"><span class="icon">🚪</span> Logout</a>
          </div>
        </div>

      <?php else: ?>

        <!-- LOGGED OUT: LOGIN & SIGNUP -->
        <a href="signup.php" class="btn btn-outline" style="text-decoration:none;">Sign Up</a>
        <a href="login.php" class="btn btn-dark" style="text-decoration:none;">Login</a>

      <?php endif; ?>
    </div>

    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
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
      <h2>Why Choose LucyPaw?</h2>
      
      <div class="why-grid">
        <div class="why-card">
          <div class="why-icon icon-yellow">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM6.5 5C7.6 5 8.5 5.9 8.5 7C8.5 8.1 7.6 9 6.5 9C5.4 9 4.5 8.1 4.5 7C4.5 5.9 5.4 5 6.5 5ZM17.5 5C18.6 5 19.5 5.9 19.5 7C19.5 8.1 18.6 9 17.5 9C16.4 9 15.5 8.1 15.5 7C15.5 5.9 16.4 5 17.5 5ZM4 11C4.8 11 5.5 11.7 5.5 12.5C5.5 13.3 4.8 14 4 14C3.2 14 2.5 13.3 2.5 12.5C2.5 11.7 3.2 11 4 11ZM20 11C20.8 11 21.5 11.7 21.5 12.5C21.5 13.3 20.8 14 20 14C19.2 14 18.5 13.3 18.5 12.5C18.5 11.7 19.2 11 20 11ZM12 8.5C14.8 8.5 17.5 10.5 17.5 14C17.5 17.5 15.2 21 12 21C8.8 21 6.5 17.5 6.5 14C6.5 10.5 9.2 8.5 12 8.5Z"/></svg>
          </div>
          <div class="why-info">
            <h3>Browse Available Dogs</h3>
            <p>Explore detailed profiles of dogs looking for a forever home.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-pink">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          </div>
          <div class="why-info">
            <h3>Simple Adoption</h3>
            <p>Submit adoption requests in a few easy steps.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-blue">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
          </div>
          <div class="why-info">
            <h3>Track your application</h3>
            <p>Check the status of your adoption request anytime.</p>
          </div>
        </div>

        <div class="why-card">
          <div class="why-icon icon-green">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 6h2v2h-2V7zm0 4h2v6h-2v-6z"/></svg>
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
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          </div>
          <span class="step-num">Step 1</span>
          <h4 class="step-title">Create Account</h4>
        </div>

        <div class="flow-arrow">➔</div>

        <div class="flow-step">
          <div class="flow-circle circle-orange">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          </div>
          <span class="step-num">Step 2</span>
          <h4 class="step-title">Browse Dogs</h4>
        </div>

        <div class="flow-arrow">➔</div>

        <div class="flow-step">
          <div class="flow-circle circle-blue">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
          </div>
          <span class="step-num">Step 3</span>
          <h4 class="step-title">Submit Request</h4>
        </div>

        <div class="flow-arrow">➔</div>

        <div class="flow-step">
          <div class="flow-circle circle-purple">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
          </div>
          <span class="step-num">Step 4</span>
          <h4 class="step-title">Admin Review</h4>
        </div>

        <div class="flow-arrow">➔</div>

        <div class="flow-step">
          <div class="flow-circle circle-lightgreen">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          </div>
          <span class="step-num">Step 5</span>
          <h4 class="step-title">Adoption Complete</h4>
        </div>
      </div>
    </section>

  </main>

 <footer class="footer">
    <div class="footer-container">
        
        <!-- 4 Columns Grid -->
        <div class="footer-columns">
            
            <!-- Column 1: PawLix -->
            <div class="footer-col col-brand">
                <h4 class="col-title">PAWLIX</h4>
                <p class="brand-text">
                    Connecting dogs waiting for rescue with loving, permanent families across Nepal through a simple and secure platform.
                </p>
            </div>

            <!-- Column 2: Services -->
            <div class="footer-col">
                <h4 class="col-title">SERVICES</h4>
                <p><a href="browse.php">Browse Dogs</a></p>
                <p><a href="adopt.php">Apply for Adoption</a></p>
                <p><a href="report.php">Report Stray / Injured</a></p>
                <p><a href="contact.php">Support</a></p>
            </div>

            <!-- Column 3: Useful Links -->
            <div class="footer-col">
                <h4 class="col-title">USEFUL LINKS</h4>
                <p><a href="index.php">Home</a></p>
                <p><a href="about.php">About Us</a></p>
                <p><a href="contact.php">Contact Us</a></p>
        
            </div>

            <!-- Column 4: Contact -->
            <div class="footer-col col-contact">
                <h4 class="col-title">CONTACT</h4>
                <p><span class="icon">📍</span> Kathmandu, Nepal</p>
                <p><span class="icon">✉</span> support@pawlix.org</p>
                <p><span class="icon">📞</span> +977 9800000000</p>
                <p><span class="icon">🐾</span> Emergency 24/7 Support</p>
            </div>

        </div>

        <!-- Thin Horizontal Line -->
        <hr class="footer-hr">

        <!-- Footer Bottom Bar -->
        <div class="footer-bottom">
            <p class="copyright">© <?php echo date('Y'); ?> PawLix. All rights reserved.</p>

            <div class="footer-bottom-right">
                <!-- Social Circle Buttons -->
                <div class="socials">
                    <a href="#" aria-label="Facebook"><span>f</span></a>
                    <a href="#" aria-label="X"><span>𝕏</span></a>
                    <a href="#" aria-label="Instagram"><span>◎</span></a>
                    <a href="#" aria-label="YouTube"><span>▶</span></a>
                </div>

                <!-- Call To Action Button (Back to Top) -->
                <button class="scroll-top-btn" id="scrollTopBtn" type="button" aria-label="Back to top">
                    <span>↑</span> Back to Top
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
  });
  </script>
  <script src="assets/js/script.js"></script>
</body>
</html>