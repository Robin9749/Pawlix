<?php
session_start();
include("config/config.php");

$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    
    $r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $uid AND status != 'Pending'");
    $r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $uid AND status != 'Pending'");
    
    $c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
    $c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
    
    $unreadCount = $c1 + $c2;
}

$error = "";
$success = false;
$refTicket = "";

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
    $refTicket = isset($_GET['ref']) ? $_GET['ref'] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reporterName  = mysqli_real_escape_string($conn, trim($_POST['reporterName'] ?? ''));
    $reporterPhone = mysqli_real_escape_string($conn, trim($_POST['reporterPhone'] ?? ''));
    $reporterEmail = mysqli_real_escape_string($conn, trim($_POST['reporterEmail'] ?? ''));
    $reportType    = mysqli_real_escape_string($conn, trim($_POST['reportType'] ?? ''));
    $dogColor      = mysqli_real_escape_string($conn, trim($_POST['dogColor'] ?? ''));
    $estimatedAge  = mysqli_real_escape_string($conn, trim($_POST['estimatedAge'] ?? ''));
    $dogGender     = mysqli_real_escape_string($conn, trim($_POST['dogGender'] ?? 'Unknown'));
    $dogCondition  = mysqli_real_escape_string($conn, trim($_POST['dogCondition'] ?? ''));
    $description   = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $locationArea  = mysqli_real_escape_string($conn, trim($_POST['locationArea'] ?? ''));
    $city          = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    $landmark      = mysqli_real_escape_string($conn, trim($_POST['landmark'] ?? ''));

    if (
        empty($reportType) ||
        empty($dogColor) ||
        empty($dogCondition) ||
        empty($description) ||
        empty($locationArea) ||
        empty($city) ||
        empty($landmark) ||
        empty($_FILES['dogPhoto']['name'])
    ) {
        $error = "Please fill all required fields (*) and upload a photo of the dog.";
    } else {

        $upload_folder = "uploads/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        $photo_name_save = "";
        $image_name = $_FILES['dogPhoto']['name'];
        $temp_name  = $_FILES['dogPhoto']['tmp_name'];
        $error_code = $_FILES['dogPhoto']['error'];

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        if ($error_code !== UPLOAD_ERR_OK) {
            $error = "There was a problem uploading the photo. Please try again.";
        } elseif (!in_array($ext, $allowed_ext)) {
            $error = "Photo must be a JPG, PNG, GIF, or WEBP image.";
        } else {
            $clean_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($image_name));
            $photo_name_save = time() . "_" . $clean_filename;

            if (!move_uploaded_file($temp_name, $upload_folder . $photo_name_save)) {
                $error = "Failed to save the uploaded photo.";
                $photo_name_save = "";
            }
        }

        if ($error === "") {
            $photo_db = mysqli_real_escape_string($conn, $photo_name_save);
            $user_id_val = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : "NULL";

            $sql = "INSERT INTO report_dogs
    (user_id, name, phone_number, email, report_type, dog_color, estimated_age, gender, dog_condition, description, location_area, city_municipality, landmark, dog_photo, status)
    VALUES
    ($user_id_val, '$reporterName', '$reporterPhone', '$reporterEmail', '$reportType', '$dogColor', '$estimatedAge', '$dogGender', '$dogCondition', '$description', '$locationArea', '$city', '$landmark', '$photo_db', 'Pending')";

            if (mysqli_query($conn, $sql)) {
                $newId = mysqli_insert_id($conn);
                $ref = "RESCUE-" . date("Y") . "-" . str_pad($newId, 4, "0", STR_PAD_LEFT);
                header("Location: report.php?success=1&ref=" . urlencode($ref));
                exit();
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawLix - Report a Dog</title>
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
        body{ font-family:'Poppins',sans-serif; background:var(--pale-yellow); color:#222; overflow-x:hidden; }
        a{ text-decoration:none; color:inherit; }

        .header{ width:100%; height:90px; display:flex; justify-content:space-between; align-items:center; padding:0 80px; background:var(--cream); position:relative; z-index:1000; box-shadow:0 5px 20px rgba(0,0,0,.05); }
        .logo img{ width:150px; position:relative; top:15px; left:-20px; }
       .nav{
    display:flex;
    align-items:center;
    gap:45px;
}

.nav a{
    font-size:16px;
    font-weight:500;
    color:var(--dark-brown);
    transition:.3s;
    position:relative;
}

.nav .active{
    color:var(--maroon);
    font-weight: 610;
}

.nav a::after{
    content:"";
    position:absolute;
    left:0;
    bottom:-8px;
    width:0%;
    height:3px;
    background:var(--maroon);
    transition:.35s;
    border-radius:20px;
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

        /* LOGOUT MODAL OVERLAY */
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

        .report-hero{ width:100%; background:var(--tan-light); padding:45px 80px 40px; text-align:center; }
        .report-hero-content{ max-width:720px; margin:0 auto; }
        .sub-kicker{ font-size:13px; font-weight:700; letter-spacing:1.5px; color:var(--maroon); display:inline-block; margin-bottom:8px; text-transform:uppercase; }
        .report-hero h1{ font-size:42px; font-weight:800; color:#1c1c1c; margin-bottom:12px; }
        .report-hero h1 span{ color:var(--orange); }
        .report-hero p{ font-size:16px; color:#444; line-height:1.6; }

        .report-section{ width:100%; background:var(--pale-yellow); padding:45px 80px 75px; }
        .report-container{ max-width:920px; margin:0 auto; }

        .report-form{ background:#f8ebd3; border:1.5px solid #e6d3b3; border-radius:20px; padding:40px 48px; box-shadow:0 10px 30px rgba(74,50,35,0.06); }
        .form-section-header{ display:flex; justify-content:space-between; align-items:center; margin-top:28px; margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #ecd8b6; }
        .form-section-header:first-of-type{ margin-top:0; }
        .form-section-title{ display:flex; align-items:center; gap:14px; }
        .step-num{ width:32px; height:32px; border-radius:50%; background:var(--maroon); color:#fff; font-weight:700; font-size:15px; display:flex; align-items:center; justify-content:center; }
        .form-section-title h2{ font-size:21px; font-weight:700; color:#1c1c1c; }

        .form-grid-2{ display:grid; grid-template-columns:repeat(2,1fr); gap:20px; }
        .form-grid-3{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .form-group{ margin-bottom:20px; }
        .form-group label{ display:block; font-size:14px; font-weight:600; color:#3b2719; margin-bottom:7px; }
        .form-group input, .form-group select, .form-group textarea{ width:100%; padding:13px 16px; border:1.5px solid #dfcaab; border-radius:10px; font-size:14px; font-family:'Poppins',sans-serif; background:#fff8ed; color:#222; outline:none; }

        .photo-upload-zone{ border:2px dashed #d7be98; background:#fff8ed; border-radius:14px; padding:32px 20px; text-align:center; cursor:pointer; position:relative; }
        .photo-upload-zone input[type="file"]{ position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        #filePreviewName{ margin-top:10px; font-size:13.5px; font-weight:600; color:var(--maroon); }

        .form-checkbox-group{ display:flex; align-items:flex-start; gap:12px; margin:24px 0 20px; background:var(--pale-yellow); padding:14px 18px; border-radius:10px; border:1px solid #e0ceae; }
        .form-actions{ display:flex; align-items:center; gap:16px; margin-top:10px; }
        .btn-submit-report{ flex:1; padding:15px; background:var(--orange); color:#fff; font-size:16px; font-weight:700; border-radius:10px; cursor:pointer; border:none; }
        .btn-cancel-report{ padding:15px 28px; background:#ecdcb8; color:var(--dark-brown); font-weight:700; font-size:14.5px; border-radius:10px; border:1px solid #d9c59d; text-decoration:none; }

        .footer{ background:var(--navy); color:#fff; padding:70px 80px 30px; }
        .footer-top{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom:40px; }
        .footer-columns{ display:grid; grid-template-columns:repeat(4,1fr); gap:40px; }
        .footer-bottom{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            <img src="assets/images/logo.png" alt="PawLix logo">
        </div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="browse.php">Browse Dogs ▾</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="report.php" class="active">Report a Dog</a>
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

    <section class="report-hero">
        <div class="report-hero-content">
            <span class="sub-kicker">EMERGENCY & RESCUE</span>
            <h1>Report a <span>Dog in Need</span></h1>
            <p>Help us rescue stray, injured, or abandoned dogs. Fill out the report form below so our rescue team can locate and assist them immediately.</p>
        </div>
    </section>

    <main class="report-section">
        <div class="report-container">
            
            <?php if ($success): ?>
                <div style="text-align:center; padding:30px; background:#fff; border-radius:16px;">
                    <h2 style="color:#1e6e2e;">Report Submitted Successfully!</h2>
                    <p style="margin-top:10px;">Reference Ticket: <strong><?php echo htmlspecialchars($refTicket); ?></strong></p>
                    <a href="index.php" class="btn-submit-report" style="display:inline-block; margin-top:20px; text-decoration:none; padding:12px 24px;">Back to Home</a>
                </div>
            <?php else: ?>

                <?php if ($error !== ""): ?>
                    <div style="background:#fdeaea; color:#b3261e; padding:14px; border-radius:10px; margin-bottom:20px; font-weight:600;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form class="report-form" id="dogReportForm" method="POST" action="report.php" enctype="multipart/form-data">
                    
                    <div class="form-section-header">
                        <div class="form-section-title">
                            <span class="step-num">1</span>
                            <h2>Reporter Information</h2>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="reporterName">Name</label>
                            <input type="text" id="reporterName" name="reporterName">
                        </div>
                        <div class="form-group">
                            <label for="reporterPhone">Phone Number</label>
                            <input type="tel" id="reporterPhone" name="reporterPhone">
                        </div>
                        <div class="form-group">
                            <label for="reporterEmail">Email</label>
                            <input type="email" id="reporterEmail" name="reporterEmail">
                        </div>
                    </div>

                    <div class="form-section-header">
                        <div class="form-section-title">
                            <span class="step-num">2</span>
                            <h2>Dog Information</h2>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="reportType">Report Type *</label>
                            <select id="reportType" name="reportType" required>
                                <option value="" disabled selected>Select report type</option>
                                <option value="Abandoned">Abandoned</option>
                                <option value="Stray">Stray</option>
                                <option value="Injured">Injured</option>
                                <option value="Sick">Sick</option>
                                <option value="Lost">Lost</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dogColor">Dog's Color *</label>
                            <input type="text" id="dogColor" name="dogColor" required>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="estimatedAge">Estimated Age</label>
                            <input type="text" id="estimatedAge" name="estimatedAge">
                        </div>
                        <div class="form-group">
                            <label for="dogGender">Gender</label>
                            <select id="dogGender" name="dogGender">
                                <option value="Unknown" selected>Unknown</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dogCondition">Dog Condition *</label>
                            <select id="dogCondition" name="dogCondition" required>
                                <option value="" disabled selected>Select condition</option>
                                <option value="Healthy">Healthy</option>
                                <option value="Injured">Injured</option>
                                <option value="Sick">Sick</option>
                                <option value="Weak">Weak</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="3" placeholder="Short description..." required></textarea>
                    </div>

                    <div class="form-section-header">
                        <div class="form-section-title">
                            <span class="step-num">3</span>
                            <h2>Location</h2>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="locationArea">Location / Area *</label>
                            <input type="text" id="locationArea" name="locationArea" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City / Municipality *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="landmark">Landmark *</label>
                            <input type="text" id="landmark" name="landmark" required>
                        </div>
                    </div>

                    <div class="form-section-header">
                        <div class="form-section-title">
                            <span class="step-num">4</span>
                            <h2>Photo</h2>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dog Photo *</label>
                        <div class="photo-upload-zone">
                            <span style="font-size:32px;">📷</span>
                            <h4>Upload Photo of the Dog</h4>
                            <div id="filePreviewName">Click to select photo</div>
                            <input type="file" id="dogPhoto" name="dogPhoto" accept="image/*" required onchange="const name=this.files[0]?.name; if(name) document.getElementById('filePreviewName').innerText = 'Selected: ' + name;">
                        </div>
                    </div>

                    <div class="form-section-header">
                        <div class="form-section-title">
                            <span class="step-num">5</span>
                            <h2>Submit</h2>
                        </div>
                    </div>

                    <div class="form-checkbox-group">
                        <input type="checkbox" id="agreeReport" required>
                        <label for="agreeReport">I confirm that the information provided is correct to the best of my knowledge.</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit-report">Submit Report</button>
                        <a href="index.php" class="btn-cancel-report">Cancel</a>
                    </div>

                </form>

            <?php endif; ?>

        </div>
    </main>

<!-- LOGOUT CONFIRMATION MODAL -->
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
    });
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>