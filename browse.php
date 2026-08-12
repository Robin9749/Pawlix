<?php
require_once 'config/config.php';

// Retrieve filter criteria from GET params or POST
$where_clauses = ["adoption_status = 'Available'"];

$selected_breeds = $_GET['breed'] ?? [];
if (!is_array($selected_breeds) && !empty($selected_breeds)) {
    $selected_breeds = [$selected_breeds];
}
if (!empty($selected_breeds)) {
    $clean_breeds = array_map(function($b) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, trim($b)) . "'";
    }, $selected_breeds);
    $where_clauses[] = "breed IN (" . implode(",", $clean_breeds) . ")";
}

$selected_genders = $_GET['gender'] ?? [];
if (!is_array($selected_genders) && !empty($selected_genders)) {
    $selected_genders = [$selected_genders];
}
if (!empty($selected_genders)) {
    $clean_genders = array_map(function($g) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, trim($g)) . "'";
    }, $selected_genders);
    $where_clauses[] = "gender IN (" . implode(",", $clean_genders) . ")";
}

$selected_sizes = $_GET['size'] ?? [];
if (!is_array($selected_sizes) && !empty($selected_sizes)) {
    $selected_sizes = [$selected_sizes];
}
if (!empty($selected_sizes)) {
    $clean_sizes = array_map(function($s) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, trim($s)) . "'";
    }, $selected_sizes);
    $where_clauses[] = "size IN (" . implode(",", $clean_sizes) . ")";
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
            <button class="btn btn-outline">Sign Up</button>
            <button class="btn btn-dark">Login</button>
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

    <script src="assets/js/browse.js"></script>
</body>
</html>
