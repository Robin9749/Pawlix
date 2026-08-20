<?php
session_start();
include("config/config.php");


if (!function_exists('safeAddColumnForgot')) {
    function safeAddColumnForgot($conn, $table, $column, $definition) {
        try {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {}
    }
}

safeAddColumnForgot($conn, 'user', 'reset_token', "VARCHAR(255) DEFAULT NULL");
safeAddColumnForgot($conn, 'user', 'reset_token_expiry', "DATETIME DEFAULT NULL");

$error = "";

if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $email_esc = mysqli_real_escape_string($conn, $email);
        $user_query = mysqli_query($conn, "SELECT user_id, first_name, email FROM user WHERE email = '$email_esc' LIMIT 1");

        if ($user_query && mysqli_num_rows($user_query) === 1) {
            $u = mysqli_fetch_assoc($user_query);
            $token = bin2hex(random_bytes(16));

            $update_sql = "UPDATE user SET reset_token = '$token', reset_token_expiry = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE user_id = " . intval($u['user_id']);
            
            if (mysqli_query($conn, $update_sql)) {
                $_SESSION['reset_user_id'] = $u['user_id'];
                $_SESSION['reset_email'] = $u['email'];
                $_SESSION['reset_token'] = $token;
                
                header("Location: reset_password.php?token=" . urlencode($token));
                exit();
            } else {
                $error = "Unable to process request. Please try again.";
            }
        } else {
            $error = "No user account was found with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password</title>

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
    padding-top: 140px;  
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

      <h2>Forgot Password?</h2>
      <p>Enter your account email address to reset your password</p>
      <hr>

      <?php if ($error != "") { ?>
        <div class="error"><?php echo $error; ?></div>
      <?php } ?>

      <form method="POST" action="forgot_password.php">
        <div class="input-wrap">
          <span>✉️</span>
          <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <button type="submit" name="request_reset" class="login-btn">Continue ➔</button>
      </form>

      <p class="bottom-text">Remember your password ? <a href="login.php">Log In</a></p>

    </div>
  </div>

</div>

</body>
</html>