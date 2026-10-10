<?php
// api/get_expenses.php
// Retrieves expense records for the logged-in user

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

// Optional filters (month, year)
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year  = isset($_GET['year']) ? (int)$_GET['year'] : 0;

$sql = "SELECT e.id, e.user_id, e.category_id, e.payment_method_id, 
               e.amount, e.expense_date, e.description, e.created_at,
               c.name AS category_name,
               p.name AS payment_method_name
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        JOIN payment_methods p ON e.payment_method_id = p.id
        WHERE e.user_id = ?";

if ($month > 0 && $year > 0) {
    $sql .= " AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
}

$sql .= " ORDER BY e.expense_date DESC, e.id DESC LIMIT 100";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query: " . mysqli_error($conn)
    ]);
    exit();
}

if ($month > 0 && $year > 0) {
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
} else {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$expenses = [];
$totalSum = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $row['amount'] = (float)$row['amount'];
    $totalSum += $row['amount'];
    $expenses[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data" => [
        "expenses"    => $expenses,
        "total_count" => count($expenses),
        "total_sum"   => round($totalSum, 2)
    ]
]);
?>
