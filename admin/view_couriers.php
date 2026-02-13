<?php
// admin/view_couriers.php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, 
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email,
           a.branch AS agent_branch,
           u.name AS agent_name
    FROM couriers c
    LEFT JOIN customers s ON c.sender_id = s.customer_id
    LEFT JOIN customers r ON c.receiver_id = r.customer_id
    LEFT JOIN agents a ON c.agent_id = a.agent_id
    LEFT JOIN users u ON a.user_id = u.user_id
";

$params = [];
$types = "";

if(!empty($search)){
    $sql .= " WHERE 
        CAST(c.courier_id AS CHAR) LIKE ?
        OR LOWER(TRIM(s.name)) LIKE LOWER(TRIM(?))
        OR LOWER(TRIM(r.name)) LIKE LOWER(TRIM(?))
        OR LOWER(TRIM(s.email)) LIKE LOWER(TRIM(?))
        OR LOWER(TRIM(r.email)) LIKE LOWER(TRIM(?))
    ";
    $search_param = "%".$search."%";
    $params = [$search_param,$search_param,$search_param,$search_param,$search_param];
    $types = "sssss";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);

if(!empty($params)){
    $stmt->bind_param($types,...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Couriers</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}

body{
background:url('../assets/admin-view-couriers.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:120px;
padding-top:80px; /* space for fixed navbar */
}
body::after{
content:'';position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.35);z-index:-1;
}

/* NAVBAR */
.navbar{
display:flex;justify-content:space-between;align-items:center;
padding:15px 30px;
position:fixed;
top:0;width:100%;
z-index:999;
/* Removed background and blur to keep navbar transparent */
}
.logo{;font-size:1.5rem;font-weight:bold; color:#ff7e5f;}
.nav-buttons{
display:flex;
gap:10px;
}
.btn{
text-decoration:none;
padding:12px 20px;
border-radius:10px;
font-weight:bold;
color:white;
transition:0.3s;
}
.dashboard{
background:linear-gradient(135deg,#ffd200,#f7971e);
}
.logout{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* CONTAINER */
.container{
width:95%;max-width:1400px;margin:50px auto;
background:rgba(255,255,255,0.15);backdrop-filter:blur(15px);
border-radius:20px;padding:25px;color:#fff;
overflow-x:hidden;
}

/* SEARCH */
.search-box{
width:100%;
margin-bottom:20px;
display:flex;
gap:10px;
}
.search-box input{
flex:1;
padding:12px;
border-radius:10px;
border:none;
outline:none;
}
.search-box button{
padding:12px 20px;
border:none;
border-radius:10px;
cursor:pointer;
font-weight:bold;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
}

table{
width:100%;border-collapse:collapse;
background:#ffffff;color:#000;border-radius:12px;
overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.15);
}
th,td{padding:14px;text-align:left;vertical-align:top;}
th{background:#ff7e5f;color:#fff;font-weight:600;}
td{background:#ffffff;border-bottom:1px solid #eee;}

a.button{
padding:8px 12px;border-radius:8px;text-decoration:none;
color:#fff;font-weight:bold;margin:2px;display:inline-block;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

@media(max-width:768px){
table,thead,tbody,tr,td{display:block;width:100%;}
thead{display:none;}
tr{margin-bottom:15px;background:#ffffff;padding:15px;border-radius:12px;}
td{display:flex;justify-content:space-between;padding:8px 0;}
td::before{content:attr(data-label);font-weight:bold;margin-right:10px;}
}
</style>
</head>

<body>

<!-- NAVBAR WITH DASHBOARD BUTTON -->
<div class="navbar">
    <div class="logo">Courier Admin</div>
    <div class="nav-buttons">
        <a href="dashboard.php" class="btn dashboard">Dashboard</a>
        <a href="../logout.php" class="btn logout">Logout</a>
    </div>
</div>

<div class="container">

<h2 style="text-align:center;margin-bottom:20px;">All Couriers</h2>

<form method="GET" class="search-box">
<input type="text" name="search" placeholder="Search by ID, Sender, Receiver or Email..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>

<table>
<thead>
<tr>
<th>ID</th>
<th>Sender</th>
<th>Receiver</th>
<th>From</th>
<th>To</th>
<th>Type</th>
<th>Status</th>
<th>Delivery Date</th>
<th>Agent</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php if ($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td data-label="ID"><?= $row['courier_id'] ?></td>
<td data-label="Sender"><?= htmlspecialchars($row['sender_name']) ?> (<?= htmlspecialchars($row['sender_email']) ?>)</td>
<td data-label="Receiver"><?= htmlspecialchars($row['receiver_name']) ?> (<?= htmlspecialchars($row['receiver_email']) ?>)</td>
<td data-label="From"><?= htmlspecialchars($row['from_location']) ?></td>
<td data-label="To"><?= htmlspecialchars($row['to_location']) ?></td>
<td data-label="Type"><?= htmlspecialchars($row['courier_type']) ?></td>
<td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
<td data-label="Delivery Date"><?= htmlspecialchars($row['delivery_date']) ?></td>
<td data-label="Agent">
<?= $row['agent_id'] ? htmlspecialchars($row['agent_name'])." (".htmlspecialchars($row['agent_branch']).")" : '-' ?>
</td>
<td data-label="Actions">
<a class="button" href="update_courier.php?id=<?= $row['courier_id'] ?>">Update</a>
<a class="button" href="delete_courier.php?id=<?= $row['courier_id'] ?>" onclick="return confirm('Delete this courier?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="10">No couriers found.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</body>
</html>
