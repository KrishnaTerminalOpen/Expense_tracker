<?php
// api/add_expense.php
// Records a new expense and checks against budget thresholds

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

$amount            = isset($data['amount']) ? (float)$data['amount'] : (float)($_POST['amount'] ?? 0);
$category_id       = isset($data['category_id']) ? (int)$data['category_id'] : (int)($_POST['category_id'] ?? 0);
$payment_method_id = isset($data['payment_method_id']) ? (int)$data['payment_method_id'] : (int)($_POST['payment_method_id'] ?? 0);
$expense_date      = trim($data['expense_date'] ?? $_POST['expense_date'] ?? '');
$description       = trim($data['description'] ?? $_POST['description'] ?? '');

// 1. Validate inputs
if ($amount <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter a valid amount greater than 0."
    ]);
    exit();
}

if ($category_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Please select a valid expense category."
    ]);
    exit();
}

if ($payment_method_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Please select a valid payment method."
    ]);
    exit();
}

if (empty($expense_date) || !strtotime($expense_date)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please provide a valid expense date."
    ]);
    exit();
}

// 2. Verify category exists and belongs to system or user
$catSql = "SELECT id FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
$catStmt = mysqli_prepare($conn, $catSql);
mysqli_stmt_bind_param($catStmt, "ii", $category_id, $user_id);
mysqli_stmt_execute($catStmt);
mysqli_stmt_store_result($catStmt);
if (mysqli_stmt_num_rows($catStmt) === 0) {
    mysqli_stmt_close($catStmt);
    echo json_encode([
        "status" => "error",
        "message" => "Selected category is invalid or inaccessible."
    ]);
    exit();
}
mysqli_stmt_close($catStmt);

// 3. Verify payment method exists and belongs to system or user
$pmSql = "SELECT id FROM payment_methods WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
$pmStmt = mysqli_prepare($conn, $pmSql);
mysqli_stmt_bind_param($pmStmt, "ii", $payment_method_id, $user_id);
mysqli_stmt_execute($pmStmt);
mysqli_stmt_store_result($pmStmt);
if (mysqli_stmt_num_rows($pmStmt) === 0) {
    mysqli_stmt_close($pmStmt);
    echo json_encode([
        "status" => "error",
        "message" => "Selected payment method is invalid or inaccessible."
    ]);
    exit();
}
mysqli_stmt_close($pmStmt);

// 4. Insert expense into database
$insertSql = "INSERT INTO expenses (user_id, category_id, payment_method_id, amount, expense_date, description) 
              VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insertSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare insert query: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iiidss", $user_id, $category_id, $payment_method_id, $amount, $expense_date, $description);

if (mysqli_stmt_execute($stmt)) {
    $expenseId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 5. Check budget thresholds using shared helper function
    $budgetCheck = check_budget_status($conn, $user_id, $category_id, $expense_date);

    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message"       => "Expense added successfully.",
            "id"            => $expenseId,
            "budget_status" => $budgetCheck
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to record expense. Please try again."
    ]);
}
?>
