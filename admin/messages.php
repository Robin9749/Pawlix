<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$admin_id = intval($_SESSION['admin_id']);

/* ================= AUTO-CREATE MESSAGES TABLE IF NOT EXISTS ================= */
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL DEFAULT 1,
    sender_type ENUM('user', 'admin') NOT NULL,
    subject VARCHAR(255) DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/* ================= GET ADMIN ================= */
$admin_query = mysqli_query($conn, "SELECT * FROM admin WHERE admin_id = $admin_id");
$admin = $admin_query ? mysqli_fetch_assoc($admin_query) : [];

/* ================= SELECT CONVERSATION ================= */
$selected_user_id = intval($_GET['user_id'] ?? 0);

/* ================= SEND ADMIN MESSAGE ================= */
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_user_id = intval($_POST['recipient_user_id'] ?? 0);
    $message_text = trim($_POST['message'] ?? '');
    $subject = trim($_POST['subject'] ?? 'PawLix Support');

    if ($recipient_user_id <= 0 || $message_text === '') {
        if (isset($_POST['send_admin_message']) || isset($_POST['submitted'])) {
            $error = "Please enter a message.";
        }
    } else {
        $user_check = mysqli_query($conn, "SELECT user_id FROM user WHERE user_id = $recipient_user_id LIMIT 1");
        if (!$user_check || mysqli_num_rows($user_check) === 0) {
            $error = "User not found.";
        } else {
            $message_db = mysqli_real_escape_string($conn, $message_text);
            $subject_db = mysqli_real_escape_string($conn, $subject);

            $insert = "
                INSERT INTO messages (user_id, admin_id, sender_type, subject, message, is_read)
                VALUES ($recipient_user_id, $admin_id, 'admin', '$subject_db', '$message_db', 0)
            ";

            if (mysqli_query($conn, $insert)) {
                header("Location: messages.php?user_id=" . $recipient_user_id . "&sent=1");
                exit();
            } else {
                $error = "Unable to send message.";
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
        (
            SELECT m.message 
            FROM messages m 
            WHERE m.user_id = u.user_id 
            ORDER BY m.created_at DESC, m.message_id DESC 
            LIMIT 1
        ) AS last_message,
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
    ORDER BY last_message_time DESC
");

/* ================= MARK USER MESSAGES READ WHEN SELECTED ================= */
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages | PawLix Admin</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root {
    --bg-body: #e8dcc0;
    --bg-topbar: #f2e6c9;
    --bg-card: #ede1c6;
    --bg-sidebar: #e8dcc0;
    --bg-sidebar-hover: #ddceac;
    --primary-orange: #f2932b;
    --primary-blue: #1f6fd6;
    --text-dark: #2b2b2b;
    --text-muted: #5c5c5c;
    --border-color: #ddccae;
    --online-green: #2ecc71;
}

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }

body {
    font-family: 'Poppins', Arial, sans-serif;
    background: var(--bg-body);
    color: var(--text-dark);
}

/* TOPBAR */
.topbar {
    height: 70px;
    background: var(--bg-topbar);
    padding: 0 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid white;
}

.topbar .logo { font-size: 20px; font-weight: 700; color: var(--text-dark); }
.topbar .logout { color: var(--primary-blue); text-decoration: none; font-weight: 600; font-size: 14px; }
.topbar .logout:hover { text-decoration: underline; }

/* LAYOUT */
.layout { display: flex; height: calc(100vh - 70px); }

/* SIDEBAR */
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

/* MAIN AREA */
.main {
    flex: 1;
    padding: 25px 30px;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.page-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.page-title h1 { margin: 0; font-size: 24px; font-weight: 700; }

.unread-pill {
    background: var(--primary-orange);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12.5px;
    font-weight: 700;
}

/* CHAT APPLICATION */
.chat-layout {
    flex: 1;
    display: flex;
    background: var(--bg-card);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
    border: 1px solid var(--border-color);
}

/* CONVERSATION LIST (LEFT SIDEBAR) */
.conversation-list {
    width: 320px;
    background: #e8dcc0;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.conversation-header {
    padding: 18px;
    border-bottom: 1px solid var(--border-color);
}

.conversation-header h3 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
}

.search-box-wrapper { position: relative; }

.search-box-wrapper input {
    width: 100%;
    padding: 10px 14px 10px 36px;
    border: 1px solid #dfcfb0;
    border-radius: 20px;
    background: #f5ecd7;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
}

.search-box-wrapper input:focus { background: #ffffff; border-color: var(--primary-orange); }

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #806d5c;
}

.conversations-scroll { flex: 1; overflow-y: auto; }

.conversation {
    display: block;
    padding: 14px 16px;
    text-decoration: none;
    color: var(--text-dark);
    border-bottom: 1px solid rgba(221,204,174,.6);
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
}

.conversation.active .avatar { background: rgba(255,255,255,.25); color: white; }

.status-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 11px;
    height: 11px;
    background: var(--online-green);
    border: 2px solid white;
    border-radius: 50%;
}

.conversation-info { min-width: 0; flex: 1; }

.conversation-name { font-size: 13.5px; font-weight: 700; line-height: 1.2; }

.conversation-preview {
    font-size: 12px;
    opacity: .75;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 3px;
}

.conversation-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }

.conversation-time { font-size: 10.5px; opacity: .75; white-space: nowrap; }

.unread-badge {
    background: #e63946;
    color: white;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10.5px;
    font-weight: 700;
}

/* CHAT WINDOW */
.chat-window { flex: 1; display: flex; flex-direction: column; min-width: 0; }

.chat-header {
    height: 70px;
    background: var(--bg-topbar);
    border-bottom: 1px solid var(--border-color);
    padding: 12px 24px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.chat-header .avatar { width: 44px; height: 44px; }
.chat-header-info h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--text-dark); }
.chat-header-info p { margin: 2px 0 0; font-size: 11.5px; color: var(--online-green); font-weight: 600; }

.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: #f5ecd7;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chat-body::-webkit-scrollbar { width: 7px; }
.chat-body::-webkit-scrollbar-thumb { background: #cbb997; border-radius: 10px; }

/* MESSAGE BUBBLES */
.message-row { display: flex; gap: 10px; align-items: flex-end; width: 100%; }
.message-row.user { justify-content: flex-start; }
.message-row.admin { justify-content: flex-end; }

.message-bubble-wrapper { max-width: 70%; display: flex; flex-direction: column; }
.message-sender-name { font-size: 11px; font-weight: 600; color: #806d5c; margin-bottom: 3px; }
.message-row.admin .message-sender-name { text-align: right; }

.message-bubble {
    padding: 11px 15px 8px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    box-shadow: 0 2px 5px rgba(0,0,0,.04);
    position: relative;
}

.message-row.user .message-bubble {
    background: #fffaf0;
    color: #43352a;
    border: 1px solid #e4d5b9;
    border-bottom-left-radius: 4px;
}

.message-row.admin .message-bubble {
    background: var(--primary-orange);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-text { margin: 0 0 4px; white-space: pre-wrap; word-break: break-word; }
.message-subject { font-weight: 700; margin-bottom: 4px; font-size: 13px; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 3px; }
.message-row.admin .message-subject { border-bottom-color: rgba(255,255,255,0.3); }
.message-time { display: block; text-align: right; font-size: 9.5px; opacity: .75; margin-top: 2px; }

/* COMPOSER */
.composer {
    padding: 14px 20px;
    background: var(--bg-topbar);
    border-top: 1px solid var(--border-color);
}

.composer-form { display: flex; align-items: flex-end; gap: 10px; }

.composer-input {
    flex: 1;
    resize: none;
    min-height: 46px;
    max-height: 110px;
    border: 1px solid #d8c6a5;
    border-radius: 22px;
    padding: 12px 18px;
    font-family: inherit;
    font-size: 13.5px;
    background: #fffaf0;
    outline: none;
    color: #222;
    transition: border-color 0.2s;
}

.composer-input:focus { border-color: var(--primary-orange); background: #ffffff; }

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
    box-shadow: 0 4px 12px rgba(242,147,43,0.3);
}

.send-button:hover { background: #e06600; transform: translateY(-1px); }

/* WELCOME & EMPTY STATES */
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

.empty-list { padding: 40px 20px; text-align: center; color: #806d5c; font-size: 13px; }

@media(max-width: 850px) {
    .sidebar { display: none; }
    .main { padding: 15px; }
    .conversation-list { width: 250px; }
}

@media(max-width: 600px) {
    .conversation-list { width: 90px; }
    .conversation-info, .conversation-meta, .search-box-wrapper, .conversation-header h3 { display: none; }
    .conversation { display: flex; justify-content: center; padding: 10px; }
    .chat-body { padding: 14px; }
    .message-bubble-wrapper { max-width: 85%; }
}
</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="logo">PawLix Admin</div>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="layout">

    <!-- SIDEBAR -->
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

    <!-- MAIN CONTENT AREA -->
    <div class="main">

        <div class="page-title">
            <h1>Messages</h1>
            <?php if ($totalUnread > 0): ?>
                <span class="unread-pill"><?php echo $totalUnread; ?> New Message<?php echo $totalUnread > 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>

        <!-- CHAT APPLICATION SPLIT-PANE -->
        <div class="chat-layout">

            <!-- LEFT: CONVERSATION LIST -->
            <div class="conversation-list">
                <div class="conversation-header">
                    <h3>Recent Chats</h3>
                    <div class="search-box-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="searchContacts" placeholder="Search contacts..." onkeyup="filterContacts()">
                    </div>
                </div>

                <div class="conversations-scroll" id="conversationsContainer">
                    <?php if (!$conversations || mysqli_num_rows($conversations) === 0): ?>
                        <div class="empty-list">No user conversations yet.</div>
                    <?php else: ?>
                        <?php while ($conv = mysqli_fetch_assoc($conversations)): ?>
                            <?php 
                                $conv_user_id = intval($conv['user_id']);
                                $conv_name = trim(($conv['first_name'] ?? '') . ' ' . ($conv['last_name'] ?? ''));
                                if (empty($conv_name)) { $conv_name = $conv['email']; }
                                $initial = strtoupper(substr($conv_name, 0, 1));
                                $active = ($selected_user_id === $conv_user_id);
                                $unread_cnt = intval($conv['unread_count'] ?? 0);
                            ?>
                            <a class="conversation <?php echo $active ? 'active' : ''; ?>" href="messages.php?user_id=<?php echo $conv_user_id; ?>" data-name="<?php echo htmlspecialchars(strtolower($conv_name)); ?>">
                                <div class="conversation-top">
                                    <div class="avatar-container">
                                        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
                                        <span class="status-dot"></span>
                                    </div>

                                    <div class="conversation-info">
                                        <div class="conversation-name"><?php echo htmlspecialchars($conv_name); ?></div>
                                        <div class="conversation-preview"><?php echo htmlspecialchars($conv['last_message'] ?? 'No messages yet'); ?></div>
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
                </div>
            </div>

            <!-- RIGHT: CHAT WINDOW -->
            <?php if ($selected_user): ?>
                <div class="chat-window">

                    <!-- CHAT HEADER -->
                    <div class="chat-header">
                        <div class="avatar-container">
                            <div class="avatar"><?php echo strtoupper(substr($selected_user['first_name'] ?? 'U', 0, 1)); ?></div>
                            <span class="status-dot"></span>
                        </div>

                        <div class="chat-header-info">
                            <h3><?php echo htmlspecialchars(trim(($selected_user['first_name'] ?? '') . ' ' . ($selected_user['last_name'] ?? ''))); ?></h3>
                            <p>🟢 online • <?php echo htmlspecialchars($selected_user['email']); ?></p>
                        </div>
                    </div>

                    <!-- CHAT BODY -->
                    <div class="chat-body" id="chatBody">
                        <?php if ($chat_messages && mysqli_num_rows($chat_messages) > 0): ?>
                            <?php while ($msg = mysqli_fetch_assoc($chat_messages)): ?>
                                <?php 
                                    $isAdmin = ($msg['sender_type'] === 'admin');
                                    $user_initial = strtoupper(substr($selected_user['first_name'] ?? 'U', 0, 1));
                                ?>
                                <div class="message-row <?php echo $isAdmin ? 'admin' : 'user'; ?>">
                                    <?php if (!$isAdmin): ?>
                                        <div class="avatar" style="width:34px; height:34px; font-size:12px; flex-shrink:0; background:#d2c29f; color:#5c4320; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;"><?php echo $user_initial; ?></div>
                                    <?php endif; ?>

                                    <div class="message-bubble-wrapper">
                                        <div class="message-sender-name">
                                            <?php echo $isAdmin ? 'PawLix Support' : htmlspecialchars($selected_user['first_name']); ?>
                                        </div>
                                        <div class="message-bubble">
                                            <?php if (!empty($msg['subject']) && $msg['subject'] !== 'PawLix Support'): ?>
                                                <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                            <?php endif; ?>
                                            <p class="message-text"><?php echo htmlspecialchars($msg['message']); ?></p>
                                            <span class="message-time"><?php echo date('M d, g:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                    </div>

                                    <?php if ($isAdmin): ?>
                                        <div class="avatar" style="width:34px; height:34px; font-size:12px; flex-shrink:0; background:var(--dark-brown); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">🐾</div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-conversation">
                                <div>
                                    <div class="no-conversation-icon">🐾</div>
                                    <h2>Start Conversation</h2>
                                    <p>Send a direct message to <?php echo htmlspecialchars($selected_user['first_name']); ?>.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- COMPOSER -->
                    <div class="composer">
                        <?php if ($error): ?>
                            <div style="background:#f8d7da; color:#721c24; padding:8px 12px; border-radius:7px; font-size:12.5px; margin-bottom:8px; font-weight:600;">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="messages.php?user_id=<?php echo $selected_user_id; ?>" class="composer-form" id="adminChatForm">
                            <input type="hidden" name="send_admin_message" value="1">
                            <input type="hidden" name="recipient_user_id" value="<?php echo $selected_user_id; ?>">
                            <input type="hidden" name="subject" value="PawLix Support">

                            <textarea
                                name="message"
                                id="messageInput"
                                class="composer-input"
                                placeholder="Type a message to <?php echo htmlspecialchars($selected_user['first_name']); ?>..."
                                rows="1"
                                required
                            ></textarea>

                            <button type="submit" name="send_admin_message_btn" id="sendBtn" class="send-button" title="Send message">
                                ➤
                            </button>
                        </form>
                    </div>

                </div>
            <?php else: ?>
                <!-- EMPTY STATE WHEN NO CONVERSATION SELECTED -->
                <div class="chat-window">
                    <div class="no-conversation">
                        <div>
                            <div class="no-conversation-icon">✉️</div>
                            <h2>Select a Conversation</h2>
                            <p>Choose a user contact from the left list to view their message thread and reply directly.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Auto scroll to bottom
    const chatBody = document.getElementById("chatBody");
    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // Enter Key to Submit Admin Form
    const messageInput = document.getElementById("messageInput");
    const adminChatForm = document.getElementById("adminChatForm");

    if (messageInput && adminChatForm) {
        messageInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    adminChatForm.submit();
                }
            }
        });
    }
});

// Live filter contact search list
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