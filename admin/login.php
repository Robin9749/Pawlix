<?php
session_start();
include("../config/config.php");

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM Admin WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        if ($password == $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['name'];

            header("Location: dashboard.php");
            exit();
        }

        else {
            $error = "Incorrect Password!";
        }
    } else {
        $error = "Username not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title>

<style>

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

*{
  box-sizing: border-box;
}

body{
    font-family: 'Poppins', Arial, sans-serif;
    margin: 0;
    padding: 0;
    overflow: hidden;
    color: #2b2b2b;
}

.navbar{
    background: #f2e6c9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 48px;
    margin: 0;
    border-bottom: 2px solid #ffffff;
}

.navbar .logo{
  font-weight: 600;
  font-size: 20px;
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
    min-height:calc(50vh - 74px);
    padding:0px;
    margin:0;
}

.left{
    flex: 1;
    overflow: hidden;
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
    padding-top: 155px;  
}

.form-box{
  width: 100%;
  max-width: 480px;
  text-align: center;
}

.form-box .logo-icon{
  font-size: 46px;
  line-height: 1;
  margin-bottom: 6px;
}

.form-box h2{
  color: #1f6fd6;
  margin: 0 0 6px;
  font-size: 30px;
  font-weight: 700;
}

.form-box p{
  margin: 0;
  color: #3a3a3a;
  font-size: 15px;
}

hr{
  border: none;
  border-top: 1px solid #ddccae;
  margin: 24px 0 28px;
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
  font-size: 16px;
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
  padding: 16px 44px;
  border: none;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  font-size: 15px;
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
  margin-bottom: 24px;
  font-size: 14px;
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
  padding: 16px;
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
  <div class="logo">🐶 PawLix</div>
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

      <div class="logo-icon">🐶</div>
      <h2>Welcome Back!</h2>
      <p>Please Log in to your account</p>
      <hr>

      <?php if ($error != "") { ?>
        <div class="error"><?php echo $error; ?></div>
      <?php } ?>

      <form method="POST">

        <div class="input-wrap">
          <span>✉️</span>
          <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="input-wrap">
          <span>🔒</span>
          <input type="password" name="password" id="pwd" placeholder="Password" required>
          <span class="eye" onclick="togglePwd()">👁️</span>
        </div>

        <div class="remember-row">
          <label><input type="checkbox" name="remember"> Remember Me</label>
          <a href="#">Forget Password?</a>
        </div>

        <button type="submit" name="login" class="login-btn">Log In</button>

      </form>

      <p class="bottom-text">Don't have an account ? <a href="signup.php">Signup</a></p>

    </div>
  </div>

</div>

<script>
function togglePwd(){
  var field = document.getElementById('pwd');
  field.type = field.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>