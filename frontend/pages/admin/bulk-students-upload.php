<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

$successMsg = '';
$errorMsg = '';

// Handle Template Download
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Student_Batch_Import_Template.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Roll_No', 'Email', 'Password', 'Gender', 'DOB_YYYY_MM_DD', 'Branch_ID', 'Semester_ID', 'Mother_Name']);
fclose($output);
exit;
}

// Handle Student CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_students'])) {
if (!isset($_FILES['student_csv']) || $_FILES['student_csv']['error'] !== UPLOAD_ERR_OK) {
$errorMsg = "Please upload a valid CSV file.";
} else {
$file = $_FILES['student_csv']['tmp_name'];
$handle = fopen($file, "r");

if ($handle !== FALSE) {
$header = fgetcsv($handle, 1000, ",");
$imported = 0;
$skipped = 0;

while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
$name = trim($data[0] ?? '');
$roll_no = trim($data[1] ?? '');
$email = trim($data[2] ?? '');
$plain_pass = trim($data[3] ?? 'Password@123');
$gender = trim($data[4] ?? 'Male');
$dob = trim($data[5] ?? '2004-01-01');
$branch_id = intval($data[6] ?? 1);
$sem_id = intval($data[7] ?? 1);
$mother_name = trim($data[8] ?? '');

if (empty($name) || empty($roll_no) || empty($email)) {
$skipped++;
continue;
}

// Check existing roll_no or email
$chk = pg_query_params($conn, "SELECT reg_id FROM student WHERE roll_no = $1 OR email = $2", array($roll_no, $email));
if ($chk && pg_num_rows($chk) > 0) {
$skipped++;
continue;
}

$hashed_pass = password_hash($plain_pass, PASSWORD_DEFAULT);
$ins = pg_query_params(
$conn, 
"INSERT INTO student (name, roll_no, email, password, gender, dob, branch_id, sem_id, status) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, 1)",
array($name, $roll_no, $email, $hashed_pass, $gender, $dob, $branch_id, $sem_id)
);

if ($ins) {
$imported++;
if (!empty($mother_name)) {
pg_query_params($conn, "INSERT INTO mother (student_roll_no, mother_name) VALUES ($1, $2)", array($roll_no, $mother_name));
}
} else {
$skipped++;
}
}
fclose($handle);

logAudit($conn, "BULK_STUDENT_IMPORT", "Imported $imported student accounts ($skipped skipped/duplicates).");
$successMsg = "Batch import finished! Successfully created $imported student accounts ($skipped skipped/duplicates).";
} else {
$errorMsg = "Unable to process uploaded file.";
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Student Batch Import - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.workflow-step {
background: #FFFFFF;
border: 1.5px solid #E5E7EB;
border-radius: 12px;
padding: 24px;
margin-bottom: 24px;
}
.step-badge {
display: inline-flex;
align-items: center;
justify-content: center;
width: 28px;
height: 28px;
background: #1E3A5F;
color: #FFFFFF;
border-radius: 50%;
font-size: 0.85rem;
font-weight: 700;
margin-right: 10px;
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 800px;">
<div class="page-header">
<div>
<h1 class="page-title">Bulk Student Account Import</h1>
<p>Register dozens or hundreds of students in a single step using our standard CSV batch template.</p>
</div>
<a href="manage-students.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-arrow-left"></i> Student Directory
</a>
</div>

<?php if (!empty($successMsg)): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($successMsg) ?></div>
</div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($errorMsg) ?></div>
</div>
<?php endif; ?>

<!-- STEP 1 -->
<div class="workflow-step">
<h3 style="margin-top: 0; color: #1E3A5F; display: flex; align-items: center; font-size: 1.15rem;">
<span class="step-badge">1</span> Step 1: Download Batch Import Template
</h3>
<p style="color: #4B5563; font-size: 0.92rem; margin-bottom: 16px;">
Download our formatted CSV template with required column headers (Name, Roll_No, Email, Password, Gender, DOB, Branch_ID, Semester_ID, Mother_Name).
</p>
<a href="bulk-students-upload.php?action=download_template" class="btn btn-secondary">
<i class="fa-solid fa-download"></i> Download CSV Template
</a>
</div>

<!-- STEP 2 -->
<div class="workflow-step">
<h3 style="margin-top: 0; color: #1E3A5F; display: flex; align-items: center; font-size: 1.15rem;">
<span class="step-badge">2</span> Step 2: Upload Student Batch CSV
</h3>
<p style="color: #4B5563; font-size: 0.92rem; margin-bottom: 16px;">
Upload your completed student CSV file. All imported students are automatically approved and ready for portal sign-in.
</p>

<form action="" method="POST" enctype="multipart/form-data" style="display: grid; gap: 16px;">
<div class="form-group" style="margin: 0;">
<label style="font-weight: 600; font-size: 0.88rem;">Select Student CSV File</label>
<input type="file" name="student_csv" accept=".csv" class="form-control" required style="padding: 9px;">
</div>

<div style="display: flex; justify-content: flex-end; margin-top: 10px;">
<button type="submit" name="import_students" class="btn btn-primary btn-lg">
<i class="fa-solid fa-user-plus"></i> Import Student Accounts
</button>
</div>
</form>
</div>
</div>
</body>
</html>
