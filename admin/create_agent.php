<?php
include('../includes/auth.php');
include('../includes/db.php');
include('../includes/mail.php');

requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $branch   = trim($_POST['branch'] ?? '');

    if (!$name || !$email || !$password || !$branch) {
        $error = 'All fields are required.';
    } elseif (!preg_match("/^[A-Z][a-zA-Z ]*$/", $name)) {
        $error = 'Name must start with a capital letter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/", $password)) {
        $error = 'Password must be at least 6 characters and include letters and numbers.';
    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already exists.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'agent')");
            $stmt->bind_param("sss",$name,$email,$hashedPassword);

            if ($stmt->execute()) {

                $user_id = $conn->insert_id;

                $stmt2 = $conn->prepare("INSERT INTO agents (user_id,branch) VALUES (?,?)");
                $stmt2->bind_param("is",$user_id,$branch);

                if ($stmt2->execute()) {

                    $subject="Your Agent Account Created";
                    $body="Hello $name,\nYour agent account has been created.\nEmail:$email\nPassword:$password";
                    send_mail($email,$subject,$body);

                    $success="Agent created successfully.";

                } else {
                    $error="Failed to create agent branch.";
                }
            } else {
                $error="Failed to create user.";
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
<title>Create Agent</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

/* HIDE SCROLLBAR BUT KEEP SCROLL */
html,body{
height:100%;
overflow:auto;
scrollbar-width:none;
-ms-overflow-style:none;
}
body::-webkit-scrollbar{display:none;}

/* BACKGROUND */
body{
background:url('../assets/agent.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:150px;
}
body::after{
content:'';position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.35);z-index:-1;
}

/* NAVBAR */
.navbar{
display:flex;justify-content:space-between;align-items:center;
padding:15px 30px;
}
.logo{
font-size:1.5rem;font-weight:bold;color:#ff7e5f;
}
.nav-right{
display:flex;gap:10px;
}
.dashboard{
text-decoration:none;padding:12px 25px;border-radius:10px;font-weight:bold;
color:#fff;background:linear-gradient(135deg,#ffd200,#f7971e);
transition:0.3s;
}
.dashboard:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(0,0,0,0.25);}
.logout{
text-decoration:none;padding:12px 25px;border-radius:10px;font-weight:bold;color:#fff;
background:linear-gradient(135deg,#ff7e5f,#feb47b);transition:0.3s;
}
.logout:hover{transform:translateY(-2px);box-shadow:0 6px 15px rgba(0,0,0,0.25);}

/* CONTAINER */
.container{
width:90%;max-width:600px;margin:50px auto 80px auto;
background:rgba(255,255,255,0.15);backdrop-filter:blur(15px);
border-radius:20px;padding:30px;color:#fff;
}

/* FORM */
h2{text-align:center;margin-bottom:20px;}
label{display:block;margin:10px 0 5px;font-weight:600;}

input,button{
width:100%;padding:14px;border-radius:10px;border:none;margin-bottom:5px;font-size:1rem;
transition:0.3s;
}

input{
background:#fff;color:#000;
}

/* --- Glow up effect on focus --- */
input:focus{
outline:none;
box-shadow: 0 0 10px 3px rgba(255,126,95,0.7);
border:1px solid #ff7e5f;
}

/* BUTTON */
button{
cursor:pointer;background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;font-weight:bold;
}
button:hover{
transform:translateY(-1px);
box-shadow:0 4px 15px rgba(0,0,0,0.25);
}

.message{display:block;margin-bottom:10px;font-size:0.9rem;}
.valid{color:#b8ffb8;}
.invalid{color:#ff4c4c;}

p.success{color:#d4ffd4;text-align:center;margin-bottom:15px;}
p.error{color:#ffd4d4;text-align:center;margin-bottom:15px;}

@media(max-width:600px){
.container{padding:20px;margin:30px auto 60px auto;}
input,button{font-size:0.9rem;padding:10px;}
}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Admin</div>
<div class="nav-right">
<a href="dashboard.php" class="dashboard">Dashboard</a>
<a href="../logout.php" class="logout">Logout</a>
</div>
</div>

<div class="container">
<h2>Create New Agent</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST" id="agentForm">

<label>Name:</label>
<input type="text" name="name" required>
<span class="message" id="nameMessage"></span>

<label>Email:</label>
<input type="email" name="email" required>
<span class="message" id="emailMessage"></span>

<label>Password:</label>
<input type="password" name="password" required>
<span class="message" id="passwordMessage"></span>

<label>Branch:</label>
<input type="text" name="branch" required>
<span class="message" id="branchMessage"></span>

<button type="submit">Create Agent</button>

</form>
</div>

<script>
// Real-time validation
const form=document.getElementById('agentForm');

const nameInput=form.name;
const emailInput=form.email;
const passwordInput=form.password;
const branchInput=form.branch;

nameInput.addEventListener('input',()=>{
const regex=/^[A-Z][a-zA-Z ]*$/;
const msg=document.getElementById('nameMessage');
msg.textContent=regex.test(nameInput.value)?'Looks good!':'Name must start with capital letter';
msg.className=regex.test(nameInput.value)?'message valid':'message invalid';
});

emailInput.addEventListener('input',()=>{
const regex=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const msg=document.getElementById('emailMessage');
msg.textContent=regex.test(emailInput.value)?'Looks good!':'Invalid email format';
msg.className=regex.test(emailInput.value)?'message valid':'message invalid';
});

passwordInput.addEventListener('input',()=>{
const regex=/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;
const msg=document.getElementById('passwordMessage');
msg.textContent=regex.test(passwordInput.value)?'Looks good!':'Password must be 6+ chars, letters & numbers';
msg.className=regex.test(passwordInput.value)?'message valid':'message invalid';
});

branchInput.addEventListener('input',()=>{
const regex=/^.+$/;
const msg=document.getElementById('branchMessage');
msg.textContent=regex.test(branchInput.value)?'Looks good!':'Branch cannot be empty';
msg.className=regex.test(branchInput.value)?'message valid':'message invalid';
});
</script>

</body>
</html>
