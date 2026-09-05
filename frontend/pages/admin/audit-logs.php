<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

// Fetch filter parameters
$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action_type'] ?? '');

$sql = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];
$pIdx = 1;

if (!empty($search)) {
$sql .= " AND (username ILIKE $" . $pIdx . " OR details ILIKE $" . $pIdx . " OR action ILIKE $" . $pIdx . ")";
$params[] = '%' . $search . '%';
$pIdx++;
}

if (!empty($actionFilter)) {
$sql .= " AND action = $" . $pIdx;
$params[] = $actionFilter;
$pIdx++;
}

$sql .= " ORDER BY created_at DESC LIMIT 150";

$res = !empty($params) ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);

// Distinct actions for dropdown
$actionsRes = pg_query($conn, "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
$allActions = [];
if ($actionsRes) {
while ($ar = pg_fetch_assoc($actionsRes)) {
$allActions[] = $ar['action'];
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security & Admin Audit Logs - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 1050px;">
<div class="page-header">
<div>
<h1 class="page-title">Security & System Audit Trails</h1>
<p>Complete historical log of all mark modifications, result declarations, student updates, and administrative events.</p>
</div>
<a href="dashboard.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Dashboard
</a>
</div>

<!-- Filter / Search Bar -->
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px; padding: 18px; margin-bottom: 24px;">
<form action="" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
<div class="form-group" style="margin: 0; flex: 2; min-width: min(100%, 200px);">
<label style="font-weight: 600; font-size: 0.88rem;">Search Log Description / User</label>
<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search keyword, roll no, username..." class="form-control">
</div>

<div class="form-group" style="margin: 0; flex: 1; min-width: min(100%, 160px);">
<label style="font-weight: 600; font-size: 0.88rem;">Action Type</label>
<select name="action_type" class="form-control">
<option value="">-- All Actions --</option>
<?php foreach ($allActions as $act): ?>
<option value="<?= htmlspecialchars($act) ?>" <?= ($actionFilter === $act) ? 'selected' : '' ?>>
<?= htmlspecialchars($act) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<button type="submit" class="btn btn-primary" style="height: 46px; flex: 0 0 auto;">
<i class="fa-solid fa-filter"></i> Filter Logs
</button>
<?php if (!empty($search) || !empty($actionFilter)): ?>
<a href="audit-logs.php" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center;">
<i class="fa-solid fa-rotate-left"></i> Reset
</a>
<?php endif; ?>
</form>
</div>

<!-- Logs Table -->
<div class="table-container">
<table>
<thead>
<tr>
<th style="width: 160px;">Timestamp</th>
<th style="width: 140px;">User & Role</th>
<th style="width: 180px;">Action</th>
<th>Details & Changes</th>
<th style="width: 120px;">IP Address</th>
</tr>
</thead>
<tbody>
<?php if ($res && pg_num_rows($res) > 0): ?>
<?php while ($log = pg_fetch_assoc($res)): ?>
<tr>
<td style="font-size: 0.84rem; color: #4B5563; white-space: nowrap;">
<i class="fa-regular fa-clock"></i> <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
</td>
<td>
<strong><?= htmlspecialchars($log['username']) ?></strong>
<div style="font-size: 0.78rem; color: #2563EB; font-weight: 600;"><?= htmlspecialchars($log['user_role'] ?? 'Admin') ?></div>
</td>
<td>
<span class="badge badge-active" style="font-size: 0.76rem; background: #EEF2F6; color: #1E3A5F; border: 1px solid #CBD5E1;">
<?= htmlspecialchars($log['action']) ?>
</span>
</td>
<td style="font-size: 0.88rem; color: #1F2937; line-height: 1.4;">
<?= htmlspecialchars($log['details']) ?>
</td>
<td style="font-size: 0.82rem; color: #6B7280; font-family: monospace;">
<?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="5" style="text-align: center; color: #4B5563; padding: 32px;">
<i class="fa-solid fa-shield-halved" style="font-size: 2rem; color: #9CA3AF; margin-bottom: 8px; display: block;"></i>
No audit log records found matching the query.
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</body>
</html>
