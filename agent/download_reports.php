<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

// Ensure logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$agent_id = $_SESSION['agent_id'] ?? null;

// Fetch agent info if missing
if ($role === 'agent' && !$agent_id) {
    $stmt = $conn->prepare("SELECT agent_id, branch FROM agents WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) die("No agent profile found.");
    $agent = $res->fetch_assoc();
    $agent_id = $agent['agent_id'];
    $_SESSION['agent_id'] = $agent_id;
    $_SESSION['branch']   = $agent['branch'];
}

// Build query for agent
$where = "WHERE c.agent_id = ?";
$params = [$agent_id];
$types  = "i";

// Query
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
    a.branch AS agent_branch
FROM couriers c
JOIN customers s ON c.sender_id = s.customer_id
JOIN customers r ON c.receiver_id = r.customer_id
LEFT JOIN users u ON c.created_by = u.user_id
LEFT JOIN agents a ON c.agent_id = a.agent_id
$where
ORDER BY c.courier_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// CSV file
$filename = 'courier_report_agent_' . date('Ymd_His') . '.csv';
$reports_dir = __DIR__ . '/../reports/';
if (!is_dir($reports_dir)) mkdir($reports_dir, 0755, true);
$filepath = $reports_dir . $filename;
$output = fopen($filepath, 'w');

// CSV header
fputcsv($output, [
    'Courier ID','Sender','Receiver','From','To','Type','Status','Delivery Date','Created By','Agent Branch'
]);

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

// Insert into reports table
$report_type      = 'courier';
$generated_by     = $_SESSION['user_id'];
$from_date_db     = null;
$to_date_db       = null;
$branch_db        = $_SESSION['branch'] ?? 'Unassigned';
$file_path_db     = 'reports/' . $filename;

$report_stmt = $conn->prepare("
    INSERT INTO reports
    (report_type, generated_by, from_date, to_date, branch, file_path, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$report_stmt->bind_param(
    "sissss",
    $report_type,
    $generated_by,
    $from_date_db,
    $to_date_db,
    $branch_db,
    $file_path_db
);
$report_stmt->execute();

// Serve CSV
if (file_exists($filepath)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filepath);
    exit();
} else {
    die("Failed to generate report.");
}
?>
