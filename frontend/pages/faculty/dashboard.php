<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'faculty') {
header("Location: ../auth/index.php");
exit;
}

$faculty_id = $_SESSION['faculty_id'] ?? 0;
$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty Member';
$faculty_dept = $_SESSION['faculty_dept'] ?? 'Academic Department';
$faculty_branch = $_SESSION['faculty_branch'] ?? 1;

// Fetch branch name
$branch_name = 'Engineering';
$bRes = pg_query_params($conn, "SELECT branch_name FROM branch WHERE branch_id = $1", array($faculty_branch));
if ($bRes && pg_num_rows($bRes) > 0) {
$branch_name = pg_fetch_result($bRes, 0, 0);
}

// Fetch stats
$studentCount = 0;
$stdRes = pg_query_params($conn, "SELECT COUNT(*) FROM student WHERE branch_id = $1 AND status = 1", array($faculty_branch));
if ($stdRes) $studentCount = (int)pg_fetch_result($stdRes, 0, 0);

$subjectCount = 0;
$subRes = pg_query_params($conn, "SELECT COUNT(DISTINCT subj_id) FROM subject_comb WHERE branch_id = $1", array($faculty_branch));
if ($subRes) $subjectCount = (int)pg_fetch_result($subRes, 0, 0);

$resultsCount = 0;
$resRes = pg_query_params($conn, "SELECT COUNT(DISTINCT roll_no) FROM results WHERE branch_id = $1", array($faculty_branch));
if ($resRes) $resultsCount = (int)pg_fetch_result($resRes, 0, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Portal - <?= htmlspecialchars($faculty_name) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.faculty-header-card {
background: linear-gradient(135deg, #1E3A5F 0%, #2563EB 100%);
color: #FFFFFF;
border-radius: 16px;
padding: 32px;
margin-bottom: 28px;
box-shadow: 0 4px 20px rgba(30, 58, 95, 0.2);
display: flex;
justify-content: space-between;
align-items: center;
flex-wrap: wrap;
gap: 20px;
}
.faculty-stat-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
gap: 20px;
margin-bottom: 32px;
}
.faculty-stat-card {
background: #FFFFFF;
border: 1px solid #E5E7EB;
border-radius: 12px;
padding: 24px;
display: flex;
align-items: center;
gap: 18px;
transition: all 0.2s ease;
}
.faculty-stat-card:hover {
transform: translateY(-2px);
box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}
.f-stat-icon {
width: 52px;
height: 52px;
border-radius: 12px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.4rem;
}
.action-cards-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
gap: 20px;
}
.action-card {
background: #FFFFFF;
border: 1.5px solid #E5E7EB;
border-radius: 14px;
padding: 26px;
text-decoration: none;
color: inherit;
display: block;
transition: all 0.2s ease;
}
.action-card:hover {
border-color: #2563EB;
transform: translateY(-3px);
box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
}
@media (max-width: 640px) {
.faculty-header-card {
padding: 20px 16px;
flex-direction: column;
align-items: stretch;
gap: 14px;
}
.faculty-header-card .btn {
width: 100%;
justify-content: center;
}
.faculty-stat-grid {
grid-template-columns: 1fr;
gap: 12px;
}
.action-cards-grid {
grid-template-columns: 1fr;
gap: 14px;
}
}
</style>
</head>
<body>
<!-- Top Nav for Faculty -->
<nav class="navbar">
<div class="navbar-container">
<a href="dashboard.php" class="navbar-brand">
<i class="fa-solid fa-graduation-cap"></i> ResultPortal <span style="font-size: 0.75rem; background: #2563EB; color: #fff; padding: 2px 8px; border-radius: 4px; margin-left: 6px;">FACULTY</span>
</a>
<div class="navbar-nav">
<a href="dashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i> Overview</a>
<a href="add-marks.php" class="nav-link"><i class="fa-solid fa-pen-to-square"></i> Record Marks</a>
<a href="manage-marks.php" class="nav-link"><i class="fa-solid fa-list-check"></i> Manage Marks</a>
<a href="view-students.php" class="nav-link"><i class="fa-solid fa-users"></i> Department Students</a>
<a href="../auth/logout.php" class="nav-link" style="color: #DC2626;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</div>
</div>
</nav>

<div class="container" style="max-width: 1050px;">
<div class="faculty-header-card">
<div>
<span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">
<i class="fa-solid fa-chalkboard-user"></i> Faculty Dashboard
</span>
<h1 style="font-size: 1.85rem; font-weight: 800; margin: 10px 0 6px;"><?= htmlspecialchars($faculty_name) ?></h1>
<p style="margin: 0; opacity: 0.9; font-size: 0.95rem;">
<i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($faculty_dept) ?> &bull; <?= htmlspecialchars($branch_name) ?>
</p>
</div>
<div>
<a href="add-marks.php" class="btn btn-primary" style="background: #FFFFFF; color: #1E3A5F; border: none; font-weight: 700;">
<i class="fa-solid fa-plus"></i> Enter Marks
</a>
</div>
</div>

<!-- Quick Stats -->
<div class="faculty-stat-grid">
<div class="faculty-stat-card">
<div class="f-stat-icon" style="background: #EFF6FF; color: #2563EB;">
<i class="fa-solid fa-user-graduate"></i>
</div>
<div>
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600;">Active Students</div>
<div style="font-size: 1.6rem; font-weight: 800; color: #1E3A5F;"><?= $studentCount ?></div>
</div>
</div>

<div class="faculty-stat-card">
<div class="f-stat-icon" style="background: #FEF3C7; color: #D97706;">
<i class="fa-solid fa-book-open"></i>
</div>
<div>
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600;">Department Subjects</div>
<div style="font-size: 1.6rem; font-weight: 800; color: #1E3A5F;"><?= $subjectCount ?></div>
</div>
</div>

<div class="faculty-stat-card">
<div class="f-stat-icon" style="background: #DCFCE7; color: #16A34A;">
<i class="fa-solid fa-square-poll-vertical"></i>
</div>
<div>
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600;">Evaluated Results</div>
<div style="font-size: 1.6rem; font-weight: 800; color: #1E3A5F;"><?= $resultsCount ?></div>
</div>
</div>
</div>

<!-- Quick Action Shortcuts -->
<h2 style="font-size: 1.25rem; font-weight: 800; color: #1E3A5F; margin-bottom: 16px;">Quick Actions</h2>
<div class="action-cards-grid">
<a href="add-marks.php" class="action-card">
<i class="fa-solid fa-pen-to-square" style="font-size: 1.6rem; color: #2563EB; margin-bottom: 12px; display: block;"></i>
<h3 style="margin: 0 0 6px; font-size: 1.1rem; color: #1E3A5F;">Enter Subject Marks</h3>
<p style="margin: 0; color: #4B5563; font-size: 0.88rem;">Select student and record semester examination marks for department subjects.</p>
</a>

<a href="manage-marks.php" class="action-card">
<i class="fa-solid fa-list-check" style="font-size: 1.6rem; color: #16A34A; margin-bottom: 12px; display: block;"></i>
<h3 style="margin: 0 0 6px; font-size: 1.1rem; color: #1E3A5F;">Review & Edit Marks</h3>
<p style="margin: 0; color: #4B5563; font-size: 0.88rem;">Look up previously submitted marks, make score adjustments, and verify ledgers.</p>
</a>

<a href="view-students.php" class="action-card">
<i class="fa-solid fa-users-viewfinder" style="font-size: 1.6rem; color: #D97706; margin-bottom: 12px; display: block;"></i>
<h3 style="margin: 0 0 6px; font-size: 1.1rem; color: #1E3A5F;">Department Roster</h3>
<p style="margin: 0; color: #4B5563; font-size: 0.88rem;">Browse student profiles, contact emails, and current semester enrolments.</p>
</a>
</div>
</div>
</body>
</html>
