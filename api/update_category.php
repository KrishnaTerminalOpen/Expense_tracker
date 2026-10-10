<?php
// api/update_category.php
// Updates an existing custom category owned by the logged-in user

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

// Read input
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$id   = isset($data['id']) ? (int)$data['id'] : (int)($_POST['id'] ?? 0);
$name = trim($data['name'] ?? $_POST['name'] ?? '');

// Validate input
if ($id <= 0 || empty($name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid Category ID and Name are required."
    ]);
    exit();
}

if (strlen($name) > 100) {
    echo json_encode([
        "status" => "error",
        "message" => "Category name cannot exceed 100 characters."
    ]);
    exit();
}

// Check category ownership
$checkSql = "SELECT user_id FROM categories WHERE id = ?";
$stmt = mysqli_prepare($conn, $checkSql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$category) {
    echo json_encode([
        "status" => "error",
        "message" => "Category not found."
    ]);
    exit();
}

// User cannot edit another user's item (only own items or global defaults)
if ($category['user_id'] !== null && (int)$category['user_id'] !== $user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "You do not have permission to edit this category."
    ]);
    exit();
}

// Check for duplicate name
$dupSql = "SELECT id FROM categories WHERE id != ? AND (user_id IS NULL OR user_id = ?) AND LOWER(name) = LOWER(?)";
$stmt = mysqli_prepare($conn, $dupSql);
mysqli_stmt_bind_param($stmt, "iis", $id, $user_id, $name);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    echo json_encode([
        "status" => "error",
        "message" => "Another category with this name already exists."
    ]);
    exit();
}
mysqli_stmt_close($stmt);

// Update category
$updateSql = "UPDATE categories SET name = ? WHERE id = ? AND (user_id IS NULL OR user_id = ?)";
$stmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmt, "sii", $name, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "success",
        "data" => [
            "message" => "Category updated successfully.",
            "id" => $id,
            "name" => $name
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to update category."
    ]);
}
?>
