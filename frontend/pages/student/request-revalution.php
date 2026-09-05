<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/index.php");
    exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';
$email = $_SESSION['student_username'] ?? '';

// 1. Fetch all subjects student has applied for a photocopy
$photoSql = "SELECT subjects FROM photocopy_requests WHERE email = $1";
$photoRes = pg_query_params($conn, $photoSql, array($email));
$appliedPhotoSubjects = [];

if ($photoRes) {
    while ($pRow = pg_fetch_assoc($photoRes)) {
        if (!empty($pRow['subjects'])) {
            $splitSubjs = explode(',', $pRow['subjects']);
            foreach ($splitSubjs as $s) {
                $trimmed = trim($s);
                if (!empty($trimmed)) {
                    $appliedPhotoSubjects[strtolower($trimmed)] = $trimmed;
                }
            }
        }
    }
}

// 2. Fetch student's course marks
$sql = "SELECT subjects.subj_name, results.marks 
        FROM results 
        JOIN subjects ON results.subj_id = subjects.subj_id 
        JOIN student ON results.roll_no = student.roll_no 
        WHERE student.email = $1";
$result = pg_query_params($conn, $sql, array($email));
$allMarks = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $allMarks[] = $row;
    }
}

// 3. Filter marks to ONLY include subjects where photocopy was applied
$eligibleMarks = [];
foreach ($allMarks as $m) {
    $subjName = trim($m['subj_name']);
    $matched = false;
    foreach ($appliedPhotoSubjects as $lowerKey => $originalName) {
        if (strcasecmp($subjName, $originalName) === 0 || stripos($subjName, $originalName) !== false || stripos($originalName, $subjName) !== false) {
            $matched = true;
            break;
        }
    }
    if ($matched) {
        $eligibleMarks[] = $m;
    }
}

// Handle form submission for revaluation request
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_subjects = $_POST['subjects'] ?? [];
    if (!empty($_POST['subject'])) {
        $selected_subjects = [$_POST['subject']];
    }
    
    if (!empty($selected_subjects) && is_array($selected_subjects)) {
        // Sanitize
        $clean_subjects = array_filter(array_map('trim', $selected_subjects));
        if (!empty($clean_subjects)) {
            $subject_string = implode(', ', $clean_subjects);
            $_SESSION['subject'] = $subject_string;
            $_SESSION['subjects_list'] = $clean_subjects;
            $_SESSION['request_type'] = 'revaluation';
            $_SESSION['revaluation_amount'] = count($clean_subjects) * 250;

            header("location: payment.php?type=revaluation");
            exit;
        }
    }
    $error_message = "Please select at least one photocopy-verified subject for revaluation.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Subject Revaluation | Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            min-height: 100vh;
        }
        .page-container {
            max-width: 880px;
            margin: 36px auto;
            padding: 0 16px;
        }
        .student-nav {
            background: #FFFFFF;
            color: #0F172A;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .rule-banner {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-left: 4px solid #2563EB;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 22px;
            font-size: 0.88rem;
            color: #1E40AF;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .subject-row {
            transition: background 0.15s ease;
        }
        .subject-row:hover {
            background: #F8FAFC;
        }
        .fee-calc-bar {
            background: #F1F5F9;
            border-radius: 8px;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            border: 1px solid #E2E8F0;
        }
        @media (max-width: 640px) {
            .student-nav {
                padding: 10px 14px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .student-nav > div:last-child {
                justify-content: space-between;
                width: 100%;
            }
            .fee-calc-bar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 8px;
            }
            .btn-block-sm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Student Topbar -->
    <header class="student-nav">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-family: 'Outfit', sans-serif; color: #0F172A;">
            <div style="width: 32px; height: 32px; background: #1E3A5F; color: #FFFFFF; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span>Student Services</span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 0.88rem; color: #64748B;"><?= htmlspecialchars($email) ?></span>
            <a href="s_login.php" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.82rem; background: #F8FAFC; color: #0F172A; border-color: #CBD5E1;">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="/backend/auth/logout.php" class="btn btn-danger" style="padding: 6px 14px; font-size: 0.82rem;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <div class="page-container">
        <div style="margin-bottom: 20px;">
            <a href="s_login.php" style="color: #2563EB; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Student Dashboard
            </a>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0; font-family: 'Outfit', sans-serif;">Apply for Subject Revaluation</h1>
            <p style="color: #64748B; font-size: 0.9rem; margin-top: 4px;">Select photocopy-verified subjects for evaluation review and marks re-verification</p>
        </div>

        <!-- Academic Policy Notice -->
        <div class="rule-banner">
            <i class="fa-solid fa-circle-info" style="font-size: 1.2rem; margin-top: 2px; color: #2563EB;"></i>
            <div>
                <strong>University Evaluation Regulation:</strong>
                <div style="margin-top: 2px;">
                    As per Savitribai Phule Pune University rules, only subjects for which you have <strong>previously applied for an Answer Book Photocopy</strong> are eligible for Revaluation.
                </div>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>

        <div class="card" style="box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #E2E8F0; border-radius: 12px;">
            <?php if (empty($appliedPhotoSubjects)): ?>
                <!-- No Photocopy Requests on File -->
                <div style="text-align: center; padding: 48px 20px;">
                    <div style="width: 56px; height: 56px; background: #FEF3C7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #D97706; font-size: 1.6rem; margin-bottom: 14px;">
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                    </div>
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: #0F172A; margin: 0 0 6px 0;">No Eligible Subjects for Revaluation</h3>
                    <p style="color: #64748B; font-size: 0.92rem; max-width: 500px; margin: 0 auto 20px auto; line-height: 1.5;">
                        You have not submitted a Photocopy Request for any evaluated answer booklets yet. Please request an answer book photocopy first to become eligible for revaluation.
                    </p>
                    <a href="request-photocopy.php" class="btn btn-primary" style="padding: 10px 22px; font-weight: 700;">
                        <i class="fa-solid fa-book-open"></i> Request Answer Book Photocopy First
                    </a>
                </div>
            <?php elseif (empty($eligibleMarks)): ?>
                <!-- Photocopies exist but subjects don't match results -->
                <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.2rem; color: #F59E0B; margin-bottom: 12px; display: block;"></i>
                    <h3 style="font-size: 1.15rem; color: #0F172A; font-weight: 700; margin-bottom: 6px;">No Matching Declared Marks</h3>
                    <p>You have submitted photocopy requests, but matching examination course records were not found.</p>
                    <a href="request-photocopy.php" class="btn btn-secondary" style="margin-top: 10px;">Check Photocopy Requests</a>
                </div>
            <?php else: ?>
                <!-- Form with Photocopy-verified subjects -->
                <form action="" method="post" id="revalForm">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: #1E293B; margin: 0;">
                            <i class="fa-solid fa-list-check" style="color: #2563EB; margin-right: 6px;"></i> 
                            Eligible Photocopy-Verified Subjects (<?= count($eligibleMarks) ?>)
                        </h3>
                        <?php if (count($eligibleMarks) > 1): ?>
                            <label style="font-size: 0.85rem; font-weight: 600; color: #2563EB; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                <input type="checkbox" id="selectAllCheckbox" style="accent-color: #2563EB; width: 16px; height: 16px; cursor: pointer;">
                                Select All Subjects
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table" style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #F8FAFC;">
                                    <th style="width: 70px; text-align: center;">Apply</th>
                                    <th>Subject Name</th>
                                    <th style="width: 130px; text-align: center;">Current Marks</th>
                                    <th style="width: 140px; text-align: center;">Revaluation Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eligibleMarks as $mark): ?>
                                    <tr class="subject-row">
                                        <td style="text-align: center;">
                                            <input type="checkbox" name="subjects[]" value="<?= htmlspecialchars($mark['subj_name']) ?>" class="subject-checkbox" style="width: 18px; height: 18px; accent-color: #2563EB; cursor: pointer;">
                                        </td>
                                        <td style="font-weight: 600; color: #0F172A;">
                                            <?= htmlspecialchars($mark['subj_name']) ?>
                                            <div style="font-size: 0.76rem; color: #059669; font-weight: 600; margin-top: 2px;">
                                                <i class="fa-solid fa-check"></i> Photocopy Application Verified
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-primary" style="font-size: 0.85rem; padding: 4px 10px;">
                                                <?= htmlspecialchars($mark['marks']) ?> / 100
                                            </span>
                                        </td>
                                        <td style="text-align: center; font-weight: 700; color: #059669;">
                                            Rs. 250.00
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="fee-calc-bar">
                        <div style="font-size: 0.9rem; color: #475569;">
                            Selected: <strong id="selectedCountText" style="color: #0F172A;">0 Subject(s)</strong>
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #059669;">
                            Total Fee: Rs. <span id="totalFeeText">0.00</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 22px; padding-top: 16px; border-top: 1px solid #E2E8F0;">
                        <span style="font-size: 0.85rem; color: #64748B;">
                            <i class="fa-solid fa-shield-halved" style="color: #2563EB;"></i> Secure Razorpay & QR Gateway
                        </span>
                        <button type="submit" id="submitBtn" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;" disabled>
                            Proceed to Payment <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.subject-checkbox');
        const selectAll = document.getElementById('selectAllCheckbox');
        const countText = document.getElementById('selectedCountText');
        const feeText = document.getElementById('totalFeeText');
        const submitBtn = document.getElementById('submitBtn');

        function updateTotals() {
            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });

            if (countText) countText.textContent = count + ' Subject(s)';
            if (feeText) feeText.textContent = (count * 250).toFixed(2);
            if (submitBtn) submitBtn.disabled = (count === 0);

            if (selectAll) {
                selectAll.checked = (count === checkboxes.length && checkboxes.length > 0);
            }
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotals);
        });

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateTotals();
            });
        }
    });
    </script>
</body>
</html>
