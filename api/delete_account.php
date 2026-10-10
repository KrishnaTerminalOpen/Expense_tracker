<?php
// api/delete_account.php
// Permanently deletes the user account after password verification

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
$password = trim($data['password'] ?? $_POST['password'] ?? '');

if (empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Password confirmation is required to delete your account."
    ]);
    exit();
}

// 1. Fetch current password hash
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($password, $user['password'])) {
    mysqli_close($conn);
    echo json_encode([
        "status" => "error",
        "message" => "Incorrect password. Account deletion cancelled."
    ]);
    exit();
}

// 2. Delete user account (CASCADE deletes associated expenses, budgets, etc.)
$deleteSql = "DELETE FROM users WHERE id = ?";
$deleteStmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($deleteStmt, "i", $user_id);

if (mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    // 3. Clear and destroy session
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Your account and all associated data have been permanently deleted."
        ]
    ]);
} else {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete account. Please try again."
    ]);
}
?>
