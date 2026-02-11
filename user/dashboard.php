<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'User';

// Determine greeting based on time
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
    scroll-behavior:smooth;
}

html,body{
    width:100%;
    overflow-x:hidden;
    background:#f4f7fb;
}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 25px;
    background:white;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    flex-wrap:wrap;
    animation:fadeDown 0.6s ease;
}

.logo{
    font-size:1.4rem;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.logout{
    text-decoration:none;
    padding:10px 20px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    transition:0.3s;
}

.logout:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(255,126,95,0.7),0 0 25px rgba(255,126,95,0.5);
}

/* HERO */
.hero{
    max-width:1200px;
    margin:40px auto;
    padding:50px 25px;
    border-radius:25px;
    background:
    linear-gradient(rgba(0,0,0,0.45),rgba(0,0,0,0.45)),
    url('../assets/user-dashboard.jpg') center/cover no-repeat;
    color:white;
    text-align:center;
    animation:fadeUp 0.8s ease;
    overflow:hidden;
}

.hero h1{
    font-size:clamp(26px,3vw,40px);
    margin-bottom:15px;
}

.hero p{
    font-size:clamp(15px,1.2vw,18px);
    max-width:700px;
    margin:auto;
    line-height:1.7;
    opacity:0.95;
}

/* CARDS */
.cards{
    max-width:1100px;
    margin:30px auto 60px auto;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:22px;
    padding:0 20px;
}

.card{
    background:rgba(255,255,255,0.9);
    backdrop-filter:blur(12px);
    padding:28px;
    border-radius:22px;
    text-align:center;
    box-shadow:0 12px 28px rgba(0,0,0,0.1);
    transition:0.35s;
    text-decoration:none;
    color:#333;
    position:relative;
    overflow:hidden;
    animation:fadeUp 1s ease;
}

.card h3{
    margin-bottom:12px;
    font-size:22px;
    color:#ff7e5f;
    transition:0.3s;
}

.card p{
    font-size:14.5px;
    color:#555;
    line-height:1.6;
}

/* GLOW HOVER EFFECT */
.card::before, .card::after{
    content:'';
    position:absolute;
    width:0;
    height:0;
    transition:0.4s;
}

.card:hover{
    transform:translateY(-5px) scale(1.02);
    box-shadow:0 0 25px rgba(255,126,95,0.5),0 0 50px rgba(255,126,95,0.3);
}

.card::before, .card::after{
    top:0; left:0; right:0; bottom:0;
    box-shadow:0 0 0 rgba(255,126,95,0);
}

.card:hover::before{
    box-shadow: inset 0 0 15px rgba(255,126,95,0.6), inset 0 0 25px rgba(255,126,95,0.3);
}

/* ANIMATIONS */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(30px);}
    to{opacity:1;transform:translateY(0);}
}

@keyframes fadeDown{
    from{opacity:0;transform:translateY(-20px);}
    to{opacity:1;transform:translateY(0);}
}

/* RESPONSIVE */
@media(max-width:992px){
    .hero{
        margin:30px 20px;
        padding:40px 20px;
        border-radius:22px;
    }
}

@media(max-width:600px){
    .navbar{
        flex-direction:column;
        gap:10px;
        text-align:center;
    }
    .hero{
        margin:25px 15px;
        padding:30px 18px;
        border-radius:20px;
    }
    .cards{
        padding:0 15px;
    }
}
</style>
</head>

<body>

<div class="navbar">
    <div class="logo">Courier Portal</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="hero">
    <h1><?= $greeting ?>, <?= htmlspecialchars($user_name) ?>!</h1>
    <p>
    Experience modern delivery management with real-time tracking,
    instant shipment updates, and a professional courier system built
    for speed, transparency, and convenience. Manage your deliveries
    effortlessly from one powerful dashboard.
    </p>
</div>

<div class="cards">

    <a href="track_courier.php" class="card">
        <h3>Track Courier</h3>
        <p>
        Track shipments live with instant updates and delivery progress
        from dispatch to arrival.
        </p>
    </a>

    <a href="view_status.php" class="card">
        <h3>View Courier Status</h3>
        <p>
        Check courier history, shipment details, and delivery stages
        in a clean and modern interface.
        </p>
    </a>

    <a href="print_status.php" target="_blank" class="card">
        <h3>Print Courier Status</h3>
        <p>
        Generate printable shipment reports for records,
        documentation, or official use.
        </p>
    </a>

</div>

</body>
</html>
