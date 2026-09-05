<?php
if (!function_exists('logAudit')) {
function logAudit($conn, $action, $details = '') {
if (!$conn) return false;

$username = $_SESSION['username'] ?? ($_SESSION['student_username'] ?? ($_SESSION['faculty_email'] ?? 'System'));
$role = $_SESSION['user_role'] ?? (isset($_SESSION['admin_id']) ? 'Admin' : (isset($_SESSION['faculty_id']) ? 'Faculty' : (isset($_SESSION['student_id']) ? 'Student' : 'Guest')));
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$sql = "INSERT INTO audit_logs (username, user_role, action, details, ip_address, created_at) VALUES ($1, $2, $3, $4, $5, CURRENT_TIMESTAMP)";
return pg_query_params($conn, $sql, array($username, $role, $action, $details, $ip));
}
}
