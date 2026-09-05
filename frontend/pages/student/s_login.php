<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

// Fetch student details along with Branch and Semester names
$email = $_SESSION['student_username'] ?? '';
$student = null;
$branch_name = "N/A";
$sem_name = "N/A";
$roll_no = '';

// Academic Analytics Data
$semestersList = [];
$sgpaList = [];
$latestSubjects = [];
$latestMarks = [];
$cgpa = 0.0;
$recentNotices = [];

if ($conn && !empty($email)) {
$sql = "SELECT s.*, b.branch_name, sm.semester 
FROM student s 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
WHERE s.email = $1";
$result = pg_query_params($conn, $sql, array($email));
if ($result && pg_num_rows($result) > 0) {
$student = pg_fetch_assoc($result);

// Ensure student is approved before accessing dashboard
if (isset($student['status']) && intval($student['status']) !== 1) {
    session_unset();
    session_destroy();
    header("location: ../auth/index.php?status_err=" . urlencode("Account Pending Approval: Your account is currently awaiting administrator review."));
    exit;
}

$branch_name = $student['branch_name'] ?? 'N/A';
$sem_name = isset($student['semester']) ? ('Semester ' . $student['semester']) : 'N/A';
$roll_no = $student['roll_no'] ?? '';
$_SESSION['student_roll'] = $roll_no;
$_SESSION['student_name'] = $student['name'];
$_SESSION['branch_id'] = $student['branch_id'];
$_SESSION['sem_id'] = $student['sem_id'];
}

// 1. Fetch Semester Results to calculate SGPA Progression
if (!empty($roll_no)) {
$cgpa = calculateCGPA($conn, $roll_no);

$resSql = "SELECT r.sem_id, r.subj_id, r.marks, COALESCE(s.credits, 4.0) as credits, s.subj_name, s.subj_code 
 FROM results r 
 LEFT JOIN subjects s ON r.subj_id = s.subj_id 
 WHERE r.roll_no = $1 
 ORDER BY r.sem_id ASC, s.subj_code ASC";
$resQuery = pg_query_params($conn, $resSql, array($roll_no));

$semResults = [];
$latestSem = 0;
if ($resQuery) {
while ($r = pg_fetch_assoc($resQuery)) {
$semId = (int)$r['sem_id'];
if ($semId > $latestSem) $latestSem = $semId;
$semResults[$semId][] = $r;
}
}

foreach ($semResults as $sId => $marksArr) {
$semestersList[] = 'Semester ' . $sId;
$sgpaCalc = calculateSGPA($marksArr);
$sgpaList[] = $sgpaCalc['sgpa'];
}

if ($latestSem > 0 && isset($semResults[$latestSem])) {
foreach ($semResults[$latestSem] as $subItem) {
$latestSubjects[] = $subItem['subj_code'] ?? $subItem['subj_name'];
$latestMarks[] = (float)$subItem['marks'];
}
}
}

// 2. Fetch Latest 3 Notices
$nRes = pg_query($conn, "SELECT title, created_at FROM notices ORDER BY notice_id DESC LIMIT 3");
if ($nRes) {
while ($n = pg_fetch_assoc($nRes)) {
$recentNotices[] = $n;
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal - ResultManagement</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<!-- Chart.js for interactive analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.student-header {
background: #1E3A5F;
color: #FFFFFF;
padding: 16px 24px;
display: flex;
justify-content: space-between;
align-items: center;
border-bottom: 1px solid #E5E7EB;
box-shadow: 0 2px 10px rgba(30, 58, 95, 0.15);
position: sticky;
top: 0;
z-index: 100;
}

.student-brand {
display: flex;
align-items: center;
gap: 12px;
font-family: 'Outfit', sans-serif;
font-size: 1.25rem;
font-weight: 700;
color: #FFFFFF;
text-decoration: none;
}

.student-brand-icon {
width: 38px;
height: 38px;
background: #2563EB;
border-radius: 10px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.1rem;
color: #FFFFFF;
}

.student-hero {
background: linear-gradient(135deg, #1E3A5F 0%, #2563EB 100%);
color: #FFFFFF;
border-radius: var(--radius-lg);
padding: 32px;
margin: 24px 0;
box-shadow: 0 10px 25px -5px rgba(30, 58, 95, 0.35);
display: flex;
align-items: center;
justify-content: space-between;
flex-wrap: wrap;
gap: 20px;
}

.student-profile-info {
display: flex;
align-items: center;
gap: 20px;
}

.student-avatar {
width: 72px;
height: 72px;
border-radius: 50%;
background: rgba(255, 255, 255, 0.2);
border: 3px solid rgba(255, 255, 255, 0.4);
display: flex;
align-items: center;
justify-content: center;
font-size: 2rem;
color: #ffffff;
}

.student-details h1 {
color: #ffffff;
font-size: 1.75rem;
margin-bottom: 4px;
}

.student-meta-chips {
display: flex;
gap: 10px;
flex-wrap: wrap;
margin-top: 8px;
}

.meta-chip {
background: rgba(255, 255, 255, 0.15);
border: 1px solid rgba(255, 255, 255, 0.25);
padding: 4px 12px;
border-radius: 20px;
font-size: 0.82rem;
font-weight: 600;
}

/* Service Cards Grid */
.services-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
gap: 22px;
margin-bottom: 36px;
}

.service-card {
background: var(--bg-card);
border: 1px solid var(--border-color);
border-radius: var(--radius-lg);
padding: 26px;
display: flex;
flex-direction: column;
justify-content: space-between;
text-decoration: none;
color: inherit;
box-shadow: var(--shadow-sm);
transition: all 0.25s ease;
position: relative;
overflow: hidden;
}

.service-card:hover {
transform: translateY(-4px);
box-shadow: var(--shadow-xl);
border-color: var(--primary);
}

.service-icon {
width: 52px;
height: 52px;
border-radius: 14px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.35rem;
margin-bottom: 18px;
}

.sc-emerald .service-icon { background: #d1fae5; color: #059669; }
.sc-indigo .service-icon { background: #e0e7ff; color: #4338ca; }
.sc-blue .service-icon { background: #e0f2fe; color: #0284c7; }
.sc-amber .service-icon { background: #fef3c7; color: #d97706; }
.sc-purple .service-icon { background: #f3e8ff; color: #9333ea; }
.sc-teal .service-icon { background: #ccfbf1; color: #0d9488; }

.service-card h3 {
font-size: 1.2rem;
margin-bottom: 6px;
}

.service-card p {
font-size: 0.88rem;
color: var(--text-muted);
margin-bottom: 16px;
}

.service-action-link {
font-weight: 700;
font-size: 0.88rem;
color: var(--primary);
display: flex;
align-items: center;
gap: 6px;
}

@media (max-width: 640px) {
.student-hero {
padding: 20px 16px;
}
.student-profile-info {
flex-direction: column;
text-align: center;
gap: 12px;
width: 100%;
}
.student-meta-chips {
justify-content: center;
}
.services-grid {
grid-template-columns: 1fr;
}
.student-header {
padding: 10px 14px;
}
.student-brand span {
font-size: 1rem;
}
}
</style>
</head>
<body>

<!-- Student Top Navbar -->
<header class="student-header">
<a href="s_login.php" class="student-brand">
<div class="student-brand-icon">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<span>Student Dashboard</span>
</a>

<div style="display: flex; align-items: center; gap: 14px;">
<a href="/backend/auth/logout.php" class="btn btn-danger btn-sm">
<i class="fa-solid fa-right-from-bracket"></i> Logout
</a>
</div>
</header>

<div class="container" style="border: none; box-shadow: none; background: transparent; padding: 0 clamp(10px, 2.5vw, 20px);">
<!-- Student Profile Hero -->
<div class="student-hero">
<div class="student-profile-info">
<div class="student-avatar">
<i class="fa-solid fa-user-graduate"></i>
</div>
<div class="student-details">
<h1><?= htmlspecialchars($student['name'] ?? $_SESSION['student_name'] ?? 'Student') ?></h1>
<p style="color: #d1fae5; margin: 0;"><?= htmlspecialchars($student['email'] ?? $email) ?></p>
<div class="student-meta-chips">
<span class="meta-chip"><i class="fa-solid fa-id-card"></i> Roll No: <?= htmlspecialchars($student['roll_no'] ?? 'N/A') ?></span>
<span class="meta-chip"><i class="fa-solid fa-code-branch"></i> Branch: <?= htmlspecialchars($branch_name) ?></span>
<span class="meta-chip"><i class="fa-solid fa-calendar-days"></i> Semester: <?= htmlspecialchars($sem_name) ?></span>
<span class="meta-chip" style="background: rgba(22, 163, 74, 0.35); border-color: rgba(22, 163, 74, 0.6);"><i class="fa-solid fa-award"></i> Cumulative CGPA: <strong><?= $cgpa ?></strong></span>
</div>
</div>
</div>

<div>
<a href="updateprofile.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.9); color: #1E3A5F; font-weight: 700;">
<i class="fa-solid fa-user-pen"></i> Edit Profile
</a>
</div>
</div>

<!-- Notification Banner if any -->
<?php if (!empty($recentNotices)): ?>
<div style="background: #FFFFFF; border: 1.5px solid #E5E7EB; border-radius: 12px; padding: 18px 22px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
<div style="display: flex; align-items: center; gap: 14px;">
<div style="width: 42px; height: 42px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
<i class="fa-solid fa-bell"></i>
</div>
<div>
<div style="font-weight: 700; color: #1E3A5F; font-size: 0.95rem;">Latest Academic Notice: <?= htmlspecialchars($recentNotices[0]['title']) ?></div>
<div style="color: #6B7280; font-size: 0.82rem;">Published on <?= date('d M Y', strtotime($recentNotices[0]['created_at'])) ?></div>
</div>
</div>
<a href="view_notices.php" class="btn btn-secondary btn-sm">
View All Circulars <i class="fa-solid fa-arrow-right"></i>
</a>
</div>
<?php endif; ?>

<!-- Academic Performance Analytics Graphs -->
<?php if (!empty($sgpaList)): ?>
<div style="margin-bottom: 32px;">
<h2 style="margin-bottom: 16px; font-size: 1.35rem; color: #1E3A5F; display: flex; align-items: center; gap: 10px;">
<i class="fa-solid fa-chart-line" style="color: #2563EB;"></i> Academic Performance Analytics
</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 20px;">
<!-- Line Chart: SGPA Progression -->
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
<h4 style="margin: 0 0 14px; color: #1E3A5F; font-size: 1rem; font-weight: 700;">
<i class="fa-solid fa-arrow-trend-up" style="color: #16A34A;"></i> SGPA Progression Trend
</h4>
<div style="position: relative; height: 220px;">
<canvas id="sgpaChart"></canvas>
</div>
</div>

<!-- Bar Chart: Latest Subject Marks -->
<?php if (!empty($latestMarks)): ?>
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
<h4 style="margin: 0 0 14px; color: #1E3A5F; font-size: 1rem; font-weight: 700;">
<i class="fa-solid fa-chart-simple" style="color: #2563EB;"></i> Latest Subject Marks Breakdown
</h4>
<div style="position: relative; height: 220px;">
<canvas id="subjectChart"></canvas>
</div>
</div>
<?php endif; ?>
</div>
</div>

<script>
// Render SGPA Progression Chart
const ctxSgpa = document.getElementById('sgpaChart').getContext('2d');
new Chart(ctxSgpa, {
type: 'line',
data: {
labels: <?= json_encode($semestersList) ?>,
datasets: [{
label: 'Semester GPA (SGPA)',
data: <?= json_encode($sgpaList) ?>,
borderColor: '#2563EB',
backgroundColor: 'rgba(37, 99, 235, 0.12)',
borderWidth: 3,
pointBackgroundColor: '#1E3A5F',
pointBorderColor: '#FFFFFF',
pointBorderWidth: 2,
pointRadius: 5,
fill: true,
tension: 0.35
}]
},
options: {
responsive: true,
maintainAspectRatio: false,
scales: {
y: {
min: 0,
max: 10,
ticks: { stepSize: 2 }
}
},
plugins: {
legend: { display: false }
}
}
});

<?php if (!empty($latestMarks)): ?>
// Render Subject Breakdown Chart
const ctxSubj = document.getElementById('subjectChart').getContext('2d');
new Chart(ctxSubj, {
type: 'bar',
data: {
labels: <?= json_encode($latestSubjects) ?>,
datasets: [{
label: 'Score (out of 100)',
data: <?= json_encode($latestMarks) ?>,
backgroundColor: '#1E3A5F',
hoverBackgroundColor: '#2563EB',
borderRadius: 6
}]
},
options: {
responsive: true,
maintainAspectRatio: false,
scales: {
y: {
min: 0,
max: 100,
ticks: { stepSize: 25 }
}
},
plugins: {
legend: { display: false }
}
}
});
<?php endif; ?>
</script>
<?php endif; ?>

<!-- Portal Service Cards -->
<h2 style="margin-bottom: 18px; font-size: 1.4rem;">Student Services</h2>
<div class="services-grid">
<!-- View Results Card -->
<a href="../auth/find-result.php" class="service-card sc-emerald">
<div>
<div class="service-icon"><i class="fa-solid fa-file-invoice"></i></div>
<h3>Semester Results</h3>
<p>View, verify, and print your official examination marksheets and scorecards.</p>
</div>
<div class="service-action-link">
Check Marksheet <i class="fa-solid fa-arrow-right"></i>
</div>
</a>

<!-- Revaluation Card -->
<a href="request-revalution.php" class="service-card sc-indigo">
<div>
<div class="service-icon"><i class="fa-solid fa-rotate-right"></i></div>
<h3>Revaluation Request</h3>
<p>Submit online request for re-evaluation and verification of subject marks.</p>
</div>
<div class="service-action-link">
Apply for Reval <i class="fa-solid fa-arrow-right"></i>
</div>
</a>

<!-- Photocopy Card -->
<a href="request-photocopy.php" class="service-card sc-blue">
<div>
<div class="service-icon"><i class="fa-solid fa-copy"></i></div>
<h3>Answer Sheet Photocopy</h3>
<p>Order certified scanned copy of your evaluated answer sheets.</p>
</div>
<div class="service-action-link">
Request Copy <i class="fa-solid fa-arrow-right"></i>
</div>
</a>

<!-- Notice Board Card -->
<a href="view_notices.php" class="service-card sc-amber">
<div>
<div class="service-icon"><i class="fa-solid fa-bullhorn"></i></div>
<h3>College Notices</h3>
<p>Check public circulars, examination schedules, and department announcements.</p>
</div>
<div class="service-action-link">
Read Notices <i class="fa-solid fa-arrow-right"></i>
</div>
</a>

<!-- Print Documents Card -->
<a href="print_documents.php" class="service-card sc-purple">
<div>
<div class="service-icon"><i class="fa-solid fa-print"></i></div>
<h3>Print Certificates</h3>
<p>Generate and print bonafide certificates and degree verification slips.</p>
</div>
<div class="service-action-link">
Generate Slips <i class="fa-solid fa-arrow-right"></i>
</div>
</a>

<!-- Account Security Card -->
<a href="../auth/change-password.php" class="service-card sc-teal">
<div>
<div class="service-icon"><i class="fa-solid fa-lock"></i></div>
<h3>Security & Password</h3>
<p>Change and update your account login credentials securely.</p>
</div>
<div class="service-action-link">
Manage Password <i class="fa-solid fa-arrow-right"></i>
</div>
</a>
</div>
</div>
</body>
</html>
