<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");


$filter = isset($_GET['status']) ? $_GET['status'] : 'All';

if($filter=="All")
{
    $requests=mysqli_query($conn,"
    SELECT
    adoption_application.*,
    user.first_name,
    user.last_name,
    user.email,
    dog.name,
    dog.breed,
    dog.image
    FROM adoption_application
    JOIN user ON adoption_application.user_id=user.user_id
    JOIN dog ON adoption_application.dog_id=dog.dog_id
    ");
}
else
{
    $requests=mysqli_query($conn,"
    SELECT
    adoption_application.*,
    user.first_name,
    user.last_name,
    user.email,
    dog.name,
    dog.breed,
    dog.image
    FROM adoption_application
    JOIN user ON adoption_application.user_id=user.user_id
    JOIN dog ON adoption_application.dog_id=dog.dog_id
    WHERE adoption_application.status='$filter'
    ");
}

$result=mysqli_query($conn,"
SELECT status,COUNT(*) total
FROM adoption_application
GROUP BY status
");

$counts=array(
"Pending"=>0,
"Approved"=>0,
"Rejected"=>0
);

while($row=mysqli_fetch_assoc($result))
{
    $counts[$row['status']]=$row['total'];
}
$totalResult = mysqli_query($conn,"SELECT COUNT(*) AS total FROM adoption_application");
$total = mysqli_fetch_assoc($totalResult)['total'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Adoption Requests | PawLix Admin</title>

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
col.col-applicant { width: 25%; }
col.col-dog { width: 20%; }
col.col-date { width: 18%; }
col.col-status { width: 15%; }
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

.status {
  padding: 6px 16px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 13px;
  display: inline-block;
}

.status-approved { background: #bfe3c4; color: #1e6e2e; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending { background: #f6cba3; color: #a15c00; }

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
    <a href="adoption_requests.php" class="active"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Adoption Requests</h1>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-applicant">
          <col class="col-dog">
          <col class="col-date">
          <col class="col-status">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-left">APPLICANT</th>
            <th class="text-left">DOG</th>
            <th class="text-center">REQUEST DATE</th>
            <th class="text-center">STATUS</th>
            <th class="text-center">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while($r = mysqli_fetch_assoc($requests)) { ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td class="text-left">
              <div class="person">
                <img class="avatar" src="../assets/img/user-placeholder.jpg" alt="User">
                <div class="name"><?php echo htmlspecialchars($r['first_name'] . " " . $r['last_name']); ?></div>
              </div>
            </td>
            <td class="text-left"><strong><?php echo htmlspecialchars($r['dog_name']); ?></strong></td>
            <td class="text-center"><?php echo date("M d, Y", strtotime($r['created_at'])); ?></td>
            <td class="text-center">
              <?php
                $cls = "status-pending";
                if ($r['status'] == "Approved") $cls = "status-approved";
                if ($r['status'] == "Rejected") $cls = "status-rejected";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($r['status'] ?? 'Pending'); ?></span>
            </td>
            <td class="text-center"><a class="view-link" href="view_request.php?id=<?php echo $r['request_id']; ?>">View</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>