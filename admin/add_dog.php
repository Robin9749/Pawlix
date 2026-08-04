<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$success = "";
$error = "";

if (isset($_POST['add_dog'])) {


    $name = trim($_POST['name']);
    $breed = trim($_POST['breed']);
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $color = trim($_POST['color']);
    $size = $_POST['size'];
    $weight = $_POST['weight'];
    $vaccination_status = $_POST['vaccination_status'];
    $health_status = trim($_POST['health_status']);
    $description = trim($_POST['description']);

    $image = $_FILES['image']['name'];
    $temp_name = $_FILES['image']['tmp_name'];

    $upload_folder = "../uploads/";

    $image_name = time() . "_" . $image;
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
        empty($description)
    ) {

        $error = "Please fill all required fields.";

    } else {

    if (!is_dir($upload_folder)) {
        mkdir($upload_folder, 0777, true);
    }

    if (move_uploaded_file($temp_name, $upload_folder . $image_name)) {

        $sql = "INSERT INTO Dog
        (name, breed, age, gender, color, size, weight,
        vaccination_status, health_status, description, image)

        VALUES
        ('$name',
        '$breed',
        '$age',
        '$gender',
        '$color',
        '$size',
        '$weight',
        '$vaccination_status',
        '$health_status',
        '$description',
        '$image_name')";

        if (mysqli_query($conn, $sql)) {

            $success = "Dog added successfully.";

        } else {

            $error = "Database Error : " . mysqli_error($conn);

        }

    } else {

        $error = "Image upload failed.";

    }

}

}
?>
<!DOCTYPE html>
<html>

<head>

    <title>Add Dog | Pawlix Admin</title>

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        *{
         margin:0;
         padding:0;
        box-sizing:border-box;
        font-family:'Poppins',sans-serif;
        }

body{

    background:#E8DCC0;
    color:#2B2B2B;

}

.container{

    width:850px;
    margin:40px auto;

    background:#EDE1C6;

    border-radius:15px;

    padding:35px;

    box-shadow:0 10px 25px rgba(0,0,0,.08);

}

h2{

    text-align:center;
    margin-bottom:30px;
    font-size:28px;
    font-weight:700;

}

form{

    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;

}

.full{

    grid-column:1/3;

}

label{

    display:block;
    margin-bottom:8px;
    font-size:14px;
    font-weight:600;

}

input,
select,
textarea{

    width:100%;

    padding:12px 14px;

    border:1px solid #D8C8A6;

    border-radius:10px;

    outline:none;

    background:white;

    font-size:14px;

    transition:.2s;

}

input:focus,
select:focus,
textarea:focus{

    border-color:#F2932B;

    box-shadow:0 0 5px rgba(242,147,43,.3);

}

textarea{

    resize:none;
    height:130px;

}

input[type=file]{

    padding:10px;
    background:white;

}

button{

    grid-column:1/3;

    background:#F2932B;

    color:white;

    border:none;

    padding:14px;

    border-radius:10px;

    cursor:pointer;

    font-size:16px;

    font-weight:600;

    transition:.3s;

}

button:hover{

    background:#E8841F;

}

.success{
    background:#d4edda;
    color:#155724;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    font-weight:600;
}

.error{
    background:#f8d7da;
    color:#721c24;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    font-weight:600;
}

@media(max-width:768px){

form{

grid-template-columns:1fr;

}

.full{

grid-column:1;

}

button{

grid-column:1;

}

.container{

width:95%;

}

}

.back{
    display: inline-block;
    text-decoration: none;
    color: #555;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 15px;
    transition: 0.3s;
}

.back:hover{
    color: #F2932B;
}

</style>

</style>
</head>

<body>

<div class="container">

<a href="dogs.php" class="back"> ❮  </span> Back</a>

<h2>Add New Dog</h2>

<?php if($success!=""){ ?>

<div class="success">
    <?php echo $success; ?>
</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="error">
    <?php echo $error; ?>
</div>

<?php } ?>

<form method="POST" enctype="multipart/form-data">

<label>Dog Name</label>

<input
type="text"
name="name"
required>

<label>Breed</label>

<input
type="text"
name="breed"
required>

<label>Age</label>

<input
type="number"
name="age"
min="0"
required>

<label>Gender</label>

<select name="gender" required>

<option value="">Select Gender</option>

<option value="Male">Male</option>

<option value="Female">Female</option>

</select>

<label>Color</label>

<input
type="text"
name="color"
required>

<label>Size</label>

<select name="size" required>

<option value="">Select Size</option>

<option value="Small">Small</option>

<option value="Medium">Medium</option>

<option value="Large">Large</option>

</select>

<label>Weight (kg)</label>

<input
type="number"
step="0.01"
name="weight"
required>

<label>Vaccination Status</label>

<select name="vaccination_status" required>

<option value="">Select</option>

<option value="Vaccinated">Vaccinated</option>

<option value="Not Vaccinated">Not Vaccinated</option>

</select>

<label>Health Status</label>

<input
type="text"
name="health_status"
required>

<label>Description</label>

<textarea
name="description"
required></textarea>

<label>Dog Image</label>

<input
type="file"
name="image"
accept="image/*"
required>

<button
type="submit"
name="add_dog"
class="btn">

Add Dog

</button>

</form>

</div>

</body>

</html>