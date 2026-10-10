<?php
// api/get_budgets.php
// Retrieves all budgets with spent amount calculated from expenses via SUM

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

// Optional filter by month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year  = isset($_GET['year']) ? (int)$_GET['year'] : 0;

$sql = "SELECT b.id, b.user_id, b.category_id, b.month, b.year, b.amount, b.alert_percent,
               COALESCE(c.name, 'Overall Monthly Budget') AS category_name,
               COALESCE(
                   (SELECT SUM(e.amount) 
                    FROM expenses e 
                    WHERE e.user_id = b.user_id 
                      AND (b.category_id IS NULL OR e.category_id = b.category_id)
                      AND MONTH(e.expense_date) = b.month 
                      AND YEAR(e.expense_date) = b.year
                   ), 0
               ) AS spent_amount
        FROM budgets b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = ?";

if ($month > 0 && $year > 0) {
    $sql .= " AND b.month = ? AND b.year = ?";
}

$sql .= " ORDER BY b.year DESC, b.month DESC, (b.category_id IS NULL) DESC, c.name ASC";

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

$budgets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $amount       = (float)$row['amount'];
    $spent        = (float)$row['spent_amount'];
    $alertPercent = (int)$row['alert_percent'];

    $percent = ($amount > 0) ? round(($spent / $amount) * 100, 1) : 0;
    $remaining = round($amount - $spent, 2);

    // Color status: green (success), yellow (warning), red (danger)
    $statusColor = 'success';
    if ($percent >= 100) {
        $statusColor = 'danger';
    } elseif ($percent >= $alertPercent) {
        $statusColor = 'warning';
    }

    $row['amount']        = $amount;
    $row['spent_amount']  = $spent;
    $row['remaining']     = $remaining;
    $row['percent_spent'] = $percent;
    $row['status_color']  = $statusColor;
    $row['is_overall']    = ($row['category_id'] === null);

    $budgets[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data"   => $budgets
]);
?>
