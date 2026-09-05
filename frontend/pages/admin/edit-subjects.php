<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$subid = intval($_GET['subid'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && $subid > 0) {
$subjname = trim($_POST['subjname'] ?? '');
$subjcode = trim($_POST['subjcode'] ?? '');
$status = intval($_POST['status'] ?? 1);

if (!empty($subjname) && !empty($subjcode)) {
$addsql = "UPDATE subjects SET subj_name = $1, subj_code = $2, status = $3 WHERE subj_id = $4";
$result = pg_query_params($conn, $addsql, array($subjname, $subjcode, $status, $subid));

if ($result) {
$showAlert = "Subject updated successfully!";
} else {
$showError = "Failed to update subject: " . pg_last_error($conn);
}
} else {
$showError = "Subject name and code cannot be empty.";
}
}

// Fetch current subject details
$subject = [];
if ($conn && $subid > 0) {
$r = pg_query_params($conn, "SELECT * FROM subjects WHERE subj_id = $1", array($subid));
if ($r && pg_num_rows($r) > 0) {
$subject = pg_fetch_assoc($r);
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Subject - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 650px;">
<div class="page-header">
<div>
<h1 class="page-title">Edit Subject</h1>
<p>Update subject description, code, and active status.</p>
</div>
<a href="manage-subjects.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-book-bookmark"></i> View Subjects
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

<form action="edit-subjects.php?subid=<?= $subid ?>" method="POST">
<div class="form-group">
<label for="subjname">Subject Name *</label>
<input type="text" id="subjname" name="subjname" value="<?= htmlspecialchars($subject['subj_name'] ?? '') ?>" required>
</div>

<div class="form-group">
<label for="subjcode">Subject Code *</label>
<input type="text" id="subjcode" name="subjcode" value="<?= htmlspecialchars($subject['subj_code'] ?? '') ?>" required>
</div>

<div class="form-group">
<label for="status">Subject Status</label>
<select id="status" name="status" required>
<option value="1" <?= (isset($subject['status']) && $subject['status'] == 1) ? 'selected' : '' ?>>Active</option>
<option value="0" <?= (isset($subject['status']) && $subject['status'] == 0) ? 'selected' : '' ?>>Inactive</option>
</select>
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-subjects.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Update Subject
</button>
</div>
</form>
</div>
</body>
</html>
