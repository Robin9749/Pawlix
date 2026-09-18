<?php
session_start();
include("config/config.php");

$error = "";
$success = "";

$full_name = "";
$email     = "";
$password  = "";
$confirm_p = "";

if (isset($_POST['signup'])) {
    $full_name = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm_p = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_p)) {
        $error = "Please fill in all required fields.";

    } elseif (!preg_match("/^[a-zA-Z\s]{2,50}$/", $full_name)) {
        $error = "Name must contain only alphabets and spaces!";

    } elseif (!preg_match("/^[A-Za-z0-9][A-Za-z0-9._%+-]*@gmail\.com$/", $email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid Gmail address ending with @gmail.com.";

    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";

    } elseif (!preg_match("/[A-Z]/", $password)) {
        $error = "Password must contain at least one uppercase letter (A-Z)!";

    } elseif (!preg_match("/[a-z]/", $password)) {
        $error = "Password must contain at least one lowercase letter (a-z)!";

    } elseif (!preg_match("/[0-9]/", $password)) {
        $error = "Password must contain at least one number (0-9)!";

    } elseif (!preg_match("/[!@#$%^&*(),.?\":{}|<>_]/", $password)) {
        $error = "Password must contain at least one special character (such as @, #, $, %, !)!";

    } elseif ($password !== $confirm_p) {
        $error = "Passwords do not match!";

    } else {
        $check_stmt = mysqli_prepare(
            $conn,
            "SELECT user_id FROM user WHERE email = ? LIMIT 1"
        );

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "An account with this email address already exists. Please log in instead.";
                mysqli_stmt_close($check_stmt);

            } else {
                mysqli_stmt_close($check_stmt);

                $parts = explode(" ", $full_name, 2);
                $first_name = $parts[0];
                $last_name  = $parts[1] ?? '';

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO user (first_name, last_name, email, password)
                     VALUES (?, ?, ?, ?)"
                );

                if ($insert_stmt) {
                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "ssss",
                        $first_name,
                        $last_name,
                        $email,
                        $hashed_password
                    );

                    try {
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $success = "Account created successfully! You can now log in.";
                            $full_name = "";
                            $email = "";
                            $password = "";
                            $confirm_p = "";
                        } else {
                            $error = "Something went wrong: " . mysqli_error($conn);
                        }

                    } catch (mysqli_sql_exception $e) {
                        if ($e->getCode() == 1062) {
                            $error = "An account with this email address already exists. Please log in instead.";
                        } else {
                            $error = "Something went wrong: " . $e->getMessage();
                        }
                    }

                    mysqli_stmt_close($insert_stmt);

                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }

        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up | PawLix</title>

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

.navbar .buttons button,
.navbar .buttons a{
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

.navbar .buttons button:hover,
.navbar .buttons a:hover{
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
    padding-top: 50px;
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
  margin: 15px 0 24px;
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

/* LIVE INPUT ERROR MESSAGES */
.input-error-msg {
    display: none;
    font-size: 12.5px;
    margin-top: -8px;
    margin-bottom: 14px;
    text-align: left;
    font-weight: 500;
}

.input-error-msg.invalid {
    display: block;
    color: #b3261e;
}

.input-error-msg.valid {
    display: block;
    color: #1e6e2e;
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
  font-size: 13px;
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
      <a href="index.php">
        <img src="assets/images/logo.png" alt="PawLix logo">
      </a>
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

      <h2>Create Your Account</h2>
      <p>Join us and find your new best friend!</p>

      <hr>

      <?php if ($error != "") { ?>
        <div class="error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php } ?>

      <?php if ($success != "") { ?>
        <div class="success">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php } ?>

      <form method="POST" action="signup.php" id="signupForm">

        <div class="input-wrap">
          <span>👤</span>
          <input
            type="text"
            name="name"
            placeholder="Full Name"
            value="<?php echo htmlspecialchars($full_name); ?>"
            pattern="[A-Za-z\s]+"
            title="Name must contain only alphabets and spaces"
            required
          >
        </div>

        <div class="input-wrap">
          <span>✉️</span>

          <input
            type="email"
            name="email"
            placeholder="Email Address"
            value="<?php echo htmlspecialchars($email); ?>"
            pattern="[A-Za-z0-9][A-Za-z0-9._%+-]*@gmail\.com"
            title="Please enter a valid Gmail address ending with @gmail.com"
            required
          >
        </div>

        <div class="input-wrap">
          <span>🔒</span>

          <input
            type="password"
            name="password"
            id="pwd"
            placeholder="Create Password"
            value="<?php echo htmlspecialchars($password); ?>"
            required
          >

          <span class="eye" id="eye-pwd" onclick="togglePwd('pwd')">

            <svg class="eye-icon eye-open"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="currentColor">

              <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>

            </svg>

            <svg class="eye-icon eye-closed"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="currentColor"
                 style="display:none;">

              <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.17c0-1.66-1.34-3-3-3l-.17.02z"/>

            </svg>

          </span>
        </div>

        <!-- DYNAMIC PASSWORD ERROR MESSAGE -->
        <div class="input-error-msg" id="pwd-error-msg"></div>

        <div class="input-wrap">

          <span>🔒</span>

          <input
            type="password"
            name="confirm_password"
            id="cpwd"
            placeholder="Confirm Password"
            value="<?php echo htmlspecialchars($confirm_p); ?>"
            required
          >

          <span class="eye" id="eye-cpwd" onclick="togglePwd('cpwd')">

            <svg class="eye-icon eye-open"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="currentColor">

              <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>

            </svg>

            <svg class="eye-icon eye-closed"
                 width="20"
                 height="20"
                 viewBox="0 0 24 24"
                 fill="currentColor"
                 style="display:none;">

              <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.17c0-1.66-1.34-3-3-3l-.17.02z"/>

            </svg>

          </span>

        </div>

        <!-- DYNAMIC CONFIRM PASSWORD ERROR MESSAGE -->
        <div class="input-error-msg" id="cpwd-error-msg"></div>

        <div class="terms-row">

          <input type="checkbox" required>

          <span>
            I agree to the
            <a href="#">Terms of Service</a>
            and
            <a href="#">Privacy Policy</a>
          </span>

        </div>

        <button type="submit" name="signup" class="signup-btn">
          Sign Up
        </button>

      </form>

      <p class="bottom-text">
        Already have an account?
        <a href="login.php">Log In</a>
      </p>

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

        if (openIcon)
            openIcon.style.display = 'none';

        if (closedIcon)
            closedIcon.style.display = 'inline-block';

    } else {

        input.type = 'password';

        if (openIcon)
            openIcon.style.display = 'inline-block';

        if (closedIcon)
            closedIcon.style.display = 'none';
    }
}

document.querySelector('input[name="name"]')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
});

// REAL-TIME SINGLE ERROR MESSAGE VALIDATION (PERSISTING ENTERED PASSWORD)
document.addEventListener('DOMContentLoaded', function() {
    const pwdInput  = document.getElementById('pwd');
    const cpwdInput = document.getElementById('cpwd');
    const pwdMsg    = document.getElementById('pwd-error-msg');
    const cpwdMsg   = document.getElementById('cpwd-error-msg');

    function validatePassword() {
        const val = pwdInput.value;
        
        if (val.length === 0) {
            pwdMsg.style.display = 'none';
            pwdMsg.textContent = '';
            return;
        }

        pwdMsg.style.display = 'block';

        if (val.length < 8) {
            pwdMsg.textContent = 'Password must be at least 8 characters long.';
            pwdMsg.className = 'input-error-msg invalid';
        } else if (!/[A-Z]/.test(val)) {
            pwdMsg.textContent = 'Password must contain at least one uppercase letter (A-Z).';
            pwdMsg.className = 'input-error-msg invalid';
        } else if (!/[a-z]/.test(val)) {
            pwdMsg.textContent = 'Password must contain at least one lowercase letter (a-z).';
            pwdMsg.className = 'input-error-msg invalid';
        } else if (!/[0-9]/.test(val)) {
            pwdMsg.textContent = 'Password must contain at least one number (0-9).';
            pwdMsg.className = 'input-error-msg invalid';
        } else if (!/[!@#$%^&*(),.?":{}|<>_]/.test(val)) {
            pwdMsg.textContent = 'Password must contain at least one special character (e.g. @, #, $, %, !).';
            pwdMsg.className = 'input-error-msg invalid';
        } else {
            pwdMsg.textContent = ' Strong password';
            pwdMsg.className = 'input-error-msg valid';
        }

        if (cpwdInput.value.length > 0) {
            validateConfirmPassword();
        }
    }

    function validateConfirmPassword() {
        const cval = cpwdInput.value;
        const pval = pwdInput.value;

        if (cval.length === 0) {
            cpwdMsg.style.display = 'none';
            cpwdMsg.textContent = '';
            return;
        }

        cpwdMsg.style.display = 'block';

     
    }

    pwdInput?.addEventListener('input', validatePassword);
    cpwdInput?.addEventListener('input', validateConfirmPassword);
});

</script>

</body>
</html>