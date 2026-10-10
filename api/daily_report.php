<?php
// api/daily_report.php
// Returns day-by-day spending for a selected month using GROUP BY

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

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Calculate number of days in the specified month
$totalDays = (int)date('t', strtotime("$year-$month-01"));

// Query daily spending with GROUP BY
$sql = "SELECT DAY(expense_date) AS day_num, 
               COALESCE(SUM(amount), 0) AS total_amount
        FROM expenses
        WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?
        GROUP BY DAY(expense_date)
        ORDER BY day_num ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

// Initialize all days of month with 0.0
$dailyData = array_fill(1, $totalDays, 0.0);

while ($row = mysqli_fetch_assoc($res)) {
    $day = (int)$row['day_num'];
    $dailyData[$day] = (float)$row['total_amount'];
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

$labels = [];
$values = [];

for ($d = 1; $d <= $totalDays; $d++) {
    $labels[] = "Day " . $d;
    $values[] = round($dailyData[$d], 2);
}

echo json_encode([
    "status" => "success",
    "data" => [
        "month"      => $month,
        "year"       => $year,
        "total_days" => $totalDays,
        "labels"     => $labels,
        "values"     => $values
    ]
]);
?>
