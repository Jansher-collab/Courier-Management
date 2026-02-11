<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php'; // PHPMailer setup

// Ensure agent is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent' || !isset($_SESSION['agent_id'])){
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$message_sent = '';

// Fetch in-progress couriers assigned to this agent
$stmt = $conn->prepare("
    SELECT c.courier_id, r.name AS receiver_name, r.email AS receiver_email, c.to_location
    FROM couriers c
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id = ? AND c.status = 'in-progress'
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();

// Store couriers in array
$couriers = [];
while($row = $result->fetch_assoc()){
    $couriers[] = $row;
}

// Initialize form fields
$selected_courier_id = $_POST['courier_id'] ?? '';
$receiver_name = '';
$receiver_email = '';
$to_location = '';
$body = '';

if(!empty($selected_courier_id)){
    $stmt_c = $conn->prepare("
        SELECT r.name, r.email, c.to_location
        FROM couriers c
        JOIN customers r ON c.receiver_id = r.customer_id
        WHERE c.courier_id = ?
    ");
    $stmt_c->bind_param("i", $selected_courier_id);
    $stmt_c->execute();
    $courier = $stmt_c->get_result()->fetch_assoc();
    if($courier){
        $receiver_name = $courier['name'];
        $receiver_email = $courier['email'];
        $to_location = $courier['to_location'];
        $body = "Hello $receiver_name,\n\nYour courier will be delivered to $to_location today.\n\nThank you,\nCourier Management Team";
    }
}

// Send delivery email if submitted
if(isset($_POST['send_delivery_email'])){
    $courier_id = $_POST['courier_id'] ?? null;
    $to = $_POST['to_email'] ?? '';
    $subject = $_POST['subject'] ?? 'Delivery Notification';
    $body = $_POST['message'] ?? '';

    if(send_mail($to, $subject, $body)){
        $message_sent = "Delivery email sent to $receiver_name ($to)";

        // Log in courier_logs
        if(!empty($courier_id)){
            $stmt_log = $conn->prepare("
                INSERT INTO courier_logs (courier_id, status, message, notified_via)
                VALUES (?, ?, ?, 'email')
            ");
            $status_msg = "Delivery email sent by agent";
            $stmt_log->bind_param("iss", $courier_id, $status_msg, $body);
            $stmt_log->execute();
        }
    } else {
        $message_sent = "Failed to send email. Check mail configuration.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Delivery Notification</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }

/* HIDE SCROLLBAR BUT ALLOW SCROLL */
html, body { height:100%; overflow:hidden; }
.scroll-wrapper { height:100vh; overflow:auto; -ms-overflow-style:none; scrollbar-width:none; }
.scroll-wrapper::-webkit-scrollbar { display:none; }

/* BACKGROUND */
body {
    background: url('../assets/agent-send-delivery-sms.jpg') center/cover no-repeat fixed;
    position: relative;
}
body::after {
    content:''; position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35); z-index:-1;
}

/* NAVBAR */
.navbar {
    display:flex; justify-content:space-between; align-items:center;
    padding:15px 30px; margin-bottom:30px;
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
    max-width:600px; margin:0 auto 50px auto;
    background:#fff; padding:25px; border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

/* HEADING */
h2 {
    text-align:center; margin-bottom:25px; font-size:1.8rem;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
}

/* LABELS & INPUTS */
label { display:block; margin:10px 0 5px; font-weight:600; color:#333; }
input, textarea {
    width:100%; padding:12px; margin-bottom:15px;
    border-radius:10px; border:1px solid #ccc; font-size:1rem;
    transition: all 0.3s ease-in-out;
}
input:focus, textarea:focus {
    outline:none;
    box-shadow: 0 0 10px 2px rgba(255,126,95,0.6);
    border-color: #ff7e5f;
}
textarea { resize:none; }

/* CUSTOM DROPDOWN */
.custom-select { position: relative; width: 100%; margin-bottom: 15px; }
.select-selected {
    background:#fff; border:1px solid #ccc; border-radius:10px;
    padding:12px; cursor:pointer; user-select:none; position:relative;
    transition: all 0.3s ease-in-out;
}
.select-selected.active {
    box-shadow: 0 0 10px 2px rgba(255,126,95,0.6);
    border-color: #ff7e5f;
}
.select-selected:after { content:"\25BC"; position:absolute; right:12px; top:50%; transform:translateY(-50%); }
.select-items {
    position: absolute; background:#fff; top:100%; left:0; right:0;
    border:1px solid #ccc; border-radius:10px; z-index:99; display:none;
    max-height:200px; overflow-y:auto;
}
.select-items div {
    padding:10px; cursor:pointer;
    border-bottom:1px solid #eee;
}
.select-items div:hover {
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:white;
}

/* BUTTON */
button {
    width:100%; padding:14px; border:none; border-radius:10px;
    font-weight:bold; color:white; cursor:pointer;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.3s;
}
button:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.25); }

/* MESSAGE */
p.message { text-align:center; font-weight:bold; color:#28a745; margin-bottom:15px; }

/* RESPONSIVE */
@media(max-width:600px){
    .container { margin:20px 15px; padding:20px; }
    input, textarea, button { font-size:0.95rem; padding:10px; }
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
<h2>Send Delivery Notification Email</h2>

<?php if($message_sent) echo "<p class='message'>" . htmlspecialchars($message_sent) . "</p>"; ?>

<form method="POST">
    <label>Select Courier (In-Progress):</label>
    <div class="custom-select" id="courier-select">
        <div class="select-selected"><?= $selected_courier_id ? "Courier ID: $selected_courier_id" : "--Select Courier--" ?></div>
        <div class="select-items">
            <?php if(!empty($couriers)): ?>
                <?php foreach($couriers as $row): ?>
                <div data-value="<?= $row['courier_id'] ?>" data-email="<?= htmlspecialchars($row['receiver_email']) ?>">
                    <?= "Courier ID: {$row['courier_id']} - " . htmlspecialchars($row['receiver_name']) . " to {$row['to_location']}" ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="cursor:default; color:#999;">No in-progress couriers</div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="courier_id" value="<?= htmlspecialchars($selected_courier_id) ?>">
        <input type="hidden" name="to_email" value="<?= htmlspecialchars($receiver_email) ?>">
    </div>

    <?php if($selected_courier_id && $receiver_email): ?>
        <label>To (Email Address):</label>
        <input type="email" name="to_email" value="<?= htmlspecialchars($receiver_email) ?>" required>

        <label>Subject:</label>
        <input type="text" name="subject" value="Delivery Notification" required>

        <label>Message:</label>
        <textarea name="message" rows="6" required><?= htmlspecialchars($body) ?></textarea>

        <button type="submit" name="send_delivery_email">Send Delivery Email</button>
    <?php endif; ?>
</form>
</div>
</div>

<script>
const selected = document.querySelector('.select-selected');
const items = document.querySelector('.select-items');
const courierInput = document.querySelector('input[name="courier_id"]');
const emailInput = document.querySelector('input[name="to_email"]');

selected.addEventListener('click', () => {
    items.style.display = items.style.display === 'block' ? 'none' : 'block';
    selected.classList.toggle('active'); // glow effect
});

document.querySelectorAll('.select-items div').forEach(div => {
    div.addEventListener('click', () => {
        selected.textContent = div.textContent;
        courierInput.value = div.dataset.value;
        emailInput.value = div.dataset.email;
        items.style.display = 'none';
        selected.classList.remove('active'); // remove glow
    });
});

document.addEventListener('click', e => {
    if(!selected.contains(e.target) && !items.contains(e.target)){
        items.style.display='none';
        selected.classList.remove('active');
    }
});
</script>

</body>
</html>
