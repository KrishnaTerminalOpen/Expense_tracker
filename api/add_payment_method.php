<?php
// api/add_payment_method.php
// Adds a new custom payment method for the logged-in user

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized. Please log in first."
    ]);
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

// Read input
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);
$name = trim($data['name'] ?? $_POST['name'] ?? '');

// Validate input
if (empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Payment method name cannot be empty."
    ]);
    exit();
}

if (strlen($name) > 100) {
    echo json_encode([
        "status" => "error",
        "message" => "Payment method name cannot exceed 100 characters."
    ]);
    exit();
}

// Check for duplicate name
$checkSql = "SELECT id FROM payment_methods WHERE (user_id IS NULL OR user_id = ?) AND LOWER(name) = LOWER(?)";
$stmt = mysqli_prepare($conn, $checkSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "is", $user_id, $name);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "A payment method with this name already exists."
    ]);
    exit();
}
mysqli_stmt_close($stmt);

// Insert new payment method
$insertSql = "INSERT INTO payment_methods (user_id, name) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $insertSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "is", $user_id, $name);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Payment method added successfully.",
            "id" => $newId,
            "name" => $name
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to add payment method."
    ]);
}
?>
