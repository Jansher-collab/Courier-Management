<?php
// admin/view_couriers.php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

// Fetch all couriers with sender, receiver, and agent info
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
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Couriers</title>

<style>
/* RESET */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

/* HIDE SCROLLBAR */
html{scrollbar-width:none;}
html::-webkit-scrollbar{display:none;}

/* BACKGROUND */
body{
    background:url('../assets/admin-view-couriers.jpg') center/cover no-repeat fixed;
    position:relative;
    padding-bottom:120px;
}
body::after{
    content:''; position:fixed; top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.35); z-index:-1;
}

/* NAVBAR */
.navbar{
    display:flex; justify-content:space-between; align-items:center;
    padding:15px 30px;
}
.logo{color:#fff;font-size:1.5rem;font-weight:bold;}
.logout{
    text-decoration:none; padding:12px 25px; border-radius:10px;
    font-weight:bold; color:white; background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* CONTAINER */
.container{
    width:95%; max-width:1400px; margin:50px auto;
    background:rgba(255,255,255,0.15); backdrop-filter:blur(15px);
    border-radius:20px; padding:25px; color:#fff;
    overflow-x:hidden; /* Remove horizontal scroll */
}

/* TABLE */
table{
    width:100%; border-collapse:collapse;
    background:#ffffff; color:#000; border-radius:12px;
    overflow:hidden; box-shadow:0 5px 20px rgba(0,0,0,0.15);
    table-layout:auto; /* allow auto column width, no fixed layout */
    word-wrap:break-word; /* wrap long text */
}

th, td{padding:14px;text-align:left; vertical-align:top;}
th{background:#ff7e5f;color:#fff; font-weight:600;}
td{background:#ffffff;border-bottom:1px solid #eee;}

/* ACTION BUTTONS */
a.button{
    padding:8px 12px; border-radius:8px; text-decoration:none;
    color:#fff; font-weight:bold; margin:2px; display:inline-block; white-space:normal;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* DESKTOP & TABLET RESPONSIVE */
@media(min-width:769px) and (max-width:1400px){
    th, td{padding:12px 10px;}
    a.button{padding:6px 10px;}
}

/* MOBILE TABLE */
@media(max-width:768px){
    table, thead, tbody, tr, td{display:block;width:100%;}
    thead{display:none;}
    tr{margin-bottom:15px;background:#ffffff;padding:15px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
    td{
        display:flex; justify-content:space-between; align-items:center;
        padding:8px 0; word-break:break-word;
    }
    td::before{content:attr(data-label); font-weight:bold; margin-right:10px; color:#333; flex-shrink:0;}
    td[data-label="Actions"]{flex-direction:row; justify-content:flex-start; gap:6px;}
    a.button{padding:6px 10px; font-size:0.85rem;}
}
</style>
</head>

<body>

<div class="navbar">
    <div class="logo">Courier Admin</div>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2 style="text-align:center;margin-bottom:20px;">All Couriers</h2>

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
            <?= $row['agent_id'] ? htmlspecialchars($row['agent_name'] ?? '-') . " (" . htmlspecialchars($row['agent_branch']) . ")" : '-' ?>
        </td>
        <td data-label="Actions">
            <a class="button" href="update_courier.php?id=<?= $row['courier_id'] ?>">Update</a>
            <a class="button" href="delete_courier.php?id=<?= $row['courier_id'] ?>" onclick="return confirm('Are you sure you want to delete this courier?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="10" class="no-data">No couriers found.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</body>
</html>
