<?php
// config.php - Database connection settings

$servername = "localhost";
$username   = "your_db_user";           // ← change this
$password   = "your_db_password";       // ← change this
$dbname     = "house_info";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>

