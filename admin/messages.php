<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM contact_message WHERE id = $id OR message_id = $id");
    header("Location: messages.php");
    exit();
}

$messages = mysqli_query($conn, "
SELECT *
FROM contact_message
ORDER BY created_at DESC
");
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
  --radius-sm: 8px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-pill: 30px;
  --font-family: 'Poppins', Arial, sans-serif;
}

*{ box-sizing: border-box; }

html, body{
  height: 100%;
  margin: 0;
  padding: 0;
  font-family: var(--font-family);
  background-color: var(--bg-body);
  color: var(--text-dark);
  overflow-x: hidden;
  overflow-y: auto;
}

body{
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.topbar{
  background: var(--bg-topbar);
  padding: 20px 45px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  z-index: 10;
}

.topbar .logo{
  font-size: 20px;
  font-weight: 700;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar a.logout{
  color: var(--primary-blue);
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
  transition: opacity 0.2s ease;
}

.topbar a.logout:hover{
  opacity: 0.8;
  text-decoration: underline;
}

.layout{
  display: flex;
  align-items: stretch;
  flex: 1;
}

.sidebar{
  width: 240px;
  background: var(--bg-sidebar);
  padding: 20px 14px;
  min-height: calc(100vh - 65px);
  flex-shrink: 0;
  border-right: 1px solid rgba(255, 255, 255, 0.4);
}

.sidebar a{
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 15px;
  margin-bottom: 6px;
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--text-dark);
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.sidebar a:hover{
  background: var(--bg-sidebar-hover);
}

.sidebar a.active{
  background: var(--primary-orange);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(242, 147, 43, 0.35);
}

.sidebar .icon{
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.main{
  flex: 1;
  padding: 28px 36px;
}

.main h1{
  margin: 0 0 22px;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.message-list{
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.message-card{
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  padding: 20px 22px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  position: relative;
}

.message-card.unread{
  border-left: 4px solid var(--primary-orange);
}

.msg-top{
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
  flex-wrap: wrap;
  gap: 8px;
}

.msg-sender{
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar{
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #d8cba9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #7a5c2e;
  flex-shrink: 0;
  font-size: 14px;
}

.msg-sender .name{
  font-weight: 600;
  font-size: 14px;
  color: #1a1a1a;
}

.msg-sender .email{
  color: #777;
  font-size: 12px;
}

.msg-date{
  color: #777;
  font-size: 12px;
  white-space: nowrap;
}

.msg-subject{
  font-weight: 600;
  font-size: 14px;
  margin: 8px 0 4px;
  color: #1a1a1a;
}

.msg-body{
  color: #444;
  font-size: 13px;
  line-height: 1.5;
}

.unread-badge{
  background: var(--primary-orange);
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  text-transform: uppercase;
}

.msg-actions{
  margin-top: 12px;
  display: flex;
  gap: 14px;
}

.msg-actions a{
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.reply-link{ color: var(--primary-blue); }
.reply-link:hover{ text-decoration: underline; }

.delete-link{ color: #b3261e; }
.delete-link:hover{ text-decoration: underline; }

.empty-state{
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  padding: 40px;
  text-align: center;
  color: #777;
  font-size: 14px;
}

@media (max-width: 850px){
  .layout{ flex-direction: column; }
  .sidebar{ width: 100%; min-height: auto; border-right: none; border-bottom: 1px solid var(--border-color); }
  .main{ padding: 20px; }
}
</style>
</head>

<body>

<div class="topbar">
  <div class="logo">PawLix</div>
  <a class="logout" href="logout.php">Logout</a>
</div>

<div class="layout">

  <div class="sidebar">
    <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
    <a href="dogs.php"><span class="icon">🐾</span> Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php" class="active"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Messages</h1>

    <?php if (mysqli_num_rows($messages) == 0) { ?>

      <div class="empty-state">No messages yet.</div>

    <?php } else { ?>

      <div class="message-list">
        <?php while($m=mysqli_fetch_assoc($messages)) { ?>
        <div class="message-card <?php echo (isset($m['read']) && !$m['read']) ? 'unread' : ''; ?>">

          <div class="msg-top">
            <div class="msg-sender">
              <div class="avatar"><?php echo strtoupper(substr($m['full_name'] ?? $m['name'] ?? 'U', 0, 1)); ?></div>
              <div>
                <div class="name"><?php echo htmlspecialchars($m['full_name'] ?? $m['name'] ?? 'Anonymous'); ?></div>
                <div class="email"><?php echo htmlspecialchars($m['email']); ?></div>
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
              <div class="msg-date"><?php echo date("M d, Y", strtotime($m['created_at'])); ?></div>
            </div>
          </div>

          <div class="msg-subject"><?php echo htmlspecialchars($m['subject'] ?? 'No Subject'); ?></div>
          <div class="msg-body"><?php echo htmlspecialchars($m['message']); ?></div>

          <div class="msg-actions">
            <a class="reply-link" href="mailto:<?php echo htmlspecialchars($m['email']); ?>">Reply by Email</a>
            <a class="delete-link" href="messages.php?delete=<?php echo $m['id'] ?? $m['message_id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
          </div>

        </div>
        <?php } ?>
      </div>

    <?php } ?>

  </div>

</div>

</body>
</html>