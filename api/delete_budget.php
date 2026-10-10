<?php
// api/delete_budget.php
// Deletes a budget owned by the logged-in user

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
$id = isset($data['id']) ? (int)$data['id'] : (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid budget ID is required."
    ]);
    exit();
}

// 1. Verify ownership before deletion
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

// 2. Delete budget
$deleteSql = "DELETE FROM budgets WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Budget deleted successfully.",
            "id"      => $id
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete budget."
    ]);
}
?>
