<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn) {
$branch_id = (int)($_POST['branch'] ?? 0);
$sem_id = (int)($_POST['semester'] ?? 0);
$subj_id = (int)($_POST['subject'] ?? 0);
$status = 1;

if ($branch_id > 0 && $sem_id > 0 && $subj_id > 0) {
// Check if combination already exists
$check = pg_query_params($conn, "SELECT id FROM subject_comb WHERE branch_id = $1 AND sem_id = $2 AND subj_id = $3", array($branch_id, $sem_id, $subj_id));
if ($check && pg_num_rows($check) > 0) {
$showError = "This subject combination is already mapped.";
} else {
$addsql = "INSERT INTO subject_comb (branch_id, sem_id, subj_id, status) VALUES ($1, $2, $3, $4)";
$result = pg_query_params($conn, $addsql, array($branch_id, $sem_id, $subj_id, $status));

if ($result) {
$showAlert = "Subject combination assigned successfully!";
} else {
$showError = "Failed to add combination: " . pg_last_error($conn);
}
}
} else {
$showError = "Please select Branch, Semester, and Subject.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Subject Combination - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 650px;">
<div class="page-header">
<div>
<h1 class="page-title">Map Subject Combination</h1>
<p>Assign subjects to specific academic streams and semesters.</p>
</div>
<a href="manage-subject-combination.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-layer-group"></i> View Combinations
</a>
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

<form action="add-subject-combination.php" method="POST">
<div class="form-group">
<label for="branch">Academic Branch *</label>
<select id="branch" name="branch" required>
<option value="">-- Select Branch --</option>
<?php
if ($conn) {
$res = pg_query($conn, "SELECT branch_id, branch_name FROM branch ORDER BY branch_name");
while ($b = pg_fetch_assoc($res)) {
echo '<option value="' . htmlspecialchars($b['branch_id']) . '">' . htmlspecialchars($b['branch_name']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label for="semester">Semester *</label>
<select id="semester" name="semester" required>
<option value="">-- Select Semester --</option>
<?php
if ($conn) {
$res = pg_query($conn, "SELECT sem_id, semester FROM semester ORDER BY sem_id");
while ($s = pg_fetch_assoc($res)) {
echo '<option value="' . htmlspecialchars($s['sem_id']) . '">' . htmlspecialchars($s['semester']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label for="subject">Subject *</label>
<select id="subject" name="subject" required>
<option value="">-- Select Subject --</option>
<?php
if ($conn) {
$res = pg_query($conn, "SELECT subj_id, subj_name, subj_code FROM subjects WHERE status = 1 ORDER BY subj_code");
while ($sub = pg_fetch_assoc($res)) {
echo '<option value="' . htmlspecialchars($sub['subj_id']) . '">' . htmlspecialchars($sub['subj_code']) . ' - ' . htmlspecialchars($sub['subj_name']) . '</option>';
}
}
?>
</select>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-subject-combination.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-link"></i> Map Subject Combination
</button>
</div>
</form>
</div>
</body>
</html>
