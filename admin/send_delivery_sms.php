<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

// CSRF token
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message_sent = '';

// Fetch only delivered couriers
$query = "SELECT 
            c.courier_id, 
            r.name AS receiver_name, 
            r.email AS receiver_email,
            c.to_location,
            c.tracking_number
          FROM couriers c
          JOIN customers r ON c.receiver_id = r.customer_id
          WHERE c.status='delivered'
          ORDER BY c.courier_id ASC";

$result = $conn->query($query);
$couriers = [];
while($row = $result->fetch_assoc()) $couriers[] = $row;

$selected_courier_id = $_POST['courier_id'] ?? '';

if(isset($_POST['send_delivery_email'], $_POST['csrf_token'])){
    if($_POST['csrf_token'] !== $_SESSION['csrf_token']){
        $message_sent = "Invalid CSRF token.";
    } elseif(!empty($selected_courier_id)){
        $courier_id = intval($selected_courier_id);

        $stmt_c = $conn->prepare(
            "SELECT r.name, r.email, c.to_location, c.tracking_number 
             FROM couriers c 
             JOIN customers r ON c.receiver_id = r.customer_id 
             WHERE c.courier_id = ? AND c.status='delivered'"
        );
        $stmt_c->bind_param("i", $courier_id);
        $stmt_c->execute();
        $courier = $stmt_c->get_result()->fetch_assoc();

        if($courier){
            $subject = "Your Courier Has Been Delivered!";
            $body =
"Hello ".$courier['name'].",

Your courier with tracking number ".$courier['tracking_number']." 
has been successfully delivered to ".$courier['to_location'].".

Thank you for using our Courier Management System.";

            if(send_mail($courier['email'], $subject, $body)){
                $message_sent = "Delivery email sent to "
                    . htmlspecialchars($courier['name'])
                    . " (" . htmlspecialchars($courier['email']) . ")";

                $stmt_log = $conn->prepare(
                    "INSERT INTO courier_logs (courier_id, status, message, notified_via)
                     VALUES (?, 'delivered', ?, 'email')"
                );
                $stmt_log->bind_param("is", $courier_id, $body);
                $stmt_log->execute();

            } else {
                $message_sent = "Failed to send email.";
            }
        } else {
            $message_sent = "Selected courier not found or not delivered.";
        }
    } else {
        $message_sent = "Please select a courier first.";
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
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}
body{background:url('../assets/admin-delivery-sms.jpg') center/cover no-repeat fixed;padding-top:100px;overflow-x:hidden;}
body::after{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.35);z-index:-1;}

/* Navbar */
.navbar{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;position:fixed;top:0;width:100%;z-index:999;flex-wrap:wrap;}
.logo{ color:#ff7e5f;font-size:1.5rem;font-weight:bold;}
.nav-buttons{display:flex;gap:10px;flex-wrap:wrap;}
.btn{text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:bold;color:white;transition:0.3s;}
.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}

/* Container */
.container{width:95%;max-width:700px;margin:50px auto;background:rgba(255,255,255,0.15);backdrop-filter:blur(15px);border-radius:20px;padding:30px;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,0.25);position:relative;}
h2{text-align:center;margin-bottom:20px;}
p.message{text-align:center;font-weight:bold;margin-bottom:10px;color:#28a745;}

/* Dropdown (looks like field but triggers modal) */
.dropdown-wrapper{position:relative;width:100%;margin-bottom:20px;z-index:100;}
.dropdown-selected{
    width:100%;padding:12px;border-radius:10px;background:#fff;color:#000;
    cursor:pointer;position:relative;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
    border:none;outline:none;transition:0.3s;
}
.dropdown-selected.active{box-shadow:0 0 12px 3px rgba(255,126,95,0.7);border:1px solid #ff7e5f;}
.dropdown-selected::after{content:"▼";position:absolute;right:15px;top:50%;transform:translateY(-50%);}
input:focus, textarea:focus{box-shadow:0 0 12px 3px rgba(255,126,95,0.7);border:1px solid #ff7e5f;}

/* Modal styles */
.modal{
    display:none;position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.6);z-index:10000;overflow:auto;
}
.modal-content{
    background:#fff;border-radius:15px;margin:10% auto;padding:20px;
    width:90%;max-width:500px;position:relative;color:#000;
}
.modal-header{font-weight:bold;margin-bottom:10px;font-size:1.2rem;}
.modal-search{width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;outline:none;}
.modal-item{padding:10px;border-radius:8px;cursor:pointer;transition:0.3s;}
.modal-item:hover{background:linear-gradient(135deg,#ff7e5f,#feb47b);color:#fff;}
.close-modal{position:absolute;top:10px;right:15px;font-size:1.5rem;cursor:pointer;color:#ff7e5f;}

/* Button */
button{display:block;margin:25px auto 0 auto;padding:12px 30px;border-radius:10px;border:none;cursor:pointer;font-size:1rem;font-weight:bold;background:linear-gradient(135deg,#ff7e5f,#feb47b);color:#fff;transition:0.3s;}
button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}

/* Responsive Navbar */
@media (max-width:600px){
    .navbar{flex-direction:column;align-items:flex-start;}
    .nav-buttons{width:100%;justify-content:space-around;margin-top:10px;}
    .btn{flex:1;text-align:center;}
}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Courier Admin</div>
    <div class="nav-buttons">
        <a href="dashboard.php" class="btn dashboard">Dashboard</a>
        <a href="../logout.php" class="btn logout">Logout</a>
    </div>
</div>

<div class="container">
<h2>Send Delivery Email (Delivered Couriers)</h2>

<?php if($message_sent) echo "<p class='message'>$message_sent</p>"; ?>

<form method="POST">

<div class="dropdown-wrapper">
<label>Select Delivered Courier:</label>
<div class="dropdown-selected" id="selected">--Select Courier--</div>
</div>

<input type="hidden" name="courier_id" id="courier_id_val">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

<button type="submit" name="send_delivery_email">Send Delivery Email</button>
</form>
</div>

<!-- Modal -->
<div class="modal" id="courierModal">
    <div class="modal-content">
        <span class="close-modal" id="closeModal">&times;</span>
        <div class="modal-header">Select Delivered Courier</div>
        <input type="text" id="modalSearch" class="modal-search" placeholder="Search by ID, Name, Email, Location...">
        <div id="modalItems">
            <?php foreach($couriers as $c): ?>
            <div class="modal-item" data-id="<?= $c['courier_id'] ?>" 
                 data-search="<?= strtolower($c['courier_id'].' '.$c['receiver_name'].' '.$c['receiver_email'].' '.$c['to_location']) ?>">
                <?= "Courier ID: {$c['courier_id']} - ".htmlspecialchars($c['receiver_name'])." ({$c['receiver_email']}) to ".htmlspecialchars($c['to_location']) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
const selected = document.getElementById('selected');
const modal = document.getElementById('courierModal');
const closeModal = document.getElementById('closeModal');
const hiddenInput = document.getElementById('courier_id_val');
const modalItems = document.getElementById('modalItems');
const modalSearch = document.getElementById('modalSearch');

// Open modal
selected.addEventListener('click', () => {
    modal.style.display = 'block';
    modalSearch.value = '';
    filterItems('');
    modalSearch.focus();
});

// Close modal
closeModal.addEventListener('click', () => { modal.style.display = 'none'; });
window.addEventListener('click', e => { if(e.target===modal) modal.style.display='none'; });

// Select courier
modalItems.querySelectorAll('.modal-item').forEach(item=>{
    item.addEventListener('click', ()=>{
        selected.textContent = item.textContent;
        hiddenInput.value = item.dataset.id;
        modal.style.display='none';
    });
});

// Search inside modal
modalSearch.addEventListener('keyup', ()=>{
    const val = modalSearch.value.toLowerCase();
    filterItems(val);
});

function filterItems(val){
    let anyVisible = false;
    modalItems.querySelectorAll('.modal-item').forEach(item=>{
        if(item.dataset.search.includes(val)){
            item.style.display='block';
            anyVisible = true;
        } else { item.style.display='none'; }
    });
    if(!anyVisible){
        if(!document.getElementById('noResults')){
            const noRes = document.createElement('div');
            noRes.id='noResults';
            noRes.className='modal-item';
            noRes.style.textAlign='center';
            noRes.style.cursor='default';
            noRes.textContent='No couriers found';
            modalItems.appendChild(noRes);
        }
    } else {
        const noRes = document.getElementById('noResults');
        if(noRes) noRes.remove();
    }
}
</script>

</body>
</html>