<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';

$email = $_SESSION['student_username'] ?? '';
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && !empty($email)) {
$name = trim($_POST['name'] ?? '');
$gender = $_POST['gender'] ?? '';
$dob = $_POST['dob'] ?? '';
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// Fetch stored password
$sql = "SELECT password FROM student WHERE LOWER(email) = LOWER($1)";
$result = pg_query_params($conn, $sql, array($email));

if ($result && pg_num_rows($result) > 0) {
$stored_password = pg_fetch_result($result, 0, 'password');

if ($current_password === $stored_password) {
$pass_to_save = !empty($new_password) ? $new_password : $stored_password;

$update_sql = "UPDATE student SET name = $1, gender = $2, dob = $3, password = $4 WHERE LOWER(email) = LOWER($5)";
$up_res = pg_query_params($conn, $update_sql, array($name, $gender, $dob, $pass_to_save, $email));

if ($up_res) {
$_SESSION['student_name'] = $name;
$success_message = "Your profile details have been updated successfully!";
} else {
$error_message = "Error updating profile: " . pg_last_error($conn);
}
} else {
$error_message = "The current password entered is incorrect.";
}
} else {
$error_message = "Student account could not be found.";
}
}

// Fetch current details
$student = [];
if ($conn && !empty($email)) {
$sql = "SELECT s.*, b.branch_name, sm.semester 
FROM student s 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
WHERE LOWER(s.email) = LOWER($1)";
$result = pg_query_params($conn, $sql, array($email));
if ($result && pg_num_rows($result) > 0) {
$student = pg_fetch_assoc($result);
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>

<header style="background: #1E3A5F; padding: 12px clamp(12px, 3vw, 24px); color: #FFFFFF; border-bottom: 1px solid #E5E7EB; box-shadow: 0 2px 10px rgba(30,58,95,0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
<a href="dashboard.php" style="color: #FFFFFF; text-decoration: none; font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-graduation-cap" style="color: #2563EB;"></i> Student Portal
</a>
<a href="dashboard.php" class="btn btn-secondary btn-sm" style="background: #FFFFFF; color: #1E3A5F; border: 1px solid #E5E7EB;">
<i class="fa-solid fa-arrow-left"></i> Back to Dashboard
</a>
</header>

<div class="container" style="max-width: 650px;">
<div class="page-header">
<div>
<h1 class="page-title"><i class="fa-solid fa-user-pen" style="color: var(--primary); margin-right: 8px;"></i> Update Profile</h1>
<p>Modify your personal information and update your portal password.</p>
</div>
</div>

<?php if ($success_message): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($success_message) ?></div>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($error_message) ?></div>
</div>
<?php endif; ?>

<form action="update-profile.php" method="POST">
<div class="form-group">
<label for="roll_no">Roll Number (Permanent)</label>
<input type="text" id="roll_no" value="<?= htmlspecialchars($student['roll_no'] ?? '') ?>" readonly style="background: var(--bg-subtle); cursor: not-allowed;">
</div>

<div class="form-group">
<label for="email">Registered Email Address</label>
<input type="email" id="email" value="<?= htmlspecialchars($student['email'] ?? $email) ?>" readonly style="background: var(--bg-subtle); cursor: not-allowed;">
</div>

<div class="form-group">
<label for="name">Full Name</label>
<input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" required placeholder="Your full name">
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
<div class="form-group">
<label for="gender">Gender</label>
<select id="gender" name="gender" required>
<option value="Male" <?= (isset($student['gender']) && strtolower($student['gender']) == 'male') ? 'selected' : '' ?>>Male</option>
<option value="Female" <?= (isset($student['gender']) && strtolower($student['gender']) == 'female') ? 'selected' : '' ?>>Female</option>
<option value="Other" <?= (isset($student['gender']) && strtolower($student['gender']) == 'other') ? 'selected' : '' ?>>Other</option>
</select>
</div>

<div class="form-group">
<label for="dob">Date of Birth</label>
<input type="date" id="dob" name="dob" value="<?= htmlspecialchars($student['dob'] ?? '') ?>" required>
</div>
</div>

<div style="background: var(--bg-subtle); border-radius: var(--radius-md); padding: 18px; margin: 20px 0; border: 1px solid var(--border-color);">
<h4 style="margin-bottom: 12px; font-size: 1rem;"><i class="fa-solid fa-key" style="color: var(--warning);"></i> Change Password (Optional)</h4>

<div class="form-group">
<label for="new_password">New Password (Leave blank to keep current)</label>
<input type="password" id="new_password" name="new_password" placeholder="Enter new password">
</div>

<div class="form-group" style="margin-bottom: 0;">
<label for="current_password">Current Password <span style="color: var(--danger);">* (Required to save changes)</span></label>
<input type="password" id="current_password" name="current_password" required placeholder="Enter current password for verification">
</div>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px;">
<a href="dashboard.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
</button>
</div>
</form>
</div>
</body>
</html>
