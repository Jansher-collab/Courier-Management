<?php
session_start();
include '../includes/db.php';
include '../includes/auth.php';

// Redirect if not logged in or not agent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$branch = $_SESSION['branch'] ?? 'N/A';

// Initialize counts
$status_counts = ['booked'=>0, 'in-progress'=>0, 'delivered'=>0];

// Fetch shipment counts by status for this agent
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
html, body{height:100%;width:100%;overflow-x:hidden;}

/* BACKGROUND */
body{
background:url('../assets/agent-dashboard.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:150px;
overflow-y:scroll;
scrollbar-width:none; /* Firefox */
}
body::-webkit-scrollbar{width:0; background:transparent;} /* WebKit */
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
}
.logo{color:#ff7e5f;font-size:1.5rem;font-weight:bold;} 
.logout{
text-decoration:none;
padding:12px 25px;
border-radius:10px;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
transition:0.4s;
}
.logout:hover{
transform:translateY(-2px);
box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* CONTAINER */
.container{
width:95%;
max-width:900px;
margin:50px auto;
background:#ffffff;
border-radius:20px;
padding:30px;
color:#ff7e5f;
box-shadow:0 10px 30px rgba(0,0,0,0.25);
}

/* HEADINGS */
h2{text-align:center;margin-bottom:15px;color:#ff7e5f;}
h3{margin-top:25px;margin-bottom:10px;color:#ff7e5f;}

/* STATUS COUNTS */
.status-list{display:flex;justify-content:space-around;flex-wrap:wrap;margin-bottom:20px;}
.status-list div{
background:#ffffff;
padding:15px 25px;
border-radius:12px;
text-align:center;
flex:1 1 30%;
margin:10px;
font-weight:bold;
font-size:1.1rem;
color:#ff7e5f;
box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

/* ACTION BUTTONS */
.actions{display:flex;flex-wrap:wrap;justify-content:center;margin-top:10px;}
.actions a{
text-decoration:none;
padding:12px 20px;
border-radius:10px;
margin:8px;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
transition:0.4s;
text-align:center;
flex:1 1 40%;
max-width:200px;
}
.actions a:hover{
transform:translateY(-2px);
box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* RESPONSIVE */
@media(max-width:600px){
.container{padding:20px;margin-top:80px;}
.status-list div{flex:1 1 100%;}
.actions a{flex:1 1 100%;max-width:none;}
}
</style>
</head>
<body>

<div class="navbar">
<div class="logo">Courier Agent Panel</div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2>Welcome, <?= htmlspecialchars($_SESSION['agent_name']); ?></h2>
<p style="text-align:center;margin-bottom:20px;">Branch: <?= htmlspecialchars($branch); ?></p>

<h3>Shipment Status Counts</h3>
<div class="status-list">
<div>Booked: <?= $status_counts['booked']; ?></div>
<div>In-Progress: <?= $status_counts['in-progress']; ?></div>
<div>Delivered: <?= $status_counts['delivered']; ?></div>
</div>

<h3>Actions</h3>
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
