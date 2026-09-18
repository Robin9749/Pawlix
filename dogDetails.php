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

    .gallery-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .spec-icon i {
        font-size: 20px;
        color: #4a3223;
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
                        <button class="gallery-arrow arrow-prev" id="prevImgBtn" type="button" aria-label="Previous Image">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="gallery-arrow arrow-next" id="nextImgBtn" type="button" aria-label="Next Image">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
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
                    <a href="browse.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>

                <div class="dog-meta-line">
                    <span class="meta-item">
                        <?php if ($gender == 'Male'): ?>
                            <i class="fa-solid fa-mars"></i> Male
                        <?php elseif ($gender == 'Female'): ?>
                            <i class="fa-solid fa-venus"></i> Female
                        <?php else: ?>
                            <?php echo $gender; ?>
                        <?php endif; ?>
                    </span>
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
                            <i class="fa-solid fa-paw"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Breed</span>
                            <span class="spec-value"><?php echo $breed; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-weight-scale"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Weight</span>
                            <span class="spec-value"><?php echo $weight; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Age</span>
                            <span class="spec-value"><?php echo $age; ?> <?php echo ($age == 1) ? 'Year' : 'Years'; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-syringe"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Vaccinated</span>
                            <span class="spec-value"><?php echo $vaccinated; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-venus-mars"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Gender</span>
                            <span class="spec-value"><?php echo $gender; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Health Status</span>
                            <span class="spec-value"><?php echo $health_status; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-ruler-combined"></i>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Size</span>
                            <span class="spec-value"><?php echo $size; ?></span>
                        </div>
                    </div>

                    <div class="spec-card">
                        <div class="spec-icon">
                            <i class="fa-solid fa-palette"></i>
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
