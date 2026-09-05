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
$sem = trim($_POST['semester'] ?? '');

if (!empty($sem)) {
$addsql = "INSERT INTO semester (semester) VALUES ($1)";
$result = pg_query_params($conn, $addsql, array($sem));

if ($result) {
$showAlert = "Semester '" . htmlspecialchars($sem) . "' added successfully!";
} else {
$showError = "Failed to add semester: " . pg_last_error($conn);
}
} else {
$showError = "Semester name cannot be empty.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Semester - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 600px;">
<div class="page-header">
<div>
<h1 class="page-title">Add Academic Semester</h1>
<p>Configure a new semester term or academic year module.</p>
</div>
<a href="manage-sem.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-calendar-check"></i> View Semesters
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

<form action="add-semester.php" method="POST">
<div class="form-group">
<label for="semester">Semester Title / Name *</label>
<input type="text" id="semester" name="semester" required placeholder="Enter Semester Name">
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-sem.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-calendar-plus"></i> Save Semester
</button>
</div>
</form>
</div>
</body>
</html>
