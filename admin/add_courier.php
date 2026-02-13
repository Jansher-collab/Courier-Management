<?php
session_start();
include('../includes/auth.php');   
include('../includes/db.php');
include('../includes/mail.php');   

requireAdmin();

$error = '';
$success = '';

function generateTrackingNumber($length = 10){
    return strtoupper(bin2hex(random_bytes($length/2)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sender_id       = intval($_POST['sender_id'] ?? 0);
    $receiver_id     = intval($_POST['receiver_id'] ?? 0);
    $from_location   = trim($_POST['from_location'] ?? '');
    $to_location     = trim($_POST['to_location'] ?? '');
    $courier_type    = trim($_POST['courier_type'] ?? '');
    $delivery_date   = trim($_POST['delivery_date'] ?? '');
    $agent_id        = intval($_POST['agent_id'] ?? 0);

    if (!$delivery_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $delivery_date)) $error = "Invalid delivery date format. Use YYYY-MM-DD.";
    if (!$sender_id && !$error) $error = "Please select a sender.";
    if (!$receiver_id && !$error) $error = "Please select a receiver.";
    if ((!$from_location || !$to_location || !$courier_type || !$agent_id) && !$error) $error = "All fields are required.";

    if (!$error) {

        $status = 'booked';
        $created_by = $_SESSION['user_id'];
        $tracking_number = generateTrackingNumber(10);

        $stmt = $conn->prepare("
            INSERT INTO couriers 
            (tracking_number, sender_id, receiver_id, from_location, to_location, courier_type, status, delivery_date, created_by, agent_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "siisssssii",
            $tracking_number,
            $sender_id,
            $receiver_id,
            $from_location,
            $to_location,
            $courier_type,
            $status,
            $delivery_date,
            $created_by,
            $agent_id
        );

        if ($stmt->execute()) {

            $courier_id = $stmt->insert_id;

            $stmt_log = $conn->prepare("INSERT INTO courier_logs (courier_id, status, message, notified_via) VALUES (?, ?, ?, ?)");
            $msg = "Courier booked by admin";
            $via = "email";
            $stmt_log->bind_param("isss", $courier_id, $status, $msg, $via);
            $stmt_log->execute();

            $stmt_email = $conn->prepare("SELECT email FROM customers WHERE customer_id = ?");
            $stmt_email->bind_param("i", $sender_id);
            $stmt_email->execute();
            $sender_email = $stmt_email->get_result()->fetch_assoc()['email'];

            $stmt_email->bind_param("i", $receiver_id);
            $stmt_email->execute();
            $receiver_email = $stmt_email->get_result()->fetch_assoc()['email'];

            $subject = "Courier Booking Confirmation";
            $body = "Courier Booking Confirmation\nTracking Number: $tracking_number\nFrom: $from_location\nTo: $to_location\nCourier Type: $courier_type\nStatus: $status\nDelivery Date: $delivery_date";

            if (!empty($sender_email)) send_mail($sender_email, $subject, $body);
            if (!empty($receiver_email)) send_mail($receiver_email, $subject, $body);

            $success = "Courier added successfully! Tracking Number: $tracking_number";
        } 
        else $error = "Failed to add courier: " . $stmt->error;
    }
}

$customers = $conn->query("SELECT customer_id, name FROM customers");
$agents = $conn->query("SELECT a.agent_id, u.name FROM agents a JOIN users u ON a.user_id=u.user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Courier</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html, body{height:100%; width:100%; overflow:auto; scroll-behavior:smooth; scrollbar-width:none; -ms-overflow-style:none;}
body::-webkit-scrollbar{display:none;}

body{background:url('../assets/admin-add-courier.jpg') center/cover no-repeat fixed; position:relative; padding-bottom:50px;}
body::after{ content: ''; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.35); z-index:-1;}

/* NAVBAR */
.navbar{width:100%; display:flex; justify-content:space-between; align-items:center; padding:15px 20px;}
.logo{font-size:1.5rem; font-weight:bold; color:#ff7e5f;}
.nav-right{display:flex; gap:10px;}
.dashboard{ text-decoration:none; padding:10px 20px; border-radius:10px; font-weight:bold; color:#fff; background:linear-gradient(135deg,#ffd200,#f7971e); }
.logout{ text-decoration:none; padding:10px 20px; border-radius:10px; font-weight:bold; color:white; background:linear-gradient(135deg,#ff7e5f,#feb47b); }

/* CONTAINER */
.container{width:90%; max-width:600px; margin:30px auto; background:rgba(255,255,255,0.15); backdrop-filter:blur(15px); border-radius:15px; padding:25px; color:#fff;}
h2{text-align:center;margin-bottom:15px;}
label{display:block;margin:8px 0 5px;font-weight:600;}

/* INPUTS & BUTTONS WITH GLOW */
input, button, .custom-dropdown-btn{
width:100%;
padding:12px;
border-radius:8px;
border:none;
margin-bottom:12px;
font-size:1rem;
background:#fff;
color:#000;
transition:0.3s;
outline:none;
}
input:focus, .custom-dropdown-btn:focus, .custom-dropdown-btn.active{
box-shadow:0 0 12px 3px rgba(255,126,95,0.8);
border:1px solid #ff7e5f;
}

button{cursor:pointer; background:linear-gradient(135deg,#ff7e5f,#feb47b); color:white; font-weight:bold;}
button:hover{ transform:translateY(-1px); box-shadow:0 4px 15px rgba(0,0,0,0.25); }

p.success{color:#d4ffd4;text-align:center;margin-bottom:12px;}
p.error{color:#ffd4d4;text-align:center;margin-bottom:12px;}

/* DROPDOWN */
.custom-dropdown{position:relative;margin-bottom:12px;}
.custom-dropdown-btn{cursor:pointer;display:flex;justify-content:space-between;align-items:center;}
.custom-dropdown-btn::after{content:"▼";font-size:0.8rem;color:#333;}
.custom-dropdown-content{display:none; position:absolute; top:100%; left:0; width:100%; max-height:250px; overflow:auto; background:white; border-radius:8px; z-index:10; scrollbar-width:none; -ms-overflow-style:none;}
.custom-dropdown-content::-webkit-scrollbar{display:none;}
.custom-dropdown-content div{padding:10px 12px; cursor:pointer; background:white; color:#000; transition:0.3s;}
.custom-dropdown-content div:hover{background:linear-gradient(135deg,#ff7e5f,#feb47b); color:white;}
.custom-dropdown-content input.search-input{width:100%; padding:8px; margin:0; border:none; border-bottom:1px solid #ccc; outline:none; font-size:0.95rem;}

@media(max-width:600px){ .container{padding:15px;} input, button, .custom-dropdown-btn, .custom-dropdown-content div{font-size:0.9rem; padding:8px;} }
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
<h2>Add New Courier</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST">

<label>Sender:</label>
<div class="custom-dropdown" id="sender-dropdown">
<div class="custom-dropdown-btn" tabindex="0">Select Sender</div>
<div class="custom-dropdown-content">
<input type="text" class="search-input" placeholder="Search sender...">
<?php
$customers->data_seek(0);
while($row=$customers->fetch_assoc()){
echo "<div data-value='".htmlspecialchars($row['customer_id'])."'>".htmlspecialchars($row['name'])." (ID: {$row['customer_id']})</div>";
}
?>
</div>
<input type="hidden" name="sender_id" required>
</div>

<label>Receiver:</label>
<div class="custom-dropdown" id="receiver-dropdown">
<div class="custom-dropdown-btn" tabindex="0">Select Receiver</div>
<div class="custom-dropdown-content">
<input type="text" class="search-input" placeholder="Search receiver...">
<?php
$customers->data_seek(0);
while($row=$customers->fetch_assoc()){
echo "<div data-value='".htmlspecialchars($row['customer_id'])."'>".htmlspecialchars($row['name'])." (ID: {$row['customer_id']})</div>";
}
?>
</div>
<input type="hidden" name="receiver_id" required>
</div>

<label>From City:</label>
<input type="text" name="from_location" required>

<label>To City:</label>
<input type="text" name="to_location" required>

<label>Courier Type:</label>
<input type="text" name="courier_type" required>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" required>

<label>Assign Agent:</label>
<div class="custom-dropdown" id="agent-dropdown">
<div class="custom-dropdown-btn" tabindex="0">Select Agent</div>
<div class="custom-dropdown-content">
<input type="text" class="search-input" placeholder="Search agent...">
<?php
$agents->data_seek(0);
while($row=$agents->fetch_assoc()){
echo "<div data-value='".htmlspecialchars($row['agent_id'])."'>".htmlspecialchars($row['name'])." (ID: {$row['agent_id']})</div>";
}
?>
</div>
<input type="hidden" name="agent_id" required>
</div>

<button type="submit">Add Courier</button>
</form>
</div>

<script>
document.querySelectorAll('.custom-dropdown').forEach(dropdown=>{
const btn=dropdown.querySelector('.custom-dropdown-btn');
const content=dropdown.querySelector('.custom-dropdown-content');
const hiddenInput=dropdown.querySelector('input[type=hidden]');
const searchInput=content.querySelector('.search-input');

btn.addEventListener('click',()=>{
content.style.display=content.style.display==='block'?'none':'block';
btn.classList.toggle('active');
if(searchInput)searchInput.focus();
});

content.querySelectorAll('div[data-value]').forEach(item=>{
item.addEventListener('click',()=>{
hiddenInput.value=item.dataset.value;
btn.textContent=item.textContent;
content.style.display='none';
btn.classList.remove('active');
});
});

if(searchInput){
searchInput.addEventListener('keyup',()=>{
const filter=searchInput.value.toLowerCase();
content.querySelectorAll('div[data-value]').forEach(item=>{
item.style.display=item.textContent.toLowerCase().includes(filter)?'block':'none';
});
});
}

document.addEventListener('click',e=>{
if(!dropdown.contains(e.target)){
content.style.display='none';
btn.classList.remove('active');
}
});
});
</script>

</body>
</html>
