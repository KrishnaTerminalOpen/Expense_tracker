<?php
// api/update_settings.php
// Updates currency preference and light/dark theme in users table

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

$currency = trim($data['currency'] ?? $_POST['currency'] ?? '₹');
$theme    = trim($data['theme'] ?? $_POST['theme'] ?? 'light');

// Sanitize inputs
$allowedThemes = ['light', 'dark'];
if (!in_array($theme, $allowedThemes)) {
    $theme = 'light';
}

if (empty($currency) || strlen($currency) > 10) {
    $currency = '₹';
}

// Update database
$sql = "UPDATE users SET currency = ?, theme = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $currency, $theme, $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Save in session
    $_SESSION['currency'] = $currency;
    $_SESSION['theme']    = $theme;

    echo json_encode([
        "status" => "success",
        "data" => [
            "message"  => "Settings saved successfully.",
            "currency" => $currency,
            "theme"    => $theme
        ]
    ]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        "status" => "error",
        "message" => "Failed to save settings."
    ]);
}
?>
