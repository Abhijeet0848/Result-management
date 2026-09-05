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
$subjname = trim($_POST['subjname'] ?? '');
$subjcode = trim($_POST['subjcode'] ?? '');
$status = 1;

if (!empty($subjname) && !empty($subjcode)) {
$addsql = "INSERT INTO subjects (subj_name, subj_code, status) VALUES ($1, $2, $3)";
$result = pg_query_params($conn, $addsql, array($subjname, $subjcode, $status));

if ($result) {
$showAlert = "Subject '" . htmlspecialchars($subjname) . "' (" . htmlspecialchars($subjcode) . ") added successfully!";
} else {
$showError = "Failed to add subject: " . pg_last_error($conn);
}
} else {
$showError = "Subject name and code are required.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Subject - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 650px;">
<div class="page-header">
<div>
<h1 class="page-title">Add Course Subject</h1>
<p>Register a subject title and corresponding course curriculum code.</p>
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

<form action="add-subjects.php" method="POST">
<div class="form-group">
<label for="subjname">Subject Name *</label>
<input type="text" id="subjname" name="subjname" required placeholder="Enter Subject Name">
</div>

<div class="form-group">
<label for="subjcode">Subject Code *</label>
<input type="text" id="subjcode" name="subjcode" required placeholder="Enter Subject Code">
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-subjects.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-book-medical"></i> Save Subject
</button>
</div>
</form>
</div>
</body>
</html>
