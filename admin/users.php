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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users | PawLix Admin</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

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


.main {
  flex: 1;
  padding: 36px 44px;
}

.main h1 {
  margin: 0 0 22px;
  font-size: 28px;
  font-weight: 700;
}

.table-card {
  background: #ede1c6;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

col.col-sn { width: 70px; }
col.col-name { width: 25%; }
col.col-email { width: 30%; }
col.col-role { width: 12%; }
col.col-joined { width: 18%; }
col.col-action { width: 110px; }

thead th {
  background: #d8c9a3;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #3d3326;
  padding: 16px 14px;
}

.text-center { text-align: center; }
.text-left { text-align: left; }

tbody tr {
  border-bottom: 1px solid #ddccae;
  transition: background 0.15s ease-in-out;
}

tbody tr:last-child {
  border-bottom: none;
}

tbody tr:hover {
  background: #e6d8bb;
}

tbody td {
  padding: 14px;
  font-size: 14px;
  vertical-align: middle;
}

.person {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  background: #d8cba9;
  flex-shrink: 0;
}

.name {
  font-weight: 600;
  font-size: 15px;
}

.email, .role, .joined {
  color: #555;
  font-size: 14px;
}

.view-link {
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
}

.view-link:hover { text-decoration: underline; }

@media(max-width: 768px) {
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
                <div class="name"><?php echo htmlspecialchars($u['first_name'] . " " . $u['last_name']); ?></div>
              </div>
            </td>
            <td class="text-left email"><?php echo htmlspecialchars($u['email']); ?></td>
            <td class="text-center role"><?php echo htmlspecialchars($u['role']); ?></td>
            <td class="text-center joined"><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
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