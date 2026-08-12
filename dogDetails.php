<?php
require_once 'config/config.php';

/*
|--------------------------------------------------------------------------
| Fetch Dog Details from Database by ID
|--------------------------------------------------------------------------
*/
$dog_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$dog = null;

if ($dog_id > 0) {
    $sql = "SELECT * FROM dog WHERE dog_id = $dog_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $dog = mysqli_fetch_assoc($result);
    }
}

// Extract database values with fallbacks to template defaults
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

/*
|--------------------------------------------------------------------------
| Process Dog Images Dynamically
|--------------------------------------------------------------------------
*/
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

// Default fallback images if database has no image or fewer images
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
</head>
<body>

    <!-- Header (Untouched Original Header) -->
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
            <a href="register.php" class="btn btn-outline">Sign Up</a>
            <a href="login.php" class="btn btn-dark">Login</a>
        </div>
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
    </header>

    <hr style="background-color: white; height: 1px; border: none;">

    <!-- Main View Dog Details Section -->
    <main class="view-dog-section">
        <div class="view-dog-container">
            
            <!-- Left Side: Image Gallery Showcase & Preview Thumbnails -->
            <div class="dog-gallery-wrapper">
                <!-- Main Image Display with Working Navigation Side Arrows -->
                <div class="main-image-container">
                    <img id="mainDogImage" src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo $dog_name; ?> - <?php echo $breed; ?>">
                    
                    <!-- Side Arrows for Switching Images -->
                    <?php if (count($images_list) > 1): ?>
                        <button class="gallery-arrow arrow-prev" id="prevImgBtn" type="button" aria-label="Previous Image">&lt;</button>
                        <button class="gallery-arrow arrow-next" id="nextImgBtn" type="button" aria-label="Next Image">&gt;</button>
                    <?php endif; ?>
                </div>

                <!-- Right Side Thumbnails Preview Column -->
                <div class="thumbnails-preview-column" id="thumbnailsContainer">
                    <?php foreach ($images_list as $index => $img_src): ?>
                        <div class="thumb-item <?php echo ($index === 0) ? 'active' : ''; ?>" data-img="<?php echo htmlspecialchars($img_src); ?>">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo $dog_name; ?> preview <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Side: Dog Info & Specifications -->
            <div class="dog-details-info">
                <!-- Top Header & Back Button -->
                <div class="dog-details-header">
                    <h1 class="dog-name"><?php echo $dog_name; ?></h1>
                    <a href="browse.php" class="btn-back">← Back</a>
                </div>

                <!-- Meta Line -->
                <div class="dog-meta-line">
                    <span class="meta-item"><?php echo ($gender == 'Male') ? '♂ Male' : (($gender == 'Female') ? '♀ Female' : '❓ ' . $gender); ?></span>
                    <span class="meta-separator">|</span>
                    <span class="meta-item"><?php echo $breed; ?></span>
                    <span class="meta-separator">|</span>
                    <span class="meta-item"><?php echo $age; ?> <?php echo ($age == 1) ? 'Year' : 'Years'; ?></span>
                </div>

                <!-- Story / Bio Paragraph -->
                <p class="dog-description">
                    <?php echo $description; ?>
                </p>

                <!-- Specifications Grid (2 Columns x 4 Rows) -->
                <div class="specs-grid">
                    
                    <!-- 1. Breed Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM6.5 5C7.6 5 8.5 5.9 8.5 7C8.5 8.1 7.6 9 6.5 9C5.4 9 4.5 8.1 4.5 7C4.5 5.9 5.4 5 6.5 5ZM17.5 5C18.6 5 19.5 5.9 19.5 7C19.5 8.1 18.6 9 17.5 9C16.4 9 15.5 8.1 15.5 7C15.5 5.9 16.4 5 17.5 5ZM4 11C4.8 11 5.5 11.7 5.5 12.5C5.5 13.3 4.8 14 4 14C3.2 14 2.5 13.3 2.5 12.5C2.5 11.7 3.2 11 4 11ZM20 11C20.8 11 21.5 11.7 21.5 12.5C21.5 13.3 20.8 14 20 14C19.2 14 18.5 13.3 18.5 12.5C18.5 11.7 19.2 11 20 11ZM12 8.5C14.8 8.5 17.5 10.5 17.5 14C17.5 17.5 15.2 21 12 21C8.8 21 6.5 17.5 6.5 14C6.5 10.5 9.2 8.5 12 8.5Z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Breed</span>
                            <span class="spec-value"><?php echo $breed; ?></span>
                        </div>
                    </div>

                    <!-- 2. Weight Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 12c-2.33 0-4.32-1.45-5.12-3.5h10.24c-.8 2.05-2.79 3.5-5.12 3.5z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Weight</span>
                            <span class="spec-value"><?php echo $weight; ?></span>
                        </div>
                    </div>

                    <!-- 3. Age Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Age</span>
                            <span class="spec-value"><?php echo $age; ?> <?php echo ($age == 1) ? 'Year' : 'Years'; ?></span>
                        </div>
                    </div>

                    <!-- 4. Vaccinated Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Vaccinated</span>
                            <span class="spec-value"><?php echo $vaccinated; ?></span>
                        </div>
                    </div>

                    <!-- 5. Gender Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm1 3h-2v3H8v2h3v4h2v-4h3v-2h-3v-3z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Gender</span>
                            <span class="spec-value"><?php echo $gender; ?></span>
                        </div>
                    </div>

                    <!-- 6. Health Status Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Health Status</span>
                            <span class="spec-value"><?php echo $health_status; ?></span>
                        </div>
                    </div>

                    <!-- 7. Size Icon -->
                    <div class="spec-card">
                        <div class="spec-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3l2.3 2.3-2.89 2.87 1.42 1.42L18.7 6.7 21 9V3h-6zM3 9l2.3-2.3 2.87 2.89 1.42-1.42L6.7 5.3 9 3H3v6zm6 12l-2.3-2.3 2.89-2.87-1.42-1.42L5.3 17.3 3 15v6h6zm12-6l-2.3 2.3-2.87-2.89-1.42 1.42 2.89 2.87L15 21h6v-6z"/></svg>
                        </div>
                        <div class="spec-text">
                            <span class="spec-label">Size</span>
                            <span class="spec-value"><?php echo $size; ?></span>
                        </div>
                    </div>

                    <!-- 8. Color Icon -->
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

                <!-- Apply for Adoption Button -->
                <a href="adopt.php?id=<?php echo $dog_id; ?>" class="btn-apply-adoption">Apply for Adoption</a>
            </div>

        </div>
    </main>

    <script src="assets/js/dogDetail.js"></script>
</body>
</html>