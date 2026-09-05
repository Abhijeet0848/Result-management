<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$semid = intval($_GET['semid'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && $semid > 0) {
$sem = trim($_POST['semester'] ?? '');

if (!empty($sem)) {
$addsql = "UPDATE semester SET semester = $1 WHERE sem_id = $2";
$result = pg_query_params($conn, $addsql, array($sem, $semid));

if ($result) {
$showAlert = "Semester updated successfully!";
} else {
$showError = "Failed to update semester: " . pg_last_error($conn);
}
} else {
$showError = "Semester title cannot be empty.";
}
}

// Fetch current semester
$currentSem = "";
if ($conn && $semid > 0) {
$r = pg_query_params($conn, "SELECT semester FROM semester WHERE sem_id = $1", array($semid));
if ($r && pg_num_rows($r) > 0) {
$currentSem = pg_fetch_result($r, 0, 'semester');
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Semester - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 600px;">
<div class="page-header">
<div>
<h1 class="page-title">Edit Academic Semester</h1>
<p>Update semester label and term information.</p>
</div>
<a href="manage-semester.php" class="btn btn-secondary btn-sm">
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

<form action="edit-semester.php?semid=<?= $semid ?>" method="POST">
<div class="form-group">
<label for="semester">Semester Title *</label>
<input type="text" id="semester" name="semester" value="<?= htmlspecialchars($currentSem) ?>" required placeholder="Enter updated semester title">
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-semester.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Update Semester
</button>
</div>
</form>
</div>
</body>
</html>
