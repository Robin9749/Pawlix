<?php
session_start();
require_once "config/config.php";

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

    $email_regex = "/^[a-zA-Z](?=.*[0-9])[a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    $name_regex  = "/^[A-Za-z\s]+$/";
    $phone_regex = "/^\d{10}$/";

    if ($full_name === '' || $email === '' || $subject === '' || $message === '') {
        $error = "Please fill in all required fields (Name, Email, Subject, and Message).";
    } elseif (!preg_match($name_regex, $full_name)) {
        $error = "Name must contain only alphabets and spaces!";
    } elseif (!preg_match($email_regex, $email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address (must start with a letter and contain numbers, e.g., user123@gmail.com, user123@yahoo.com, user123@achsnp.edu.np).";
    } elseif (!empty($phone) && !preg_match($phone_regex, $phone)) {
        $error = "Phone number must be exactly 10 digits!";
    } else {
        $fn_clean = mysqli_real_escape_string($conn, $full_name);
        $em_clean = mysqli_real_escape_string($conn, $email);
        $ph_clean = mysqli_real_escape_string($conn, $phone);
        $sb_clean = mysqli_real_escape_string($conn, $subject);
        $ms_clean = mysqli_real_escape_string($conn, $message);

        try {
            @mysqli_query($conn, "INSERT INTO contact_message (full_name, email, phone, subject, message) 
                VALUES ('$fn_clean', '$em_clean', '$ph_clean', '$sb_clean', '$ms_clean')");
        } catch (Throwable $t) {
            @mysqli_query($conn, "INSERT INTO contact_message (full_name, email, subject, message) 
                VALUES ('$fn_clean', '$em_clean', '$sb_clean', '$ms_clean')");
        }

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        .form-alert{ padding:14px 18px; border-radius:10px; font-size:14px; font-weight:600; margin-bottom:20px; }
        .form-alert-error{ background:#fdeaea; border:1px solid #f3c6c6; color:#b3261e; }
        .form-alert-success{ background:#e5f6e8; border:1px solid #bfe3c4; color:#1e6e2e; }

        /* LIVE ERROR MESSAGES STYLING */
        .input-error-msg {
            display: none;
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 2px;
            text-align: left;
            font-weight: 500;
        }
        .input-error-msg.invalid {
            display: block;
            color: #b3261e;
        }

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

        .col-contact .icon,
        .info-item .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            <a href="browse.php">Browse Dogs <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 2px;"></i></a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
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
                  <span class="icon"><i class="fa-solid fa-envelope"></i></span> Messages <?php if ($unreadCount > 0): ?><span class="badge-sub"><?php echo $unreadCount; ?></span><?php endif; ?>
                </a>
                <a href="notifications.php">
                  <span class="icon"><i class="fa-solid fa-bell"></i></span> Notification
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

    <section class="contact-hero">
        <div class="contact-hero-content">
            <div class="contact-hero-text">
                <span class="sub-kicker">CONTACT US</span>
                <h1>We're Here for You and <span>Your Future Friend</span></h1>
                <p>Have questions about dog adoption or need assistance? Whether you're looking for information about a dog, your adoption request, or using our platform, our team is here to help every step of the way.</p>
                <div class="contact-hero-buttons">
                    <a href="#contactForm" class="btn-hero-send"><i class="fa-solid fa-envelope"></i> Send Message</a>
                    <a href="browse.php" class="btn-hero-browse-paw"><i class="fa-solid fa-paw"></i> Browse Dogs</a>
                </div>
            </div>
            <div class="contact-hero-image">
                <img src="assets/images/ContactHero.png" alt="Woman with happy dog">
            </div>
        </div>
    </section>

    <main class="contact-main">
        <div class="contact-grid">
            
            <aside class="contact-info-card">
                <h2>Get In Touch</h2>
                <p class="info-subtitle">We'd love to hear from you. Whether you have questions about available dogs, the adoption process, or your application status, our team is always ready to assist.</p>

                <div class="info-items">
                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="info-details">
                            <h4>Address</h4>
                            <p>Asian College of Higher Studies<br>Kathmandu, Nepal</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-details">
                            <h4>Phone</h4>
                            <p>+977-98XXXXXXXX</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div class="info-details">
                            <h4>Email</h4>
                            <p>support@pawlix.org</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="contact-form-card" id="contactForm">
                <h2>Send Us a Message</h2>

                <?php if ($success !== ""): ?>
                    <div class="form-alert form-alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="form-alert form-alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form class="contact-form" id="contactMainForm" action="contact.php#contactForm" method="POST">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="userName">Your Name</label>
                            <input type="text" id="userName" name="userName" placeholder="Your Name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            <div id="userName-error-msg" class="input-error-msg"></div>
                        </div>
                        <div class="form-group">
                            <label for="userEmail">Email Address</label>
                            <input type="email" id="userEmail" name="userEmail" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                            <div id="userEmail-error-msg" class="input-error-msg"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="userPhone">Phone Number</label>
                            <input type="tel" id="userPhone" name="userPhone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone); ?>">
                            <div id="userPhone-error-msg" class="input-error-msg"></div>
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

        // LOGOUT MODAL HANDLERS
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

        // REAL-TIME JS VALIDATION FOR CONTACT FORM
        const nameInput  = document.getElementById('userName');
        const emailInput = document.getElementById('userEmail');
        const phoneInput = document.getElementById('userPhone');
        const nameMsg    = document.getElementById('userName-error-msg');
        const emailMsg   = document.getElementById('userEmail-error-msg');
        const phoneMsg   = document.getElementById('userPhone-error-msg');
        const form       = document.getElementById('contactMainForm');

        const nameRegex  = /^[A-Za-z\s]+$/;
        const emailRegex = /^[a-zA-Z](?=.*[0-9])[a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const phoneRegex = /^\d{10}$/;

        function validateName() {
            if (!nameInput || !nameMsg) return true;
            const val = nameInput.value.trim();
            if (val.length === 0) {
                nameMsg.style.display = 'none';
                nameMsg.textContent = '';
                return false;
            }
            if (!nameRegex.test(val)) {
                nameMsg.style.display = 'block';
                nameMsg.textContent = 'Name must contain only alphabets and spaces!';
                nameMsg.className = 'input-error-msg invalid';
                return false;
            } else {
                nameMsg.style.display = 'none';
                nameMsg.textContent = '';
                return true;
            }
        }

        function validateEmail() {
            if (!emailInput || !emailMsg) return true;
            const val = emailInput.value.trim();
            if (val.length === 0) {
                emailMsg.style.display = 'none';
                emailMsg.textContent = '';
                return false;
            }
            if (!emailRegex.test(val)) {
                emailMsg.style.display = 'block';
                emailMsg.textContent = 'Please enter a valid email address.';
                emailMsg.className = 'input-error-msg invalid';
                return false;
            } else {
                emailMsg.style.display = 'none';
                emailMsg.textContent = '';
                return true;
            }
        }

        function validatePhone() {
            if (!phoneInput || !phoneMsg) return true;
            const val = phoneInput.value.trim();
            if (val.length === 0) {
                phoneMsg.style.display = 'none';
                phoneMsg.textContent = '';
                return true;
            }
            if (!phoneRegex.test(val)) {
                phoneMsg.style.display = 'block';
                phoneMsg.textContent = 'Phone number must be exactly 10 digits!';
                phoneMsg.className = 'input-error-msg invalid';
                return false;
            } else {
                phoneMsg.style.display = 'none';
                phoneMsg.textContent = '';
                return true;
            }
        }

        nameInput?.addEventListener('input', validateName);
        emailInput?.addEventListener('input', validateEmail);
        phoneInput?.addEventListener('input', validatePhone);

        if (form) {
            form.addEventListener('submit', function(e) {
                const v1 = validateName();
                const v2 = validateEmail();
                const v3 = validatePhone();
                if (!v1 || !v2 || !v3) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>