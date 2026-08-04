<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM Messages WHERE message_id = $id");
    header("Location: messages.php");
    exit();
}

$messages=mysqli_query($conn,"
SELECT *
FROM contact_message
ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Messages</title>

<style>

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

*{ box-sizing: border-box; }

body{
  font-family: 'Poppins', Arial, sans-serif;
  margin: 0;
  background: #e8dcc0;
  color: #2b2b2b;
}

.topbar{
  background: #f2e6c9;
  padding: 30px 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
}

.topbar .logo{ font-size: 20px; font-weight: 700; }

.topbar a.logout{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 15px;
}

.layout{ display: flex; align-items: flex-start; }

.sidebar{
  width: 260px;
  background: #e8dcc0;
  padding: 24px 18px;
  min-height: calc(100vh - 78px);
}

.sidebar a{
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  margin-bottom: 8px;
  border-radius: 10px;
  text-decoration: none;
  color: #2b2b2b;
  font-weight: 600;
  font-size: 15px;
}

.sidebar a:hover{ background: #ddceac; }

.sidebar a.active{
  background: #f2932b;
  color: white;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
}

.sidebar .icon{ font-size: 18px; width: 20px; text-align: center; }

.main{ flex: 1; padding: 36px 44px; }

.main h1{
  margin: 0 0 22px;
  font-size: 28px;
  font-weight: 700;
}

.message-list{
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.message-card{
  background: #ede1c6;
  border-radius: 12px;
  padding: 22px 24px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
  position: relative;
}

.message-card.unread{
  border-left: 4px solid #f2932b;
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
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #d8cba9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #7a5c2e;
  flex-shrink: 0;
}

.msg-sender .name{
  font-weight: 600;
  font-size: 15px;
}

.msg-sender .email{
  color: #888;
  font-size: 13px;
}

.msg-date{
  color: #888;
  font-size: 13px;
  white-space: nowrap;
}

.msg-subject{
  font-weight: 600;
  font-size: 15px;
  margin: 10px 0 4px;
}

.msg-body{
  color: #444;
  font-size: 14px;
  line-height: 1.6;
}

.unread-badge{
  background: #f2932b;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  text-transform: uppercase;
}

.msg-actions{
  margin-top: 14px;
  display: flex;
  gap: 14px;
}

.msg-actions a{
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.reply-link{ color: #1f6fd6; }
.reply-link:hover{ text-decoration: underline; }

.delete-link{ color: #b3261e; }
.delete-link:hover{ text-decoration: underline; }

.empty-state{
  background: #ede1c6;
  border-radius: 12px;
  padding: 50px;
  text-align: center;
  color: #777;
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
        <div class="message-card <?php echo !$m['read'] ? 'unread' : ''; ?>">

          <div class="msg-top">
            <div class="msg-sender">
              <div class="avatar"><?php echo strtoupper(substr($m['full_name'],0,1)); ?></div>
              <div>
                <div class="name"><?php echo $m['full_name']; ?></div>
                <div class="email"><?php echo $m['email']; ?></div>
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
              <div class="msg-date"><?php echo date("M d, Y",strtotime($m['created_at'])); ?></div>
            </div>
          </div>

          <div class="msg-subject"><?php echo $m['subject']; ?></div>
          <div class="msg-body"><?php echo htmlspecialchars($m['message']); ?></div>

          <div class="msg-actions">
            <a class="reply-link" href="mailto:<?php echo $m['email']; ?>">Reply by Email</a>
            <a class="delete-link" href="messages.php?delete=<?php echo $m['id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
          </div>

        </div>
        <?php } ?>
      </div>

    <?php } ?>

  </div>

</div>

</body>
</html>