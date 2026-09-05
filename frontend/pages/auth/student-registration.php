<?php
session_start();
$registrationSuccess = false;
$showRegistrationError = false;

include_once __DIR__ . '/../../../backend/config/connection.php';

// Fetch Branches and Semesters for dropdown selection
$branches = [];
$semesters = [];
if ($conn) {
    $bRes = pg_query($conn, "SELECT branch_id, branch_name FROM branch ORDER BY branch_name ASC");
    if ($bRes) {
        while ($b = pg_fetch_assoc($bRes)) {
            $branches[] = $b;
        }
    }
    $sRes = pg_query($conn, "SELECT sem_id, semester FROM semester ORDER BY semester ASC");
    if ($sRes) {
        while ($s = pg_fetch_assoc($sRes)) {
            $semesters[] = $s;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register']) && $conn) {
        $email = filter_var(trim($_POST["register_email"] ?? ''), FILTER_SANITIZE_EMAIL);
        $name = trim($_POST["name"] ?? '');
        $roll_no = trim($_POST["roll_no"] ?? '');
        $gender = trim($_POST["gender"] ?? 'Male');
        $branch_id = !empty($_POST["branch_id"]) ? intval($_POST["branch_id"]) : null;
        $sem_id = !empty($_POST["sem_id"]) ? intval($_POST["sem_id"]) : null;
        $password = $_POST["register_password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $showRegistrationError = "Invalid email format entered.";
        } elseif (empty($name) || empty($roll_no)) {
            $showRegistrationError = "Please fill in your Full Name and Roll Number.";
        } else {
            // Check if email or roll number already exists
            $sql = "SELECT reg_id, email, roll_no FROM student WHERE LOWER(email) = LOWER($1) OR roll_no = $2 LIMIT 1";
            $result = pg_query_params($conn, $sql, array($email, $roll_no));

            if (!$result) {
                $showRegistrationError = "Database error: " . pg_last_error($conn);
            } elseif (pg_num_rows($result) > 0) {
                $existing = pg_fetch_assoc($result);
                if (strtolower($existing['email']) === strtolower($email)) {
                    $showRegistrationError = "An account with email '" . htmlspecialchars($email) . "' is already registered.";
                } else {
                    $showRegistrationError = "A student with Roll Number '" . htmlspecialchars($roll_no) . "' is already registered.";
                }
            } else {
                if ($password === $confirm_password) {
                    if (strlen($password) < 6) {
                        $showRegistrationError = "Password must be at least 6 characters long.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $insert_sql = "INSERT INTO student (name, roll_no, email, gender, branch_id, sem_id, password, status) 
                                       VALUES ($1, $2, $3, $4, $5, $6, $7, 0)";
                        $insert_result = pg_query_params($conn, $insert_sql, array($name, $roll_no, $email, $gender, $branch_id, $sem_id, $hashed_password));

                        if ($insert_result) {
                            $registrationSuccess = true;
                        } else {
                            $showRegistrationError = "Registration failed: " . pg_last_error($conn);
                        }
                    }
                } else {
                    $showRegistrationError = "Passwords do not match.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | ResultPortal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <style>
        body {
            background-color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1F2937;
        }
        .register-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(31, 41, 55, 0.08);
            border: 1px solid #E5E7EB;
            width: 100%;
            max-width: 520px;
            padding: 36px 32px;
        }
        .brand-logo {
            width: 54px;
            height: 54px;
            background: #1E3A5F;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.25);
            margin-bottom: 12px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .register-card {
                padding: 24px 18px;
            }
        }
    </style>
</head>
<body>

<div class="register-card">
    <div style="text-align: center; margin-bottom: 24px;">
        <div class="brand-logo">
            <i class="fa-solid fa-user-plus"></i>
        </div>
        <h2 style="font-size: 1.55rem; font-weight: 800; color: #1E3A5F; margin: 0;">Student Registration</h2>
        <p style="color: #4B5563; font-size: 0.92rem; font-weight: 500; margin-top: 6px;">Submit your credentials for Administrator review and enrollment</p>
    </div>

    <?php if ($registrationSuccess): ?>
        <div class="alert alert-warning" style="margin-bottom: 22px; line-height: 1.55; border-radius: 12px; padding: 18px;">
            <i class="fa-solid fa-clock-rotate-left" style="font-size: 1.5rem; color: #D97706; margin-top: 2px;"></i>
            <div>
                <strong style="color: #92400E; font-size: 1.05rem; display: block; margin-bottom: 4px;">Registration Request Submitted!</strong>
                Your account registration has been received and is currently <strong>Pending Administrator Approval</strong>.<br><br>
                <span style="font-size: 0.88rem; color: #4B5563; display: block;">
                    <i class="fa-solid fa-shield-halved"></i> For security, student accounts require verification by the examination cell before dashboard access is granted. You can check your account status anytime by signing in.
                </span>
            </div>
        </div>
        <a href="index.php" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700; border-radius: 8px;">
            <i class="fa-solid fa-right-to-bracket"></i> Go to Sign In Page
        </a>
    <?php else: ?>
        <?php if ($showRegistrationError): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 10px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($showRegistrationError) ?></div>
            </div>
        <?php endif; ?>

        <form action="student-registration.php" method="post" style="display: flex; flex-direction: column; gap: 14px;">
            <div class="form-group">
                <label class="form-label" for="name"><i class="fa-solid fa-user"></i> Full Name <span style="color:#DC2626;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter Full Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="roll_no"><i class="fa-solid fa-id-card"></i> Roll Number <span style="color:#DC2626;">*</span></label>
                    <input type="text" id="roll_no" name="roll_no" class="form-control" placeholder="e.g. 101, 102" required value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gender"><i class="fa-solid fa-venus-mars"></i> Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="branch_id"><i class="fa-solid fa-code-branch"></i> Branch / Dept</label>
                    <select id="branch_id" name="branch_id" class="form-control">
                        <option value="">-- Select Branch --</option>
                        <?php foreach ($branches as $br): ?>
                            <option value="<?= $br['branch_id'] ?>" <?= (($_POST['branch_id'] ?? '') == $br['branch_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($br['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sem_id"><i class="fa-solid fa-calendar-days"></i> Semester</label>
                    <select id="sem_id" name="sem_id" class="form-control">
                        <option value="">-- Select Semester --</option>
                        <?php foreach ($semesters as $sm): ?>
                            <option value="<?= $sm['sem_id'] ?>" <?= (($_POST['sem_id'] ?? '') == $sm['sem_id']) ? 'selected' : '' ?>>
                                Semester <?= htmlspecialchars($sm['semester']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="register_email"><i class="fa-solid fa-envelope"></i> Email Address <span style="color:#DC2626;">*</span></label>
                <input type="email" id="register_email" name="register_email" class="form-control" placeholder="Enter Email Address" required value="<?= htmlspecialchars($_POST['register_email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="register_password"><i class="fa-solid fa-lock"></i> Password <span style="color:#DC2626;">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" id="register_password" name="register_password" class="form-control" placeholder="Create account password (min 6 chars)" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('register_password', 'toggleIcon1')" aria-label="Toggle password visibility">
                        <i class="fa-solid fa-eye" id="toggleIcon1"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password"><i class="fa-solid fa-check-double"></i> Confirm Password <span style="color:#DC2626;">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password to confirm" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', 'toggleIcon2')" aria-label="Toggle password visibility">
                        <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="register" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem; margin-top: 6px; font-weight: 700; border-radius: 8px;">
                <i class="fa-solid fa-paper-plane"></i> Submit Registration Request
            </button>
        </form>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #E5E7EB;">
        <a href="index.php" style="color: #2563EB; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Already registered? Sign in here
        </a>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;

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

