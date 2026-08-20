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

$dog_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dog = null;

if ($dog_id > 0) {
    $sql = "SELECT * FROM dog WHERE dog_id = $dog_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $dog = mysqli_fetch_assoc($result);
    }
}

$dog_name      = htmlspecialchars($dog['name'] ?? 'Max');
$breed         = htmlspecialchars($dog['breed'] ?? 'German Sheperd');
$age           = (int) ($dog['age'] ?? 2);
$gender        = htmlspecialchars($dog['gender'] ?? 'Male');
$weight        = htmlspecialchars($dog['weight'] ?? '28 kg');
$vaccinated    = htmlspecialchars($dog['vaccinated'] ?? $dog['is_vaccinated'] ?? 'Yes');
$health_status = htmlspecialchars($dog['health_status'] ?? 'Healthy');
$size          = htmlspecialchars($dog['size'] ?? 'Large');
$color         = htmlspecialchars($dog['color'] ?? 'Black and Tan');
$description   = htmlspecialchars($dog['description'] ?? $dog['bio'] ?? 'Max is an energetic, intelligent, and loyal dog who loves outdoor activities and spending time with people. He enjoys playing, learning new things, and is looking for a caring family that can provide him with a safe and loving forever home.');

$raw_images = trim($dog['image'] ?? '');
$images_list = [];

if (!empty($raw_images)) {
    if (strpos($raw_images, ',') !== false) {
        $raw_arr = explode(',', $raw_images);
        foreach ($raw_arr as $img) {
            $img = trim($img);
            if (!empty($img)) {
                $images_list[] = (file_exists('uploads/' . $img)) ? 'uploads/' . $img : 'assets/images/' . $img;
            }
        }
    } else {
        $images_list[] = (file_exists('uploads/' . $raw_images)) ? 'uploads/' . $raw_images : 'assets/images/' . $raw_images;
    }
}

$default_thumbs = [
    'assets/images/dog1.jpg',
    'assets/images/dog4.jpg',
    'assets/images/dog5.jpg',
    'assets/images/dog2.jpg',
    'assets/images/dog6.jpg'
];

if (empty($images_list)) {
    $images_list = $default_thumbs;
}

$main_image = $images_list[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawLix - View Dog Details (<?php echo $dog_name; ?>)</title>
    <link rel="stylesheet" href="assets/css/dogDetail.css">

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
            <a href="index.php">Home</a>
            <a href="browse.php" class="active">Browse Dogs ▾</a>
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

            <a href="register.php" class="btn btn-outline" style="text-decoration:none;">Sign Up</a>
            <a href="login.php" class="btn btn-dark" style="text-decoration:none;">Login</a>

          <?php endif; ?>
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
    </header>

    <hr style="background-color: white; height: 1px; border: none;">

    <main class="view-dog-section">
        <div class="view-dog-container">
            
            <div class="dog-gallery-wrapper">
                <div class="main-image-container">
                    <img id="mainDogImage" src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo $dog_name; ?> - <?php echo $breed; ?>">
                    
                    <?php if (count($images_list) > 1): ?>
                        <button class="gallery-arrow arrow-prev" id="prevImgBtn" type="button" aria-label="Previous Image">&lt;</button>
                        <button class="gallery-arrow arrow-next" id="nextImgBtn" type="button" aria-label="Next Image">&gt;</button>
                    <?php endif; ?>
                </div>

                <div class="thumbnails-preview-column" id="thumbnailsContainer">
                    <?php foreach ($images_list as $index => $img_src): ?>
                        <div class="thumb-item <?php echo ($index === 0) ? 'active' : ''; ?>" data-img="<?php echo htmlspecialchars($img_src); ?>">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo $dog_name; ?> preview <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dog-details-info">
                <div class="dog-details-header">
                    <h1 class="dog-name"><?php echo $dog_name; ?></h1>
                    <a href="browse.php" class="btn-back">← Back</a>
                </div>

                <div class="dog-meta-line">
                    <span class="meta-item"><?php echo ($gender == 'Male') ? '♂ Male' : (($gender == 'Female') ? '♀ Female' : '❓ ' . $gender); ?></span>
                    <span class="meta-separator">|</span>
                    <span class="meta-item"><?php echo $breed; ?></span>
                    <span class="meta-separator">|</span>
                    <span class="meta-item"><?php echo $age; ?> <?php echo ($age == 1) ? 'Year' : 'Years'; ?></span>
                </div>

                <p class="dog-description">
                    <?php echo $description; ?>
                </p>

                <div class="specs-grid">
                    
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM6.5 5C7.6 5 8.5 5.9 8.5 7C8.5 8.1 7.6 9 6.5 9C5.4 9 4.5 8.1 4.5 7C4.5 5.9 5.4 5 6.5 5ZM17.5 5C18.6 5 19.5 5.9 19.5 7C19.5 8.1 18.6 9 17.5 9C16.4 9 15.5 8.1 15.5 7C15.5 5.9 16.4 5 17.5 5ZM4 11C4.8 11 5.5 11.7 5.5 12.5C5.5 13.3 4.8 14 4 14C3.2 14 2.5 13.3 2.5 12.5C2.5 11.7 3.2 11 4 11ZM20 11C20.8 11 21.5 11.7 21.5 12.5C21.5 13.3 20.8 14 20 14C19.2 14 18.5 13.3 18.5 12.5C18.5 11.7 19.2 11 20 11ZM12 8.5C14.8 8.5 17.5 10.5 17.5 14C17.5 17.5 15.2 21 12 21C8.8 21 6.5 17.5 6.5 14C6.5 10.5 9.2 8.5 12 8.5Z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Breed</span>
                            <span class="spec-value"><?php echo $breed; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 12c-2.33 0-4.32-1.45-5.12-3.5h10.24c-.8 2.05-2.79 3.5-5.12 3.5z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Weight</span>
                            <span class="spec-value"><?php echo $weight; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Age</span>
                            <span class="spec-value"><?php echo $age; ?> <?php echo ($age == 1) ? 'Year' : 'Years'; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Vaccinated</span>
                            <span class="spec-value"><?php echo $vaccinated; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm1 3h-2v3H8v2h3v4h2v-4h3v-2h-3v-3z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Gender</span>
                            <span class="spec-value"><?php echo $gender; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Health Status</span>
                            <span class="spec-value"><?php echo $health_status; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3l2.3 2.3-2.89 2.87 1.42 1.42L18.7 6.7 21 9V3h-6zM3 9l2.3-2.3 2.87 2.89 1.42-1.42L6.7 5.3 9 3H3v6zm6 12l-2.3-2.3 2.89-2.87-1.42-1.42L5.3 17.3 3 15v6h6zm12-6l-2.3 2.3-2.87-2.89-1.42 1.42 2.89 2.87L15 21h6v-6z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Size</span>
                            <span class="spec-value"><?php echo $size; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 18c-.46.46-.46 1.2 0 1.66.46.46 1.2.46 1.66 0l.64-.64C8.19 19.64 10.02 20 12 20c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-4 9c-.83 0-1.5-.67-1.5-1.5S7.17 9 8 9s1.5.67 1.5 1.5S8.83 12 8 12zm4-3c-.83 0-1.5-.67-1.5-1.5S11.17 6 12 6s1.5.67 1.5 1.5S12.83 9 12 9zm4 3c-.83 0-1.5-.67-1.5-1.5S15.17 9 16 9s1.5.67 1.5 1.5S16.83 12 16 12z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Color</span>
                            <span class="spec-value"><?php echo $color; ?></span>
                        </div>
                    </div>

                </div>

                <a href="adopt.php?id=<?php echo $dog_id; ?>" class="btn-apply-adoption">Apply for Adoption</a>
            </div>

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
    <script src="assets/js/dogDetail.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>