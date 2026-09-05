<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

$email = $_SESSION['student_username'] ?? '';
$student_name = $_SESSION['student_name'] ?? 'Student';
$roll_no = $_SESSION['student_roll'] ?? '';
$branch_name = "N/A";
$dob = "N/A";
$mother_name = "N/A";

if (!empty($email) && $conn) {
$sql = "SELECT s.name, s.roll_no, s.dob, b.branch_name, m.mother_name 
FROM student s 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN mother m ON s.roll_no = m.student_roll_no 
WHERE s.email = $1";
$res = pg_query_params($conn, $sql, array($email));
if ($res && pg_num_rows($res) > 0) {
$row = pg_fetch_assoc($res);
if (!empty($row['name'])) $student_name = $row['name'];
if (!empty($row['roll_no'])) $roll_no = $row['roll_no'];
if (!empty($row['branch_name'])) $branch_name = $row['branch_name'];
if (!empty($row['dob'])) $dob = $row['dob'];
if (!empty($row['mother_name'])) $mother_name = $row['mother_name'];
}
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/student/degree-print.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Degree Certificate - <?= htmlspecialchars($student_name) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<script src="../../assets/js/qrcode.min.js"></script>
<style>
body {
background-color: #f1f5f9;
padding: 30px 10px;
font-family: 'Cinzel', 'Times New Roman', Georgia, serif;
}
.cert-wrapper {
max-width: 900px;
margin: auto;
}
.certificate {
border: 12px double #1e3a8a;
padding: 40px;
background-color: #ffffff;
border-radius: 12px;
box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
text-align: center;
position: relative;
}
.cert-header {
margin-bottom: 24px;
}
.logo {
height: 90px;
margin-bottom: 12px;
}
.univ-name {
font-size: 1.6rem;
font-weight: 800;
color: #1e3a8a;
letter-spacing: 1px;
margin: 0;
text-transform: uppercase;
}
.cert-title {
font-size: 2rem;
font-weight: 900;
color: #0f172a;
margin: 24px 0 16px;
letter-spacing: 2px;
border-bottom: 2px solid #cbd5e1;
display: inline-block;
padding-bottom: 4px;
}
.recipient-name {
font-size: 2rem;
font-weight: 800;
color: #1e40af;
margin: 20px 0;
font-family: 'Georgia', serif;
}
.cert-body {
font-size: 1.15rem;
line-height: 2;
color: #334155;
max-width: 760px;
margin: 0 auto;
}
.cert-signatures {
margin-top: 60px;
display: flex;
justify-content: space-between;
padding: 0 40px;
}
.sig-block {
text-align: center;
font-size: 0.95rem;
font-weight: 600;
color: #475569;
}
.sig-line {
width: 180px;
border-top: 1.5px solid #0f172a;
margin-bottom: 6px;
}
@media print {
body { background: white !important; padding: 0 !important; }
.no-print { display: none !important; }
.certificate { box-shadow: none !important; border: 10px double #1e3a8a !important; }
}
</style>
</head>
<body>

<div class="cert-wrapper">
<div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
<a href="dashboard.php" class="btn btn-secondary" style="font-family: 'Plus Jakarta Sans', sans-serif;">
<i class="fa-solid fa-arrow-left"></i> Return to Portal
</a>
<button onclick="window.print()" class="btn btn-primary" style="font-family: 'Plus Jakarta Sans', sans-serif;">
<i class="fa-solid fa-print"></i> Print Degree Certificate
</button>
</div>

<div class="certificate">
<div class="cert-header">
<img src="../../assets/images/logo.webp" alt="University Seal" class="logo" onerror="this.style.display='none'">
<h1 class="univ-name">Savitribai Phule Pune University</h1>
<p style="margin: 4px 0; color: #64748b; font-size: 0.9rem; font-style: italic;">(Formerly University of Pune) * Ganeshkhind, Pune 411007</p>
</div>

<div class="cert-title">DEGREE OF BACHELOR OF SCIENCE</div>

<p style="color: #64748b; font-size: 1rem; margin-top: 10px;">This is to certify that</p>
<div class="recipient-name"><?= htmlspecialchars($student_name) ?></div>

<div class="cert-body">
Roll No: <strong><?= htmlspecialchars($roll_no) ?></strong>, having examined and verified by the Board of Examinations, has been admitted to the degree of <strong>Bachelor of Science</strong> in <strong><?= htmlspecialchars($branch_name) ?></strong> with distinction at the examination held in <strong>Academic Session 2023-2026</strong>.
</div>

<div class="cert-signatures" style="align-items: flex-end;">
<div class="sig-block">
<div class="sig-line"></div>
<div>Registrar</div>
</div>

<div style="text-align: center; margin: 0 10px;">
<div id="degreeQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #1e3a8a; border-radius: 4px; box-shadow: 0 2px 8px rgba(30, 58, 138, 0.15);"></div>
<div style="font-size: 6.5pt; font-weight: 800; color: #1e3a8a; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">E-Authenticate Degree</div>
</div>

<div class="sig-block">
<div class="sig-line"></div>
<div>Vice-Chancellor</div>
</div>
</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
const qrContainer = document.getElementById('degreeQRCode');
if (qrContainer && typeof QRCode !== 'undefined') {
const verifyData = <?= json_encode($verifyUrl) ?>;
new QRCode(qrContainer, {
text: verifyData,
width: 60,
height: 60,
colorDark: "#1e3a8a",
colorLight: "#ffffff",
correctLevel: QRCode.CorrectLevel.M
});
}
});
</script>
</body>
</html>
