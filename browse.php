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

$where_clauses = ["adoption_status = 'Available'"];

$selected_breeds = $_GET['breed'] ?? [];
if (!is_array($selected_breeds) && !empty($selected_breeds)) {
    $selected_breeds = [$selected_breeds];
}
if (!empty($selected_breeds)) {
    $breed_sub_clauses = [];
    foreach ($selected_breeds as $b) {
        $raw_b = trim($b);
        $escaped_b = mysqli_real_escape_string($conn, $raw_b);
        
        $first_word = strtok($raw_b, " (/");
        $escaped_word = mysqli_real_escape_string($conn, $first_word);
        
        $breed_sub_clauses[] = "(breed LIKE '%$escaped_b%' OR breed LIKE '%$escaped_word%')";
    }
    $where_clauses[] = "(" . implode(" OR ", $breed_sub_clauses) . ")";
}

$selected_genders = $_GET['gender'] ?? [];
if (!is_array($selected_genders) && !empty($selected_genders)) {
    $selected_genders = [$selected_genders];
}
if (!empty($selected_genders)) {
    $clean_genders = array_map(function($g) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, strtolower(trim($g))) . "'";
    }, $selected_genders);
    $where_clauses[] = "LOWER(gender) IN (" . implode(",", $clean_genders) . ")";
}

$selected_sizes = $_GET['size'] ?? [];
if (!is_array($selected_sizes) && !empty($selected_sizes)) {
    $selected_sizes = [$selected_sizes];
}
if (!empty($selected_sizes)) {
    $clean_sizes = array_map(function($s) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, strtolower(trim($s))) . "'";
    }, $selected_sizes);
    $where_clauses[] = "LOWER(size) IN (" . implode(",", $clean_sizes) . ")";
}

$search_query = trim($_GET['search'] ?? '');
if (!empty($search_query)) {
    $escaped_search = mysqli_real_escape_string($conn, $search_query);
    $where_clauses[] = "(name LIKE '%$escaped_search%' OR breed LIKE '%$escaped_search%' OR description LIKE '%$escaped_search%')";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM dog WHERE $where_sql ORDER BY dog_id DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawLix - Browse Dogs</title>
    <link rel="stylesheet" href="assets/css/browses.css">
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
            <a href="browse.php" class="active">Browse Dogs <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 2px;"></i></a>
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

            <a href="signup.php" class="btn btn-outline" style="text-decoration:none;">Sign Up</a>
            <a href="login.php" class="btn btn-dark" style="text-decoration:none;">Login</a>

          <?php endif; ?>
        </div>

        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
    </header>

    <hr style="background-color: white; height: 1px; border: none;">

    <section class="browse-hero">
        <div class="browse-hero-text">
            <h1>Find Your <span>New Best Friend</span></h1>
            <p>Browse our available dogs and find the perfect companion for your home</p>
        </div>

        <div class="browse-hero-image">
            <img src="assets/images/browse.png" alt="Happy Golden Retriever">
        </div>
    </section>

    <section class="browse-section">
        <div class="browse-layout">

            <form method="GET" action="browse.php" class="filters" id="filterForm">
                <div class="filters-title">Filters</div>

                <div class="filter-group">
                    <h4>Breed</h4>
                    <?php
                    $breed_options = [
                        "German Shepherd", "Labrador Retriever", "Golden Retriever",
                        "Japanese Spitz", "Tibetan Mastiff(Bhote Kukur)",
                        "Himalayan Sheepdog(Bhotia Kukur)", "Local/ Cross Breed(Local Kukur)",
                        "Beagle", "Pug", "Husky"
                    ];
                    foreach ($breed_options as $b_opt):
                        $checked = in_array($b_opt, $selected_breeds) ? 'checked' : '';
                    ?>
                    <label class="checkbox-row">
                        <input type="checkbox" name="breed[]" value="<?php echo htmlspecialchars($b_opt); ?>" <?php echo $checked; ?>>
                        <span><?php echo htmlspecialchars($b_opt); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h4>Gender</h4>
                    <label class="checkbox-row">
                        <input type="checkbox" name="gender[]" value="Male" <?php echo in_array('Male', $selected_genders) ? 'checked' : ''; ?>>
                        <span>Male</span>
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="gender[]" value="Female" <?php echo in_array('Female', $selected_genders) ? 'checked' : ''; ?>>
                        <span>Female</span>
                    </label>
                </div>

                <div class="filter-group">
                    <h4>Size</h4>
                    <label class="checkbox-row">
                        <input type="checkbox" name="size[]" value="Small" <?php echo in_array('Small', $selected_sizes) ? 'checked' : ''; ?>>
                        <span>Small</span>
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="size[]" value="Medium" <?php echo in_array('Medium', $selected_sizes) ? 'checked' : ''; ?>>
                        <span>Medium</span>
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="size[]" value="Large" <?php echo in_array('Large', $selected_sizes) ? 'checked' : ''; ?>>
                        <span>Large</span>
                    </label>
                </div>

                <button class="btn-apply" type="submit">Apply Filters</button>
                <a href="browse.php" class="btn-clear" style="display:block; text-align:center; margin-top:10px; text-decoration:none; line-height:38px;">Clear Filters</a>
            </form>

            <main>
                <div class="results-grid" id="resultsGrid">

                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($dog = mysqli_fetch_assoc($result)): ?>

                            <?php
                            $age = (int) $dog['age'];
                            $image_name = trim($dog['image'] ?? '');
                            if (strpos($image_name, ',') !== false) {
                                $image_array = explode(',', $image_name);
                                $image_name = trim($image_array[0]);
                            }

                            if (!empty($image_name)) {
                                $image_path = 'uploads/' . $image_name;
                            } else {
                                $image_path = 'assets/images/default-dog.jpg';
                            }

                            $dog_name = htmlspecialchars($dog['name'] ?? 'Unnamed Dog');
                            $breed    = htmlspecialchars($dog['breed'] ?? 'Unknown Breed');
                            $size     = htmlspecialchars($dog['size'] ?? '');
                            $dog_id   = (int) $dog['dog_id'];
                            ?>

                            <div class="result-card">
                                <div class="result-img">
                                    <a href="dogDetails.php?id=<?php echo $dog_id; ?>">
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                             alt="<?php echo $dog_name; ?>"
                                             onerror="this.onerror=null;this.src='assets/images/dog grid 1.jpg';">
                                    </a>
                                </div>

                                <div class="result-info">
                                    <div class="result-top">
                                        <div class="result-meta-left">
                                            <h3><a href="dogDetails.php?id=<?php echo $dog_id; ?>" style="text-decoration:none; color:inherit;"><?php echo $dog_name; ?></a></h3>
                                            <p class="result-breed"><?php echo $breed; ?></p>
                                        </div>

                                        <span class="result-age">
                                            <?php echo $age; ?>
                                            <?php echo ($age == 1) ? ' Year' : ' Years'; ?>
                                        </span>
                                    </div>

                                    <a href="dogDetails.php?id=<?php echo $dog_id; ?>" class="btn-view-more">
                                        View Details
                                    </a>
                                </div>
                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <p class="no-results" style="display:block;">
                            No dogs currently match your selection. Try clearing your filters!
                        </p>

                    <?php endif; ?>

                </div>
            </main>

        </div>
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
        const savedScrollPos = sessionStorage.getItem('browseScrollPos');
        if (savedScrollPos !== null) {
            window.scrollTo({
                top: parseInt(savedScrollPos, 10),
                behavior: 'instant'
            });
            sessionStorage.removeItem('browseScrollPos');
        }

        const btn = document.getElementById('scrollTopBtn');
        if (btn) {
            btn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    sessionStorage.setItem('browseScrollPos', window.scrollY);
                    filterForm.submit();
                });
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
