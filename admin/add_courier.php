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
html, body{width:100%; overflow-x:hidden;}

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

/* NAVBAR */
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

.dashboard, .logout{
text-decoration:none;
padding:10px 20px;
border-radius:10px;
font-weight:bold;
color:white;
white-space:nowrap;
}

.dashboard{
background:linear-gradient(135deg,#ffd200,#f7971e);
}

.logout{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* MOBILE NAVBAR CENTER FIX */
@media(max-width:768px){
.navbar{
flex-direction:column;
align-items:center;
gap:15px;
}

.nav-right{
width:100%;
display:flex;
justify-content:center;
gap:12px;
flex-wrap:wrap;
}

.dashboard, .logout{
padding:8px 18px;
font-size:0.9rem;
}
}

/* CONTAINER */
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

input, button, .custom-dropdown-btn{
width:100%;
padding:12px;
border-radius:8px;
border:none;
margin-bottom:12px;
font-size:1rem;
background:#fff;
color:#000;
outline:none;
}

button{
cursor:pointer;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:white;
font-weight:bold;
}

button:hover{
transform:translateY(-1px);
}

@media(max-width:600px){
.container{padding:15px;}
input, button, .custom-dropdown-btn{font-size:0.9rem;padding:10px;}
}

p.success{color:#28a745;text-align:center;margin-bottom:12px;font-weight:700;}
p.error{color:#ffd4d4;text-align:center;margin-bottom:12px;font-weight:700;}

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
<input type="text" name="sender_id" required>

<label>Receiver:</label>
<input type="text" name="receiver_id" required>

<label>From City:</label>
<input type="text" name="from_location" required>

<label>To City:</label>
<input type="text" name="to_location" required>

<label>Courier Type:</label>
<input type="text" name="courier_type" required>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" required>

<label>Assign Agent:</label>
<input type="text" name="agent_id" required>

<button type="submit">Add Courier</button>

</form>
</div>

</body>
</html>