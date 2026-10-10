<?php
// api/update_profile.php
// Updates user's name, email, and phone

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

$name  = trim($data['name'] ?? $_POST['name'] ?? '');
$email = trim($data['email'] ?? $_POST['email'] ?? '');
$phone = trim($data['phone'] ?? $_POST['phone'] ?? '');

// 1. Validation
if (empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Name cannot be empty."
    ]);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please enter a valid email address."
    ]);
    exit();
}

// 2. Check duplicate email for another user
$checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "si", $email, $user_id);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    mysqli_stmt_close($checkStmt);
    echo json_encode([
        "status" => "error",
        "message" => "This email is already in use by another account."
    ]);
    exit();
}
mysqli_stmt_close($checkStmt);

// 3. Update user profile
$updateSql = "UPDATE users SET name = ?, full_name = ?, email = ?, phone = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmt, "ssssi", $name, $name, $email, $phone, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Refresh session variables
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Profile updated successfully.",
            "name"    => $name,
            "email"   => $email,
            "phone"   => $phone
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update profile."
    ]);
}
?>
