<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$username = trim($_POST["username"] ?? '');
$password = $_POST["password"] ?? '';
$confirm_password = $_POST["confirm_password"] ?? '';

if (empty($username) || empty($password)) {
$message = "Please fill in all required fields.";
$messageType = "danger";
} elseif ($password !== $confirm_password) {
$message = "Passwords do not match. Please re-enter.";
$messageType = "danger";
} else {
// Check if username already exists
$chk = pg_query_params($conn, "SELECT admin_id FROM admin WHERE username = $1", array($username));
if ($chk && pg_num_rows($chk) > 0) {
$message = "Administrator username '" . htmlspecialchars($username) . "' already exists.";
$messageType = "danger";
} else {
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$sql = "INSERT INTO admin (username, password) VALUES ($1, $2)";
$result = pg_query_params($conn, $sql, array($username, $hashed_password));

if ($result) {
$message = "Administrator account created successfully!";
$messageType = "success";
} else {
$message = "Database error: " . pg_last_error($conn);
$messageType = "danger";
}
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Administrator | ResultPortal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.page-container {
max-width: 640px;
margin: 36px auto;
padding: 0 16px;
}
</style>
</head>
<body style="background: #f8fafc; min-height: 100vh;">

<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="page-container">
<!-- Header -->
<div style="margin-bottom: 24px;">
<a href="dashboard.php" style="color: #6366f1; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
<i class="fa-solid fa-arrow-left"></i> Back to Dashboard
</a>
<h1 style="font-size: 1.6rem; font-weight: 700; color: #0f172a; margin: 0;">Add Administrator</h1>
<p style="color: #64748b; font-size: 0.9rem; margin-top: 4px;">Provision new staff credentials for portal administrative management</p>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($messageType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= $message ?></div>
</div>
<?php endif; ?>

<div class="card">
<form method="post" style="display: flex; flex-direction: column; gap: 18px;">
<div class="form-group">
<label class="form-label" for="username"><i class="fa-solid fa-user-shield"></i> Admin Username *</label>
<input type="text" id="username" name="username" class="form-control" placeholder="Enter Admin Username" required autofocus>
</div>

<div class="form-group">
<label class="form-label" for="password"><i class="fa-solid fa-lock"></i> Password *</label>
<div class="password-input-wrapper">
<input type="password" id="password" name="password" class="form-control" placeholder="Create secure password" required minlength="4">
<button type="button" class="password-toggle-btn" onclick="togglePassword('password', 'toggleIcon1')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon1"></i>
</button>
</div>
</div>

<div class="form-group">
<label class="form-label" for="confirm_password"><i class="fa-solid fa-check-double"></i> Confirm Password *</label>
<div class="password-input-wrapper">
<input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type password" required minlength="4">
<button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', 'toggleIcon2')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon2"></i>
</button>
</div>
</div>

<div style="display: flex; gap: 12px; margin-top: 12px; justify-content: flex-end;">
<a href="dashboard.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
<i class="fa-solid fa-user-plus"></i> Create Admin Account
</button>
</div>
</form>
</div>
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
