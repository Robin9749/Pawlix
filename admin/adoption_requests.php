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
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Adoption Requests</title>

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

/* Tabs */
.tabs{
  display: flex;
  gap: 34px;
  border-bottom: 1px solid #d8c9a3;
  margin-bottom: 24px;
}

.tabs a{
  text-decoration: none;
  color: #6b6b6b;
  font-weight: 600;
  font-size: 15px;
  padding-bottom: 14px;
  border-bottom: 3px solid transparent;
}

.tabs a.active{
  color: #f2932b;
  border-bottom-color: #f2932b;
}

/* Table card */
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

.dog-photo{
  width: 44px;
  height: 44px;
  border-radius: 8px;
  object-fit: cover;
  background: #d8cba9;
  flex-shrink: 0;
}

.person .name{
  font-weight: 600;
  font-size: 15px;
}

.person .sub{
  color: #888;
  font-size: 13px;
  margin-top: 2px;
}

.status{
  padding: 6px 16px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 13px;
  display: inline-block;
}

.status-pending{ background: #f6cba3; color: #a15c00; }
.status-approved{ background: #bfe3c4; color: #1e6e2e; }
.status-review{ background: #b9d3ee; color: #1958ab; }
.status-rejected{ background: #f5bcbc; color: #b3261e; }

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
    <a href="adoption_requests.php" class="active"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
</div>

  <div class="main">

    <h1>Adoption Requests</h1>

    <div class="tabs">
      <a href="?status=All" class="<?php echo $filter=='All' ? 'active' : ''; ?>">All (<?php echo $total; ?>)</a>
      <a href="?status=Pending" class="<?php echo $filter=='Pending' ? 'active' : ''; ?>">Pending (<?php echo $counts['Pending']; ?>)</a>
      <a href="?status=Approved" class="<?php echo $filter=='Approved' ? 'active' : ''; ?>">Approved (<?php echo $counts['Approved']; ?>)</a>
      <a href="?status=Rejected" class="<?php echo $filter=='Rejected' ? 'active' : ''; ?>">Rejected (<?php echo $counts['Rejected']; ?>)</a>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <td width="60">S.N.</td>
            <td>Adopter</td>
            <td>Dog</td>
            <td>Date</td>
            <td>Status</td>
            <td width="80">Action</td>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while($r=mysqli_fetch_assoc($requests)) { ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td>
              <div class="person">
                <img class="avatar" src="../assets/img/user-placeholder.jpg">
                <div>
                  <div class="name"><?php echo $r['first_name']." ".$r['last_name']; ?></div>
                  <div class="sub"><?php echo $r['email']; ?></div>
                </div>
              </div>
            </td>
            <td>
              <div class="person">
                <img class="dog-photo" src="../assets/img/<?php echo $r['image']; ?>" alt="Dog">
                <div>
                  <div class="name"><?php echo $r['name']; ?></div>
                  <div class="sub"><?php echo $r['breed']; ?></div>
                </div>
              </div>
            </td>
            <td><?php echo date("M d, Y",strtotime($r['application_date'])); ?></td>
            <td>
              <?php
                $cls = "status-pending";
                if ($r['status'] == "Approved") $cls = "status-approved";

                if ($r['status'] == "Rejected") $cls = "status-rejected";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo $r['status']; ?></span>
            </td>
            <td><a class="view-link" href="view_request.php?id=<?php echo $r['application_id']; ?>">View</a></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>