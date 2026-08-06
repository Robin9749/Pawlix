<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include("../config/config.php");

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_name'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $success_msg = "Name updated successfully!";
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
        } else {
            $success_msg = "Password updated successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - PawLix</title>
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
  padding: 20px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
}

.topbar .logo{
  font-size: 22px;
  font-weight: 700;
}

.topbar a.logout{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 15px;
}

.layout{
  display: flex;
  align-items: flex-start;
}

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

.sidebar .icon{
  font-size: 18px;
  width: 20px;
  text-align: center;
}

.main{ 
  flex: 1; 
  padding: 36px 44px; 
}

.main h1{ 
  margin: 0 0 22px; 
  font-size: 28px; 
  font-weight: 700; 
}

.alert{
  max-width: 1000px;
  padding: 12px 18px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  font-weight: 500;
}
.alert-success{
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}
.alert-error{
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.settings-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  max-width: 1000px;
  align-items: start;
}

@media (max-width: 850px){
  .settings-grid{
    grid-template-columns: 1fr;
  }
}

.settings-card{
  background: #ede1c6;
  border-radius: 14px;
  padding: 28px 32px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
}

.settings-card h2{
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: 600;
  border-bottom: 1px solid #d8c9a3;
  padding-bottom: 10px;
}

.form-group{ margin-bottom: 20px; }

.form-group label{
  display: block;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: #2b2b2b;
}

.form-group input{
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d8c9a3;
  border-radius: 10px;
  background: #f5ecda;
  font-family: inherit;
  font-size: 14px;
  color: #2b2b2b;
  outline: none;
}

.form-group input:focus{
  border-color: #f2932b;
}

.btn-save{
  width: 100%;
  background: #f2932b;
  color: white;
  border: none;
  padding: 12px 28px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
  transition: background 0.2s ease;
}

.btn-save:hover{ background: #e08420; }
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
            <input type="text" name="name" value="Admin" required>
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
</html><?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include("../config/config.php");

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_name'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $success_msg = "Name updated successfully!";
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
        } else {
            $success_msg = "Password updated successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - PawLix</title>
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

.topbar .logo{
  font-size: 20px;
  font-weight: 700;
  color: #2b2b2b;
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar a.logout{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 15px;
}

.topbar a.logout:hover{
  text-decoration: underline;
}

.layout{
  display: flex;
  align-items: flex-start;
}

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
  transition: background 0.15s ease;
}

.sidebar a:hover{
  background: #ddceac;
}

.sidebar a.active{
  background: #f2932b;
  color: white;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
}

.sidebar a.active:hover{
  background: #f2932b;
}

.sidebar .icon{
  font-size: 18px;
  width: 20px;
  text-align: center;
}


.main{ 
  flex: 1; 
  padding: 36px 44px; 
}

.main h1{ 
  margin: 0 0 22px; 
  font-size: 28px; 
  font-weight: 700; 
}

.alert{
  max-width: 1000px;
  padding: 12px 18px;
  border-radius: 10px;
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

@media (max-width: 850px){
  .settings-grid{
    grid-template-columns: 1fr;
  }
}

.settings-card{
  background: rgba(237, 225, 198, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 16px;
  padding: 28px 32px;
  border: 1px solid rgba(255, 255, 255, 0.65);
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.settings-card:hover{
  box-shadow: 0 12px 36px 0 rgba(0, 0, 0, 0.08);
}

.settings-card h2{
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: 600;
  border-bottom: 1px solid rgba(216, 201, 163, 0.7);
  padding-bottom: 10px;
}

.form-group{ margin-bottom: 20px; }

.form-group label{
  display: block;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: #2b2b2b;
}

.form-group input{
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(216, 201, 163, 0.8);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.45);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  font-family: inherit;
  font-size: 14px;
  color: #2b2b2b;
  outline: none;
  transition: all 0.2s ease;
}

.form-group input:focus{
  border-color: #f2932b;
  background: rgba(255, 255, 255, 0.75);
  box-shadow: 0 0 0 3px rgba(242, 147, 43, 0.15);
}

.btn-save{
  width: 100%;
  background: #f2932b;
  color: white;
  border: none;
  padding: 12px 28px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(242,147,43,0.35);
  transition: background 0.2s ease, transform 0.1s ease;
}

.btn-save:hover{ background: #e08420; }
.btn-save:active{ transform: translateY(1px); }
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
            <input type="text" name="name" value="Admin" required>
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