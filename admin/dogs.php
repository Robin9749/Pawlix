<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$success = "";
$error = "";

/* SAFE HELPER TO ADD COLUMNS WITHOUT DUPLICATE COLUMN EXCEPTION IN PHP 8.1+ */
if (!function_exists('safeAddColumnDogs')) {
    function safeAddColumnDogs($conn, $table, $column, $definition) {
        try {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {}
    }
}

safeAddColumnDogs($conn, 'dog', 'color', "VARCHAR(50) DEFAULT ''");
safeAddColumnDogs($conn, 'dog', 'size', "VARCHAR(50) DEFAULT ''");
safeAddColumnDogs($conn, 'dog', 'weight', "FLOAT DEFAULT 0");
safeAddColumnDogs($conn, 'dog', 'vaccination_status', "VARCHAR(100) DEFAULT ''");
safeAddColumnDogs($conn, 'dog', 'health_status', "VARCHAR(255) DEFAULT ''");
safeAddColumnDogs($conn, 'dog', 'adoption_status', "ENUM('Available', 'Pending', 'Adopted') DEFAULT 'Available'");

/* ================= DELETE DOG ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM dog WHERE dog_id = $id");
    header("Location: dogs.php?msg=deleted");
    exit();
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success = "Dog deleted successfully!";
}

/* ================= ADD DOG ================= */
if (isset($_POST['add_dog'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $breed = mysqli_real_escape_string($conn, trim($_POST['breed']));
    
    if ($breed === 'Other' && !empty($_POST['custom_breed'])) {
        $breed = mysqli_real_escape_string($conn, trim($_POST['custom_breed']));
    }
    
    $age = intval($_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $color = mysqli_real_escape_string($conn, trim($_POST['color']));
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $weight = floatval($_POST['weight']);
    $vaccination_status = mysqli_real_escape_string($conn, $_POST['vaccination_status']);
    $health_status = mysqli_real_escape_string($conn, trim($_POST['health_status']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    $upload_folder = "../uploads/";
    $uploaded_images = [];

    if (!is_dir($upload_folder)) {
        mkdir($upload_folder, 0777, true);
    }

    if (
        empty($name) ||
        empty($breed) ||
        empty($age) ||
        empty($gender) ||
        empty($color) ||
        empty($size) ||
        empty($weight) ||
        empty($vaccination_status) ||
        empty($health_status) ||
        empty($description) ||
        empty($_FILES['images']['name'][0])
    ) {
        $error = "Please fill all required fields and upload at least one image.";
    } else {
        $total_files = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            $image_name = $_FILES['images']['name'][$i];
            $temp_name = $_FILES['images']['tmp_name'][$i];
            $error_code = $_FILES['images']['error'][$i];

            if ($error_code === UPLOAD_ERR_OK && !empty($image_name)) {
                $clean_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($image_name));
                $image_name_save = time() . "_" . $i . "_" . $clean_filename;

                if (move_uploaded_file($temp_name, $upload_folder . $image_name_save)) {
                    $uploaded_images[] = $image_name_save;
                }
            }
        }

        if (!empty($uploaded_images)) {
            $image_string = implode(",", $uploaded_images);

            $sql = "INSERT INTO dog 
            (name, breed, age, gender, color, size, weight, vaccination_status, health_status, description, image, adoption_status)
            VALUES
            ('$name', '$breed', '$age', '$gender', '$color', '$size', '$weight', '$vaccination_status', '$health_status', '$description', '$image_string', 'Available')";

            if (mysqli_query($conn, $sql)) {
                $success = "Dog added successfully!";
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        } else {
            $error = "Failed to upload images.";
        }
    }
}

/* ================= UPDATE / EDIT DOG ================= */
if (isset($_POST['update_dog'])) {
    $dog_id = intval($_POST['dog_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $breed = mysqli_real_escape_string($conn, trim($_POST['breed']));
    
    if ($breed === 'Other' && !empty($_POST['custom_breed'])) {
        $breed = mysqli_real_escape_string($conn, trim($_POST['custom_breed']));
    }
    
    $age = intval($_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $color = mysqli_real_escape_string($conn, trim($_POST['color']));
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $weight = floatval($_POST['weight']);
    $vaccination_status = mysqli_real_escape_string($conn, $_POST['vaccination_status']);
    $health_status = mysqli_real_escape_string($conn, trim($_POST['health_status']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $adoption_status = mysqli_real_escape_string($conn, $_POST['adoption_status'] ?? 'Available');

    $upload_folder = "../uploads/";
    $uploaded_images = [];

    if (!is_dir($upload_folder)) {
        mkdir($upload_folder, 0777, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        $total_files = count($_FILES['images']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $image_name = $_FILES['images']['name'][$i];
            $temp_name = $_FILES['images']['tmp_name'][$i];
            $error_code = $_FILES['images']['error'][$i];

            if ($error_code === UPLOAD_ERR_OK && !empty($image_name)) {
                $clean_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($image_name));
                $image_name_save = time() . "_" . $i . "_" . $clean_filename;

                if (move_uploaded_file($temp_name, $upload_folder . $image_name_save)) {
                    $uploaded_images[] = $image_name_save;
                }
            }
        }
    }

    if (!empty($uploaded_images)) {
        $image_string = implode(",", $uploaded_images);
        $image_update_sql = ", image = '$image_string'";
    } else {
        $image_update_sql = "";
    }

    $update_sql = "UPDATE dog SET 
        name = '$name', 
        breed = '$breed', 
        age = '$age', 
        gender = '$gender', 
        color = '$color', 
        size = '$size', 
        weight = '$weight', 
        vaccination_status = '$vaccination_status', 
        health_status = '$health_status', 
        description = '$description',
        adoption_status = '$adoption_status'
        $image_update_sql
        WHERE dog_id = $dog_id";

    if (mysqli_query($conn, $update_sql)) {
        $success = "Dog details updated successfully!";
    } else {
        $error = "Failed to update dog: " . mysqli_error($conn);
    }
}

$dogs = mysqli_query($conn, "SELECT * FROM dog ORDER BY dog_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Dogs | PawLix Admin</title>

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

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.main-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.add-btn {
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
  transition: background 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.add-btn:hover {
  background: var(--primary-orange-hover);
}

.alert-success {
  background: #d4edda;
  color: #155724;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
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
col.col-photo { width: 18%; }
col.col-name { width: 20%; }
col.col-breed { width: 22%; }
col.col-age { width: 10%; }
col.col-gender { width: 10%; }
col.col-status { width: 12%; }
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

.img-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  position: relative;
}

.dog-photo {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  object-fit: cover;
  background: #d8cba9;
  border: 1px solid rgba(0,0,0,0.06);
}

.photo-count {
  font-size: 10px;
  background: var(--primary-orange);
  color: #fff;
  padding: 2px 6px;
  border-radius: 10px;
  font-weight: 600;
}

.status {
  padding: 5px 14px;
  border-radius: var(--radius-pill);
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.status-available { background: #bfe3c4; color: #1e6e2e; }
.status-adopted { background: #b9d3ee; color: #1958ab; }
.status-pending { background: #f6cba3; color: #a15c00; }

.action-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.edit-btn {
  background: #ede8f8;
  color: #5b3fd6;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.edit-btn:hover {
  background: #5b3fd6;
  color: #ffffff;
}

.delete-btn {
  background: #fde8e8;
  color: #b3261e;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.delete-btn:hover {
  background: #b3261e;
  color: #ffffff;
}

.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  padding: 20px;
  overflow-y: auto;
}

.modal-content {
  background: var(--bg-card);
  width: 100%;
  max-width: 750px;
  border-radius: 14px;
  padding: 26px;
  box-shadow: 0 15px 30px rgba(0,0,0,0.25);
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
  animation: popup 0.3s ease-out;
}

@keyframes popup {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 2px solid var(--border-color);
  padding-bottom: 10px;
}

.modal-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: var(--text-dark);
}

.close-modal {
  background: none;
  border: none;
  font-size: 26px;
  font-weight: bold;
  cursor: pointer;
  color: #666;
}

.close-modal:hover { color: var(--primary-orange); }

.modal-body form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.modal-body .full { grid-column: 1 / 3; }

.modal-body label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
}

.modal-body input,
.modal-body select,
.modal-body textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  outline: none;
  background: #ffffff;
  font-size: 13px;
  font-family: inherit;
}

.modal-body input:focus,
.modal-body select:focus,
.modal-body textarea:focus {
  border-color: var(--primary-orange);
  box-shadow: 0 0 5px rgba(242,147,43,.3);
}

.modal-body textarea {
  resize: none;
  height: 90px;
}

.submit-btn {
  grid-column: 1 / 3;
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 13px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 600;
  transition: background 0.3s;
}

.submit-btn:hover { background: var(--primary-orange-hover); }

@media (max-width: 850px) {
  .layout {
    flex-direction: column;
  }
  .sidebar {
    width: 100%;
    min-height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
  .main {
    padding: 20px;
  }
  .modal-body form { grid-template-columns: 1fr; }
  .modal-body .full, .submit-btn { grid-column: 1; }
  table { table-layout: auto; }
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
    <a href="dogs.php" class="active"><span class="icon">🐾</span> Dogs</a>
    <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <?php if ($success != "") { ?>
      <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
      <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <div class="main-header">
      <h1>All Dogs</h1>
      <button type="button" class="add-btn" onclick="openAddModal()">+ Add New Dog</button>
    </div>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-photo">
          <col class="col-name">
          <col class="col-breed">
          <col class="col-age">
          <col class="col-gender">
          <col class="col-status">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-center">Photo</th>
            <th class="text-left">Name</th>
            <th class="text-left">Breed</th>
            <th class="text-center">Age</th>
            <th class="text-center">Gender</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if (!$dogs || mysqli_num_rows($dogs) === 0) {
          ?>
          <tr>
            <td colspan="8" class="text-center" style="padding:25px; color:#665444;">No dogs found. Click "+ Add New Dog" to add one!</td>
          </tr>
          <?php
          } else {
            $i = 1; 
            while($d = mysqli_fetch_assoc($dogs)) { 
              $images_array = explode(",", $d['image']);
              $first_image = !empty($images_array[0]) ? trim($images_array[0]) : 'default.jpg';
              $image_count = count($images_array);
              $status_val = $d['adoption_status'] ?? 'Available';
            ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td class="text-center">
                <div class="img-container">
                  <img class="dog-photo" src="../uploads/<?php echo $first_image; ?>" onerror="this.src='../assets/img/default.jpg';" alt="Dog">
                  <?php if ($image_count > 1) { ?>
                    <span class="photo-count">+<?php echo ($image_count - 1); ?></span>
                  <?php } ?>
                </div>
              </td>
              <td class="text-left"><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
              <td class="text-left"><?php echo htmlspecialchars($d['breed']); ?></td>
              <td class="text-center"><?php echo htmlspecialchars($d['age']); ?> yrs</td>
              <td class="text-center"><?php echo htmlspecialchars($d['gender']); ?></td>
              <td class="text-center">
                <?php
                  $cls = "status-available";
                  if ($status_val == "Adopted") $cls = "status-adopted";
                  if ($status_val == "Pending") $cls = "status-pending";
                ?>
                <span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($status_val); ?></span>
              </td>
              <td class="text-center">
                <div class="action-links">
                  <button 
                    type="button"
                    class="edit-btn" 
                    title="Edit Dog"
                    onclick="openEditModal(
                      <?php echo $d['dog_id']; ?>, 
                      '<?php echo addslashes($d['name']); ?>', 
                      '<?php echo addslashes($d['breed']); ?>', 
                      <?php echo intval($d['age']); ?>, 
                      '<?php echo addslashes($d['gender']); ?>', 
                      '<?php echo addslashes($d['color'] ?? ''); ?>', 
                      '<?php echo addslashes($d['size'] ?? ''); ?>', 
                      '<?php echo floatval($d['weight'] ?? 0); ?>', 
                      '<?php echo addslashes($d['vaccination_status'] ?? ''); ?>', 
                      '<?php echo addslashes($d['health_status'] ?? ''); ?>', 
                      '<?php echo addslashes(str_replace(array("\r", "\n"), ' ', $d['description'] ?? '')); ?>', 
                      '<?php echo addslashes($status_val); ?>'
                    )"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                  </button>
                  <a class="delete-btn" href="dogs.php?delete=<?php echo $d['dog_id']; ?>" onclick="return confirm('Are you sure you want to delete <?php echo addslashes($d['name']); ?>?');" title="Delete">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php } ?>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>


<div class="modal-overlay" id="addDogModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Dog</h2>
      <button class="close-modal" onclick="closeAddModal()">&times;</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="dogs.php" enctype="multipart/form-data">
        <div>
          <label>Dog Name *</label>
          <input type="text" name="name" required>
        </div>

        <div>
          <label>Breed *</label>
          <select name="breed" id="breedSelect" onchange="toggleCustomBreed(this)" required>
            <option value="">Select Breed</option>
            <option value="German Shepherd">German Shepherd</option>
            <option value="Labrador Retriever">Labrador Retriever</option>
            <option value="Golden Retriever">Golden Retriever</option>
            <option value="Japanese Spitz">Japanese Spitz</option>
            <option value="Tibetan Mastiff (Bhote Kukur)">Tibetan Mastiff (Bhote Kukur)</option>
            <option value="Himalayan Sheepdog (Bhotia Kukur)">Himalayan Sheepdog (Bhotia Kukur)</option>
            <option value="Local / Cross Breed (Local Kukur)">Local / Cross Breed (Local Kukur)</option>
            <option value="Beagle">Beagle</option>
            <option value="Pug">Pug</option>
            <option value="Husky">Husky</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div id="customBreedGroup" style="display: none;" class="full">
          <label>Specify Custom Breed Name</label>
          <input type="text" name="custom_breed" id="customBreedInput">
        </div>

        <div>
          <label>Age (years) *</label>
          <input type="number" name="age" min="0" required>
        </div>

        <div>
          <label>Gender *</label>
          <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label>Color *</label>
          <input type="text" name="color" required>
        </div>

        <div>
          <label>Size *</label>
          <select name="size" required>
            <option value="">Select Size</option>
            <option value="Small">Small</option>
            <option value="Medium">Medium</option>
            <option value="Large">Large</option>
          </select>
        </div>

        <div>
          <label>Weight (kg) *</label>
          <input type="number" step="0.01" name="weight" required>
        </div>

        <div>
          <label>Vaccination Status *</label>
          <select name="vaccination_status" required>
            <option value="">Select</option>
            <option value="Vaccinated">Vaccinated</option>
            <option value="Not Vaccinated">Not Vaccinated</option>
          </select>
        </div>

        <div class="full">
          <label>Health Status *</label>
          <input type="text" name="health_status" required>
        </div>

        <div class="full">
          <label>Description *</label>
          <textarea name="description" required></textarea>
        </div>

        <div class="full">
          <label>Dog Images *</label>
          <input type="file" name="images[]" accept="image/*" multiple required>
        </div>

        <button type="submit" name="add_dog" class="submit-btn">+ Add Dog</button>
      </form>
    </div>
  </div>
</div>


<div class="modal-overlay" id="editDogModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Edit Dog Details</h2>
      <button class="close-modal" onclick="closeEditModal()">&times;</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="dogs.php" enctype="multipart/form-data">
        <input type="hidden" name="update_dog" value="1">
        <input type="hidden" name="dog_id" id="editDogId" value="">

        <div>
          <label>Dog Name *</label>
          <input type="text" name="name" id="editName" required>
        </div>

        <div>
          <label>Breed *</label>
          <select name="breed" id="editBreedSelect" onchange="toggleEditCustomBreed(this)" required>
            <option value="German Shepherd">German Shepherd</option>
            <option value="Labrador Retriever">Labrador Retriever</option>
            <option value="Golden Retriever">Golden Retriever</option>
            <option value="Japanese Spitz">Japanese Spitz</option>
            <option value="Tibetan Mastiff (Bhote Kukur)">Tibetan Mastiff (Bhote Kukur)</option>
            <option value="Himalayan Sheepdog (Bhotia Kukur)">Himalayan Sheepdog (Bhotia Kukur)</option>
            <option value="Local / Cross Breed (Local Kukur)">Local / Cross Breed (Local Kukur)</option>
            <option value="Beagle">Beagle</option>
            <option value="Pug">Pug</option>
            <option value="Husky">Husky</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div id="editCustomBreedGroup" style="display: none;" class="full">
          <label>Specify Custom Breed Name</label>
          <input type="text" name="custom_breed" id="editCustomBreedInput">
        </div>

        <div>
          <label>Age (years) *</label>
          <input type="number" name="age" id="editAge" min="0" required>
        </div>

        <div>
          <label>Gender *</label>
          <select name="gender" id="editGender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label>Color *</label>
          <input type="text" name="color" id="editColor" required>
        </div>

        <div>
          <label>Size *</label>
          <select name="size" id="editSize" required>
            <option value="Small">Small</option>
            <option value="Medium">Medium</option>
            <option value="Large">Large</option>
          </select>
        </div>

        <div>
          <label>Weight (kg) *</label>
          <input type="number" step="0.01" name="weight" id="editWeight" required>
        </div>

        <div>
          <label>Vaccination Status *</label>
          <select name="vaccination_status" id="editVaccinationStatus" required>
            <option value="Vaccinated">Vaccinated</option>
            <option value="Not Vaccinated">Not Vaccinated</option>
          </select>
        </div>

        <div>
          <label>Adoption Status *</label>
          <select name="adoption_status" id="editAdoptionStatus" required>
            <option value="Available">Available</option>
            <option value="Pending">Pending</option>
            <option value="Adopted">Adopted</option>
          </select>
        </div>

        <div class="full">
          <label>Health Status *</label>
          <input type="text" name="health_status" id="editHealthStatus" required>
        </div>

        <div class="full">
          <label>Description *</label>
          <textarea name="description" id="editDescription" required></textarea>
        </div>

        <div class="full">
          <label>Replace Images (Optional - leave empty to keep existing images)</label>
          <input type="file" name="images[]" accept="image/*" multiple>
        </div>

        <button type="submit" class="submit-btn">Save & Update Dog Details ➔</button>
      </form>
    </div>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('addDogModal').style.display = 'flex';
}

function closeAddModal() {
  document.getElementById('addDogModal').style.display = 'none';
}

function openEditModal(id, name, breed, age, gender, color, size, weight, vaccinationStatus, healthStatus, description, adoptionStatus) {
  document.getElementById('editDogId').value = id;
  document.getElementById('editName').value = name;
  
  const breedSelect = document.getElementById('editBreedSelect');
  let optionFound = false;
  for (let i = 0; i < breedSelect.options.length; i++) {
    if (breedSelect.options[i].value === breed) {
      breedSelect.selectedIndex = i;
      optionFound = true;
      break;
    }
  }
  
  if (!optionFound) {
    breedSelect.value = 'Other';
    document.getElementById('editCustomBreedGroup').style.display = 'block';
    document.getElementById('editCustomBreedInput').value = breed;
  } else {
    document.getElementById('editCustomBreedGroup').style.display = 'none';
    document.getElementById('editCustomBreedInput').value = '';
  }

  document.getElementById('editAge').value = age;
  document.getElementById('editGender').value = gender;
  document.getElementById('editColor').value = color;
  document.getElementById('editSize').value = size;
  document.getElementById('editWeight').value = weight;
  document.getElementById('editVaccinationStatus').value = vaccinationStatus;
  document.getElementById('editHealthStatus').value = healthStatus;
  document.getElementById('editDescription').value = description;
  document.getElementById('editAdoptionStatus').value = adoptionStatus;

  document.getElementById('editDogModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editDogModal').style.display = 'none';
}

window.onclick = function(event) {
  var addModal = document.getElementById('addDogModal');
  var editModal = document.getElementById('editDogModal');
  if (event.target == addModal) {
    closeAddModal();
  }
  if (event.target == editModal) {
    closeEditModal();
  }
}

function toggleCustomBreed(selectElement) {
  var customGroup = document.getElementById('customBreedGroup');
  var customInput = document.getElementById('customBreedInput');
  if (selectElement.value === 'Other') {
    customGroup.style.display = 'block';
    customInput.setAttribute('required', 'required');
  } else {
    customGroup.style.display = 'none';
    customInput.removeAttribute('required');
    customInput.value = '';
  }
}

function toggleEditCustomBreed(selectElement) {
  var customGroup = document.getElementById('editCustomBreedGroup');
  var customInput = document.getElementById('editCustomBreedInput');
  if (selectElement.value === 'Other') {
    customGroup.style.display = 'block';
    customInput.setAttribute('required', 'required');
  } else {
    customGroup.style.display = 'none';
    customInput.removeAttribute('required');
    customInput.value = '';
  }
}

<?php if ($error != "" && !isset($_POST['update_dog'])) { ?>
openAddModal();
<?php } ?>
</script>

</body>
</html>