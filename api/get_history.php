<?php
// api/get_history.php
// Paginated and filtered expense history API

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

// 1. Read filter inputs
$search            = trim($_GET['search'] ?? '');
$category_id       = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$payment_method_id = isset($_GET['payment_method_id']) ? (int)$_GET['payment_method_id'] : 0;
$from_date         = trim($_GET['from_date'] ?? '');
$to_date           = trim($_GET['to_date'] ?? '');
$min_amount        = isset($_GET['min_amount']) && $_GET['min_amount'] !== '' ? (float)$_GET['min_amount'] : null;
$max_amount        = isset($_GET['max_amount']) && $_GET['max_amount'] !== '' ? (float)$_GET['max_amount'] : null;

// Sorting parameters
$sort_by    = (isset($_GET['sort_by']) && $_GET['sort_by'] === 'amount') ? 'amount' : 'date';
$sort_order = (isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC') ? 'ASC' : 'DESC';

// Pagination parameters
$page  = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = max(1, min(100, isset($_GET['limit']) ? (int)$_GET['limit'] : 10));
$offset = ($page - 1) * $limit;

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

// 3. Count total records & sum for pagination
$countSql = "SELECT COUNT(*) AS total_records, COALESCE(SUM(e.amount), 0) AS total_sum 
             FROM expenses e 
             $whereClause";
$countStmt = mysqli_prepare($conn, $countSql);
mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$countRes = mysqli_stmt_get_result($countStmt);
$countRow = mysqli_fetch_assoc($countRes);
$totalRecords = (int)$countRow['total_records'];
$totalSum     = (float)$countRow['total_sum'];
mysqli_stmt_close($countStmt);

$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

// 4. Query paginated records
$sortColumn = ($sort_by === 'amount') ? 'e.amount' : 'e.expense_date';
$dataSql = "SELECT e.id, e.amount, e.expense_date, e.description,
                   c.name AS category_name,
                   p.name AS payment_method_name
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            JOIN payment_methods p ON e.payment_method_id = p.id
            $whereClause
            ORDER BY $sortColumn $sort_order, e.id DESC
            LIMIT ? OFFSET ?";

$dataStmt = mysqli_prepare($conn, $dataSql);
$dataTypes = $types . "ii";
$dataParams = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($dataStmt);
$dataRes = mysqli_stmt_get_result($dataStmt);

$expenses = [];
while ($row = mysqli_fetch_assoc($dataRes)) {
    $row['amount'] = (float)$row['amount'];
    $expenses[] = $row;
}
mysqli_stmt_close($dataStmt);
mysqli_close($conn);

// Return JSON response
echo json_encode([
    "status" => "success",
    "data" => [
        "expenses" => $expenses,
        "pagination" => [
            "current_page"  => $page,
            "total_pages"   => $totalPages,
            "total_records" => $totalRecords,
            "limit"         => $limit
        ],
        "summary" => [
            "total_amount" => round($totalSum, 2)
        ]
    ]
]);
?>
