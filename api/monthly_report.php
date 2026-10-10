<?php
// api/monthly_report.php
// Returns 12-month expense summary for a selected year using GROUP BY

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

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Query monthly spending for the selected year
$sql = "SELECT MONTH(expense_date) AS month_num, 
               COALESCE(SUM(amount), 0) AS total_amount,
               COUNT(id) AS count
        FROM expenses
        WHERE user_id = ? AND YEAR(expense_date) = ?
        GROUP BY MONTH(expense_date)
        ORDER BY month_num ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $year);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

// Map months 1 to 12
$monthlyData = array_fill(1, 12, 0.0);
$yearlyTotal = 0.0;

while ($row = mysqli_fetch_assoc($res)) {
    $m = (int)$row['month_num'];
    $amt = (float)$row['total_amount'];
    $monthlyData[$m] = $amt;
    $yearlyTotal += $amt;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

$monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$labels = [];
$values = [];

for ($i = 1; $i <= 12; $i++) {
    $labels[] = $monthNames[$i - 1];
    $values[] = round($monthlyData[$i], 2);
}

echo json_encode([
    "status" => "success",
    "data" => [
        "year"         => $year,
        "yearly_total" => round($yearlyTotal, 2),
        "labels"       => $labels,
        "values"       => $values
    ]
]);
?>
