<?php
session_start();
include("config/config.php");

$error = "";
$success = "";

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Password reset successfully! You can now log in with your new password.";
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";
    } else {
        $email_esc = mysqli_real_escape_string($conn, $email);
        $sql = "SELECT * FROM user WHERE email='$email_esc'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect Password!";
            }
        } else {
            $error = "Email not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | PawLix</title>

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
  height: 75px;
  padding: 0 55px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  box-sizing: border-box;
  position: relative;
  z-index: 1000;
}

.navbar .logo{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  height: 100%;
}

.navbar .logo img{
  width: 110px;
  display: block;
  position: static;
  padding-top: 20px;
}

.navbar .links a{
  margin: 0 18px;
  text-decoration: none;
  color: #7a241f;
  font-weight: 600;
  font-size: 15px;
}

.navbar .buttons button, .navbar .buttons a{
  padding: 9px 24px;
  border-radius: 30px;
  border: none;
  margin-left: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  font-family: inherit;
  text-decoration: none;
  display: inline-block;
  transition: opacity 0.2s ease;
}

.navbar .buttons button:hover, .navbar .buttons a:hover{
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

.eye {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    user-select: none;
    color: #4a3223;
}
.eye:hover {
    color: #7c7a7a;
}

.wrapper{
    display:flex;
    flex: 1;
    min-height: calc(100vh - 75px);
    width: 100%;
    padding:0px;
    margin:0;
    position: relative;
    z-index: 1;
}

.left{
    flex: 1;
    overflow: hidden;
    display: flex;
}

.left img{
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.right{
    flex: 1;
    background: #ece0c2;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 100px;  
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

.success{
  color: #1e6e2e;
  background: #e5f6e8;
  border: 1px solid #bfe3c4;
  padding: 10px 14px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  text-align: left;
  font-weight: 600;
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

.remember-row{
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom:18px;
  font-size: 12.5px;
  text-align: left;
  flex-wrap: wrap;
  gap: 10px;
}

.remember-row label{
  display: flex;
  align-items: center;
  gap: 8px;
  color: #2b2b2b;
}

.remember-row a{
  color: #1f6fd6;
  text-decoration: none;
  font-weight: 500;
}

.remember-row a:hover{
  text-decoration: underline;
}

button.login-btn{
  display: block;
  width: 100%;
  padding: 13px;
  background: #1f6fd6;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(31,111,214,0.3);
  transition: background 0.2s ease;
}

button.login-btn:hover{
  background: #1958ab;
}

.bottom-text{
  margin-top: 20px;
  font-size: 14px;
  color: #333;
  padding-top: 10px;
}

.bottom-text a{
  color: #1f6fd6;
  font-weight: 600;
  text-decoration: underline;
}

@media (max-width: 768px){
  .left{
    display: none;
  }
  .navbar .links{
    display: none;
  }
  .right{
    padding: 40px 20px;
  }
}

</style>
</head>

<body>

<div class="navbar">
  <div class="logo">
      <a href="index.php"><img src="assets/images/logo.png" alt="PawLix logo"></a>
    </div>
  <div class="links">
    <a href="index.php">Home</a>
    <a href="browse.php">Browse Dogs</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <a href="report.php">Report a Dog</a>
  </div>
  
  <div class="buttons">
    <a href="signup.php" class="btn-signup">Sign Up</a>
    <a href="login.php" class="btn-login">Login</a>
  </div>
</div>

<div class="wrapper">

  <div class="left">
    <img src="assets/img/login image dog.png" alt="Dog">
  </div>

  <div class="right">
    <div class="form-box">

      <h2>Welcome Back!</h2>
      <p>Please Log in to your account</p>
      <hr>

      <?php if ($success != "") { ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
      <?php } ?>

      <?php if ($error != "") { ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php } ?>

      <form method="POST" action="login.php">

        <div class="input-wrap">
          <span>✉️</span>
          <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="input-wrap">
          <span>🔒</span>
          <input type="password" name="password" id="pwd" placeholder="Password" minlength="8" required>
           <span class="eye" id="eye-pwd" onclick="togglePwd('pwd')">
            <svg class="eye-icon eye-open" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
               <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>

            <svg class="eye-icon eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.17c0-1.66-1.34-3-3-3l-.17.02z"/>
            </svg>
          </span>
        </div>

        <div class="remember-row">
          <label><input type="checkbox" name="remember"> Remember Me</label>
          <a href="forgot_password.php">Forget Password?</a>
        </div>

        <button type="submit" name="login" class="login-btn">Log In</button>

      </form>

      <p class="bottom-text">Don't have an account ? <a href="signup.php">Signup</a></p>

    </div>
  </div>

</div>

<script>
function togglePwd(id) {
    const input = document.getElementById(id);
    const eyeBtn = document.getElementById('eye-' + id);
    if (!input || !eyeBtn) return;

    const openIcon = eyeBtn.querySelector('.eye-open');
    const closedIcon = eyeBtn.querySelector('.eye-closed');

    if (input.type === 'password') {
        input.type = 'text';
        if (openIcon) openIcon.style.display = 'none';
        if (closedIcon) closedIcon.style.display = 'inline-block';
    } else {
        input.type = 'password';
        if (openIcon) openIcon.style.display = 'inline-block';
        if (closedIcon) closedIcon.style.display = 'none';
    }
}
</script>

</body>
</html>