<?php
// api/get_unread_count.php
// Returns the count of unread notifications for the active user

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$count = (int)($row['unread_count'] ?? 0);

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    "status" => "success",
    "data" => [
        "unread_count" => $count
    ]
]);
?>
