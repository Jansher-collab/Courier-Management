<?php
// admin/dashboard.php
session_start();
include('../includes/db.php');
include('../includes/auth.php');
include('../includes/functions.php');

// Allow only admin
requireAdmin();

// Fetch shipment status counts from database using the function in functions.php
$statusCounts = getAllStatusCounts($conn);

// Ensure all statuses exist and default to 0
$allStatuses = ['booked', 'in-progress', 'delivered'];
foreach ($allStatuses as $status) {
    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
/* --- RESET & GLOBAL --- */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html, body{height:100%; width:100%;}

/* --- BACKGROUND IMAGE WITH DARK OVERLAY --- */
body {
    background: url('../assets/dashboard.jpg') center/cover no-repeat fixed;
    position: relative;
}
body::after {
    content: '';
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35); /* dark overlay for contrast */
    z-index: -1;
}

/* --- NAVBAR --- */
.navbar{
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    z-index: 2;
    position: relative;
}
.navbar .logo{
    font-size:1.5rem;
    font-weight:bold;
     color:#ff7e5f;
}
.navbar a.logout{
    text-decoration:none;
    padding:12px 25px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.4s;
}
.navbar a.logout:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* --- CONTAINER --- */
.container{
    width:90%;
    max-width:1200px;
    margin:40px auto;
    position: relative;
    z-index: 2;
}

/* --- PAGE TITLE --- */
.container h1{
    font-size:clamp(24px,2.5vw,32px);
   color:#ffffff;
    text-align:center;
    margin-bottom:25px;
}

/* --- STATUS CARDS --- */
.cards{
    display:flex;
    flex-wrap:wrap;
    gap:20px;
    justify-content:center;
    margin-bottom:40px;
}
.card{
    flex:0 0 auto;
    padding:25px;
    border-radius:20px;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(15px);
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    text-align:center;
    transition:0.7s ease;
    animation:fadeIn 0.7s ease;
}
.card h3{
    font-size:clamp(16px,2vw,20px);
    margin-bottom:10px;
    color:#ffffff; /* changed from black to white */
}

.card p{
    font-size:clamp(20px,4vw,28px);
    font-weight:bold;
    color:#ffffff; /* changed from black to white */
}
.card:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(0,0,0,0.35);
}

/* --- QUICK LINKS --- */
.links{
    display:flex;
    flex-wrap:wrap;
    gap:15px;
    justify-content:center;
    margin-bottom:50px; /* bottom margin */
}
.links a{
    display:inline-block;
    flex:0 0 auto;
    padding:15px 0;
    width:auto;
    min-width:150px;
    border-radius:10px;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
    font-weight:bold;
    text-decoration:none;
    text-align:center;
    transition:0.4s;
}
.links a:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* --- ANIMATION --- */
@keyframes fadeIn{
    from{opacity:0;transform:translateY(15px);}
    to{opacity:1;transform:translateY(0);}
}

/* --- RESPONSIVE --- */
@media(max-width:992px){
    .cards,.links{justify-content:center;}
}
@media(max-width:600px){
    .cards,.links{
        flex-direction:column;
        gap:15px;
        align-items:center;
    }
    .card,.links a{width:90%;}
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Courier Admin</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></h1>

    <!-- Shipment Status Cards -->
    <div class="cards">
        <div class="card">
            <h3>Booked</h3>
            <p><?= $statusCounts['booked'] ?></p>
        </div>
        <div class="card">
            <h3>In-Progress</h3>
            <p><?= $statusCounts['in-progress'] ?></p>
        </div>
        <div class="card">
            <h3>Delivered</h3>
            <p><?= $statusCounts['delivered'] ?></p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="links">
        <a href="add_courier.php">Add Courier</a>
        <a href="manage_customers.php">Manage Customers</a>
        <a href="create_agent.php">Create Agent</a>
        <a href="approve_agents.php">Approve Agents</a>
        <a href="manage_agents.php">Manage Agents</a>
        <a href="view_couriers.php">View Couriers</a>
        <a href="download_reports.php">Download Reports</a>
        <a href="send_sms.php">Send Email</a>
        <a href="send_delivery_sms.php">Delivery Email</a>
    </div>
</div>

</body>
</html>
