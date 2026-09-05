<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'faculty') {
header("Location: ../auth/index.php");
exit;
}

$faculty_branch = $_SESSION['faculty_branch'] ?? 1;

// Fetch unique students with declared marks for this faculty's branch
$sql = "SELECT DISTINCT r.roll_no, s.name, s.email, r.sem_id, b.branch_name, sm.semester,
COUNT(r.subj_id) as total_subjects, SUM(r.marks) as total_marks 
FROM results r 
LEFT JOIN student s ON r.roll_no = s.roll_no 
LEFT JOIN branch b ON r.branch_id = b.branch_id 
LEFT JOIN semester sm ON r.sem_id = sm.sem_id 
WHERE r.branch_id = $1
GROUP BY r.roll_no, s.name, s.email, r.sem_id, b.branch_name, sm.semester 
ORDER BY r.roll_no ASC";
$result = pg_query_params($conn, $sql, array($faculty_branch));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Department Marks - Faculty Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<nav class="navbar">
<div class="navbar-container">
<a href="dashboard.php" class="navbar-brand">
<i class="fa-solid fa-graduation-cap"></i> ResultPortal <span style="font-size: 0.75rem; background: #2563EB; color: #fff; padding: 2px 8px; border-radius: 4px; margin-left: 6px;">FACULTY</span>
</a>
<div class="navbar-nav">
<a href="dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i> Overview</a>
<a href="add-marks.php" class="nav-link"><i class="fa-solid fa-pen-to-square"></i> Record Marks</a>
<a href="manage-marks.php" class="nav-link active"><i class="fa-solid fa-list-check"></i> Manage Marks</a>
<a href="view-students.php" class="nav-link"><i class="fa-solid fa-users"></i> Department Students</a>
<a href="../auth/logout.php" class="nav-link" style="color: #DC2626;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</div>
</div>
</nav>

<div class="container">
<div class="page-header">
<div>
<h1 class="page-title">Department Examination Results</h1>
<p>View, verify, and edit recorded examination marks for your branch.</p>
</div>
<a href="add-marks.php" class="btn btn-primary">
<i class="fa-solid fa-plus"></i> Record New Marks
</a>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Sr. No.</th>
<th>Roll Number</th>
<th>Student Name</th>
<th>Semester</th>
<th>Total Marks</th>
<th style="text-align: center;">Actions</th>
</tr>
</thead>
<tbody>
<?php 
$cnt = 1;
if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
?>
<tr>
<td><?= $cnt ?></td>
<td><strong><?= htmlspecialchars($row['roll_no']) ?></strong></td>
<td><?= htmlspecialchars($row['name'] ?? 'N/A') ?></td>
<td>Semester <?= htmlspecialchars($row['semester'] ?? $row['sem_id']) ?></td>
<td><strong><?= htmlspecialchars($row['total_marks']) ?></strong> (<?= $row['total_subjects'] ?> Subjects)</td>
<td style="text-align: center;">
<div style="display: inline-flex; gap: 8px;">
<a href="../auth/result.php?roll_no=<?= urlencode($row['roll_no']) ?>&sem_id=<?= urlencode($row['sem_id']) ?>" target="_blank" class="btn-action" title="Preview Marksheet">
<i class="fa-solid fa-eye"></i>
</a>
<a href="../admin/edit-result.php?roll_no=<?= urlencode($row['roll_no']) ?>" class="btn-action" title="Edit Marks">
<i class="fa-solid fa-pen-to-square"></i>
</a>
</div>
</td>
</tr>
<?php
$cnt++;
}
} else {
echo '<tr><td colspan="6" style="text-align: center; color: #4B5563; padding: 28px;">No department result records found.</td></tr>';
}
?>
</tbody>
</table>
</div>
</div>
</body>
</html>
