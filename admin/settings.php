<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
include("../config/config.php");
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setting</title>
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
  flex: 1; padding: 36px 44px; 
}
.main h1{ 
  margin: 0 0 22px; font-size: 28px; font-weight: 700; 
}
.settings-card{
  background: #ede1c6;
  border-radius: 14px;
  padding: 32px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
  max-width: 640px;
}
.form-group{ margin-bottom: 20px; }
.form-group label{
  display: block;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: #2b2b2b;
}
.form-group input, .form-group select{
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
.form-group input:focus, .form-group select:focus{
  border-color: #f2932b;
}
.btn-save{
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
    <div class="settings-card">
      <form method="post" action="">
        <div class="form-group">
          <label>Admin Name</label>
          <input type="text" name="name" value="Admin" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="admin@pawlix.com" required>
        </div>
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" placeholder="Enter current password">
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password">
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm new password">
        </div>
        <button type="submit" class="btn-save">Save Changes</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>