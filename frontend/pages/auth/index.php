<?php
$login = false;
$showError = false;
$statusAlert = null;
$posted_username = '';

include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

ob_start();

if (isset($_GET['status_err']) && !empty($_GET['status_err'])) {
    $statusAlert = [
        'type' => 'warning',
        'icon' => 'fa-solid fa-triangle-exclamation',
        'title' => 'Portal Access Notice',
        'msg' => htmlspecialchars($_GET['status_err'])
    ];
}

// Check if currently locked out due to excessive failed attempts
if (isset($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until']) {
$remainingMinutes = ceil(($_SESSION['login_lock_until'] - time()) / 60);
$showError = "Too many failed login attempts. Security lock is active. Please wait $remainingMinutes minute(s) before trying again.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
$posted_username = trim($_POST["username"] ?? '');
$password = $_POST["password"] ?? '';

if (empty($posted_username) || empty($password)) {
$showError = "Please enter both your credentials (Username / Roll No / Email) and password.";
} elseif (!$conn) {
$showError = "Database Connection Error: Could not connect to PostgreSQL database. Please ensure PostgreSQL service is active.";
} else {
$authenticated = false;

// 1. Check if credential belongs to Administrator
$adminSql = "SELECT admin_id, username, password FROM admin WHERE LOWER(username) = LOWER($1) LIMIT 1";
$adminResult = pg_query_params($conn, $adminSql, array($posted_username));

if ($adminResult && pg_num_rows($adminResult) > 0) {
$adminRow = pg_fetch_assoc($adminResult);
$isPasswordCorrect = ($password === $adminRow['password']) || password_verify($password, $adminRow['password']);
if ($isPasswordCorrect) {
$authenticated = true;
unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['username'] = $adminRow['username'];
$_SESSION['role'] = 'admin';
$_SESSION['user_role'] = 'Admin';
$_SESSION['created_at'] = time();
header("location: ../admin/dashboard.php");
exit;
}
}

// 2. Check if credential belongs to Faculty / Teacher
$facultySql = "SELECT faculty_id, name, email, password, branch_id, department, status FROM faculty 
 WHERE LOWER(email) = LOWER($1) OR LOWER(name) = LOWER($1) LIMIT 1";
$facResult = pg_query_params($conn, $facultySql, array($posted_username));

if ($facResult && pg_num_rows($facResult) > 0) {
$facRow = pg_fetch_assoc($facResult);
if (isset($facRow['status']) && (int)$facRow['status'] === 0) {
$showError = "Account Inactive: Faculty account for '" . htmlspecialchars($facRow['name']) . "' is deactivated. Please contact administrator.";
$authenticated = true;
} else {
$isPasswordCorrect = ($password === $facRow['password']) || password_verify($password, $facRow['password']);
if ($isPasswordCorrect) {
$authenticated = true;
unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['faculty_id'] = $facRow['faculty_id'];
$_SESSION['faculty_name'] = $facRow['name'];
$_SESSION['faculty_email'] = $facRow['email'];
$_SESSION['faculty_branch'] = $facRow['branch_id'];
$_SESSION['faculty_dept'] = $facRow['department'];
$_SESSION['username'] = $facRow['name'];
$_SESSION['role'] = 'faculty';
$_SESSION['user_role'] = 'Faculty';
$_SESSION['created_at'] = time();
header("location: ../faculty/dashboard.php");
exit;
}
}
}

// 3. Check if credential belongs to Student (by Roll No, Email, or Full Name)
$studentSql = "SELECT reg_id, name, email, password, roll_no, status FROM student 
 WHERE roll_no = $1 
OR LOWER(email) = LOWER($1) 
OR LOWER(name) = LOWER($1)
 LIMIT 1";
$studentResult = pg_query_params($conn, $studentSql, array($posted_username));

if ($studentResult && pg_num_rows($studentResult) > 0) {
$studentRow = pg_fetch_assoc($studentResult);
$isPasswordCorrect = ($password === $studentRow['password']) || password_verify($password, $studentRow['password']);

if ($isPasswordCorrect) {
$authenticated = true;
$studentStatus = intval($studentRow['status'] ?? 0);

if ($studentStatus === 0) {
$statusAlert = [
'type' => 'warning',
'icon' => 'fa-solid fa-clock-rotate-left',
'title' => 'Account Status: Pending Administrator Approval',
'msg' => 'Hello <strong>' . htmlspecialchars($studentRow['name']) . '</strong> (Roll No: <strong>' . htmlspecialchars($studentRow['roll_no']) . '</strong>), your registration request was received and is currently <strong>awaiting Administrator verification and approval</strong>.<br><br><span style="font-size: 0.88rem; color: #4B5563;"><i class="fa-solid fa-circle-info"></i> You will be able to sign in to your dashboard as soon as an administrator approves your account.</span>'
];
} elseif ($studentStatus === 2) {
$statusAlert = [
'type' => 'danger',
'icon' => 'fa-solid fa-ban',
'title' => 'Account Status: Deactivated / Suspended',
'msg' => 'Hello <strong>' . htmlspecialchars($studentRow['name']) . '</strong>, your student account has been deactivated or rejected by the administrator. Please contact the Examination Office.'
];
} else {
// Status is 1 (Approved & Active)
unset($_SESSION['login_fail_count'], $_SESSION['login_lock_until']);
session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['student_username'] = $studentRow['email'];
$_SESSION['student_name'] = $studentRow['name'];
$_SESSION['student_roll'] = $studentRow['roll_no'];
$_SESSION['role'] = 'student';
$_SESSION['created_at'] = time();
header("location: ../student/dashboard.php");
exit;
}
}
}

if (!$authenticated && empty($statusAlert)) {
$_SESSION['login_fail_count'] = ($_SESSION['login_fail_count'] ?? 0) + 1;
if ($_SESSION['login_fail_count'] >= 5) {
$_SESSION['login_lock_until'] = time() + 300; // 5 minute lockout
}
$showError = "Invalid credentials. Please verify your username / roll number / email and password, and try again.";
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Examination Portal - Sign In</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
body {
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
background-color: #F8FAFC;
padding: clamp(12px, 3.5vw, 24px);
font-family: 'Plus Jakarta Sans', sans-serif;
color: #1F2937;
}

.auth-wrapper {
width: 100%;
max-width: 440px;
}

.auth-card {
background: #FFFFFF;
border-radius: 16px;
padding: clamp(22px, 5vw, 36px) clamp(16px, 4vw, 30px);
box-shadow: 0 4px 20px rgba(31, 41, 55, 0.08);
border: 1px solid #E5E7EB;
animation: fadeIn 0.35s ease-out;
}

.auth-brand {
text-align: center;
margin-bottom: 26px;
}

.auth-icon {
width: 58px;
height: 58px;
margin: 0 auto 12px;
background: #1E3A5F;
border-radius: 14px;
display: flex;
align-items: center;
justify-content: center;
color: #FFFFFF;
font-size: 1.65rem;
box-shadow: 0 4px 12px rgba(30, 58, 95, 0.25);
}

.auth-brand h1 {
font-size: 1.6rem;
font-weight: 800;
color: #1E3A5F;
margin-bottom: 4px;
}

.auth-brand p {
font-size: 0.94rem;
color: #4B5563;
font-weight: 500;
margin-bottom: 0;
}

.input-group-custom {
position: relative;
display: flex;
align-items: center;
}

.input-group-custom input {
padding-left: 42px;
padding-right: 44px;
font-size: 0.96rem;
font-weight: 500;
color: #1F2937;
background: #FFFFFF;
border: 1.5px solid #E5E7EB;
border-radius: 8px;
width: 100%;
height: 46px;
}

.input-group-custom input:focus {
border-color: #2563EB;
box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.input-icon {
position: absolute;
left: 14px;
color: #6B7280;
font-size: 1rem;
pointer-events: none;
}

.password-toggle {
position: absolute;
right: 12px;
background: transparent;
border: none;
color: #6B7280;
font-size: 1rem;
cursor: pointer;
padding: 6px;
display: flex;
align-items: center;
justify-content: center;
}

.password-toggle:hover {
color: #2563EB;
}

.auth-links {
display: flex;
justify-content: space-between;
align-items: center;
font-size: 0.88rem;
margin-top: 16px;
}

.auth-links a {
color: #2563EB;
font-weight: 600;
text-decoration: none;
transition: color 0.15s;
}

.auth-links a:hover {
color: #1E3A5F;
text-decoration: underline;
}

.extra-actions {
margin-top: 22px;
padding-top: 18px;
border-top: 1px solid #E5E7EB;
display: flex;
flex-direction: column;
gap: 10px;
text-align: center;
font-size: 0.9rem;
}

.quick-result-banner {
background: #EFF6FF;
color: #2563EB;
padding: 10px 14px;
border-radius: 8px;
font-weight: 600;
display: flex;
align-items: center;
justify-content: center;
gap: 8px;
text-decoration: none;
border: 1px solid #DBEAFE;
transition: all 0.2s ease;
}

.quick-result-banner:hover {
background: #2563EB;
color: #FFFFFF;
border-color: #2563EB;
}

@media (max-width: 480px) {
.auth-card {
padding: 24px 18px;
border-radius: 14px;
}
.auth-brand h1 {
font-size: 1.35rem;
}
.auth-links {
flex-direction: column;
gap: 8px;
text-align: center;
}
}
</style>
</head>
<body>

<div class="auth-wrapper">
<div class="auth-card">
<!-- Brand Header -->
<div class="auth-brand">
<div class="auth-icon">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<h1>Examination Portal</h1>
<p>Sign in to access your dashboard</p>
</div>

<!-- Flash Status / Error Alert -->
<?php if ($statusAlert): ?>
<div class="alert alert-<?= htmlspecialchars($statusAlert['type'] ?? 'warning') ?>" style="line-height: 1.5; margin-bottom: 20px; border-radius: 10px;">
<i class="<?= htmlspecialchars($statusAlert['icon'] ?? 'fa-solid fa-circle-info') ?>" style="font-size: 1.25rem; flex-shrink: 0; margin-top: 2px;"></i>
<div>
<?php if (!empty($statusAlert['title'])): ?>
<div style="font-weight: 800; font-size: 0.96rem; margin-bottom: 4px; color: inherit;">
<?= htmlspecialchars($statusAlert['title']) ?>
</div>
<?php endif; ?>
<div style="font-size: 0.92rem;"><?= $statusAlert['msg'] ?></div>
</div>
</div>
<?php elseif ($showError): ?>
<div class="alert alert-danger" style="line-height: 1.5; margin-bottom: 20px; border-radius: 10px;">
<i class="fa-solid fa-circle-exclamation" style="font-size: 1.15rem; flex-shrink: 0; margin-top: 2px;"></i>
<div><?= $showError ?></div>
</div>
<?php endif; ?>

<!-- Universal Login Form -->
<form action="index.php" method="POST" id="loginForm">
<div class="form-group" style="margin-bottom: 18px;">
<label for="username" style="display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.92rem; color: #1F2937;">
Username / Roll No / Email
</label>
<div class="input-group-custom">
<i class="fa-solid fa-user input-icon"></i>
<input type="text" id="username" name="username" value="<?= htmlspecialchars($posted_username) ?>" required placeholder="Enter Username, Roll Number, or Email" autofocus autocomplete="username">
</div>
</div>

<div class="form-group" style="margin-bottom: 22px;">
<label for="password" style="display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.92rem; color: #1F2937;">
Password
</label>
<div class="input-group-custom">
<i class="fa-solid fa-lock input-icon"></i>
<input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
<button type="button" class="password-toggle" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility">
<i class="fa-solid fa-eye" id="passwordEye"></i>
</button>
</div>
</div>

<button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn" style="width: 100%; justify-content: center; height: 46px; font-size: 1rem; border-radius: 8px;">
<i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
</button>

<div class="auth-links">
<a href="student-forgot-password.php">Forgot Password?</a>
<a href="student-registration.php">New Student? Register</a>
</div>
</form>

<!-- Extra Navigation Shortcuts -->
<div class="extra-actions">
<a href="find-result.php" class="quick-result-banner">
<i class="fa-solid fa-magnifying-glass"></i> Check Results Directly
</a>
</div>
</div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
const input = document.getElementById(fieldId);
const eye = document.getElementById('passwordEye');
if (!input || !eye) return;
if (input.type === 'password') {
input.type = 'text';
eye.classList.remove('fa-eye');
eye.classList.add('fa-eye-slash');
} else {
input.type = 'password';
eye.classList.remove('fa-eye-slash');
eye.classList.add('fa-eye');
}
}

// Auto-unregister any leftover/rogue service workers on localhost:8000
if ('serviceWorker' in navigator) {
navigator.serviceWorker.getRegistrations().then(function(registrations) {
for (let registration of registrations) {
registration.unregister();
}
});
}
</script>
</body>
</html>
