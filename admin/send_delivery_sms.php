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

// Fetch all couriers that are either in-progress or delivered
$query = "SELECT 
            c.courier_id, 
            r.name AS receiver_name, 
            r.email AS receiver_email,
            c.to_location,
            c.tracking_number,
            c.status
          FROM couriers c
          JOIN customers r ON c.receiver_id = r.customer_id
          WHERE c.status IN ('in-progress', 'delivered')
          ORDER BY c.courier_id ASC";

$result = $conn->query($query);
$couriers = [];
while($row = $result->fetch_assoc()) $couriers[] = $row;

$selected_courier_id = $_POST['courier_id'] ?? '';

if(isset($_POST['send_delivery_email']) && !empty($selected_courier_id)){

    $courier_id = $selected_courier_id;

    $stmt_c = $conn->prepare(
        "SELECT r.name, r.email, c.to_location, c.tracking_number 
         FROM couriers c 
         JOIN customers r ON c.receiver_id = r.customer_id 
         WHERE c.courier_id = ?"
    );

    $stmt_c->bind_param("i", $courier_id);
    $stmt_c->execute();
    $courier = $stmt_c->get_result()->fetch_assoc();

    if($courier){

        $subject = "Courier Status Update";

        $body =
"Hello ".$courier['name'].",

Your courier with tracking number ".$courier['tracking_number']." 
is currently ".$courier['status']." and scheduled for delivery to ".$courier['to_location'].".

Thank you for using our Courier Management System.";

        if(send_mail($courier['email'], $subject, $body)){

            $message_sent = "Delivery email sent to "
                . htmlspecialchars($courier['name'])
                . " (" . htmlspecialchars($courier['email']) . ")";

            $stmt_log = $conn->prepare(
                "INSERT INTO courier_logs (courier_id, status, message, notified_via)
                 VALUES (?, ?, ?, 'email')"
            );

            $status_msg = "Delivery email sent by admin";
            $stmt_log->bind_param("iss", $courier_id, $status_msg, $body);
            $stmt_log->execute();

        } else {
            $message_sent = "Failed to send email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Delivery Email</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}
body{
background:url('../assets/admin-delivery-sms.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:120px;
}
body::after{
content:'';
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.35);
z-index:-1;
}
.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
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
position:relative;
overflow:hidden;
}
h2{text-align:center;margin-bottom:20px;}
p.message{text-align:center;font-weight:bold;margin-bottom:10px;color:#00ff88;}
.dropdown-wrapper{position:relative;width:100%;margin-bottom:20px;}
.dropdown-selected{
width:100%;
padding:12px;
border-radius:10px;
background:#fff;
color:#000;
cursor:pointer;
position:relative;
overflow:hidden;
text-overflow:ellipsis;
white-space:nowrap;
border:none;
outline:none;
transition:0.3s;
}
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
}
.dropdown-items{
position:absolute;
width:100%;
max-width:100%;
max-height:180px;
overflow-y:auto;
overflow-x:hidden;
background:#fff;
border-radius:10px;
top:105%;
left:0; right:0;
box-shadow:0 5px 15px rgba(0,0,0,0.2);
display:none;
z-index:50;
}
.dropdown-items div{
padding:10px;
cursor:pointer;
color:#000;
word-break:break-word;
transition:0.3s;
}
.dropdown-items div:hover{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
}
button{
display:block;
margin:25px auto 0 auto;
padding:12px 30px;
border-radius:10px;
border:none;
cursor:pointer;
font-size:1rem;
font-weight:bold;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
transition:0.3s;
}
button:hover{
transform:translateY(-2px);
box-shadow:0 6px 20px rgba(0,0,0,0.25);
}
input:focus, textarea:focus{
box-shadow:0 0 15px 4px rgba(255,126,95,0.9);
border:1px solid #ff7e5f;
}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Admin</div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">

<h2>Send Delivery Notification Email (Admin)</h2>

<?php if($message_sent) echo "<p class='message'>$message_sent</p>"; ?>

<form method="POST">

<div class="dropdown-wrapper">

<label>Select Courier (In-Progress / Delivered):</label>

<div class="dropdown-selected" id="selected">
<?= $selected_courier_id ? "Courier ID: $selected_courier_id" : "--Select Courier--" ?>
</div>

<div class="dropdown-items" id="dropdown-items">
<?php foreach($couriers as $c): ?>
<div data-id="<?= $c['courier_id'] ?>">
<?= "Courier ID: {$c['courier_id']} - " . htmlspecialchars($c['receiver_name']) . " ({$c['receiver_email']}) to " . htmlspecialchars($c['to_location']) . " [{$c['status']}]" ?>
</div>
<?php endforeach; ?>
</div>

<input type="hidden" name="courier_id" id="courier_id_val" value="<?= $selected_courier_id ?>">

</div>

<button type="submit" name="send_delivery_email">Send Delivery Email</button>

</form>

<div style="height: 80px;"></div> <!-- extra space after form -->

</div>

<script>
// Custom dropdown glow functionality
const selected=document.getElementById('selected');
const items=document.getElementById('dropdown-items');
const hiddenInput=document.getElementById('courier_id_val');

selected.addEventListener('click',()=>{
items.style.display=items.style.display==='block'?'none':'block';
selected.classList.toggle('active');
});

document.querySelectorAll('.dropdown-items div').forEach(div=>{
div.addEventListener('click',()=>{
selected.textContent=div.textContent;
hiddenInput.value=div.getAttribute('data-id');
items.style.display='none';
selected.classList.remove('active');
});
});

document.addEventListener('click',(e)=>{
if(!selected.contains(e.target)&&!items.contains(e.target)){
items.style.display='none';
selected.classList.remove('active');
}
});
</script>

</body>
</html>
