<?php
session_start();
include('../includes/db.php');

// --- Session check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Validate courier_id
$courier_id = isset($_GET['courier_id']) ? intval($_GET['courier_id']) : 0;

// Fetch courier details only if courier_id is valid
$data = null;
if($courier_id > 0){
    $stmt = $conn->prepare("
        SELECT c.*, 
               s.name AS sender, s.email AS sender_email, 
               r.name AS receiver, r.email AS receiver_email
        FROM couriers c
        JOIN customers s ON c.sender_id = s.customer_id
        JOIN customers r ON c.receiver_id = r.customer_id
        WHERE c.courier_id = ?
    ");
    $stmt->bind_param("i", $courier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courier Status</title>
<style>
/* RESET & GLOBAL */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f4f7fb;color:#333;padding:0;margin:0;}
a{text-decoration:none;}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 25px;
    background:white;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    flex-wrap:wrap;
}
.navbar .logo{
    font-size:1.4rem;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.navbar .logout{
    padding:10px 20px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.3s;
}
.navbar .logout:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.7),0 0 25px rgba(255,126,95,0.5);
}

/* CONTAINER */
.container{
    max-width:750px;
    margin:30px auto;
    background:white;
    padding:25px 30px;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
    text-align:center;
}

/* HEADINGS & TEXT */
h2{font-size:26px;color:#ff7e5f;margin-bottom:25px;}
p{font-size:16px;line-height:1.6;margin-bottom:12px;}
label{font-weight:bold;color:#555;}

/* STATUS BADGES */
.status{
    display:inline-block;
    padding:5px 12px;
    border-radius:8px;
    color:white;
    font-weight:bold;
}
.status-delivered{background:#27ae60;}
.status-in-progress{background:#f39c12;}
.status-pending{background:#e74c3c;}

/* NO DATA STATE */
.no-data{
    margin-top:30px;
    color:#555;
}
.no-data img{
    max-width:250px;
    width:80%;
    opacity:0.8;
    margin-bottom:20px;
    border-radius:12px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}
.no-data p{margin-bottom:12px;font-size:16px;}

/* PRINT BUTTON */
.print-btn{
    display:inline-block;
    margin-top:20px;
    padding:12px 25px;
    border:none;
    border-radius:10px;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
    cursor:pointer;
    transition:0.3s;
}
.print-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.6), 0 0 25px rgba(255,126,95,0.4);
}

/* RESPONSIVE */
@media(max-width:600px){
    .container{padding:20px 15px;margin:20px 10px;}
    h2{font-size:22px;}
    p, .no-data p{font-size:14px;}
    .no-data img{max-width:200px;}
}
@media print{
    .navbar{display:none;}
    .container{box-shadow:none;border:none;padding:0;}
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Courier Portal</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
    <h2>Courier Status</h2>

    <?php if($data): ?>
        <p><label>Sender:</label> <?= htmlspecialchars($data['sender']) ?> (<?= htmlspecialchars($data['sender_email']) ?>)</p>
        <p><label>Receiver:</label> <?= htmlspecialchars($data['receiver']) ?> (<?= htmlspecialchars($data['receiver_email']) ?>)</p>
        <p><label>From:</label> <?= htmlspecialchars($data['from_location']) ?></p>
        <p><label>To:</label> <?= htmlspecialchars($data['to_location']) ?></p>
        <p><label>Status:</label> 
            <?php 
            $statusClass = strtolower(str_replace(' ', '-', $data['status']));
            echo "<span class='status status-{$statusClass}'>" . htmlspecialchars($data['status']) . "</span>";
            ?>
        </p>
        <p><label>Delivery Date:</label> <?= htmlspecialchars($data['delivery_date']) ?></p>
        <button class="print-btn" onclick="window.print()">Print This Page</button>
    <?php else: ?>
        <div class="no-data">
            <img src="../assets/print-status.jpg" alt="No Data">
            <p>No courier found yet. Enter a valid tracking number or check back later.</p>
            <p><a href="track_courier.php" style="color:#ff7e5f;font-weight:bold;">Track Another Courier</a></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
