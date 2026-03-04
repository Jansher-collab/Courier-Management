<?php
session_start();
include('../includes/auth.php');
include('../includes/db.php');
include('../includes/mail.php');

requireAdmin();

$courier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($courier_id <= 0) { header("Location: view_couriers.php"); exit; }

$valid_statuses = ['booked', 'in-progress', 'delivered'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_location = trim($_POST['from_location'] ?? '');
    $to_location   = trim($_POST['to_location'] ?? '');
    $courier_type  = trim($_POST['courier_type'] ?? '');
    $delivery_date = $_POST['delivery_date'] ?? '';
    $status        = trim($_POST['status'] ?? '');
    $agent_id      = ($_POST['agent_id'] !== '') ? intval($_POST['agent_id']) : NULL;

    if (!$from_location || !$to_location || !$courier_type || !$delivery_date || !in_array($status, $valid_statuses)) {
        $error = "All fields are required and status must be valid.";
    } else {
        $stmt = $conn->prepare("
            UPDATE couriers SET
                from_location=?,
                to_location=?,
                courier_type=?,
                delivery_date=?,
                status=?,
                agent_id=?
            WHERE courier_id=?
        ");
        $stmt->bind_param("sssssii",$from_location,$to_location,$courier_type,$delivery_date,$status,$agent_id,$courier_id);
        if ($stmt->execute()) { $success="Courier updated successfully."; } 
        else { $error="Failed to update courier."; }
    }
}

$stmt = $conn->prepare("
    SELECT c.*, s.name AS sender_name, r.name AS receiver_name
    FROM couriers c
    LEFT JOIN customers s ON c.sender_id = s.customer_id
    LEFT JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.courier_id = ? LIMIT 1
");
$stmt->bind_param("i", $courier_id);
$stmt->execute();
$result = $stmt->get_result();
$courier = $result ? $result->fetch_assoc() : null;
if (!$courier) { header("Location: view_couriers.php"); exit; }

$agents=[];
$res=$conn->query("SELECT a.agent_id,u.name,a.branch FROM agents a JOIN users u ON a.user_id=u.user_id ORDER BY branch ASC");
while($r=$res->fetch_assoc()){$agents[]=$r;}

/* Get agent name */
$currentAgentName = "Select Agent";
foreach($agents as $a){
    if($a['agent_id'] == $courier['agent_id']){
        $currentAgentName = $a['name']." - ".$a['branch'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Courier</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{overflow-x:hidden;}

body{
background:url('../assets/admin-view-couriers.jpg') center/cover no-repeat fixed;
padding-bottom:80px;
position:relative;
}
body::after{
content:'';
position:fixed;
top:0;left:0;
width:100%;height:100%;
background:rgba(0,0,0,0.35);
z-index:-1;
}

/* NAVBAR */
.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
flex-wrap:wrap;
}
.logo{font-size:1.4rem;font-weight:bold;color:#ff7e5f;}
.nav-buttons{display:flex;gap:15px;}
.btn{
padding:8px 16px;
border-radius:8px;
font-weight:bold;
color:white;
text-decoration:none;
font-size:14px;
}
.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}

@media(max-width:768px){
.navbar{flex-direction:column;align-items:center;gap:15px;}
.nav-buttons{width:100%;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;}
.btn{padding:7px 14px;font-size:13px;}
}

/* CONTAINER */
.container{
width:95%;
max-width:500px;
margin:40px auto;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(15px);
border-radius:20px;
padding:25px;
color:#fff;
}

/* FORM */
label{font-weight:600;margin-top:10px;display:block;font-size:14px;}
input{
width:100%;
padding:10px;
margin-top:6px;
margin-bottom:12px;
border-radius:8px;
border:none;
outline:none;
}
input:hover,input:focus{box-shadow:0 0 10px rgba(255,126,95,.8);}

button{
padding:10px 18px;
border:none;
border-radius:8px;
font-weight:bold;
cursor:pointer;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
font-size:14px;
display:block;
margin:20px auto 0;
}

/* STATUS DROPDOWN */
.custom-dropdown{position:relative;margin-bottom:15px;cursor:pointer;}
.custom-selected{background:#fff;color:#000;padding:10px;border-radius:8px;}
.dropdown-items{
position:absolute;
top:100%;
left:0;
right:0;
background:#fff;
color:#000;
border-radius:8px;
display:none;
z-index:99;
overflow:hidden;
}
.dropdown-items div{padding:10px;}
.dropdown-items div:hover{background:#ff7e5f;color:#fff;}

/* AGENT MODAL */
.modal{
display:none;
position:fixed;
top:0;left:0;
width:100%;height:100%;
background:rgba(0,0,0,0.6);
z-index:9999;
justify-content:center;
align-items:center;
}
.modal-content{
background:#fff;
color:#000;
padding:20px;
border-radius:12px;
max-height:70%;
overflow-y:auto;
width:90%;
max-width:400px;
}
.modal-content div{
padding:10px;
cursor:pointer;
border-bottom:1px solid #eee;
}
.modal-content div:hover{background:#ff7e5f;color:#fff;}
#selected-agent{
background:#fff;
color:#000;
padding:10px;
border-radius:8px;
cursor:pointer;
margin-bottom:12px;
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

<h2 style="text-align:center;margin-bottom:20px;">Update Courier #<?= htmlspecialchars($courier_id) ?></h2>

<form method="POST">

<label>From</label>
<input type="text" name="from_location" value="<?= htmlspecialchars($courier['from_location']) ?>" required>

<label>To</label>
<input type="text" name="to_location" value="<?= htmlspecialchars($courier['to_location']) ?>" required>

<label>Type</label>
<input type="text" name="courier_type" value="<?= htmlspecialchars($courier['courier_type']) ?>" required>

<label>Delivery Date</label>
<input type="date" name="delivery_date" value="<?= htmlspecialchars($courier['delivery_date']) ?>" required>

<label>Status</label>
<div class="custom-dropdown" id="statusDropdown">
<div class="custom-selected"><?= ucfirst(str_replace('-',' ',$courier['status'])) ?></div>
<div class="dropdown-items">
<?php foreach($valid_statuses as $s): ?>
<div data-value="<?= $s ?>"><?= ucfirst(str_replace('-',' ',$s)) ?></div>
<?php endforeach; ?>
</div>
<input type="hidden" name="status" value="<?= $courier['status'] ?>">
</div>

<label>Assign Agent</label>
<div id="selected-agent"><?= htmlspecialchars($currentAgentName) ?></div>
<input type="hidden" name="agent_id" id="agentInput" value="<?= $courier['agent_id'] ?>">

<button type="submit">Update Courier</button>
</form>
</div>

<!-- Agent Modal -->
<div class="modal" id="agentModal">
<div class="modal-content">
<?php foreach($agents as $a): ?>
<div data-value="<?= $a['agent_id'] ?>"><?= htmlspecialchars($a['name'].' - '.$a['branch']) ?></div>
<?php endforeach; ?>
</div>
</div>

<script>
// STATUS DROPDOWN
const statusDropdown = document.getElementById('statusDropdown');
const selected = statusDropdown.querySelector('.custom-selected');
const items = statusDropdown.querySelector('.dropdown-items');
const statusInput = statusDropdown.querySelector('input');

selected.onclick = ()=> items.style.display = items.style.display==='block'?'none':'block';
items.querySelectorAll('div').forEach(div=>{
div.onclick=()=>{
selected.textContent=div.textContent;
statusInput.value=div.dataset.value;
items.style.display='none';
};
});
document.addEventListener('click',e=>{
if(!statusDropdown.contains(e.target)) items.style.display='none';
});

// AGENT MODAL
const agentDisplay=document.getElementById('selected-agent');
const agentModal=document.getElementById('agentModal');
const agentInput=document.getElementById('agentInput');

agentDisplay.onclick=()=>agentModal.style.display='flex';
agentModal.querySelectorAll('.modal-content div').forEach(div=>{
div.onclick=()=>{
agentDisplay.textContent=div.textContent;
agentInput.value=div.dataset.value;
agentModal.style.display='none';
};
});
agentModal.onclick=e=>{if(e.target===agentModal)agentModal.style.display='none';};
</script>

</body>
</html>