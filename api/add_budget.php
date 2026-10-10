<?php
// api/add_budget.php
// Creates an overall monthly budget or category-specific budget

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

$categoryIdRaw = $data['category_id'] ?? $_POST['category_id'] ?? null;
$category_id   = ($categoryIdRaw !== null && $categoryIdRaw !== "" && (int)$categoryIdRaw > 0) ? (int)$categoryIdRaw : null;
$month         = isset($data['month']) ? (int)$data['month'] : (int)($_POST['month'] ?? 0);
$year          = isset($data['year']) ? (int)$data['year'] : (int)($_POST['year'] ?? 0);
$amount        = isset($data['amount']) ? (float)$data['amount'] : (float)($_POST['amount'] ?? 0);
$alert_percent = isset($data['alert_percent']) ? (int)$data['alert_percent'] : (int)($_POST['alert_percent'] ?? 80);

// 1. Validation
if ($month < 1 || $month > 12) {
    echo json_encode([
        "status" => "error",
        "message" => "Please select a valid month (1 - 12)."
    ]);
    exit();
}

if ($year < 2000 || $year > 2100) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter a valid 4-digit year."
    ]);
    exit();
}

if ($amount <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Budget amount must be greater than 0."
    ]);
    exit();
}

if ($alert_percent < 1 || $alert_percent > 100) {
    $alert_percent = 80;
}

// 2. Validate category if provided
if ($category_id !== null) {
    $catSql = "SELECT id FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
    $catStmt = mysqli_prepare($conn, $catSql);
    mysqli_stmt_bind_param($catStmt, "ii", $category_id, $user_id);
    mysqli_stmt_execute($catStmt);
    mysqli_stmt_store_result($catStmt);
    if (mysqli_stmt_num_rows($catStmt) === 0) {
        mysqli_stmt_close($catStmt);
        echo json_encode([
            "status" => "error",
            "message" => "Selected category is invalid."
        ]);
        exit();
    }
    mysqli_stmt_close($catStmt);
}

// 3. Check for existing budget for same category/overall in that month/year
if ($category_id === null) {
    $checkSql = "SELECT id FROM budgets WHERE user_id = ? AND category_id IS NULL AND month = ? AND year = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "iii", $user_id, $month, $year);
} else {
    $checkSql = "SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "iiii", $user_id, $category_id, $month, $year);
}

mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    mysqli_stmt_close($checkStmt);
    echo json_encode([
        "status" => "error",
        "message" => "A budget for this timeframe and category already exists. Please edit the existing budget."
    ]);
    exit();
}
mysqli_stmt_close($checkStmt);

// 4. Insert new budget
if ($category_id === null) {
    $insertSql = "INSERT INTO budgets (user_id, category_id, month, year, amount, alert_percent) 
                  VALUES (?, NULL, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($stmt, "iiidi", $user_id, $month, $year, $amount, $alert_percent);
} else {
    $insertSql = "INSERT INTO budgets (user_id, category_id, month, year, amount, alert_percent) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($stmt, "iiiidi", $user_id, $category_id, $month, $year, $amount, $alert_percent);
}

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message"   => "Budget saved successfully.",
            "budget_id" => $newId
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to save budget."
    ]);
}
?>
