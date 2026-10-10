<?php
// config/db.php
// Dual-environment database connection (Local XAMPP & Live Hosting / InfinityFree)

// Detect if running on local development server (XAMPP)
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost   = $_SERVER['HTTP_HOST'] ?? '';

$is_localhost = (
    $serverName === 'localhost' || 
    $serverName === '127.0.0.1' || 
    $serverName === '::1' ||
    strpos($httpHost, 'localhost') !== false || 
    strpos($httpHost, '127.0.0.1') !== false
);

if ($is_localhost) {
    // ========================================================
    // 1. LOCAL ENVIRONMENT (XAMPP on your computer)
    // ========================================================
    $host     = "localhost";
    $username = "root";
    $password = "";
    $database = "spendwise";
} else {
    // ========================================================
    // 2. LIVE PRODUCTION ENVIRONMENT (InfinityFree / cPanel)
    // Replace the placeholders below with the details from your:
    // InfinityFree Client Area -> Manage -> MySQL Details
    // ========================================================
    $host     = getenv('DB_HOST') ?: "sqlXXX.infinityfree.com"; // MySQL Host Name (e.g., sql304.infinityfree.com)
    $username = getenv('DB_USER') ?: "if0_XXXXXX";              // MySQL User Name (e.g., if0_38123456)
    $password = getenv('DB_PASS') ?: "YOUR_VPANEL_PASSWORD";   // vPanel Account Password
    $database = getenv('DB_NAME') ?: "if0_XXXXXX_spendwise";    // MySQL Database Name (e.g., if0_38123456_spendwise)
}

// Establish connection using procedural mysqli
$conn = @mysqli_connect($host, $username, $password, $database);

// Check if connection was successful
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed. Please check config/db.php credentials. (" . mysqli_connect_error() . ")"
    ]);
    exit();
}

// Set charset to utf8mb4 for universal currency symbol & text support
mysqli_set_charset($conn, "utf8mb4");
?>
