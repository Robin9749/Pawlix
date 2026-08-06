<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$users = mysqli_query($conn, "
SELECT *
FROM user
ORDER BY user_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users | PawLix Admin</title>

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

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

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
  transition: opacity 0.2s ease;
}

.topbar a.logout:hover {
  opacity: 0.8;
  text-decoration: underline;
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
  transition: all 0.2s ease;
}

.sidebar a:hover {
  background: var(--bg-sidebar-hover);
}

.sidebar a.active {
  background: var(--primary-orange);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(242, 147, 43, 0.35);
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

.main h1 {
  margin: 0 0 22px;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.table-card {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

col.col-sn { width: 60px; }
col.col-name { width: 25%; }
col.col-email { width: 30%; }
col.col-role { width: 12%; }
col.col-joined { width: 18%; }
col.col-action { width: 90px; }

thead th {
  background: #d8c9a3;
  font-weight: 700;
  font-size: 12px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #3d3326;
  padding: 14px 10px;
}

.text-center { text-align: center; }
.text-left { text-align: left; }

tbody tr {
  border-bottom: 1px solid var(--border-color);
  transition: background 0.15s ease-in-out;
}

tbody tr:last-child {
  border-bottom: none;
}

tbody tr:hover {
  background: #e6d8bb;
}

tbody td {
  padding: 12px 10px;
  font-size: 13px;
  vertical-align: middle;
}

.person {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  background: #d8cba9;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.name {
  font-weight: 600;
  font-size: 14px;
  color: #1a1a1a;
}

.email, .role, .joined {
  color: #555555;
  font-size: 13px;
}

.view-link {
  color: var(--primary-blue);
  font-weight: 600;
  text-decoration: none;
  font-size: 13px;
}

.view-link:hover { text-decoration: underline; }

@media(max-width: 850px) {
  .layout {
    flex-direction: column;
  }
  .sidebar {
    width: 100%;
    min-height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
  .main { padding: 20px; }
  table { table-layout: auto; }
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
    <a href="users.php" class="active"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Users</h1>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-name">
          <col class="col-email">
          <col class="col-role">
          <col class="col-joined">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-left">NAME</th>
            <th class="text-left">EMAIL</th>
            <th class="text-center">ROLE</th>
            <th class="text-center">JOINED DATE</th>
            <th class="text-center">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while($u = mysqli_fetch_assoc($users)) { ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td class="text-left">
              <div class="person">
                <img class="avatar" src="../assets/img/user-placeholder.jpg" alt="User">
                <div class="name"><?php echo htmlspecialchars(($u['first_name'] ?? '') . " " . ($u['last_name'] ?? '')); ?></div>
              </div>
            </td>
            <td class="text-left email"><?php echo htmlspecialchars($u['email']); ?></td>
            <td class="text-center role"><?php echo htmlspecialchars($u['role'] ?? 'User'); ?></td>
            <td class="text-center joined"><?php echo date("M d, Y", strtotime($u['created_at'] ?? $u['joined_date'] ?? 'now')); ?></td>
            <td class="text-center"><a class="view-link" href="view_user.php?id=<?php echo $u['user_id']; ?>">View</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>