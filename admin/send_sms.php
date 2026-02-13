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

$selected_courier_id = $_POST['courier_id'] ?? '';
$to_email = '';
$courier_id_val = '';

if($selected_courier_id){
    $stmt_c = $conn->prepare("SELECT r.email FROM couriers c JOIN customers r ON c.receiver_id = r.customer_id WHERE c.courier_id = ?");
    $stmt_c->bind_param("i", $selected_courier_id);
    $stmt_c->execute();
    $courier_data = $stmt_c->get_result()->fetch_assoc();
    $to_email = $courier_data['email'];
    $courier_id_val = $selected_courier_id;
}

if(isset($_POST['send_email'])){
    $courier_id = $_POST['courier_id'] ?: null;
    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $body_content = trim($_POST['message']);

    if(send_mail($to, $subject, $body_content, false)){
        $message_sent = "Email sent successfully to " . htmlspecialchars($to);
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
<title>Send Email</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}
body{background:url('../assets/send-sms.jpg') center/cover no-repeat fixed;padding-top:80px;overflow-x:hidden;}
body::-webkit-scrollbar{display:none;}

.navbar{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;position:fixed;top:0;width:100%;}
.logo{ color:#ff7e5f;font-size:1.5rem;font-weight:bold;}
.nav-buttons{display:flex;gap:10px;}
.btn{text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:bold;color:white;}
.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}

.container{
width:95%;max-width:700px;margin:50px auto;background:rgba(255,255,255,0.15);backdrop-filter:blur(15px);border-radius:20px;padding:30px;color:#fff;
}

form{display:flex;flex-direction:column;gap:15px;}

input,textarea{
padding:12px;
border-radius:10px;
border:none;
background:#fff;
color:#000;
transition:0.3s;
}

/* --- Glow up on focus --- */
input:focus, textarea:focus{
outline:none;
box-shadow:0 0 12px 3px rgba(255,126,95,0.7);
border:1px solid #ff7e5f;
}

button{
padding:12px;
border-radius:10px;
border:none;
cursor:pointer;
font-weight:bold;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
transition:0.3s;
}
button:hover{transform:translateY(-1px);box-shadow:0 4px 15px rgba(0,0,0,0.25);}

/* Dropdown */
.dropdown-wrapper{position:relative;}
.dropdown-selected{
padding:12px;
border-radius:10px;
background:#fff;
color:#000;
cursor:pointer;
transition:0.3s;
}
/* Glow when clicked */
.dropdown-selected.active{
box-shadow:0 0 12px 3px rgba(255,126,95,0.7);
border:1px solid #ff7e5f;
}

.dropdown-items{
position:absolute;
width:100%;
max-height:250px;
overflow-y:auto;
background:#fff;
border-radius:10px;
display:none;
z-index:100;
scrollbar-width:none;
}
.dropdown-items::-webkit-scrollbar{display:none;}

.dropdown-items div{
padding:10px;
cursor:pointer;
color:#000;
}
.dropdown-items div:hover{
background:#ff7e5f;
color:#fff;
}

.search-box{
padding:10px;
border:none;
border-bottom:1px solid #ccc;
outline:none;
width:100%;
transition:0.3s;
}
/* Glow on focus for search box */
.search-box:focus{
box-shadow:0 0 12px 3px rgba(255,126,95,0.7);
border:1px solid #ff7e5f;
}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Admin</div>
<div class="nav-buttons">
<a href="dashboard.php" class="btn dashboard">Dashboard</a>
<a href="../logout.php" class="btn logout">Logout</a>
</div>
</div>

<div class="container">

<h2>Send Email to Customer</h2>
<?php if($message_sent) echo "<p>$message_sent</p>"; ?>

<div class="dropdown-wrapper">
<label>Select Courier:</label>
<div class="dropdown-selected" id="selected">--Select Courier--</div>
<div class="dropdown-items" id="dropdown-items">
<input type="text" class="search-box" id="searchInput" placeholder="Search by ID, Name or Email...">
<?php foreach($couriers as $c): ?>
<div 
data-search="<?= strtolower($c['courier_id']." ".$c['receiver_name']." ".$c['receiver_email']) ?>"
data-email="<?= htmlspecialchars($c['receiver_email'], ENT_QUOTES) ?>"
data-id="<?= htmlspecialchars($c['courier_id'], ENT_QUOTES) ?>">
<?= "Courier ID: ".$c['courier_id']." - ".$c['receiver_name']." (".$c['receiver_email'].")" ?>
</div>
<?php endforeach; ?>
</div>
</div>

<form method="POST">
<input type="hidden" name="courier_id" id="courier_id_field">

<label>Email:</label>
<input type="email" id="to_email_input" name="to_email" required>

<label>Subject:</label>
<input type="text" name="subject" value="Courier Notification" required>

<label>Message:</label>
<textarea name="message" rows="5" required>Hello, your courier is being processed.</textarea>

<button type="submit" name="send_email">Send Email</button>
</form>

</div>

<script>
const selected=document.getElementById('selected');
const items=document.getElementById('dropdown-items');
const emailInput=document.getElementById('to_email_input');
const courierField=document.getElementById('courier_id_field');
const searchInput=document.getElementById('searchInput');

selected.onclick=()=>{
items.style.display=items.style.display==='block'?'none':'block';
selected.classList.toggle('active');
};

document.querySelectorAll('.dropdown-items div[data-id]').forEach(div=>{
div.onclick=()=>{
selected.textContent=div.textContent;
emailInput.value=div.dataset.email;
courierField.value=div.dataset.id;
items.style.display='none';
selected.classList.remove('active');
};
});

searchInput.onkeyup=()=>{
const value=searchInput.value.toLowerCase();
document.querySelectorAll('.dropdown-items div[data-id]').forEach(div=>{
div.style.display=div.dataset.search.includes(value)?'block':'none';
});
};

document.addEventListener('click',e=>{
if(!selected.contains(e.target)&&!items.contains(e.target)){
items.style.display='none';
selected.classList.remove('active');
}
});
</script>

</body>
</html>
