<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include("../config/config.php");

$admin_id = $_SESSION['admin_id'];
$stmt = mysqli_prepare($conn, "SELECT name, password FROM Admin WHERE admin_id = ?");
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$success_msg = "";
$error_msg = "";

if (isset($_POST['update_name'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = mysqli_prepare($conn, "UPDATE Admin SET name = ? WHERE admin_id = ?"); 
        mysqli_stmt_bind_param($stmt, "si", $name, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Name updated successfully!";
            $admin['name'] = $name; 
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Name cannot be empty.";
    }
}

if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New password and Confirm password do not match.";
    } elseif ($current_password !== $admin['password']) {
        $error_msg = "Current password is incorrect.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE Admin SET password = ? WHERE admin_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_password, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Password updated successfully!";
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - PawLix</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root {
  --bg-body: #e8dcc0;
  --bg-topbar: #f2e6c9;
  --bg-card: #ede1c6;
  --bg-sidebar: #e8dcc0;
  --bg-sidebar-hover: #ddceac;
  --primary-orange: #f2932b;
  --primary-orange-hover: #e08420;
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
  overflow-y: hidden;
}

body{
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

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

.alert{
  max-width: 1000px;
  padding: 12px 18px;
  border-radius: var(--radius-md);
  margin-bottom: 20px;
  font-size: 14px;
  font-weight: 500;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.alert-success{
  background: rgba(212, 237, 218, 0.85);
  color: #155724;
  border: 1px solid rgba(195, 230, 203, 0.9);
}

.alert-error{
  background: rgba(248, 215, 218, 0.85);
  color: #721c24;
  border: 1px solid rgba(245, 198, 203, 0.9);
}

.settings-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  max-width: 1000px;
  align-items: start;
}

.settings-card{
  background: rgba(237, 225, 198, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  border: 1px solid rgba(255, 255, 255, 0.65);
  box-shadow: 0 6px 24px 0 rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.settings-card:hover{
  box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.07);
}

.settings-card h2{
  margin-top: 0;
  margin-bottom: 18px;
  font-size: 17px;
  font-weight: 600;
  border-bottom: 1px solid rgba(216, 201, 163, 0.7);
  padding-bottom: 10px;
  color: #1a1a1a;
}

.form-group{ margin-bottom: 18px; }

.form-group label{
  display: block;
  font-weight: 600;
  font-size: 13px;
  margin-bottom: 6px;
  color: var(--text-dark);
}

.form-group input{
  width: 100%;
  padding: 11px 14px;
  border: 1px solid rgba(216, 201, 163, 0.8);
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  font-family: inherit;
  font-size: 13px;
  color: var(--text-dark);
  outline: none;
  transition: all 0.2s ease;
}

.form-group input:focus{
  border-color: var(--primary-orange);
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 0 0 3px rgba(242, 147, 43, 0.15);
}

.btn-save{
  width: 100%;
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(242,147,43,0.35);
  transition: background 0.2s ease, transform 0.1s ease;
}

.btn-save:hover{ background: var(--primary-orange-hover); }
.btn-save:active{ transform: translateY(1px); }

@media (max-width: 850px){
  .layout{
    flex-direction: column;
  }
  .sidebar{
    width: 100%;
    min-height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
  .main{
    padding: 20px;
  }
  .settings-grid{
    grid-template-columns: 1fr;
  }
}
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
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php" class="active"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">
    <h1>Setting</h1>

    <?php if (!empty($success_msg)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="settings-grid">

      <div class="settings-card">
        <h2>Profile Information</h2>
        <form method="post" action="">
          <div class="form-group">
            <label>Admin Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?>" required>
          </div>
          <button type="submit" name="update_name" class="btn-save">Update Name</button>
        </form>
      </div>

      <div class="settings-card">
        <h2>Change Password</h2>
        <form method="post" action="">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Enter current password" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
          </div>
          <button type="submit" name="update_password" class="btn-save">Update Password</button>
        </form>
      </div>

    </div>
  </div>
</div>

</body>
</html>