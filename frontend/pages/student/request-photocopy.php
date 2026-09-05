<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/index.php");
    exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';
$email = $_SESSION['student_username'] ?? '';

// Fetch student's marks
$sql = "SELECT subjects.subj_name, subjects.subj_code, results.marks 
        FROM results 
        JOIN subjects ON results.subj_id = subjects.subj_id 
        JOIN student ON results.roll_no = student.roll_no 
        WHERE student.email = $1
        ORDER BY subjects.subj_name ASC";
$result = pg_query_params($conn, $sql, array($email));
$marks = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $marks[] = $row;
    }
}

// Handle form submission for photocopy request
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_subjects = $_POST['subjects'] ?? [];
    if (!empty($selected_subjects) && is_array($selected_subjects)) {
        // Sanitize and format
        $clean_subjects = array_map('trim', $selected_subjects);
        $_SESSION['subjects_list'] = $clean_subjects;
        $_SESSION['subject'] = implode(", ", $clean_subjects);
        $_SESSION['photocopy_count'] = count($clean_subjects);
        $_SESSION['photocopy_amount'] = count($clean_subjects) * 100;
        header("location: payment.php");
        exit;
    } else {
        $error_message = "Please select at least one subject to request photocopy.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Answer Book Photocopy | Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <style>
        .page-container {
            max-width: 880px;
            margin: 36px auto;
            padding: 0 16px;
        }
        .student-nav {
            background: #ffffff;
            color: #0f172a;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .subject-row {
            transition: background 0.15s ease;
            cursor: pointer;
        }
        .subject-row:hover {
            background: #f8fafc;
        }
        .subject-row.selected {
            background: #eff6ff;
        }
        .fee-summary-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 24px;
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
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
            .fee-summary-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                padding: 16px;
            }
            .fee-summary-card .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body style="background: #f8fafc; min-height: 100vh;">

    <!-- Student Topbar -->
    <header class="student-nav">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-family: 'Outfit', sans-serif; color: #0f172a;">
            <div style="width: 32px; height: 32px; background: #2563eb; color: #ffffff; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span>Student Services</span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 0.88rem; color: #64748b;"><?= htmlspecialchars($email) ?></span>
            <a href="s_login.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="/backend/auth/logout.php" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <div class="page-container">
        <div style="margin-bottom: 24px;">
            <a href="s_login.php" style="color: #2563eb; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Student Portal
            </a>
            <h1 style="font-size: 1.6rem; font-weight: 700; color: #0f172a; margin: 0;">Request Answer Sheet Photocopy</h1>
            <p style="color: #64748b; font-size: 0.9rem; margin-top: 4px;">Select one or multiple evaluated course subjects to receive certified scanned digital answer books.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <form action="" method="post" id="photocopyForm" onsubmit="return validateSelection();">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-book" style="color: #2563eb;"></i> Your Registered Examination Subjects
                    </h3>
                    <?php if (!empty($marks)): ?>
                        <div style="font-size: 0.88rem; color: #4b5563; font-weight: 600;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                Select All Subjects
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($marks)): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                        No declared examination marks found for your account. Please check your results ledger or contact administration.
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="margin-bottom: 10px;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Select</th>
                                    <th>Subject Name</th>
                                    <th style="width: 140px; text-align: center;">Marks Scored</th>
                                    <th style="width: 140px; text-align: center;">Fee / Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($marks as $idx => $mark): ?>
                                <tr class="subject-row" id="row-<?= $idx ?>" onclick="toggleRowCheckbox(<?= $idx ?>, event)">
                                    <td style="text-align: center;">
                                        <input type="checkbox" 
                                               name="subjects[]" 
                                               value="<?= htmlspecialchars($mark['subj_name']) ?>" 
                                               id="chk-<?= $idx ?>"
                                               class="subject-checkbox" 
                                               onchange="updateSummary()" 
                                               style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($mark['subj_name']) ?></div>
                                        <?php if (!empty($mark['subj_code'])): ?>
                                            <div style="font-size: 0.78rem; color: #6b7280; font-family: monospace;"><?= htmlspecialchars($mark['subj_code']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-primary" style="font-size: 0.85rem; padding: 4px 10px; font-weight: 700;">
                                            <?= htmlspecialchars($mark['marks']) ?> / 100
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; color: #059669;">
                                        Rs. 100.00
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Live Fee & Selection Summary -->
                    <div class="fee-summary-card">
                        <div>
                            <div style="font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Selection Summary</div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-top: 2px;">
                                <span id="selectedCountText">0</span> subject(s) selected
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Service Fee</div>
                            <div style="font-size: 1.35rem; font-weight: 800; color: #059669; margin-top: 2px;">
                                Rs. <span id="totalAmountText">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 14px;">
                        <span style="font-size: 0.88rem; color: #64748b; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-shield-halved" style="color: #2563eb;"></i> Secure Payment Gateway Integration
                        </span>
                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" style="padding: 12px 28px; opacity: 0.6; cursor: not-allowed;" disabled>
                            Proceed to Payment <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.subject-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
        });
        updateSummary();
    }

    function toggleRowCheckbox(index, event) {
        if (event.target.tagName.toLowerCase() === 'input') {
            return;
        }
        const cb = document.getElementById('chk-' + index);
        if (cb) {
            cb.checked = !cb.checked;
            updateSummary();
        }
    }

    function updateSummary() {
        const checkboxes = document.querySelectorAll('.subject-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const totalFee = checkedCount * 100;

        document.getElementById('selectedCountText').textContent = checkedCount;
        document.getElementById('totalAmountText').textContent = totalFee.toFixed(2);

        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) {
            selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
        }

        checkboxes.forEach((cb, idx) => {
            const row = document.getElementById('row-' + idx);
            if (row) {
                if (cb.checked) row.classList.add('selected');
                else row.classList.remove('selected');
            }
        });

        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            if (checkedCount > 0) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        }
    }

    function validateSelection() {
        const checkedCount = Array.from(document.querySelectorAll('.subject-checkbox')).filter(cb => cb.checked).length;
        if (checkedCount === 0) {
            alert('Please select at least one subject before proceeding.');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>
