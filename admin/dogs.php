<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$success = "";
$error = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM dog WHERE dog_id = $id");
    header("Location: dogs.php");
    exit();
}

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
            (name, breed, age, gender, color, size, weight, vaccination_status, health_status, description, image)
            VALUES
            ('$name', '$breed', '$age', '$gender', '$color', '$size', '$weight', '$vaccination_status', '$health_status', '$description', '$image_string')";

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

$dogs = mysqli_query($conn, "SELECT * FROM dog ORDER BY dog_id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>All Dogs | PawLix Admin</title>

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

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
}

.main-header h1 {
  font-size: 28px;
  font-weight: 700;
}

.add-btn {
  background: #f2932b;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(242,147,43,0.35);
  transition: background 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.add-btn:hover { background: #d97f1e; }

.alert-success {
  background: #d4edda;
  color: #155724;
  padding: 12px 18px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 600;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 18px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 600;
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
col.col-photo { width: 20%; }
col.col-name { width: 20%; }
col.col-breed { width: 22%; }
col.col-age { width: 10%; }
col.col-gender { width: 12%; }
col.col-status { width: 14%; }
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

.img-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  position: relative;
}

.dog-photo {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  object-fit: cover;
  background: #d8cba9;
  border: 1px solid rgba(0,0,0,0.06);
}

.photo-count {
  font-size: 10px;
  background: #f2932b;
  color: #fff;
  padding: 2px 6px;
  border-radius: 10px;
  font-weight: 600;
}

.status {
  padding: 6px 16px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 13px;
  display: inline-block;
}

.status-available { background: #bfe3c4; color: #1e6e2e; }
.status-adopted { background: #b9d3ee; color: #1958ab; }
.status-pending { background: #f6cba3; color: #a15c00; }

.action-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.edit-btn {
  background: #ede8f8;
  color: #5b3fd6;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.2s ease;
}

.edit-btn:hover {
  background: #5b3fd6;
  color: #ffffff;
}

.delete-btn {
  background: #fde8e8;
  color: #b3261e;
  width: 36px;
  height: 36px;
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
  background: #EDE1C6;
  width: 100%;
  max-width: 800px;
  border-radius: 15px;
  padding: 30px;
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
  margin-bottom: 24px;
  border-bottom: 2px solid #D8C8A6;
  padding-bottom: 12px;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #2B2B2B;
}

.close-modal {
  background: none;
  border: none;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  color: #666;
}

.close-modal:hover { color: #f2932b; }

.modal-body form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
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
  border: 1px solid #D8C8A6;
  border-radius: 8px;
  outline: none;
  background: white;
  font-size: 14px;
  font-family: inherit;
}

.modal-body input:focus,
.modal-body select:focus,
.modal-body textarea:focus {
  border-color: #F2932B;
  box-shadow: 0 0 5px rgba(242,147,43,.3);
}

.modal-body textarea {
  resize: none;
  height: 100px;
}

.submit-btn {
  grid-column: 1 / 3;
  background: #F2932B;
  color: white;
  border: none;
  padding: 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: background 0.3s;
}

.submit-btn:hover { background: #E8841F; }

@media(max-width: 768px) {
  .modal-body form { grid-template-columns: 1fr; }
  .modal-body .full, .submit-btn { grid-column: 1; }
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
    <a href="dogs.php" class="active"><span class="icon">🐾</span> Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <?php if ($success != "") { ?>
      <div class="alert-success"><?php echo $success; ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
      <div class="alert-error"><?php echo $error; ?></div>
    <?php } ?>

    <div class="main-header">
      <h1>All Dogs</h1>
      <button type="button" class="add-btn" onclick="openModal()">+ Add New Dog</button>
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
          $i = 1; 
          while($d = mysqli_fetch_assoc($dogs)) { 
            $images_array = explode(",", $d['image']);
            $first_image = !empty($images_array[0]) ? trim($images_array[0]) : 'default.jpg';
            $image_count = count($images_array);
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
                if ($d['adoption_status'] == "Adopted") $cls = "status-adopted";
                if ($d['adoption_status'] == "Pending") $cls = "status-pending";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($d['adoption_status'] ?? 'Available'); ?></span>
            </td>
            <td class="text-center">
              <div class="action-links">
                <a class="edit-btn" href="edit_dog.php?id=<?php echo $d['dog_id']; ?>" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </a>
                <a class="delete-btn" href="dogs.php?delete=<?php echo $d['dog_id']; ?>" onclick="return confirm('Delete this dog?');" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </a>
              </div>
            </td>
          </tr>
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
      <button class="close-modal" onclick="closeModal()">&times;</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="dogs.php" enctype="multipart/form-data">

        <div>
          <label>Dog Name</label>
          <input type="text" name="name" required>
        </div>

        <div>
          <label>Breed</label>
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
            <option value="Husky"> Husky</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div id="customBreedGroup" style="display: none;" class="full">
          <label>Specify Custom Breed Name</label>
          <input type="text" name="custom_breed" id="customBreedInput">
        </div>

        <div>
          <label>Age</label>
          <input type="number" name="age" min="0" required>
        </div>

        <div>
          <label>Gender</label>
          <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div>
          <label>Color</label>
          <input type="text" name="color" required>
        </div>

        <div>
          <label>Size</label>
          <select name="size" required>
            <option value="">Select Size</option>
            <option value="Small">Small</option>
            <option value="Medium">Medium</option>
            <option value="Large">Large</option>
          </select>
        </div>

        <div>
          <label>Weight (kg)</label>
          <input type="number" step="0.01" name="weight" required>
        </div>

        <div>
          <label>Vaccination Status</label>
          <select name="vaccination_status" required>
            <option value="">Select</option>
            <option value="Vaccinated">Vaccinated</option>
            <option value="Not Vaccinated">Not Vaccinated</option>
          </select>
        </div>

        <div class="full">
          <label>Health Status</label>
          <input type="text" name="health_status" required>
        </div>

        <div class="full">
          <label>Description</label>
          <textarea name="description" required></textarea>
        </div>

        <div class="full">
          <label>Dog Images</label>
          <input type="file" name="images[]" accept="image/*" multiple required>
        </div>

        <button type="submit" name="add_dog" class="submit-btn">Add Dog</button>

      </form>
    </div>

  </div>
</div>

<script>
function openModal() {
  document.getElementById('addDogModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('addDogModal').style.display = 'none';
}

window.onclick = function(event) {
  var modal = document.getElementById('addDogModal');
  if (event.target == modal) {
    closeModal();
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

<?php if ($error != "") { ?>
openModal();
<?php } ?>
</script>

</body>
</html>