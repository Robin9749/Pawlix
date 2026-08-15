<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM dog");
$totalDogs = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application");
$adoptionReqs = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user");
$totalUsers = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM contact_message");
$unreadMsgs = mysqli_fetch_assoc($result)['total'];

$requests = mysqli_query($conn,"
SELECT
adoption_application.*,
user.first_name,
user.last_name,
user.email,
dog.name AS dog_name,
dog.image AS dog_image
FROM adoption_application
JOIN user ON adoption_application.user_id=user.user_id
JOIN dog ON adoption_application.dog_id=dog.dog_id
ORDER BY application_date DESC
LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Overview - PawLix</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

*{
  box-sizing: border-box;
}

body{
  font-family: 'Poppins', Arial, sans-serif;
  margin: 0;
  background: #e8dcc0;
  color: #2b2b2b;
  min-height: 100vh;
  overflow-x: hidden;
  overflow-y: hidden;
}

.topbar{
  background: #f2e6c9;
  padding: 20px 45px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
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
}

.sidebar{
  width: 240px;
  background: #e8dcc0;
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
  border-radius: 10px;
  text-decoration: none;
  color: #2b2b2b;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.sidebar a:hover{
  background: #ddceac;
}

.sidebar a.active{
  background: #f2932b;
  color: white;
  box-shadow: 0 4px 12px rgba(242,147,43,0.35);
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

.stats{
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card{
  background: #ede1c6;
  border-radius: 14px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover{
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.08);
}

.icon-circle{
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.icon-orange{ background: #f6cba3; }
.icon-green{ background: #bfe3c4; }
.icon-blue{ background: #b9d3ee; }
.icon-peach{ background: #f6c9a3; }

.stat-card .label{
  color: #5c5c5c;
  font-size: 13px;
  margin-bottom: 2px;
  font-weight: 500;
}

.stat-card .number{
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.1;
}

.requests-box{
  background: #ede1c6;
  border-radius: 14px;
  padding: 24px 26px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.requests-box .header{
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.requests-box .header h2{
  margin: 0;
  font-size: 17px;
  font-weight: 650;
  color: #1a1a1a;
}

.requests-box .header a{
  color: #1f6fd6;
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
}

.requests-box .header a:hover{
  text-decoration: underline;
}

table{
  width: 100%;
  border-collapse: collapse;
}

table tr{
  border-bottom: 1px solid #ddccae;
}

table tr:last-child{
  border-bottom: none;
}

table td{
  padding: 13px 8px;
  vertical-align: middle;
  font-size: 13px;
}

.date-cell {
  color: #555555;
  font-weight: 500;
}

.person{
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar{
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  background: #4a3223;
  color: #f3ecd5;
  font-weight: 700;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.6);
}

.person .name{
  font-weight: 600;
  font-size: 14px;
  color: #1a1a1a;
}

.person .email{
  color: #777777;
  font-size: 12px;
  margin-top: 1px;
}

/* STATUS BADGES */
.status{
  padding: 5px 14px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.status-pending{
  background: #f6cba3;
  color: #a15c00;
}

.status-approved{
  background: #bfe3c4;
  color: #1e6e2e;
}

.status-review{
  background: #b9d3ee;
  color: #1958ab;
}

.status-rejected{
  background: #f5bcbc;
  color: #b3261e;
}

@media (max-width: 850px){
  .layout{
    flex-direction: column;
  }
  .sidebar{
    width: 100%;
    min-height: auto;
  }
  .main{
    padding: 20px;
  }
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
    <a href="dashboard.php" class="active"><span class="icon">🏠</span> Dashboard</a>
    <a href="dogs.php"><span class="icon">🐾</span> Dogs</a>
    <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Dashboard Overview</h1>

    <div class="stats">

      <div class="stat-card">
        <div class="icon-circle icon-orange">🐾</div>
        <div>
          <div class="label">Total Dogs</div>
          <div class="number"><?php echo $totalDogs; ?></div>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-circle icon-green">📋</div>
        <div>
          <div class="label">Adoption Request</div>
          <div class="number"><?php echo $adoptionReqs; ?></div>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-circle icon-blue">👤</div>
        <div>
          <div class="label">Total Users</div>
          <div class="number"><?php echo $totalUsers; ?></div>
        </div>
      </div>

      <div class="stat-card">
        <div class="icon-circle icon-peach">✉️</div>
        <div>
          <div class="label">Unread Messages</div>
          <div class="number"><?php echo $unreadMsgs; ?></div>
        </div>
      </div>

    </div>

    <div class="requests-box">

      <div class="header">
        <h2>Recent Adoption Requests</h2>
        <a href="adoption_requests.php">View All</a>
      </div>

      <table>
        <?php while($r=mysqli_fetch_assoc($requests)){ 
          // User Initials Avatar
          $user_initials = strtoupper(substr($r['first_name'] ?? 'U', 0, 1) . substr($r['last_name'] ?? '', 0, 1));
          
          // Dog Image Path Resolution
          $dog_img_name = trim($r['dog_image'] ?? '');
          if (strpos($dog_img_name, ',') !== false) {
              $img_arr = explode(',', $dog_img_name);
              $dog_img_name = trim($img_arr[0]);
          }
          
          if (!empty($dog_img_name) && file_exists('../uploads/' . $dog_img_name)) {
              $dog_img_src = '../uploads/' . $dog_img_name;
          } elseif (!empty($dog_img_name) && file_exists('../assets/images/' . $dog_img_name)) {
              $dog_img_src = '../assets/images/' . $dog_img_name;
          } else {
              $dog_img_src = '../assets/images/default-dog.jpg';
          }
        ?>
        <tr>
          <td>
            <div class="person">
              <div class="avatar"><?php echo htmlspecialchars($user_initials); ?></div>
              <div>
                <div class="name"><?php echo htmlspecialchars($r['first_name']." ".$r['last_name']); ?></div>
                <div class="email"><?php echo htmlspecialchars($r['email']); ?></div>
              </div>
            </div>
          </td>
          <td>
            <div class="person">
              <img class="avatar" src="<?php echo htmlspecialchars($dog_img_src); ?>" alt="Dog" onerror="this.onerror=null;this.src='../assets/images/default-dog.jpg';">
              <div>
                <div class="name"><?php echo htmlspecialchars($r['dog_name']); ?></div>
              </div>
            </div>
          </td>
          <td class="date-cell"><?php echo date("M d, Y", strtotime($r['application_date'])); ?></td>
          <td>
            <?php
              $statusClass = "status-pending";

              if ($r['status'] == "Approved") {
                $statusClass = "status-approved";
              }

              if ($r['status'] == "Rejected") {
                $statusClass = "status-rejected";
              }
            ?>
            <span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
          </td>
        </tr>
        <?php } ?>
      </table>

    </div>

  </div>

</div>

</body>
</html>