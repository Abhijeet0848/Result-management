<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

$flashMsg = '';
$flashType = 'success';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['bid']) && $conn) {
$bid = intval($_GET['bid']);
// Check if branch is assigned to students
$stdChk = pg_query_params($conn, "SELECT reg_id FROM student WHERE branch_id = $1", array($bid));
if ($stdChk && pg_num_rows($stdChk) > 0) {
$flashMsg = "Cannot delete branch: There are enrolled students assigned to this branch.";
$flashType = "danger";
} else {
pg_query_params($conn, "DELETE FROM subject_comb WHERE branch_id = $1", array($bid));
pg_query_params($conn, "UPDATE faculty SET branch_id = NULL WHERE branch_id = $1", array($bid));
$del = pg_query_params($conn, "DELETE FROM branch WHERE branch_id = $1", array($bid));
if ($del) {
logAudit($conn, "DELETE_BRANCH", "Deleted branch ID: $bid.");
$flashMsg = "Branch deleted successfully.";
$flashType = "success";
} else {
$flashMsg = "Error deleting branch: " . pg_last_error($conn);
$flashType = "danger";
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Branches - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 900px;">
<div class="page-header">
<div>
<h1 class="page-title">Branch Directory</h1>
<p>Manage and configure academic courses, departments, and streams.</p>
</div>
<a href="add-branch.php" class="btn btn-primary">
<i class="fa-solid fa-plus-circle"></i> Add New Branch
</a>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($flashType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= htmlspecialchars($flashMsg) ?></div>
</div>
<?php endif; ?>

<div class="table-toolbar">
<div class="search-input-wrapper">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="branchSearch" placeholder="Search branch name..." onkeyup="filterBranches()">
</div>
</div>

<div class="table-responsive">
<table id="branchTable">
<thead>
<tr>
<th style="width: 10%;">Sr. No.</th>
<th style="width: 25%;">Branch ID</th>
<th style="width: 50%;">Branch Name</th>
<th style="width: 15%; text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT branch_id, branch_name FROM branch ORDER BY branch_id";
$result = pg_query($conn, $sql);
$c = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
?>
<tr>
<td><?= $c ?></td>
<td><strong><?= htmlspecialchars($row['branch_id']) ?></strong></td>
<td><?= htmlspecialchars($row['branch_name']) ?></td>
<td style="text-align: center; white-space: nowrap;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
 <a href="edit-branch.php?bid=<?= urlencode($row['branch_id']) ?>" class="btn-action" title="Edit Branch">
 <i class="fa-solid fa-pen-to-square"></i>
 </a>
 <a href="manage-branch.php?action=delete&bid=<?= urlencode($row['branch_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Branch" onclick="return confirm('Delete this branch? Note: Branches with assigned students cannot be deleted.');">
 <i class="fa-solid fa-trash"></i>
 </a>
 </div>
 </td>
</tr>
<?php
$c++;
}
} else {
echo '<tr><td colspan="4" style="text-align:center; padding: 25px; color: var(--text-muted);">No branches configured yet.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>

<script>
function filterBranches() {
const input = document.getElementById("branchSearch");
const filter = input.value.toLowerCase();
const table = document.getElementById("branchTable");
const trs = table.getElementsByTagName("tr");

for (let i = 1; i < trs.length; i++) {
const text = trs[i].textContent || trs[i].innerText;
trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
}
}
</script>
</body>
</html>
