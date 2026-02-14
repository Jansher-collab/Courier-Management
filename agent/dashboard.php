<?php
session_start();
include '../includes/db.php';
include '../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$branch = $_SESSION['branch'] ?? 'N/A';

$status_counts = ['booked'=>0, 'in-progress'=>0, 'delivered'=>0];

$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM couriers
    WHERE agent_id = ?
    GROUP BY status
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $status_counts[$row['status']] = $row['count'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Dashboard</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{overflow-x:hidden;scrollbar-width:none;}
body::-webkit-scrollbar{display:none;}

body{
background:url('../assets/agent-dashboard.jpg') center/cover no-repeat fixed;
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

/* NAVBAR */
.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:18px 35px;
}
.logo{
color:#ff7e5f;
font-size:1.6rem;
font-weight:bold;
}
.logout{
text-decoration:none;
padding:12px 25px;
border-radius:10px;
font-weight:bold;
color:#fff;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
transition:0.3s;
}
.logout:hover{
transform:translateY(-2px);
box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* MAIN CONTAINER */
.container{
width:92%;
max-width:950px;
margin:50px auto;
background:#ffffff;
border-radius:22px;
padding:35px;
color:#333;
box-shadow:0 12px 35px rgba(0,0,0,0.25);
}

/* HEADINGS */
h2{
text-align:center;
margin-bottom:10px;
color:#ff7e5f;
font-size:1.9rem;
}
h3{
margin-top:25px;
margin-bottom:12px;
color:#ff7e5f;
font-size:1.3rem;
text-align:center;
}
.branch{
text-align:center;
margin-bottom:20px;
font-weight:500;
}

/* STATUS CARDS */
.status-list{
display:flex;
gap:15px;
flex-wrap:wrap;
justify-content:center;
margin-bottom:25px;
}
.status-card{
flex:1 1 30%;
min-width:180px;
background:#fff;
padding:20px;
border-radius:14px;
text-align:center;
font-weight:bold;
color:#ff7e5f;
box-shadow:0 8px 20px rgba(0,0,0,0.12);
transition:0.3s;
}
.status-card:hover{
transform:translateY(-4px);
box-shadow:0 12px 28px rgba(0,0,0,0.18);
}

/* ACTION BUTTONS */
.actions{
display:flex;
flex-wrap:wrap;
justify-content:center;
gap:15px;
}
.actions a{
text-decoration:none;
padding:14px 20px;
border-radius:12px;
font-weight:bold;
color:#fff;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
transition:0.3s;
text-align:center;
flex:1 1 40%;
max-width:220px;
}
.actions a:hover{
transform:translateY(-3px);
box-shadow:0 8px 22px rgba(0,0,0,0.25);
}

/* MOBILE */
@media(max-width:600px){
.container{padding:22px;margin-top:80px;}
.status-card{flex:1 1 100%;}
.actions a{flex:1 1 100%;max-width:none;}
}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Agent </div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">

<h2>Welcome, <?= htmlspecialchars($_SESSION['agent_name']); ?></h2>
<p class="branch">Branch: <?= htmlspecialchars($branch); ?></p>

<h3>Shipment Status Overview</h3>

<div class="status-list">
<div class="status-card">Booked<br><?= $status_counts['booked']; ?></div>
<div class="status-card">In-Progress<br><?= $status_counts['in-progress']; ?></div>
<div class="status-card">Delivered<br><?= $status_counts['delivered']; ?></div>
</div>

<h3>Quick Actions</h3>

<div class="actions">
<a href="add_courier.php">Add New Courier</a>
<a href="view_couriers.php">View Couriers</a>
<a href="download_reports.php">Download Report</a>
<a href="send_sms.php">Send Custom Email</a>
<a href="send_delivery_sms.php">Send Delivery Email</a>
</div>

</div>

</body>
</html>
