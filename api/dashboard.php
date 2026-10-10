<?php
// api/dashboard.php
// Returns dashboard statistics: today, this week, this month spending, budget remaining, and last 5 expenses

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

// 1. Calculate Today's Total Spending using SUM
$todaySql = "SELECT COALESCE(SUM(amount), 0) AS total_today 
             FROM expenses 
             WHERE user_id = ? AND expense_date = CURDATE()";
$stmt = mysqli_prepare($conn, $todaySql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$totalToday = (float)$row['total_today'];
mysqli_stmt_close($stmt);

// 2. Calculate This Week's Total Spending using SUM (mode 1: Monday is first day of week)
$weekSql = "SELECT COALESCE(SUM(amount), 0) AS total_week 
            FROM expenses 
            WHERE user_id = ? AND YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)";
$stmt = mysqli_prepare($conn, $weekSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$totalWeek = (float)$row['total_week'];
mysqli_stmt_close($stmt);

// 3. Calculate This Month's Total Spending using SUM
$monthSql = "SELECT COALESCE(SUM(amount), 0) AS total_month 
             FROM expenses 
             WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $monthSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$totalMonth = (float)$row['total_month'];
mysqli_stmt_close($stmt);

// 4. Retrieve Current Month's Budget & calculate remaining
// First check for Overall Monthly Budget (category_id IS NULL)
$budgetSql = "SELECT amount FROM budgets 
              WHERE user_id = ? AND category_id IS NULL 
                AND month = MONTH(CURDATE()) AND year = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $budgetSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$hasBudget = false;
$monthlyBudget = 0.0;

if ($bRow = mysqli_fetch_assoc($res)) {
    $hasBudget = true;
    $monthlyBudget = (float)$bRow['amount'];
} else {
    // If no overall budget, check sum of all category budgets for this month
    mysqli_stmt_close($stmt);
    $catBudgetSql = "SELECT COALESCE(SUM(amount), 0) AS total_cat_budget FROM budgets 
                     WHERE user_id = ? AND month = MONTH(CURDATE()) AND year = YEAR(CURDATE())";
    $stmt = mysqli_prepare($conn, $catBudgetSql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $catRes = mysqli_stmt_get_result($stmt);
    $catRow = mysqli_fetch_assoc($catRes);
    if ((float)$catRow['total_cat_budget'] > 0) {
        $hasBudget = true;
        $monthlyBudget = (float)$catRow['total_cat_budget'];
    }
}
mysqli_stmt_close($stmt);

$remainingBudget = $hasBudget ? ($monthlyBudget - $totalMonth) : 0.0;
$budgetPercentSpent = ($hasBudget && $monthlyBudget > 0) ? round(($totalMonth / $monthlyBudget) * 100, 1) : 0;

// 5. Fetch Last 5 Expenses
$last5Sql = "SELECT e.id, e.amount, e.expense_date, e.description,
                    c.name AS category_name,
                    p.name AS payment_method_name
             FROM expenses e
             JOIN categories c ON e.category_id = c.id
             JOIN payment_methods p ON e.payment_method_id = p.id
             WHERE e.user_id = ?
             ORDER BY e.expense_date DESC, e.id DESC
             LIMIT 5";
$stmt = mysqli_prepare($conn, $last5Sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$last5Expenses = [];
while ($item = mysqli_fetch_assoc($res)) {
    $item['amount'] = (float)$item['amount'];
    $last5Expenses[] = $item;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Return consolidated JSON response
echo json_encode([
    "status" => "success",
    "data" => [
        "today_spending"       => round($totalToday, 2),
        "week_spending"        => round($totalWeek, 2),
        "month_spending"       => round($totalMonth, 2),
        "has_budget"           => $hasBudget,
        "monthly_budget"       => round($monthlyBudget, 2),
        "remaining_budget"     => round($remainingBudget, 2),
        "budget_percent_spent" => $budgetPercentSpent,
        "recent_expenses"      => $last5Expenses
    ]
]);
?>
