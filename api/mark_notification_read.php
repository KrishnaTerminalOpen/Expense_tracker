<?php
// api/mark_notification_read.php
// Marks a single notification or all notifications as read

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

require_once '../config/db.php';
$user_id = $_SESSION['user_id'];

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);
$id = $data['id'] ?? $_POST['id'] ?? '';

if ($id === 'all') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
} else {
    $notifId = (int)$id;
    if ($notifId <= 0) {
        echo json_encode(["status" => "error", "message" => "Valid notification ID is required"]);
        exit();
    }
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notifId, $user_id);
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    echo json_encode(["status" => "success", "data" => ["message" => "Notification marked as read"]]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    echo json_encode(["status" => "error", "message" => "Failed to update notification"]);
}
?>
