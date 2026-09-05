<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';

// Fetch summary metrics
$numStudents = 0;
$numPendingStudents = 0;
$numSemesters = 0;
$numBranches = 0;
$numSubjects = 0;
$numResults = 0;
$numPhotocopyReq = 0;
$numRevalReq = 0;

if ($conn) {
    $r1 = @pg_query($conn, "SELECT COUNT(*) FROM student");
    if ($r1) $numStudents = (int)pg_fetch_result($r1, 0, 0);

    $rPending = @pg_query($conn, "SELECT COUNT(*) FROM student WHERE status = 0");
    if ($rPending) $numPendingStudents = (int)pg_fetch_result($rPending, 0, 0);

    $r2 = @pg_query($conn, "SELECT COUNT(*) FROM semester");
    if ($r2) $numSemesters = (int)pg_fetch_result($r2, 0, 0);

    $r3 = @pg_query($conn, "SELECT COUNT(*) FROM branch");
    if ($r3) $numBranches = (int)pg_fetch_result($r3, 0, 0);

    $r4 = @pg_query($conn, "SELECT COUNT(*) FROM subjects");
    if ($r4) $numSubjects = (int)pg_fetch_result($r4, 0, 0);

    $r5 = @pg_query($conn, "SELECT COUNT(DISTINCT roll_no) FROM results");
    if ($r5) $numResults = (int)pg_fetch_result($r5, 0, 0);

    $r6 = @pg_query($conn, "SELECT COUNT(*) FROM photocopy_requests");
    if ($r6) $numPhotocopyReq = (int)pg_fetch_result($r6, 0, 0);

    $r7 = @pg_query($conn, "SELECT COUNT(*) FROM revaluation_requests");
    if ($r7) $numRevalReq = (int)pg_fetch_result($r7, 0, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.dashboard-welcome {
background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
color: #ffffff;
border-radius: var(--radius-lg);
padding: 32px 28px;
margin-bottom: 28px;
box-shadow: 0 10px 25px -5px rgba(49, 46, 129, 0.4);
display: flex;
justify-content: space-between;
align-items: center;
flex-wrap: wrap;
gap: 20px;
}

.welcome-text h1 {
color: #ffffff;
font-size: 1.85rem;
margin-bottom: 6px;
}

.welcome-text p {
color: #cbd5e1;
font-size: 0.95rem;
margin-bottom: 0;
}

.quick-actions-bar {
display: flex;
gap: 12px;
flex-wrap: wrap;
}

.quick-action-btn {
background: rgba(255, 255, 255, 0.12);
color: #ffffff;
border: 1px solid rgba(255, 255, 255, 0.2);
padding: 10px 18px;
border-radius: 10px;
font-size: 0.9rem;
font-weight: 600;
display: flex;
align-items: center;
gap: 8px;
text-decoration: none;
transition: all 0.2s ease;
}

.quick-action-btn:hover {
background: #EFF6FF;
color: #1E3A5F;
transform: translateY(-2px);
}

/* Metric Cards */
.metrics-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
gap: 20px;
margin-bottom: 30px;
}

.metric-card {
background: var(--bg-card);
border: 1px solid var(--border-color);
border-radius: var(--radius-lg);
padding: 22px;
display: flex;
align-items: center;
gap: 18px;
box-shadow: var(--shadow-sm);
text-decoration: none;
color: inherit;
transition: all 0.25s ease;
}

.metric-card:hover {
transform: translateY(-3px);
box-shadow: var(--shadow-lg);
border-color: var(--primary);
}

.metric-icon-wrap {
width: 54px;
height: 54px;
border-radius: 14px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.4rem;
flex-shrink: 0;
}

.m-blue .metric-icon-wrap { background: #e0f2fe; color: #0284c7; }
.m-purple .metric-icon-wrap { background: #f3e8ff; color: #9333ea; }
.m-emerald .metric-icon-wrap { background: #d1fae5; color: #059669; }
.m-amber .metric-icon-wrap { background: #fef3c7; color: #d97706; }
.m-indigo .metric-icon-wrap { background: #e0e7ff; color: #4338ca; }
.m-rose .metric-icon-wrap { background: #ffe4e6; color: #e11d48; }

.metric-info h3 {
font-size: 1.85rem;
font-weight: 800;
margin: 0;
line-height: 1.1;
}

.metric-info span {
font-size: 0.85rem;
color: var(--text-muted);
font-weight: 600;
text-transform: uppercase;
letter-spacing: 0.5px;
}

/* Two Column Panel Grid */
.panel-grid {
display: grid;
grid-template-columns: 2fr 1fr;
gap: 24px;
}

.panel-card {
background: var(--bg-card);
border: 1px solid var(--border-color);
border-radius: var(--radius-lg);
padding: 24px;
box-shadow: var(--shadow-sm);
}

.panel-title {
font-size: 1.15rem;
font-weight: 700;
margin-bottom: 16px;
display: flex;
align-items: center;
justify-content: space-between;
}

.action-list {
display: flex;
flex-direction: column;
gap: 10px;
}

.action-item {
display: flex;
align-items: center;
justify-content: space-between;
padding: 12px 16px;
background: var(--bg-subtle);
border-radius: var(--radius-md);
text-decoration: none;
color: var(--text-main);
font-weight: 600;
font-size: 0.92rem;
transition: all 0.2s ease;
}

.action-item:hover {
background: var(--primary-light);
color: var(--primary);
transform: translateX(4px);
}

.action-item i {
color: var(--primary);
}

@media (max-width: 900px) {
.panel-grid {
grid-template-columns: 1fr;
}
}

@media (max-width: 640px) {
.dashboard-welcome {
padding: 20px 18px;
flex-direction: column;
align-items: stretch;
}
.quick-actions-bar {
flex-direction: column;
width: 100%;
}
.quick-action-btn {
justify-content: center;
width: 100%;
}
.metrics-grid {
grid-template-columns: 1fr;
}
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="margin-top: 20px;">
<!-- Welcome Hero Banner -->
<div class="dashboard-welcome">
<div class="welcome-text">
<h1>Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Administrator') ?>!</h1>
<p>System Overview & Quick Administrative Controls</p>
</div>
<div class="quick-actions-bar">
<a href="add-results.php" class="quick-action-btn">
<i class="fa-solid fa-square-plus"></i> Declare Result
</a>
<a href="add-student.php" class="quick-action-btn">
<i class="fa-solid fa-user-plus"></i> Add Student
</a>
<a href="publish-notice.php" class="quick-action-btn">
<i class="fa-solid fa-bullhorn"></i> Post Notice
</a>
</div>
</div>

<?php if ($numPendingStudents > 0): ?>
    <div class="alert alert-warning" style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 12px; padding: 16px 20px; box-shadow: 0 2px 10px rgba(245, 158, 11, 0.1);">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; background: #FEF3C7; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #D97706; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fa-solid fa-user-clock"></i>
            </div>
            <div>
                <h4 style="margin: 0; color: #92400E; font-size: 1.02rem; font-weight: 800;">
                    <?= $numPendingStudents ?> Student Registration Request<?= $numPendingStudents > 1 ? 's' : '' ?> Pending Approval
                </h4>
                <p style="margin: 2px 0 0; color: #B45309; font-size: 0.88rem; font-weight: 500;">
                    Self-registered students are awaiting your verification to activate their accounts and access their dashboards.
                </p>
            </div>
        </div>
        <a href="manage-students.php?filter=pending" class="btn btn-sm" style="background: #D97706; color: #FFFFFF; font-weight: 700; padding: 9px 18px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);">
            <i class="fa-solid fa-user-check"></i> Review & Approve (<?= $numPendingStudents ?>) →
        </a>
    </div>
<?php endif; ?>

<!-- Metrics Grid -->
<div class="metrics-grid">
<a href="manage-students.php" class="metric-card m-indigo">
<div class="metric-icon-wrap"><i class="fa-solid fa-user-graduate"></i></div>
<div class="metric-info">
<h3 id="cStudents">0</h3>
<span>Total Students</span>
</div>
</a>

<a href="manage-results.php" class="metric-card m-emerald">
<div class="metric-icon-wrap"><i class="fa-solid fa-square-poll-vertical"></i></div>
<div class="metric-info">
<h3 id="cResults">0</h3>
<span>Results Declared</span>
</div>
</a>

<a href="manage-subjects.php" class="metric-card m-purple">
<div class="metric-icon-wrap"><i class="fa-solid fa-book"></i></div>
<div class="metric-info">
<h3 id="cSubjects">0</h3>
<span>Subjects Listed</span>
</div>
</a>

<a href="manage-branch.php" class="metric-card m-blue">
<div class="metric-icon-wrap"><i class="fa-solid fa-code-branch"></i></div>
<div class="metric-info">
<h3 id="cBranches">0</h3>
<span>Branches Active</span>
</div>
</a>

<a href="manage-semester.php" class="metric-card m-amber">
<div class="metric-icon-wrap"><i class="fa-solid fa-calendar-days"></i></div>
<div class="metric-info">
<h3 id="cSemesters">0</h3>
<span>Semesters</span>
</div>
</a>

<a href="manage-revaluation.php" class="metric-card m-rose">
<div class="metric-icon-wrap"><i class="fa-solid fa-envelope-open-text"></i></div>
<div class="metric-info">
<h3 id="cReval"><?= $numRevalReq + $numPhotocopyReq ?></h3>
<span>Pending Requests</span>
</div>
</a>
</div>

<!-- Two Column Panel Section -->
<div class="panel-grid">
<!-- Left Panel: Quick Navigation Management Hub -->
<div class="panel-card">
<div class="panel-title">
<span><i class="fa-solid fa-sliders" style="color: var(--primary); margin-right: 8px;"></i> Management Hub</span>
</div>
<div class="card-grid" style="margin: 0; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
<a href="manage-students.php" class="action-item">
<span><i class="fa-solid fa-users" style="margin-right: 8px;"></i> Students Directory</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
<a href="manage-results.php" class="action-item">
<span><i class="fa-solid fa-chart-pie" style="margin-right: 8px;"></i> Results Ledger</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
<a href="manage-subject-combination.php" class="action-item">
<span><i class="fa-solid fa-layer-group" style="margin-right: 8px;"></i> Subject Combos</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
<a href="manage-photocopy.php" class="action-item">
<span><i class="fa-solid fa-copy" style="margin-right: 8px;"></i> Photocopy Queue</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
<a href="manage-revaluation.php" class="action-item">
<span><i class="fa-solid fa-rotate-right" style="margin-right: 8px;"></i> Revaluation Queue</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
<a href="register-admin.php" class="action-item">
<span><i class="fa-solid fa-user-shield" style="margin-right: 8px;"></i> Add Administrator</span>
<i class="fa-solid fa-chevron-right" style="font-size: 0.8rem;"></i>
</a>
</div>
</div>

<!-- Right Panel: System Status & Security -->
<div class="panel-card">
<div class="panel-title">
<span><i class="fa-solid fa-shield-halved" style="color: var(--success); margin-right: 8px;"></i> Security & Info</span>
</div>
<div class="action-list">
<div style="padding: 12px; background: var(--bg-subtle); border-radius: var(--radius-md); font-size: 0.88rem;">
<div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
<span style="color: var(--text-muted);">Database:</span>
<span class="badge badge-success">PostgreSQL Online</span>
</div>
<div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
<span style="color: var(--text-muted);">Current User:</span>
<span style="font-weight: 700;"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
</div>
<div style="display: flex; justify-content: space-between;">
<span style="color: var(--text-muted);">Session:</span>
<span class="badge badge-info">Active</span>
</div>
</div>

<a href="../auth/change-password.php" class="btn btn-secondary btn-block btn-sm" style="margin-top: 6px;">
<i class="fa-solid fa-key"></i> Update Admin Password
</a>
</div>
</div>
</div>
</div>

<!-- Metric Counter Animation Script -->
<script>
function animateCounter(elementId, targetValue, duration = 800) {
const el = document.getElementById(elementId);
if (!el) return;
let start = 0;
const end = parseInt(targetValue) || 0;
if (end === 0) {
el.innerText = "0";
return;
}
const increment = end / (duration / 16);
const timer = setInterval(() => {
start += increment;
if (start >= end) {
el.innerText = end.toLocaleString();
clearInterval(timer);
} else {
el.innerText = Math.floor(start).toLocaleString();
}
}, 16);
}

document.addEventListener("DOMContentLoaded", () => {
animateCounter("cStudents", <?= $numStudents ?>);
animateCounter("cResults", <?= $numResults ?>);
animateCounter("cSubjects", <?= $numSubjects ?>);
animateCounter("cBranches", <?= $numBranches ?>);
animateCounter("cSemesters", <?= $numSemesters ?>);
});
</script>
</body>
</html>
