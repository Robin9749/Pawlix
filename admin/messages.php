<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$success = "";
$error = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
  
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM contact_message LIKE 'message_id'");
    $pk = (mysqli_num_rows($col_check) > 0) ? 'message_id' : 'id';
    
    if (mysqli_query($conn, "DELETE FROM contact_message WHERE $pk = $id")) {
        header("Location: messages.php?msg=deleted");
        exit();
    } else {
        $error = "Error deleting message: " . mysqli_error($conn);
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Message deleted successfully!";
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

.alert-success {
  background: #d4edda;
  color: #155724;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
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

.msg-actions{
  margin-top: 12px;
  display: flex;
  gap: 14px;
}

.msg-actions a{
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
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

/* POP-UP MODAL STYLES */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.modal-content {
  background: var(--bg-card);
  width: 100%;
  max-width: 420px;
  border-radius: 14px;
  padding: 26px;
  box-shadow: 0 15px 30px rgba(0,0,0,0.25);
  text-align: center;
  animation: popup 0.3s ease-out;
}

@keyframes popup {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-btn-group {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-top: 20px;
}

.btn-cancel {
  background: #d8cba9;
  color: #2b2b2b;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  font-family: inherit;
}

.btn-confirm-delete {
  background: #b3261e;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  display: inline-block;
  font-family: inherit;
  transition: background 0.2s;
}

.btn-confirm-delete:hover {
  background: #8f1d17;
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
    <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php" class="active"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Messages</h1>

    <?php if ($success != "") { ?>
      <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
      <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if (mysqli_num_rows($messages) == 0) { ?>

      <div class="empty-state">No messages yet.</div>

    <?php } else { ?>

      <div class="message-list">
        <?php 
        while($m=mysqli_fetch_assoc($messages)) { 
          $msg_id = $m['id'] ?? $m['message_id'] ?? 0;
        ?>
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

            <a class="delete-link" href="javascript:void(0)" onclick="confirmDelete(<?php echo $msg_id; ?>)">Delete</a>
          </div>

        </div>
        <?php } ?>
      </div>

    <?php } ?>

  </div>

</div>

<div class="modal-overlay" id="deleteConfirmModal">
  <div class="modal-content">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #1a1a1a;">Delete Message?</h2>
    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.4;">Are you sure you want to delete this message?</p>
    <div class="modal-btn-group">
      <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
      <a id="confirmDeleteBtn" href="#" class="btn-confirm-delete">Yes, Delete</a>
    </div>
  </div>
</div>

<script>
function confirmDelete(id) {
  document.getElementById('confirmDeleteBtn').href = 'messages.php?delete=' + id;
  document.getElementById('deleteConfirmModal').style.display = 'flex';
}

function closeDeleteModal() {
  document.getElementById('deleteConfirmModal').style.display = 'none';
}

window.onclick = function(event) {
  var modal = document.getElementById('deleteConfirmModal');
  if (event.target == modal) {
    closeDeleteModal();
  }
}
</script>

</body>
</html>