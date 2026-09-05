<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

$flashMsg = '';
$flashType = 'success';

// Handle Add Faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? 'Password@123');
$branch_id = intval($_POST['branch_id'] ?? 1);
$department = trim($_POST['department'] ?? '');
$contact = trim($_POST['contact_no'] ?? '');

if (empty($name) || empty($email)) {
$flashMsg = "Name and email are required.";
$flashType = 'danger';
} else {
$chk = pg_query_params($conn, "SELECT faculty_id FROM faculty WHERE email = $1", array($email));
if ($chk && pg_num_rows($chk) > 0) {
$flashMsg = "A faculty account with email '$email' already exists.";
$flashType = 'danger';
} else {
$hashed = password_hash($password, PASSWORD_DEFAULT);
$ins = pg_query_params(
$conn,
"INSERT INTO faculty (name, email, password, branch_id, department, contact_no, status) VALUES ($1, $2, $3, $4, $5, $6, 1)",
array($name, $email, $hashed, $branch_id, $department, $contact)
);
if ($ins) {
logAudit($conn, "ADD_FACULTY", "Created faculty account for $name ($email).");
$flashMsg = "Faculty member '$name' created successfully!";
$flashType = 'success';
} else {
$flashMsg = "Database error: " . pg_last_error($conn);
$flashType = 'danger';
}
}
}
}

// Handle Status Toggle / Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
$facId = intval($_GET['id']);
if ($_GET['action'] === 'toggle') {
$upd = pg_query_params($conn, "UPDATE faculty SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE faculty_id = $1", array($facId));
if ($upd) {
logAudit($conn, "TOGGLE_FACULTY_STATUS", "Toggled status for faculty ID $facId.");
$flashMsg = "Faculty status updated.";
$flashType = 'success';
}
} elseif ($_GET['action'] === 'delete') {
$del = pg_query_params($conn, "DELETE FROM faculty WHERE faculty_id = $1", array($facId));
if ($del) {
logAudit($conn, "DELETE_FACULTY", "Deleted faculty account ID $facId.");
$flashMsg = "Faculty member removed successfully.";
$flashType = 'danger';
}
}
}

// Fetch all faculty
$sql = "SELECT f.*, b.branch_name 
FROM faculty f 
LEFT JOIN branch b ON f.branch_id = b.branch_id 
ORDER BY f.faculty_id DESC";
$res = pg_query($conn, $sql);

// Fetch branches
$bRes = pg_query($conn, "SELECT * FROM branch ORDER BY branch_name ASC");
$branches = [];
if ($bRes) {
while ($b = pg_fetch_assoc($bRes)) { $branches[] = $b; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Faculty & Teachers - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 1050px;">
<div class="page-header">
<div>
<h1 class="page-title">Faculty & Teacher Management</h1>
<p>Add instructors and manage department teaching staff credentials and permissions.</p>
</div>
<a href="dashboard.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Dashboard
</a>
</div>

<?php if (!empty($flashMsg)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($flashType === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
<div><?= htmlspecialchars($flashMsg) ?></div>
</div>
<?php endif; ?>

<!-- Add Faculty Form -->
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; padding: 28px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);">
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #E5E7EB; padding-bottom: 14px;">
<h3 style="margin: 0; color: #1E3A5F; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
<i class="fa-solid fa-user-tie" style="color: #2563EB;"></i> Add New Faculty Member
</h3>
<span style="font-size: 0.82rem; color: #6B7280;">All credentials are encrypted & logged</span>
</div>

<form action="" method="POST" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Full Name *</label>
<input type="text" name="name" placeholder="Enter Full Name" class="form-control" required autocomplete="off" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Email Address *</label>
<input type="email" name="email" placeholder="Enter Email Address" class="form-control" required autocomplete="off" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Assigned Branch *</label>
<select name="branch_id" class="form-control" required style="width: 100%; padding: 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
<option value="">-- Select Branch --</option>
<?php foreach ($branches as $b): ?>
<option value="<?= $b['branch_id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Department Name</label>
<input type="text" name="department" placeholder="Enter Department Name" class="form-control" autocomplete="off" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Contact Phone</label>
<input type="tel" name="contact_no" placeholder="Enter Phone Number" class="form-control" autocomplete="off" style="width: 100%; padding: 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
</div>

<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem; color: #1F2937; margin-bottom: 6px; display: block;">Initial Password *</label>
<div style="position: relative;">
<input type="password" id="facultyPassword" name="password" placeholder="Enter Initial Password" class="form-control" required autocomplete="new-password" style="width: 100%; padding: 10px 42px 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 8px;">
<button type="button" onclick="togglePasswordVisibility('facultyPassword', 'facultyPassIcon')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6B7280; padding: 4px;" title="Show/Hide Password">
<i class="fa-solid fa-eye" id="facultyPassIcon"></i>
</button>
</div>
</div>

<div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; padding-top: 14px; border-top: 1px solid #F3F4F6;">
<button type="reset" class="btn btn-secondary">
<i class="fa-solid fa-rotate-left"></i> Reset Form
</button>
<button type="submit" name="add_faculty" class="btn btn-primary" style="padding: 10px 24px;">
<i class="fa-solid fa-user-plus"></i> Create Faculty Account
</button>
</div>
</form>
</div>

<script>
function togglePasswordVisibility(inputId, iconId) {
const input = document.getElementById(inputId);
const icon = document.getElementById(iconId);
if (input.type === 'password') {
input.type = 'text';
icon.classList.remove('fa-eye');
icon.classList.add('fa-eye-slash');
} else {
input.type = 'password';
icon.classList.remove('fa-eye-slash');
icon.classList.add('fa-eye');
}
}
</script>

<!-- Faculty Directory Table -->
<div class="table-container">
<table>
<thead>
<tr>
<th>Sr. No.</th>
<th>Name</th>
<th>Email</th>
<th>Branch & Dept</th>
<th>Contact</th>
<th>Status</th>
<th style="text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php 
$cnt = 1;
if ($res && pg_num_rows($res) > 0) {
while ($row = pg_fetch_assoc($res)) {
$isActive = intval($row['status']) === 1;
?>
<tr>
<td><?= $cnt ?></td>
<td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td>
<?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?>
<div style="font-size: 0.78rem; color: #4B5563;"><?= htmlspecialchars($row['department'] ?? '') ?></div>
</td>
<td><?= htmlspecialchars($row['contact_no'] ?? '-') ?></td>
<td>
<span class="badge <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
<?= $isActive ? 'Active' : 'Inactive' ?>
</span>
</td>
<td style="text-align: center;">
<div style="display: inline-flex; gap: 8px;">
<a href="manage-faculty.php?action=toggle&id=<?= $row['faculty_id'] ?>" class="btn-action" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
<i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
</a>
<a href="manage-faculty.php?action=delete&id=<?= $row['faculty_id'] ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" onclick="return confirm('Delete this faculty account?');" title="Delete">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php
$cnt++;
}
} else {
echo '<tr><td colspan="7" style="text-align: center; color: #4B5563; padding: 28px;">No faculty accounts registered yet.</td></tr>';
}
?>
</tbody>
</table>
</div>
</div>
</body>
</html>
