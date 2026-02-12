<?php
include('../includes/auth.php');
include('../includes/db.php');
include('../includes/mail.php');

requireAdmin();

$error = '';
$success = '';

// Form processing
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
            $error = "Email already exists. Please use another email.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'agent')");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $stmt2 = $conn->prepare("INSERT INTO agents (user_id, branch) VALUES (?, ?)");
                $stmt2->bind_param("is", $user_id, $branch);

                if ($stmt2->execute()) {
                    $subject = "Your Agent Account Created";
                    $body = "Hello $name,\nYour agent account has been created.\nEmail: $email\nPassword: $password";
                    send_mail($email, $subject, $body);
                    $success = "Agent created successfully.";
                } else {
                    $error = "Failed to create agent branch.";
                }
            } else {
                $error = "Failed to create user.";
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
<title>Create Agent - Admin Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html, body{height:100%; width:100%; overflow:auto; scroll-behavior:smooth; scrollbar-width:none; -ms-overflow-style:none;}
body::-webkit-scrollbar{display:none;}

/* --- BACKGROUND IMAGE WITH DARK OVERLAY --- */
body {
    background: url('../assets/agent.jpg') center/cover no-repeat fixed;
    position: relative;
    padding-bottom: 150px; /* extra space after form */
}
body::after {
    content: '';
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35);
    z-index: -1;
}

/* --- NAVBAR --- */
.navbar{
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    position: sticky;
    top:0;
    z-index: 999;
}
.navbar .logo{
    font-size:1.5rem;
    font-weight:bold;
    color:#fff;
}
.navbar a.logout{
    text-decoration:none;
    padding:12px 25px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.4s;
}
.navbar a.logout:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* --- FORM CONTAINER --- */
.container{
    width:90%;
    max-width:600px;
    margin:50px auto 80px auto; /* space after navbar and below */
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(15px);
    border-radius:20px;
    padding:30px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    color: #fff;
    animation:fadeIn 0.7s ease;
}

/* --- FORM ELEMENTS --- */
h2{ text-align:center; margin-bottom:20px; }
label{ display:block; margin:10px 0 5px; }
input, button{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:none;
    outline:none;
    margin-bottom:5px;
    font-size:1rem;
    background: rgba(255,255,255,0.95);
    color:#000;
    transition:0.3s;
}
input:focus, select:focus{
    box-shadow:0 0 10px rgba(255,126,95,0.6);
}

/* --- BUTTON --- */
button{
    padding:14px;
    border-radius:10px;
    font-size:1rem;
    cursor:pointer;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
    font-weight:bold;
    transition:0.4s;
}
button:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* --- VALIDATION MESSAGES --- */
.container span.message{
    display:block;
    margin-bottom:10px;
    font-size:0.9rem;
}
.container span.invalid{ color:#ff4c4c; }
.container span.valid{ color:#b8ffb8; }

/* --- MESSAGES --- */
p.success{ color:#d4ffd4; text-align:center; margin-bottom:15px; }
p.error{ color:#ffd4d4; text-align:center; margin-bottom:15px; }

/* --- ANIMATION --- */
@keyframes fadeIn{
    from{opacity:0; transform:translateY(15px);}
    to{opacity:1; transform:translateY(0);}
}

/* --- RESPONSIVE --- */
@media(max-width:600px){
    .container{ padding:20px; margin:30px auto 60px auto; }
    input, select, button{ font-size:0.9rem; padding:10px; }
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Courier Admin</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
    <h2>Create New Agent</h2>
    <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if(!empty($success)) echo "<p class='success'>$success</p>"; ?>

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

<!-- Extra space after form -->
<div style="height: 150px;"></div>

<script>
// Real-time validation
const form = document.getElementById('agentForm');

const nameInput = form.name;
const emailInput = form.email;
const passwordInput = form.password;
const branchInput = form.branch;

nameInput.addEventListener('input', () => {
    const regex = /^[A-Z][a-zA-Z ]*$/;
    document.getElementById('nameMessage').textContent = regex.test(nameInput.value) ? 'Looks good!' : 'Name must start with capital letter';
    document.getElementById('nameMessage').className = regex.test(nameInput.value) ? 'message valid' : 'message invalid';
});

emailInput.addEventListener('input', () => {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    document.getElementById('emailMessage').textContent = regex.test(emailInput.value) ? 'Looks good!' : 'Invalid email format';
    document.getElementById('emailMessage').className = regex.test(emailInput.value) ? 'message valid' : 'message invalid';
});

passwordInput.addEventListener('input', () => {
    const regex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;
    document.getElementById('passwordMessage').textContent = regex.test(passwordInput.value) ? 'Looks good!' : 'Password must be 6+ chars, letters & numbers';
    document.getElementById('passwordMessage').className = regex.test(passwordInput.value) ? 'message valid' : 'message invalid';
});

branchInput.addEventListener('input', () => {
    const regex = /^.+$/;
    document.getElementById('branchMessage').textContent = regex.test(branchInput.value) ? 'Looks good!' : 'Branch cannot be empty';
    document.getElementById('branchMessage').className = regex.test(branchInput.value) ? 'message valid' : 'message invalid';
});
</script>

</body>
</html>
