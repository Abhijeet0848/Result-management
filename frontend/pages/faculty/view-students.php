<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'faculty') {
header("Location: ../auth/index.php");
exit;
}

$faculty_branch = $_SESSION['faculty_branch'] ?? 1;

$sql = "SELECT s.reg_id, s.roll_no, s.name, s.email, s.gender, s.dob, sm.semester, s.status 
FROM student s 
LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
WHERE s.branch_id = $1 AND s.status = 1 
ORDER BY s.roll_no ASC";
$result = pg_query_params($conn, $sql, array($faculty_branch));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Department Student Roster - Faculty Portal</title>
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
<a href="manage-marks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> Manage Marks</a>
<a href="view-students.php" class="nav-link active"><i class="fa-solid fa-users"></i> Department Students</a>
<a href="../auth/logout.php" class="nav-link" style="color: #DC2626;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</div>
</div>
</nav>

<div class="container">
<div class="page-header">
<div>
<h1 class="page-title">Department Student Roster</h1>
<p>Enrolled students in your academic branch.</p>
</div>
<a href="add-marks.php" class="btn btn-primary">
<i class="fa-solid fa-pen-to-square"></i> Record Marks
</a>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Sr. No.</th>
<th>Roll Number</th>
<th>Student Name</th>
<th>Email Address</th>
<th>Gender</th>
<th>Semester</th>
<th>Status</th>
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
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['gender'] ?? 'N/A') ?></td>
<td>Semester <?= htmlspecialchars($row['semester'] ?? '1') ?></td>
<td><span class="badge badge-active">Active</span></td>
</tr>
<?php
$cnt++;
}
} else {
echo '<tr><td colspan="7" style="text-align: center; color: #4B5563; padding: 28px;">No enrolled students found in this department.</td></tr>';
}
?>
</tbody>
</table>
</div>
</div>
</body>
</html>
