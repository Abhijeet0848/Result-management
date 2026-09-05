<?php
session_start();
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
include_once __DIR__ . '/../../../backend/config/connection.php';
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$new_password = $_POST['PASSWORD'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$message = "Invalid email format.";
$messageType = "danger";
} elseif ($new_password !== $confirm_password) {
$message = "Passwords do not match.";
$messageType = "danger";
} else {
$query = "SELECT reg_id FROM student WHERE email = $1";
$result = pg_query_params($conn, $query, array($email));

if ($result && pg_num_rows($result) > 0) {
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
$update = "UPDATE student SET password = $1 WHERE email = $2";
$res = pg_query_params($conn, $update, array($hashed_password, $email));

if ($res) {
$message = "Password updated successfully! You can now log in with your new credentials.";
$messageType = "success";
} else {
$message = "Database error: " . pg_last_error($conn);
$messageType = "danger";
}
} else {
$message = "No student account found with the given email address.";
$messageType = "danger";
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Password Recovery | ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
body {
background-color: #F8FAFC;
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
padding: 20px;
font-family: 'Plus Jakarta Sans', sans-serif;
color: #1F2937;
}
.form-card {
background: #FFFFFF;
border-radius: 16px;
box-shadow: 0 4px 20px rgba(31, 41, 55, 0.08);
border: 1px solid #E5E7EB;
width: 100%;
max-width: 440px;
padding: 36px;
}
.icon-wrap {
width: 52px;
height: 52px;
background: #2563EB;
border-radius: 12px;
display: inline-flex;
align-items: center;
justify-content: center;
font-size: 1.4rem;
color: #FFFFFF;
box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
margin-bottom: 12px;
}
</style>
</head>
<body>

<div class="form-card">
<div style="text-align: center; margin-bottom: 24px;">
<div class="icon-wrap">
<i class="fa-solid fa-key"></i>
</div>
<h2 style="font-size: 1.55rem; font-weight: 800; color: #1E3A5F; margin: 0;">Reset Student Password</h2>
<p style="color: #4B5563; font-size: 0.95rem; font-weight: 500; margin-top: 6px;">Enter your student email and set a new password</p>
</div>

<?php if (!empty($message)): ?>
<div class="alert alert-<?= $messageType ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($messageType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= htmlspecialchars($message) ?></div>
</div>
<?php endif; ?>

<form method="post" action="" style="display: flex; flex-direction: column; gap: 14px;">
<div class="form-group">
<label class="form-label" for="email"><i class="fa-solid fa-envelope"></i> Registered Email</label>
<input type="email" id="email" name="email" class="form-control" placeholder="Enter Registered Email" required autofocus>
</div>

<div class="form-group">
<label class="form-label" for="PASSWORD"><i class="fa-solid fa-lock"></i> New Password</label>
<div class="password-input-wrapper">
<input type="password" id="PASSWORD" name="PASSWORD" class="form-control" placeholder="Enter new password" required minlength="4">
<button type="button" class="password-toggle-btn" onclick="togglePassword('PASSWORD', 'toggleIcon1')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon1"></i>
</button>
</div>
</div>

<div class="form-group">
<label class="form-label" for="confirm_password"><i class="fa-solid fa-check-double"></i> Confirm New Password</label>
<div class="password-input-wrapper">
<input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type new password" required minlength="4">
<button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', 'toggleIcon2')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="toggleIcon2"></i>
</button>
</div>
</div>

<button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem; margin-top: 6px;">
<i class="fa-solid fa-rotate"></i> Update Password
</button>
</form>

<div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #E5E7EB;">
<a href="index.php" style="color: #2563EB; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
<i class="fa-solid fa-arrow-left"></i> Return to Login
</a>
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
