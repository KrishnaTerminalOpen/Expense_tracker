<?php
// api/update_budget.php
// Updates an existing budget owned by the logged-in user

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

// Read input (support JSON or POST)
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$id            = isset($data['id']) ? (int)$data['id'] : (int)($_POST['id'] ?? 0);
$amount        = isset($data['amount']) ? (float)$data['amount'] : (float)($_POST['amount'] ?? 0);
$alert_percent = isset($data['alert_percent']) ? (int)$data['alert_percent'] : (int)($_POST['alert_percent'] ?? 80);

// Validation
if ($id <= 0 || $amount <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid budget ID and amount greater than 0 are required."
    ]);
    exit();
}

if ($alert_percent < 1 || $alert_percent > 100) {
    $alert_percent = 80;
}

// 1. Verify ownership
$checkSql = "SELECT id FROM budgets WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "Budget record not found or permission denied."
    ]);
    exit();
}
mysqli_stmt_close($stmt);

// 2. Update budget amount and alert threshold
$updateSql = "UPDATE budgets SET amount = ?, alert_percent = ? WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "diii", $amount, $alert_percent, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Budget updated successfully.",
            "id"      => $id
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update budget."
    ]);
}
?>
