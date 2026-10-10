<?php
// api/logout.php
// Clears session data and logs the user out

session_start();
header('Content-Type: application/json');

// Clear all session variables
$_SESSION = array();

// Destroy session cookie if present
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

// Destroy the session
session_destroy();

echo json_encode([
    "status" => "success",
    "data" => [
        "message" => "Logged out successfully."
    ]
]);
?>
