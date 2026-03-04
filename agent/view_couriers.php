<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if(!isset($_SESSION['agent_id'])){
    die("<p style='color:red;'>You must be logged in as an agent.</p>");
}

$agent_user_id = $_SESSION['user_id'] ?? null;

// Fetch agent_id and branch
$stmt = $conn->prepare("SELECT agent_id, branch FROM agents WHERE user_id = ?");
$stmt->bind_param("i", $agent_user_id);
$stmt->execute();
$agent_data = $stmt->get_result()->fetch_assoc();

if(!$agent_data){
    die("<p style='color:red;'>Agent not found.</p>");
}

$agent_id = $agent_data['agent_id'];
$branch = $agent_data['branch'];

// Handle search
$search = trim($_GET['search'] ?? '');
$search_sql = "";
$params = [];
$types = "";

if($search !== ""){
    $search_sql = " AND (
        c.courier_id LIKE ? OR
        s.name LIKE ? OR
        s.email LIKE ? OR
        r.name LIKE ? OR
        r.email LIKE ?
    )";
    $like = "%$search%";
    $types = "issss";
    $params = [$like, $like, $like, $like, $like];
}

$query = "
    SELECT c.*, 
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    JOIN customers s ON c.sender_id = s.customer_id
    JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.agent_id = ?
    $search_sql
    ORDER BY c.courier_id DESC
";

$stmt = $conn->prepare($query);

if($search !== ""){
    $stmt->bind_param("i" . $types, $agent_id, ...$params);
} else {
    $stmt->bind_param("i", $agent_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Couriers - Agent Panel</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html, body{width:100%;overflow-x:hidden;}

body{
    background:url('../assets/agent-view-couriers.jpg') center/cover no-repeat fixed;
    position:relative;
    padding-bottom:50px;
    padding-top:70px; /* space for fixed navbar */
}
body::after{
    content:'';position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.35);z-index:-1;
}

/* NAVBAR */
.navbar{
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    flex-wrap:wrap;
    position:fixed;
    top:0;
    left:0;
    z-index:999;
}
.logo{
    font-size:1.5rem;
    font-weight:bold;
    color:#ff7e5f;
}
.nav-right{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}
.dashboard, .logout{
    text-decoration:none;
    padding:10px 20px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    white-space:nowrap;
    transition:0.4s;
}
.dashboard{background:linear-gradient(135deg,#ffd200,#f7971e);}
.logout{background:linear-gradient(135deg,#ff7e5f,#feb47b);}
.dashboard:hover,.logout:hover{transform:translateY(-1px);}

/* MOBILE NAVBAR CENTER FIX */
@media(max-width:768px){
.navbar{
flex-direction:column;
align-items:center;
gap:15px;
}
.nav-right{
width:100%;
display:flex;
justify-content:center;
gap:12px;
flex-wrap:wrap;
}
.dashboard, .logout{
padding:8px 18px;
font-size:0.9rem;
}
}

/* CONTAINER */
.container{
width:90%;
max-width:1100px;
margin:20px auto 50px auto;
background:rgba(255,255,255,0.95);
backdrop-filter:blur(6px);
border-radius:20px;
padding:25px;
box-shadow:0 10px 30px rgba(0,0,0,0.15);
overflow-x:auto;
}

/* HEADING */
h2{text-align:center;margin-bottom:20px;color:#ff7e5f;font-size:1.8rem;}

/* SEARCH FORM */
.search-form{
margin-bottom:20px;
display:flex;
justify-content:center;
flex-wrap:wrap;
gap:10px;
}
.search-form input[type="text"]{
padding:10px 15px;
border-radius:8px;
border:1px solid #ccc;
width:250px;
max-width:80%;
font-size:1rem;
outline:none;
transition:0.3s;
}
.search-form input[type="text"]:focus{
box-shadow:0 0 10px rgba(255,126,95,0.7);
border-color:#ff7e5f;
}
.search-form button{
padding:10px 20px;
border:none;
border-radius:8px;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
font-weight:bold;
cursor:pointer;
transition:0.3s;
}
.search-form button:hover{transform:translateY(-1px);}

/* TABLE */
.table-wrapper{overflow-x:auto;}
table{
width:100%;
border-collapse:collapse;
background:#fff;
color:#000;
border-radius:12px;
overflow:hidden;
box-shadow:0 5px 20px rgba(0,0,0,0.15);
}
th, td{padding:14px 20px;text-align:left;vertical-align:top;}
th{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
font-weight:600;
}
td{background:#fff;border-bottom:1px solid #eee;}
a.action-link{color:#ff7e5f;font-weight:bold;text-decoration:none;}
a.action-link:hover{color:#feb47b;}
.no-data{text-align:center;padding:20px;font-style:italic;color:#555;background:#f8f8f8;border-radius:8px;}

/* RESPONSIVE */
@media(max-width:1024px){
th, td{padding:12px 15px;font-size:0.95rem;}
}
@media(max-width:768px){
.container{margin:40px 15px;padding:20px;}
table{display:block;overflow-x:auto;white-space:nowrap;}
th, td{padding:10px 12px;}
.nav-right{justify-content:center;width:100%;}
}
@media(max-width:480px){
.container{margin:20px 10px;padding:15px;}
table, thead, tbody, th, td, tr{display:block;width:100%;}
thead{display:none;}
tr{margin-bottom:15px;background:rgba(255,255,255,0.95);border-radius:12px;padding:10px;}
td{
display:flex;
justify-content:space-between;
padding:8px 10px;
border-bottom:1px solid rgba(0,0,0,0.1);
}
td span.label{
font-weight:bold;
color:#ff7e5f;
flex:0 0 110px;
}
td span.value{
flex:1;
text-align:right;
word-break:break-word;
}
th, td{font-size:0.85rem;}
}
</style>
</head>
<body>
<div class="navbar">
<div class="logo">Courier Agent</div>
<div class="nav-right">
<a href="dashboard.php" class="dashboard">Dashboard</a>
<a href="../logout.php" class="logout">Logout</a>
</div>
</div>

<div class="container">
<h2>View Couriers (Branch: <?= htmlspecialchars($branch) ?>)</h2>

<form class="search-form" method="GET">
<input type="text" name="search" placeholder="Search by ID, Sender, Receiver..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>ID</th>
<th>Sender</th>
<th>Receiver</th>
<th>Status</th>
<th>Delivery Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= $row['courier_id'] ?></td>
<td><?= htmlspecialchars($row['sender_name']) ?> (<?= htmlspecialchars($row['sender_email']) ?>)</td>
<td><?= htmlspecialchars($row['receiver_name']) ?> (<?= htmlspecialchars($row['receiver_email']) ?>)</td>
<td><?= ucfirst($row['status']) ?></td>
<td><?= $row['delivery_date'] ?></td>
<td><a class="action-link" href="update_courier.php?id=<?= $row['courier_id'] ?>">Edit</a></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="no-data">No couriers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>