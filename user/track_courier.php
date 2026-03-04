<?php 
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$courier_info = null;
$logs = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $tracking_number = trim($_POST['tracking_number']);

    if (empty($tracking_number)) {
        $error = "Please enter a tracking number.";
    } else {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   s.name AS sender_name, s.email AS sender_email,
                   r.name AS receiver_name, r.email AS receiver_email
            FROM couriers c
            JOIN customers s ON c.sender_id = s.customer_id
            JOIN customers r ON c.receiver_id = r.customer_id
            WHERE c.tracking_number = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $tracking_number);
        $stmt->execute();
        $courier = $stmt->get_result()->fetch_assoc();

        if ($courier) {
            $courier_info = $courier;
            $stmt_log = $conn->prepare("
                SELECT * FROM courier_logs 
                WHERE courier_id = ? 
                ORDER BY log_time ASC
            ");
            $stmt_log->bind_param("i", $courier['courier_id']);
            $stmt_log->execute();
            $logs = $stmt_log->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "No courier found with this tracking number.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Courier</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background: url('../assets/user-track-courier.jpg') center/cover no-repeat fixed;
    min-height:100vh;
    padding:15px;
}

/* Navbar */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    background:rgba(255,255,255,0.85);
    backdrop-filter:blur(10px);
    padding:12px 25px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    gap:10px;
}
.logo{
    font-size:clamp(18px,2vw,24px);
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.nav-buttons{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.logout, .dashboard-btn{
    color:white;
    text-decoration:none;
    font-weight:bold;
    padding:8px 16px;
    border-radius:10px;
    transition:0.3s;
    white-space:nowrap;
}
.logout{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
}
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

/* Hero Section */
.hero{
    max-width:900px;
    margin:20px auto;
    text-align:center;
    background:rgba(255,255,255,0.85);
    backdrop-filter:blur(10px);
    padding:25px 20px;
    border-radius:15px;
}
.hero h1{
    font-size:clamp(24px,2.5vw,36px);
    margin-bottom:12px;
    color:#333;
}
.hero p{
    font-size:clamp(14px,1vw,16px);
    line-height:1.5;
    color:#333;
}

/* Track Form */
.track-form{
    max-width:500px;
    margin:20px auto;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:center;
}
.track-form input[type=text]{
    padding:12px;
    border-radius:12px;
    border:1px solid #ccc;
    font-size:1rem;
    outline:none;
    transition:0.3s;
    flex:1 1 200px;
    max-width:300px;
}
.track-form input:focus{
    border-color:#ff7e5f;
    box-shadow:0 0 10px rgba(255,126,95,0.6);
}
.track-form button{
    padding:12px 20px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}
.track-form button:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.6),0 0 30px rgba(255,126,95,0.4);
}

/* Message */
.message{text-align:center;margin-bottom:15px;font-weight:bold;}
.error{color:#e74c3c;}
.success{color:#27ae60;}

/* Courier Cards Container */
.courier-cards-container{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:20px;
    max-width:1200px;
    margin:20px auto;
}

/* Individual Courier Card */
.courier-card{
    flex:1 1 300px;
    min-width:280px;
    max-width:380px;
    background:rgba(255,255,255,0.92);
    backdrop-filter:blur(10px);
    padding:20px;
    border-radius:18px;
    box-shadow:0 12px 28px rgba(0,0,0,0.1);
    transition:0.35s;
}
.courier-card:hover{
    box-shadow:0 0 20px rgba(255,126,95,0.5),0 0 40px rgba(255,126,95,0.3);
    transform:translateY(-2px);
}
.courier-card h3{margin-bottom:15px;color:#ff7e5f;}
.courier-card p{margin-bottom:8px;color:#333;}
.action-links{margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;}
.action-links a{
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    color:white;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
}
.action-links a:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.6),0 0 30px rgba(255,126,95,0.4);
}

/* Logs Table */
.logs-table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
    font-size:0.9rem;
}
.logs-table th, .logs-table td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:left;
}
.logs-table th{
    background:#ff7e5f;
    color:white;
}
.logs-table tr:hover{background:rgba(255,126,95,0.1);transition:0.3s;}

/* Responsive */
@media(max-width:1024px){
    .navbar{gap:8px; padding:12px 20px;}
    .track-form{flex-direction:column; align-items:center;}
    .track-form input[type=text], .track-form button{width:100%; max-width:95%;}
    .hero{padding:20px 15px; margin:15px auto;}
    .courier-card{max-width:340px;}
}
@media(max-width:768px){
    .navbar{justify-content:space-between;}
    .nav-buttons{gap:6px;}
    .dashboard-btn, .logout{padding:8px 14px; font-size:0.85rem;}
    .courier-cards-container{gap:15px;}
}
@media(max-width:480px){
    .navbar{flex-direction:column; align-items:center; gap:10px;}
    .nav-buttons{width:100%; justify-content:center;}
    .dashboard-btn, .logout{padding:6px 12px; font-size:0.8rem;}
    .hero h1{font-size:22px;}
    .hero p{font-size:14px;}
    .hero{margin:10px; padding:15px;}
    .track-form input[type=text]{flex:1 1 90%; max-width:95%; font-size:0.9rem;}
    .track-form button{width:100%; max-width:95%; font-size:0.9rem;}
    .courier-card{padding:15px; min-width:100%;}
    .logs-table th, .logs-table td{font-size:0.8rem;}
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

<div class="hero">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h1>
    <p>Track your shipments easily and stay updated with real-time courier status and logs. Enter your tracking number below to begin.</p>
</div>

<form method="POST" class="track-form">
    <input type="text" name="tracking_number" placeholder="Enter your tracking number" required value="<?= htmlspecialchars($_POST['tracking_number'] ?? '') ?>">
    <button type="submit">Track</button>
</form>

<?php if ($error): ?>
    <p class="message error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($courier_info): ?>
<div class="courier-cards-container">
    <div class="courier-card">
        <h3>Courier Details</h3>
        <p><b>Tracking Number:</b> <?= htmlspecialchars($courier_info['tracking_number']) ?></p>
        <p><b>From:</b> <?= htmlspecialchars($courier_info['from_location']) ?></p>
        <p><b>To:</b> <?= htmlspecialchars($courier_info['to_location']) ?></p>
        <p><b>Courier Type:</b> <?= htmlspecialchars($courier_info['courier_type']) ?></p>
        <p><b>Status:</b> <?= htmlspecialchars($courier_info['status']) ?></p>
        <p><b>Delivery Date:</b> <?= htmlspecialchars($courier_info['delivery_date']) ?></p>
        <p><b>Sender:</b> <?= htmlspecialchars($courier_info['sender_name']) ?> (<?= htmlspecialchars($courier_info['sender_email']) ?>)</p>
        <p><b>Receiver:</b> <?= htmlspecialchars($courier_info['receiver_name']) ?> (<?= htmlspecialchars($courier_info['receiver_email']) ?>)</p>

        <div class="action-links">
            <a href="view_status.php?courier_id=<?= $courier_info['courier_id'] ?>">View Status</a>
            <a href="print_status.php?courier_id=<?= $courier_info['courier_id'] ?>" target="_blank">Print Status</a>
        </div>

        <?php if ($logs): ?>
            <h3>Courier Logs</h3>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Notified Via</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['status']) ?></td>
                        <td><?= htmlspecialchars($log['message']) ?></td>
                        <td><?= htmlspecialchars($log['notified_via']) ?></td>
                        <td><?= htmlspecialchars($log['log_time']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>