<?php
// api/export_pdf.php
// Exports filtered expenses as an elegant downloadable PDF document using Dompdf

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo "Unauthorized. Please log in first.";
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$user_id = $_SESSION['user_id'];

// 1. Fetch user information (name, currency preference)
$userQuery = "SELECT name, currency FROM users WHERE id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($userStmt, "i", $user_id);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$userData = mysqli_fetch_assoc($userResult);

$userName = $userData['name'] ?? 'User';
$currency = $userData['currency'] ?? '₹';

// 2. Read filter inputs
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

// 3. Build dynamic WHERE clause
$whereConditions = ["e.user_id = ?"];
$params = [$user_id];
$types  = "i";
$filterNotes = [];

if (!empty($search)) {
    $whereConditions[] = "e.description LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
    $filterNotes[] = "Search: " . htmlspecialchars($search);
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
    $filterNotes[] = "From: " . htmlspecialchars($from_date);
}

if (!empty($to_date)) {
    $whereConditions[] = "e.expense_date <= ?";
    $params[] = $to_date;
    $types .= "s";
    $filterNotes[] = "To: " . htmlspecialchars($to_date);
}

if ($min_amount !== null && $min_amount >= 0) {
    $whereConditions[] = "e.amount >= ?";
    $params[] = $min_amount;
    $types .= "d";
    $filterNotes[] = "Min: " . $currency . number_format($min_amount, 2);
}

if ($max_amount !== null && $max_amount > 0) {
    $whereConditions[] = "e.amount <= ?";
    $params[] = $max_amount;
    $types .= "d";
    $filterNotes[] = "Max: " . $currency . number_format($max_amount, 2);
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);
$sortColumn  = ($sort_by === 'amount') ? 'e.amount' : 'e.expense_date';

// 4. Query all filtered records
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

$expenses = [];
$totalAmount = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $expenses[] = $row;
    $totalAmount += (float)$row['amount'];
}

$recordCount = count($expenses);
$filtersText = empty($filterNotes) ? "All Records (No active date/amount filters)" : implode(" | ", $filterNotes);
$generatedAt = date("d M Y, h:i A");

// 5. Construct HTML template for Dompdf
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SpendWise Expense Report</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: -0.5px;
        }
        .brand-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 20px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table td {
            vertical-align: top;
        }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .summary-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .summary-value.primary {
            color: #4f46e5;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th {
            background-color: #4f46e5;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
        }
        .data-table th.text-end {
            text-align: right;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table td.text-end {
            text-align: right;
        }
        .data-table td.amount-cell {
            font-weight: bold;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 4px;
            background-color: #e2e8f0;
            color: #334155;
        }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: middle;">
                <div class="brand-title">SpendWise</div>
                <div class="brand-sub">Personal Expense Tracking &amp; Budget Management System</div>
            </td>
            <td class="report-meta" style="vertical-align: middle;">
                <strong>Statement Period:</strong> ' . htmlspecialchars($filtersText) . '<br>
                <strong>Generated For:</strong> ' . htmlspecialchars($userName) . '<br>
                <strong>Date &amp; Time:</strong> ' . $generatedAt . '
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <div class="summary-box">
        <table class="summary-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 33%;">
                    <div class="summary-label">Total Transactions</div>
                    <div class="summary-value">' . number_format($recordCount) . '</div>
                </td>
                <td style="width: 33%;">
                    <div class="summary-label">Total Amount Spent</div>
                    <div class="summary-value primary">' . htmlspecialchars($currency) . ' ' . number_format($totalAmount, 2) . '</div>
                </td>
                <td style="width: 34%;">
                    <div class="summary-label">Average per Expense</div>
                    <div class="summary-value">' . htmlspecialchars($currency) . ' ' . number_format($recordCount > 0 ? ($totalAmount / $recordCount) : 0, 2) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Expenses Data Table -->
    <table class="data-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 22%;">Category</th>
                <th style="width: 20%;">Payment Method</th>
                <th style="width: 28%;">Description</th>
                <th style="width: 15%;" class="text-end">Amount (' . htmlspecialchars($currency) . ')</th>
            </tr>
        </thead>
        <tbody>';

if (empty($expenses)) {
    $html .= '
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
                    No expense records found matching the specified filters.
                </td>
            </tr>';
} else {
    foreach ($expenses as $row) {
        $formattedDate = date("d M Y", strtotime($row['expense_date']));
        $desc = !empty($row['description']) ? htmlspecialchars($row['description']) : '<span style="color: #94a3b8;">-</span>';
        $cat = htmlspecialchars($row['category_name']);
        $pm  = htmlspecialchars($row['payment_method_name']);
        $amt = number_format((float)$row['amount'], 2);

        $html .= '
            <tr>
                <td>' . $formattedDate . '</td>
                <td><strong>' . $cat . '</strong></td>
                <td><span class="badge">' . $pm . '</span></td>
                <td>' . $desc . '</td>
                <td class="text-end amount-cell">' . htmlspecialchars($currency) . ' ' . $amt . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="4" style="text-align: right; padding: 8px;">Total Spent:</td>
                <td class="text-end" style="padding: 8px; color: #4f46e5; font-size: 11px;">' . htmlspecialchars($currency) . ' ' . number_format($totalAmount, 2) . '</td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <div class="footer">
        SpendWise Automated Report &bull; Thank you for managing your finances responsibly.
    </div>

</body>
</html>';

// 6. Initialize Dompdf and stream response
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfFilename = "spendwise_expenses_" . date('Ymd_His') . ".pdf";
$dompdf->stream($pdfFilename, ["Attachment" => true]);
exit();
