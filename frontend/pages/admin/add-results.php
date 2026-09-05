<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['studentid']) && !empty($_POST['studentid'])) {
$branch_id = (int)$_POST['branch'];
$sem_id = (int)$_POST['semester'];
$st_id = (int)$_POST['studentid'];
$marks = $_POST['marks'] ?? [];

$sql = "SELECT subjects.subj_id FROM subject_comb 
JOIN subjects ON subjects.subj_id = subject_comb.subj_id 
WHERE subject_comb.sem_id = $1 AND subject_comb.branch_id = $2 
ORDER BY subjects.subj_name";
$result = pg_query_params($conn, $sql, array($sem_id, $branch_id));

$subject_ids = [];
while ($row = pg_fetch_assoc($result)) {
$subject_ids[] = $row['subj_id'];
}

$allSuccess = true;
foreach ($subject_ids as $index => $subj_id) {
$mark = isset($marks[$index]) ? (float)$marks[$index] : 0;

$addsql = "INSERT INTO results (roll_no, branch_id, sem_id, subj_id, marks) VALUES ($1, $2, $3, $4, $5)";
$insert_result = pg_query_params($conn, $addsql, array($st_id, $branch_id, $sem_id, $subj_id, $mark));

if (!$insert_result) {
$allSuccess = false;
}
}

if ($allSuccess && count($subject_ids) > 0) {
logAudit($conn, "DECLARE_RESULT", "Declared semester $sem_id results for Roll No $st_id (Branch ID: $branch_id).");
$showAlert = "Examination Results declared successfully for Roll No: " . htmlspecialchars($st_id) . "!";
} else {
$showError = "Error declaring results or no subjects configured for this semester & branch.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Declare Examination Result - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 750px;">
<div class="page-header">
<div>
<h1 class="page-title">Declare Result</h1>
<p>Select student, branch, semester, and record marks for all active subjects.</p>
</div>
<a href="manage-results.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-list-check"></i> View Results
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
<div class="form-group">
<label for="branch">Branch / Stream</label>
<select name="branch" id="branch" required>
<option value="">-- Select Branch --</option>
<?php 
if ($conn) {
$sql = "SELECT * FROM branch ORDER BY branch_name";
$result = pg_query($conn, $sql);
while ($row = pg_fetch_assoc($result)) {
echo '<option value="' . htmlspecialchars($row['branch_id']) . '">' . htmlspecialchars($row['branch_name']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label for="semester">Semester</label>
<select name="semester" id="semester" required>
<option value="">-- Select Semester --</option>
<?php 
if ($conn) {
$sql = "SELECT * FROM semester ORDER BY sem_id";
$result = pg_query($conn, $sql);
while ($row = pg_fetch_assoc($result)) {
echo '<option value="' . htmlspecialchars($row['sem_id']) . '">Semester ' . htmlspecialchars($row['semester'] ?? $row['sem_id']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label for="studentid">Student Roll Number</label>
<select name="studentid" id="studentid" required>
<option value="">-- First Select Branch & Semester --</option>
</select>
</div>

<div class="form-group" style="margin-top: 24px;">
<label style="font-size: 1.05rem;">Enter Subject Marks</label>
<div id="marksContainer" style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
<p style="color: var(--text-muted); margin: 0; text-align: center;">
<i class="fa-solid fa-arrow-pointer"></i> Please select a student above to automatically load their registered subjects.
</p>
</div>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-results.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Save & Declare Result
</button>
</div>
</form>
</div>

<script>
function fetchStudents() {
var branch_id = $("#branch").val();
var semester_id = $("#semester").val();
if (branch_id && semester_id) {
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
$("#studentid").html('<option value="">-- First Select Branch & Semester --</option>');
$("#marksContainer").html('<p style="color: var(--text-muted); margin: 0; text-align: center;">Please select a student above to load subjects.</p>');
}
}

function getMarks() {
var semester_id = $("#semester").val();
var branch_id = $("#branch").val();
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
$("#branch, #semester").change(fetchStudents);
$("#studentid").change(getMarks);
});
</script>
</body>
</html>
