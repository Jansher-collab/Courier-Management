<?php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = $_POST['action'] ?? '';
    $agent_id = intval($_POST['agent_id']);

    if ($agent_id <= 0) {
        echo "Invalid agent ID.";
        exit();
    }

    if ($action === 'update') {

        $branch = trim($_POST['branch'] ?? '');

        if (empty($branch)) {
            echo "Branch is required.";
            exit();
        }

        $stmt = $conn->prepare("UPDATE agents SET branch=? WHERE agent_id=?");
        $stmt->bind_param("si", $branch, $agent_id);

        echo $stmt->execute() ? "Agent updated successfully." : "Failed to update agent.";
        exit();
    }

    if ($action === 'delete') {

        $stmtCheck = $conn->prepare("SELECT COUNT(*) AS count FROM couriers WHERE agent_id=?");
        $stmtCheck->bind_param("i", $agent_id);
        $stmtCheck->execute();
        $count = $stmtCheck->get_result()->fetch_assoc()['count'];

        if ($count > 0) {
            echo "Cannot delete agent. They have assigned couriers.";
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM agents WHERE agent_id=?");
        $stmt->bind_param("i", $agent_id);

        echo $stmt->execute() ? "Agent deleted successfully." : "Failed to delete agent.";
        exit();
    }
}

$result = $conn->query("
    SELECT a.agent_id, a.branch, u.name, u.email
    FROM agents a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.agent_id DESC
");

$agents = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Agents</title>

<style>

/* RESET */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

/* HIDE SCROLLBAR BUT KEEP SCROLL */
html{
scrollbar-width:none;
}
html::-webkit-scrollbar{
display:none;
}

/* BACKGROUND */
body{
background:url('../assets/admin-manage-agents.jpg') center/cover no-repeat fixed;
position:relative;
padding-bottom:120px;
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
}
.logo{color:#fff;font-size:1.5rem;font-weight:bold;}
.logout{
text-decoration:none;
padding:12px 25px;
border-radius:10px;
font-weight:bold;
color:white;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* CONTAINER */
.container{
width:95%;
max-width:1100px;
margin:50px auto;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(15px);
border-radius:20px;
padding:25px;
color:#fff;
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

/* INPUT */
input[type=text]{
width:100%;
padding:8px;
border-radius:8px;
border:1px solid #ccc;
}

/* BUTTON */
button{
padding:8px 14px;
border:none;
border-radius:8px;
cursor:pointer;
margin:2px;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
color:#fff;
font-weight:bold;
}

/* MOBILE TABLE */
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
}

</style>
</head>

<body>

<div class="navbar">
<div class="logo">Courier Admin</div>
<a href="../logout.php" class="logout">Logout</a>
</div>

<div class="container">
<h2 style="text-align:center;margin-bottom:20px;">Manage Agents</h2>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Branch</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php if(count($agents)>0): ?>
<?php foreach($agents as $agent): ?>
<tr>
<td data-label="ID"><?= $agent['agent_id'] ?></td>
<td data-label="Name"><?= htmlspecialchars($agent['name']) ?></td>
<td data-label="Email"><?= htmlspecialchars($agent['email']) ?></td>
<td data-label="Branch">
<input type="text"
value="<?= htmlspecialchars($agent['branch']) ?>"
id="branch-<?= $agent['agent_id'] ?>">
</td>
<td data-label="Actions">
<button onclick="updateAgent(<?= $agent['agent_id'] ?>)">Update</button>
<button onclick="deleteAgent(<?= $agent['agent_id'] ?>)">Delete</button>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="5">No agents found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<script>
function updateAgent(agentId){
const branch=document.getElementById('branch-'+agentId).value;
fetch('manage_agents.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`action=update&agent_id=${agentId}&branch=${encodeURIComponent(branch)}`
})
.then(res=>res.text())
.then(msg=>{alert(msg);location.reload();});
}

function deleteAgent(agentId){
if(!confirm('Are you sure you want to delete this agent?')) return;
fetch('manage_agents.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`action=delete&agent_id=${agentId}`
})
.then(res=>res.text())
.then(msg=>{alert(msg);location.reload();});
}
</script>

</body>
</html>
