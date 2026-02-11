<?php
// admin/delete_courier.php
include('../includes/auth.php');
include('../includes/db.php');

requireAdmin();

$courier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courier_id <= 0) {
    die("Invalid courier ID.");
}

// Optional: Delete related logs first (if you want to keep DB consistent)
$stmt_logs = $conn->prepare("DELETE FROM courier_logs WHERE courier_id=?");
$stmt_logs->bind_param("i", $courier_id);
$stmt_logs->execute();

// Delete courier
$stmt = $conn->prepare("DELETE FROM couriers WHERE courier_id = ?");
$stmt->bind_param("i", $courier_id);

if ($stmt->execute()) {
    // Redirect back to view_couriers.php with success message
    header("Location: view_couriers.php?deleted=1");
    exit();
} else {
    // Redirect back with error message
    header("Location: view_couriers.php?deleted=0&error=" . urlencode($stmt->error));
    exit();
}
?>
