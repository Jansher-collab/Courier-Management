<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

// Ensure agent is logged in
if(!isset($_SESSION['agent_id'])){
    die("<p style='color:red;'>You must be logged in as an agent.</p>");
}

$agent_user_id = $_SESSION['user_id'] ?? null;

// Fetch agent_id and branch
$stmt = $conn->prepare("SELECT agent_id, branch FROM agents WHERE user_id = ?");
$stmt->bind_param("i", $agent_user_id);
$stmt->execute();
$agent_data = $stmt->get_result()->fetch_assoc();

if(!$agent_data){
    die("<p style='color:red;'>Agent not found.</p>");
}

$agent_id = $agent_data['agent_id'];
$branch = $agent_data['branch'];

// Fetch couriers for this agent
$query = "
    SELECT c.*, 
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id = ?
    ORDER BY c.courier_id DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Couriers - Agent Panel</title>
<style>
/* RESET */
* {margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif;}

/* HIDE SCROLLBAR BUT ALLOW SCROLL */
html, body { overflow:hidden; }
.scroll-wrapper { height:100vh; overflow:auto; -ms-overflow-style: none; scrollbar-width: none; }
.scroll-wrapper::-webkit-scrollbar { display:none; }

/* BACKGROUND */
body {
    background: url('../assets/agent-view-couriers.jpg') center/cover no-repeat fixed;
    position: relative;
    padding-bottom:50px;
}
body::after {
    content:''; position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35); z-index:-1;
}

/* NAVBAR */
.navbar {
    display:flex; justify-content:space-between; align-items:center; padding:15px 30px;
}
.logo {
    font-size:1.5rem; font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
}
.logout {
    color:white; text-decoration:none; padding:12px 25px; border-radius:10px;
    font-weight:bold; background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* CONTAINER */
.container {
    max-width:1200px; margin:50px auto;
    background:#fff; padding:25px; border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
    overflow-x:auto;
}

/* HEADING */
h2 {text-align:center; margin-bottom:30px; color:#ff7e5f; font-size:1.8rem;}

/* TABLE */
table {
    width:100%; border-collapse: separate; border-spacing:0;
    background:#fff; color:#000; border-radius:12px;
    overflow:hidden; box-shadow:0 5px 20px rgba(0,0,0,0.15);
    table-layout:auto; word-wrap:break-word;
}

th, td {padding:14px 20px; text-align:left; vertical-align:top;}
th {
    background: linear-gradient(135deg,#ff7e5f,#feb47b);
    color:#fff; font-weight:600;
}
td {background:#fff; border-bottom:1px solid #eee;}

/* ACTION LINK */
a.action-link {color:#ff7e5f; font-weight:bold; text-decoration:none;}
a.action-link:hover {color:#feb47b;}

/* NO DATA ROW */
.no-data {text-align:center; padding:20px; font-style:italic; color:#555; background:#f8f8f8; border-radius:8px;}

/* RESPONSIVE */
@media(max-width:1024px){
    th, td{padding:12px 15px; font-size:0.95rem;}
}
@media(max-width:768px){
    .container{margin:40px 15px; padding:20px;}
    th, td{padding:10px 12px;}
}
@media(max-width:480px){
    .container{margin:20px 10px; padding:15px;}
    table {font-size:0.85rem;}
    th, td {padding:8px 10px;}
}
</style>
</head>
<body>
<div class="scroll-wrapper">

<div class="navbar">
    <div class="logo">Courier Agent</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2>View Couriers (Branch: <?= htmlspecialchars($branch) ?>)</h2>

<table>
<thead>
<tr>
<th>ID</th>
<th>Sender</th>
<th>Receiver</th>
<th>Status</th>
<th>Delivery Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['courier_id'] ?></td>
        <td><?= htmlspecialchars($row['sender_name']) ?> (<?= htmlspecialchars($row['sender_email']) ?>)</td>
        <td><?= htmlspecialchars($row['receiver_name']) ?> (<?= htmlspecialchars($row['receiver_email']) ?>)</td>
        <td><?= ucfirst($row['status']) ?></td>
        <td><?= $row['delivery_date'] ?></td>
        <td>
            <a class="action-link" href="update_courier.php?id=<?= $row['courier_id'] ?>">Edit</a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="no-data">No couriers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>
