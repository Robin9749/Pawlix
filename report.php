<?php
include("config/config.php");

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

           $sql = "INSERT INTO report_dogs
    (name, phone_number, email, report_type, dog_color, estimated_age, gender, dog_condition, description, location_area, city_municipality, landmark, dog_photo, status)
    VALUES
    ('$reporterName', '$reporterPhone', '$reporterEmail', '$reportType', '$dogColor', '$estimatedAge', '$dogGender', '$dogCondition', '$description', '$locationArea', '$city', '$landmark', '$photo_db', 'Pending')";

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
        .nav{ display:flex; align-items:center; gap:45px; }
        .nav a{ font-size:16px; font-weight:500; color:var(--dark-brown); transition:.3s; position:relative; }
        .nav a:hover, .nav .active{ color:var(--maroon); }

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
            <button class="btn btn-outline">Sign Up</button>
            <button class="btn btn-dark">Login</button>
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
                        <a href="index.html" class="btn-cancel-report">Cancel</a>
                    </div>

                </form>

            <?php endif; ?>

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

    <script src="assets/js/script.js"></script>
</body>
</html>
