<?php
// admin/download_reports.php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

// Ensure reports folder exists
$reports_dir = __DIR__ . '/../reports/';
if (!is_dir($reports_dir)) {
    mkdir($reports_dir, 0755, true);
}

// Get filters
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$branch    = $_GET['branch'] ?? '';

// Base query
$query = "
    SELECT c.courier_id, c.sender_id, c.receiver_id, c.from_location, c.to_location,
           c.courier_type, c.status, c.delivery_date, c.created_by, c.agent_id,
           c.created_at, c.updated_at,
           s.name AS sender_name, s.email AS sender_email,
           r.name AS receiver_name, r.email AS receiver_email,
           a.branch AS agent_branch
    FROM couriers c
    LEFT JOIN customers s ON c.sender_id = s.customer_id
    LEFT JOIN customers r ON c.receiver_id = r.customer_id
    LEFT JOIN agents a ON c.agent_id = a.agent_id
    WHERE 1=1
";

$params = [];
$types  = "";

// Filters
if ($from_date !== '') {
    $query .= " AND c.delivery_date >= ?";
    $params[] = $from_date;
    $types   .= "s";
}
if ($to_date !== '') {
    $query .= " AND c.delivery_date <= ?";
    $params[] = $to_date;
    $types   .= "s";
}
if ($branch !== '') {
    $query .= " AND a.branch = ?";
    $params[] = $branch;
    $types   .= "s";
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// CSV file
$filename = 'courier_report_' . date('Ymd_His') . '.csv';
$filepath = $reports_dir . $filename;
$output = fopen($filepath, 'w');

// Headers
fputcsv($output, [
    'Courier ID','Sender ID','Sender Name','Sender Email',
    'Receiver ID','Receiver Name','Receiver Email',
    'From Location','To Location','Courier Type','Status',
    'Delivery Date','Created By','Agent ID','Agent Branch',
    'Created At','Updated At'
]);

// Data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['courier_id'],
        $row['sender_id'],
        $row['sender_name'],
        $row['sender_email'],
        $row['receiver_id'],
        $row['receiver_name'],
        $row['receiver_email'],
        $row['from_location'],
        $row['to_location'],
        $row['courier_type'],
        $row['status'],
        $row['delivery_date'],
        $row['created_by'],
        $row['agent_id'],
        $row['agent_branch'],
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);

// Log report
$report_type    = 'courier';
$generated_by   = $_SESSION['user_id'];
$from_date_param = $from_date ?: null;
$to_date_param   = $to_date ?: null;
$branch_param    = $branch ?: null;
$file_path_db    = 'reports/' . $filename; // relative path for DB

$report_stmt = $conn->prepare("
    INSERT INTO reports
    (report_type, generated_by, from_date, to_date, branch, file_path, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$report_stmt->bind_param(
    "sissss",
    $report_type,
    $generated_by,
    $from_date_param,
    $to_date_param,
    $branch_param,
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
