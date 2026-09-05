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

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && $conn) {
$reqId = intval($_GET['id']);
$del = pg_query_params($conn, "DELETE FROM revaluation_requests WHERE request_id = $1", array($reqId));
if ($del) {
logAudit($conn, "DELETE_REVAL_REQ", "Deleted revaluation request ID: $reqId.");
$flashMsg = "Revaluation application record removed.";
$flashType = "success";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revaluation Requests - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 1100px;">
<div class="page-header">
<div>
<h1 class="page-title">Revaluation Applications</h1>
<p>Monitor and process student re-evaluation requests and fee transaction IDs.</p>
</div>
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
<input type="text" id="revalSearch" placeholder="Search student email, payment ID, or subjects..." onkeyup="filterReval()">
</div>
</div>

<div class="table-responsive">
<table id="revalTable">
<thead>
<tr>
<th style="width: 5%;">Sr. No.</th>
<th style="width: 25%;">Student Email</th>
<th style="width: 30%;">Revaluation Subjects</th>
<th style="width: 15%;">Payment ID</th>
<th style="width: 15%;">Request Date</th>
<th style="width: 10%; text-align: center;">Action</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT * FROM revaluation_requests ORDER BY request_id DESC";
$result = pg_query($conn, $sql);
$c = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
?>
<tr>
<td><?= $c ?></td>
<td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
<td><?= htmlspecialchars($row['subjects']) ?></td>
<td><span class="badge badge-info"><?= htmlspecialchars($row['payment_id'] ?? 'N/A') ?></span></td>
<td><i class="fa-regular fa-clock" style="color: var(--text-light); margin-right: 4px;"></i> <?= htmlspecialchars($row['request_date'] ?? 'N/A') ?></td>
<td style="text-align: center;">
<a href="manage-revaluation.php?action=delete&id=<?= urlencode($row['request_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Record" onclick="return confirm('Delete this revaluation application record?');">
<i class="fa-solid fa-trash"></i>
</a>
</td>
</tr>
<?php
$c++;
}
} else {
echo '<tr><td colspan="5" style="text-align:center; padding: 30px; color: var(--text-muted);">No revaluation applications submitted yet.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>

<script>
function filterReval() {
const input = document.getElementById("revalSearch");
const filter = input.value.toLowerCase();
const table = document.getElementById("revalTable");
const trs = table.getElementsByTagName("tr");

for (let i = 1; i < trs.length; i++) {
const text = trs[i].textContent || trs[i].innerText;
trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
}
}
</script>
</body>
</html>
