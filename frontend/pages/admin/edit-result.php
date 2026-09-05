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

$roll_no = $_GET['stid'] ?? $_GET['roll_no'] ?? $_GET['resultid'] ?? '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn) {
$result_ids = $_POST['result_id'] ?? [];
$marks = $_POST['marks'] ?? [];

$allSuccess = true;
foreach ($result_ids as $index => $rid) {
$mrk = (float)($marks[$index] ?? 0);
$sql = "UPDATE results SET marks = $1 WHERE result_id = $2";
$res = pg_query_params($conn, $sql, array($mrk, intval($rid)));
if (!$res) {
$allSuccess = false;
}
}

if ($allSuccess) {
logAudit($conn, "EDIT_MARKS", "Updated examination marks for Roll No $roll_no (" . count($result_ids) . " subject entries).");
$showAlert = "Student marks updated successfully!";
} else {
$showError = "Error updating marks: " . pg_last_error($conn);
}
}

// Fetch student & results
$student = [];
$rows = [];

if ($conn && !empty($roll_no)) {
$sQuery = "SELECT s.*, b.branch_name, sm.semester 
 FROM student s 
 LEFT JOIN branch b ON s.branch_id = b.branch_id 
 LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
 WHERE s.roll_no = $1";
$sRes = pg_query_params($conn, $sQuery, array($roll_no));
if ($sRes && pg_num_rows($sRes) > 0) {
$student = pg_fetch_assoc($sRes);
}

$rQuery = "SELECT r.result_id, sub.subj_code, sub.subj_name, r.marks 
 FROM results r 
 JOIN subjects sub ON r.subj_id = sub.subj_id 
 WHERE r.roll_no = $1 
 ORDER BY sub.subj_code";
$rRes = pg_query_params($conn, $rQuery, array($roll_no));
if ($rRes) {
$rows = pg_fetch_all($rRes) ?: [];
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Examination Marks - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 750px;">
<div class="page-header">
<div>
<h1 class="page-title">Edit Student Marks</h1>
<p>Update examination scores for Roll No: <?= htmlspecialchars($roll_no) ?></p>
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

<?php if (!empty($student)): ?>
<div style="background: var(--bg-subtle); padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 24px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
<div><strong>Student Name:</strong> <?= htmlspecialchars($student['name']) ?></div>
<div><strong>Branch:</strong> <?= htmlspecialchars($student['branch_name'] ?? 'N/A') ?></div>
<div><strong>Semester:</strong> <?= htmlspecialchars($student['semester'] ?? 'N/A') ?></div>
</div>
<?php endif; ?>

<form action="edit-result.php?stid=<?= urlencode($roll_no) ?>" method="POST">
<div class="table-responsive">
<table>
<thead>
<tr>
<th style="width: 25%;">Subject Code</th>
<th style="width: 50%;">Subject Title</th>
<th style="width: 25%;">Marks (Out of 100)</th>
</tr>
</thead>
<tbody>
<?php if (!empty($rows)): ?>
<?php foreach ($rows as $r): ?>
<tr>
<td>
<strong><?= htmlspecialchars($r['subj_code']) ?></strong>
<input type="hidden" name="result_id[]" value="<?= htmlspecialchars($r['result_id']) ?>">
</td>
<td><?= htmlspecialchars($r['subj_name']) ?></td>
<td>
<input type="number" name="marks[]" min="0" max="100" step="0.5" value="<?= htmlspecialchars($r['marks']) ?>" required style="padding: 6px 10px; font-weight: 700;">
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="3" style="text-align: center; padding: 25px; color: var(--text-muted);">
No subject result records found for this student.
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if (!empty($rows)): ?>
<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-results.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Save Updated Marks
</button>
</div>
<?php endif; ?>
</form>
</div>
</body>
</html>