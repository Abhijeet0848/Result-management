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

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['semid']) && $conn) {
$semid = intval($_GET['semid']);
// Check if semester is assigned to students or results
$stdChk = pg_query_params($conn, "SELECT reg_id FROM student WHERE sem_id = $1", array($semid));
$resChk = pg_query_params($conn, "SELECT result_id FROM results WHERE sem_id = $1", array($semid));

if ($stdChk && pg_num_rows($stdChk) > 0) {
$flashMsg = "Cannot delete semester: There are enrolled students assigned to this semester.";
$flashType = "danger";
} elseif ($resChk && pg_num_rows($resChk) > 0) {
$flashMsg = "Cannot delete semester: There are declared results tied to this semester.";
$flashType = "danger";
} else {
pg_query_params($conn, "DELETE FROM subject_comb WHERE sem_id = $1", array($semid));
$del = pg_query_params($conn, "DELETE FROM semester WHERE sem_id = $1", array($semid));
if ($del) {
logAudit($conn, "DELETE_SEMESTER", "Deleted semester ID: $semid.");
$flashMsg = "Semester deleted successfully.";
$flashType = "success";
} else {
$flashMsg = "Error deleting semester: " . pg_last_error($conn);
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
<title>Manage Semesters - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 900px;">
<div class="page-header">
<div>
<h1 class="page-title">Semester Directory</h1>
<p>Manage academic semesters and term schedules.</p>
</div>
<a href="add-semester.php" class="btn btn-primary">
<i class="fa-solid fa-plus-circle"></i> Add New Semester
</a>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($flashType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= htmlspecialchars($flashMsg) ?></div>
</div>
<?php endif; ?>

<div class="table-responsive">
<table>
<thead>
<tr>
<th style="width: 10%;">Sr. No.</th>
<th style="width: 25%;">Semester ID</th>
<th style="width: 45%;">Semester Name</th>
<th style="width: 20%; text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT sem_id, semester FROM semester ORDER BY sem_id";
$result = pg_query($conn, $sql);
$c = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
?>
<tr>
<td><?= $c ?></td>
<td><strong><?= htmlspecialchars($row['sem_id']) ?></strong></td>
<td>Semester <?= htmlspecialchars($row['semester']) ?></td>
<td style="text-align: center; white-space: nowrap;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
<a href="edit-semester.php?semid=<?= urlencode($row['sem_id']) ?>" class="btn-action" title="Edit Semester">
<i class="fa-solid fa-pen-to-square"></i>
</a>
<a href="manage-semester.php?action=delete&semid=<?= urlencode($row['sem_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Semester" onclick="return confirm('Delete this semester? Note: Semesters with assigned students cannot be deleted.');">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php
$c++;
}
} else {
echo '<tr><td colspan="4" style="text-align:center; padding: 25px; color: var(--text-muted);">No semesters configured yet.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>
</body>
</html>
