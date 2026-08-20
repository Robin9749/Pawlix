<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);

$user_query = mysqli_query($conn, "SELECT * FROM user WHERE user_id=$user_id");
$user = ($user_query) ? mysqli_fetch_assoc($user_query) : [];

$unreadCount = 0;

$r1 = @mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM report_dogs 
     WHERE user_id = $user_id AND status != 'Pending'"
);

$r2 = @mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM adoption_application 
     WHERE user_id = $user_id AND status != 'Pending'"
);

$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;

$unreadCount = $c1 + $c2;

$first_name = htmlspecialchars($user['first_name'] ?? 'User');
$last_name  = htmlspecialchars($user['last_name'] ?? '');
$email      = htmlspecialchars($user['email'] ?? 'Not Provided');
$phone      = htmlspecialchars($user['phone'] ?? 'Not Provided');

$initials = strtoupper(
    substr($first_name, 0, 1) . substr($last_name, 0, 1)
);

if (empty(trim($initials))) {
    $initials = '👤';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Account Profile | PawLix</title>

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
    --orange-dark: #e06600;
    --navy: #0e1524;
    --border-color: #dfcfb0;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--pale-yellow);
    color: #222;
    margin: 0;
    padding: 0;
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
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 12px;
    margin-left: 2px;
}

.user-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 50px;
    background-color: var(--cream);
    min-width: 210px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-radius: 14px;
    overflow: hidden;
    z-index: 1000;
    border: 1px solid var(--border-color);
}

.user-dropdown-menu.show {
    display: block;
}

.user-dropdown-menu a {
    color: var(--dark-brown);
    padding: 12px 18px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease, color 0.2s ease;
}

.user-dropdown-menu a:hover {
    background-color: #dfcfb0;
    color: var(--maroon);
}

.user-dropdown-menu a .icon {
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.user-dropdown-menu .divider {
    height: 1px;
    background-color: var(--border-color);
    margin: 4px 0;
}

.user-dropdown-menu a.logout-link {
    color: var(--maroon);
}

.user-dropdown-menu a.logout-link:hover {
    background-color: #f8d7da;
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

.account-wrapper {
    min-height: calc(100vh - 250px);

    padding: 50px 20px;

    display: flex;

    justify-content: center;

    align-items: flex-start;
}

.account-card {
    max-width: 780px;

    width: 100%;

    background: var(--tan-card);

    border-radius: 20px;

    padding: 40px;

    box-shadow: 0 10px 30px rgba(0,0,0,0.06);

    border: 1px solid var(--border-color);
}

.profile-header-banner {
    display: flex;

    align-items: center;

    gap: 22px;

    margin-bottom: 30px;

    padding-bottom: 25px;

    border-bottom: 2px solid var(--border-color);
}

.avatar-circle {
    width: 72px;
    height: 72px;

    border-radius: 50%;

    background: var(--dark-brown);

    color: var(--cream);

    font-size: 26px;

    font-weight: 800;

    display: flex;

    align-items: center;

    justify-content: center;

    box-shadow: 0 6px 16px rgba(74,50,35,0.25);

    border: 3px solid var(--orange);

    flex-shrink: 0;
}

.profile-header-info h2 {
    margin: 0 0 6px 0;

    font-size: 24px;

    font-weight: 800;

    color: var(--dark-brown);
}

.profile-status-badge {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    background: #e1f5fe;

    color: #0288d1;

    font-size: 12.5px;

    font-weight: 700;

    padding: 4px 12px;

    border-radius: 20px;

    border: 1px solid #b3e5fc;
}

.profile-status-badge .dot {
    width: 8px;
    height: 8px;

    background: #0288d1;

    border-radius: 50%;
}

.info-box {
    background: var(--pale-yellow);

    padding: 28px;

    border-radius: 16px;

    border: 1px solid var(--border-color);
}

.info-row {
    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 16px 0;

    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    display: flex;

    align-items: center;

    gap: 10px;

    color: #665243;

    font-weight: 700;

    font-size: 13.5px;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}

.info-label .label-icon {
    font-size: 16px;

    width: 22px;
    height: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--cream);

    border-radius: 50%;

    color: var(--dark-brown);
}

.info-val {
    font-weight: 700;

    color: var(--dark-brown);

    font-size: 15.5px;
}

.account-actions {
    margin-top: 30px;

    display: flex;

    justify-content: flex-end;

    gap: 14px;
}

.btn-profile {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 12px 26px;

    border-radius: 50px;

    font-size: 14.5px;

    font-weight: 700;

    text-decoration: none;

    transition: all 0.3s ease;

    border: none;

    cursor: pointer;
}

.btn-home {
    background: var(--dark-brown);

    color: #fff;

    box-shadow: 0 4px 12px rgba(74,50,35,0.2);
}

.btn-home:hover {
    background: #2c1d14;

    transform: translateY(-2px);

    box-shadow: 0 6px 16px rgba(74,50,35,0.3);
}

.btn-edit {
    background: var(--orange);

    color: #fff;

    box-shadow: 0 4px 14px rgba(255,127,17,0.3);
}

.btn-edit:hover {
    background: var(--orange-dark);

    transform: translateY(-2px);

    box-shadow: 0 6px 18px rgba(255,127,17,0.45);
}

@media (max-width: 600px) {

    .account-card {
        padding: 25px 20px;
    }

    .profile-header-banner {
        flex-direction: column;
        text-align: center;
    }

    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .account-actions {
        flex-direction: column;
    }

    .btn-profile {
        width: 100%;
        justify-content: center;
    }

}

</style>
</head>

<body>

<header class="header">

    <div class="logo">

        <a href="index.php">

            <img src="assets/images/logo.png" alt="PawLix logo">

        </a>

    </div>


    <nav class="nav">

        <a href="index.php">Home</a>

        <a href="browse.php">Browse Dogs ▾</a>

        <a href="about.php">About</a>

        <a href="contact.php">Contact</a>

        <a href="report.php">Report a Dog</a>

    </nav>


    <div class="header-buttons">

        <div class="user-menu-wrapper">

            <button
                class="menu-icon-btn"
                id="userMenuBtn"
                onclick="toggleUserDropdown()"
                aria-label="User Account Menu">

                <span>👤</span>

                ▾

                <?php if ($unreadCount > 0): ?>

                    <span class="badge-count">
                        <?php echo $unreadCount; ?>
                    </span>

                <?php endif; ?>

            </button>


            <div
                class="user-dropdown-menu"
                id="userDropdownMenu">

                <a href="account.php">
                    <span class="icon">👤</span>
                    Account Profile
                </a>

                <a href="messages.php">
                    <span class="icon">✉️</span>
                    Messages

                    <?php if ($unreadCount > 0): ?>

                        <span class="badge-count">
                            <?php echo $unreadCount; ?>
                        </span>

                    <?php endif; ?>

                </a>

                <a href="notifications.php">
                    <span class="icon">🔔</span>
                    Notifications
                </a>

                <a href="history.php">
                    <span class="icon">📜</span>
                    Adoption History
                </a>

                <a href="settings.php">
                    <span class="icon">⚙️</span>
                    Settings
                </a>

                <div class="divider"></div>

                <a
                    href="#"
                    class="logout-trigger logout-link">

                    <span class="icon">🚪</span>
                    Logout

                </a>

            </div>

        </div>

    </div>


    <button
        class="menu-toggle"
        id="menuToggle"
        aria-label="Toggle menu">

        ☰

    </button>

</header>


<hr style="background-color: white; height: 1px; border: none;">


<main class="account-wrapper">

    <div class="account-card">

        <div class="profile-header-banner">

            <div class="avatar-circle">

                <?php echo $initials; ?>

            </div>


            <div class="profile-header-info">

                <h2>
                    My Account Profile
                </h2>

                <div class="profile-status-badge">

                    <span class="dot"></span>

                    Logged In as
                    <?php echo $first_name . ' ' . $last_name; ?>

                </div>

            </div>

        </div>


        <div class="info-box">

            <div class="info-row">

                <span class="info-label">

                    <span class="label-icon">👤</span>

                    First Name

                </span>

                <span class="info-val">
                    <?php echo $first_name; ?>
                </span>

            </div>


            <div class="info-row">

                <span class="info-label">

                    <span class="label-icon">👤</span>

                    Last Name

                </span>

                <span class="info-val">

                    <?php
                    echo !empty($last_name)
                        ? $last_name
                        : '—';
                    ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">

                    <span class="label-icon">✉️</span>

                    Email Address

                </span>

                <span class="info-val">

                    <?php echo $email; ?>

                </span>

            </div>


            <div class="info-row">

                <span class="info-label">

                    <span class="label-icon">📞</span>

                    Phone Number

                </span>

                <span class="info-val">

                    <?php echo $phone; ?>

                </span>

            </div>

        </div>


        <div class="account-actions">

            <a
                href="index.php"
                class="btn-profile btn-home">

                <span>🏠</span>

                Back to Home

            </a>


            <a
                href="settings.php"
                class="btn-profile btn-edit">

                <span>⚙️</span>

                Edit Profile

            </a>

        </div>

    </div>

</main>


<div
    class="logout-modal-overlay"
    id="logoutModal">

    <div
        class="logout-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logoutTitle">

        <h2 id="logoutTitle">
            Log Out?
        </h2>

        <p>
            Are you sure you want to log out?
        </p>

        <div class="logout-modal-actions">

            <button
                type="button"
                class="logout-cancel"
                id="cancelLogout">

                Cancel

            </button>


            <a
                href="logout.php?confirm=true"
                class="logout-confirm">

                Log Out

            </a>

        </div>

    </div>

</div>


<?php

if (file_exists('includes/footer.php')) {

    include 'includes/footer.php';

} else {

?>

<footer class="footer">

    <div class="footer-container">

        <div class="footer-columns">

            <div class="footer-col col-brand">

                <h4 class="col-title">
                    PAWLIX
                </h4>

                <p class="brand-text">
                    Connecting dogs waiting for rescue with loving,
                    permanent families across Nepal through a simple
                    and secure platform.
                </p>

            </div>


            <div class="footer-col">

                <h4 class="col-title">
                    SERVICES
                </h4>

                <p>
                    <a href="browse.php">
                        Browse Dogs
                    </a>
                </p>

                <p>
                    <a href="adopt.php">
                        Apply for Adoption
                    </a>
                </p>

                <p>
                    <a href="report.php">
                        Report Stray / Injured
                    </a>
                </p>

                <p>
                    <a href="contact.php">
                        Support
                    </a>
                </p>

            </div>


            <div class="footer-col">

                <h4 class="col-title">
                    USEFUL LINKS
                </h4>

                <p>
                    <a href="index.php">
                        Home
                    </a>
                </p>

                <p>
                    <a href="about.php">
                        About Us
                    </a>
                </p>

                <p>
                    <a href="contact.php">
                        Contact Us
                    </a>
                </p>

            </div>


            <div class="footer-col col-contact">

                <h4 class="col-title">
                    CONTACT
                </h4>

                <p>
                    <span class="icon">📍</span>
                    Kathmandu, Nepal
                </p>

                <p>
                    <span class="icon">✉</span>
                    support@pawlix.org
                </p>

                <p>
                    <span class="icon">📞</span>
                    +977 9800000000
                </p>

            </div>

        </div>


        <hr class="footer-hr">


        <div class="footer-bottom">

            <p class="copyright">
                © <?php echo date('Y'); ?> PawLix.
                All rights reserved.
            </p>

        </div>

    </div>

</footer>

<?php } ?>


<script>

function toggleUserDropdown() {

    const menu =
        document.getElementById("userDropdownMenu");

    if (menu) {

        menu.classList.toggle("show");

    }

}


window.addEventListener("click", function(e) {

    const btn =
        document.getElementById("userMenuBtn");

    const menu =
        document.getElementById("userDropdownMenu");

    if (
        menu &&
        btn &&
        !btn.contains(e.target) &&
        !menu.contains(e.target)
    ) {

        menu.classList.remove("show");

    }

});


document.addEventListener("DOMContentLoaded", function() {

    const logoutModal =
        document.getElementById("logoutModal");

    const cancelLogout =
        document.getElementById("cancelLogout");

    const logoutTrigger =
        document.querySelector(".logout-trigger");


    if (logoutTrigger) {

        logoutTrigger.addEventListener("click", function(e) {

            e.preventDefault();

            logoutModal.classList.add("show");

            const dropdown =
                document.getElementById("userDropdownMenu");

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

        if (
            e.key === "Escape" &&
            logoutModal &&
            logoutModal.classList.contains("show")
        ) {

            logoutModal.classList.remove("show");

        }

    });

});

</script>


<script src="assets/js/script.js"></script>

</body>
</html>