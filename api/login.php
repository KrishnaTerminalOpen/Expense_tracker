<?php
// api/login.php
// User login API endpoint

session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// Accept JSON payload or standard POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$email    = trim($data['email'] ?? $_POST['email'] ?? '');
$password = trim($data['password'] ?? $_POST['password'] ?? '');

// 1. Basic validation
if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Both email and password are required."
    ]);
    exit();
}

// 2. Query user record using prepared statement
$sql = "SELECT id, name, email, password FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database query failed: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 3. Verify user exists and check password
if ($user = mysqli_fetch_assoc($result)) {
    if (password_verify($password, $user['password'])) {
        // Store authenticated user information in session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        echo json_encode([
            "status" => "success",
            "data" => [
                "message" => "Login successful!",
                "user" => [
                    "id"    => $user['id'],
                    "name"  => $user['name'],
                    "email" => $user['email']
                ]
            ]
        ]);
        exit();
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// If user not found or password verification failed
echo json_encode([
    "status" => "error",
    "message" => "Invalid email or password."
]);
?>
