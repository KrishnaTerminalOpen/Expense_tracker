<?php
// api/change_password.php
// Verifies old password and updates to a new hashed password

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

// Read input (support JSON or POST)
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$old_password     = trim($data['old_password'] ?? $_POST['old_password'] ?? '');
$new_password     = trim($data['new_password'] ?? $_POST['new_password'] ?? '');
$confirm_password = trim($data['confirm_password'] ?? $_POST['confirm_password'] ?? '');

// 1. Validation
if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode([
        "status" => "error",
        "message" => "All password fields are required."
    ]);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode([
        "status" => "error",
        "message" => "New password and confirmation do not match."
    ]);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode([
        "status" => "error",
        "message" => "New password must be at least 6 characters long."
    ]);
    exit();
}

// 2. Fetch existing password hash
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User account not found."
    ]);
    exit();
}

// 3. Verify current password
if (!password_verify($old_password, $user['password'])) {
    mysqli_close($conn);
    echo json_encode([
        "status" => "error",
        "message" => "Your current password does not match our records."
    ]);
    exit();
}

// 4. Hash new password and update
$newHashed = password_hash($new_password, PASSWORD_DEFAULT);
$updateSql = "UPDATE users SET password = ? WHERE id = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, "si", $newHashed, $user_id);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Password changed successfully."
        ]
    ]);
} else {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update password. Please try again."
    ]);
}
?>
