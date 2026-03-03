<?php 
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent'){
    header("Location: login.php");
    exit();
}

$agent_id = $_SESSION['agent_id'];

if(!isset($_GET['id'])){
    die("Invalid Courier ID");
}

$courier_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT c.*, s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.courier_id=? AND c.agent_id=?
");
$stmt->bind_param("ii", $courier_id, $agent_id);
$stmt->execute();
$courier = $stmt->get_result()->fetch_assoc();

if(!$courier){
    die("Courier not found");
}

$message = '';

if(isset($_POST['update'])){
    $status = $_POST['status'];
    $delivery_date = $_POST['delivery_date'];

    $update = $conn->prepare("
        UPDATE couriers SET status=?, delivery_date=? 
        WHERE courier_id=? AND agent_id=?
    ");
    $update->bind_param("ssii", $status, $delivery_date, $courier_id, $agent_id);

    if($update->execute()){

        $subject = "Courier Delivered Successfully";
        $body = "
            <h3>Courier Update</h3>
            <p><b>Tracking Number:</b> {$courier['tracking_number']}</p>
            <p><b>Status:</b> $status</p>
            <p><b>Delivery Date:</b> $delivery_date</p>
        ";

        send_mail($courier['sender_email'], $subject, $body);
        send_mail($courier['receiver_email'], $subject, $body);

        $message = "Courier updated and email sent successfully.";
    }
}

$statuses = ['booked','in-progress','delivered'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Courier</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html,body{height:100%;overflow:hidden;}
.scroll-wrapper{height:100vh;overflow:auto;-ms-overflow-style:none;scrollbar-width:none;}
.scroll-wrapper::-webkit-scrollbar{display:none;}
body{background:url('../assets/agent-send-sms.jpg') center/cover no-repeat fixed;position:relative;}
body::after{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.35);z-index:-1;}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    margin-bottom:30px;
    flex-wrap:wrap; /* prevents collapsing */
    gap:10px;
}

.logo{
    font-size:1.5rem;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    flex:1 1 auto;
}

.navbar-buttons{
    display:flex;
    gap:10px;
    flex-shrink:0;
}

.logout,.dashboard-btn{
    color:white;
    text-decoration:none;
    padding:12px 25px;
    border-radius:10px;
    font-weight:bold;
    transition:0.3s;
    white-space:nowrap;
}

.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}
.dashboard-btn{background:linear-gradient(135deg,#fddb6d,#fcb045);}

.logout:hover,.dashboard-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}

/* CONTAINER */
.container{max-width:600px;margin:0 auto 50px auto;background:#fff;padding:25px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);}
h2{text-align:center;margin-bottom:25px;font-size:1.8rem;background:linear-gradient(135deg,#ff7e5f,#feb47b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
label{display:block;margin:10px 0 5px;font-weight:600;color:#333;}
input{width:100%;padding:12px;margin-bottom:15px;border-radius:10px;border:1px solid #ccc;font-size:1rem;transition:all 0.3s ease-in-out;}
input:focus{outline:none;box-shadow:0 0 10px 2px rgba(255,126,95,0.6);border-color:#ff7e5f;}
button{width:100%;padding:14px;border:none;border-radius:10px;font-weight:bold;color:white;cursor:pointer;background:linear-gradient(135deg,#ff7e5f,#feb47b);transition:0.3s;}
button:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.25);}
p.message{text-align:center;font-weight:bold;color:#28a745;margin-bottom:15px;}

/* CUSTOM SELECT */
.custom-select{position:relative;width:100%;margin-bottom:15px;}
.select-selected{background:#fff;border:1px solid #ccc;border-radius:10px;padding:12px;cursor:pointer;user-select:none;position:relative;transition:all 0.3s ease-in-out;}
.select-selected.active{box-shadow:0 0 10px 2px rgba(255,126,95,0.6);border-color:#ff7e5f;}
.select-selected:after{content:"\25BC";position:absolute;right:12px;top:50%;transform:translateY(-50%);}
.select-items{position:absolute;background:#fff;top:100%;left:0;right:0;border:1px solid #ccc;border-radius:10px;z-index:99;display:none;}
.select-items div{padding:10px;cursor:pointer;border-bottom:1px solid #eee;}
.select-items div:hover{background:linear-gradient(135deg,#ff7e5f,#feb47b);color:white;}

/* RESPONSIVE FIX */
@media(max-width:768px){

    .navbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .logo{
        width:100%;
        margin-bottom:8px;
    }

    .navbar-buttons{
        width:100%;
        justify-content:flex-start;
    }

    .logout,.dashboard-btn{
        padding:8px 14px;
        font-size:0.9rem;
    }

    .container{
        margin:20px 15px;
        padding:20px;
    }
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
<h2>Update Courier</h2>

<?php if($message) echo "<p class='message'>$message</p>"; ?>

<form method="POST">

<p><strong>Courier ID:</strong> <?= $courier['courier_id'] ?></p>
<p><strong>Sender:</strong> <?= htmlspecialchars($courier['sender_name']) ?></p>
<p><strong>Receiver:</strong> <?= htmlspecialchars($courier['receiver_name']) ?></p>

<label>Status:</label>
<div class="custom-select">
    <div class="select-selected"><?= ucfirst($courier['status']) ?></div>
    <div class="select-items">
        <?php foreach($statuses as $s): ?>
            <div data-value="<?= $s ?>"><?= ucfirst($s) ?></div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="status" value="<?= $courier['status'] ?>">
</div>

<label>Delivery Date:</label>
<input type="date" name="delivery_date" value="<?= htmlspecialchars($courier['delivery_date']) ?>" required>

<button type="submit" name="update">Update Courier</button>

</form>
</div>

</div>

<script>
const selected = document.querySelector('.select-selected');
const items = document.querySelector('.select-items');
const hiddenInput = document.querySelector('input[name="status"]');

selected.addEventListener('click',()=>{
    items.style.display = items.style.display==='block'?'none':'block';
    selected.classList.toggle('active');
});

document.querySelectorAll('.select-items div').forEach(div=>{
    div.addEventListener('click',()=>{
        selected.textContent=div.textContent;
        hiddenInput.value=div.getAttribute('data-value');
        items.style.display='none';
        selected.classList.remove('active');
    });
});

document.addEventListener('click',(e)=>{
    if(!selected.contains(e.target) && !items.contains(e.target)){
        items.style.display='none';
        selected.classList.remove('active');
    }
});
</script>

</body>
</html>