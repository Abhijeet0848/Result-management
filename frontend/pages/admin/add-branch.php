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
$branch = trim($_POST['branch'] ?? '');

if (!empty($branch)) {
$addsql = "INSERT INTO branch (branch_name) VALUES ($1)";
$result = pg_query_params($conn, $addsql, array($branch));

if ($result) {
$showAlert = "Branch '" . htmlspecialchars($branch) . "' added successfully!";
} else {
$showError = "Failed to add branch: " . pg_last_error($conn);
}
} else {
$showError = "Branch name cannot be empty.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Branch - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 600px;">
<div class="page-header">
<div>
<h1 class="page-title">Add Academic Branch</h1>
<p>Register a new stream, department, or degree program.</p>
</div>
<a href="manage-branch.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-list-check"></i> View Branches
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

<form action="add-branch.php" method="POST">
<div class="form-group">
<label for="branch">Branch / Department Name *</label>
<input type="text" id="branch" name="branch" required placeholder="Enter Branch Name">
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-branch.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-plus-circle"></i> Save Branch
</button>
</div>
</form>
</div>
</body>
</html>
