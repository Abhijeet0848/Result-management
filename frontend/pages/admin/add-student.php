<?php
session_start();
$showAlert = false;
$showError = false;
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn) {
$fullname = trim($_POST['fullname'] ?? '');
$rollno = trim($_POST['rollno'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? 'student123';
$gender = $_POST['gender'] ?? 'Male';
$dob = $_POST['birthDate'] ?? '';
$branch = (int)($_POST['branch'] ?? 0);
$sem = (int)($_POST['semester'] ?? 0);
$status = 1;

if (!empty($fullname) && !empty($rollno) && !empty($email)) {
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$addsql = "INSERT INTO student (name, roll_no, email, gender, dob, branch_id, sem_id, status, password) 
 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
$result = pg_query_params($conn, $addsql, array($fullname, $rollno, $email, $gender, $dob, $branch, $sem, $status, $hashed_password));

if ($result) {
$showAlert = "Student " . htmlspecialchars($fullname) . " (Roll No: " . htmlspecialchars($rollno) . ") enrolled successfully!";
} else {
$showError = "Failed to add student: " . pg_last_error($conn);
}
} else {
$showError = "Please fill in all required fields.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 800px;">
<div class="page-header">
<div>
<h1 class="page-title">Enroll New Student</h1>
<p>Register student particulars, academic branch, and semester assignment.</p>
</div>
<a href="manage-students.php" class="btn btn-secondary btn-sm">
<i class="fa-solid fa-users"></i> View All Students
</a>
</div>

<?php if ($showAlert): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($showAlert) ?></div>
</div>
<?php endif; ?>

<?php if ($showError): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($showError) ?></div>
</div>
<?php endif; ?>

<form action="add-student.php" method="POST">
    <div class="form-grid-2">
        <div class="form-group">
            <label for="fullname">Full Name *</label>
            <input type="text" id="fullname" name="fullname" required placeholder="Enter Full Name">
        </div>

        <div class="form-group">
            <label for="rollno">Roll Number *</label>
            <input type="text" id="rollno" name="rollno" required placeholder="Enter Roll Number">
        </div>
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required placeholder="Enter Email Address">
        </div>

        <div class="form-group">
            <label for="password">Portal Password *</label>
            <div class="password-input-wrapper">
                <input type="password" id="password" name="password" required placeholder="Enter Initial Password">
                <button type="button" class="password-toggle-btn" onclick="togglePassword('password', 'passIcon')" title="Show/Hide Password" aria-label="Toggle Password Visibility">
                    <i class="fa-solid fa-eye" id="passIcon"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="birthDate">Date of Birth</label>
            <input type="date" id="birthDate" name="birthDate" required>
        </div>
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label for="branch">Academic Branch *</label>
            <select id="branch" name="branch" required>
                <option value="">-- Select Branch --</option>
                <?php
                if ($conn) {
                    $res = pg_query($conn, "SELECT branch_id, branch_name FROM branch ORDER BY branch_name");
                    while ($b = pg_fetch_assoc($res)) {
                        echo '<option value="' . htmlspecialchars($b['branch_id']) . '">' . htmlspecialchars($b['branch_name']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="semester">Current Semester *</label>
            <select id="semester" name="semester" required>
                <option value="">-- Select Semester --</option>
                <?php
                if ($conn) {
                    $res = pg_query($conn, "SELECT sem_id, semester FROM semester ORDER BY sem_id");
                    while ($s = pg_fetch_assoc($res)) {
                        echo '<option value="' . htmlspecialchars($s['sem_id']) . '">' . htmlspecialchars($s['semester']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <a href="manage-students.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-user-plus"></i> Save &amp; Enroll Student
        </button>
    </div>
</form>
</div>

<script>
function togglePassword(inputId, iconId) {
const input = document.getElementById(inputId);
const icon = document.getElementById(iconId);
if (input.type === 'password') {
input.type = 'text';
icon.classList.remove('fa-eye');
icon.classList.add('fa-eye-slash');
} else {
input.type = 'password';
icon.classList.remove('fa-eye-slash');
icon.classList.add('fa-eye');
}
}
</script>
</body>
</html>
