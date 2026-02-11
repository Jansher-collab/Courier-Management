<?php
// functions.php
// Common reusable functions for Courier Management System

include_once 'db.php';

function getShipmentCountByStatus($status) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM couriers WHERE status = ?"
    );
    $stmt->bind_param("s", $status);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

function getAllStatusCounts() {
    return [
        'booked'     => getShipmentCountByStatus('booked'),
        'in_transit' => getShipmentCountByStatus('in_transit'),
        'delivered'  => getShipmentCountByStatus('delivered')
    ];
}

function getAgentStatusCounts($agent_id) {
    global $conn;

    $statuses = ['booked', 'in_transit', 'delivered'];
    $counts = [];

    foreach ($statuses as $status) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total 
             FROM couriers 
             WHERE status = ? AND agent_id = ?"
        );
        $stmt->bind_param("si", $status, $agent_id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        $counts[$status] = $result['total'] ?? 0;
    }

    return $counts;
}

function getAllCouriers() {
    global $conn;

    $sql = "SELECT * FROM couriers ORDER BY created_at DESC";
    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getCouriersByAgent($agent_id) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT * FROM couriers 
         WHERE agent_id = ? 
         ORDER BY created_at DESC"
    );
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllCustomers() {
    global $conn;

    $sql = "SELECT * FROM customers ORDER BY created_at DESC";
    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getCouriersByDate($from, $to) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT * FROM couriers 
         WHERE DATE(created_at) BETWEEN ? AND ? 
         ORDER BY created_at DESC"
    );
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getCouriersByCity($city) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT * FROM couriers 
         WHERE from_city = ? OR to_city = ? 
         ORDER BY created_at DESC"
    );
    $stmt->bind_param("ss", $city, $city);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
