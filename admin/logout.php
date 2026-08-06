<?php
session_start();

if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$back_url = (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Logout - PawLix</title>

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

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  font-family: var(--font-family);
  background-color: var(--bg-body);
  color: var(--text-dark);
  overflow-x: hidden;
  overflow-y: auto;
}

body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.topbar {
  background: var(--bg-topbar);
  padding: 20px 45px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  z-index: 10;
}

.topbar .logo {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar a.logout {
  color: var(--primary-blue);
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
}

.layout {
  display: flex;
  align-items: stretch;
  flex: 1;
}

.sidebar {
  width: 240px;
  background: var(--bg-sidebar);
  padding: 20px 14px;
  min-height: calc(100vh - 65px);
  flex-shrink: 0;
  border-right: 1px solid rgba(255, 255, 255, 0.4);
}

.sidebar a {
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
}

.sidebar .icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.main {
  flex: 1;
  padding: 28px 36px;
}

.modal-overlay {
  display: flex;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(5px);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.modal-content {
  background: #ede1c6;
  width: 100%;
  max-width: 400px;
  border-radius: 16px;
  padding: 32px 28px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
  position: relative;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.6);
  animation: popup 0.25s ease-out;
}

@keyframes popup {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}


.modal-content h2 {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.modal-content p {
  font-size: 14px;
  color: #555555;
  margin-bottom: 26px;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel {
  flex: 1;
  background: #ffffff;
  color: #2b2b2b;
  border: 1px solid #ddccae;
  padding: 13px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  cursor: pointer;
  font-family: inherit;
  display: inline-block;
  text-align: center;
  transition: background 0.2s ease;
}

.btn-cancel:hover {
  background: #f5ecda;
}

.btn-logout {
  flex: 1;
  background: #b3261e;
  color: #ffffff;
  border: none;
  padding: 13px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  cursor: pointer;
  font-family: inherit;
  display: inline-block;
  text-align: center;
  box-shadow: 0 4px 12px rgba(179, 38, 30, 0.3);
  transition: background 0.2s ease;
}

.btn-logout:hover {
  background: #961e17;
}

@media (max-width: 850px){
  .layout{ flex-direction: column; }
  .sidebar{ width: 100%; min-height: auto; }
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
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main"></div>
</div>

<div class="modal-overlay">
  <div class="modal-content">
    <h2>Log Out?</h2>
    <p>Are you sure you want to log out?</p>
    <div class="modal-actions">
      <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-cancel">Cancel</a>
      <a href="logout.php?confirm=true" class="btn-logout">Log Out</a>
    </div>
  </div>
</div>

</body>
</html>