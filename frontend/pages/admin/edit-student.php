<?php
session_start();
$showAlert = false;
$showError = false;
$errorMessage = "";
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$stid = isset($_GET['stid']) ? intval($_GET['stid']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$fullname = trim($_POST['fullname'] ?? '');
$rollno = trim($_POST['rollno'] ?? '');
$email = trim($_POST['email'] ?? '');
$gender = $_POST['gender'] ?? '';
$dob = $_POST['birthDate'] ?? '';
$status = intval($_POST['status'] ?? 1);

if (!empty($fullname) && !empty($rollno) && !empty($email)) {
$sql = "UPDATE student SET name = $1, roll_no = $2, email = $3, gender = $4, dob = $5, status = $6 WHERE reg_id = $7";
$result = pg_query_params($conn, $sql, array($fullname, $rollno, $email, $gender, $dob, $status, $stid));

if ($result) {
$showAlert = true;
} else {
$showError = true;
$errorMessage = pg_last_error($conn);
}
} else {
$showError = true;
$errorMessage = "Please fill in all required fields.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Student Profile | ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.page-container {
max-width: 860px;
margin: 32px auto;
padding: 0 16px;
}
.form-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
gap: 18px;
}
.full-width {
grid-column: 1 / -1;
}
.radio-pills {
display: flex;
gap: 12px;
margin-top: 6px;
}
.radio-pill {
flex: 1;
display: flex;
align-items: center;
justify-content: center;
gap: 8px;
padding: 10px 16px;
border: 1px solid var(--border-color, #e2e8f0);
border-radius: 8px;
cursor: pointer;
transition: all 0.2s ease;
font-size: 0.9rem;
font-weight: 500;
}
.radio-pill input[type="radio"] {
margin: 0;
accent-color: #4f46e5;
}
.radio-pill:hover {
border-color: #6366f1;
background: #f8fafc;
}
.readonly-field {
background-color: #f1f5f9;
cursor: not-allowed;
color: #64748b;
}
</style>
</head>
<body style="background: #f8fafc; min-height: 100vh;">

<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="page-container">
<!-- Breadcrumb Header -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
<div>
<a href="manage-students.php" style="color: #6366f1; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
<i class="fa-solid fa-arrow-left"></i> Back to Student Directory
</a>
<h1 style="font-size: 1.6rem; font-weight: 700; color: #0f172a; margin: 0;">Edit Student Profile</h1>
</div>
<a href="manage-students.php" class="btn btn-secondary">
<i class="fa-solid fa-users"></i> All Students
</a>
</div>

<?php if ($showAlert): ?>
<div class="alert alert-success" style="margin-bottom: 20px;">
<i class="fa-solid fa-circle-check"></i>
<div>
<strong>Success!</strong> Student record has been updated successfully.
</div>
</div>
<?php endif; ?>

<?php if ($showError): ?>
<div class="alert alert-danger" style="margin-bottom: 20px;">
<i class="fa-solid fa-circle-exclamation"></i>
<div>
<strong>Update Failed:</strong> <?= htmlspecialchars($errorMessage) ?>
</div>
</div>
<?php endif; ?>

<div class="card">
<?php
$sql = "SELECT student.name, student.roll_no, student.reg_id, student.status, student.email, student.gender, student.dob, branch.branch_name, semester.semester 
FROM student 
LEFT JOIN branch ON student.branch_id = branch.branch_id 
LEFT JOIN semester ON student.sem_id = semester.sem_id 
WHERE student.reg_id = $1";

$result = pg_query_params($conn, $sql, array($stid));

if ($result && pg_num_rows($result) > 0) {
$row = pg_fetch_assoc($result);
?>
<form method="post">
<div class="form-grid">
<div class="form-group full-width">
<label class="form-label" for="fullname"><i class="fa-solid fa-user"></i> Full Name *</label>
<input type="text" id="fullname" name="fullname" class="form-control" value="<?= htmlspecialchars($row['name'] ?? '') ?>" required />
</div>

<div class="form-group">
<label class="form-label" for="rollno"><i class="fa-solid fa-id-card"></i> Roll Number *</label>
<input type="text" id="rollno" name="rollno" class="form-control" value="<?= htmlspecialchars($row['roll_no'] ?? '') ?>" required />
</div>

<div class="form-group">
<label class="form-label" for="email"><i class="fa-solid fa-envelope"></i> Email Address *</label>
<input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email'] ?? '') ?>" required />
</div>

<div class="form-group">
<label class="form-label" for="birthDate"><i class="fa-solid fa-cake-candles"></i> Date of Birth</label>
<input type="date" id="birthDate" name="birthDate" class="form-control" value="<?= htmlspecialchars($row['dob'] ?? '') ?>" required />
</div>

<div class="form-group">
<label class="form-label"><i class="fa-solid fa-venus-mars"></i> Gender</label>
<div class="radio-pills">
<label class="radio-pill">
<input type="radio" name="gender" value="Male" <?= ($row['gender'] == 'Male') ? 'checked' : '' ?> required /> Male
</label>
<label class="radio-pill">
<input type="radio" name="gender" value="Female" <?= ($row['gender'] == 'Female') ? 'checked' : '' ?> required /> Female
</label>
<label class="radio-pill">
<input type="radio" name="gender" value="Other" <?= ($row['gender'] == 'Other') ? 'checked' : '' ?> required /> Other
</label>
</div>
</div>

<div class="form-group">
<label class="form-label"><i class="fa-solid fa-code-branch"></i> Academic Branch</label>
<input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?>" readonly />
</div>

<div class="form-group">
<label class="form-label"><i class="fa-solid fa-calendar-days"></i> Semester</label>
<input type="text" class="form-control readonly-field" value="Semester <?= htmlspecialchars($row['semester'] ?? 'N/A') ?>" readonly />
</div>

<div class="form-group full-width">
<label class="form-label"><i class="fa-solid fa-shield-halved"></i> Account Status</label>
<div class="radio-pills" style="max-width: 360px;">
<label class="radio-pill">
<input type="radio" name="status" value="1" <?= ($row['status'] == 1) ? 'checked' : '' ?> required /> 
<span class="badge badge-success" style="font-size: 0.8rem;">Active</span>
</label>
<label class="radio-pill">
<input type="radio" name="status" value="0" <?= ($row['status'] == 0) ? 'checked' : '' ?> required /> 
<span class="badge badge-danger" style="font-size: 0.8rem;">Blocked</span>
</label>
</div>
</div>
</div>

<div style="display: flex; gap: 12px; margin-top: 28px; justify-content: flex-end;">
<a href="manage-students.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
<i class="fa-solid fa-floppy-disk"></i> Update Student Profile
</button>
</div>
</form>
<?php
} else {
echo '<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Student record not found. <a href="manage-students.php" style="margin-left: 10px; font-weight: 600;">Return to list</a></div>';
}
?>
</div>
</div>

</body>
</html>
