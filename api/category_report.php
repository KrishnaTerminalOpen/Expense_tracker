<?php
// api/category_report.php
// Returns spending breakdown by category for a selected month/year using GROUP BY

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

// Read month and year from query params or default to current
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// 1. Category Breakdown Query with GROUP BY
$catSql = "SELECT c.id, c.name, 
                  COALESCE(SUM(e.amount), 0) AS total_amount, 
                  COUNT(e.id) AS transaction_count
           FROM categories c
           JOIN expenses e ON e.category_id = c.id
           WHERE e.user_id = ? AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
           GROUP BY c.id, c.name
           ORDER BY total_amount DESC";

$stmt = mysqli_prepare($conn, $catSql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$categories = [];
$totalSpent = 0.0;
while ($row = mysqli_fetch_assoc($res)) {
    $row['total_amount'] = (float)$row['total_amount'];
    $row['transaction_count'] = (int)$row['transaction_count'];
    $totalSpent += $row['total_amount'];
    $categories[] = $row;
}
mysqli_stmt_close($stmt);

// Calculate percentage for each category
foreach ($categories as &$cat) {
    $cat['percent'] = ($totalSpent > 0) ? round(($cat['total_amount'] / $totalSpent) * 100, 1) : 0;
}
unset($cat);

// 2. Highest Single Expense in this month
$highSql = "SELECT e.id, e.amount, e.expense_date, e.description, c.name AS category_name
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            WHERE e.user_id = ? AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
            ORDER BY e.amount DESC
            LIMIT 1";
$highStmt = mysqli_prepare($conn, $highSql);
mysqli_stmt_bind_param($highStmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($highStmt);
$highRes = mysqli_stmt_get_result($highStmt);
$highestExpense = mysqli_fetch_assoc($highRes);
if ($highestExpense) {
    $highestExpense['amount'] = (float)$highestExpense['amount'];
}
mysqli_stmt_close($highStmt);

// 3. Average Daily Spend Calculation
$daysInMonth = (int)date('t', strtotime("$year-$month-01"));
// If current month and year, divide by elapsed days; otherwise full month days
$isCurrentMonth = ($month === (int)date('n') && $year === (int)date('Y'));
$divisorDays = $isCurrentMonth ? max(1, (int)date('j')) : $daysInMonth;
$avgDailySpend = round($totalSpent / $divisorDays, 2);

// Top category name
$topCategoryName = count($categories) > 0 ? $categories[0]['name'] : 'None';
$topCategoryAmount = count($categories) > 0 ? $categories[0]['total_amount'] : 0.0;

mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data" => [
        "month"             => $month,
        "year"              => $year,
        "total_spent"       => round($totalSpent, 2),
        "avg_daily_spend"   => $avgDailySpend,
        "top_category"      => [
            "name"   => $topCategoryName,
            "amount" => $topCategoryAmount
        ],
        "highest_expense"   => $highestExpense,
        "categories"        => $categories
    ]
]);
?>
