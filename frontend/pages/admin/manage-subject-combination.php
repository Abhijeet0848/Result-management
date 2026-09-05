<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

require_auth('admin');

$showAlert = false;
$showError = false;

include_once __DIR__ . '/../../../backend/helpers/audit.php';

// Handle activation
if (isset($_GET['acid']) && $conn) {
$acid = intval($_GET['acid']);
$sql = "UPDATE subject_comb SET status = 1 WHERE id = $1";
$result = pg_query_params($conn, $sql, array($acid));
if ($result) {
logAudit($conn, "ACTIVATE_SUBJECT_COMB", "Activated subject combination ID $acid.");
$showAlert = "Combination activated successfully!";
} else {
$showError = "Failed to activate combination: " . pg_last_error($conn);
}
}

// Handle deactivation
if (isset($_GET['did']) && $conn) {
$did = intval($_GET['did']);
$sql = "UPDATE subject_comb SET status = 0 WHERE id = $1";
$result = pg_query_params($conn, $sql, array($did));
if ($result) {
logAudit($conn, "DEACTIVATE_SUBJECT_COMB", "Deactivated subject combination ID $did.");
$showAlert = "Combination deactivated successfully!";
} else {
$showError = "Failed to deactivate combination: " . pg_last_error($conn);
}
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $conn) {
$delId = intval($_GET['id']);
$sql = "DELETE FROM subject_comb WHERE id = $1";
$result = pg_query_params($conn, $sql, array($delId));
if ($result) {
logAudit($conn, "DELETE_SUBJECT_COMB", "Deleted subject combination ID $delId.");
$showAlert = "Combination deleted successfully!";
} else {
$showError = "Failed to delete combination: " . pg_last_error($conn);
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subject Combinations - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container">
<div class="page-header">
<div>
<h1 class="page-title">Subject Combinations</h1>
<p>Map subjects to specific branches and academic semesters.</p>
</div>
<a href="add-subject-combination.php" class="btn btn-primary">
<i class="fa-solid fa-layer-group"></i> Add New Combination
</a>
</div>

<?php if ($showAlert): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($showAlert) ?></div>
</div>
<?php endif; ?>

<div class="table-responsive">
<table>
<thead>
<tr>
<th style="width: 5%;">Sr. No.</th>
<th>Branch</th>
<th>Semester</th>
<th>Subject Name</th>
<th>Subject Code</th>
<th>Status</th>
<th style="text-align: center;">Action</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT sc.id, b.branch_name, sm.semester, s.subj_name, s.subj_code, sc.status 
FROM subject_comb sc 
LEFT JOIN branch b ON sc.branch_id = b.branch_id 
LEFT JOIN semester sm ON sc.sem_id = sm.sem_id 
LEFT JOIN subjects s ON sc.subj_id = s.subj_id 
ORDER BY b.branch_name, sm.semester, s.subj_name";
$result = pg_query($conn, $sql);
$c = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
$statusBadge = ($row['status'] == 1) 
? '<span class="badge badge-success">Active</span>' 
: '<span class="badge badge-danger">Inactive</span>';
?>
<tr>
<td><?= $c ?></td>
<td><strong><?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?></strong></td>
<td><?= htmlspecialchars($row['semester'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($row['subj_name'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($row['subj_code'] ?? 'N/A') ?></td>
<td><?= $statusBadge ?></td>
<td style="text-align: center; white-space: nowrap;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
 <?php if ($row['status'] == 1): ?>
 <a href="manage-subject-combination.php?did=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" style="color: #D97706; border-color: #FDE68A; background: #FFFBEB;" title="Deactivate Combination" onclick="return confirm('Deactivate this combination?');">
 <i class="fa-solid fa-ban"></i> Deactivate
 </a>
 <?php else: ?>
 <a href="manage-subject-combination.php?acid=<?= $row['id'] ?>" class="btn btn-sm btn-success" title="Activate Combination" onclick="return confirm('Activate this combination?');">
 <i class="fa-solid fa-check"></i> Activate
 </a>
 <?php endif; ?>
 <a href="manage-subject-combination.php?action=delete&id=<?= $row['id'] ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Combination" onclick="return confirm('Delete this subject combination?');">
 <i class="fa-solid fa-trash"></i>
 </a>
 </div>
 </td>
</tr>
<?php
$c++;
}
} else {
echo '<tr><td colspan="7" style="text-align:center; padding: 25px; color: var(--text-muted);">No subject combinations found. Click "Add New Combination" to assign subjects.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>
</body>
</html>
