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
    dog.name AS dog_name,
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
    dog.name AS dog_name,
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adoption Requests | PawLix Admin</title>

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
col.col-applicant { width: 25%; }
col.col-dog { width: 20%; }
col.col-date { width: 18%; }
col.col-status { width: 15%; }
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

.status {
  padding: 5px 14px;
  border-radius: var(--radius-pill);
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.status-approved { background: #bfe3c4; color: #1e6e2e; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending { background: #f6cba3; color: #a15c00; }

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
        <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
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
                
                <div class="name"><?php echo htmlspecialchars($r['first_name'] . " " . $r['last_name']); ?></div>
              </div>
            </td>
            <td class="text-left"><strong><?php echo htmlspecialchars($r['dog_name'] ?? $r['name']); ?></strong></td>
            <td class="text-center"><?php echo date("M d, Y", strtotime($r['application_date'] ?? $r['created_at'] ?? 'now')); ?></td>
            <td class="text-center">
              <?php
                $cls = "status-pending";
                if (($r['status'] ?? '') == "Approved") $cls = "status-approved";
                if (($r['status'] ?? '') == "Rejected") $cls = "status-rejected";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($r['status'] ?? 'Pending'); ?></span>
            </td>
            <td class="text-center"><a class="view-link" href="view_request.php?id=<?php echo $r['application_id'] ?? $r['request_id']; ?>">View</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>