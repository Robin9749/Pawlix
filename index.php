<?php
session_start();
require_once "config/config.php";

// Unread Notifications Count (Directly from existing tables)
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    
    // Count status updates from report_dogs and adoption_application
    $r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $uid AND status != 'Pending'");
    $r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $uid AND status != 'Pending'");
    
    $c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
    $c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
    
    $unreadCount = $c1 + $c2;
}

$dogs_sql = "SELECT dog_id, name, breed, description, image
             FROM dog
             WHERE adoption_status = 'Available'
             ORDER BY created_at DESC
             LIMIT 6";
$dogs_result = mysqli_query($conn, $dogs_sql);

function pawlix_short_text($text, $limit = 140) {
    $text = trim((string) $text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit - 1) . "…";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PawLix - Find a Friend, Give a Home</title>
<link rel="stylesheet" href="assets/css/style.css">

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
      <a href="index.php" class="active">Home</a>
      <a href="browse.php">Browse Dogs ▾</a>
      <a href="about.php">About</a>
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

      <?php else: ?>

        <!-- LOGGED OUT: LOGIN & SIGNUP -->
        <a href="signup.php" class="btn btn-outline" style="text-decoration:none;">Sign Up</a>
        <a href="login.php" class="btn btn-dark" style="text-decoration:none;">Login</a>

      <?php endif; ?>
    </div>

    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
  </header>

<hr style="background-color: white; height: 1px; border: none;">

  <section class="hero">
    <div class="hero-text">
      <h1>Find a Friend.<br>Give a Home.<br>Change a Life.</h1>
      <p>Discover dogs waiting for a loving family. Browse available dogs, learn about their stories, and begin your adoption journey through a simple and secure platform.</p>
    </div>
    <div class="hero-image">
      <img src="assets/images/hero image.png" alt="Puppies" id="heroImg">
    </div>
    <div class="dots" id="heroDots">
      <span class="dot active" data-index="0"></span>
      <span class="dot" data-index="1"></span>
      <span class="dot" data-index="2"></span>
    </div>
  </section>

  <section class="steps">
    <h2>100+ Dog Adopted</h2>
    <p class="subtitle">Here is how you can get started on your pet adoption journey</p>

    <div class="steps-cards">
      <div class="step-card">
        <div class="circle"><img src="assets/images/step card 1.jpg" alt="Browse Dogs"></div>
        <h3>Browse Dogs</h3>
        <p>Find your perfect companion by exploring dogs available for adoption. View their breed, age, personality, and other details before making your choice.</p>
      </div>
      <div class="step-card">
        <div class="circle"><img src="assets/images/step card 2.jpg" alt="Adopt a Dog"></div>
        <h3>Adopt a Dog</h3>
        <p>Ready to welcome a new family member? Submit an adoption request through our simple and secure online process.</p>
      </div>
      <div class="step-card">
        <div class="circle"><img src="assets/images/step card 3.jpg" alt="Track Your Request"></div>
        <h3>Track Your Request</h3>
        <p>Stay informed about your adoption application. Check whether your request is pending, approved, or rejected at any time.</p>
      </div>
    </div>
  </section>

  <section class="explore">
    <h2>Featured Dogs</h2>
    <div class="dog-grid" id="dogGrid">
      <?php if ($dogs_result && mysqli_num_rows($dogs_result) > 0): ?>
        <?php while ($dog = mysqli_fetch_assoc($dogs_result)):
            $images = explode(",", (string) $dog['image']);
            $first_image = !empty($images[0]) ? trim($images[0]) : '';
            $image_src = $first_image ? "uploads/" . $first_image : "assets/images/dog grid 1.jpg";
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($dog['name'])));
        ?>
        <div class="dog-card" data-name="<?php echo htmlspecialchars($dog['name']); ?>">
          <img src="<?php echo htmlspecialchars($image_src); ?>"
               alt="<?php echo htmlspecialchars($dog['name']); ?>"
               onerror="this.onerror=null;this.src='assets/images/dog grid 1.jpg';">
          <div class="dog-info">
            <h3><?php echo htmlspecialchars($dog['name']); ?></h3>
            <p class="breed"><?php echo htmlspecialchars($dog['breed']); ?></p>
            <p class="desc"><?php echo htmlspecialchars(pawlix_short_text($dog['description'])); ?></p>
            <div class="vote" data-dog="<?php echo htmlspecialchars($slug); ?>">
              <button class="vote-btn like-btn" aria-label="Like <?php echo htmlspecialchars($dog['name']); ?>">👍 <span class="count like-count">0</span></button>
              <button class="vote-btn dislike-btn" aria-label="Dislike <?php echo htmlspecialchars($dog['name']); ?>">👎 <span class="count dislike-count">0</span></button>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-results" style="display:block;">No dogs available right now — please check back soon!</p>
      <?php endif; ?>
    </div>

    <div class="view-all-container">
      <a href="browse.php" class="btn-view-all">View all Pets</a>
    </div>
    <p class="no-results" id="noResults" style="display:none;">No dogs found matching your search.</p>
  </section>

  <!-- FOOTER -->
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
</body>
</html>