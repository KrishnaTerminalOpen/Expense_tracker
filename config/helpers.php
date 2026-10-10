<?php
// config/helpers.php
// Shared helper functions for SpendWise

/**
 * Checks budget thresholds after an expense is recorded and creates notifications
 * Avoids duplicate notifications for the same budget, type, and month.
 *
 * @param mysqli $conn Active database connection
 * @param int $user_id Logged-in user ID
 * @param int|null $category_id Category ID of the expense (optional)
 * @param string|null $expense_date Date of the expense (YYYY-MM-DD, optional defaults to today)
 * @return array Array containing triggered alert details
 */
function check_budget_alerts($conn, $user_id, $category_id = null, $expense_date = null) {
    if (empty($expense_date)) {
        $expense_date = date('Y-m-d');
    }

    $month = (int)date('n', strtotime($expense_date)); // 1 to 12
    $year  = (int)date('Y', strtotime($expense_date)); // e.g. 2026

    $alerts = [];

    // 1. Check Category Budget (if category_id is provided)
    if ($category_id !== null && (int)$category_id > 0) {
        $catBudgetSql = "SELECT b.id, b.amount, b.alert_percent, c.name AS category_name 
                         FROM budgets b
                         JOIN categories c ON b.category_id = c.id
                         WHERE b.user_id = ? AND b.category_id = ? AND b.month = ? AND b.year = ?";
        $stmt = mysqli_prepare($conn, $catBudgetSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiii", $user_id, $category_id, $month, $year);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($b = mysqli_fetch_assoc($result)) {
                $budgetId     = (int)$b['id'];
                $budgetAmount = (float)$b['amount'];
                $alertPercent = (int)$b['alert_percent'];
                $catName      = $b['category_name'];

                // Total spent in this category for this month
                $spentSql = "SELECT COALESCE(SUM(amount), 0) AS total_spent 
                             FROM expenses 
                             WHERE user_id = ? AND category_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
                $spentStmt = mysqli_prepare($conn, $spentSql);
                if ($spentStmt) {
                    mysqli_stmt_bind_param($spentStmt, "iiii", $user_id, $category_id, $month, $year);
                    mysqli_stmt_execute($spentStmt);
                    $spentRow = mysqli_fetch_assoc(mysqli_stmt_get_result($spentStmt));
                    $totalSpent = (float)$spentRow['total_spent'];
                    mysqli_stmt_close($spentStmt);

                    $pctSpent = ($budgetAmount > 0) ? round(($totalSpent / $budgetAmount) * 100, 1) : 0;

                    if ($totalSpent > $budgetAmount) {
                        $msg = "Warning: You exceeded your budget for '{$catName}'! Spent ₹{$totalSpent} of ₹{$budgetAmount} ({$pctSpent}%).";
                        $alerts[] = $msg;
                        save_unique_notification($conn, $user_id, $budgetId, $msg, 'danger', $month, $year);
                    } elseif ($pctSpent >= $alertPercent) {
                        $msg = "Alert: You have reached {$pctSpent}% of your '{$catName}' budget! Spent ₹{$totalSpent} of ₹{$budgetAmount}.";
                        $alerts[] = $msg;
                        save_unique_notification($conn, $user_id, $budgetId, $msg, 'warning', $month, $year);
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 2. Check Overall Monthly Budget (category_id IS NULL)
    $overallSql = "SELECT id, amount, alert_percent 
                   FROM budgets 
                   WHERE user_id = ? AND category_id IS NULL AND month = ? AND year = ?";
    $stmt = mysqli_prepare($conn, $overallSql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($b = mysqli_fetch_assoc($result)) {
            $budgetId     = (int)$b['id'];
            $budgetAmount = (float)$b['amount'];
            $alertPercent = (int)$b['alert_percent'];

            // Total spent across all categories for this month
            $spentSql = "SELECT COALESCE(SUM(amount), 0) AS total_spent 
                         FROM expenses 
                         WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
            $spentStmt = mysqli_prepare($conn, $spentSql);
            if ($spentStmt) {
                mysqli_stmt_bind_param($spentStmt, "iii", $user_id, $month, $year);
                mysqli_stmt_execute($spentStmt);
                $spentRow = mysqli_fetch_assoc(mysqli_stmt_get_result($spentStmt));
                $totalSpent = (float)$spentRow['total_spent'];
                mysqli_stmt_close($spentStmt);

                $pctSpent = ($budgetAmount > 0) ? round(($totalSpent / $budgetAmount) * 100, 1) : 0;

                if ($totalSpent > $budgetAmount) {
                    $msg = "Warning: You exceeded your Overall Monthly Budget! Spent ₹{$totalSpent} of ₹{$budgetAmount} ({$pctSpent}%).";
                    $alerts[] = $msg;
                    save_unique_notification($conn, $user_id, $budgetId, $msg, 'danger', $month, $year);
                } elseif ($pctSpent >= $alertPercent) {
                    $msg = "Alert: You have reached {$pctSpent}% of your Overall Monthly Budget! Spent ₹{$totalSpent} of ₹{$budgetAmount}.";
                    $alerts[] = $msg;
                    save_unique_notification($conn, $user_id, $budgetId, $msg, 'warning', $month, $year);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

    return [
        "has_alert" => count($alerts) > 0,
        "alerts"    => $alerts
    ];
}

/**
 * Backward compatibility alias for check_budget_alerts
 */
function check_budget_status($conn, $user_id, $category_id, $expense_date) {
    return check_budget_alerts($conn, $user_id, $category_id, $expense_date);
}

/**
 * Saves a notification only if an identical alert type for the same budget has not already been created this month
 */
function save_unique_notification($conn, $user_id, $budget_id, $message, $type, $month, $year) {
    // Check for existing notification in the same month and year
    $checkSql = "SELECT id FROM notifications 
                 WHERE user_id = ? AND budget_id = ? AND type = ? 
                   AND MONTH(created_at) = ? AND YEAR(created_at) = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "iisii", $user_id, $budget_id, $type, $month, $year);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        $exists = (mysqli_stmt_num_rows($checkStmt) > 0);
        mysqli_stmt_close($checkStmt);

        if (!$exists) {
            $insertSql = "INSERT INTO notifications (user_id, budget_id, message, type) VALUES (?, ?, ?, ?)";
            $insStmt = mysqli_prepare($conn, $insertSql);
            if ($insStmt) {
                mysqli_stmt_bind_param($insStmt, "iiss", $user_id, $budget_id, $message, $type);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);
            }
        }
    }
}
?>
