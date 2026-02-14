<?php
session_start();
include('../includes/db.php');

// --- Session check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'] ?? 'User';

// Validate courier_id
$courier_id = isset($_GET['courier_id']) ? intval($_GET['courier_id']) : 0;
if($courier_id <= 0){
    $error = "Invalid courier ID.";
} else {
    // Fetch courier details
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
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background: url('../assets/user-view-status.jpg') center/cover no-repeat fixed;
    padding:20px;
    min-height:100vh;
}

/* NAVBAR */
.navbar{
    display:flex;justify-content:space-between;align-items:center;
    background:white;padding:15px 25px;box-shadow:0 4px 12px rgba(0,0,0,0.08);
    border-radius:12px;flex-wrap:wrap;margin-bottom:30px;
}
.logo{
    font-size:1.4rem;font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.nav-buttons{display:flex; gap:10px;}
.logout, .dashboard-btn{
    color:white;text-decoration:none;font-weight:bold;padding:10px 20px;border-radius:10px;transition:0.3s;
}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}
.logout:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.7),0 0 25px rgba(255,126,95,0.5);
}
.dashboard-btn{
    background:linear-gradient(135deg,#fddb6d,#fcb045);
}
.dashboard-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,200,90,0.7),0 0 25px rgba(255,180,70,0.5);
}

/* CONTAINER */
.container{
    max-width:800px;margin:0 auto;background:rgba(255,255,255,0.95);
    padding:25px;border-radius:18px;box-shadow:0 12px 28px rgba(0,0,0,0.1);
}

/* HEADINGS */
h2{font-size:clamp(22px,2.5vw,28px);color:#ff7e5f;margin-bottom:20px;text-align:center;}
p{margin-bottom:12px;font-size:1rem;color:#333;line-height:1.5;}
a.print-btn{
    display:inline-block;margin-top:15px;padding:10px 20px;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;border-radius:10px;text-decoration:none;font-weight:bold;transition:0.3s;
}
a.print-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.6),0 0 30px rgba(255,126,95,0.4);
}

/* ERROR MESSAGE */
.error-msg{text-align:center;color:#e74c3c;font-weight:bold;margin-bottom:15px;}

/* RESPONSIVE */
@media(max-width:600px){
    .container{padding:18px;}
    h2{font-size:22px;}
    p{font-size:0.95rem;}
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Courier Portal</div>
    <div class="nav-buttons">
        <a href="dashboard.php" class="dashboard-btn">Dashboard</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Courier Details</h2>

    <?php if(isset($error)): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php elseif($data): ?>
        <p><b>Sender:</b> <?= htmlspecialchars($data['sender']) ?> (<?= htmlspecialchars($data['sender_email']) ?>)</p>
        <p><b>Receiver:</b> <?= htmlspecialchars($data['receiver']) ?> (<?= htmlspecialchars($data['receiver_email']) ?>)</p>
        <p><b>From:</b> <?= htmlspecialchars($data['from_location']) ?></p>
        <p><b>To:</b> <?= htmlspecialchars($data['to_location']) ?></p>
        <p><b>Status:</b> <span style="color:black;font-weight:bold;"><?= htmlspecialchars($data['status']) ?></span></p>
        <p><b>Delivery Date:</b> <?= htmlspecialchars($data['delivery_date']) ?></p>

        <a href="print_status.php?courier_id=<?= $courier_id ?>" target="_blank" class="print-btn">Print Status</a>
    <?php else: ?>
        <p class="error-msg">Courier not found.</p>
    <?php endif; ?>
</div>

</body>
</html>
