<?php
include('../includes/db.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Backend validation
    if (!preg_match("/^[A-Z][a-zA-Z\s]*$/", $name)) {
        $error = "Name must start with a capital letter and contain only letters and spaces.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($phone && !preg_match("/^\d{10,15}$/", $phone)) {
        $error = "Phone number must contain only digits (10-15 digits).";
    } else {
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $stmt2 = $conn->prepare("INSERT INTO customers (customer_id, name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("issss", $user_id, $name, $email, $phone, $address);
                $stmt2->execute();
                $success = "Registration successful. <a href='../login.php'>Login here</a>.";
            } else {
                $error = "Registration failed. Please try again.";
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
<title>User Registration</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body, html{height:100%; width:100%; background:#f4f7fb; display:flex; align-items:center; justify-content:center;}
body{
    background:url('../assets/user-register.jpg') center/cover no-repeat fixed;
    display:flex; justify-content:center; align-items:center;
    position:relative;
}
.container{
    width:100%; max-width:450px; padding:35px;
    background:rgba(255,255,255,0.95); border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15); backdrop-filter:blur(10px);
    animation:fadeIn 0.8s ease;
}

.container h2{
    text-align:center; margin-bottom:25px;
    font-size:1.8rem;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.input-group{margin-bottom:18px; position:relative;}
.input-group input{
    width:100%; padding:14px; border-radius:12px; border:1px solid #ccc;
    font-size:1rem; outline:none; transition:0.3s;
}
.input-group input:focus{
    box-shadow:0 0 10px rgba(255,126,95,0.6), 0 0 20px rgba(255,126,95,0.3);
    border-color:#ff7e5f;
}

/* Validation message */
.validation-msg{font-size:0.85rem; margin-top:5px; height:18px;}
.validation-msg.valid{color:#27ae60;}
.validation-msg.invalid{color:#e74c3c;}

/* BUTTON */
button{
    width:100%; padding:14px; border:none; border-radius:12px;
    font-weight:bold; font-size:1rem; color:white; cursor:pointer;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.4s;
}
button:hover{
    transform:translateY(-2px); box-shadow:0 0 15px rgba(255,126,95,0.6),0 0 30px rgba(255,126,95,0.4);
}

/* MESSAGES */
.message{margin-bottom:15px; text-align:center; font-weight:bold;}
.error{color:#e74c3c;}
.success{color:#27ae60;}

/* RESPONSIVE */
@media(max-width:500px){
    .container{padding:25px; margin:10px;}
    button, .input-group input{font-size:0.95rem; padding:12px;}
}

/* ANIMATION */
@keyframes fadeIn{from{opacity:0; transform:translateY(20px);}to{opacity:1; transform:translateY(0);}}
</style>
</head>
<body>

<div class="container">
<h2>User Registration</h2>

<?php if($error) echo "<p class='message error'>$error</p>"; ?>
<?php if($success) echo "<p class='message success'>$success</p>"; ?>

<form method="POST" id="registerForm" novalidate>
    <div class="input-group">
        <input type="text" name="name" id="name" placeholder="Full Name" required pattern="^[A-Z][a-zA-Z\s]*$" title="Name must start with a capital letter">
        <div class="validation-msg" id="nameMsg"></div>
    </div>
    <div class="input-group">
        <input type="email" name="email" id="email" placeholder="Email Address" required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" title="Enter a valid email">
        <div class="validation-msg" id="emailMsg"></div>
    </div>
    <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required pattern=".{6,}" title="Password must be at least 6 characters">
        <div class="validation-msg" id="passMsg"></div>
    </div>
    <div class="input-group">
        <input type="text" name="phone" id="phone" placeholder="Phone (Optional)" pattern="^\d{10,15}$" title="Digits only, 10-15 digits">
        <div class="validation-msg" id="phoneMsg"></div>
    </div>
    <div class="input-group">
        <input type="text" name="address" placeholder="Address (Optional)">
    </div>
    <button type="submit">Register Now</button>
</form>
</div>

<script>
const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const phoneInput = document.getElementById('phone');

const nameMsg = document.getElementById('nameMsg');
const emailMsg = document.getElementById('emailMsg');
const passMsg = document.getElementById('passMsg');
const phoneMsg = document.getElementById('phoneMsg');

nameInput.addEventListener('input', ()=>{
    const regex = /^[A-Z][a-zA-Z\s]*$/;
    if(nameInput.value === '') nameMsg.textContent = '';
    else if(regex.test(nameInput.value)) nameMsg.textContent='Valid Name ', nameMsg.className='validation-msg valid';
    else nameMsg.textContent='Name must start with a capital letter', nameMsg.className='validation-msg invalid';
});

emailInput.addEventListener('input', ()=>{
    const regex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    if(emailInput.value === '') emailMsg.textContent='';
    else if(regex.test(emailInput.value)) emailMsg.textContent='Valid Email ', emailMsg.className='validation-msg valid';
    else emailMsg.textContent='Invalid Email', emailMsg.className='validation-msg invalid';
});

passwordInput.addEventListener('input', ()=>{
    if(passwordInput.value === '') passMsg.textContent='';
    else if(passwordInput.value.length >= 6) passMsg.textContent='Strong Password ', passMsg.className='validation-msg valid';
    else passMsg.textContent='Password must be at least 6 characters', passMsg.className='validation-msg invalid';
});

phoneInput.addEventListener('input', ()=>{
    const regex = /^\d{11,15}$/;
    if(phoneInput.value === '') phoneMsg.textContent='';
    else if(regex.test(phoneInput.value)) phoneMsg.textContent='Valid Phone ', phoneMsg.className='validation-msg valid';
    else phoneMsg.textContent='Phone must be 11-15 digits', phoneMsg.className='validation-msg invalid';
});
</script>

</body>
</html>
