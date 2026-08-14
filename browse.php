<?php
session_start();
require_once 'config/config.php';

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

// Retrieve filter criteria from GET params or POST
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
        
        // Extract primary keyword for flexible breed matching
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
            <a href="browse.php" class="active">Browse Dogs ▾</a>
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

    <!-- HERO SECTION -->
    <section class="browse-hero">
        <div class="browse-hero-text">
            <h1>Find Your <span>New Best Friend</span></h1>
            <p>Browse our available dogs and find the perfect companion for your home</p>
        </div>

        <div class="browse-hero-image">
            <img src="assets/images/browse.png" alt="Happy Golden Retriever">
        </div>
    </section>

    <!-- BROWSE SECTION -->
    <section class="browse-section">
        <div class="browse-layout">

            <!-- FILTER SIDEBAR -->
            <form method="GET" action="browse.php" class="filters" id="filterForm">
                <div class="filters-title">Filters</div>

                <!-- BREED -->
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

                <!-- GENDER -->
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

                <!-- SIZE -->
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

            <!-- DOG RESULTS -->
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

    <!-- FOOTER -->
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
        // Restore scroll position if previously saved before filter form submit
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

        // Auto-submit filter form on checkbox change and maintain scroll position
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
    });
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>