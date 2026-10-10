<?php
// api/update_expense.php
// Updates an existing expense owned by the logged-in user

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
require_once '../config/helpers.php';
$user_id = $_SESSION['user_id'];

// Read input (support JSON or POST)
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$id                = isset($data['id']) ? (int)$data['id'] : (int)($_POST['id'] ?? 0);
$amount            = isset($data['amount']) ? (float)$data['amount'] : (float)($_POST['amount'] ?? 0);
$category_id       = isset($data['category_id']) ? (int)$data['category_id'] : (int)($_POST['category_id'] ?? 0);
$payment_method_id = isset($data['payment_method_id']) ? (int)$data['payment_method_id'] : (int)($_POST['payment_method_id'] ?? 0);
$expense_date      = trim($data['expense_date'] ?? $_POST['expense_date'] ?? '');
$description       = trim($data['description'] ?? $_POST['description'] ?? '');

// 1. Validation
if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid Expense ID is required."
    ]);
    exit();
}

if ($amount <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter a valid amount greater than 0."
    ]);
    exit();
}

if ($category_id <= 0 || $payment_method_id <= 0 || empty($expense_date)) {
    echo json_encode([
        "status" => "error",
        "message" => "All required fields must be filled."
    ]);
    exit();
}

// 2. Check ownership of this expense
$checkSql = "SELECT id FROM expenses WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "Expense record not found or permission denied."
    ]);
    exit();
}
mysqli_stmt_close($stmt);

// 3. Update expense record
$updateSql = "UPDATE expenses 
              SET category_id = ?, payment_method_id = ?, amount = ?, expense_date = ?, description = ? 
              WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iidssii", $category_id, $payment_method_id, $amount, $expense_date, $description, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Check budget thresholds after update
    $budgetCheck = check_budget_status($conn, $user_id, $category_id, $expense_date);

    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message"       => "Expense updated successfully.",
            "id"            => $id,
            "budget_status" => $budgetCheck
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update expense."
    ]);
}
?>
