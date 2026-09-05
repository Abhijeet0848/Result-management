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

// Handle Delete Result for student in specific semester
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['roll_no']) && isset($_GET['sem_id']) && $conn) {
$delRoll = trim($_GET['roll_no']);
$delSem = intval($_GET['sem_id']);

$delSql = "DELETE FROM results WHERE roll_no = $1 AND sem_id = $2";
$delRes = pg_query_params($conn, $delSql, array($delRoll, $delSem));

if ($delRes) {
logAudit($conn, "DELETE_RESULT", "Deleted semester $delSem result record for Roll No: $delRoll.");
$flashMsg = "Examination results for Roll No '$delRoll' (Semester $delSem) deleted successfully.";
$flashType = "success";
} else {
$flashMsg = "Error deleting results: " . pg_last_error($conn);
$flashType = "danger";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Results - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container">
<div class="page-header">
<div>
<h1 class="page-title">Results Ledger</h1>
<p>View, modify, and audit examination results declared for all registered candidates.</p>
</div>
<a href="add-results.php" class="btn btn-primary">
<i class="fa-solid fa-square-plus"></i> Declare New Results
</a>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($flashType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= htmlspecialchars($flashMsg) ?></div>
</div>
<?php endif; ?>

<!-- Toolbar with Quick Search -->
<div class="table-toolbar">
<div class="search-input-wrapper">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="resultSearch" placeholder="Search by student name, roll no, branch..." onkeyup="filterResults()">
</div>
</div>

<div class="table-responsive">
<table id="resultsTable">
<thead>
<tr>
<th style="width: 5%;">Sr. No.</th>
<th>Student Name</th>
<th>Roll No</th>
<th>Branch</th>
<th>Semester</th>
<th>Status</th>
<th style="text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT DISTINCT s.name, s.roll_no, b.branch_name, sm.semester, r.sem_id 
FROM results r 
JOIN student s ON s.roll_no = r.roll_no 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN semester sm ON r.sem_id = sm.sem_id 
ORDER BY s.roll_no";
$result = pg_query($conn, $sql);
$cnt = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
?>
<tr>
<td><?= $cnt ?></td>
<td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
<td><code style="color: #2563EB; font-weight: 700; background: #EFF6FF; padding: 3px 8px; border-radius: 6px; border: 1px solid #DBEAFE;"><?= htmlspecialchars($row['roll_no']) ?></code></td>
<td><?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?></td>
<td>Semester <?= htmlspecialchars($row['semester'] ?? $row['sem_id']) ?></td>
<td><span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Declared</span></td>
<td style="text-align: center; white-space: nowrap;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
<a href="edit-result.php?stid=<?= urlencode($row['roll_no']) ?>" class="btn-action" title="Edit Marks">
<i class="fa-solid fa-pen-to-square"></i>
</a>
<a href="../auth/result.php?roll_no=<?= urlencode($row['roll_no']) ?>&sem_id=<?= urlencode($row['sem_id']) ?>" target="_blank" class="btn-action" title="View Marksheet">
<i class="fa-solid fa-file-lines"></i>
</a>
<a href="manage-results.php?action=delete&roll_no=<?= urlencode($row['roll_no']) ?>&sem_id=<?= urlencode($row['sem_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Result" onclick="return confirm('Permanently delete this result record?');">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php
$cnt++;
}
} else {
echo '<tr><td colspan="7" style="text-align:center; padding: 30px; color: var(--text-muted);">No examination results declared yet. Click "Declare New Results" to enter student marks.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>

<script>
function filterResults() {
const input = document.getElementById("resultSearch");
const filter = input.value.toLowerCase();
const table = document.getElementById("resultsTable");
const trs = table.getElementsByTagName("tr");

for (let i = 1; i < trs.length; i++) {
const text = trs[i].textContent || trs[i].innerText;
if (text.toLowerCase().indexOf(filter) > -1) {
trs[i].style.display = "";
} else {
trs[i].style.display = "none";
}
}
}
</script>
</body>
</html>
