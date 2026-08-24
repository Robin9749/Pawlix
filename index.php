<?php
session_start();
require_once "config/config.php";

$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    
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

function pawlix_short_text($text, $limit = 130) {
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
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

:root {
    --cream: #e9d9b8;
    --tan-light: #ecdfc3;
    --pale-yellow: #f3ecd5;
    --tan-card: #f8eac9;
    --maroon: #7a1f1f;
    --dark-brown: #4a3223;
    --orange: #ff7f11;
    --border-color: #dfcfb0;
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
  text-align: center;
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

.explore {
    padding: 60px 40px;
    text-align: center;
}

.dog-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 390px));
    justify-content: center;
    gap: 48px;
    margin-top: 35px;
}

@media (max-width: 1024px) {
    .dog-grid {
        grid-template-columns: repeat(2, minmax(0, 390px));
        gap: 32px;
    }
}

@media (max-width: 650px) {
    .dog-grid {
        grid-template-columns: minmax(0, 100%);
        gap: 24px;
    }
}

.dog-card {
    background: #f8ebd3;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #e6d3b3;
    box-shadow: 0 8px 24px rgba(74, 50, 35, 0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    text-align: left;
    width: 100%;
}

.dog-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 32px rgba(74, 50, 35, 0.12);
}

.dog-card-image-wrap {
    position: relative;
    width: 100%;
    height: 195px;
    overflow: hidden;
    background: #e2d3b4;
}

.dog-card-image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.dog-card:hover .dog-card-image-wrap img {
    transform: scale(1.05);
}

.wishlist-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.14);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
    outline: none;
}

.wishlist-btn:hover {
    transform: scale(1.12);
    background: #ffffff;
    box-shadow: 0 5px 16px rgba(230, 57, 70, 0.25);
}

.wishlist-btn svg {
    width: 20px;
    height: 20px;
    fill: transparent;
    stroke: #e63946;
    stroke-width: 2.2;
    transition: fill 0.25s ease, stroke 0.25s ease, transform 0.2s ease;
}

.wishlist-btn.active svg {
    fill: #e63946;
    stroke: #e63946;
    animation: heartPulse 0.35s ease-out;
}

@keyframes heartPulse {
    0% { transform: scale(0.7); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.dog-info {
    padding: 18px 22px 22px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.dog-info h3 {
    font-size: 19px;
    font-weight: 700;
    color: #2b2b2b;
    margin-bottom: 4px;
}

.dog-info .breed {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--maroon);
    margin-bottom: 9px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dog-info .desc {
    font-size: 13.5px;
    color: #555;
    line-height: 1.48;
    margin-bottom: 16px;
    flex: 1;
}

.btn-adopt-card {
    display: inline-block;
    text-align: center;
    width: 100%;
    padding: 10.5px;
    background: var(--orange);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.1s ease;
    box-shadow: 0 4px 10px rgba(255, 127, 17, 0.25);
}

.btn-adopt-card:hover {
    background: #e06600;
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

    <div class="header-buttons">
      <?php if (isset($_SESSION['user_id'])): ?>
        
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
            <a href="#" class="logout-trigger logout-link"><span class="icon">🚪</span> Logout</a>
          </div>
        </div>

      <?php else: ?>

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
            $dog_id = intval($dog['dog_id']);
        ?>
        <div class="dog-card">
          <div class="dog-card-image-wrap">
            <img src="<?php echo htmlspecialchars($image_src); ?>"
                 alt="<?php echo htmlspecialchars($dog['name']); ?>"
                 onerror="this.onerror=null;this.src='assets/images/dog grid 1.jpg';">
            
            <button type="button" class="wishlist-btn" data-dog-id="<?php echo $dog_id; ?>" onclick="toggleWishlist(event, <?php echo $dog_id; ?>)" aria-label="Add <?php echo htmlspecialchars($dog['name']); ?> to Wishlist" title="Save to Favorites">
              <svg viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
              </svg>
            </button>
          </div>

          <div class="dog-info">
            <h3><?php echo htmlspecialchars($dog['name']); ?></h3>
            <p class="breed"><?php echo htmlspecialchars($dog['breed']); ?></p>
            <p class="desc"><?php echo htmlspecialchars(pawlix_short_text($dog['description'])); ?></p>
            <a href="dogDetails.php?id=<?php echo $dog_id; ?>" class="btn-adopt-card">Meet <?php echo htmlspecialchars($dog['name']); ?> 🐾</a>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-results" style="display:block;">No dogs available right now — please check back soon!</p>
      <?php endif; ?>
    </div>

    <div class="view-all-container">
      <a href="browse.php" class="btn-view-all">View all Dogs</a>
    </div>
    <p class="no-results" id="noResults" style="display:none;">No dogs found matching your search.</p>
  </section>

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

function initWishlist() {
    let wishlist = [];
    try {
        wishlist = JSON.parse(localStorage.getItem('pawlix_wishlist') || '[]');
    } catch(e) { wishlist = []; }

    document.querySelectorAll('.wishlist-btn').forEach(function(btn) {
        const dogId = btn.getAttribute('data-dog-id');
        if (wishlist.includes(String(dogId))) {
            btn.classList.add('active');
        }
    });
}

function toggleWishlist(e, dogId) {
    if (e) { e.stopPropagation(); e.preventDefault(); }
    
    let wishlist = [];
    try {
        wishlist = JSON.parse(localStorage.getItem('pawlix_wishlist') || '[]');
    } catch(e) { wishlist = []; }

    const strId = String(dogId);
    const btn = document.querySelector(`.wishlist-btn[data-dog-id="${dogId}"]`);

    if (wishlist.includes(strId)) {
        wishlist = wishlist.filter(id => id !== strId);
        if (btn) btn.classList.remove('active');
    } else {
        wishlist.push(strId);
        if (btn) btn.classList.add('active');
    }

    localStorage.setItem('pawlix_wishlist', JSON.stringify(wishlist));
}

document.addEventListener('DOMContentLoaded', function() {
    initWishlist();

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
</body>
</html>