<?php
// api/get_payment_methods.php
// Fetches system default payment methods (user_id IS NULL) and the user's custom payment methods

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

// Select system defaults (user_id IS NULL) or user's custom payment methods
$sql = "SELECT id, user_id, name, 
        CASE WHEN user_id IS NULL THEN 1 ELSE 0 END AS is_default 
        FROM payment_methods 
        WHERE user_id IS NULL OR user_id = ? 
        ORDER BY is_default DESC, name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$methods = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['is_default'] = (int)$row['is_default'];
    $methods[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data" => $methods
]);
?>
