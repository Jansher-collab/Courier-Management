<?php 
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent' || !isset($_SESSION['agent_id'])){
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];
$message_sent = '';

// Fetch only Delivered couriers
$stmt = $conn->prepare("
    SELECT c.courier_id, r.name AS receiver_name, r.email AS receiver_email, 
           c.to_location, c.status
    FROM couriers c
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id = ? AND c.status = 'Delivered'
    ORDER BY c.courier_id DESC
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();

$couriers = [];
while($row = $result->fetch_assoc()){
    $couriers[] = $row;
}

$selected_courier_id = $_POST['courier_id'] ?? '';
$receiver_name = '';
$receiver_email = '';
$to_location = '';
$body = '';
$status = '';

if(!empty($selected_courier_id)){
    $stmt_c = $conn->prepare("
        SELECT r.name, r.email, c.to_location, c.status
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
        $status = $courier['status'];
        $body = "Hello $receiver_name,\n\nYour courier (Status: $status) has been delivered to $to_location.\n\nThank you,\nCourier Management Team";
    }
}

if(isset($_POST['send_delivery_email'])){
    $courier_id = $_POST['courier_id'] ?? null;
    $to = $_POST['to_email'] ?? '';
    $subject = $_POST['subject'] ?? 'Delivery Notification';
    $body = strip_tags($_POST['message']);

    if(send_mail($to, $subject, $body)){
        $message_sent = "Delivery email sent to $receiver_name ($to)";

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
<title>Send Delivery Email</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{height:100%;overflow:hidden;}
.scroll-wrapper{height:100vh;overflow:auto;-ms-overflow-style:none;scrollbar-width:none;}
.scroll-wrapper::-webkit-scrollbar{display:none;}
body{
    background:url('../assets/agent-send-delivery-sms.jpg') center/cover no-repeat fixed;
    position:relative;
}

/* Navbar */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    flex-wrap: wrap;
    width:100%;
}
.logo{
    font-size:1.5rem;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.navbar-buttons{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.dashboard-btn,.logout{
    color:white;
    text-decoration:none;
    padding:10px 20px;
    border-radius:10px;
    font-weight:bold;
    white-space:nowrap;
    transition:0.4s;
}
.dashboard-btn{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}
.dashboard-btn:hover,.logout:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}

/* Mobile Navbar */
@media(max-width:768px){
.navbar{flex-direction:column;align-items:center;gap:15px;}
.navbar-buttons{justify-content:center;width:100%;}
.dashboard-btn,.logout{padding:8px 15px;font-size:0.9rem;}
}

/* Container & Form */
.container{
    max-width:600px;
    margin:30px auto 50px auto; /* reduced top margin */
    padding:25px;
    background:rgba(255,255,255,0.9);
    border-radius:20px;
    color:#000;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}
h2{text-align:center;margin-bottom:25px;color:#ff7e5f;}
p.message{text-align:center;font-weight:bold;color:#28a745;margin-bottom:15px;}
label{display:block;margin:10px 0 5px;font-weight:600;}
input,textarea,button{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
    margin-bottom:12px;
    font-size:1rem;
    transition:0.3s;
}
input,textarea{background:#fff;color:#000;}
input:hover, textarea:hover,
input:focus, textarea:focus{box-shadow:0 0 10px 2px rgba(255,126,95,0.6);outline:none;}
textarea{resize:none;}
button{background:linear-gradient(135deg,#ff7e5f,#feb47b);color:#fff;font-weight:bold;cursor:pointer;}
button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}

/* Modal */
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);}
.modal-content{background:#fff;margin:50px auto;padding:20px;border-radius:20px;width:90%;max-width:500px;max-height:70vh;overflow:auto;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.close-btn{cursor:pointer;font-size:1.5rem;font-weight:bold;}
#modal-search{width:100%;padding:10px;margin-bottom:15px;border-radius:10px;border:1px solid #ccc;}
.courier-item{padding:10px;border-bottom:1px solid #eee;cursor:pointer;transition:0.3s;}
.courier-item:hover{background:linear-gradient(135deg,#ff7e5f,#feb47b);color:#fff;}

/* Responsive */
@media(max-width:768px){
    .container{margin:40px 15px 50px 15px;padding:20px;}
}
@media(max-width:480px){
    .logo{font-size:1.2rem;}
    .dashboard-btn,.logout{padding:6px 10px;font-size:0.85rem;}
    .container{margin:30px 10px 50px 10px;padding:15px;}
}
</style>
</head>
<body>
<div class="scroll-wrapper">

<div class="navbar">
    <div class="logo">Courier Agent</div>
    <div class="navbar-buttons">
        <a href="dashboard.php" class="dashboard-btn">Dashboard</a>
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
<h2>Send Delivery Email</h2>

<?php if($message_sent) echo "<p class='message'>".htmlspecialchars($message_sent)."</p>"; ?>

<form method="POST">
    <label>Select Delivered Courier:</label>
    <input type="text" id="open-modal" readonly placeholder="Click to select courier" value="<?= $selected_courier_id ? "ID: $selected_courier_id | $receiver_name" : '' ?>">

    <label>To (Email Address):</label>
    <input type="email" name="to_email" value="<?= htmlspecialchars($receiver_email) ?>" required>

    <label>Subject:</label>
    <input type="text" name="subject" value="Delivery Notification" required>

    <label>Message:</label>
    <textarea name="message" rows="5" required><?= htmlspecialchars($body) ?></textarea>

    <button type="submit" name="send_delivery_email">Send Delivery Email</button>

    <input type="hidden" name="courier_id" id="courier_id_hidden" value="<?= htmlspecialchars($selected_courier_id) ?>">
</form>
</div>

<div id="courier-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Select Delivered Courier</h3>
            <span class="close-btn" id="modal-close">&times;</span>
        </div>

        <input type="text" id="modal-search" placeholder="Search by ID, Name, Email, Location">

        <div id="modal-list">
            <?php foreach($couriers as $c): ?>
            <div class="courier-item" data-id="<?= $c['courier_id'] ?>" data-email="<?= htmlspecialchars($c['receiver_email']) ?>" data-name="<?= htmlspecialchars($c['receiver_name']) ?>" data-location="<?= htmlspecialchars($c['to_location']) ?>" data-status="<?= htmlspecialchars($c['status']) ?>">
                <?= "ID: {$c['courier_id']} | " . htmlspecialchars($c['receiver_name']) . " | To: {$c['to_location']}" ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('courier-modal');
const openModal = document.getElementById('open-modal');
const closeModal = document.getElementById('modal-close');
const courierIdHidden = document.getElementById('courier_id_hidden');
const modalSearch = document.getElementById('modal-search');
const toEmailInput = document.querySelector('input[name="to_email"]');
const messageTextarea = document.querySelector('textarea[name="message"]');

// Show modal
openModal.addEventListener('click', () => { 
    modal.style.display = 'block'; 
    modalSearch.focus(); 
});

// Close modal
closeModal.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', e => { if(e.target == modal) modal.style.display = 'none'; });

// Select courier and autofill email + message
document.addEventListener('click', e => {
    if(e.target.classList.contains('courier-item')){
        const courierId = e.target.dataset.id;
        const receiverName = e.target.dataset.name;
        const receiverEmail = e.target.dataset.email;
        const toLocation = e.target.dataset.location;
        const status = e.target.dataset.status;

        courierIdHidden.value = courierId;
        openModal.value = e.target.textContent;
        toEmailInput.value = receiverEmail;
        messageTextarea.value = `Hello ${receiverName},\n\nYour courier (Status: ${status}) has been delivered to ${toLocation}.\n\nThank you,\nCourier Management Team`;

        modal.style.display = 'none';
    }
});

// Search
modalSearch.addEventListener('keyup', () => {
    const val = modalSearch.value.toLowerCase();
    document.querySelectorAll('.courier-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(val) ? 'block' : 'none';
    });
});
</script>

</div>
</body>
</html>