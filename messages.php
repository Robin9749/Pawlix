<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/config.php";

$user_id = intval($_SESSION['user_id']);

if (!function_exists('safeAddColumnMessages')) {
    function safeAddColumnMessages($conn, $table, $column, $definition) {
        try {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {}
    }
}

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL DEFAULT 1,
    sender_type ENUM('user', 'admin') NOT NULL,
    subject VARCHAR(255) DEFAULT '',
    message TEXT NOT NULL,
    image TEXT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

safeAddColumnMessages($conn, 'messages', 'image', "TEXT DEFAULT NULL");

$user_query = mysqli_query($conn, "SELECT * FROM user WHERE user_id = $user_id");
$user = ($user_query) ? mysqli_fetch_assoc($user_query) : [];
$first_name = htmlspecialchars($user['first_name'] ?? 'User');
$full_name = htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));

$admin_query = mysqli_query($conn, "SELECT admin_id, name, email FROM admin ORDER BY admin_id ASC LIMIT 1");
$admin = ($admin_query) ? mysqli_fetch_assoc($admin_query) : null;
$admin_id = $admin ? intval($admin['admin_id']) : 1;

$message_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_text = trim($_POST['message'] ?? '');
    $subject = trim($_POST['subject'] ?? 'PawLix Support');

    $uploaded_image = "";
    if (!empty($_FILES['image']['name'])) {
        $upload_folder = "uploads/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        $img_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $err = $_FILES['image']['error'];

        if ($err === UPLOAD_ERR_OK && !empty($img_name)) {
            $clean_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($img_name));
            $uploaded_image = time() . "_user_" . $clean_filename;
            move_uploaded_file($tmp_name, $upload_folder . $uploaded_image);
        }
    }

    if ($message_text !== '' || $uploaded_image !== '') {
        $message_text_db = mysqli_real_escape_string($conn, $message_text);
        $subject_db = mysqli_real_escape_string($conn, $subject);
        $image_db = mysqli_real_escape_string($conn, $uploaded_image);

        $insert_sql = "
            INSERT INTO messages (user_id, admin_id, sender_type, subject, message, image, is_read)
            VALUES ($user_id, $admin_id, 'user', '$subject_db', '$message_text_db', '$image_db', 0)
        ";

        if (mysqli_query($conn, $insert_sql)) {
            header("Location: messages.php?sent=1");
            exit();
        } else {
            $message_error = "Unable to send message. Please try again.";
        }
    } elseif (isset($_POST['send_message']) || isset($_POST['submitted']) || isset($_POST['send_message_btn'])) {
        $message_error = "Please enter a message or select an image to send.";
    }
}

@mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE user_id = $user_id AND sender_type = 'admin'");

$messages = mysqli_query($conn, "
    SELECT message_id, user_id, admin_id, sender_type, subject, message, image, is_read, created_at
    FROM messages
    WHERE user_id = $user_id
    ORDER BY created_at ASC, message_id ASC
");

$r1 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM report_dogs WHERE user_id = $user_id AND status != 'Pending'");
$r2 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application WHERE user_id = $user_id AND status != 'Pending'");
$r3 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE user_id = $user_id AND sender_type = 'admin' AND is_read = 0");
$c1 = ($r1) ? mysqli_fetch_assoc($r1)['total'] : 0;
$c2 = ($r2) ? mysqli_fetch_assoc($r2)['total'] : 0;
$c3 = ($r3) ? mysqli_fetch_assoc($r3)['total'] : 0;
$unreadCount = $c1 + $c2 + $c3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages & Support | PawLix</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    --border-color: #dfcfb0;
    --online-green: #2ecc71;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--pale-yellow);
    color: #222;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
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

.menu-icon-btn:hover { background: #dccfad; }

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

.user-dropdown-menu.show { display: block; }

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

.user-dropdown-menu a:hover { background-color: #ddceac; }

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

.logout-link { color: #b3261e !important; }
.logout-link:hover { background-color: #f8d7da !important; }

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

.logout-modal-overlay.show { display: flex; }

.logout-modal {
  width: 100%;
  max-width: 400px;
  background: #ede1c6;
  border-radius: 16px;
  padding: 32px 28px;
  text-align: center;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.30);
  border: 1px solid rgba(255, 255, 255, 0.6);
  animation: logoutPopup 0.25s ease-out;
}

@keyframes logoutPopup {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.logout-modal h2 { font-size: 22px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px; }
.logout-modal p { font-size: 14px; color: #555555; margin-bottom: 26px; line-height: 1.5; }
.logout-modal-actions { display: flex; gap: 12px; }

.logout-cancel, .logout-confirm {
  flex: 1; padding: 13px; border-radius: 10px; font-family: inherit; font-size: 14px;
  font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; text-align: center;
}

.logout-cancel { background: #ffffff; color: #2b2b2b; border: 1px solid #ddccae; }
.logout-cancel:hover { background: #f5ecda; }
.logout-confirm { background: #b3261e; color: #ffffff; border: none; box-shadow: 0 4px 12px rgba(179, 38, 30, 0.3); }
.logout-confirm:hover { background: #961e17; }

.messages-wrapper {
    flex: 1;
    padding: 30px 20px 40px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.chat-card {
    width: 100%;
    max-width: 940px;
    height: 680px;
    max-height: calc(100vh - 160px);
    background: var(--tan-card);
    border: 1px solid var(--border-color);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 12px 35px rgba(74,50,35,0.08);
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: var(--cream);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.admin-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: var(--dark-brown);
    color: var(--cream);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 700;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: var(--online-green);
    border: 2px solid #ffffff;
    border-radius: 50%;
}

.chat-user-info h3 {
    margin: 0;
    font-size: 16.5px;
    color: var(--dark-brown);
    font-weight: 700;
}

.chat-user-info p {
    margin: 2px 0 0;
    font-size: 12px;
    color: #27ae60;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.chat-user-info p::before {
    content: '';
    display: inline-block;
    width: 7px;
    height: 7px;
    background: var(--online-green);
    border-radius: 50%;
}

.header-tag {
    background: #f0e4c7;
    color: var(--dark-brown);
    font-size: 12px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid #dfcfb0;
}

.chat-body {
    flex: 1;
    padding: 24px 28px;
    overflow-y: auto;
    background: #f7efdd;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chat-body::-webkit-scrollbar { width: 6px; }
.chat-body::-webkit-scrollbar-thumb { background: #d2c2a2; border-radius: 10px; }

.empty-chat {
    margin: auto;
    text-align: center;
    max-width: 380px;
    color: #7a6350;
    padding: 30px 20px;
}
.empty-chat-icon { font-size: 50px; margin-bottom: 12px; }
.empty-chat h3 { margin: 0 0 6px; color: var(--dark-brown); font-size: 19px; font-weight: 700; }
.empty-chat p { font-size: 13.5px; line-height: 1.6; color: #6b5544; }

.message-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    width: 100%;
}

.message-row.user { justify-content: flex-end; }
.message-row.admin { justify-content: flex-start; }

.user-avatar-small {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--dark-brown);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.message-bubble-container {
    max-width: 70%;
    display: flex;
    flex-direction: column;
}

.sender-meta {
    font-size: 11px;
    font-weight: 600;
    color: #8c735d;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.message-row.user .sender-meta { justify-content: flex-end; }

.message-bubble {
    padding: 12px 18px 9px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.55;
    position: relative;
    box-shadow: 0 3px 10px rgba(0,0,0,.04);
}

.message-row.admin .message-bubble {
    background: #ffffff;
    color: #3b2719;
    border: 1px solid #e4d5b9;
    border-bottom-left-radius: 4px;
}

.message-row.user .message-bubble {
    background: var(--orange);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-text {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-img-container { margin-top: 8px; }
.message-img {
    max-width: 250px;
    max-height: 250px;
    border-radius: 12px;
    object-fit: cover;
    display: block;
    cursor: pointer;
    border: 2px solid rgba(255,255,255,0.4);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    transition: transform 0.2s;
}

.message-img:hover { transform: scale(1.03); }

.message-subject {
    font-weight: 700;
    margin-bottom: 6px;
    font-size: 13.5px;
    border-bottom: 1px dashed rgba(0,0,0,0.12);
    padding-bottom: 4px;
}

.message-row.user .message-subject { border-bottom-color: rgba(255,255,255,0.35); }

.message-time {
    display: block;
    text-align: right;
    font-size: 10px;
    opacity: .75;
    margin-top: 4px;
}

.chat-composer {
    padding: 14px 20px;
    background: var(--cream);
    border-top: 1px solid var(--border-color);
}

.image-preview-bar {
    display: none;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    padding: 6px 12px;
    border-radius: 10px;
    border: 1px solid #d8c6a5;
    margin-bottom: 8px;
    width: fit-content;
}

.image-preview-bar.show { display: flex; }
.image-preview-bar img { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; }
.image-preview-bar span { font-size: 12px; font-weight: 600; color: #4a3223; }
.remove-img-btn { background: none; border: none; font-weight: bold; color: #b3261e; cursor: pointer; font-size: 16px; margin-left: 6px; }

.composer-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.attach-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid #d8c6a5;
    background: #fffaf0;
    color: #5c4320;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.attach-btn:hover { background: #f0e4c7; border-color: var(--orange); color: var(--orange); }

.composer-input {
    flex: 1;
    resize: none;
    height: 46px;
    max-height: 110px;
    border: 1px solid #d8c6a5;
    border-radius: 24px;
    padding: 12px 18px;
    font-family: inherit;
    font-size: 14px;
    background: #fffaf0;
    outline: none;
    color: #222;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.composer-input:focus {
    border-color: var(--orange);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(255,127,17,0.12);
}

.send-btn {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    border: none;
    background: var(--orange);
    color: white;
    font-size: 17px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(255,127,17,0.3);
}

.send-btn:hover {
    background: var(--orange-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255,127,17,0.45);
}

.chat-alert {
    margin: 0 0 10px;
    padding: 9px 14px;
    border-radius: 10px;
    background: #f8d7da;
    color: #721c24;
    font-size: 13px;
    font-weight: 600;
}

@media (max-width: 650px) {
    .messages-wrapper { padding: 15px 10px; }
    .chat-card { height: calc(100vh - 150px); border-radius: 16px; }
    .chat-body { padding: 16px 12px; }
    .message-bubble-container { max-width: 84%; }
    .chat-header { padding: 12px 16px; }
    .header-tag { display: none; }
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
        <a href="browse.php">Browse Dogs <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 2px;"></i></a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="report.php">Report a Dog</a>
    </nav>

    <div class="header-buttons">
      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-menu-wrapper">
          <button class="menu-icon-btn" id="userMenuBtn" onclick="toggleUserDropdown()" type="button" aria-label="User Menu">
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

    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
</header>

<hr style="background-color:white;height:1px;border:none;">

<main class="messages-wrapper">
    <div class="chat-card">

        <div class="chat-header">
            <div class="header-left">
                <div class="avatar-wrapper">
                    <div class="admin-avatar">🐾</div>
                    <span class="online-dot"></span>
                </div>

                <div class="chat-user-info">
                    <h3>PawLix Shelter Team</h3>
                    <p>Official Shelter Support</p>
                </div>
            </div>

            <div class="header-tag">
                Live Support
            </div>
        </div>

        <div class="chat-body" id="chatBody">
            <?php if (!$messages || mysqli_num_rows($messages) === 0): ?>
                <div class="empty-chat">
                    <div class="empty-chat-icon">🐾</div>
                    <h3>Start a conversation</h3>
                    <p>Have a question about dog adoption, rescue status, or PawLix shelter services? Send us a message or photo below and our team will reply directly to your thread!</p>
                </div>
            <?php else: ?>

                <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
                    <?php 
                        $isUser = ($msg['sender_type'] === 'user');
                        $user_initial = strtoupper(substr($user['first_name'] ?? 'U', 0, 1));
                        $formatted_time = date('M d, g:i A', strtotime($msg['created_at']));
                    ?>
                    <div class="message-row <?php echo $isUser ? 'user' : 'admin'; ?>">
                        
                        <?php if (!$isUser): ?>
                            <div class="user-avatar-small" style="background:var(--dark-brown);">🐾</div>
                        <?php endif; ?>

                        <div class="message-bubble-container">
                            <div class="sender-meta">
                                <span><?php echo $isUser ? htmlspecialchars($full_name ? $full_name : 'You') : 'PawLix Shelter Admin'; ?></span>
                            </div>

                            <div class="message-bubble">
                                <?php if (!empty($msg['subject']) && $msg['subject'] !== 'PawLix Support'): ?>
                                    <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <?php endif; ?>

                                <?php if (!empty($msg['message'])): ?>
                                    <p class="message-text"><?php echo htmlspecialchars($msg['message']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($msg['image'])): ?>
                                    <div class="message-img-container">
                                        <img src="uploads/<?php echo htmlspecialchars($msg['image']); ?>" class="message-img" onclick="window.open(this.src)" alt="Chat Image Attachment">
                                    </div>
                                <?php endif; ?>

                                <span class="message-time"><?php echo $formatted_time; ?></span>
                            </div>
                        </div>

                        <?php if ($isUser): ?>
                            <div class="user-avatar-small" style="background:var(--orange);"><?php echo $user_initial; ?></div>
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>

            <?php endif; ?>
        </div>

        <div class="chat-composer">
            <?php if ($message_error): ?>
                <div class="chat-alert"><?php echo htmlspecialchars($message_error); ?></div>
            <?php endif; ?>

            <div class="image-preview-bar" id="imgPreviewBar">
                <img id="imgPreviewThumb" src="" alt="Preview">
                <span id="imgPreviewName">image.jpg</span>
                <button type="button" class="remove-img-btn" onclick="clearSelectedImage()">&times;</button>
            </div>

            <form method="POST" action="messages.php" enctype="multipart/form-data" class="composer-form" id="chatForm">
                <input type="hidden" name="send_message" value="1">
                <input type="hidden" name="subject" value="PawLix Support">

                <input type="file" name="image" id="imageInput" accept="image/*" style="display:none;" onchange="handleImageSelection(this)">

                <button type="button" class="attach-btn" onclick="document.getElementById('imageInput').click();" title="Attach Image">📷</button>

                <textarea
                    name="message"
                    class="composer-input"
                    placeholder="Type a message or attach photo..."
                    rows="1"
                    id="messageInput"
                ></textarea>

                <button type="submit" name="send_message_btn" id="sendBtn" class="send-btn" title="Send message">
                    ➤
                </button>
            </form>
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

<?php
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
?>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-columns">
            <div class="footer-col col-brand">
                <h4 class="col-title">PAWLIX</h4>
                <p class="brand-text">Connecting dogs waiting for rescue with loving, permanent families across Nepal through a simple and secure platform.</p>
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
            </div>
        </div>
        <hr class="footer-hr">
        <div class="footer-bottom">
            <p class="copyright">© <?php echo date('Y'); ?> PawLix. All rights reserved.</p>
        </div>
    </div>
</footer>
<?php } ?>

<script>
function toggleUserDropdown() {
    const menu = document.getElementById("userDropdownMenu");
    if (menu) { menu.classList.toggle("show"); }
}

window.addEventListener("click", function(e) {
    const btn = document.getElementById("userMenuBtn");
    const menu = document.getElementById("userDropdownMenu");
    if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove("show");
    }
});

document.addEventListener("DOMContentLoaded", function() {
    const chatBody = document.getElementById("chatBody");
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    const logoutModal = document.getElementById("logoutModal");
    const cancelLogout = document.getElementById("cancelLogout");
    const logoutTrigger = document.querySelector(".logout-trigger");

    if (logoutTrigger) {
        logoutTrigger.addEventListener("click", function(e) {
            e.preventDefault();
            logoutModal.classList.add("show");
            const dropdown = document.getElementById("userDropdownMenu");
            if (dropdown) { dropdown.classList.remove("show"); }
        });
    }

    if (cancelLogout) {
        cancelLogout.addEventListener("click", function() {
            logoutModal.classList.remove("show");
        });
    }

    if (logoutModal) {
        logoutModal.addEventListener("click", function(e) {
            if (e.target === logoutModal) { logoutModal.classList.remove("show"); }
        });
    }

    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && logoutModal && logoutModal.classList.contains("show")) {
            logoutModal.classList.remove("show");
        }
    });

    const messageInput = document.getElementById("messageInput");
    const chatForm = document.getElementById("chatForm");

    if (messageInput && chatForm) {
        messageInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                const imageInput = document.getElementById("imageInput");
                if (this.value.trim() !== '' || (imageInput && imageInput.files.length > 0)) {
                    chatForm.submit();
                }
            }
        });
    }
});

function handleImageSelection(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("imgPreviewThumb").src = e.target.result;
            document.getElementById("imgPreviewName").innerText = input.files[0].name;
            document.getElementById("imgPreviewBar").classList.add("show");
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearSelectedImage() {
    const input = document.getElementById("imageInput");
    if (input) { input.value = ""; }
    document.getElementById("imgPreviewBar").classList.remove("show");
}
</script>

<script src="assets/js/script.js"></script>
</body>
</html>