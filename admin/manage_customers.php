<?php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

$action = $_POST['action'] ?? '';
$customer_id = intval($_POST['customer_id'] ?? 0);
$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $customer_id > 0) {

    if ($action === 'update') {
        $name    = trim($_POST['name']);
        $email   = trim($_POST['email']);
        $phone   = trim($_POST['phone']);
        $address = trim($_POST['address']);

        if (!$name || !$email || !$phone) {
            $response = "Name, Email, and Phone are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE customer_id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
            $response = $stmt->execute() ? "Customer updated successfully." : "Update failed.";
        }

        echo $response;
        exit();
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id=?");
        $stmt->bind_param("i", $customer_id);
        $response = $stmt->execute() ? "Customer deleted successfully." : "Delete failed.";
        echo $response;
        exit();
    }
}

// Handle search by name or ID
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR customer_id LIKE ? ORDER BY customer_id DESC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM customers ORDER BY customer_id DESC");
}

$customers=[];
if($result) while($row=$result->fetch_assoc()) $customers[]=$row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Customers</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;}
html::-webkit-scrollbar{display:none;}

body{
    background:url('../assets/admin-manage-customers.jpg') center/cover no-repeat fixed;
    position:relative;
    padding-bottom:120px;
    padding-top:80px; /* space for navbar */
}
body::after{
    content:'';
    position:fixed;
    top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.35);
    z-index:-1;
}

/* NAVBAR */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    position:fixed;
    top:0;
    width:100%;
    z-index:999;
}
.logo{
    color:#ff7e5f;
    font-size:1.5rem;
    font-weight:bold;
}
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
    width:95%;
    max-width:1200px;
    margin:30px auto 50px auto;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(15px);
    border-radius:20px;
    padding:25px;
    color:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
}

/* SEARCH */
.search-box{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:20px;
}
.search-box input{
    flex:1;
    padding:12px;
    border-radius:10px;
    border:none;
}
.search-box button{
    padding:12px 20px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:#fff;
    font-weight:bold;
}

/* TABLE */
.table-wrapper{overflow-x:auto;}
table{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    color:#000;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.15);
}
th,td{padding:14px;text-align:left;}
th{background:#ff7e5f;color:#fff;}
td{
    background:#ffffff;
    border-bottom:1px solid #eee;
}
input{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
}
button.action{
    padding:8px 12px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin:2px 2px;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:#fff;
    font-weight:bold;
    white-space:nowrap;
}

/* RESPONSIVE TABLE */
@media(max-width:768px){
table,thead,tbody,tr,td{display:block;width:100%;}
thead{display:none;}
tr{
    margin-bottom:15px;
    background:#ffffff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}
td{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:8px 0;
}
td::before{
    content:attr(data-label);
    font-weight:bold;
    margin-right:10px;
    color:#333;
}
td[data-label="Actions"]{
    flex-direction:row;
    justify-content:flex-start;
    gap:6px;
}
button.action{
    padding:8px 10px;
    font-size:0.9rem;
}
}
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">Courier Admin</div>
    <div class="nav-buttons">
        <a href="dashboard.php" class="btn dashboard">Dashboard</a>
        <a href="../logout.php" class="btn logout">Logout</a>
    </div>
</div>

<div class="container">
<h2 style="text-align:center;margin-bottom:20px;">Manage Customers</h2>

<!-- SEARCH FORM -->
<form method="GET" class="search-box">
<input type="text" name="search" placeholder="Search by Name or ID..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Address</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php if(count($customers)>0): ?>
<?php foreach($customers as $c): ?>
<tr>
<td data-label="ID"><?= $c['customer_id'] ?></td>
<td data-label="Name"><input id="name-<?= $c['customer_id'] ?>" value="<?= htmlspecialchars($c['name']) ?>"></td>
<td data-label="Email"><input id="email-<?= $c['customer_id'] ?>" value="<?= htmlspecialchars($c['email']) ?>"></td>
<td data-label="Phone"><input id="phone-<?= $c['customer_id'] ?>" value="<?= htmlspecialchars($c['phone']) ?>"></td>
<td data-label="Address"><input id="address-<?= $c['customer_id'] ?>" value="<?= htmlspecialchars($c['address']) ?>"></td>
<td data-label="Actions">
<button class="action" onclick="updateCustomer(<?= $c['customer_id'] ?>)">Update</button>
<button class="action" onclick="deleteCustomer(<?= $c['customer_id'] ?>)">Delete</button>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6">No customers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>

<script>
function updateCustomer(id){
const name=document.getElementById('name-'+id).value;
const email=document.getElementById('email-'+id).value;
const phone=document.getElementById('phone-'+id).value;
const address=document.getElementById('address-'+id).value;

fetch('manage_customers.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`action=update&customer_id=${id}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&phone=${encodeURIComponent(phone)}&address=${encodeURIComponent(address)}`
})
.then(r=>r.text())
.then(msg=>{alert(msg);location.reload();});
}

function deleteCustomer(id){
if(!confirm('Delete this customer?')) return;

fetch('manage_customers.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`action=delete&customer_id=${id}`
})
.then(r=>r.text())
.then(msg=>{alert(msg);location.reload();});
}
</script>

</body>
</html>
