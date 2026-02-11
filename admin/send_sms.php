<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

$message_sent = '';

// Fetch all couriers
$query = "SELECT 
            c.courier_id, 
            r.name AS receiver_name, 
            r.email AS receiver_email
          FROM couriers c
          JOIN customers r ON c.receiver_id = r.customer_id
          ORDER BY c.courier_id ASC";

$result = $conn->query($query);
$couriers = [];
while($row = $result->fetch_assoc()) $couriers[] = $row;

// Pre-fill form if courier selected
$selected_courier_id = $_POST['courier_id'] ?? '';
$to_email = '';
$courier_id_val = '';

if($selected_courier_id){
    $stmt_c = $conn->prepare("SELECT r.email, r.name, c.tracking_number FROM couriers c JOIN customers r ON c.receiver_id = r.customer_id WHERE c.courier_id = ?");
    $stmt_c->bind_param("i", $selected_courier_id);
    $stmt_c->execute();
    $courier_data = $stmt_c->get_result()->fetch_assoc();
    $to_email = $courier_data['email'];
    $courier_id_val = $selected_courier_id;
}

// Send email if form submitted
if(isset($_POST['send_email'])){
    $courier_id = $_POST['courier_id'] ?: null;
    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $body_content = trim($_POST['message']); // plain text

    // Send as plain text (no HTML tags)
    if(send_mail($to, $subject, $body_content, false)){ // 4th param false = plain text
        $message_sent = "Email sent successfully to " . htmlspecialchars($to);

        if($courier_id){
            $stmt_log = $conn->prepare(
                "INSERT INTO courier_logs (courier_id, status, message, notified_via) VALUES (?, ?, ?, 'email')"
            );
            $status_msg = "Custom email sent by admin";
            $stmt_log->bind_param("iss", $courier_id, $status_msg, $body_content);
            $stmt_log->execute();
        }
    } else {
        $message_sent = "Failed to send email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Email to Customer</title>
<style>
/* RESET */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}

/* BACKGROUND */
body{
background:url('../assets/send-sms.jpg') center/cover no-repeat fixed;
position:relative;
padding-top:80px; /* space for navbar */
padding-bottom:50px;
}
body::after{
content:'';
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.35);
z-index:-1;
}

/* NAVBAR */
.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
position:fixed;
top:0;
left:0;
width:100%;
z-index:1000;
}
.logo{color:#fff;font-size:1.5rem;font-weight:bold;}
.logout{
text-decoration:none;
padding:12px 25px;
border-radius:10px;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* CONTAINER */
.container{
width:95%;
max-width:700px;
margin:50px auto;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(15px);
border-radius:20px;
padding:30px;
color:#fff;
box-shadow:0 10px 30px rgba(0,0,0,0.25);
}

/* HEADINGS */
h2{text-align:center;margin-bottom:20px;}

/* FORM */
form{display:flex;flex-direction:column;gap:15px;}
label{font-weight:bold;margin-bottom:5px;}

/* INPUTS / TEXTAREA */
input[type=email], input[type=text], textarea{
width:100%;
padding:12px;
border-radius:10px;
border:none;
outline:none;
font-size:1rem;
background: rgba(255,255,255,0.95);
color:#000;
transition:0.3s;
}

/* Glow effect for inputs and textarea */
input:focus, textarea:focus{
box-shadow:0 0 15px 4px rgba(255,126,95,0.9);
border:1px solid #ff7e5f;
}

/* BUTTON */
button{
padding:12px;
border-radius:10px;
border:none;
cursor:pointer;
font-size:1rem;
font-weight:bold;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
transition:0.4s;
}
button:hover{
transform:translateY(-2px);
box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* MESSAGE */
p.message{
text-align:center;
font-weight:bold;
margin-bottom:10px;
color:#d4ffd4;
}

/* CUSTOM DROPDOWN */
.dropdown-wrapper{position:relative;width:100%;}
.dropdown-selected{
width:100%;
padding:12px;
border-radius:10px;
background:#fff;
color:#000;
cursor:pointer;
user-select:none;
position:relative;
border:none;
outline:none;
transition:0.3s;
}
/* Glow on click/focus */
.dropdown-selected.active{
box-shadow:0 0 15px 4px rgba(255,126,95,0.9);
border:1px solid #ff7e5f;
}
.dropdown-selected::after{
content:"▼";
position:absolute;
right:15px;
top:50%;
transform:translateY(-50%);
font-size:0.8rem;
color:#333;
}
.dropdown-items{
position:absolute;
width:100%;
max-height:200px;
overflow-y:auto;
background:#fff;
border-radius:10px;
top:100%;
left:0;
box-shadow:0 5px 15px rgba(0,0,0,0.2);
display:none;
z-index:100;
}
.dropdown-items div{
padding:10px;
cursor:pointer;
transition:0.3s;
color:#000;
}
.dropdown-items div:hover{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
}

/* SCROLLBAR HIDDEN */
.dropdown-items::-webkit-scrollbar{display:none;}
.dropdown-items{ -ms-overflow-style:none; scrollbar-width:none;}

/* RESPONSIVE */
@media(max-width:600px){
.container{padding:20px;margin-top:80px;}
input, textarea, button, .dropdown-selected{font-size:0.9rem;padding:10px;}
}
</style>
</head>
<body>

<div class="navbar">
<div class="logo">Courier Admin</div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2>Send Email to Customer (Admin)</h2>
<?php if($message_sent) echo "<p class='message'>$message_sent</p>"; ?>

<!-- Custom dropdown -->
<form method="POST" class="dropdown-wrapper">
<label>Select Courier (optional for pre-fill):</label>
<div class="dropdown-selected" id="selected"><?= $selected_courier_id ? "Courier ID: $selected_courier_id" : "--Select Courier--" ?></div>
<div class="dropdown-items" id="dropdown-items">
<?php foreach($couriers as $c): ?>
<div data-id="<?= $c['courier_id'] ?>"><?= "Courier ID: {$c['courier_id']} - " . htmlspecialchars($c['receiver_name']) . " ({$c['receiver_email']})" ?></div>
<?php endforeach; ?>
</div>
<input type="hidden" name="courier_id" id="courier_id_val" value="<?= $selected_courier_id ?>">
</form>

<form method="POST">
<input type="hidden" name="courier_id" value="<?= $courier_id_val ?>">

<label>To (Email Address):</label>
<input type="email" name="to_email" value="<?= htmlspecialchars($to_email) ?>" required />

<label>Subject:</label>
<input type="text" name="subject" value="Courier Notification" required />

<label>Message:</label>
<textarea name="message" rows="5" required>
Hello, your courier is being processed. Please check your tracking number for updates.
</textarea>

<button type="submit" name="send_email">Send Email</button>
</form>
</div>

<script>
// Custom dropdown functionality with glow effect
const selected = document.getElementById('selected');
const items = document.getElementById('dropdown-items');
const hiddenInput = document.getElementById('courier_id_val');

selected.addEventListener('click', ()=>{
    items.style.display = items.style.display === 'block' ? 'none' : 'block';
    selected.classList.toggle('active'); // glow on click
});

document.querySelectorAll('.dropdown-items div').forEach(div=>{
    div.addEventListener('click', ()=>{
        selected.textContent = div.textContent;
        hiddenInput.value = div.getAttribute('data-id');
        items.style.display = 'none';
        selected.classList.remove('active'); // remove glow when selection made
        selected.form.submit(); // auto submit to prefill
    });
});

document.addEventListener('click', (e)=>{
    if(!selected.contains(e.target) && !items.contains(e.target)){
        items.style.display='none';
        selected.classList.remove('active'); // remove glow when click outside
    }
});
</script>

</body>
</html>
