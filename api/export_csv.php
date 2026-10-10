<?php
// api/export_csv.php
// Exports filtered expenses as a downloadable CSV file using PHP header() and fputcsv()

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo "Unauthorized. Please log in first.";
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

// 1. Read filter inputs
$search            = trim($_GET['search'] ?? '');
$category_id       = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$payment_method_id = isset($_GET['payment_method_id']) ? (int)$_GET['payment_method_id'] : 0;
$from_date         = trim($_GET['from_date'] ?? '');
$to_date           = trim($_GET['to_date'] ?? '');
$min_amount        = isset($_GET['min_amount']) && $_GET['min_amount'] !== '' ? (float)$_GET['min_amount'] : null;
$max_amount        = isset($_GET['max_amount']) && $_GET['max_amount'] !== '' ? (float)$_GET['max_amount'] : null;

// Sorting
$sort_by    = (isset($_GET['sort_by']) && $_GET['sort_by'] === 'amount') ? 'amount' : 'date';
$sort_order = (isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC') ? 'ASC' : 'DESC';

// 2. Build dynamic WHERE clause
$whereConditions = ["e.user_id = ?"];
$params = [$user_id];
$types  = "i";

if (!empty($search)) {
    $whereConditions[] = "e.description LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($category_id > 0) {
    $whereConditions[] = "e.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($payment_method_id > 0) {
    $whereConditions[] = "e.payment_method_id = ?";
    $params[] = $payment_method_id;
    $types .= "i";
}

if (!empty($from_date)) {
    $whereConditions[] = "e.expense_date >= ?";
    $params[] = $from_date;
    $types .= "s";
}

if (!empty($to_date)) {
    $whereConditions[] = "e.expense_date <= ?";
    $params[] = $to_date;
    $types .= "s";
}

if ($min_amount !== null && $min_amount >= 0) {
    $whereConditions[] = "e.amount >= ?";
    $params[] = $min_amount;
    $types .= "d";
}

if ($max_amount !== null && $max_amount > 0) {
    $whereConditions[] = "e.amount <= ?";
    $params[] = $max_amount;
    $types .= "d";
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);
$sortColumn  = ($sort_by === 'amount') ? 'e.amount' : 'e.expense_date';

// 3. Query all filtered records without LIMIT
$sql = "SELECT e.id, e.expense_date, c.name AS category_name, p.name AS payment_method_name, 
               e.amount, e.description 
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        JOIN payment_methods p ON e.payment_method_id = p.id
        $whereClause
        ORDER BY $sortColumn $sort_order, e.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 4. Set headers for file download
$filename = "spendwise_expenses_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 5. Open output stream and write CSV
$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column Headers
fputcsv($output, ['ID', 'Date', 'Category', 'Payment Method', 'Amount (INR)', 'Description']);

// Rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['expense_date'],
        $row['category_name'],
        $row['payment_method_name'],
        number_format((float)$row['amount'], 2, '.', ''),
        $row['description']
    ]);
}

fclose($output);
mysqli_stmt_close($stmt);
mysqli_close($conn);
exit();
?>
