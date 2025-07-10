<?php
// Local Database Configuration for WSL2 Development
$servername = 'localhost';
$username = 'sd3_user'; // WSL2 MySQL user
$password = 'sd3_password_123'; // Password we just created
$dbname = 'sd3'; // Using your existing sd3 database
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

echo "<!-- WSL2 database connected successfully -->";
?> 