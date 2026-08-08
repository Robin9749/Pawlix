<?php
session_start();
include("../config/config.php");

$error = "";
$success = "";

if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $sql = "INSERT INTO Admin (name, email, password) VALUES ('$name', '$email', '$password')";

        if (mysqli_query($conn, $sql)) {
            $success = "Account created successfully! You can now log in.";
        } else {
            $error = "Something went wrong: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Sign Up</title>

<style>

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

*{
  box-sizing: border-box;
}

body{
    font-family: 'Poppins', Arial, sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    overflow-y: hidden;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: #2b2b2b;
}

.navbar{
    background: #f2e6c9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 48px;
    margin: 0;
    border-bottom: 2px solid #ffffff;
}

.navbar .logo{
  font-size: 20px;
  font-weight: 700;
  color: #2b2b2b;
  display: flex;
  align-items: center;
  gap: 8px;
}

.navbar .links a{
  margin: 0 18px;
  text-decoration: none;
  color: #7a241f;
  font-weight: 600;
  font-size: 15px;
}

.navbar .buttons button{
  padding: 9px 24px;
  border-radius: 30px;
  border: none;
  margin-left: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  font-family: inherit;
  transition: opacity 0.2s ease;
}

.navbar .buttons button:hover{
  opacity: 0.85;
}

.btn-signup{
  background: transparent;
  border: 1.5px solid #7a241f !important;
  color: #7a241f;
}

.btn-login{
  background: #1c1c1c;
  color: white;
}

.wrapper{
    display:flex;
    flex: 1;
    min-height: calc(100vh - 65px);
    width: 100%;
    padding:0px;
    margin:0;
}

.left{
    flex: 1;
    overflow: hidden;
        display: flex;
}

.left img{
    width: 100%;
    height: 80%;
    object-fit: cover;
    display: block;
}

.right{
    flex: 1;
    background: #ece0c2;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 90px;  
}

.form-box{
  width: 100%;
  max-width: 430px;
  text-align: center;
}


.form-box h2{
  color: #1f6fd6;
  margin: 0 0 2px;
  font-size: 28px;
  font-weight: 700;
}

.form-box p{
  margin: 0;
  color: #3a3a3a;
  font-size: 13px;
}

hr{
  border: none;
  border-top: 1px solid #ddccae;
  margin: 15px 0 28px;
}

.error{
  color: #b3261e;
  background: #fdeaea;
  border: 1px solid #f3c6c6;
  padding: 10px 14px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  text-align: left;
}

.input-wrap{
  position: relative;
  width: 100%;
  margin-bottom: 16px;
}

.input-wrap span{
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #8a8a8a;
  font-size: 14px;
  line-height: 1;
}

.input-wrap span.eye{
  left: auto;
  right: 16px;
  cursor: pointer;
}

.input-wrap input{
  display: block;
  width: 100%;
  padding: 14px 44px;
  border: none;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  font-size: 13px;
  font-family: inherit;
  background: #ffffff;
}

.input-wrap input:focus{
  outline: 2px solid #1f6fd6;
  outline-offset: 1px;
}

.input-wrap input::placeholder{
  color: #9a9a9a;
}

.terms-row{
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 15px;
  font-size: 12.5px;
  text-align: left;
  color: #2b2b2b;
}

.terms-row input{
  margin-top: 3px;
}

.terms-row a{
  color: #1f6fd6;
  text-decoration: none;
  font-weight: 500;
}

.terms-row a:hover{
  text-decoration: underline;
}

button.signup-btn{
  width: 100%;
  padding: 12px;
  background: #1f6fd6;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(31,111,214,0.3);
  transition: background 0.2s ease;
}

button.signup-btn:hover{
  background: #1958ab;
}

.bottom-text{
  font-size: 12px;
  color: #333;
  padding-top: 8px;
}

.bottom-text a{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: underline;
}

</style>
</head>

<body>

<div class="navbar">
  <div class="logo">PawLix</div>
  <div class="links">
    <a href="#">Home</a>
    <a href="#">Browse Dogs</a>
    <a href="#">About</a>
    <a href="#">Contact</a>
  </div>
  <div class="buttons">
    <button class="btn-signup">Sign Up</button>
    <button class="btn-login">Login</button>
  </div>
</div>

<div class="wrapper">

  <div class="left">
    <img src="../assets/img/login image dog.png" alt="Dog">
  </div>

  <div class="right">
    <div class="form-box">

      <h2>Create Your Account</h2>
      <p>Join us and find your new best friend!</p>
      <hr>

      <?php if ($error != "") { ?>
        <div class="error"><?php echo $error; ?></div>
      <?php } ?>

      <?php if ($success != "") { ?>
        <div class="success"><?php echo $success; ?></div>
      <?php } ?>

      <form method="POST">

        <div class="input-wrap">
          <span>👤</span>
          <input type="text" name="name" placeholder="Full Name" required>
        </div>

        <div class="input-wrap">
          <span>✉️</span>
          <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="input-wrap">
          <span>🔒</span>
          <input type="password" name="password" id="pwd" placeholder="Create Password" required>
          <span class="eye" onclick="togglePwd('pwd')">👁️</span>
        </div>

        <div class="input-wrap">
          <span>🔒</span>
          <input type="password" name="confirm_password" id="cpwd" placeholder="Confirm Password" required>
          <span class="eye" onclick="togglePwd('cpwd')">👁️</span>
        </div>

        <div class="terms-row">
          <input type="checkbox" required>
          <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
        </div>

        <button type="submit" name="signup" class="signup-btn">Sign Up</button>

      </form>

      <p class="bottom-text">Already have an account? <a href="login.php">Log In</a></p>

    </div>
  </div>

</div>

<script>
function togglePwd(id){
  var field = document.getElementById(id);
  field.type = field.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>