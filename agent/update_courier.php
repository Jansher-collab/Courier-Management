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
$branch = $agent_data['branch'];

if(!isset($_GET['id']) || empty($_GET['id'])){
    die("<p style='color:red;'>Invalid courier ID.</p>");
}

$courier_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT c.*, 
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.courier_id = ? AND c.agent_id = ?
");
$stmt->bind_param("ii", $courier_id, $agent_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    die("<p style='color:red;'>Courier not found or not assigned to you.</p>");
}

$courier = $result->fetch_assoc();

$success = '';
$error = '';

if(isset($_POST['update'])){
    $status = $_POST['status'];
    $delivery_date = $_POST['delivery_date'];

    $stmt_update = $conn->prepare("
        UPDATE couriers SET status = ?, delivery_date = ? 
        WHERE courier_id = ? AND agent_id = ?
    ");
    $stmt_update->bind_param("ssii", $status, $delivery_date, $courier_id, $agent_id);

    if($stmt_update->execute()){

        $stmt_log = $conn->prepare("
            INSERT INTO courier_logs (courier_id, status, message, notified_via) 
            VALUES (?, ?, ?, ?)
        ");
        $message = "Courier updated by agent";
        $via = "email";
        $stmt_log->bind_param("isss", $courier_id, $status, $message, $via);
        $stmt_log->execute();

        $subject = "Courier Update Notification";
        $body = "
            <h3>Courier Update</h3>
            <p><b>Tracking Number:</b> {$courier['tracking_number']}</p>
            <p><b>Status:</b> $status</p>
            <p><b>Delivery Date:</b> $delivery_date</p>
            <p>Thank you,<br>Courier Management Team</p>
        ";

        if(!empty($courier['sender_email'])) send_mail($courier['sender_email'], $subject, $body);
        if(!empty($courier['receiver_email'])) send_mail($courier['receiver_email'], $subject, $body);

        $success = "Courier updated successfully and notifications sent.";

        $stmt->execute();
        $courier = $stmt->get_result()->fetch_assoc();

    } else {
        $error = "Failed to update courier. Please try again.";
    }
}

// Define statuses for custom dropdown
$statuses = ['booked','in-progress','delivered'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Courier - Agent Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{height:100%; width:100%; overflow:hidden;}
.scroll-wrapper{height:100%; overflow:auto; -ms-overflow-style:none; scrollbar-width:none;}
.scroll-wrapper::-webkit-scrollbar{display:none;}
body{
    background:url('../assets/update-courier.jpg') center/cover no-repeat fixed;
    position:relative;
}
body::after{
    content:'';
    position:fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35); z-index:-1;
}
.navbar{
    width:100%; display:flex; justify-content:space-between; align-items:center;
    padding:15px 30px;
}
.navbar .logo{
    font-size:1.5rem; font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
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
.container{
    width:90%; 
    max-width:600px;
    margin:40px auto 80px auto;
    background: #fff;
    backdrop-filter: blur(15px);
    border-radius:20px;
    padding:30px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    color: #ff7e5f; /* Orange text like logout button */
}

h2{text-align:center;margin-bottom:20px;}
label{display:block;margin:10px 0 5px;}
input, button{width:100%;padding:14px;border-radius:10px;border:none;margin-bottom:15px;font-size:1rem;}
input{background:rgba(255,255,255,0.95);color:#000;}
input:focus{box-shadow:0 0 10px rgba(255,126,95,0.6);}
button{cursor:pointer;background:linear-gradient(135deg,#ff7e5f,#feb47b);color:white;font-weight:bold;}
button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}
p.success{ color:#d4ffd4; text-align:center; margin-bottom:15px; }
p.error{ color:#ffd4d4; text-align:center; margin-bottom:15px; }

/* --- CUSTOM DROPDOWN --- */
.custom-select{position:relative;width:100%;margin-bottom:15px;}
.select-selected{
    background:rgba(255,255,255,0.95);
    padding:14px;
    border-radius:10px;
    cursor:pointer;
    user-select:none;
    color:#000;
}
.select-selected:after{content:"\25BC"; float:right;}
.select-items{
    position:absolute;
    background:#fff;
    top:100%; left:0; right:0;
    border-radius:10px;
    max-height:200px; overflow-y:auto;
    display:none; z-index:99;
    box-shadow:0 5px 15px rgba(0,0,0,0.2);
}
.select-items::-webkit-scrollbar{display:none;}
.select-items div{
    padding:12px; cursor:pointer; color:#000;
}
.select-items div:hover{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
}
.custom-select.open .select-items{display:block;}
</style>
</head>
<body>
<div class="scroll-wrapper">
    <div class="navbar">
        <div class="logo">Courier Agent</div>
        <a href="../logout.php" class="logout">Logout</a>
    </div>

    <div class="container">
        <h2>Update Courier</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <?php if($success) echo "<p class='success'>$success</p>"; ?>

        <form method="POST">
            <p><strong>Courier ID:</strong> <?= $courier['courier_id'] ?></p>
            <p><strong>Sender:</strong> <?= htmlspecialchars($courier['sender_name']) ?> (<?= htmlspecialchars($courier['sender_email']) ?>)</p>
            <p><strong>Receiver:</strong> <?= htmlspecialchars($courier['receiver_name']) ?> (<?= htmlspecialchars($courier['receiver_email']) ?>)</p>

            <label>Status:</label>
            <div class="custom-select" id="status-select">
                <div class="select-selected"><?= ucfirst($courier['status']) ?></div>
                <div class="select-items">
                    <?php foreach($statuses as $s): ?>
                        <div data-value="<?= $s ?>"><?= ucfirst($s) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="status" value="<?= $courier['status'] ?>">

            <label>Delivery Date:</label>
            <input type="date" name="delivery_date" value="<?= htmlspecialchars($courier['delivery_date']) ?>" required />

            <button type="submit" name="update">Update Courier</button>
        </form>
    </div>
</div>

<script>
// Custom dropdown functionality
const customSelect = document.getElementById('status-select');
const selected = customSelect.querySelector('.select-selected');
const items = customSelect.querySelectorAll('.select-items div');
const hiddenInput = customSelect.nextElementSibling;

selected.addEventListener('click', () => {
    customSelect.classList.toggle('open');
});

items.forEach(item => {
    item.addEventListener('click', () => {
        selected.textContent = item.textContent;
        hiddenInput.value = item.dataset.value;
        customSelect.classList.remove('open');
    });
});

document.addEventListener('click', (e)=>{
    if(!customSelect.contains(e.target)){
        customSelect.classList.remove('open');
    }
});
</script>
</body>
</html>
