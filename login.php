<?php
session_start();
include('includes/db.php');
include('includes/functions.php');

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: admin/dashboard.php"); exit;
        case 'agent': header("Location: agent/dashboard.php"); exit;
        case 'user': header("Location: user/dashboard.php"); exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) $error = "Email and password are required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email format.";
    else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                // ===== AGENT LOGIN =====
                if ($user['role'] === 'agent') {

                    $stmt_agent = $conn->prepare("SELECT * FROM agents WHERE user_id=? LIMIT 1");
                    $stmt_agent->bind_param("i", $user['user_id']);
                    $stmt_agent->execute();
                    $res_agent = $stmt_agent->get_result();

                    if ($res_agent->num_rows === 1) {
                        $agent = $res_agent->fetch_assoc();

                        // 🔴 approval check
                        if ($agent['approved'] != 1) {
                            session_destroy();
                            $error = "Your agent account is waiting for admin approval.";
                        } else {
                            $_SESSION['agent_id'] = $agent['agent_id'];
                            $_SESSION['agent_name'] = $user['name'];
                            $_SESSION['branch'] = $agent['branch'];

                            header("Location: agent/dashboard.php");
                            exit;
                        }
                    } else {
                        $error = "Agent record not found.";
                    }

                // ===== ADMIN LOGIN =====
                } elseif ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                    exit;

                // ===== USER LOGIN =====
                } else {
                    $_SESSION['name'] = $user['name']; // <-- Added to store real user name
                    header("Location: user/dashboard.php");
                    exit;
                }

            } else { 
                $error = "Invalid email or password."; 
            }
        } else { 
            $error = "Invalid email or password."; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courier Management Login</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body,html{height:100%;width:100%;}
body{display:flex;align-items:center;justify-content:center;background:#f0f2f5;}
.container{display:flex;flex-wrap:wrap;width:100%;min-height:100vh;}
.left-panel{
flex:1 1 500px;
background:url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
display:flex;align-items:center;justify-content:center;
color:white;text-align:center;padding:5vw;
position:relative;
}
.left-panel::after{
content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);
}
.left-panel > div{position:relative;z-index:2;}
.left-panel h1{font-size:clamp(28px,4vw,48px);margin-bottom:15px;}
.left-panel p{font-size:clamp(14px,1.3vw,20px);opacity:0.9;}
.right-panel{
flex:1 1 400px;
display:flex;align-items:center;justify-content:center;padding:5vw 3vw;
}
.login-box{
width:100%;max-width:420px;
padding:clamp(20px,3vw,40px);
border-radius:20px;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(15px);
box-shadow:0 10px 30px rgba(0,0,0,0.25);
animation:fadeIn 0.7s ease;
}
<<<<<<< HEAD

.login-box h2{text-align:center;margin-bottom:25px;color:black;font-size:clamp(22px,2.5vw,30px);}
=======
.login-box h2{text-align:center;margin-bottom:25px;color:white;font-size:clamp(22px,2.5vw,30px);}
>>>>>>> 1c21ee6 (Added agent approval system, SMS updates, dashboard improvements, new registration pages)
.input-group{margin-bottom:16px;}
input{width:100%;padding:14px;border-radius:10px;border:none;outline:none;font-size:clamp(14px,1vw,16px);background:rgba(255,255,255,0.95);transition:0.3s;}
input:focus{box-shadow:0 0 10px rgba(255,126,95,0.6);}
button{width:100%;padding:14px;border:none;border-radius:10px;font-size:clamp(15px,1vw,17px);cursor:pointer;background:linear-gradient(135deg,#ff7e5f,#feb47b);color:white;font-weight:bold;transition:0.4s;}
button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}
.error{color:#ffd4d4;margin-bottom:15px;text-align:center;font-size:14px;}
.register-link{text-align:center;margin-top:15px;}
.register-link a{color:#ff7e5f;text-decoration:none;font-weight:bold;transition:0.3s;}
.register-link a:hover{text-decoration:underline;}
@keyframes fadeIn{from{opacity:0;transform:translateY(15px);}to{opacity:1;transform:translateY(0);}}
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <div>
            <h1>Courier Management</h1>
            <p>Fast. Secure. Professional Delivery Platform.</p>
        </div>
    </div>
    <div class="right-panel">
        <div class="login-box">
            <h2>Login</h2>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST" onsubmit="return validateForm()">
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Enter Email" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Enter Password" required>
                </div>
                <button type="submit">Login Now</button>
            </form>

            <div class="register-link">
                <p>Don't have a user account? <a href="user/register.php">User Register</a></p>
                <p>Want to become an agent? <a href="agent/register.php">Apply as Agent</a></p>
            </div>

        </div>
    </div>
</div>

<script>
function validateForm(){
    let email=document.getElementById("email").value;
    let password=document.getElementById("password").value;
    let emailRegex=/^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    if(!emailRegex.test(email)){alert("Enter a valid email");return false;}
    if(password.length<6){alert("Password must be at least 6 characters");return false;}
    return true;
}
</script>
</body>
</html>
