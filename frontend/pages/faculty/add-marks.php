<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'faculty') {
header("Location: ../auth/index.php");
exit;
}

$faculty_branch = $_SESSION['faculty_branch'] ?? 1;
$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty';
$showAlert = false;
$showError = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentid']) && !empty($_POST['studentid'])) {
$sem_id = (int)$_POST['semester'];
$st_id = (int)$_POST['studentid'];
$marks = $_POST['marks'] ?? [];

$sql = "SELECT subjects.subj_id FROM subject_comb 
JOIN subjects ON subjects.subj_id = subject_comb.subj_id 
WHERE subject_comb.sem_id = $1 AND subject_comb.branch_id = $2 
ORDER BY subjects.subj_name";
$result = pg_query_params($conn, $sql, array($sem_id, $faculty_branch));

$subject_ids = [];
while ($row = pg_fetch_assoc($result)) {
$subject_ids[] = $row['subj_id'];
}

$allSuccess = true;
foreach ($subject_ids as $index => $subj_id) {
$mark = isset($marks[$index]) ? (float)$marks[$index] : 0;

// Check if already declared
$chk = pg_query_params($conn, "SELECT result_id FROM results WHERE roll_no = $1 AND sem_id = $2 AND subj_id = $3", array($st_id, $sem_id, $subj_id));
if ($chk && pg_num_rows($chk) > 0) {
$upd = pg_query_params($conn, "UPDATE results SET marks = $1 WHERE roll_no = $2 AND sem_id = $3 AND subj_id = $4", array($mark, $st_id, $sem_id, $subj_id));
if (!$upd) $allSuccess = false;
} else {
$ins = pg_query_params($conn, "INSERT INTO results (roll_no, branch_id, sem_id, subj_id, marks) VALUES ($1, $2, $3, $4, $5)", array($st_id, $faculty_branch, $sem_id, $subj_id, $mark));
if (!$ins) $allSuccess = false;
}
}

if ($allSuccess && count($subject_ids) > 0) {
logAudit($conn, "FACULTY_MARK_ENTRY", "Faculty $faculty_name recorded marks for Roll No: $st_id (Sem: $sem_id).");
$showAlert = "Examination results recorded successfully for Student Roll No: " . htmlspecialchars($st_id) . "!";
} else {
$showError = "Error recording marks or no subjects mapped to this semester.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Subject Marks - Faculty Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
<nav class="navbar">
<div class="navbar-container">
<a href="dashboard.php" class="navbar-brand">
<i class="fa-solid fa-graduation-cap"></i> ResultPortal <span style="font-size: 0.75rem; background: #2563EB; color: #fff; padding: 2px 8px; border-radius: 4px; margin-left: 6px;">FACULTY</span>
</a>
<div class="navbar-nav">
<a href="dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Overview</a>
<a href="add-marks.php" class="nav-link active"><i class="fa-solid fa-pen-to-square"></i> Record Marks</a>
<a href="manage-marks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> Manage Marks</a>
<a href="view-students.php" class="nav-link"><i class="fa-solid fa-users"></i> Department Students</a>
<a href="../auth/logout.php" class="nav-link" style="color: #DC2626;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</div>
</div>
</nav>

<div class="container" style="max-width: 750px;">
<div class="page-header">
<div>
<h1 class="page-title">Record Student Marks</h1>
<p>Enter and submit semester marks for students enrolled in your department.</p>
</div>
<a href="manage-marks.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-list-check"></i> View Marks
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

<form method="POST" action="">
<input type="hidden" name="branch" id="branch" value="<?= $faculty_branch ?>">

<div class="form-group">
<label for="semester">Semester</label>
<select name="semester" id="semester" required class="form-control">
<option value="">-- Select Semester --</option>
<?php 
$sql = "SELECT * FROM semester ORDER BY sem_id";
$result = pg_query($conn, $sql);
while ($row = pg_fetch_assoc($result)) {
echo '<option value="' . htmlspecialchars($row['sem_id']) . '">Semester ' . htmlspecialchars($row['semester'] ?? $row['sem_id']) . '</option>';
}
?>
</select>
</div>

<div class="form-group">
<label for="studentid">Student Roll Number</label>
<select name="studentid" id="studentid" required class="form-control">
<option value="">-- First Select Semester --</option>
</select>
</div>

<div class="form-group" style="margin-top: 24px;">
<label style="font-size: 1.05rem;">Enter Subject Marks</label>
<div id="marksContainer" style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
<p style="color: var(--text-muted); margin: 0; text-align: center;">
<i class="fa-solid fa-arrow-pointer"></i> Please select a student above to automatically load their subjects.
</p>
</div>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-marks.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Submit Marks
</button>
</div>
</form>
</div>

<script>
function fetchStudents() {
var branch_id = <?= $faculty_branch ?>;
var semester_id = $("#semester").val();
if (semester_id) {
$.ajax({
type: "POST",
url: '../../../backend/api/fetch_students.php',
data: { branch_id: branch_id, semester_id: semester_id },
success: function(data) {
$("#studentid").html(data);
$("#marksContainer").html('<p style="color: var(--text-muted); margin: 0; text-align: center;">Select a student from the dropdown above.</p>');
}
});
} else {
$("#studentid").html('<option value="">-- First Select Semester --</option>');
$("#marksContainer").html('<p style="color: var(--text-muted); margin: 0; text-align: center;">Please select a student above to load subjects.</p>');
}
}

function getMarks() {
var semester_id = $("#semester").val();
var branch_id = <?= $faculty_branch ?>;
var student_id = $("#studentid").val();
if (semester_id && branch_id && student_id) {
$.ajax({
type: "POST",
url: '../../../backend/api/get_marks.php',
data: { semester_id: semester_id, branch_id: branch_id, student_id: student_id },
success: function(data) {
$("#marksContainer").html(data);
}
});
}
}

$(document).ready(function() {
$("#semester").change(fetchStudents);
$("#studentid").change(getMarks);
});
</script>
</body>
</html>
