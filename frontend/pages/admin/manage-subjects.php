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

// Handle Toggle / Delete actions
if (isset($_GET['action']) && isset($_GET['id']) && $conn) {
$subId = intval($_GET['id']);
if ($_GET['action'] === 'toggle') {
$upd = pg_query_params($conn, "UPDATE subjects SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE subj_id = $1", array($subId));
if ($upd) {
logAudit($conn, "TOGGLE_SUBJECT_STATUS", "Toggled status for Subject ID $subId.");
$flashMsg = "Subject status updated successfully.";
$flashType = "success";
}
} elseif ($_GET['action'] === 'delete') {
// Delete combinations & results first if any
pg_query_params($conn, "DELETE FROM results WHERE subj_id = $1", array($subId));
pg_query_params($conn, "DELETE FROM subject_comb WHERE subj_id = $1", array($subId));
$del = pg_query_params($conn, "DELETE FROM subjects WHERE subj_id = $1", array($subId));
if ($del) {
logAudit($conn, "DELETE_SUBJECT", "Deleted subject ID $subId.");
$flashMsg = "Subject deleted successfully.";
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
<title>Manage Subjects - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 1000px;">
<div class="page-header">
<div>
<h1 class="page-title">Subjects Directory</h1>
<p>Configure course subjects, curriculum codes, and active course modules.</p>
</div>
<a href="add-subjects.php" class="btn btn-primary">
<i class="fa-solid fa-book-medical"></i> Add New Subject
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
<input type="text" id="subjSearch" placeholder="Search subject by code or name..." onkeyup="filterSubjects()">
</div>
</div>

<div class="table-responsive">
<table id="subjectsTable">
<thead>
<tr>
<th style="width: 5%;">Sr. No.</th>
<th style="width: 20%;">Subject Code</th>
<th style="width: 45%;">Subject Name</th>
<th style="width: 15%;">Status</th>
<th style="width: 15%; text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT subj_id, subj_name, subj_code, status FROM subjects ORDER BY subj_code";
$result = pg_query($conn, $sql);
$c = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
$statusBadge = ($row['status'] == 1) 
? '<span class="badge badge-success"><i class="fa-solid fa-check"></i> Active</span>' 
: '<span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Inactive</span>';
?>
<tr>
<td><?= $c ?></td>
<td><strong><?= htmlspecialchars($row['subj_code']) ?></strong></td>
<td><?= htmlspecialchars($row['subj_name']) ?></td>
<td><?= $statusBadge ?></td>
<td style="text-align: center;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
<a href="edit-subjects.php?subid=<?= urlencode($row['subj_id']) ?>" class="btn-action" title="Edit Subject">
<i class="fa-solid fa-pen-to-square"></i>
</a>
<a href="manage-subjects.php?action=toggle&id=<?= urlencode($row['subj_id']) ?>" class="btn-action" title="Toggle Status">
<i class="fa-solid <?= ($row['status'] == 1) ? 'fa-ban' : 'fa-check' ?>"></i>
</a>
<a href="manage-subjects.php?action=delete&id=<?= urlencode($row['subj_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Subject" onclick="return confirm('Permanently delete this subject?');">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php
$c++;
}
} else {
echo '<tr><td colspan="5" style="text-align:center; padding: 25px; color: var(--text-muted);">No subjects configured yet.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>

<script>
function filterSubjects() {
const input = document.getElementById("subjSearch");
const filter = input.value.toLowerCase();
const table = document.getElementById("subjectsTable");
const trs = table.getElementsByTagName("tr");

for (let i = 1; i < trs.length; i++) {
const text = trs[i].textContent || trs[i].innerText;
trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
}
}
</script>
</body>
</html>
