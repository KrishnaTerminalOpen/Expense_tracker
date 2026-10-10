<?php
// api/get_notifications.php
// Returns user notifications and monthly spending insights

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

// 1. Fetch user notifications
$notifSql = "SELECT id, budget_id, message, type, is_read, created_at 
             FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT 50";
$stmt = mysqli_prepare($conn, $notifSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$notifications = [];
$unreadCount = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $row['is_read'] = (int)$row['is_read'];
    if ($row['is_read'] === 0) {
        $unreadCount++;
    }
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

// 2. Insights Calculation:
// A. Top Spending Category this month
$topCatSql = "SELECT c.name, COALESCE(SUM(e.amount), 0) AS total_amount 
              FROM categories c 
              JOIN expenses e ON e.category_id = c.id 
              WHERE e.user_id = ? AND MONTH(e.expense_date) = MONTH(CURDATE()) AND YEAR(e.expense_date) = YEAR(CURDATE()) 
              GROUP BY c.id, c.name 
              ORDER BY total_amount DESC 
              LIMIT 1";
$stmt = mysqli_prepare($conn, $topCatSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$topCatRes = mysqli_stmt_get_result($stmt);
$topCategory = mysqli_fetch_assoc($topCatRes);
$topCatName = $topCategory ? $topCategory['name'] : 'None';
$topCatAmount = $topCategory ? (float)$topCategory['total_amount'] : 0.0;
mysqli_stmt_close($stmt);

// B. Current Month Total vs Last Month Total
$curMonthSql = "SELECT COALESCE(SUM(amount), 0) AS total 
                FROM expenses 
                WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $curMonthSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$curMonthRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$curMonthTotal = (float)$curMonthRow['total'];
mysqli_stmt_close($stmt);

$lastMonthSql = "SELECT COALESCE(SUM(amount), 0) AS total 
                 FROM expenses 
                 WHERE user_id = ? 
                   AND MONTH(expense_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) 
                   AND YEAR(expense_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
$stmt = mysqli_prepare($conn, $lastMonthSql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$lastMonthRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$lastMonthTotal = (float)$lastMonthRow['total'];
mysqli_stmt_close($stmt);

$diffAmount = $curMonthTotal - $lastMonthTotal;
$pctChange = ($lastMonthTotal > 0) ? round(($diffAmount / $lastMonthTotal) * 100, 1) : 0.0;

// C. Average Daily Spend This Month
$dayOfMonth = max(1, (int)date('j'));
$avgDailySpend = round($curMonthTotal / $dayOfMonth, 2);

mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data" => [
        "unread_count"  => $unreadCount,
        "notifications" => $notifications,
        "insights"      => [
            "top_category"         => $topCatName,
            "top_category_amount"  => round($topCatAmount, 2),
            "current_month_total"  => round($curMonthTotal, 2),
            "last_month_total"     => round($lastMonthTotal, 2),
            "diff_amount"          => round($diffAmount, 2),
            "pct_change"           => $pctChange,
            "avg_daily_spend"      => $avgDailySpend
        ]
    ]
]);
?>
