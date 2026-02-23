<?php
$host = "localhost";        // Replace with your local DB host, e.g., 'localhost'
$user = "root";        // Replace with your MySQL username
$password = "";    // Replace with your MySQL password
$dbname = "courier_management";      // Replace with your database name
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}
if (!$conn->set_charset("utf8")) {
    die("Error loading character set utf8: " . $conn->error);
}

?>
