<?php
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
$params["path"], $params["domain"],
$params["secure"], $params["httponly"]
);
}

// Finally, destroy the session.
session_destroy();

// Redirect to homepage
$homeUrl = "/index.php";

if (!headers_sent()) {
    header("Location: " . $homeUrl);
    exit;
} else {
    echo '<script>window.location.href="' . htmlspecialchars($homeUrl) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($homeUrl) . '"></noscript>';
    exit;
}
?>