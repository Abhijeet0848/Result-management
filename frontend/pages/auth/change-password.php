<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: index.php");
exit;
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || isset($_SESSION['username']);
$username = $isAdmin ? ($_SESSION['username'] ?? '') : ($_SESSION['student_username'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && !empty($username)) {
$og_password = $_POST['ogpass'] ?? '';
$curr_password = $_POST['npass'] ?? '';
$concurr_password = $_POST['cnpass'] ?? '';

if ($curr_password !== $concurr_password) {
$showError = "New password and Confirm password do not match.";
} elseif (strlen($curr_password) < 4) {
$showError = "New password must be at least 4 characters.";
} else {
if ($isAdmin) {
$sql = "SELECT password FROM admin WHERE LOWER(username) = LOWER($1)";
$result = pg_query_params($conn, $sql, array($username));

if ($result && pg_num_rows($result) == 1) {
$row = pg_fetch_assoc($result);
$stored = $row['password'];

if ($og_password === $stored || password_verify($og_password, $stored)) {
$hashed_password = password_hash($curr_password, PASSWORD_BCRYPT);
$update_sql = "UPDATE admin SET password = $1 WHERE LOWER(username) = LOWER($2)";
$update_result = pg_query_params($conn, $update_sql, array($hashed_password, $username));

if ($update_result) {
$showAlert = "Admin password updated successfully!";
} else {
$showError = "Failed to update password in database.";
}
} else {
$showError = "The current admin password entered is incorrect.";
}
} else {
$showError = "Admin user not found.";
}
} else {
// Student
$sql = "SELECT password FROM student WHERE LOWER(email) = LOWER($1)";
$result = pg_query_params($conn, $sql, array($username));

if ($result && pg_num_rows($result) == 1) {
$row = pg_fetch_assoc($result);
$stored = $row['password'];

if ($og_password === $stored || password_verify($og_password, $stored)) {
$hashed_password = password_hash($curr_password, PASSWORD_BCRYPT);
$update_sql = "UPDATE student SET password = $1 WHERE LOWER(email) = LOWER($2)";
$update_result = pg_query_params($conn, $update_sql, array($hashed_password, $username));

if ($update_result) {
$showAlert = "Student password updated successfully!";
} else {
$showError = "Failed to update password in database.";
}
} else {
$showError = "The current password entered is incorrect.";
}
} else {
$showError = "Student account not found.";
}
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - Portal Security</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php if ($isAdmin): ?>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>
<?php else: ?>
<header style="background: #0f172a; padding: 16px 24px; color: #fff; display: flex; justify-content: space-between; align-items: center;">
<a href="../student/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-graduation-cap" style="color: #10b981;"></i> Student Portal
</a>
<a href="../student/dashboard.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.1); color: #fff;">
<i class="fa-solid fa-arrow-left"></i> Back to Dashboard
</a>
</header>
<?php endif; ?>

<div class="container" style="max-width: 550px;">
<div class="page-header">
<div>
<h1 class="page-title"><i class="fa-solid fa-key" style="color: var(--primary); margin-right: 8px;"></i> Change Password</h1>
<p>Update your account security credentials.</p>
</div>
</div>

<?php if ($showAlert): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($showAlert) ?></div>
</div>
<?php endif; ?>

<?php if ($showError): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($showError) ?></div>
</div>
<?php endif; ?>

<form action="change-password.php" method="POST">
<div class="form-group">
<label for="ogpass">Current Password</label>
<div class="password-input-wrapper">
<input type="password" id="ogpass" name="ogpass" required placeholder="Enter current password">
<button type="button" class="password-toggle-btn" onclick="togglePassword('ogpass', 'toggleIcon1')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon1"></i>
</button>
</div>
</div>

<div class="form-group">
<label for="npass">New Password</label>
<div class="password-input-wrapper">
<input type="password" id="npass" name="npass" required placeholder="Enter new password (min 4 characters)">
<button type="button" class="password-toggle-btn" onclick="togglePassword('npass', 'toggleIcon2')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon2"></i>
</button>
</div>
</div>

<div class="form-group">
<label for="cnpass">Confirm New Password</label>
<div class="password-input-wrapper">
<input type="password" id="cnpass" name="cnpass" required placeholder="Re-enter new password">
<button type="button" class="password-toggle-btn" onclick="togglePassword('cnpass', 'toggleIcon3')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon3"></i>
</button>
</div>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="<?= $isAdmin ? '../admin/dashboard.php' : '../student/dashboard.php' ?>" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-lock"></i> Update Password
</button>
</div>
</form>
</div>

<script>
function togglePassword(inputId, iconId) {
const input = document.getElementById(inputId);
const icon = document.getElementById(iconId);
if (!input || !icon) return;

if (input.type === 'password') {
input.type = 'text';
icon.classList.remove('fa-eye');
icon.classList.add('fa-eye-slash');
} else {
input.type = 'password';
icon.classList.remove('fa-eye-slash');
icon.classList.add('fa-eye');
}
}
</script>
</body>
</html>
