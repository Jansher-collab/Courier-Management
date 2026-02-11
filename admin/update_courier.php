<?php
session_start();
include('../includes/auth.php');
include('../includes/db.php');
include('../includes/mail.php');

requireAdmin();

// Get courier ID from URL
$courier_id = intval($_GET['id'] ?? 0);
if ($courier_id <= 0) {
    die("Invalid courier ID.");
}

// Allowed statuses
$valid_statuses = ['booked', 'in-progress', 'delivered'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $from_location = trim($_POST['from_location'] ?? '');
    $to_location   = trim($_POST['to_location'] ?? '');
    $courier_type  = trim($_POST['courier_type'] ?? '');
    $delivery_date = $_POST['delivery_date'] ?? '';
    $status        = trim($_POST['status'] ?? '');
    $agent_id      = ($_POST['agent_id'] !== '') ? intval($_POST['agent_id']) : NULL;

    if (!$from_location || !$to_location || !$courier_type || !$delivery_date || !in_array($status, $valid_statuses)) {
        $error = "All fields are required and status must be valid.";
    } else {

        $stmt = $conn->prepare("
            UPDATE couriers SET
                from_location=?,
                to_location=?,
                courier_type=?,
                delivery_date=?,
                status=?,
                agent_id=?
            WHERE courier_id=?
        ");

        // FIXED BIND TYPES
        $stmt->bind_param(
            "sssssii",
            $from_location,
            $to_location,
            $courier_type,
            $delivery_date,
            $status,
            $agent_id,
            $courier_id
        );

        if ($stmt->execute()) {

            $success = "Courier updated successfully.";

            $stmt_log = $conn->prepare("
                INSERT INTO courier_logs
                (courier_id, status, message, notified_via)
                VALUES (?, ?, ?, ?)
            ");

            $msg = "Courier updated by admin";
            $via = "none";

            $stmt_log->bind_param("isss", $courier_id, $status, $msg, $via);
            $stmt_log->execute();

        } else {
            $error = "Failed to update courier: " . $stmt->error;
        }
    }
}

// Fetch courier
$stmt = $conn->prepare("
    SELECT c.*, 
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email
    FROM couriers c
    LEFT JOIN customers s ON c.sender_id = s.customer_id
    LEFT JOIN customers r ON c.receiver_id = r.customer_id
    WHERE c.courier_id = ? LIMIT 1
");
$stmt->bind_param("i", $courier_id);
$stmt->execute();
$courier = $stmt->get_result()->fetch_assoc();

if (!$courier) die("Courier not found.");

$agents = [];
$result = $conn->query("
    SELECT a.agent_id, u.name, a.branch
    FROM agents a
    JOIN users u ON a.user_id=u.user_id
    ORDER BY branch ASC
");

while($row = $result->fetch_assoc()){
    $agents[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Courier</title>
<style>
body{font-family:Arial;padding:20px;}
input,select,button{width:100%;padding:8px;margin:6px 0;}
button{background:#4CAF50;color:white;border:none;}
p.success{color:green;}
p.error{color:red;}
</style>
</head>
<body>

<h2>Update Courier #<?= $courier_id ?></h2>

<?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
<?php if(!empty($success)) echo "<p class='success'>$success</p>"; ?>

<h3>Sender</h3>
<p><?= htmlspecialchars($courier['sender_name']) ?></p>

<h3>Receiver</h3>
<p><?= htmlspecialchars($courier['receiver_name']) ?></p>

<form method="POST">

<label>From</label>
<input type="text" name="from_location" value="<?= htmlspecialchars($courier['from_location']) ?>" required>

<label>To</label>
<input type="text" name="to_location" value="<?= htmlspecialchars($courier['to_location']) ?>" required>

<label>Type</label>
<input type="text" name="courier_type" value="<?= htmlspecialchars($courier['courier_type']) ?>" required>

<label>Delivery Date</label>
<input type="date" name="delivery_date" value="<?= $courier['delivery_date'] ?>" required>

<label>Status</label>
<select name="status">
<?php foreach($valid_statuses as $s): ?>
<option value="<?= $s ?>" <?= ($courier['status']==$s?'selected':'') ?>>
<?= ucfirst(str_replace('-',' ',$s)) ?>
</option>
<?php endforeach; ?>
</select>

<label>Assign Agent</label>
<select name="agent_id">
<option value="">No Agent</option>
<?php foreach($agents as $a): ?>
<option value="<?= $a['agent_id'] ?>" <?= ($courier['agent_id']==$a['agent_id']?'selected':'') ?>>
<?= htmlspecialchars($a['name']." - ".$a['branch']) ?>
</option>
<?php endforeach; ?>
</select>

<button type="submit">Update Courier</button>
</form>

</body>
</html>
