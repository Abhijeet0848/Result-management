<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/index.php");
    exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';

$email = $_SESSION['student_username'] ?? '';
$success_message = "";
$error_message = "";

// Ensure photo column exists in student table safely
if ($conn) {
    @pg_query($conn, "ALTER TABLE student ADD COLUMN IF NOT EXISTS photo TEXT;");
}

// Fetch current student details
$student = [];
$current_photo = "";
if ($conn && !empty($email)) {
    $sql = "SELECT s.*, b.branch_name, sm.semester 
            FROM student s 
            LEFT JOIN branch b ON s.branch_id = b.branch_id 
            LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
            WHERE LOWER(s.email) = LOWER($1)";
    $result = pg_query_params($conn, $sql, array($email));
    if ($result && pg_num_rows($result) > 0) {
        $student = pg_fetch_assoc($result);
        $current_photo = $student['photo'] ?? '';
    }
}

// Check if file exists on disk even if DB field was empty
$roll_no_val = $student['roll_no'] ?? ($_SESSION['student_roll'] ?? '');
$upload_dir = __DIR__ . '/../../assets/uploads/students/';
if (empty($current_photo) && !empty($roll_no_val)) {
    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
        if (file_exists($upload_dir . 'student_' . $roll_no_val . '.' . $ext)) {
            $current_photo = '/frontend/assets/uploads/students/student_' . $roll_no_val . '.' . $ext;
            break;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn && !empty($email)) {
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Fetch stored password
    $sql = "SELECT password, roll_no FROM student WHERE LOWER(email) = LOWER($1)";
    $result = pg_query_params($conn, $sql, array($email));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $stored_password = $row['password'] ?? '';
        $student_roll = $row['roll_no'] ?? $roll_no_val;

        // Verify password (supports plain or bcrypt)
        $pass_valid = false;
        if ($current_password === $stored_password || password_verify($current_password, $stored_password)) {
            $pass_valid = true;
        }

        if ($pass_valid) {
            $pass_to_save = !empty($new_password) ? $new_password : $stored_password;

            // Handle Photo Upload
            $new_photo_path = $current_photo;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_name = $_FILES['photo']['name'];
                $file_size = $_FILES['photo']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_message = "Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP, GIF.";
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $error_message = "Image size exceeds 5MB limit.";
                } else {
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $target_file_name = 'student_' . preg_replace('/[^A-Za-z0-9_-]/', '', $student_roll) . '.' . $file_ext;
                    $target_path = $upload_dir . $target_file_name;

                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $new_photo_path = '/frontend/assets/uploads/students/' . $target_file_name . '?v=' . time();
                        $current_photo = $new_photo_path;
                        $_SESSION['student_photo'] = $new_photo_path;
                    } else {
                        $error_message = "Failed to upload photo to server.";
                    }
                }
            }

            if (empty($error_message)) {
                $update_sql = "UPDATE student SET name = $1, gender = $2, dob = $3, password = $4, photo = $5 WHERE LOWER(email) = LOWER($6)";
                $up_res = pg_query_params($conn, $update_sql, array($name, $gender, $dob, $pass_to_save, $new_photo_path, $email));

                if ($up_res) {
                    $_SESSION['student_name'] = $name;
                    $student['name'] = $name;
                    $student['gender'] = $gender;
                    $student['dob'] = $dob;
                    $student['photo'] = $new_photo_path;
                    $success_message = "Your profile and photograph have been updated successfully!";
                } else {
                    $error_message = "Error updating profile in database: " . pg_last_error($conn);
                }
            }
        } else {
            $error_message = "The current password entered is incorrect.";
        }
    } else {
        $error_message = "Student account could not be found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile & Photo - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <style>
        .photo-upload-container {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #f8fafc;
            border: 1.5px dashed #cbd5e1;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 24px;
        }
        .photo-preview-box {
            width: 90px;
            height: 105px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }
        .photo-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-preview-box i {
            font-size: 2.2rem;
            color: #94a3b8;
        }
        @media (max-width: 540px) {
            .photo-upload-container {
                flex-direction: column;
                text-align: center;
                gap: 14px;
            }
        }
    </style>
</head>
<body>

<header style="background: #1E3A5F; padding: 12px clamp(12px, 3vw, 24px); color: #FFFFFF; border-bottom: 1px solid #E5E7EB; box-shadow: 0 2px 10px rgba(30,58,95,0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <a href="dashboard.php" style="color: #FFFFFF; text-decoration: none; font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-graduation-cap" style="color: #2563EB;"></i> Student Portal
    </a>
    <a href="dashboard.php" class="btn btn-secondary btn-sm" style="background: #FFFFFF; color: #1E3A5F; border: 1px solid #E5E7EB;">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</header>

<div class="container" style="max-width: 650px; margin: 24px auto;">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-user-pen" style="color: var(--primary); margin-right: 8px;"></i> Update Profile & Photo</h1>
            <p>Upload your official photograph (used in Hall Tickets & Admission records) and update your profile details.</p>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div><?= htmlspecialchars($success_message) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= htmlspecialchars($error_message) ?></div>
        </div>
    <?php endif; ?>

    <form action="update-profile.php" method="POST" enctype="multipart/form-data">
        
        <!-- Photo Upload Box -->
        <div class="photo-upload-container">
            <div class="photo-preview-box" id="photoPreviewBox">
                <?php if (!empty($current_photo)): ?>
                    <img src="<?= htmlspecialchars($current_photo) ?>" alt="Student Photo" id="photoPreviewImg">
                <?php else: ?>
                    <i class="fa-solid fa-user-graduate" id="photoPlaceholderIcon"></i>
                <?php endif; ?>
            </div>
            <div style="flex: 1; text-align: left;">
                <label for="photoInput" style="font-weight: 700; font-size: 0.95rem; color: #0f172a; display: block; margin-bottom: 4px;">
                    Official Student Photograph
                </label>
                <p style="font-size: 0.82rem; color: #64748b; margin: 0 0 10px 0;">
                    This photograph will be printed on your <strong>Exam Hall Ticket</strong> and <strong>Admission Documents</strong>. (Max 5MB: JPG, PNG, WEBP)
                </p>
                <input type="file" id="photoInput" name="photo" accept="image/*" style="font-size: 0.85rem;" onchange="previewPhoto(event)">
            </div>
        </div>

        <div class="form-group">
            <label for="roll_no">Roll Number (Permanent)</label>
            <input type="text" id="roll_no" value="<?= htmlspecialchars($student['roll_no'] ?? '') ?>" readonly style="background: var(--bg-subtle); cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label for="email">Registered Email Address</label>
            <input type="email" id="email" value="<?= htmlspecialchars($student['email'] ?? $email) ?>" readonly style="background: var(--bg-subtle); cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" required placeholder="Your full name">
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?= (isset($student['gender']) && strtolower($student['gender']) == 'male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= (isset($student['gender']) && strtolower($student['gender']) == 'female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= (isset($student['gender']) && strtolower($student['gender']) == 'other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($student['dob'] ?? '') ?>" required>
            </div>
        </div>

        <div style="background: var(--bg-subtle); border-radius: var(--radius-md); padding: 18px; margin: 20px 0; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 12px; font-size: 1rem;"><i class="fa-solid fa-key" style="color: var(--warning);"></i> Change Password (Optional)</h4>

            <div class="form-group">
                <label for="new_password">New Password (Leave blank to keep current)</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="current_password">Current Password <span style="color: var(--danger);">* (Required to save changes)</span></label>
                <input type="password" id="current_password" name="current_password" required placeholder="Enter current password for verification">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i> Save Profile & Photo
            </button>
        </div>
    </form>
</div>

<script>
function previewPhoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewBox = document.getElementById('photoPreviewBox');
            previewBox.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
        }
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>
