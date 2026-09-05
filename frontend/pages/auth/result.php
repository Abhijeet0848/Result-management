<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

// Extract roll number and optional filters from POST or GET
$roll_no = trim($_POST['stid'] ?? ($_POST['roll_no'] ?? ($_GET['roll_no'] ?? ($_GET['stid'] ?? ($_SESSION['student_roll'] ?? '')))));
$branch_id = intval($_POST['branch_id'] ?? ($_GET['branch_id'] ?? 0));
$sem_id = intval($_POST['sem_id'] ?? ($_GET['sem_id'] ?? 0));

$rows = [];
$student = null;
$error = '';
$availableSemesters = [];
$totalMarks = 0;
$maxMarks = 0;
$percentage = 0;
$totalCredits = 0;
$totalGradePoints = 0;
$overallStatus = "PASS";

if (!empty($roll_no) && $conn) {
// 1. Fetch Student Details
$stdQuery = "SELECT s.*, b.branch_name, sm.semester, m.mother_name 
 FROM student s 
 LEFT JOIN branch b ON s.branch_id = b.branch_id 
 LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
 LEFT JOIN mother m ON s.roll_no = m.student_roll_no 
 WHERE s.roll_no = $1";
$stdRes = pg_query_params($conn, $stdQuery, array($roll_no));
if ($stdRes && pg_num_rows($stdRes) > 0) {
$student = pg_fetch_assoc($stdRes);
}

// 2. Fetch Available Semesters for this student
$semListQuery = "SELECT DISTINCT sem_id FROM results WHERE roll_no = $1 ORDER BY sem_id ASC";
$semListRes = pg_query_params($conn, $semListQuery, array($roll_no));
if ($semListRes && pg_num_rows($semListRes) > 0) {
$availableSemesters = pg_fetch_all_columns($semListRes, 0);
}

// If sem_id is not specified, default to first available semester or student's active semester
if ($sem_id === 0) {
if (!empty($availableSemesters)) {
$sem_id = intval($availableSemesters[0]);
} elseif (!empty($student['sem_id'])) {
$sem_id = intval($student['sem_id']);
} else {
$sem_id = 1;
}
}

// 3. Fetch Subject Results for the selected semester
$resultsQuery = "SELECT r.sem_id, sub.subj_code, sub.subj_name, r.marks
 FROM results r
 JOIN subjects sub ON r.subj_id = sub.subj_id
 WHERE r.roll_no = $1 AND r.sem_id = $2
 ORDER BY sub.subj_code ASC";
$resultsResult = pg_query_params($conn, $resultsQuery, array($roll_no, $sem_id));

if ($resultsResult && pg_num_rows($resultsResult) > 0) {
$rows = pg_fetch_all($resultsResult);
foreach ($rows as $row) {
$m = (float)$row['marks'];
$credits = 2; // Standard university 2-credit per subject unit
$totalCredits += $credits;
$totalMarks += $m;
$maxMarks += 100;

// Grade points
if ($m >= 90) $gp = 10;
elseif ($m >= 75) $gp = 9;
elseif ($m >= 60) $gp = 8;
elseif ($m >= 55) $gp = 7;
elseif ($m >= 50) $gp = 6;
elseif ($m >= 40) $gp = 5;
else $gp = 0;

$totalGradePoints += ($gp * $credits);

if ($m < 40) {
$overallStatus = "FAIL";
}
}
if ($maxMarks > 0) {
$percentage = round(($totalMarks / $maxMarks) * 100, 2);
}
$sgpa = ($totalCredits > 0) ? round($totalGradePoints / $totalCredits, 2) : 0;
} else {
$error = "No examination results found for Roll Number: " . htmlspecialchars($roll_no) . " (Semester $sem_id)";
}
} elseif (empty($roll_no)) {
$error = "Please enter a valid Student Roll Number to check results.";
}

// Construct dynamic verification URL for QR Code
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/auth/result.php?roll_no=" . urlencode($roll_no) . "&sem_id=" . urlencode($sem_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Marksheet - Roll No: <?= htmlspecialchars($roll_no) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<!-- Local QRCode.js Library for tamper-proof verification -->
<script src="../../assets/js/qrcode.min.js"></script>
<!-- html2pdf for direct 1-click vector PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
:root {
--doc-navy: #0f2744;
--doc-gold: #9a6b12;
--doc-border: #1e293b;
--doc-bg: #f8fafc;
}

body {
background-color: #f1f5f9;
margin: 0;
padding: 20px 10px;
font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
color: #0f172a;
}

/* Screen Action Toolbar */
.screen-top-bar {
max-width: 840px;
margin: 0 auto 14px;
display: flex;
justify-content: space-between;
align-items: center;
flex-wrap: wrap;
gap: 12px;
}

.action-group {
display: flex;
gap: 8px;
align-items: center;
flex-wrap: wrap;
}

.sem-selector-bar {
max-width: 840px;
margin: 0 auto 14px;
display: flex;
align-items: center;
gap: 8px;
background: #ffffff;
padding: 8px 14px;
border-radius: 10px;
border: 1px solid #e2e8f0;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.sem-pill {
padding: 5px 14px;
border-radius: 6px;
font-size: 0.82rem;
font-weight: 600;
color: #475569;
background: #f8fafc;
border: 1px solid #e2e8f0;
text-decoration: none;
transition: all 0.2s ease;
}

.sem-pill:hover {
background: #e2e8f0;
color: #0f172a;
}

.sem-pill.active {
background: #4f46e5;
color: #ffffff;
border-color: #4f46e5;
box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
}

.result-search-form {
display: flex;
gap: 6px;
align-items: center;
}

.result-search-form input {
background: #ffffff;
border: 1px solid #cbd5e1;
padding: 6px 12px;
border-radius: 6px;
font-size: 0.85rem;
color: #0f172a;
outline: none;
width: 170px;
}

/* Authentic Academic Marksheet / PDF Document Container */
.pdf-page-container {
max-width: 840px;
margin: 0 auto 16px;
background: #ffffff;
box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
border-radius: 2px;
box-sizing: border-box;
}

.marksheet-document {
background: #ffffff;
padding: 16px 20px 14px;
border: 2px solid #0f2744;
outline: 1px solid #9a6b12;
outline-offset: -5px;
position: relative;
box-sizing: border-box;
font-family: 'Times New Roman', Times, serif;
color: #000000;
line-height: 1.18;
}

/* Subtle Security Watermark */
.marksheet-document::before {
content: "SSR COLLEGE OF ARTS, COMMERCE & SCIENCE * OFFICIAL TRANSCRIPT * SSR COLLEGE OF ARTS, COMMERCE & SCIENCE";
position: absolute;
top: 50%;
left: 50%;
transform: translate(-50%, -50%) rotate(-32deg);
font-size: 1.55rem;
font-weight: 800;
color: rgba(15, 39, 68, 0.035);
width: 140%;
text-align: center;
pointer-events: none;
letter-spacing: 3px;
z-index: 1;
font-family: Arial, sans-serif;
text-transform: uppercase;
}

/* Institutional Header */
.doc-header {
text-align: center;
border-bottom: 2px double #0f2744;
padding-bottom: 6px;
margin-bottom: 8px;
position: relative;
z-index: 2;
}

.doc-header-top {
display: flex;
align-items: center;
justify-content: center;
gap: 12px;
margin-bottom: 2px;
}

.doc-crest-icon {
font-size: 1.6rem;
color: #0f2744;
}

.doc-inst-title {
font-size: 1.22rem;
font-weight: 900;
color: #0f2744;
text-transform: uppercase;
letter-spacing: 0.8px;
margin: 0;
font-family: 'Times New Roman', Times, serif;
}

.doc-inst-sub {
font-size: 0.76rem;
color: #334155;
font-weight: 600;
margin: 1px 0;
font-family: Arial, sans-serif;
}

.doc-title-badge-row {
margin-top: 4px;
display: flex;
justify-content: center;
align-items: center;
gap: 10px;
}

.doc-title-badge {
display: inline-block;
background: #0f2744;
color: #ffffff;
font-size: 0.76rem;
font-weight: 700;
letter-spacing: 0.8px;
padding: 2px 14px;
border-radius: 2px;
text-transform: uppercase;
font-family: Arial, sans-serif;
}

.doc-session-tag {
font-size: 0.72rem;
color: #0f2744;
font-weight: 700;
font-family: Arial, sans-serif;
}

/* Student Metadata Table */
.doc-meta-table {
width: 100%;
border-collapse: collapse;
margin-bottom: 6px;
border: 1px solid #64748b;
font-size: 7.6pt;
font-family: Arial, sans-serif;
position: relative;
z-index: 2;
}

.doc-meta-table td {
padding: 3px 6px;
border: 1px solid #cbd5e1;
vertical-align: middle;
}

.meta-label {
color: #475569;
font-weight: 700;
text-transform: uppercase;
font-size: 6.8pt;
background: #f8fafc;
width: 16%;
}

.meta-val {
color: #0f172a;
font-weight: 700;
font-size: 7.8pt;
width: 34%;
}

/* Subject Ledger Table */
.doc-ledger-table {
width: 100%;
border-collapse: collapse;
margin-bottom: 6px;
font-size: 7.2pt;
font-family: Arial, sans-serif;
position: relative;
z-index: 2;
}

.doc-ledger-table th {
background: #0f2744;
color: #ffffff;
padding: 3.5px 4px;
font-weight: 700;
text-align: center;
border: 1px solid #0f2744;
font-size: 6.8pt;
text-transform: uppercase;
letter-spacing: 0.3px;
}

.doc-ledger-table td {
padding: 2.5px 4px;
border: 1px solid #94a3b8;
text-align: center;
color: #000000;
line-height: 1.12;
}

.doc-ledger-table tr:nth-child(even) td {
background-color: #fbfcfe;
}

.doc-ledger-table td.subj-desc {
text-align: left;
font-weight: 600;
padding-left: 6px;
}

.grade-badge {
font-weight: 800;
font-size: 7.4pt;
}

.g-pass { color: #047857; }
.g-fail { color: #b91c1c; }

/* Summary & Performance Box */
.doc-summary-box {
display: flex;
justify-content: space-between;
align-items: center;
background: #f8fafc;
border: 1px solid #0f2744;
padding: 4px 10px;
margin-bottom: 6px;
font-family: Arial, sans-serif;
position: relative;
z-index: 2;
}

.sum-item {
display: flex;
flex-direction: column;
align-items: center;
}

.sum-title {
color: #475569;
font-size: 6.5pt;
font-weight: 700;
text-transform: uppercase;
}

.sum-val {
font-size: 8.5pt;
font-weight: 800;
color: #0f2744;
}

.doc-status-pass {
background: #059669;
color: #ffffff;
padding: 1px 8px;
border-radius: 2px;
font-weight: 800;
font-size: 7pt;
letter-spacing: 0.4px;
}

.doc-status-fail {
background: #dc2626;
color: #ffffff;
padding: 1px 8px;
border-radius: 2px;
font-weight: 800;
font-size: 7pt;
}

/* Grading Legend & Security Row */
.doc-security-row {
display: flex;
justify-content: space-between;
align-items: center;
border-top: 1px solid #cbd5e1;
border-bottom: 1px solid #cbd5e1;
padding: 3px 6px;
margin-bottom: 6px;
font-size: 6.2pt;
color: #475569;
font-family: Arial, sans-serif;
position: relative;
z-index: 2;
}

.grading-scale-text {
font-weight: 600;
}

.security-barcode {
font-family: 'Courier New', monospace;
font-weight: 900;
letter-spacing: 1.5px;
font-size: 6.5pt;
color: #0f2744;
display: flex;
align-items: center;
gap: 4px;
}

/* Dual Signatures & Official Stamp Grid */
.doc-sign-grid {
display: flex;
justify-content: space-between;
align-items: flex-end;
margin-top: 10px;
padding-top: 4px;
font-size: 6.8pt;
color: #0f172a;
font-family: Arial, sans-serif;
position: relative;
z-index: 2;
}

.sign-block {
text-align: center;
width: 28%;
}

.sign-line {
border-top: 1px solid #0f2744;
margin-bottom: 3px;
}

.official-seal-box {
text-align: center;
width: 20%;
border: 1.5px dashed #9a6b12;
border-radius: 50%;
height: 38px;
width: 38px;
margin: 0 auto;
display: flex;
flex-direction: column;
align-items: center;
justify-content: center;
font-size: 5pt;
font-weight: 800;
color: #9a6b12;
text-transform: uppercase;
line-height: 1;
}

/* Document Services Box on Screen */
.doc-services-box {
max-width: 840px;
margin: 0 auto;
background: #ffffff;
border: 1px solid #e2e8f0;
box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
border-radius: 10px;
padding: 14px 18px;
text-align: center;
color: #0f172a;
}

.doc-services-box h4 {
margin: 0 0 10px;
font-size: 0.92rem;
color: #1e293b;
font-weight: 700;
}

.doc-services-grid {
display: flex;
justify-content: center;
gap: 8px;
flex-wrap: wrap;
}

.mobile-scroll-hint {
display: none;
text-align: center;
font-size: 0.8rem;
color: #64748b;
background: #f1f5f9;
padding: 6px 12px;
border-radius: 6px;
margin-bottom: 8px;
max-width: 840px;
margin: 0 auto 10px;
}

/* Screen Mobile Breakpoints */
@media screen and (max-width: 768px) {
.screen-top-bar {
flex-direction: column;
align-items: stretch;
gap: 10px;
padding: 10px 14px;
}

.action-group {
width: 100%;
justify-content: space-between;
}

.action-group .btn {
flex: 1 1 auto;
justify-content: center;
font-size: 0.8rem;
padding: 7px 10px;
}

.result-search-form {
width: 100%;
}

.result-search-form input {
flex: 1;
width: auto;
}

.sem-selector-bar {
overflow-x: auto;
-webkit-overflow-scrolling: touch;
white-space: nowrap;
padding: 8px 12px;
justify-content: flex-start;
}

.sem-pill {
flex-shrink: 0;
}

.mobile-scroll-hint {
display: flex;
align-items: center;
justify-content: center;
gap: 6px;
}

.pdf-page-container {
width: 100%;
overflow-x: auto;
-webkit-overflow-scrolling: touch;
border-radius: 6px;
box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.marksheet-document {
min-width: 620px;
}
}

@media screen and (max-width: 480px) {
body {
padding: 8px;
}

.action-group {
flex-wrap: wrap;
gap: 6px;
}

.doc-services-box {
padding: 12px;
}

.doc-services-grid .btn {
width: 100%;
justify-content: center;
}
}

/* ==========================================================================
 STRICT SINGLE-PAGE PRINT RULES (A4 Guaranteed)
 ========================================================================== */
@page {
size: A4 portrait;
margin: 5mm 6mm;
}

@media print {
html, body {
background: #ffffff !important;
padding: 0 !important;
margin: 0 !important;
color: #000000 !important;
width: 100% !important;
height: 100% !important;
-webkit-print-color-adjust: exact !important;
print-color-adjust: exact !important;
}

.screen-top-bar,
.sem-selector-bar,
.doc-services-box,
.mobile-scroll-hint,
.no-print,
#loginPromptModal {
display: none !important;
}

.pdf-page-container {
max-width: 100% !important;
width: 100% !important;
margin: 0 !important;
padding: 0 !important;
box-shadow: none !important;
border: none !important;
border-radius: 0 !important;
overflow-x: visible !important;
}

.marksheet-document {
border: 2px solid #0f2744 !important;
outline: 1px solid #9a6b12 !important;
outline-offset: -3px !important;
box-shadow: none !important;
border-radius: 0 !important;
width: 100% !important;
max-width: 100% !important;
min-width: auto !important;
margin: 0 !important;
padding: 10px 14px 8px !important;
page-break-inside: avoid !important;
break-inside: avoid !important;
box-sizing: border-box !important;
}

.doc-header {
padding-bottom: 4px !important;
margin-bottom: 6px !important;
}

.doc-inst-title {
font-size: 1.15rem !important;
}

.doc-crest-icon {
font-size: 1.3rem !important;
}

.doc-title-badge {
background: #0f2744 !important;
color: #ffffff !important;
font-size: 0.72rem !important;
padding: 1px 12px !important;
}

.doc-meta-table td {
padding: 2.5px 5px !important;
font-size: 7.2pt !important;
}

.doc-ledger-table th {
background: #0f2744 !important;
color: #ffffff !important;
padding: 3px 4px !important;
font-size: 6.8pt !important;
}

.doc-ledger-table td {
padding: 2px 4px !important;
font-size: 7.2pt !important;
line-height: 1.12 !important;
}

.doc-summary-box {
padding: 3px 8px !important;
margin-bottom: 5px !important;
page-break-inside: avoid !important;
break-inside: avoid !important;
}

.doc-sign-grid {
margin-top: 8px !important;
page-break-inside: avoid !important;
break-inside: avoid !important;
}
}
</style>
</head>
<body>

<!-- Screen Action Toolbar -->
<div class="screen-top-bar no-print">
<div class="action-group">
<a href="../../../index.php" class="btn btn-secondary btn-sm" style="background: #FFFFFF; color: #1F2937; border-color: #E5E7EB; box-shadow: 0 1px 2px rgba(31,41,55,0.05);">
<i class="fa-solid fa-house"></i> Homepage
</a>
<button onclick="downloadAsPDF()" class="btn btn-primary btn-sm" style="padding: 7px 15px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
<i class="fa-solid fa-download"></i> Download PDF
</button>
<button onclick="window.print()" class="btn btn-primary btn-sm" style="padding: 7px 15px;">
<i class="fa-solid fa-print"></i> Print / Save A4
</button>
</div>

<form method="GET" action="result.php" class="result-search-form">
<input type="text" name="roll_no" placeholder="Enter Roll Number" value="<?= htmlspecialchars($roll_no) ?>" required autocomplete="off">
<button type="submit" class="btn btn-primary btn-sm" style="padding: 6px 12px;">
<i class="fa-solid fa-magnifying-glass"></i> Search
</button>
</form>
</div>

<!-- Semester Selector Bar If Available -->
<?php if (!empty($availableSemesters) && count($availableSemesters) > 1): ?>
<div class="sem-selector-bar no-print">
<span style="color: #94a3b8; font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">
<i class="fa-solid fa-layer-group"></i> Semester:
</span>
<?php foreach ($availableSemesters as $sNum): ?>
<a href="result.php?roll_no=<?= urlencode($roll_no) ?>&sem_id=<?= $sNum ?>" class="sem-pill <?= ($sem_id == $sNum) ? 'active' : '' ?>">
Semester <?= $sNum ?>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Error Alert If Any -->
<?php if ($error): ?>
<div class="container no-print" style="max-width: 600px; text-align: center; margin-bottom: 20px;">
<div class="alert alert-danger" style="margin-bottom: 0;">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($error) ?></div>
</div>
</div>
<?php endif; ?>

<!-- Official Printable Marksheet Document Container -->
<?php if (!empty($rows)): ?>
<div class="mobile-scroll-hint no-print">
    <i class="fa-solid fa-arrows-left-right"></i> Scroll / swipe horizontally to inspect full document
</div>
<div class="pdf-page-container">
<div class="marksheet-document" id="printableMarksheet">
<!-- Institutional Header with Dynamic QR Verification -->
<div class="doc-header">
<div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px;">
<div style="width: 70px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
<div class="doc-crest-icon" style="font-size: 2.2rem; color: #0f2744;">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<span style="font-size: 5.5pt; font-weight: 800; color: #0f2744; letter-spacing: 0.5px; text-transform: uppercase;">Est. 2006</span>
</div>
<div style="flex: 1; text-align: center;">
<h1 class="doc-inst-title">SSR COLLEGE OF ARTS, COMMERCE & SCIENCE</h1>
<p class="doc-inst-sub">Affiliated to Savitribai Phule Pune University (SPPU) * NAAC Accredited 'A' Grade</p>
<p class="doc-inst-sub" style="font-size: 0.68rem; color: #475569;">Sayli Road, Silvassa, Dadra & Nagar Haveli (UT) - 396230 | College Code: 0832</p>
</div>
<div style="width: 70px; text-align: center;">
<div id="marksheetQRCode" style="display: inline-flex; align-items: center; justify-content: center; padding: 2px; background: #ffffff; border: 1px solid #0f2744; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);"></div>
<div style="font-size: 5pt; font-weight: 800; color: #0f2744; text-transform: uppercase; margin-top: 2px; font-family: Arial, sans-serif; letter-spacing: 0.3px;">Scan to Verify</div>
</div>
</div>
<div class="doc-title-badge-row">
<div class="doc-title-badge">
STATEMENT OF MARKS & GRADES
</div>
<div class="doc-session-tag">
SEMESTER <?= htmlspecialchars($sem_id) ?> EXAMINATION * ACADEMIC SESSION 2025-2026
</div>
</div>
</div>

<!-- Student Demographic Table -->
<table class="doc-meta-table">
<tr>
<td class="meta-label">Candidate Name:</td>
<td class="meta-val"><?= htmlspecialchars($student['name'] ?? 'N/A') ?></td>
<td class="meta-label">Seat / Roll No:</td>
<td class="meta-val"><?= htmlspecialchars($roll_no) ?></td>
</tr>
<tr>
<td class="meta-label">Mother's Name:</td>
<td class="meta-val"><?= htmlspecialchars($student['mother_name'] ?? 'N/A') ?></td>
<td class="meta-label">Program / Branch:</td>
<td class="meta-val"><?= htmlspecialchars($student['branch_name'] ?? 'N/A') ?></td>
</tr>
<tr>
<td class="meta-label">Semester:</td>
<td class="meta-val">Semester <?= htmlspecialchars($sem_id) ?></td>
<td class="meta-label">Date of Issue:</td>
<td class="meta-val"><?= date("d-M-Y") ?></td>
</tr>
</table>

<!-- Subject Ledger Table -->
<table class="doc-ledger-table">
<thead>
<tr>
<th style="width: 12%;">Sub Code</th>
<th style="width: 46%; text-align: left; padding-left: 6px;">Subject Title & Paper Description</th>
<th style="width: 9%;">Credits</th>
<th style="width: 9%;">Max</th>
<th style="width: 12%;">Obtained</th>
<th style="width: 12%;">Grade</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): 
$m = (float)$r['marks'];
$grade = ($m >= 90) ? "O" : (($m >= 75) ? "A+" : (($m >= 60) ? "A" : (($m >= 55) ? "B+" : (($m >= 50) ? "B" : (($m >= 40) ? "C" : "F")))));
$gradeClass = ($m >= 40) ? "g-pass" : "g-fail";
?>
<tr>
<td><strong><?= htmlspecialchars($r['subj_code']) ?></strong></td>
<td class="subj-desc"><?= htmlspecialchars($r['subj_name']) ?></td>
<td>2</td>
<td>100</td>
<td><strong><?= htmlspecialchars($r['marks']) ?></strong></td>
<td><span class="grade-badge <?= $gradeClass ?>"><?= $grade ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Cumulative Summary & Classification -->
<div class="doc-summary-box">
<div class="sum-item">
<span class="sum-title">Total Marks</span>
<span class="sum-val"><?= $totalMarks ?> / <?= $maxMarks ?></span>
</div>
<div class="sum-item">
<span class="sum-title">Percentage</span>
<span class="sum-val" style="color: #0f2744;"><?= $percentage ?>%</span>
</div>
<div class="sum-item">
<span class="sum-title">Total Credits</span>
<span class="sum-val"><?= $totalCredits ?></span>
</div>
<div class="sum-item">
<span class="sum-title">SGPA</span>
<span class="sum-val" style="color: #059669;"><?= $sgpa ?></span>
</div>
<div class="sum-item">
<span class="sum-title">Final Result</span>
<?php if ($overallStatus === 'PASS'): ?>
<span class="doc-status-pass"><?= ($percentage >= 70) ? "FIRST CLASS DISTINCTION" : (($percentage >= 60) ? "FIRST CLASS" : "PASS") ?></span>
<?php else: ?>
<span class="doc-status-fail">FAIL / ATKT</span>
<?php endif; ?>
</div>
</div>

<!-- Grading Scale Legend & Barcode Verification -->
<div class="doc-security-row">
<div class="grading-scale-text">
<strong>Scale:</strong> O: 90-100 (10) | A+: 75-89 (9) | A: 60-74 (8) | B+: 55-59 (7) | B: 50-54 (6) | C: 40-49 (5) | F: &lt;40 (0)
</div>
<div class="security-barcode">
<i class="fa-solid fa-barcode"></i> DOC-VERIFY: SSR-<?= htmlspecialchars($roll_no) ?>-S<?= htmlspecialchars($sem_id) ?>-<?= date('Y') ?>
</div>
</div>

<!-- Authentic Dual Signatures & College Seal -->
<div class="doc-sign-grid">
<div class="sign-block">
<div class="sign-line"></div>
<strong>Prepared & Verified By</strong><br>
<span style="color: #64748b;">Examination Section</span>
</div>

<div class="official-seal-box">
<span>SSR<br>SEAL<br>SPPU</span>
</div>

<div class="sign-block">
<div class="sign-line"></div>
<strong>Controller of Examinations</strong><br>
<span style="color: #64748b;">SSR Examination Cell</span>
</div>

<div class="sign-block">
<div class="sign-line"></div>
<strong>Principal / Director</strong><br>
<span style="color: #64748b;">Institutional Authority</span>
</div>
</div>
</div>
</div>

<!-- Student Document Services Shortcuts (Screen Only) -->
<div class="doc-services-box no-print">
<h4>Need Official Academic Certificates or Services?</h4>
<div class="doc-services-grid">
<a href="../student/request-revalution.php" class="btn btn-secondary btn-sm" onclick="return handleServiceClick(event, '../student/request-revalution.php')">
<i class="fa-solid fa-rotate-right"></i> Apply Revaluation
</a>
<a href="../student/request-photocopy.php" class="btn btn-secondary btn-sm" onclick="return handleServiceClick(event, '../student/request-photocopy.php')">
<i class="fa-solid fa-copy"></i> Request Photocopy
</a>
<a href="../student/degree_print.php" class="btn btn-secondary btn-sm" onclick="return handleServiceClick(event, '../student/degree_print.php')">
<i class="fa-solid fa-graduation-cap"></i> Degree Slip
</a>
<a href="../student/generate_certificate.php" class="btn btn-secondary btn-sm" onclick="return handleServiceClick(event, '../student/generate_certificate.php')">
<i class="fa-solid fa-file-certificate"></i> Migration Cert
</a>
</div>
</div>
<?php endif; ?>

<!-- Login Required Prompt Modal -->
<div id="loginPromptModal" style="display: none; position: fixed; inset: 0; background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
<div style="background: #FFFFFF; border-radius: 16px; max-width: 440px; width: 100%; padding: 32px 28px; text-align: center; box-shadow: 0 20px 45px -10px rgba(31,41,55,0.25); border: 1px solid #E5E7EB; animation: fadeIn 0.25s ease-out;">
<div style="width: 58px; height: 58px; background: #DBEAFE; color: #1E3A5F; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 16px;">
<i class="fa-solid fa-user-lock"></i>
</div>
<h3 style="font-size: 1.3rem; color: #1E3A5F; margin: 0 0 8px; font-weight: 700;">Student Login Required</h3>
<p style="color: #4B5563; font-size: 0.92rem; line-height: 1.5; margin-bottom: 24px;">
To apply for revaluation, request answer book photocopies, or download official academic certificates, please sign in with your student credentials first.
</p>
<div style="display: flex; flex-direction: column; gap: 10px;">
<a href="index.php" class="btn btn-primary" style="padding: 12px; font-size: 0.95rem; justify-content: center; border-radius: 10px;">
<i class="fa-solid fa-right-to-bracket"></i> Sign In to Student Portal
</a>
<button type="button" onclick="closeLoginModal()" class="btn btn-secondary" style="padding: 10px; font-size: 0.9rem; justify-content: center; border-radius: 10px;">
Cancel
</button>
</div>
</div>
</div>

<script>
const isStudentLoggedIn = <?= (!empty($_SESSION['loggedin']) && ($_SESSION['role'] ?? '') === 'student') ? 'true' : 'false'; ?>;

function handleServiceClick(event, targetUrl) {
if (!isStudentLoggedIn) {
event.preventDefault();
document.getElementById('loginPromptModal').style.display = 'flex';
return false;
}
return true;
}

function closeLoginModal() {
document.getElementById('loginPromptModal').style.display = 'none';
}

// Generate Dynamic Official Verification QR Code
document.addEventListener('DOMContentLoaded', function() {
const qrContainer = document.getElementById('marksheetQRCode');
if (qrContainer && typeof QRCode !== 'undefined') {
const verifyData = <?= json_encode($verifyUrl) ?>;
new QRCode(qrContainer, {
text: verifyData,
width: 52,
height: 52,
colorDark: "#0f2744",
colorLight: "#ffffff",
correctLevel: QRCode.CorrectLevel.M
});
}
});

// Direct 1-Click Vector PDF Generation
function downloadAsPDF() {
const element = document.getElementById('printableMarksheet');
if (!element) return;

const opt = {
margin: [5, 6, 5, 6], // top, left, bottom, right (mm)
filename: 'Official_Marksheet_<?= htmlspecialchars($roll_no) ?>_Sem<?= htmlspecialchars($sem_id) ?>.pdf',
image:{ type: 'jpeg', quality: 0.98 },
html2canvas:{ scale: 2, useCORS: true, logging: false },
jsPDF:{ unit: 'mm', format: 'a4', orientation: 'portrait' }
};

// Run html2pdf download
html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
