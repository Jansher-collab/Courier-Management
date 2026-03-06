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
            $success = "Courier added successfully! Tracking Number: $tracking_number";
        } 
        else $error = "Failed to add courier: " . $stmt->error;
    }
}

$users = $conn->query("SELECT user_id,name,email FROM users");
$agents = $conn->query("SELECT a.agent_id,u.name,u.email FROM agents a JOIN users u ON a.user_id=u.user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Courier</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html, body{width:100%; overflow-x:hidden;/* Hide scrollbar but keep scroll */
scrollbar-width:none;        /* Firefox */
-ms-overflow-style:none; }

body{
background:url('../assets/admin-add-courier.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:50px;
}

body::after{
content:'';
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.35);
z-index:-1;
}

.navbar{
width:100%;
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
flex-wrap:wrap;
}

.logo{
font-size:1.5rem;
font-weight:bold;
color:#ff7e5f;
}

.nav-right{
display:flex;
gap:15px;
}

.dashboard,.logout{
text-decoration:none;
padding:10px 20px;
border-radius:10px;
font-weight:bold;
color:white;
}

.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}

.container{
width:90%;
max-width:600px;
margin:30px auto;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(15px);
border-radius:15px;
padding:25px;
color:#fff;
}

h2{text-align:center;margin-bottom:15px;}

label{display:block;margin:8px 0 5px;font-weight:600;}

input,button,.custom-dropdown-btn{
width:100%;
padding:12px;
border-radius:8px;
border:none;
margin-bottom:12px;
font-size:1rem;
background:#fff;
color:#000;
outline:none;
transition:all .25s ease;
}

/* Glow hover effect */
input:hover,
.custom-dropdown-btn:hover{
box-shadow:0 0 10px rgba(255,255,255,0.6),
           0 0 20px rgba(255,126,95,0.6);
transform:translateY(-1px);
}

input:focus{
box-shadow:0 0 10px rgba(255,255,255,0.7),
           0 0 25px rgba(255,126,95,0.9);
}

button{
cursor:pointer;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:white;
font-weight:bold;

width:auto;
display:block;
margin:15px auto;   /* centers the button */
padding:12px 22px;
}

p.success{color:#28a745;text-align:center;margin-bottom:12px;font-weight:700;}
p.error{color:#ffd4d4;text-align:center;margin-bottom:12px;font-weight:700;}

.modal{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:999;
}

.modal-content{
background:white;
width:90%;
max-width:500px;
padding:20px;
border-radius:10px;
color:#000;
}

#searchUser{
width:100%;
padding:10px;
margin-bottom:10px;
border:1px solid #ccc;
border-radius:6px;
}

.list{
max-height:300px;
overflow-y:auto;
}

/* hidden scrollbar but still scrollable */
.list::-webkit-scrollbar{width:0px;}

.user-item{
padding:10px;
border-bottom:1px solid #eee;
cursor:pointer;
}

.user-item:hover{
background:#f3f3f3;
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
<h2>Add New Courier</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST">

<label>Sender:</label>
<input type="hidden" name="sender_id" id="sender_id" required>
<button type="button" class="custom-dropdown-btn" onclick="openModal('sender')">Select Sender</button>
<div id="sender_display"></div>

<label>Receiver:</label>
<input type="hidden" name="receiver_id" id="receiver_id" required>
<button type="button" class="custom-dropdown-btn" onclick="openModal('receiver')">Select Receiver</button>
<div id="receiver_display"></div>

<label>From City:</label>
<input type="text" name="from_location" required>

<label>To City:</label>
<input type="text" name="to_location" required>

<label>Courier Type:</label>
<input type="text" name="courier_type" required>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" required>

<label>Assign Agent:</label>
<input type="hidden" name="agent_id" id="agent_id" required>
<button type="button" class="custom-dropdown-btn" onclick="openModal('agent')">Select Agent</button>
<div id="agent_display"></div>

<button type="submit">Add Courier</button>

</form>
</div>

<div id="userModal" class="modal">
<div class="modal-content" onmouseleave="closeModal()">

<input type="text" id="searchUser" placeholder="Search by name, email or id" onkeyup="searchUser()">

<div class="list">

<?php
$users->data_seek(0);
while($u=$users->fetch_assoc()){
echo "<div class='user-item user'
data-id='{$u['user_id']}'
data-name='".strtolower($u['name'])."'
data-email='".strtolower($u['email'])."'
onclick=\"selectUser('{$u['user_id']}','{$u['name']}','{$u['email']}')\">
<b>{$u['name']}</b><br>{$u['email']}<br>ID: {$u['user_id']}
</div>";
}

while($a=$agents->fetch_assoc()){
echo "<div class='user-item agent'
data-id='{$a['agent_id']}'
data-name='".strtolower($a['name'])."'
data-email='".strtolower($a['email'])."'
onclick=\"selectAgent('{$a['agent_id']}','{$a['name']}','{$a['email']}')\">
<b>{$a['name']}</b><br>{$a['email']}<br>Agent ID: {$a['agent_id']}
</div>";
}
?>

</div>

</div>
</div>

<script>

let currentType="";

function openModal(type){
currentType=type;
document.getElementById("userModal").style.display="flex";

document.querySelectorAll(".user").forEach(el=>{
el.style.display=(type==="sender"||type==="receiver")?"block":"none";
});

document.querySelectorAll(".agent").forEach(el=>{
el.style.display=(type==="agent")?"block":"none";
});
}

function closeModal(){
document.getElementById("userModal").style.display="none";
document.getElementById("searchUser").value="";
document.querySelectorAll(".user-item").forEach(row=>{
row.style.display="block";
});
}

function selectUser(id,name,email){

if(currentType==="sender"){
document.getElementById("sender_id").value=id;
document.getElementById("sender_display").innerHTML=name+" ("+email+")";
}

if(currentType==="receiver"){
document.getElementById("receiver_id").value=id;
document.getElementById("receiver_display").innerHTML=name+" ("+email+")";
}

closeModal();
}

function selectAgent(id,name,email){
document.getElementById("agent_id").value=id;
document.getElementById("agent_display").innerHTML=name+" ("+email+")";
closeModal();
}

function searchUser(){

let input=document.getElementById("searchUser").value.toLowerCase();
let rows=document.querySelectorAll(".user-item");

rows.forEach(row=>{
let name=row.dataset.name;
let email=row.dataset.email;
let id=row.dataset.id;

if(name.includes(input)||email.includes(input)||id.includes(input)){
row.style.display="block";
}else{
row.style.display="none";
}
});

}

</script>

</body>
</html>