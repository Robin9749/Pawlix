<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$admin_id = intval($_SESSION['admin_id']);

/* SAFE HELPER TO CREATE TABLE & ADD IMAGE COLUMN */
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

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/* ================= GET ADMIN DETAILS ================= */
$admin_query = mysqli_query($conn, "SELECT * FROM admin WHERE admin_id = $admin_id");
$admin = $admin_query ? mysqli_fetch_assoc($admin_query) : [];

/* ================= SELECT CONVERSATION ================= */
$selected_user_id = intval($_GET['user_id'] ?? 0);

/* ================= SEND ADMIN MESSAGE WITH OPTIONAL IMAGE ================= */
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_user_id = intval($_POST['recipient_user_id'] ?? 0);
    $message_text = trim($_POST['message'] ?? '');
    $subject = trim($_POST['subject'] ?? 'PawLix Support Response');

    // Handle Image Upload
    $uploaded_image = "";
    if (!empty($_FILES['image']['name'])) {
        $upload_folder = "../uploads/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        $img_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $err = $_FILES['image']['error'];

        if ($err === UPLOAD_ERR_OK && !empty($img_name)) {
            $clean_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($img_name));
            $uploaded_image = time() . "_admin_" . $clean_filename;
            move_uploaded_file($tmp_name, $upload_folder . $uploaded_image);
        }
    }

    if ($recipient_user_id <= 0 || ($message_text === '' && $uploaded_image === '')) {
        if (isset($_POST['send_admin_message']) || isset($_POST['submitted']) || isset($_POST['send_admin_message_btn'])) {
            $error = "Please enter a message or choose an image to send.";
        }
    } else {
        $user_check = mysqli_query($conn, "SELECT user_id FROM user WHERE user_id = $recipient_user_id LIMIT 1");
        if (!$user_check || mysqli_num_rows($user_check) === 0) {
            $error = "User account not found.";
        } else {
            $message_db = mysqli_real_escape_string($conn, $message_text);
            $subject_db = mysqli_real_escape_string($conn, $subject);
            $image_db = mysqli_real_escape_string($conn, $uploaded_image);

            $insert = "
                INSERT INTO messages (user_id, admin_id, sender_type, subject, message, image, is_read)
                VALUES ($recipient_user_id, $admin_id, 'admin', '$subject_db', '$message_db', '$image_db', 0)
            ";

            if (mysqli_query($conn, $insert)) {
                header("Location: messages.php?user_id=" . $recipient_user_id . "&sent=1");
                exit();
            } else {
                $error = "Unable to send message: " . mysqli_error($conn);
            }
        }
    }
}

/* ================= GET ALL CONVERSATIONS ================= */
$conversations = mysqli_query($conn, "
    SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.email,
        u.phone,
        (
            SELECT m.message 
            FROM messages m 
            WHERE m.user_id = u.user_id 
            ORDER BY m.created_at DESC, m.message_id DESC 
            LIMIT 1
        ) AS last_message,
        (
            SELECT m.image
            FROM messages m 
            WHERE m.user_id = u.user_id 
            ORDER BY m.created_at DESC, m.message_id DESC 
            LIMIT 1
        ) AS last_message_image,
        (
            SELECT m.created_at 
            FROM messages m 
            WHERE m.user_id = u.user_id 
            ORDER BY m.created_at DESC, m.message_id DESC 
            LIMIT 1
        ) AS last_message_time,
        (
            SELECT COUNT(*) 
            FROM messages m 
            WHERE m.user_id = u.user_id 
              AND m.sender_type = 'user' 
              AND m.is_read = 0
        ) AS unread_count
    FROM user u
    WHERE EXISTS (
        SELECT 1 
        FROM messages m2 
        WHERE m2.user_id = u.user_id
    )
    ORDER BY last_message_time DESC, u.user_id DESC
");

/* ================= MARK USER MESSAGES AS READ WHEN SELECTED ================= */
if ($selected_user_id > 0) {
    @mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE user_id = $selected_user_id AND sender_type = 'user'");
}

/* ================= SELECTED USER DETAILS ================= */
$selected_user = null;
if ($selected_user_id > 0) {
    $selected_query = mysqli_query($conn, "SELECT user_id, first_name, last_name, email, phone FROM user WHERE user_id = $selected_user_id LIMIT 1");
    if ($selected_query) {
        $selected_user = mysqli_fetch_assoc($selected_query);
    }
}

/* ================= SELECTED CHAT MESSAGES ================= */
$chat_messages = null;
if ($selected_user_id > 0) {
    $chat_messages = mysqli_query($conn, "SELECT * FROM messages WHERE user_id = $selected_user_id ORDER BY created_at ASC, message_id ASC");
}

/* ================= TOTAL UNREAD MESSAGES FOR ADMIN ================= */
$unread_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM messages WHERE sender_type = 'user' AND is_read = 0");
$totalUnread = 0;
if ($unread_result) {
    $row = mysqli_fetch_assoc($unread_result);
    $totalUnread = intval($row['total']);
}

/* ================= PUBLIC CONTACT INQUIRIES ================= */
$public_inquiries = mysqli_query($conn, "SELECT * FROM contact_message ORDER BY created_at DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages Center | PawLix Admin</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

:root {
    --bg-body: #e8dcc0;
    --bg-topbar: #f2e6c9;
    --bg-card: #ede1c6;
    --bg-sidebar: #e8dcc0;
    --bg-sidebar-hover: #ddceac;
    --primary-orange: #f2932b;
    --primary-blue: #1f6fd6;
    --text-dark: #2b2b2b;
    --text-muted: #665444;
    --border-color: #ddccae;
    --online-green: #2ecc71;
    --radius-md: 12px;
    --radius-lg: 18px;
    --font-family: 'Poppins', Arial, sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body { height: 100%; font-family: var(--font-family); background: var(--bg-body); color: var(--text-dark); }

body { display: flex; flex-direction: column; min-height: 100vh; }

.topbar{
  background: #f2e6c9;
  height: 75px;
  padding: 0 55px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  box-sizing: border-box;
}

.topbar .logo{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  height: 100%;
}

.topbar .logo img{
  width: 110px;
  display: block;
  position: static;
  padding-top: 20px;
}

.topbar .logout { color: var(--primary-blue); text-decoration: none; font-weight: 600; font-size: 14px; }
.topbar .logout:hover { text-decoration: underline; }

.layout { display: flex; flex: 1; height: calc(100vh - 75px); }

.sidebar {
    width: 240px;
    background: var(--bg-sidebar);
    padding: 20px 14px;
    border-right: 1px solid rgba(255,255,255,.5);
    flex-shrink: 0;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 15px;
    margin-bottom: 6px;
    border-radius: 10px;
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.sidebar a:hover { background: var(--bg-sidebar-hover); }
.sidebar a.active { background: var(--primary-orange); color: white; box-shadow: 0 4px 12px rgba(242,147,43,0.35); }
.sidebar .icon { width: 20px; text-align: center; font-size: 16px; }

.main {
    flex: 1;
    padding: 24px 32px;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.page-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.page-title h1 { margin: 0; font-size: 24px; font-weight: 700; color: #1a1a1a; }

.unread-pill {
    background: var(--primary-orange);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(242,147,43,0.3);
}

.chat-layout {
    flex: 1;
    display: flex;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.06);
    border: 1px solid var(--border-color);
    min-height: 0;
}

/* CONVERSATION LIST (LEFT PANEL) */
.conversation-list {
    width: 360px;
    background: #e8dcc0;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.conversation-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border-color);
    background: #ebdcb8;
}

.conversation-header h3 { margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: var(--text-dark); }

.search-box-wrapper { position: relative; }
.search-box-wrapper input {
    width: 100%;
    padding: 9px 14px 9px 36px;
    border: 1px solid #dfcfb0;
    border-radius: 20px;
    background: #f5ecd7;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s;
}

.search-box-wrapper input:focus { background: #ffffff; border-color: var(--primary-orange); box-shadow: 0 0 0 3px rgba(242,147,43,0.15); }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #806d5c; }

.conversations-scroll {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.conversations-scroll::-webkit-scrollbar { width: 5px; }
.conversations-scroll::-webkit-scrollbar-thumb { background: #cbb997; border-radius: 10px; }

.inquiry-section-title {
    padding: 10px 18px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    background: #dcc9a3;
    color: #4a3223;
    border-bottom: 1px solid var(--border-color);
    border-top: 1px solid var(--border-color);
}

.conversation {
    display: block;
    padding: 14px 18px;
    text-decoration: none;
    color: var(--text-dark);
    border-bottom: 1px solid rgba(221,204,174,0.6);
    transition: background 0.2s;
}

.conversation:hover { background: #ddceac; }
.conversation.active { background: var(--primary-orange); color: white; }

.conversation-top { display: flex; align-items: center; gap: 12px; }

.avatar-container { position: relative; flex-shrink: 0; }
.avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #d2c29f;
    color: #5c4320;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    border: 1.5px solid white;
}

.conversation.active .avatar { background: rgba(255,255,255,0.25); color: white; border-color: rgba(255,255,255,0.5); }
.status-dot { position: absolute; bottom: 1px; right: 1px; width: 11px; height: 11px; background: var(--online-green); border: 2px solid white; border-radius: 50%; }

.conversation-info { min-width: 0; flex: 1; }
.conversation-name { font-size: 13.5px; font-weight: 700; line-height: 1.2; }
.conversation-preview { font-size: 12px; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 3px; }

.conversation-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.conversation-time { font-size: 10.5px; opacity: 0.8; white-space: nowrap; }
.unread-badge { background: #e63946; color: white; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 10.5px; font-weight: 700; }

.inquiry-item {
    padding: 14px 18px;
    border-bottom: 1px solid rgba(221,204,174,0.6);
    font-size: 12px;
    background: #f4eaaf;
    transition: background 0.2s;
}

.inquiry-item:hover { background: #ebdca0; }
.inquiry-item strong { color: #1a1a1a; font-size: 12.5px; }
.inquiry-item p { margin: 4px 0 4px; color: #443427; line-height: 1.45; word-break: break-word; }
.inquiry-item .date { font-size: 10px; color: #7a6350; font-weight: 600; display: block; margin-top: 4px; }

.empty-list { padding: 24px 18px; text-align: center; color: #806d5c; font-size: 12.5px; }

/* CHAT WINDOW (RIGHT PANEL) */
.chat-window { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f5ecd7; }

.chat-header {
    height: 65px;
    background: var(--bg-topbar);
    border-bottom: 1px solid var(--border-color);
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}

.chat-header .avatar { width: 42px; height: 42px; font-size: 15px; }
.chat-header-info h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--text-dark); }
.chat-header-info p { margin: 1px 0 0; font-size: 11.5px; color: var(--online-green); font-weight: 600; }

/* CHAT BODY SCROLL */
.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    overscroll-behavior: contain;
}

.chat-body::-webkit-scrollbar { width: 6px; }
.chat-body::-webkit-scrollbar-thumb { background: #cbb997; border-radius: 10px; }

.message-row { display: flex; gap: 12px; align-items: flex-end; width: 100%; }
.message-row.user { justify-content: flex-start; }
.message-row.admin { justify-content: flex-end; }

.message-bubble-wrapper { max-width: 68%; display: flex; flex-direction: column; }
.message-sender-name { font-size: 11px; font-weight: 700; color: #7a6350; margin-bottom: 4px; }
.message-row.admin .message-sender-name { text-align: right; }

.message-bubble {
    padding: 12px 16px 10px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.5;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    position: relative;
}

.message-row.user .message-bubble {
    background: #ffffff;
    color: #2b2b2b;
    border: 1px solid #e0d0b4;
    border-bottom-left-radius: 4px;
}

.message-row.admin .message-bubble {
    background: var(--primary-orange);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-text { margin: 0; white-space: pre-wrap; word-break: break-word; }

/* CHAT ATTACHED IMAGE STYLING */
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
    font-size: 13px;
    border-bottom: 1px dashed rgba(0,0,0,0.12);
    padding-bottom: 4px;
}

.message-row.admin .message-subject { border-bottom-color: rgba(255,255,255,0.35); }
.message-time { display: block; text-align: right; font-size: 10px; opacity: 0.75; margin-top: 4px; }

/* COMPOSER */
.composer {
    padding: 14px 22px;
    background: var(--bg-topbar);
    border-top: 1px solid var(--border-color);
    flex-shrink: 0;
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

.composer-form { display: flex; align-items: flex-end; gap: 10px; }

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

.attach-btn:hover { background: #f0e4c7; border-color: var(--primary-orange); color: var(--primary-orange); }

.composer-input {
    flex: 1;
    resize: none;
    min-height: 46px;
    max-height: 120px;
    border: 1px solid #d8c6a5;
    border-radius: 22px;
    padding: 12px 18px;
    font-family: inherit;
    font-size: 13.5px;
    background: #fffaf0;
    outline: none;
    color: #222;
    transition: all 0.2s;
}

.composer-input:focus { border-color: var(--primary-orange); background: #ffffff; box-shadow: 0 0 0 3px rgba(242,147,43,0.18); }

.send-button {
    width: 46px;
    height: 46px;
    border: none;
    border-radius: 50%;
    background: var(--primary-orange);
    color: white;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s, background 0.2s;
    box-shadow: 0 4px 12px rgba(242,147,43,0.35);
}

.send-button:hover { background: #e06600; transform: translateY(-1px); }

.no-conversation {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #806d5c;
    padding: 20px;
}

.no-conversation-icon { font-size: 52px; margin-bottom: 12px; }
.no-conversation h2 { color: #4a3223; margin: 0 0 6px 0; font-size: 20px; font-weight: 700; }
.no-conversation p { font-size: 13.5px; margin: 0; }
</style>
</head>

<body>

<div class="topbar">
    <div class="logo">
      <img src="../assets/images/logo.png" alt="PawLix logo">
    </div>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="layout">
    <div class="sidebar">
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="dogs.php"><span class="icon">🐾</span> Dogs</a>
        <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
        <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
        <a href="messages.php" class="active">
            <span class="icon">✉️</span> Messages
            <?php if ($totalUnread > 0): ?>
                <span class="unread-badge" style="margin-left:auto;"><?php echo $totalUnread; ?></span>
            <?php endif; ?>
        </a>
        <a href="users.php"><span class="icon">👤</span> Users</a>
        <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
    </div>

    <div class="main">
        <div class="page-title">
            <h1>Messages Center</h1>
            <?php if ($totalUnread > 0): ?>
                <span class="unread-pill"><?php echo $totalUnread; ?> New Unread Message<?php echo $totalUnread > 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>

        <div class="chat-layout">
            
            <!-- LEFT PANEL: CONVERSATIONS & INQUIRIES -->
            <div class="conversation-list">
                <div class="conversation-header">
                    <h3>Conversations & Inquiries</h3>
                    <div class="search-box-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="searchContacts" placeholder="Search contacts by name..." onkeyup="filterContacts()">
                    </div>
                </div>

                <div class="conversations-scroll" id="conversationsContainer">
                    <div class="inquiry-section-title">💬 Live User Chats</div>
                    <?php if (!$conversations || mysqli_num_rows($conversations) === 0): ?>
                        <div class="empty-list">No user chat threads yet.</div>
                    <?php else: ?>
                        <?php while ($conv = mysqli_fetch_assoc($conversations)): ?>
                            <?php 
                                $conv_user_id = intval($conv['user_id']);
                                $conv_name = trim(($conv['first_name'] ?? '') . ' ' . ($conv['last_name'] ?? ''));
                                if (empty($conv_name)) { $conv_name = $conv['email']; }
                                $initial = strtoupper(substr($conv_name, 0, 1));
                                $active = ($selected_user_id === $conv_user_id);
                                $unread_cnt = intval($conv['unread_count'] ?? 0);
                                $preview_text = !empty($conv['last_message']) ? $conv['last_message'] : (!empty($conv['last_message_image']) ? '📷 [Image attachment]' : 'No messages yet');
                            ?>
                            <a class="conversation <?php echo $active ? 'active' : ''; ?>" href="messages.php?user_id=<?php echo $conv_user_id; ?>" data-name="<?php echo htmlspecialchars(strtolower($conv_name)); ?>">
                                <div class="conversation-top">
                                    <div class="avatar-container">
                                        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
                                        <span class="status-dot"></span>
                                    </div>

                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($conv_name); ?></div>
                                        <div class="conversation-preview"><?php echo htmlspecialchars($preview_text); ?></div>
                                    </div>

                                    <div class="conversation-meta">
                                        <?php if (!empty($conv['last_message_time'])): ?>
                                            <div class="conversation-time"><?php echo date('M d', strtotime($conv['last_message_time'])); ?></div>
                                        <?php endif; ?>

                                        <?php if ($unread_cnt > 0): ?>
                                            <span class="unread-badge"><?php echo $unread_cnt; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <div class="inquiry-section-title">📩 Public Contact Inquiries</div>
                    <?php if ($public_inquiries && mysqli_num_rows($public_inquiries) > 0): ?>
                        <?php while ($inq = mysqli_fetch_assoc($public_inquiries)): ?>
                            <div class="inquiry-item">
                                <strong><?php echo htmlspecialchars($inq['full_name']); ?></strong> 
                                <span style="font-size:11px; color:#665444;">(<?php echo htmlspecialchars($inq['email']); ?>)</span>
                                <p><strong>Subject:</strong> <?php echo htmlspecialchars($inq['subject']); ?></p>
                                <p><?php echo htmlspecialchars($inq['message']); ?></p>
                                <span class="date">📅 <?php echo date('M d, Y g:i A', strtotime($inq['created_at'])); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-list">No guest contact form submissions yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT PANEL: ACTIVE CHAT WINDOW -->
            <?php if ($selected_user): ?>
                <div class="chat-window">
                    <div class="chat-header">
                        <div class="avatar-container">
                            <div class="avatar"><?php echo strtoupper(substr($selected_user['first_name'] ?? 'U', 0, 1)); ?></div>
                            <span class="status-dot"></span>
                        </div>
                        <div class="chat-header-info">
                            <h3><?php echo htmlspecialchars(trim(($selected_user['first_name'] ?? '') . ' ' . ($selected_user['last_name'] ?? ''))); ?></h3>
                            <p>🟢 online • <?php echo htmlspecialchars($selected_user['email']); ?> <?php echo !empty($selected_user['phone']) ? '• ' . htmlspecialchars($selected_user['phone']) : ''; ?></p>
                        </div>
                    </div>

                    <div class="chat-body" id="chatBody">
                        <?php if ($chat_messages && mysqli_num_rows($chat_messages) > 0): ?>
                            <?php while ($msg = mysqli_fetch_assoc($chat_messages)): ?>
                                <?php 
                                    $isAdmin = ($msg['sender_type'] === 'admin');
                                    $user_initial = strtoupper(substr($selected_user['first_name'] ?? 'U', 0, 1));
                                ?>
                                <div class="message-row <?php echo $isAdmin ? 'admin' : 'user'; ?>">
                                    <?php if (!$isAdmin): ?>
                                        <div class="avatar" style="width:34px; height:34px; font-size:12px; flex-shrink:0; background:#d2c29f; color:#5c4320; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; border:1px solid white;"><?php echo $user_initial; ?></div>
                                    <?php endif; ?>

                                    <div class="message-bubble-wrapper">
                                        <div class="message-sender-name">
                                            <?php echo $isAdmin ? 'PawLix Support' : htmlspecialchars($selected_user['first_name']); ?>
                                        </div>
                                        <div class="message-bubble">
                                            <?php if (!empty($msg['subject']) && $msg['subject'] !== 'PawLix Support Response'): ?>
                                                <div class="message-subject">📌 <?php echo htmlspecialchars($msg['subject']); ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($msg['message'])): ?>
                                                <p class="message-text"><?php echo htmlspecialchars($msg['message']); ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($msg['image'])): ?>
                                                <div class="message-img-container">
                                                    <img src="../uploads/<?php echo htmlspecialchars($msg['image']); ?>" class="message-img" onclick="window.open(this.src)" alt="Chat Image Attachment">
                                                </div>
                                            <?php endif; ?>

                                            <span class="message-time"><?php echo date('M d, g:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                    </div>

                                    <?php if ($isAdmin): ?>
                                        <div class="avatar" style="width:34px; height:34px; font-size:12px; flex-shrink:0; background:#4a3223; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; border:1px solid white;">🐾</div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-conversation">
                                <div>
                                    <div class="no-conversation-icon">🐾</div>
                                    <h2>Start Conversation</h2>
                                    <p>Send a direct message or share photos with <?php echo htmlspecialchars($selected_user['first_name']); ?>.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="composer">
                        <?php if ($error): ?>
                            <div style="background:#fdeaea; color:#b3261e; border:1px solid #f3c6c6; padding:9px 14px; border-radius:8px; font-size:12.5px; margin-bottom:10px; font-weight:600;">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <div class="image-preview-bar" id="imgPreviewBar">
                            <img id="imgPreviewThumb" src="" alt="Preview">
                            <span id="imgPreviewName">image.jpg</span>
                            <button type="button" class="remove-img-btn" onclick="clearSelectedImage()">&times;</button>
                        </div>

                        <form method="POST" action="messages.php?user_id=<?php echo $selected_user_id; ?>" enctype="multipart/form-data" class="composer-form" id="adminChatForm">
                            <input type="hidden" name="send_admin_message" value="1">
                            <input type="hidden" name="recipient_user_id" value="<?php echo $selected_user_id; ?>">
                            <input type="hidden" name="subject" value="PawLix Support Response">

                            <input type="file" name="image" id="imageInput" accept="image/*" style="display:none;" onchange="handleImageSelection(this)">

                            <button type="button" class="attach-btn" onclick="document.getElementById('imageInput').click();" title="Attach Image">📷</button>

                            <textarea name="message" id="messageInput" class="composer-input" placeholder="Type a message to <?php echo htmlspecialchars($selected_user['first_name']); ?>..." rows="1"></textarea>

                            <button type="submit" name="send_admin_message_btn" id="sendBtn" class="send-button" title="Send message (Enter)">➤</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="chat-window">
                    <div class="no-conversation">
                        <div>
                            <div class="no-conversation-icon">✉️</div>
                            <h2>Select a Conversation</h2>
                            <p>Choose a user chat thread from the left list to view their live conversation or review guest inquiries.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const chatBody = document.getElementById("chatBody");
    if (chatBody) { chatBody.scrollTop = chatBody.scrollHeight; }

    const messageInput = document.getElementById("messageInput");
    const adminChatForm = document.getElementById("adminChatForm");

    if (messageInput && adminChatForm) {
        messageInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                const imageInput = document.getElementById("imageInput");
                if (this.value.trim() !== '' || (imageInput && imageInput.files.length > 0)) {
                    adminChatForm.submit();
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

function filterContacts() {
    const query = document.getElementById("searchContacts").value.toLowerCase();
    const items = document.querySelectorAll("#conversationsContainer .conversation");
    
    items.forEach(function(item) {
        const name = item.getAttribute("data-name") || "";
        if (name.includes(query)) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
}
</script>
</body>
</html>