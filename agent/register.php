<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $branch   = trim($_POST['branch']);

    if(!$name || !$email || !$password || !$branch){
        $error = "All fields are required.";
    } else {
        // Check if email already exists in users table
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows > 0){
            $error = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,branch) VALUES (?,?,?,?,?)");
            $role = 'agent';
            $stmt->bind_param("sssss", $name, $email, $password_hash, $role, $branch);

            if($stmt->execute()){
                $user_id = $conn->insert_id;

                // Insert into agents table
                $stmt2 = $conn->prepare("INSERT INTO agents (user_id, branch, approved) VALUES (?,?,0)");
                $stmt2->bind_param("is", $user_id, $branch);
                $stmt2->execute();

                $success = "Registration submitted. Wait for admin approval.";
            } else {
                $error = "Failed to register agent.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Registration</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{height:100%;overflow:hidden;}
.scroll-wrapper{height:100%;overflow:auto;-ms-overflow-style:none;scrollbar-width:none;}
.scroll-wrapper::-webkit-scrollbar{display:none;}

body{
background:url('../assets/agent-register.jpg') center/cover no-repeat fixed;
position:relative;
}
body::after{
content:'';position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.35);z-index:-1;
}

.container{
width:90%; max-width:600px; margin:40px auto 80px auto;
background:#fff; border-radius:20px; padding:30px;
box-shadow:0 10px 30px rgba(0,0,0,0.25); color:#000;
}

h2{text-align:center;margin-bottom:20px;color:#ff7e5f;}
label{display:block;margin:10px 0 5px;color:#333;font-weight:600;}

input{
width:100%; padding:12px; border-radius:10px; border:1px solid #ccc; margin-bottom:15px;
font-size:1rem; transition:0.3s;
}

input:focus{
outline:none; border-color:#ff7e5f; box-shadow:0 0 8px rgba(255,126,95,0.6);
}

button{
display:inline-block; width:auto; padding:12px 30px; border-radius:10px; border:none; cursor:pointer;
background:linear-gradient(135deg,#ff7e5f,#feb47b); color:white; font-weight:bold; transition:0.3s;
}

button:hover{
transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

.form-center{text-align:center;}
p.success{color:#28a745;text-align:center;margin-bottom:15px;}
p.error{color:#dc3545;text-align:center;margin-bottom:15px;}

@media(max-width:600px){
.container{padding:20px;}
input{font-size:0.95rem;padding:10px;}
}
</style>
</head>
<body>
<div class="scroll-wrapper">
<div class="container">
<h2>Agent Registration</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST">
<label>Name:</label>
<input type="text" name="name" required>

<label>Email:</label>
<input type="email" name="email" required>

<label>Password:</label>
<input type="password" name="password" required>

<label>Branch:</label>
<input type="text" name="branch" required>

<div class="form-center">
<button type="submit">Register</button>
</div>
</form>
</div>
</div>
</body>
</html>
