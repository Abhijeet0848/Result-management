<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

$successMsg = '';
$errorMsg = '';
$previewData = [];

// Handle CSV Template Download
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
$b_id = intval($_GET['branch_id'] ?? 0);
$s_id = intval($_GET['sem_id'] ?? 0);

if ($b_id <= 0 || $s_id <= 0) {
die("Please specify valid branch and semester to download template.");
}

// Fetch subjects for this combination
$subSql = "SELECT s.subj_id, s.subj_name, s.subj_code 
 FROM subject_comb sc 
 JOIN subjects s ON sc.subj_id = s.subj_id 
 WHERE sc.branch_id = $1 AND sc.sem_id = $2 
 ORDER BY s.subj_code ASC";
$subRes = pg_query_params($conn, $subSql, array($b_id, $s_id));
$subjects = [];
while ($r = pg_fetch_assoc($subRes)) {
$subjects[] = $r;
}

// Fetch students
$stdSql = "SELECT roll_no, name FROM student WHERE branch_id = $1 AND sem_id = $2 AND status = 1 ORDER BY roll_no ASC";
$stdRes = pg_query_params($conn, $stdSql, array($b_id, $s_id));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Marks_Entry_Template_Branch' . $b_id . '_Sem' . $s_id . '.csv');

$output = fopen('php://output', 'w');

// Header row
$headers = ['Roll_No', 'Student_Name'];
foreach ($subjects as $s) {
$headers[] = $s['subj_code'] . ' (' . $s['subj_name'] . ')';
}
fputcsv($output, $headers);

// Rows
if ($stdRes) {
while ($std = pg_fetch_assoc($stdRes)) {
$row = [$std['roll_no'], $std['name']];
foreach ($subjects as $s) {
$row[] = ''; // blank for marks entry
}
fputcsv($output, $row);
}
}

fclose($output);
exit;
}

// Handle CSV Upload and Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_upload'])) {
$branch_id = intval($_POST['branch_id'] ?? 0);
$sem_id = intval($_POST['sem_id'] ?? 0);

if ($branch_id <= 0 || $sem_id <= 0) {
$errorMsg = "Please select both Branch and Semester.";
} elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
$errorMsg = "Please select a valid CSV file to upload.";
} else {
$file = $_FILES['csv_file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle !== FALSE) {
$header = fgetcsv($handle, 1000, ",");
if (!$header || count($header) < 3) {
$errorMsg = "Invalid CSV format. Header must contain Roll_No, Student_Name, and subject columns.";
} else {
// Fetch subjects mapped to branch & sem
$subSql = "SELECT s.subj_id, s.subj_code, s.subj_name 
 FROM subject_comb sc 
 JOIN subjects s ON sc.subj_id = s.subj_id 
 WHERE sc.branch_id = $1 AND sc.sem_id = $2 
 ORDER BY s.subj_code ASC";
$subRes = pg_query_params($conn, $subSql, array($branch_id, $sem_id));
$dbSubjects = [];
while ($r = pg_fetch_assoc($subRes)) {
$dbSubjects[] = $r;
}

$importedCount = 0;
$updatedCount = 0;
$rowNum = 1;

while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
$rowNum++;
$roll_no = trim($data[0] ?? '');
if (empty($roll_no)) continue;

// Match subjects columns from index 2
foreach ($dbSubjects as $idx => $subj) {
$colIdx = $idx + 2;
if (isset($data[$colIdx]) && is_numeric(trim($data[$colIdx]))) {
$mark = (float)trim($data[$colIdx]);
if ($mark < 0) $mark = 0;
if ($mark > 100) $mark = 100;

$subj_id = (int)$subj['subj_id'];

// Check if result already exists
$chkSql = "SELECT result_id FROM results WHERE roll_no = $1 AND sem_id = $2 AND branch_id = $3 AND subj_id = $4";
$chkRes = pg_query_params($conn, $chkSql, array($roll_no, $sem_id, $branch_id, $subj_id));

if ($chkRes && pg_num_rows($chkRes) > 0) {
$updSql = "UPDATE results SET marks = $1 WHERE roll_no = $2 AND sem_id = $3 AND branch_id = $4 AND subj_id = $5";
pg_query_params($conn, $updSql, array($mark, $roll_no, $sem_id, $branch_id, $subj_id));
$updatedCount++;
} else {
$insSql = "INSERT INTO results (roll_no, branch_id, sem_id, subj_id, marks) VALUES ($1, $2, $3, $4, $5)";
pg_query_params($conn, $insSql, array($roll_no, $branch_id, $sem_id, $subj_id, $mark));
$importedCount++;
}
}
}
}
fclose($handle);

logAudit($conn, "BULK_MARKS_UPLOAD", "Uploaded CSV marks for Branch ID: $branch_id, Sem ID: $sem_id. Inserted: $importedCount, Updated: $updatedCount.");
$successMsg = "Successfully processed marks spreadsheet! Records inserted: $importedCount, updated: $updatedCount.";
}
} else {
$errorMsg = "Unable to read uploaded CSV file.";
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Mark Entry via CSV - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.workflow-step {
background: #FFFFFF;
border: 1.5px solid #E5E7EB;
border-radius: 12px;
padding: 24px;
margin-bottom: 24px;
}
.step-badge {
display: inline-flex;
align-items: center;
justify-content: center;
width: 28px;
height: 28px;
background: #1E3A5F;
color: #FFFFFF;
border-radius: 50%;
font-size: 0.85rem;
font-weight: 700;
margin-right: 10px;
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 800px;">
<div class="page-header">
<div>
<h1 class="page-title">Bulk Mark Entry via Spreadsheet</h1>
<p>Download pre-formatted class spreadsheets, enter student marks in bulk, and upload in one click.</p>
</div>
<a href="manage-results.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Manage Results
</a>
</div>

<?php if (!empty($successMsg)): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($successMsg) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($errorMsg) ?></div>
</div>
<?php endif; ?>

<!-- STEP 1: Download Template -->
<div class="workflow-step">
<h3 style="margin-top: 0; color: #1E3A5F; display: flex; align-items: center; font-size: 1.15rem;">
<span class="step-badge">1</span> Step 1: Download Class Marksheet Template (CSV)
</h3>
<p style="color: #4B5563; font-size: 0.92rem; margin-bottom: 16px;">
Select the target branch and semester to download a pre-filled CSV with all enrolled students and subject codes ready for mark entry.
</p>

<div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 14px; align-items: flex-end;">
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Branch</label>
<select id="tpl_branch" class="form-control" style="width: 100%;">
<option value="">-- Choose Branch --</option>
<?php 
$bRes = pg_query($conn, "SELECT * FROM branch ORDER BY branch_name");
while ($b = pg_fetch_assoc($bRes)) {
echo '<option value="' . htmlspecialchars($b['branch_id']) . '">' . htmlspecialchars($b['branch_name']) . '</option>';
}
?>
</select>
</div>
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Semester</label>
<select id="tpl_sem" class="form-control" style="width: 100%;">
<option value="">-- Choose Semester --</option>
<?php 
$sRes = pg_query($conn, "SELECT * FROM semester ORDER BY sem_id");
while ($s = pg_fetch_assoc($sRes)) {
echo '<option value="' . htmlspecialchars($s['sem_id']) . '">Semester ' . htmlspecialchars($s['semester'] ?? $s['sem_id']) . '</option>';
}
?>
</select>
</div>
<div>
<button type="button" class="btn btn-secondary" onclick="downloadTemplate()" style="height: 42px;">
<i class="fa-solid fa-file-csv"></i> Download CSV Template
</button>
</div>
</div>
</div>

<!-- STEP 2: Upload Filled CSV -->
<div class="workflow-step">
<h3 style="margin-top: 0; color: #1E3A5F; display: flex; align-items: center; font-size: 1.15rem;">
<span class="step-badge">2</span> Step 2: Upload Completed Marks Spreadsheet
</h3>
<p style="color: #4B5563; font-size: 0.92rem; margin-bottom: 16px;">
Upload your completed CSV file. The system will validate all student roll numbers and update or create examination records.
</p>

<form action="" method="POST" enctype="multipart/form-data" style="display: grid; gap: 16px;">
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Branch</label>
<select name="branch_id" class="form-control" required>
<option value="">-- Choose Branch --</option>
<?php 
$bRes = pg_query($conn, "SELECT * FROM branch ORDER BY branch_name");
while ($b = pg_fetch_assoc($bRes)) {
echo '<option value="' . htmlspecialchars($b['branch_id']) . '">' . htmlspecialchars($b['branch_name']) . '</option>';
}
?>
</select>
</div>
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Semester</label>
<select name="sem_id" class="form-control" required>
<option value="">-- Choose Semester --</option>
<?php 
$sRes = pg_query($conn, "SELECT * FROM semester ORDER BY sem_id");
while ($s = pg_fetch_assoc($sRes)) {
echo '<option value="' . htmlspecialchars($s['sem_id']) . '">Semester ' . htmlspecialchars($s['semester'] ?? $s['sem_id']) . '</option>';
}
?>
</select>
</div>
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Select CSV File (.csv)</label>
<input type="file" name="csv_file" accept=".csv" class="form-control" required style="padding: 9px;">
</div>

<div style="display: flex; justify-content: flex-end; margin-top: 10px;">
<button type="submit" name="process_upload" class="btn btn-primary btn-lg">
<i class="fa-solid fa-cloud-arrow-up"></i> Process & Import Marks
</button>
</div>
</form>
</div>
</div>

<script>
function downloadTemplate() {
const branch = document.getElementById('tpl_branch').value;
const sem = document.getElementById('tpl_sem').value;
if (!branch || !sem) {
alert('Please select both Branch and Semester first.');
return;
}
window.location.href = `bulk-marks-upload.php?action=download_template&branch_id=${branch}&sem_id=${sem}`;
}
</script>
</body>
</html>
