<?php 
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php'; // PHPMailer setup

// Ensure agent is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent'){
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$message_sent = '';

// Fetch all couriers assigned to this agent
$stmt = $conn->prepare("
    SELECT c.courier_id, r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id = ?
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$couriers_result = $stmt->get_result();

// Store results in an array
$couriers = [];
while($row = $couriers_result->fetch_assoc()){
    $couriers[] = $row;
}

// Initialize form fields safely
$selected_courier_id = $_POST['courier_id'] ?? '';
$to_email = $_POST['to_email'] ?? '';
$subject = $_POST['subject'] ?? 'Courier Notification';
$body = $_POST['message'] ?? 'Type your message here';

// Fetch courier data if selected
if(!empty($selected_courier_id)){
    $stmt_c = $conn->prepare("
        SELECT r.name, r.email 
        FROM couriers c 
        JOIN customers r ON c.receiver_id = r.customer_id 
        WHERE c.courier_id = ?
    ");
    $stmt_c->bind_param("i", $selected_courier_id);
    $stmt_c->execute();
    $courier_data = $stmt_c->get_result()->fetch_assoc();
    if($courier_data){
        $to_email = $courier_data['email'];
        $receiver_name = $courier_data['name'];
    }
}

// Send email if form submitted
if(isset($_POST['send_email'])){
    $courier_id = $_POST['courier_id'] ?? null;
    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $body = strip_tags($_POST['message']); // remove HTML tags for plain text email

    if(send_mail($to, $subject, $body)){
        $message_sent = "Email sent successfully to $to";

        if(!empty($courier_id)){
            $stmt_log = $conn->prepare("
                INSERT INTO courier_logs (courier_id, status, message, notified_via) 
                VALUES (?, ?, ?, 'email')
            ");
            $status_msg = "Custom email sent by agent";
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
<title>Send Email to Customer</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }

/* HIDE SCROLLBAR */
html, body { height:100%; overflow:hidden; }
.scroll-wrapper { height:100vh; overflow:auto; -ms-overflow-style:none; scrollbar-width:none; }
.scroll-wrapper::-webkit-scrollbar { display:none; }

/* BACKGROUND */
body {
    background: url('../assets/agent-send-sms.jpg') center/cover no-repeat fixed;
    position: relative;
}
body::after {
    content:''; position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.35); z-index:-1;
}

/* NAVBAR */
.navbar { display:flex; justify-content:flex-end; align-items:center; padding:15px 30px; margin-bottom:30px; gap:10px; }
.logo {
    font-size:1.5rem; font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    position:absolute; left:30px;
}
.logout {
    color:white; text-decoration:none; padding:12px 25px; border-radius:10px;
    font-weight:bold; background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.3s;
}
.logout:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.25); }

.dashboard-btn {
    color:white; text-decoration:none; padding:12px 25px; border-radius:10px;
    font-weight:bold; background:linear-gradient(135deg,#fddb6d,#fcb045);
    transition:0.3s;
}
.dashboard-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.25); }

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
    padding:10px; cursor:pointer; border-bottom:1px solid #eee;
}
.select-items div:hover {
    background:linear-gradient(135deg,#ff7e5f,#feb47b); color:white;
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
    <a href="dashboard.php" class="dashboard-btn">Dashboard</a>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2>Send Email to Customer</h2>

<?php if($message_sent) echo "<p class='message'>" . htmlspecialchars($message_sent) . "</p>"; ?>

<form method="POST">
    <label>Select Courier (optional):</label>
    <div class="custom-select">
        <div class="select-selected"><?= $selected_courier_id ? "Courier ID: $selected_courier_id" : "--Select Courier--" ?></div>
        <div class="select-items">
            <?php if(!empty($couriers)): ?>
                <?php foreach($couriers as $row): ?>
                <div 
                    data-value="<?= $row['courier_id'] ?>" 
                    data-email="<?= htmlspecialchars($row['receiver_email']) ?>"
                >
                    <?= "ID: {$row['courier_id']} | Name: " . htmlspecialchars($row['receiver_name']) . " | Email: " . htmlspecialchars($row['receiver_email']) ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="cursor:default; color:#999;">No couriers available</div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="courier_id" value="<?= htmlspecialchars($selected_courier_id) ?>">
    </div>

    <label>To (Email):</label>
    <input type="email" name="to_email" value="<?= htmlspecialchars($to_email) ?>" required>

    <label>Subject:</label>
    <input type="text" name="subject" value="<?= htmlspecialchars($subject) ?>" required>

    <label>Message:</label>
    <textarea name="message" rows="6" required><?= htmlspecialchars($body) ?></textarea>

    <button type="submit" name="send_email">Send Email</button>
</form>
</div>

</div>

<script>
// Dropdown functionality
const selected = document.querySelector('.select-selected');
const items = document.querySelector('.select-items');
const courierInput = document.querySelector('input[name="courier_id"]');
const emailInput = document.querySelector('input[name="to_email"]');

selected.addEventListener('click', () => {
    items.style.display = items.style.display === 'block' ? 'none' : 'block';
    selected.classList.toggle('active');
});

document.querySelectorAll('.select-items div').forEach(div => {
    div.addEventListener('click', () => {
        selected.textContent = div.textContent;
        courierInput.value = div.getAttribute('data-value');
        emailInput.value = div.getAttribute('data-email');
        items.style.display = 'none';
        selected.classList.remove('active');
    });
});

document.addEventListener('click', (e) => {
    if(!selected.contains(e.target) && !items.contains(e.target)){
        items.style.display='none';
        selected.classList.remove('active');
    }
});
</script>

</body>
</html>
