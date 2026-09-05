<?php
// Secure Session, Security Headers & CSRF Protection Bootstrap

if (session_status() === PHP_SESSION_NONE) {
// Configure secure session cookie settings
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
 (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
'lifetime' => 0,
'path' => '/',
'domain' => '',
'secure' => $isHttps,
'httponly' => true,
'samesite' => 'Lax'
]);

session_start();
}

// Global Security Response Headers
if (!headers_sent()) {
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

/**
 * Generate or retrieve the active session CSRF token.
 */
function get_csrf_token() {
if (empty($_SESSION['csrf_token'])) {
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token using timing-safe comparison.
 */
function verify_csrf_token($token) {
if (empty($_SESSION['csrf_token']) || empty($token)) {
return false;
}
return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Prevent browser caching on private/sensitive authenticated portals.
 */
function set_private_cache_headers() {
if (!headers_sent()) {
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}
}

/**
 * Require valid authenticated session and optional role check.
 */
function require_auth($requiredRole = null) {
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: /frontend/pages/auth/index.php");
exit;
}

if ($requiredRole !== null) {
$userRole = $_SESSION['role'] ?? '';
if ($userRole !== $requiredRole) {
header("Location: /frontend/pages/auth/index.php");
exit;
}
}

set_private_cache_headers();
}
?>
