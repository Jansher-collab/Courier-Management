<?php
// db.php
// Database connection for Courier Management System

$host = "localhost";        // Replace with your local DB host, e.g., 'localhost'
$user = "root";        // Replace with your MySQL username
$password = "";    // Replace with your MySQL password
$dbname = "courier_management";      // Replace with your database name

// Create connection using MySQLi with error handling
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
if (!$conn->set_charset("utf8")) {
    die("Error loading character set utf8: " . $conn->error);
}

// Use $conn in all PHP files for queries
?>
