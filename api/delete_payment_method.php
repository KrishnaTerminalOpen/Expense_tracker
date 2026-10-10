<?php
// api/delete_payment_method.php
// Deletes a custom payment method if it belongs to the user and is not linked to any expenses

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
$id = isset($data['id']) ? (int)$data['id'] : (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid Payment Method ID is required."
    ]);
    exit();
}

// 1. Verify payment method exists and check ownership
$checkSql = "SELECT user_id, name FROM payment_methods WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$method = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$method) {
    echo json_encode([
        "status" => "error",
        "message" => "Payment method not found."
    ]);
    exit();
}

// Reject deletion if owned by another user
if ($method['user_id'] !== null && (int)$method['user_id'] !== $user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "You do not have permission to delete this payment method."
    ]);
    exit();
}

// 2. Check if payment method is linked to any expenses
$expenseSql = "SELECT COUNT(*) AS total FROM expenses WHERE payment_method_id = ?";
$stmt = mysqli_prepare($conn, $expenseSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$expResult = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($expResult);
$usedCount = (int)($row['total'] ?? 0);
mysqli_stmt_close($stmt);

if ($usedCount > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cannot delete '" . htmlspecialchars($method['name']) . "' because it is already used in " . $usedCount . " expense record(s)."
    ]);
    exit();
}

// 3. Delete the payment method
$deleteSql = "DELETE FROM payment_methods WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
$stmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Payment method deleted successfully.",
            "id" => $id
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete payment method."
    ]);
}
?>
