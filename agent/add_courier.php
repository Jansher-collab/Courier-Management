<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['agent_id'])){
    die("<p style='color:red;'>You must be logged in as an agent.</p>");
}

$agent_id = $_SESSION['agent_id'];
$branch   = $_SESSION['branch'] ?? 'N/A';

$available_couriers = $conn->prepare("
    SELECT c.courier_id, c.tracking_number, s.name AS sender_name, r.name AS receiver_name, c.from_location
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id=? AND c.status='booked'
    ORDER BY c.courier_id DESC
");
$available_couriers->bind_param("i", $agent_id);
$available_couriers->execute();
$couriers_result = $available_couriers->get_result();

$success = $error = '';

$status_options = ['in-progress' => 'In-Progress', 'delivered' => 'Delivered'];

if(isset($_POST['process'])){
    $courier_id   = intval($_POST['courier_id']);
    $delivery_date= $_POST['delivery_date'];
    $status       = $_POST['status'];

    if(!$courier_id || !$delivery_date || !$status){
        $error = "All fields are required.";
    } else {
        $stmt_update = $conn->prepare("
            UPDATE couriers
            SET delivery_date=?, status=?
            WHERE courier_id=? AND agent_id=?
        ");
        $stmt_update->bind_param("ssii", $delivery_date, $status, $courier_id, $agent_id);
        if($stmt_update->execute()){
            $stmt_mail = $conn->prepare("
                SELECT c.tracking_number, s.email AS sender_email, r.email AS receiver_email
                FROM couriers c
                JOIN customers s ON c.sender_id = s.customer_id
                JOIN customers r ON c.receiver_id = r.customer_id
                WHERE c.courier_id=?
            ");
            $stmt_mail->bind_param("i",$courier_id);
            $stmt_mail->execute();
            $courier_info = $stmt_mail->get_result()->fetch_assoc();

            $subject = "Courier Update Notification";
            $body = "Courier has been processed by your branch.\n";
            $body .= "Tracking Number: {$courier_info['tracking_number']}\n";
            $body .= "Branch: {$branch}\n";
            $body .= "Delivery Date: $delivery_date\n";
            $body .= "Status: $status\n";

            if(!empty($courier_info['sender_email'])) send_mail($courier_info['sender_email'], $subject, $body, false);
            if(!empty($courier_info['receiver_email'])) send_mail($courier_info['receiver_email'], $subject, $body, false);

            $success = "Courier processed successfully!";
        } else {
            $error = "Failed to process courier. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Process Courier - Agent Panel</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{height:100%;width:100%;overflow-x:hidden;}
body::-webkit-scrollbar{width:0; background:transparent;}
body{
    background:url('../assets/agent-add-courier.jpg') center/cover no-repeat fixed;
    position:relative;
}
body::after{
    content:'';position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.45);z-index:-1;
}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
}
.logo{font-size:1.4rem;font-weight:bold;color:#ff7e5f;}
.nav-buttons{display:flex; gap:10px;}
.logout,.dashboard{
    text-decoration:none;padding:10px 20px;border-radius:10px;font-weight:bold;color:white;transition:0.4s;
}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}
.logout:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}
.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.dashboard:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}

/* MAIN CARD */
.container{
    max-width:650px;margin:50px auto;background:rgba(255,255,255,0.95);
    padding:30px;border-radius:20px;backdrop-filter:blur(8px);
    box-shadow:0 10px 35px rgba(0,0,0,0.2);
}
h2{text-align:center;margin-bottom:20px;color:#ff7e5f;}
label{font-weight:600;margin:12px 0 6px;display:block;}
input,button{width:100%;padding:12px;border-radius:12px;border:1px solid #ccc;font-size:1rem;margin-bottom:15px;transition:.3s;}
input:focus{outline:none;border-color:#ff7e5f;box-shadow:0 0 10px rgba(255,126,95,0.6);}
button{
    border:none;background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;font-weight:bold;cursor:pointer;width:auto;padding:12px 30px;display:block;margin:10px auto 0 auto;
}
button:hover{transform:translateY(-1px);box-shadow:0 5px 15px rgba(255,126,95,0.4);}
.success{color:#28a745;text-align:center;margin-bottom:15px;}
.error{color:#dc3545;text-align:center;margin-bottom:15px;}

/* DROPDOWN LIST */
.dropdown{position:relative;margin-bottom:15px;}
.dropdown-btn{
    padding:12px;border-radius:12px;border:1px solid #ccc;cursor:pointer;background:#fff;
}
.dropdown-btn:focus,.dropdown-btn.active{
    outline:none;border-color:#ff7e5f;box-shadow:0 0 10px rgba(255,126,95,0.6);
}
.dropdown-panel{
    display:none;position:absolute;width:100%;background:#fff;border-radius:12px;overflow:hidden;
    box-shadow:0 8px 25px rgba(0,0,0,0.25);z-index:10;
}
.dropdown-search{
    padding:10px;border:none;border-bottom:1px solid #eee;width:100%;
}
.dropdown-item{
    padding:12px;cursor:pointer;transition:.3s;
}
.dropdown-item:hover{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);color:white;
}
.preview{
    background:#fff7f3;padding:12px;border-radius:10px;margin-bottom:15px;
    display:none;font-size:.95rem;
}

/* RESPONSIVE */
@media(max-width:768px){
.container{margin:30px 15px;padding:25px;}
}
@media(max-width:480px){
.navbar{flex-direction:column;gap:10px;}
.nav-buttons{width:100%;justify-content:space-around;}
}
</style>
</head>

<body>
<div class="navbar">
<div class="logo">Courier Agent</div>
<div class="nav-buttons">
<a href="dashboard.php" class="dashboard">Dashboard</a>
<a href="../logout.php" class="logout">Logout</a>
</div>
</div>

<div class="container">
<h2>Process Assigned Courier</h2>
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
if($couriers_result->num_rows>0){
    $couriers_result->data_seek(0);
    while($c=$couriers_result->fetch_assoc()){
        $text="[{$c['tracking_number']}] {$c['sender_name']} → {$c['receiver_name']} ({$c['from_location']})";
        echo "<div class='dropdown-item' data-value='{$c['courier_id']}'>$text</div>";
    }
}else{
    echo "<div class='dropdown-item' style='cursor:default;color:#999;'>No couriers available</div>";
}
?>
</div>
</div>
<input type="hidden" name="courier_id" required>
</div>

<div class="preview" id="previewBox"></div>

<label>Status:</label>
<div class="dropdown">
<div class="dropdown-btn" tabindex="0">Select Status</div>
<div class="dropdown-panel">
<div class="dropdown-list">
<?php foreach($status_options as $val=>$text): ?>
<div class="dropdown-item" data-value="<?= $val ?>"><?= $text ?></div>
<?php endforeach; ?>
</div>
</div>
<input type="hidden" name="status" required>
</div>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" required>

<button type="submit" name="process">Process Courier</button>
</form>
</div>

<script>
// Function to initialize custom dropdowns
function initDropdown(dropdown){
    const btn = dropdown.querySelector('.dropdown-btn');
    const panel = dropdown.querySelector('.dropdown-panel');
    const hidden = dropdown.querySelector('input[type=hidden]');
    const search = dropdown.querySelector('.dropdown-search');
    const list = dropdown.querySelectorAll('.dropdown-item');

    btn.onclick=()=>{panel.style.display=panel.style.display==='block'?'none':'block'; btn.classList.toggle('active');};

    list.forEach(item=>{
        item.onclick=()=>{
            hidden.value=item.dataset.value;
            btn.textContent=item.textContent;
            const preview=document.getElementById('previewBox');
            if(preview) preview.style.display='block', preview.innerHTML="<b>Selected:</b><br>"+item.textContent;
            panel.style.display='none';
            btn.classList.remove('active');
        };
    });

    if(search){
        search.onkeyup=()=>{
            const val=search.value.toLowerCase();
            list.forEach(i=>{i.style.display=i.textContent.toLowerCase().includes(val)?'block':'none';});
        };
    }

    document.addEventListener('click',e=>{
        if(!dropdown.contains(e.target)){panel.style.display='none'; btn.classList.remove('active');}
    });
}

document.querySelectorAll('.dropdown').forEach(initDropdown);
</script>
</body>
</html>