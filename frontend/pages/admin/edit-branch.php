<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$bid = intval($_GET['bid'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && $bid > 0) {
$branch = trim($_POST['branch'] ?? '');

if (!empty($branch)) {
$addsql = "UPDATE branch SET branch_name = $1 WHERE branch_id = $2";
$result = pg_query_params($conn, $addsql, array($branch, $bid));

if ($result) {
$showAlert = "Branch updated successfully!";
} else {
$showError = "Failed to update branch: " . pg_last_error($conn);
}
} else {
$showError = "Branch name cannot be empty.";
}
}

// Fetch current branch
$currentBranch = "";
if ($conn && $bid > 0) {
$r = pg_query_params($conn, "SELECT branch_name FROM branch WHERE branch_id = $1", array($bid));
if ($r && pg_num_rows($r) > 0) {
$currentBranch = pg_fetch_result($r, 0, 'branch_name');
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Branch - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 600px;">
<div class="page-header">
<div>
<h1 class="page-title">Edit Academic Branch</h1>
<p>Update branch title and academic department name.</p>
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

<form action="edit-branch.php?bid=<?= $bid ?>" method="POST">
<div class="form-group">
<label for="branch">Branch Name *</label>
<input type="text" id="branch" name="branch" value="<?= htmlspecialchars($currentBranch) ?>" required placeholder="Enter updated branch name">
</div>

<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
<a href="manage-branch.php" class="btn btn-secondary">Cancel</a>
<button type="submit" class="btn btn-primary btn-lg">
<i class="fa-solid fa-floppy-disk"></i> Update Branch
</button>
</div>
</form>
</div>
</body>
</html>
