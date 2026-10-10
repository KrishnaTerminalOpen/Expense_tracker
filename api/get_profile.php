<?php
// api/get_profile.php
// Returns profile information and settings for the authenticated user

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

$sql = "SELECT id, name, email, phone, currency, theme, created_at 
        FROM users 
        WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User account not found."
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "data" => [
        "id"         => (int)$user['id'],
        "name"       => $user['name'],
        "email"      => $user['email'],
        "phone"      => $user['phone'] ?? '',
        "currency"   => $user['currency'] ?? '₹',
        "theme"      => $user['theme'] ?? 'light',
        "created_at" => $user['created_at']
    ]
]);
?>
