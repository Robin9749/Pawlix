<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$users=mysqli_query($conn,"
SELECT *
FROM user
ORDER BY user_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users</title>

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
  flex: 1;
  padding: 36px 44px;
}

.main h1{
  margin: 0 0 22px;
  font-size: 28px;
  font-weight: 700;
}

.table-card{
  background: #ede1c6;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
}

table{
  width: 100%;
  border-collapse: collapse;
}

thead td{
  background: #d8c9a3;
  font-weight: 700;
  font-size: 14px;
  padding: 16px 14px;
}

tbody tr{
  border-bottom: 1px solid #ddccae;
}

tbody tr:last-child{
  border-bottom: none;
}

tbody td{
  padding: 14px;
  font-size: 14px;
  vertical-align: middle;
}

.person{
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar{
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  background: #d8cba9;
  flex-shrink: 0;
}

.name{
  font-weight: 600;
  font-size: 15px;
}

.email, .role, .joined{
  color: #555;
  font-size: 14px;
}

.view-link{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
}

.view-link:hover{ text-decoration: underline; }

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
        <thead>
          <tr>
            <td width="60">S.N.</td>
            <td>Name</td>
            <td>Email</td>
            <td>Role</td>
            <td>Joined Date</td>
            <td width="80">Action</td>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while($u=mysqli_fetch_assoc($users)) { ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td>
              <div class="person">
                <img class="avatar" src="../assets/img/user-placeholder.jpg">
                <div class="name"><?php echo $u['first_name']." ".$u['last_name']; ?></div>
              </div>
            </td>
            <td class="email"><?php echo $u['email']; ?></td>
            <td class="role"><?php echo $u['role']; ?></td>
            <td class="joined"><?php echo date("M d, Y",strtotime($u['created_at'])); ?></td>
            <td><a class="view-link" href="view_user.php">View</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>