<?php
// api/check_session.php
// Checks whether a user is currently logged in and returns preferences

session_start();
header('Content-Type: application/json');

// Check if user session variable exists
if (isset($_SESSION['user_id'])) {
    require_once '../config/db.php';
    $user_id = $_SESSION['user_id'];

    // Retrieve fresh user details and settings from database
    $sql = "SELECT name, email, phone, currency, theme FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    $theme = $user['theme'] ?? 'light';
    $currency = $user['currency'] ?? '₹';

    if ($user) {
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['theme']      = $theme;
        $_SESSION['currency']   = $currency;
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "logged_in"  => true,
            "user_id"    => $user_id,
            "user_name"  => $_SESSION['user_name'] ?? 'User',
            "user_email" => $_SESSION['user_email'] ?? '',
            "currency"   => $currency,
            "theme"      => $theme
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User is not logged in."
    ]);
}
?>
