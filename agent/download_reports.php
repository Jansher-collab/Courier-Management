<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

// ---------------------------
// Ensure user is logged in
// ---------------------------
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$agent_id = $_SESSION['agent_id'] ?? null;

// ---------------------------
// For agents: fetch agent info if not in session
// ---------------------------
if ($role === 'agent' && !$agent_id) {
    $stmt = $conn->prepare("SELECT agent_id, branch FROM agents WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        die("No agent profile found for this user.");
    }
    $agent = $res->fetch_assoc();
    $agent_id = $agent['agent_id'];
    $_SESSION['agent_id'] = $agent_id;
    $_SESSION['branch']   = $agent['branch'];
}

// ---------------------------
// Build WHERE clause based on role
// ---------------------------
$where = "";
$params = [];
$types = "";

if ($role === 'agent') {
    $where = "WHERE c.agent_id = ? OR c.agent_id IS NULL";
    $params[] = $agent_id;
    $types .= "i";
} elseif ($role !== 'admin') {
    die("You do not have permission to download reports.");
}

// ---------------------------
// Prepare SQL query
// ---------------------------
$sql = "
SELECT 
    c.courier_id,
    s.name AS sender_name,
    r.name AS receiver_name,
    c.from_location,
    c.to_location,
    c.courier_type,
    c.status,
    c.delivery_date,
    u.name AS created_by,
    COALESCE(a.branch, 'Unassigned') AS agent_branch
FROM couriers c
JOIN customers s ON c.sender_id = s.customer_id
JOIN customers r ON c.receiver_id = r.customer_id
LEFT JOIN users u ON c.created_by = u.user_id
LEFT JOIN agents a ON c.agent_id = a.agent_id
$where
ORDER BY c.courier_id ASC
";

$stmt = $conn->prepare($sql);

// Bind parameters if agent
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// ---------------------------
// Output CSV
// ---------------------------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="courier_report.csv"');

$output = fopen('php://output', 'w');

// CSV header
fputcsv($output, [
    'Courier ID','Sender','Receiver','From','To','Type','Status','Delivery Date','Created By','Agent Branch'
]);

// Write rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['courier_id'],
        $row['sender_name'],
        $row['receiver_name'],
        $row['from_location'],
        $row['to_location'],
        $row['courier_type'],
        $row['status'],
        $row['delivery_date'],
        $row['created_by'],
        $row['agent_branch']
    ]);
}

fclose($output);
exit();
?>
