<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin'){
    die("<p style='color:red;'>Access denied.</p>");
}

$success='';
$error='';

// ---------------- APPROVE AGENT ----------------
if(isset($_POST['approve'])){
    $agent_id=intval($_POST['agent_id']);

    $stmt=$conn->prepare("UPDATE agents SET approved=1 WHERE agent_id=?");
    $stmt->bind_param("i",$agent_id);

    if($stmt->execute()){

        $stmt2=$conn->prepare("
            SELECT u.email,u.name 
            FROM users u
            JOIN agents a ON u.user_id=a.user_id
            WHERE a.agent_id=?
        ");
        $stmt2->bind_param("i",$agent_id);
        $stmt2->execute();
        $agent=$stmt2->get_result()->fetch_assoc();

        if($agent){
            $subject="Agent Account Approved";
            $body="
            <h3>Your Agent Account is Approved</h3>
            <p>Hello {$agent['name']},</p>
            <p>Your agent account has been approved by admin.</p>
            <p>You can now login.</p>
            ";
            send_mail($agent['email'],$subject,$body);
        }

        $success="Agent approved successfully.";
    }else{
        $error="Failed to approve agent.";
    }
}

// ---------------- REJECT AGENT ----------------
if(isset($_POST['reject'])){
    $agent_id=intval($_POST['agent_id']);

    $stmt2=$conn->prepare("
        SELECT u.email,u.name 
        FROM users u
        JOIN agents a ON u.user_id=a.user_id
        WHERE a.agent_id=?
    ");
    $stmt2->bind_param("i",$agent_id);
    $stmt2->execute();
    $agent=$stmt2->get_result()->fetch_assoc();

    $stmt=$conn->prepare("DELETE FROM agents WHERE agent_id=?");
    $stmt->bind_param("i",$agent_id);

    if($stmt->execute()){
        if($agent){
            $subject="Agent Account Rejected";
            $body="
            <h3>Your Agent Account is Rejected</h3>
            <p>Hello {$agent['name']},</p>
            <p>Unfortunately, your agent account has been rejected by the admin.</p>
            ";
            send_mail($agent['email'],$subject,$body);
        }
        $success="Agent rejected successfully.";
    } else{
        $error="Failed to reject agent.";
    }
}

$agents=$conn->query("
SELECT a.agent_id,u.name,u.email,a.branch
FROM agents a
JOIN users u ON a.user_id=u.user_id
WHERE a.approved=0
ORDER BY a.agent_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Approve Agents</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

html,body{
height:100%;
overflow:hidden;
background:url('../assets/admin-agents-approval.avif') center/cover no-repeat fixed;
position:relative;
}

body::after{
content:'';
position:fixed;
top:0; left:0;
width:100%; height:100%;
background:rgba(0,0,0,0.35);
z-index:-1;
}

.scroll-wrapper{
height:100%;
overflow:auto;
-ms-overflow-style:none;
scrollbar-width:none;
}
.scroll-wrapper::-webkit-scrollbar{display:none;}

.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px 30px;
}

.logo{
font-size:1.5rem;
font-weight:bold;
background:linear-gradient(135deg,#ff7e5f,#feb47b);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}

.nav-buttons{
display:flex;
gap:10px;
}

.btn{
text-decoration:none;
padding:12px 25px;
border-radius:10px;
font-weight:bold;
color:white;
}

/* DASHBOARD */
.dashboard{
background:linear-gradient(135deg,#ffd200,#f7971e);
}

/* LOGOUT */
.logout{
background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

.container{
max-width:900px;
margin:50px auto;
background:#fff;
padding:25px;
border-radius:20px;
box-shadow:0 10px 30px rgba(0,0,0,0.25);
}

h2{text-align:center;margin-bottom:20px;color:#ff7e5f;}

table{
width:100%;
border-collapse:collapse;
}
th,td{
padding:12px;
border-bottom:1px solid #ddd;
text-align:left;
}

button{
padding:6px 14px;
border:none;
border-radius:8px;
cursor:pointer;
font-weight:bold;
margin-right:5px;
color:white;
}
button.approve{background:linear-gradient(135deg,#28a745,#2ecc71);}
button.reject{background:linear-gradient(135deg,#e74c3c,#ff4d4d);}

p.success{color:#28a745;text-align:center;margin-bottom:15px;}
p.error{color:#dc3545;text-align:center;margin-bottom:15px;}
</style>
</head>

<body>
<div class="scroll-wrapper">

<div class="navbar">
<div class="logo">Admin Panel</div>

<div class="nav-buttons">
<a href="dashboard.php" class="btn dashboard">Dashboard</a>
<a href="../logout.php" class="btn logout">Logout</a>
</div>

</div>

<div class="container">
<h2>Pending Agent Approvals</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<table>
<tr>
<th>Name</th>
<th>Email</th>
<th>Branch</th>
<th>Action</th>
</tr>

<?php if($agents && $agents->num_rows>0){
while($a=$agents->fetch_assoc()){ ?>
<tr>
<td><?= htmlspecialchars($a['name']) ?></td>
<td><?= htmlspecialchars($a['email']) ?></td>
<td><?= htmlspecialchars($a['branch']) ?></td>
<td>
<form method="POST" style="display:flex; gap:5px;">
<input type="hidden" name="agent_id" value="<?= $a['agent_id'] ?>">
<button type="submit" name="approve" class="approve">Approve</button>
<button type="submit" name="reject" class="reject">Reject</button>
</form>
</td>
</tr>
<?php }}else{ ?>
<tr><td colspan="4">No pending agents</td></tr>
<?php } ?>
</table>

</div>
</div>
</body>
</html>
