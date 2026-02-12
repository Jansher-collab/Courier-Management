<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php'; 

if(!isset($_SESSION['agent_id'])){
    die("<p style='color:red;'>You must be logged in as an agent.</p>");
}

$agent_user_id = $_SESSION['user_id'] ?? null;

$stmt = $conn->prepare("SELECT agent_id, branch FROM agents WHERE user_id=?");
$stmt->bind_param("i", $agent_user_id);
$stmt->execute();
$agent_data = $stmt->get_result()->fetch_assoc();

if(!$agent_data){
    die("<p style='color:red;'>Agent profile not found.</p>");
}

$agent_id = $agent_data['agent_id'];
$branch   = $agent_data['branch'];

$couriers_result = $conn->query("
    SELECT c.courier_id, c.tracking_number, 
           s.name AS sender_name, 
           r.name AS receiver_name
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id IS NULL
    ORDER BY c.courier_id DESC
");

$error = '';
$success = '';

if(isset($_POST['assign'])){
    $courier_id   = intval($_POST['courier_id']);
    $to_location  = trim($_POST['to_location']);
    $courier_type = trim($_POST['courier_type']);
    $delivery_date= $_POST['delivery_date'];

    if(!$courier_id || !$to_location || !$courier_type || !$delivery_date){
        $error = "All fields are required.";
    } else {

        $stmt_update = $conn->prepare("
            UPDATE couriers 
            SET agent_id=?, from_location=?, to_location=?, courier_type=?, delivery_date=? 
            WHERE courier_id=?
        ");

        $stmt_update->bind_param(
            "issssi",
            $agent_id,
            $branch,
            $to_location,
            $courier_type,
            $delivery_date,
            $courier_id
        );

        if($stmt_update->execute()){

            $stmt_email = $conn->prepare("
                SELECT c.tracking_number,
                       s.email AS sender_email,
                       r.email AS receiver_email
                FROM couriers c
                JOIN customers s ON c.sender_id = s.customer_id
                JOIN customers r ON c.receiver_id = r.customer_id
                WHERE c.courier_id=?
            ");

            $stmt_email->bind_param("i", $courier_id);
            $stmt_email->execute();
            $courier_info = $stmt_email->get_result()->fetch_assoc();

            $subject = "Courier Assigned by Agent";
            $body = "
                <h3>Courier Assigned</h3>
                <p><b>Tracking Number:</b> {$courier_info['tracking_number']}</p>
                <p><b>From:</b> $branch</p>
                <p><b>To:</b> $to_location</p>
                <p><b>Courier Type:</b> $courier_type</p>
                <p><b>Delivery Date:</b> $delivery_date</p>
                <p>Status: booked</p>
            ";

            if(!empty($courier_info['sender_email']))
                send_mail($courier_info['sender_email'], $subject, $body);

            if(!empty($courier_info['receiver_email']))
                send_mail($courier_info['receiver_email'], $subject, $body);

            $success = "Courier assigned successfully! Tracking Number: {$courier_info['tracking_number']}";

        } else {
            $error = "Failed to assign courier. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Courier - Agent Panel</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body{
background:url('../assets/agent-add-courier.jpg') center/cover no-repeat fixed;
}

body::after{
content:'';
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.45);
z-index:-1;
}

.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
}

.logo{
font-size:1.4rem;
font-weight:bold;
color:white;
}

.logout{
text-decoration:none;
padding:10px 20px;
border-radius:10px;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

.container{
max-width:650px;
margin:50px auto;
background:rgba(255,255,255,0.95);
padding:30px;
border-radius:20px;
backdrop-filter:blur(8px);
box-shadow:0 10px 35px rgba(0,0,0,0.2);
}

h2{text-align:center;margin-bottom:20px;color:#ff7e5f;}

label{font-weight:600;margin:12px 0 6px;display:block;}

input,button{
width:100%;
padding:12px;
border-radius:12px;
border:1px solid #ccc;
font-size:1rem;
margin-bottom:15px;
transition:.3s;
}

input:focus{
outline:none;
border-color:#ff7e5f;
box-shadow:0 0 10px rgba(255,126,95,0.6);
}

button{
border:none;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:white;
font-weight:bold;
cursor:pointer;
}

button:hover{
transform:translateY(-1px);
box-shadow:0 5px 15px rgba(255,126,95,0.4);
}

.dropdown{position:relative;}

.dropdown-btn{
padding:12px;
border-radius:12px;
border:1px solid #ccc;
cursor:pointer;
background:#fff;
}

.dropdown-btn:focus,
.dropdown-btn.active{
outline:none;
border-color:#ff7e5f;
box-shadow:0 0 10px rgba(255,126,95,0.6);
}

.dropdown-panel{
display:none;
position:absolute;
width:100%;
background:#fff;
border-radius:12px;
overflow:hidden;
box-shadow:0 8px 25px rgba(0,0,0,0.25);
z-index:10;
}

.dropdown-search{
padding:10px;
border:none;
border-bottom:1px solid #eee;
width:100%;
}

.dropdown-item{
padding:12px;
cursor:pointer;
transition:.3s;
}

.dropdown-item:hover{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:white;
}

.preview{
background:#fff7f3;
padding:12px;
border-radius:10px;
margin-bottom:15px;
display:none;
font-size:.95rem;
}

.success{color:#28a745;text-align:center;}
.error{color:#dc3545;text-align:center;}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Agent</div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2>Assign Courier</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST">

<label>Select Courier:</label>
<div class="dropdown">
<div class="dropdown-btn" tabindex="0">Select Courier</div>

<div class="dropdown-panel">
<input type="text" class="dropdown-search" placeholder="Search courier...">
<div class="dropdown-list">
<?php
if($couriers_result){
$couriers_result->data_seek(0);
while($c = $couriers_result->fetch_assoc()){
$text = "[{$c['tracking_number']}] {$c['sender_name']} → {$c['receiver_name']}";
echo "<div class='dropdown-item' data-value='{$c['courier_id']}'>$text</div>";
}
}
?>
</div>
</div>

<input type="hidden" name="courier_id" required>
</div>

<div class="preview" id="previewBox"></div>

<label>Destination:</label>
<input type="text" name="to_location" required>

<label>Courier Type:</label>
<input type="text" name="courier_type" required>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" required>

<button type="submit" name="assign">Assign Courier</button>

</form>
</div>

<script>
const dropdown=document.querySelector('.dropdown');
const btn=dropdown.querySelector('.dropdown-btn');
const panel=dropdown.querySelector('.dropdown-panel');
const hiddenInput=dropdown.querySelector('input[type=hidden]');
const search=dropdown.querySelector('.dropdown-search');
const items=dropdown.querySelectorAll('.dropdown-item');
const preview=document.getElementById('previewBox');

btn.onclick=()=>{
panel.style.display=panel.style.display==='block'?'none':'block';
btn.classList.toggle('active');
};

items.forEach(item=>{
item.onclick=()=>{
hiddenInput.value=item.dataset.value;
btn.textContent=item.textContent;
preview.style.display='block';
preview.innerHTML="<b>Selected:</b><br>"+item.textContent;
panel.style.display='none';
btn.classList.remove('active');
};
});

search.onkeyup=()=>{
const val=search.value.toLowerCase();
items.forEach(i=>{
i.style.display=i.textContent.toLowerCase().includes(val)?'block':'none';
});
};

document.addEventListener('click',e=>{
if(!dropdown.contains(e.target)){
panel.style.display='none';
btn.classList.remove('active');
}
});
</script>
</body>
</html>
