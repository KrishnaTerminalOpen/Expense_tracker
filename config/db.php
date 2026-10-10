<?php
// config/db.php
// Database connection configuration for SpendWise on XAMPP

$host     = "localhost"; // MySQL server hostname
$username = "root";      // Default XAMPP MySQL username
$password = "";          // Default XAMPP MySQL password is empty
$database = "spendwise"; // Database name

// Establish connection using procedural mysqli
$conn = mysqli_connect($host, $username, $password, $database);

// Check if connection was successful
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]);
    exit();
}

// Set charset to utf8mb4 for universal currency symbol & text support
mysqli_set_charset($conn, "utf8mb4");
?>
