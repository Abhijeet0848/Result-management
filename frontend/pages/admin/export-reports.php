<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/academic.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

$branch_id = intval($_GET['branch_id'] ?? 1);
$sem_id = intval($_GET['sem_id'] ?? 1);

// Fetch branches and semesters for filter dropdowns
$branches = [];
$bRes = pg_query($conn, "SELECT * FROM branch ORDER BY branch_name");
while ($b = pg_fetch_assoc($bRes)) { $branches[] = $b; }

$semesters = [];
$sRes = pg_query($conn, "SELECT * FROM semester ORDER BY sem_id");
while ($s = pg_fetch_assoc($sRes)) { $semesters[] = $s; }

// Fetch subjects for chosen branch & semester
$subSql = "SELECT s.subj_id, s.subj_name, s.subj_code, COALESCE(s.credits, 4.0) as credits 
 FROM subject_comb sc 
 JOIN subjects s ON sc.subj_id = s.subj_id 
 WHERE sc.branch_id = $1 AND sc.sem_id = $2 
 ORDER BY s.subj_code ASC";
$subRes = pg_query_params($conn, $subSql, array($branch_id, $sem_id));
$subjects = [];
while ($row = pg_fetch_assoc($subRes)) {
$subjects[] = $row;
}

// Fetch all students in this branch & semester
$stdSql = "SELECT roll_no, name FROM student WHERE branch_id = $1 AND sem_id = $2 AND status = 1 ORDER BY roll_no ASC";
$stdRes = pg_query_params($conn, $stdSql, array($branch_id, $sem_id));
$students = [];
while ($std = pg_fetch_assoc($stdRes)) {
$students[] = $std;
}

// Fetch all marks for this branch & sem
$marksMap = [];
$resultsSql = "SELECT roll_no, subj_id, marks FROM results WHERE branch_id = $1 AND sem_id = $2";
$resQuery = pg_query_params($conn, $resultsSql, array($branch_id, $sem_id));
while ($rm = pg_fetch_assoc($resQuery)) {
$marksMap[$rm['roll_no']][$rm['subj_id']] = (float)$rm['marks'];
}

// Process Ledger and Calculate Statistics
$ledgerRows = [];
$subjectStats = [];
foreach ($subjects as $s) {
$subjectStats[$s['subj_id']] = [
'name' => $s['subj_name'],
'code' => $s['subj_code'],
'appeared' => 0,
'passed' => 0,
'failed' => 0
];
}

$classTotalAppeared = 0;
$classTotalPassed = 0;
$classTotalFailed = 0;

foreach ($students as $std) {
$rNo = $std['roll_no'];
$subMarksList = [];
$totalMarks = 0;
$maxMarks = count($subjects) * 100;
$hasMarks = false;
$isStudentPass = true;

foreach ($subjects as $s) {
$sId = $s['subj_id'];
$m = isset($marksMap[$rNo][$sId]) ? $marksMap[$rNo][$sId] : null;
if ($m !== null) {
$hasMarks = true;
$totalMarks += $m;
$subMarksList[] = ['marks' => $m, 'credits' => $s['credits']];

$subjectStats[$sId]['appeared']++;
if ($m >= 40) {
$subjectStats[$sId]['passed']++;
} else {
$subjectStats[$sId]['failed']++;
$isStudentPass = false;
}
} else {
$isStudentPass = false;
}
}

if ($hasMarks) {
$classTotalAppeared++;
$sgpaData = calculateSGPA($subMarksList);
$percentage = ($maxMarks > 0) ? round(($totalMarks / $maxMarks) * 100, 2) : 0;
$status = ($isStudentPass && !$sgpaData['has_backlog']) ? 'PASS' : 'FAIL / ATKT';

if ($status === 'PASS') {
$classTotalPassed++;
} else {
$classTotalFailed++;
}

$ledgerRows[] = [
'roll_no' => $rNo,
'name' => $std['name'],
'marks' => $marksMap[$rNo] ?? [],
'total_marks' => $totalMarks,
'max_marks' => $maxMarks,
'percentage' => $percentage,
'sgpa' => $sgpaData['sgpa'],
'status' => $status
];
}
}

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_ledger_csv') {
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Result_Ledger_Branch' . $branch_id . '_Sem' . $sem_id . '_' . date('Y-m-d') . '.csv');

$out = fopen('php://output', 'w');
$head = ['Roll_No', 'Student_Name'];
foreach ($subjects as $s) {
$head[] = $s['subj_code'] . ' (' . $s['subj_name'] . ')';
}
$head = array_merge($head, ['Total_Marks', 'Max_Marks', 'Percentage', 'SGPA', 'Status']);
fputcsv($out, $head);

foreach ($ledgerRows as $row) {
$line = [$row['roll_no'], $row['name']];
foreach ($subjects as $s) {
$line[] = isset($row['marks'][$s['subj_id']]) ? $row['marks'][$s['subj_id']] : 'N/A';
}
$line[] = $row['total_marks'];
$line[] = $row['max_marks'];
$line[] = $row['percentage'] . '%';
$line[] = $row['sgpa'];
$line[] = $row['status'];
fputcsv($out, $line);
}
fclose($out);
exit;
}

$overallPassRate = ($classTotalAppeared > 0) ? round(($classTotalPassed / $classTotalAppeared) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consolidated Examination Reports & Ledgers - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.stat-card-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-box {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 18px 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stat-box-num {
    font-size: 1.85rem;
    font-weight: 800;
    margin-top: 4px;
}

@media print {
    .navbar, .filter-bar, .no-print, .btn, footer {
        display: none !important;
    }
    body {
        background: #FFFFFF !important;
        padding: 0 !important;
    }
    .container {
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
</head>
<body>
<div class="no-print">
<?php include_once __DIR__ . '/../../components/nav.php'; ?>
</div>

<div class="container" style="max-width: 1100px;">
<div class="page-header">
    <div>
        <h1 class="page-title">Consolidated Result Ledger & Analytics</h1>
        <p>Semester-wide academic ledgers, grade point distribution, and subject pass percentages.</p>
    </div>
    <div class="no-print header-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Report
        </button>
        <a href="export-reports.php?branch_id=<?= $branch_id ?>&sem_id=<?= $sem_id ?>&action=export_ledger_csv" class="btn btn-primary">
            <i class="fa-solid fa-file-csv"></i> Export to CSV / Excel
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar no-print" style="background: #FFFFFF; border: 1px solid #E5E7EB; padding: 18px; border-radius: 12px; margin-bottom: 24px;">
    <form action="" method="GET" class="form-grid" style="align-items: flex-end;">
        <div class="form-group" style="margin: 0;">
            <label style="font-weight: 600; font-size: 0.88rem;">Academic Branch</label>
            <select name="branch_id" class="form-control">
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['branch_id'] ?>" <?= ($b['branch_id'] == $branch_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['branch_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label style="font-weight: 600; font-size: 0.88rem;">Semester</label>
            <select name="sem_id" class="form-control">
                <?php foreach ($semesters as $s): ?>
                <option value="<?= $s['sem_id'] ?>" <?= ($s['sem_id'] == $sem_id) ? 'selected' : '' ?>>
                    Semester <?= htmlspecialchars($s['semester'] ?? $s['sem_id']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="height: 46px; width: 100%; justify-content: center;">
                <i class="fa-solid fa-filter"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- KPI Stats Cards -->
<div class="stat-card-row">
<div class="stat-box">
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Students Appeared</div>
<div class="stat-box-num" style="color: #1E3A5F;"><?= $classTotalAppeared ?></div>
</div>
<div class="stat-box">
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Passed</div>
<div class="stat-box-num" style="color: #16A34A;"><?= $classTotalPassed ?></div>
</div>
<div class="stat-box">
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Failed / ATKT</div>
<div class="stat-box-num" style="color: #DC2626;"><?= $classTotalFailed ?></div>
</div>
<div class="stat-box">
<div style="color: #4B5563; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Pass Percentage</div>
<div class="stat-box-num" style="color: #2563EB;"><?= $overallPassRate ?>%</div>
</div>
</div>

<!-- Subject Pass/Fail Summary -->
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px; padding: 22px; margin-bottom: 24px;">
<h3 style="margin-top: 0; color: #1E3A5F; font-size: 1.15rem; margin-bottom: 14px;">
<i class="fa-solid fa-chart-pie"></i> Subject-Wise Pass / Fail Performance
</h3>
<div class="table-container" style="box-shadow: none; border: 1px solid #E5E7EB; margin-bottom: 0;">
<table>
<thead>
<tr>
<th>Subject Code</th>
<th>Subject Name</th>
<th style="text-align: center;">Appeared</th>
<th style="text-align: center;">Passed</th>
<th style="text-align: center;">Failed</th>
<th style="text-align: center;">Pass Rate</th>
</tr>
</thead>
<tbody>
<?php if (!empty($subjects)): ?>
<?php foreach ($subjects as $s): 
$stat = $subjectStats[$s['subj_id']];
$rate = ($stat['appeared'] > 0) ? round(($stat['passed'] / $stat['appeared']) * 100, 1) : 0;
?>
<tr>
<td><strong><?= htmlspecialchars($s['subj_code']) ?></strong></td>
<td><?= htmlspecialchars($s['subj_name']) ?></td>
<td style="text-align: center;"><?= $stat['appeared'] ?></td>
<td style="text-align: center; color: #16A34A; font-weight: 700;"><?= $stat['passed'] ?></td>
<td style="text-align: center; color: #DC2626; font-weight: 700;"><?= $stat['failed'] ?></td>
<td style="text-align: center;">
<span class="badge <?= ($rate >= 75) ? 'badge-active' : (($rate >= 40) ? 'badge-pending' : 'badge-inactive') ?>">
<?= $rate ?>%
</span>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6" style="text-align: center; color: #4B5563;">No subjects mapped.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<!-- Master Result Ledger -->
<div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px; padding: 22px;">
<h3 style="margin-top: 0; color: #1E3A5F; font-size: 1.15rem; margin-bottom: 14px;">
<i class="fa-solid fa-table-list"></i> Master Marks Ledger
</h3>
<div class="table-container" style="box-shadow: none; border: 1px solid #E5E7EB;">
<table>
<thead>
<tr>
<th>Roll No</th>
<th>Student Name</th>
<?php foreach ($subjects as $s): ?>
<th style="text-align: center;"><?= htmlspecialchars($s['subj_code']) ?></th>
<?php endforeach; ?>
<th style="text-align: center;">Total</th>
<th style="text-align: center;">%</th>
<th style="text-align: center;">SGPA</th>
<th style="text-align: center;">Status</th>
</tr>
</thead>
<tbody>
<?php if (!empty($ledgerRows)): ?>
<?php foreach ($ledgerRows as $lr): ?>
<tr>
<td><strong><?= htmlspecialchars($lr['roll_no']) ?></strong></td>
<td><?= htmlspecialchars($lr['name']) ?></td>
<?php foreach ($subjects as $s): 
$mVal = isset($lr['marks'][$s['subj_id']]) ? $lr['marks'][$s['subj_id']] : '-';
?>
<td style="text-align: center; <?= (is_numeric($mVal) && $mVal < 40) ? 'color: #DC2626; font-weight: 700;' : '' ?>">
<?= htmlspecialchars($mVal) ?>
</td>
<?php endforeach; ?>
<td style="text-align: center; font-weight: 600;"><?= $lr['total_marks'] ?> / <?= $lr['max_marks'] ?></td>
<td style="text-align: center;"><?= $lr['percentage'] ?>%</td>
<td style="text-align: center; font-weight: 700; color: #1E3A5F;"><?= $lr['sgpa'] ?></td>
<td style="text-align: center;">
<span class="badge <?= ($lr['status'] === 'PASS') ? 'badge-active' : 'badge-inactive' ?>">
<?= $lr['status'] ?>
</span>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="<?= count($subjects) + 6 ?>" style="text-align: center; color: #4B5563; padding: 24px;">No examination marks recorded for this semester yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
