<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM Dogs WHERE dog_id = $id");
    header("Location: dogs.php");
    exit();
}

$dogs = mysqli_query($conn,"
SELECT *
FROM dog
ORDER BY dog_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>All Dogs</title>

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

.main-header{
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
}

.main-header h1{
  margin: 0;
  font-size: 28px;
  font-weight: 700;
}

.add-btn{
  background: #f2932b;
  color: white;
  border: none;
  padding: 13px 26px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
}

.add-btn:hover{
  background: #d97f1e;
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

.dog-photo{
  width: 46px;
  height: 46px;
  border-radius: 8px;
  object-fit: cover;
  background: #d8cba9;
}

.status{
  padding: 6px 16px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 13px;
  display: inline-block;
}

.status-available{ background: #bfe3c4; color: #1e6e2e; }
.status-adopted{ background: #b9d3ee; color: #1958ab; }
.status-pending{ background: #f6cba3; color: #a15c00; }

.edit-link{
  color: #5b3fd6;
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
  margin-right: 14px;
}

.edit-link:hover{ text-decoration: underline; }

.delete-btn{
  background: #f5bcbc;
  color: #b3261e;
  border: none;
  padding: 6px 14px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
}

.delete-btn:hover{
  background: #f0a3a3;
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
    <a href="dogs.php" class="active"><span class="icon">🐾</span> Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <div class="main-header">
      <h1>All Dogs</h1>
      <a href="add_dog.php" class="add-btn">Add New Dog</a>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <td width="60">S.N.</td>
            <td>Photo</td>
            <td>Name</td>
            <td>Breed</td>
            <td>Age</td>
            <td>Gender</td>
            <td>Status</td>
            <td width="140">Action</td>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while($d=mysqli_fetch_assoc($dogs)) { ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><img class="dog-photo" src="../assets/img/<?php echo $d['image']; ?>" alt="Dog"></td>
            <td><?php echo $d['name']; ?></td>
            <td><?php echo $d['breed']; ?></td>
            <td><?php echo $d['age']; ?></td>
            <td><?php echo $d['gender']; ?></td>
            <td>
              <?php
                $cls = "status-available";
                if ($d['adoption_status']== "Adopted") $cls = "status-adopted";
                if ($d['adoption_status']== "Pending") $cls = "status-pending";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo $d['adoption_status']; ?></span>
            </td>
            <td>
              <a class="edit-link" href="edit_dog.php?id=<?php echo $d['dog_id'] ?>">Edit</a>
              <a class="delete-btn" href="dogs.php?delete=<?php echo $d['dog_id']; ?>" onclick="return confirm('Delete this dog?');">Delete</a>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

</body>
</html>