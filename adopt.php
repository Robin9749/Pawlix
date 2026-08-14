<?php
session_start();
require_once 'config/config.php';

$dog_id = 0;
if (isset($_REQUEST['dog_id']) && (int)$_REQUEST['dog_id'] > 0) {
    $dog_id = (int)$_REQUEST['dog_id'];
} elseif (isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
    $dog_id = (int)$_REQUEST['id'];
}

if (!isset($_SESSION['user_id'])) {
    $dog_param = $dog_id > 0 ? '?dog_id=' . $dog_id : '';
    header("Location: login.php" . $dog_param);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Unread Notifications Count
$unreadCount = 0;
$r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $user_id AND status != 'Pending'");
$r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $user_id AND status != 'Pending'");
$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
$unreadCount = $c1 + $c2;

$user_stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE user_id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_res = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_res);
mysqli_stmt_close($user_stmt);

$full_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
$email     = $user_data['email'] ?? '';
$phone     = $user_data['phone'] ?? '';
$address   = $user_data['address'] ?? '';

$dog = null;
if ($dog_id > 0) {
    $dog_stmt = mysqli_prepare($conn, "SELECT * FROM dog WHERE dog_id = ?");
    mysqli_stmt_bind_param($dog_stmt, "i", $dog_id);
    mysqli_stmt_execute($dog_stmt);
    $dog_res = mysqli_stmt_get_result($dog_stmt);
    if ($dog_res && mysqli_num_rows($dog_res) > 0) {
        $dog = mysqli_fetch_assoc($dog_res);
    }
    mysqli_stmt_close($dog_stmt);
}

$image_name = trim($dog['image'] ?? '');
if (strpos($image_name, ',') !== false) {
    $image_array = explode(',', $image_name);
    $image_name = trim($image_array[0]);
}
$image_path = !empty($image_name) ? 'uploads/' . $image_name : 'assets/images/default-dog.jpg';
$dog_age = (int)($dog['age'] ?? 0);
$age_text = $dog_age > 0 ? ($dog_age . ($dog_age == 1 ? ' Year' : ' Years')) : 'N/A';

$success_msg = "";
$error_msg   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_dog_id = isset($_POST['dog_id']) ? (int)$_POST['dog_id'] : $dog_id;

    if ($post_dog_id <= 0) {
        $error_msg = "Please select a valid dog to adopt.";
    } else {
        $check_stmt = mysqli_prepare($conn, "SELECT * FROM adoption_application WHERE user_id = ? AND dog_id = ? AND status = 'Pending'");
        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $post_dog_id);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);

        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $error_msg = "You already have a pending application for this dog.";
        } else {
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO adoption_application (user_id, dog_id, status) VALUES (?, ?, 'Pending')");
            mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $post_dog_id);
            $executed = mysqli_stmt_execute($insert_stmt);

            if ($executed) {
                $success_msg = "Application submitted successfully! Our team will review your request.";
            } else {
                $error_msg = "Error submitting application: " . mysqli_error($conn);
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }

    // Re-fetch the dog for this dog_id so the form still shows the right dog after POST
    if ($post_dog_id > 0 && $post_dog_id !== $dog_id) {
        $dog_id = $post_dog_id;
        $dog_stmt = mysqli_prepare($conn, "SELECT * FROM dog WHERE dog_id = ?");
        mysqli_stmt_bind_param($dog_stmt, "i", $dog_id);
        mysqli_stmt_execute($dog_stmt);
        $dog_res = mysqli_stmt_get_result($dog_stmt);
        if ($dog_res && mysqli_num_rows($dog_res) > 0) {
            $dog = mysqli_fetch_assoc($dog_res);
        }
        mysqli_stmt_close($dog_stmt);

        $image_name = trim($dog['image'] ?? '');
        if (strpos($image_name, ',') !== false) {
            $image_array = explode(',', $image_name);
            $image_name = trim($image_array[0]);
        }
        $image_path = !empty($image_name) ? 'uploads/' . $image_name : 'assets/images/default-dog.jpg';
        $dog_age = (int)($dog['age'] ?? 0);
        $age_text = $dog_age > 0 ? ($dog_age . ($dog_age == 1 ? ' Year' : ' Years')) : 'N/A';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawLix - Apply for Adoption</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root{
            --cream:#e9d9b8;
            --tan-light:#ecdfc3;
            --pale-yellow:#f3ecd5;
            --tan-card:#dcb590;
            --maroon:#7a1f1f;
            --dark-brown:#4a3223;
            --orange:#ff7f11;
            --orange-dark:#e06600;
            --navy:#0e1524;
            --blue:#2f6bf0;
            --blue-dark:#1f54d1;
        }

        *{ margin:0; padding:0; box-sizing:border-box; }
        html{ scroll-behavior:smooth; }
        body{ font-family:'Poppins',sans-serif; background:var(--pale-yellow); color:#222; overflow-x:hidden; }
        img{ width:100%; display:block; }
        a{ text-decoration:none; color:inherit; }
        button{ font-family:'Poppins',sans-serif; cursor:pointer; border:none; }

        .header{
            width:100%; height:90px; display:flex; justify-content:space-between; align-items:center;
            padding:0 80px; background:var(--cream); position:relative; z-index:1000; box-shadow:0 5px 20px rgba(0,0,0,.05);
        }
        .logo img{ width:150px; position:relative; top:15px; left:-20px; }
        .nav{ display:flex; align-items:center; gap:45px; }
        .nav a{ font-size:16px; font-weight:500; color:var(--dark-brown); transition:.3s; position:relative; }
        .nav a:hover, .nav .active{ color:var(--maroon); }
        .nav a::after{ content:""; position:absolute; left:0; bottom:-8px; width:0%; height:3px; background:var(--maroon); transition:.35s; border-radius:20px; }
        .header-buttons{ display:flex; gap:15px; }
        .btn{ padding:12px 28px; border-radius:50px; font-size:15px; font-weight:600; transition:.35s; }
        .btn-outline{ background:transparent; border:2px solid var(--dark-brown); color:var(--dark-brown); }
        .btn-outline:hover{ background:var(--dark-brown); color:#fff; }
        .btn-dark{ background:#1c1c1c; color:#fff; }
        .btn-dark:hover{ background:var(--orange); }
        .menu-toggle{ display:none; font-size:32px; background:none; color:var(--dark-brown); }

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

        .adopt-hero{ width:100%; background:var(--tan-light); padding:45px 80px 40px; text-align:center; }
        .adopt-hero-content{ max-width:700px; margin:0 auto; }
        .sub-kicker{ font-size:13px; font-weight:700; letter-spacing:1.5px; color:var(--maroon); display:inline-block; margin-bottom:8px; text-transform:uppercase; }
        .adopt-hero h1{ font-size:42px; font-weight:800; color:#1c1c1c; margin-bottom:12px; }
        .adopt-hero h1 span{ color:var(--orange); }
        .adopt-hero p{ font-size:16px; color:#444; line-height:1.6; }

        .adopt-section{ width:100%; background:var(--pale-yellow); padding:45px 80px 75px; }
        .adopt-container{ max-width:900px; margin:0 auto; }
        .adopt-form{ background:#f8ebd3; border:1.5px solid #e6d3b3; border-radius:20px; padding:40px 48px; box-shadow:0 10px 30px rgba(74,50,35,0.06); }

        .alert{ padding:15px 20px; border-radius:10px; margin-bottom:20px; font-weight:600; font-size:15px; text-align:center; }
        .alert-success{ background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-error{ background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        .form-section-title{ display:flex; align-items:center; gap:14px; margin-top:24px; margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #ecd8b6; }
        .form-section-title:first-of-type{ margin-top:0; }
        .step-num{ width:32px; height:32px; border-radius:50%; background:var(--maroon); color:#fff; font-weight:700; font-size:15px; display:flex; align-items:center; justify-content:center; box-shadow:0 3px 8px rgba(122,31,31,0.25); }
        .form-section-title h2{ font-size:21px; font-weight:700; color:#1c1c1c; }

        .form-grid-2{ display:grid; grid-template-columns:repeat(2,1fr); gap:20px; }
        .form-group{ margin-bottom:20px; }
        .form-group label{ display:block; font-size:14px; font-weight:600; color:#3b2719; margin-bottom:7px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group select, .form-group textarea{
            width:100%; padding:13px 16px; border:1.5px solid #dfcaab; border-radius:10px; font-size:14px; font-family:'Poppins',sans-serif; background:#fff8ed; color:#222; outline:none; transition:all 0.3s ease;
        }
        .form-group input[readonly]{ background:#ebdcb8; border-color:#d7c39d; color:#4a3223; font-weight:600; cursor:not-allowed; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus{ border-color:var(--orange); background:#ffffff; box-shadow:0 0 0 3px rgba(255,127,17,0.18); }
        .form-group textarea{ resize:vertical; }

        .selected-dog-simple{ display:flex; gap:24px; align-items:flex-start; margin-bottom:10px; }
        .selected-dog-img{ width:110px; height:110px; border-radius:14px; overflow:hidden; flex-shrink:0; border:3px solid var(--orange); box-shadow:0 4px 12px rgba(0,0,0,0.08); margin-top:4px; }
        .selected-dog-img img{ width:100%; height:100%; object-fit:cover; }
        .selected-dog-fields{ flex:1; }

        .form-checkbox-group{ display:flex; align-items:flex-start; gap:12px; margin:24px 0 20px; background:var(--pale-yellow); padding:14px 18px; border-radius:10px; border:1px solid #e0ceae; }
        .form-checkbox-group input[type="checkbox"]{ width:18px; height:18px; accent-color:var(--orange); margin-top:2px; cursor:pointer; }
        .form-checkbox-group label{ font-size:13.5px; color:#443224; line-height:1.5; cursor:pointer; font-weight:500; }

        .form-actions{ display:flex; align-items:center; gap:16px; margin-top:10px; }
        .btn-submit-adopt{ flex:1; padding:15px; background:var(--orange); color:#fff; font-size:16px; font-weight:700; border-radius:10px; box-shadow:0 4px 16px rgba(255,127,17,0.3); transition:0.3s; cursor:pointer; text-align:center; }
        .btn-submit-adopt:hover{ background:var(--orange-dark); transform:translateY(-2px); box-shadow:0 7px 20px rgba(255,127,17,0.4); }
        .btn-cancel-adopt{ padding:15px 28px; background:#ecdcb8; color:var(--dark-brown); font-weight:700; font-size:14.5px; border-radius:10px; border:1px solid #d9c59d; transition:0.3s; text-decoration:none; }
        .btn-cancel-adopt:hover{ background:var(--dark-brown); color:#fff; }

        .footer{ background:var(--navy); color:#fff; padding:70px 80px 30px; }
        .footer-top{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom:40px; }
        .footer .logo{ font-size:32px; font-weight:700; color:#fff; }
        .search-box{ display:flex; max-width:520px; width:100%; background:#fff; border-radius:50px; overflow:hidden; }
        .search-box input{ flex:1; border:none; outline:none; padding:18px 22px; font-size:15px; }
        .search-box button{ background:var(--blue); color:#fff; padding:0 30px; font-size:16px; transition:.3s; }
        .search-box button:hover{ background:var(--blue-dark); }
        .footer hr{ border:none; height:1px; background:rgba(255,255,255,.15); margin:35px 0; }
        .footer-columns{ display:grid; grid-template-columns:repeat(4,1fr); gap:40px; }
        .footer-col h4{ margin-bottom:18px; font-size:15px; letter-spacing:1px; color:#cfcfcf; }
        .footer-col p{ margin-bottom:12px; color:#b9b9b9; transition:.3s; cursor:pointer; }
        .footer-col p:hover{ color:#fff; padding-left:6px; }
        .socials{ display:flex; gap:12px; margin-top:15px; }
        .socials span{ width:42px; height:42px; border-radius:50%; display:flex; justify-content:center; align-items:center; background:#1c2438; cursor:pointer; transition:.3s; font-size:18px; }
        .socials span:hover{ background:var(--orange); transform:translateY(-4px); }
        .footer-bottom{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
        .footer-bottom p{ color:#999; font-size:14px; }
        .footer-links{ display:flex; gap:30px; }
        .footer-links a{ color:#ccc; transition:.3s; font-size:15px; }
        .footer-links a:hover{ color:var(--orange); }

        @media(max-width:1100px){ .adopt-section{ padding:40px 30px; } .adopt-form{ padding:30px 25px; } .footer-columns{ grid-template-columns:repeat(2,1fr); } }
        @media(max-width:768px){
            .header{ padding:18px 25px; }
            .nav{ position:absolute; top:90px; left:0; width:100%; background:var(--cream); flex-direction:column; display:none; padding:30px; box-shadow:0 10px 25px rgba(0,0,0,.1); }
            .nav.show{ display:flex; }
            .header-buttons{ display:none; }
            .menu-toggle{ display:block; }
            .adopt-hero{ padding:35px 20px 30px; }
            .adopt-hero h1{ font-size:32px; }
            .selected-dog-simple{ flex-direction:column; align-items:center; }
            .form-grid-2{ grid-template-columns:1fr; }
            .form-actions{ flex-direction:column; }
            .btn-cancel-adopt{ width:100%; text-align:center; }
            .footer{ padding:50px 20px 20px; }
            .footer-columns{ grid-template-columns:1fr; }
            .footer-bottom{ flex-direction:column; text-align:center; }
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

    <section class="adopt-hero">
        <div class="adopt-hero-content">
            <span class="sub-kicker">PAWLIX ADOPTION</span>
            <h1>Apply for <span>Adoption</span></h1>
            <p>Please fill out this form to complete your adoption application. All information is kept strictly confidential.</p>
        </div>
    </section>

    <main class="adopt-section">
        <div class="adopt-container">

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <?php if ($dog_id > 0 && !$dog): ?>
                <div class="alert alert-error">The dog you selected could not be found. Please go back and choose a dog to adopt.</div>
            <?php endif; ?>

            <form class="adopt-form" id="adoptForm" method="POST" action="adopt.php<?php echo $dog_id ? '?dog_id='.$dog_id : ''; ?>">
                <input type="hidden" name="dog_id" value="<?php echo $dog_id; ?>">

                <div class="form-section-title">
                    <span class="step-num">1</span>
                    <h2>Applicant Information</h2>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" id="fullName" value="<?php echo htmlspecialchars($full_name); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" <?php echo !empty($phone) ? 'readonly' : ''; ?> required>
                    </div>
                    <div class="form-group">
                        <label for="address">Residential Address *</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" <?php echo !empty($address) ? 'readonly' : ''; ?> required>
                    </div>
                </div>

                <div class="form-section-title">
                    <span class="step-num">2</span>
                    <h2>Selected Dog</h2>
                </div>

                <div class="selected-dog-simple">
                    <div class="selected-dog-img">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($dog['name'] ?? 'Dog'); ?>">
                    </div>
                    <div class="selected-dog-fields">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Dog Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($dog['name'] ?? 'N/A'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Breed</label>
                                <input type="text" value="<?php echo htmlspecialchars($dog['breed'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Age</label>
                                <input type="text" value="<?php echo htmlspecialchars($age_text); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <input type="text" value="<?php echo htmlspecialchars($dog['gender'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section-title">
                    <span class="step-num">3</span>
                    <h2>Household Information</h2>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="housingType">Housing Type *</label>
                        <select id="housingType" name="housing_type" required>
                            <option value="" disabled selected>Select housing type</option>
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="currentPets">Current Pets</label>
                        <select id="currentPets" name="current_pets">
                            <option value="none">None</option>
                            <option value="dog">Dog</option>
                            <option value="cat">Cat</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-title">
                    <span class="step-num">4</span>
                    <h2>Adoption Information</h2>
                </div>

                <div class="form-group">
                    <label for="adoptionReason">Reason for Adoption *</label>
                    <select id="adoptionReason" name="adoption_reason" required>
                        <option value="" disabled selected>Select primary reason</option>
                        <option value="companion">Family Companion / Pet</option>
                        <option value="active-partner">Active Outdoor Partner</option>
                        <option value="emotional-support">Emotional Support & Companionship</option>
                        <option value="second-pet">Companion for Current Pet</option>
                        <option value="watchdog">Guard / Watch Dog</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="whyThisDog">Why do you want to adopt this dog? *</label>
                    <textarea id="whyThisDog" name="why_this_dog" rows="3" placeholder="Tell us why you want to adopt..." required></textarea>
                </div>

                <div class="form-section-title">
                    <span class="step-num">5</span>
                    <h2>Confirmation</h2>
                </div>

                <div class="form-checkbox-group">
                    <input type="checkbox" id="agreeTerms" required>
                    <label for="agreeTerms">I confirm that the information provided is correct and I agree to the adoption terms.</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit-adopt">Submit Application</button>
                    <a href="browse.php" class="btn-cancel-adopt">Cancel</a>
                </div>

            </form>

        </div>
    </main>

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