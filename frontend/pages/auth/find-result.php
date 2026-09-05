<?php
session_start();
include_once __DIR__ . '/../../../backend/config/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Examination Result | ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
body {
background-color: #F8FAFC;
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
padding: 20px;
font-family: 'Plus Jakarta Sans', sans-serif;
color: #1F2937;
}
.search-card {
background: #FFFFFF;
border-radius: 16px;
box-shadow: 0 4px 20px rgba(31, 41, 55, 0.08);
border: 1px solid #E5E7EB;
width: 100%;
max-width: 480px;
padding: 36px;
}
.brand-header {
text-align: center;
margin-bottom: 28px;
}
.brand-logo {
width: 56px;
height: 56px;
background: #1E3A5F;
border-radius: 12px;
display: inline-flex;
align-items: center;
justify-content: center;
font-size: 1.5rem;
color: #FFFFFF;
box-shadow: 0 4px 12px rgba(30, 58, 95, 0.25);
margin-bottom: 12px;
}
</style>
</head>
<body>

<div class="search-card">
<div class="brand-header">
<div class="brand-logo">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<h2 style="font-size: 1.55rem; font-weight: 800; color: #1E3A5F; margin: 0;">Online Result Verification</h2>
<p style="color: #4B5563; font-size: 0.95rem; font-weight: 500; margin-top: 6px;">Enter your roll number and academic info to view mark sheet</p>
</div>

<form action="result.php" method="post" style="display: flex; flex-direction: column; gap: 16px;">
<div class="form-group">
<label class="form-label" for="branch_id"><i class="fa-solid fa-code-branch"></i> Academic Branch</label>
<select name="branch_id" id="branch_id" class="form-control" required>
<option value="">-- Choose Branch --</option>
<?php 
$sql = "SELECT branch_id, branch_name FROM branch ORDER BY branch_name ASC";
$result = pg_query($conn, $sql);
if ($result) {
while ($row = pg_fetch_assoc($result)) {
echo '<option value="' . htmlspecialchars($row['branch_id']) . '">' . htmlspecialchars($row['branch_name']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label class="form-label" for="sem_id"><i class="fa-solid fa-calendar-days"></i> Semester</label>
<select name="sem_id" id="sem_id" class="form-control" required>
<option value="">-- Choose Semester --</option>
<?php 
$sql = "SELECT sem_id, semester FROM semester ORDER BY semester ASC";
$result = pg_query($conn, $sql);
if ($result) {
while ($row = pg_fetch_assoc($result)) {
echo '<option value="' . htmlspecialchars($row['sem_id']) . '">Semester ' . htmlspecialchars($row['semester']) . '</option>';
}
}
?>
</select>
</div>

<div class="form-group">
<label class="form-label" for="stid"><i class="fa-solid fa-id-card"></i> Student Roll Number</label>
<input type="text" name="stid" id="stid" class="form-control" placeholder="Enter Roll Number" required autocomplete="off">
</div>

<button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 10px; justify-content: center;">
<i class="fa-solid fa-magnifying-glass"></i> Search & View Result
</button>
</form>

<div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #E5E7EB;">
<a href="index.php" style="color: #2563EB; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
<i class="fa-solid fa-arrow-left"></i> Back to Main Portal Login
</a>
</div>
</div>

</body>
</html>
