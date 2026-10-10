<?php
// api/register.php
// User registration API endpoint

header('Content-Type: application/json');
require_once '../config/db.php';

// Accept JSON payload or standard POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$name     = trim($data['name'] ?? $_POST['name'] ?? '');
$email    = trim($data['email'] ?? $_POST['email'] ?? '');
$password = trim($data['password'] ?? $_POST['password'] ?? '');

// 1. Basic validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields (name, email, password) are required."
    ]);
    exit();
}

// 2. Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please provide a valid email address."
    ]);
    exit();
}

// 3. Validate password length
if (strlen($password) < 6) {
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 6 characters long."
    ]);
    exit();
}

// 4. Check if email already exists using a prepared statement
$checkSql = "SELECT id FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $checkSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Query preparation failed: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "An account with this email already exists."
    ]);
    exit();
}
mysqli_stmt_close($stmt);

// 5. Hash the password securely using BCRYPT
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 6. Insert new user into database using a prepared statement
$insertSql = "INSERT INTO users (name, full_name, email, password) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insertSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Registration query failed: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ssss", $name, $name, $email, $hashedPassword);

if (mysqli_stmt_execute($stmt)) {
    $newUserId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Registration successful! You can now log in.",
            "user_id" => $newUserId
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create account. Please try again."
    ]);
}

mysqli_close($conn);
?>
