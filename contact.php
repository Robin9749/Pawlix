<?php
session_start();
require_once "config/config.php";

/* ================= SAFE HELPER TO ENSURE TABLE COLUMNS EXIST ================= */
if (!function_exists('safeAddColumnContact')) {
    function safeAddColumnContact($conn, $table, $column, $definition) {
        try {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {}
    }
}

/* AUTO-CREATE OR ALTER TABLES IF MISSING COLUMNS */
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

safeAddColumnContact($conn, 'contact_message', 'phone', "VARCHAR(50) DEFAULT ''");
safeAddColumnContact($conn, 'contact_message', 'subject', "VARCHAR(255) DEFAULT ''");

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

/* ================= UNREAD NOTIFICATIONS COUNT ================= */
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    
    $r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $uid AND status != 'Pending'");
    $r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $uid AND status != 'Pending'");
    $r3 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE user_id = $uid AND sender_type = 'admin' AND is_read = 0");
    
    $c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
    $c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
    $c3 = ($r3) ? mysqli_fetch_assoc($r3)['total'] : 0;
    
    $unreadCount = $c1 + $c2 + $c3;
}

$success = "";
$error = "";

$full_name = "";
$email     = "";
$phone     = "";
$subject   = "";
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {

    $full_name = trim($_POST['userName'] ?? '');
    $email     = trim($_POST['userEmail'] ?? '');
    $phone     = trim($_POST['userPhone'] ?? '');
    $subject   = trim($_POST['userSubject'] ?? 'PawLix Contact Inquiry');
    $message   = trim($_POST['userMessage'] ?? '');

    if ($full_name === '' || $email === '' || $subject === '' || $message === '') {
        $error = "Please fill in your name, email, subject, and message.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $fn_clean = mysqli_real_escape_string($conn, $full_name);
        $em_clean = mysqli_real_escape_string($conn, $email);
        $ph_clean = mysqli_real_escape_string($conn, $phone);
        $sb_clean = mysqli_real_escape_string($conn, $subject);
        $ms_clean = mysqli_real_escape_string($conn, $message);

        // 1. SAVE TO contact_message TABLE SAFELY
        try {
            @mysqli_query($conn, "INSERT INTO contact_message (full_name, email, phone, subject, message) 
                VALUES ('$fn_clean', '$em_clean', '$ph_clean', '$sb_clean', '$ms_clean')");
        } catch (Throwable $t) {
            // Fallback if phone column isn't accessible
            @mysqli_query($conn, "INSERT INTO contact_message (full_name, email, subject, message) 
                VALUES ('$fn_clean', '$em_clean', '$sb_clean', '$ms_clean')");
        }

        // 2. CHECK IF USER IS LOGGED IN OR REGISTERED BY EMAIL
        $target_user_id = 0;
        if (isset($_SESSION['user_id'])) {
            $target_user_id = intval($_SESSION['user_id']);
        } else {
            $check_user = mysqli_query($conn, "SELECT user_id FROM user WHERE email = '$em_clean' LIMIT 1");
            if ($check_user && mysqli_num_rows($check_user) > 0) {
                $u_row = mysqli_fetch_assoc($check_user);
                $target_user_id = intval($u_row['user_id']);
            }
        }

        // 3. IF USER ACCOUNT EXISTS -> INSERT INTO LIVE CHAT MESSAGES TABLE
        if ($target_user_id > 0) {
            try {
                @mysqli_query($conn, "INSERT INTO messages (user_id, admin_id, sender_type, subject, message, is_read) 
                    VALUES ($target_user_id, 1, 'user', '$sb_clean', '$ms_clean', 0)");
            } catch (Throwable $t) {}

            if (isset($_SESSION['user_id'])) {
                header("Location: messages.php?sent=1");
                exit();
            }
        }

        $success = "Thanks, " . htmlspecialchars($full_name) . "! Your message has been sent — our team will get back to you soon.";
        $full_name = $email = $phone = $subject = $message = "";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawLix - Contact Us</title>
    <link rel="stylesheet" href="assets/css/contact.css">
    <style>
        .form-alert{ padding:14px 18px; border-radius:10px; font-size:14px; font-weight:600; margin-bottom:20px; }
        .form-alert-error{ background:#fdeaea; border:1px solid #f3c6c6; color:#b3261e; }
        .form-alert-success{ background:#e5f6e8; border:1px solid #bfe3c4; color:#1e6e2e; }

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
            <a href="index.php"><img src="assets/images/logo.png" alt="PawLix logo"></a>
        </div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="browse.php">Browse Dogs ▾</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
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
                <a href="messages.php"><span class="icon">✉️</span> Messages <?php if ($unreadCount > 0): ?><span class="badge-sub"><?php echo $unreadCount; ?></span><?php endif; ?></a>
                <a href="notifications.php"><span class="icon">🔔</span> Notification</a>
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

    <!-- Contact Hero Banner -->
    <section class="contact-hero">
        <div class="contact-hero-content">
            <div class="contact-hero-text">
                <span class="sub-kicker">CONTACT US</span>
                <h1>We're Here for You and <span>Your Future Friend</span></h1>
                <p>Have questions about dog adoption or need assistance? Whether you're looking for information about a dog, your adoption request, or using our platform, our team is here to help every step of the way.</p>
                <div class="contact-hero-buttons">
                    <a href="#contactForm" class="btn-hero-send">✉ Send Message</a>
                    <a href="browse.php" class="btn-hero-browse-paw">🐾 Browse Dogs</a>
                </div>
            </div>
            <div class="contact-hero-image">
                <img src="assets/images/ContactHero.png" alt="Woman with happy dog">
            </div>
        </div>
    </section>

    <!-- Main Contact Section -->
    <main class="contact-main">
        <div class="contact-grid">
            
            <!-- Left Info Card -->
            <aside class="contact-info-card">
                <h2>Get In Touch</h2>
                <p class="info-subtitle">We'd love to hear from you. Whether you have questions about available dogs, the adoption process, or your application status, our team is always ready to assist.</p>

                <div class="info-items">
                    <div class="info-item">
                        <div class="info-icon">📍</div>
                        <div class="info-details">
                            <h4>Address</h4>
                            <p>Asian College of Higher Studies<br>Kathmandu, Nepal</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div class="info-details">
                            <h4>Phone</h4>
                            <p>+977-98XXXXXXXX</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">✉</div>
                        <div class="info-details">
                            <h4>Email</h4>
                            <p>support@pawlix.org</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Right Form Card -->
            <section class="contact-form-card" id="contactForm">
                <h2>Send Us a Message</h2>

                <?php if ($success !== ""): ?>
                    <div class="form-alert form-alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="form-alert form-alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form class="contact-form" action="contact.php#contactForm" method="POST">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="userName">Your Name</label>
                            <input type="text" id="userName" name="userName" placeholder="Your Name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="userEmail">Email Address</label>
                            <input type="email" id="userEmail" name="userEmail" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="userPhone">Phone Number</label>
                            <input type="tel" id="userPhone" name="userPhone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        <div class="form-group">
                            <label for="userSubject">Subject</label>
                            <input type="text" id="userSubject" name="userSubject" placeholder="Subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="userMessage">Your Message</label>
                        <textarea id="userMessage" name="userMessage" rows="6" placeholder="Type your message here ..." required><?php echo htmlspecialchars($message); ?></textarea>
                    </div>

                    <button type="submit" name="send_message" class="btn-send-submit">Send Message</button>
                </form>
            </section>

        </div>
    </main>

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
                <p class="copyright">© <?php echo date('Y'); ?> PawLix. All rights reserved.</p>

                <div class="footer-bottom-right">
                    <div class="socials">
                        <a href="#" aria-label="Facebook"><span>f</span></a>
                        <a href="#" aria-label="X"><span>𝕏</span></a>
                        <a href="#" aria-label="Instagram"><span>◎</span></a>
                        <a href="#" aria-label="YouTube"><span>▶</span></a>
                    </div>

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