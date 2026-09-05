<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/index.php");
    exit;
}

// Fetch all documents from database repository
$sql = "SELECT * FROM documents ORDER BY doc_id ASC";
$result = pg_query($conn, $sql);
$documents = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $documents[] = $row;
    }
}

// Fetch logged-in student details
$email = $_SESSION['student_username'] ?? '';
$student = null;
$student_name = $_SESSION['student_name'] ?? 'Student';
$roll_no = $_SESSION['student_roll'] ?? '';
$branch_name = "Computer Science";
$branch_id = 1;
$sem_id = 1;
$sem_name = "Semester 1";
$dob = "N/A";
$mother_name = "N/A";
$gender = "N/A";
$reg_id = "1";

if (!empty($email) && $conn) {
    $stdSql = "SELECT s.*, b.branch_name, sm.semester, m.mother_name 
               FROM student s 
               LEFT JOIN branch b ON s.branch_id = b.branch_id 
               LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
               LEFT JOIN mother m ON s.roll_no = m.student_roll_no 
               WHERE s.email = $1";
    $stdRes = pg_query_params($conn, $stdSql, array($email));
    if ($stdRes && pg_num_rows($stdRes) > 0) {
        $student = pg_fetch_assoc($stdRes);
        $student_name = $student['name'] ?? $student_name;
        $roll_no = $student['roll_no'] ?? $roll_no;
        $branch_name = $student['branch_name'] ?? $branch_name;
        $branch_id = intval($student['branch_id'] ?? 1);
        $sem_id = intval($student['sem_id'] ?? 1);
        $sem_name = isset($student['semester']) ? ('Semester ' . $student['semester']) : 'Semester ' . $sem_id;
        $dob = $student['dob'] ?? $dob;
        $mother_name = $student['mother_name'] ?? $mother_name;
        $gender = $student['gender'] ?? $gender;
        $reg_id = $student['reg_id'] ?? $reg_id;
    }
}

// Fetch subject results or enrolled subjects for Hall Ticket & Result Statement
$student_subjects = [];
$totalMarksScored = 0;
$maxTotalMarks = 0;
$totalCredits = 0;
$totalGradePoints = 0;

if (!empty($roll_no) && $conn) {
    $resQuery = "SELECT r.marks, sub.subj_code, sub.subj_name, COALESCE(sub.credits, 4.0) as credits 
                 FROM results r 
                 JOIN subjects sub ON r.subj_id = sub.subj_id 
                 WHERE r.roll_no = $1 AND r.sem_id = $2 
                 ORDER BY sub.subj_code ASC";
    $resRes = pg_query_params($conn, $resQuery, array($roll_no, $sem_id));
    if ($resRes && pg_num_rows($resRes) > 0) {
        while ($subRow = pg_fetch_assoc($resRes)) {
            $student_subjects[] = $subRow;
            $m = (float)$subRow['marks'];
            $c = (float)$subRow['credits'];
            $totalMarksScored += $m;
            $maxTotalMarks += 100;
            $totalCredits += $c;

            if ($m >= 90) $gp = 10;
            elseif ($m >= 75) $gp = 9;
            elseif ($m >= 60) $gp = 8;
            elseif ($m >= 55) $gp = 7;
            elseif ($m >= 50) $gp = 6;
            elseif ($m >= 40) $gp = 5;
            else $gp = 0;
            $totalGradePoints += ($gp * $c);
        }
    } else {
        // Fallback to active subjects for this branch and sem
        $combSql = "SELECT s.subj_code, s.subj_name, COALESCE(s.credits, 4.0) as credits 
                    FROM subject_comb sc 
                    JOIN subjects s ON sc.subj_id = s.subj_id 
                    WHERE sc.branch_id = $1 AND sc.sem_id = $2 AND sc.status = '1'
                    ORDER BY s.subj_code ASC";
        $combRes = pg_query_params($conn, $combSql, array($branch_id, $sem_id));
        if ($combRes && pg_num_rows($combRes) > 0) {
            while ($combRow = pg_fetch_assoc($combRes)) {
                $student_subjects[] = $combRow;
            }
        }
    }
}

$percentage = ($maxTotalMarks > 0) ? round(($totalMarksScored / $maxTotalMarks) * 100, 2) : 0;
$sgpa = ($totalCredits > 0) ? round($totalGradePoints / $totalCredits, 2) : 0;

// Handle selected document
$selectedDocument = null;
$error = "";
$doc_id = isset($_GET['doc_id']) ? intval($_GET['doc_id']) : 0;

if ($doc_id > 0) {
    $query = "SELECT * FROM documents WHERE doc_id = $1";
    $docResult = pg_query_params($conn, $query, [$doc_id]);
    if ($docResult && pg_num_rows($docResult) > 0) {
        $selectedDocument = pg_fetch_assoc($docResult);
    } else {
        $error = "Requested document could not be located in the university repository.";
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/student/print-documents.php" . ($doc_id > 0 ? "?doc_id=" . $doc_id : "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $selectedDocument ? htmlspecialchars($selectedDocument['doc_name']) . ' - ' : '' ?>Academic Documents Repository</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <script src="../../assets/js/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            margin: 0;
            padding: 24px 12px;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }
        .page-container {
            max-width: 920px;
            margin: 0 auto;
            box-sizing: border-box;
            width: 100%;
        }
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .doc-item-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .doc-item-link:hover {
            border-color: #4f46e5;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.12);
            transform: translateY(-2px);
        }
        .doc-item-link.active {
            border-color: #4f46e5;
            background: #f5f3ff;
        }

        /* Printable Document Styling */
        .printable-sheet {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 28px;
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
        }
        .printable-content {
            padding: 36px 32px;
            box-sizing: border-box;
            position: relative;
        }

        /* Bonafide Certificate Box */
        .bonafide-box {
            border: 8px double #1e3a8a;
            padding: 36px 28px;
            text-align: center;
            border-radius: 8px;
            background: #ffffff;
            font-family: 'Georgia', serif;
        }
        .univ-logo-header img {
            height: 70px;
            object-fit: contain;
            margin-bottom: 8px;
        }
        .univ-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 0;
        }
        .univ-sub {
            color: #64748b;
            font-size: 0.85rem;
            font-style: italic;
            margin: 4px 0 16px;
        }
        .doc-heading-pill {
            display: inline-block;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 1px;
            border-bottom: 2px solid #94a3b8;
            padding-bottom: 4px;
            margin: 12px 0 20px;
            text-transform: uppercase;
        }
        .bonafide-body {
            font-size: 1.05rem;
            line-height: 1.85;
            color: #334155;
            text-align: center;
            max-width: 760px;
            margin: 0 auto;
        }
        .bonafide-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e3a8a;
            margin: 10px 0;
        }

        /* General Document Tables */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 0.92rem;
        }
        .doc-table th, .doc-table td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
        }
        .doc-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
        }
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin: 16px 0;
            text-align: left;
            font-size: 0.92rem;
        }
        .doc-grid-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 6px;
        }
        .doc-grid-label {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .doc-grid-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        /* Signatures block */
        .doc-signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding: 0 10px;
            gap: 12px;
        }
        .doc-sig-block {
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            color: #475569;
            flex: 1;
            max-width: 180px;
        }
        .doc-sig-line {
            width: 100%;
            border-top: 1.5px solid #0f172a;
            margin-bottom: 6px;
        }

        @media (max-width: 640px) {
            body {
                padding: 12px 6px;
            }
            .printable-content {
                padding: 20px 14px;
            }
            .bonafide-box {
                padding: 20px 10px;
                border-width: 5px;
            }
            .univ-title {
                font-size: 1.15rem;
            }
            .doc-heading-pill {
                font-size: 1.15rem;
                margin: 10px 0 14px;
            }
            .bonafide-name {
                font-size: 1.3rem;
            }
            .bonafide-body {
                font-size: 0.92rem;
                line-height: 1.6;
            }
            .doc-table th, .doc-table td {
                padding: 6px 8px;
                font-size: 0.82rem;
            }
            .doc-signatures {
                margin-top: 28px;
                padding: 0;
                gap: 6px;
            }
            .doc-sig-block {
                font-size: 0.72rem;
            }
            .top-nav-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .top-nav-bar .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .printable-sheet {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
            }
            .printable-content {
                padding: 20px !important;
            }
            .bonafide-box {
                border: 8px double #1e3a8a !important;
            }
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Top Action Bar -->
    <div class="no-print top-nav-bar">
        <div>
            <a href="dashboard.php" style="color: #4f46e5; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Student Portal
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
                <?= $selectedDocument ? htmlspecialchars($selectedDocument['doc_name']) : 'Academic Documents Repository' ?>
            </h1>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($selectedDocument): ?>
                <a href="print-documents.php" class="btn btn-secondary">
                    <i class="fa-solid fa-folder-open"></i> All Documents
                </a>
                <button class="btn btn-primary" onclick="window.print();">
                    <i class="fa-solid fa-print"></i> Print Document
                </button>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fa-solid fa-house"></i> Portal Home
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger no-print" style="margin-bottom: 20px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- RENDERED AUTHENTIC DOCUMENT VIEW -->
    <?php if ($selectedDocument): ?>
        <div class="printable-sheet">
            <div class="printable-content">

                <?php 
                $docName = $selectedDocument['doc_name'];
                
                // 1. BONAFIDE CERTIFICATE
                if (stripos($docName, 'Bonafide') !== false || $doc_id == 1): 
                ?>
                    <div class="bonafide-box">
                        <div class="univ-logo-header">
                            <img src="../../assets/images/logo.webp" alt="University Seal" onerror="this.style.display='none'">
                            <h2 class="univ-title">Savitribai Phule Pune University</h2>
                            <p class="univ-sub">(Formerly University of Pune) * Ganeshkhind, Pune 411007</p>
                        </div>

                        <div class="doc-heading-pill">BONAFIDE & CHARACTER CERTIFICATE</div>

                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 8px;">This is to certify that</p>
                        <div class="bonafide-name"><?= htmlspecialchars($student_name) ?></div>

                        <div class="bonafide-body">
                            Student Roll Number: <strong><?= htmlspecialchars($roll_no) ?></strong> | Registration ID: <strong><?= htmlspecialchars($reg_id) ?></strong>
                            <br><br>
                            is a regular, bonafide student of this university admitted to the Department of <strong><?= htmlspecialchars($branch_name) ?></strong> (<strong><?= htmlspecialchars($sem_name) ?></strong>) for the Academic Session <strong>2023-2026</strong>.
                            <br><br>
                            During the period of academic enrollment in this institution, his/her conduct, attendance, and moral character have been found to be <strong>Exemplary and Satisfactory</strong>.
                            <br><br>
                            <span style="font-size: 0.92rem; color: #475569;">Issued on: <strong><?= date('d-M-Y') ?></strong> at Pune on student's request for official academic reference.</span>
                        </div>

                        <div class="doc-signatures">
                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Head of Department</div>
                            </div>

                            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                                <div id="docQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #1e3a8a; border-radius: 4px; box-shadow: 0 2px 8px rgba(30, 58, 138, 0.15);"></div>
                                <div style="font-size: 6.5pt; font-weight: 800; color: #1e3a8a; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">E-Verify Bonafide</div>
                            </div>

                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Principal / Registrar</div>
                            </div>
                        </div>
                    </div>

                <?php 
                // 2. FEE RECEIPT
                elseif (stripos($docName, 'Fee') !== false || $doc_id == 2): 
                ?>
                    <div style="border: 2px solid #0f172a; padding: 24px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../assets/images/logo.webp" alt="University Seal" style="height: 60px;" onerror="this.style.display='none'">
                                <div>
                                    <h2 style="font-size: 1.3rem; margin: 0; color: #1e3a8a; text-transform: uppercase;">Savitribai Phule Pune University</h2>
                                    <p style="margin: 2px 0; color: #64748b; font-size: 0.82rem;">Finance & Accounts Division * Ganeshkhind, Pune 411007</p>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #047857; text-transform: uppercase;">Official Academic Fee Receipt</span>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 0.85rem;">
                                <div><strong>Receipt No:</strong> SPPU-REC-<?= date('Y') ?>-<?= htmlspecialchars($roll_no) ?></div>
                                <div><strong>Txn Ref:</strong> PAY_<?= strtoupper(substr(md5($roll_no . 'FEES'), 0, 10)) ?></div>
                                <div><strong>Date:</strong> <?= date('d-M-Y H:i') ?></div>
                            </div>
                        </div>

                        <div class="doc-grid">
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Student Name</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($student_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Roll Number</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($roll_no) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Department / Program</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($branch_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Semester / Term</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($sem_name) ?> (2023-2026)</div>
                            </div>
                        </div>

                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Fee Description & Head</th>
                                    <th>Session / Term</th>
                                    <th style="text-align: right;">Amount (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Academic Tuition & Course Instruction Fee</td>
                                    <td>Academic Year 2023-2026</td>
                                    <td style="text-align: right;">₹ 12,500.00</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>University Examination & Assessment Fee</td>
                                    <td><?= htmlspecialchars($sem_name) ?></td>
                                    <td style="text-align: right;">₹ 1,850.00</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Computer Laboratory & Digital Library Access</td>
                                    <td>Annual Term</td>
                                    <td style="text-align: right;">₹ 1,200.00</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>Student Welfare, Gymkhana & Activity Fund</td>
                                    <td>Annual Term</td>
                                    <td style="text-align: right;">₹ 450.00</td>
                                </tr>
                                <tr style="background-color: #f8fafc; font-weight: 800; font-size: 1rem;">
                                    <td colspan="3" style="text-align: right;">Grand Total Amount Paid:</td>
                                    <td style="text-align: right; color: #047857;">₹ 16,000.00</td>
                                </tr>
                            </tbody>
                        </table>

                        <p style="font-size: 0.88rem; color: #475569; margin: 6px 0;">
                            <strong>Amount in Words:</strong> Rupees Sixteen Thousand Only.
                            <br>
                            <strong>Payment Status:</strong> <span class="badge badge-success" style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.78rem;">SUCCESS / PAID</span> (Payment Gateway - Net Banking/UPI)
                        </p>

                        <div class="doc-signatures">
                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Student / Depositor</div>
                            </div>

                            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                                <div id="docQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #047857; border-radius: 4px;"></div>
                                <div style="font-size: 6.5pt; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 4px;">E-Verify Receipt</div>
                            </div>

                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Finance & Accounts Officer</div>
                            </div>
                        </div>
                    </div>

                <?php 
                // 3. ADMISSION FORM
                elseif (stripos($docName, 'Admission') !== false || $doc_id == 3): 
                ?>
                    <div style="border: 2px solid #334155; padding: 24px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #334155; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../assets/images/logo.webp" alt="University Seal" style="height: 60px;" onerror="this.style.display='none'">
                                <div>
                                    <h2 style="font-size: 1.3rem; margin: 0; color: #1e3a8a; text-transform: uppercase;">Savitribai Phule Pune University</h2>
                                    <p style="margin: 2px 0; color: #64748b; font-size: 0.82rem;">Central Admissions Directorate * Academic Session 2023-2026</p>
                                </div>
                            </div>
                            <div style="border: 2px dashed #94a3b8; width: 85px; height: 95px; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; color: #64748b; text-align: center;">
                                Affix Student Photograph
                            </div>
                        </div>

                        <div style="text-align: center; font-weight: 800; font-size: 1.15rem; color: #0f172a; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            STUDENT ADMISSION & ENROLLMENT RECORD
                        </div>

                        <div class="doc-grid">
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Application / Reg ID</div>
                                <div class="doc-grid-value">ADM-SPPU-<?= htmlspecialchars($reg_id) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Student Roll Number</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($roll_no) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Full Name of Candidate</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($student_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Mother's Name</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($mother_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Date of Birth</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($dob) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Gender</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($gender) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Enrolled Department</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($branch_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Admitted Semester</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($sem_name) ?></div>
                            </div>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; margin: 16px 0; font-size: 0.85rem; color: #475569; line-height: 1.6;">
                            <strong>Declaration by Student:</strong> I hereby certify that the academic information, certificates, and personal details furnished by me during enrollment are true and complete to the best of my knowledge. I abide by all rules, regulations, and discipline standards mandated by Savitribai Phule Pune University.
                        </div>

                        <div class="doc-signatures">
                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Signature of Candidate</div>
                            </div>

                            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                                <div id="docQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #1e3a8a; border-radius: 4px;"></div>
                                <div style="font-size: 6.5pt; font-weight: 800; color: #1e3a8a; text-transform: uppercase; margin-top: 4px;">E-Verify Admission</div>
                            </div>

                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Dean / Registrar (Admissions)</div>
                            </div>
                        </div>
                    </div>

                <?php 
                // 4. EXAM HALL TICKET
                elseif (stripos($docName, 'Hall Ticket') !== false || $doc_id == 4): 
                ?>
                    <div style="border: 2px solid #1e3a8a; padding: 24px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../assets/images/logo.webp" alt="University Seal" style="height: 60px;" onerror="this.style.display='none'">
                                <div>
                                    <h2 style="font-size: 1.3rem; margin: 0; color: #1e3a8a; text-transform: uppercase;">Savitribai Phule Pune University</h2>
                                    <p style="margin: 2px 0; color: #64748b; font-size: 0.82rem;">Examination Division * End-Semester Examination Admit Card</p>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #1e40af; text-transform: uppercase;">EXAM HALL TICKET / ADMIT CARD</span>
                                </div>
                            </div>
                            <div style="border: 2px dashed #94a3b8; width: 80px; height: 90px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b; text-align: center;">
                                Candidate Photo
                            </div>
                        </div>

                        <div class="doc-grid">
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Candidate Name</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($student_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Seat / Roll Number</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($roll_no) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Department / Branch</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($branch_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Examination Center</div>
                                <div class="doc-grid-value">Center 01 - Main University Campus, Pune</div>
                            </div>
                        </div>

                        <h4 style="font-size: 0.95rem; margin: 16px 0 8px; color: #0f172a; text-transform: uppercase; font-weight: 700;">
                            Scheduled Theory & Practical Papers
                        </h4>

                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Course Title</th>
                                    <th>Timing</th>
                                    <th>Candidate Sign</th>
                                    <th>Invigilator Sign</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($student_subjects)): ?>
                                    <?php foreach ($student_subjects as $sub): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($sub['subj_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($sub['subj_name']) ?></td>
                                            <td>10:00 AM - 01:00 PM</td>
                                            <td style="width: 120px;"></td>
                                            <td style="width: 120px;"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td><strong>CS-101</strong></td>
                                        <td>Computer Science Fundamentals & Problem Solving</td>
                                        <td>10:00 AM - 01:00 PM</td>
                                        <td style="width: 120px;"></td>
                                        <td style="width: 120px;"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>MTC-101</strong></td>
                                        <td>Discrete Mathematics & Graph Theory</td>
                                        <td>10:00 AM - 01:00 PM</td>
                                        <td style="width: 120px;"></td>
                                        <td style="width: 120px;"></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div style="font-size: 0.8rem; color: #64748b; line-height: 1.5; margin-top: 10px;">
                            <strong>Instructions:</strong> 1. Possession of this Admit Card & Student ID is mandatory to enter the Exam Hall. 2. Arrive at least 20 minutes prior to exam time. 3. Electronic gadgets & smart watches are prohibited.
                        </div>

                        <div class="doc-signatures">
                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Signature of Candidate</div>
                            </div>

                            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                                <div id="docQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #1e3a8a; border-radius: 4px;"></div>
                                <div style="font-size: 6.5pt; font-weight: 800; color: #1e3a8a; text-transform: uppercase; margin-top: 4px;">E-Verify Hall Ticket</div>
                            </div>

                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Controller of Examinations</div>
                            </div>
                        </div>
                    </div>

                <?php 
                // 5. RESULT STATEMENT / TRANSCRIPT
                else: 
                ?>
                    <div style="border: 2px solid #0f2744; padding: 24px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f2744; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../assets/images/logo.webp" alt="University Seal" style="height: 60px;" onerror="this.style.display='none'">
                                <div>
                                    <h2 style="font-size: 1.3rem; margin: 0; color: #1e3a8a; text-transform: uppercase;">Savitribai Phule Pune University</h2>
                                    <p style="margin: 2px 0; color: #64748b; font-size: 0.82rem;">Official Academic Examination Transcript & Statement of Marks</p>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #1e3a8a; text-transform: uppercase;">STATEMENT OF MARKS & GRADES</span>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 0.85rem;">
                                <div><strong>Transcript ID:</strong> TR-<?= htmlspecialchars($roll_no) ?>-<?= $sem_id ?></div>
                                <div><strong>Date Issued:</strong> <?= date('d-M-Y') ?></div>
                            </div>
                        </div>

                        <div class="doc-grid">
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Candidate Name</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($student_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Student Roll Number</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($roll_no) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Program & Department</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($branch_name) ?></div>
                            </div>
                            <div class="doc-grid-item">
                                <div class="doc-grid-label">Semester / Term</div>
                                <div class="doc-grid-value"><?= htmlspecialchars($sem_name) ?></div>
                            </div>
                        </div>

                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Description</th>
                                    <th style="text-align: center;">Max Marks</th>
                                    <th style="text-align: center;">Marks Scored</th>
                                    <th style="text-align: center;">Credits</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($student_subjects)): ?>
                                    <?php foreach ($student_subjects as $sub): 
                                        $m = isset($sub['marks']) ? (float)$sub['marks'] : 85;
                                        $status = ($m >= 40) ? 'PASS' : 'FAIL';
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($sub['subj_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($sub['subj_name']) ?></td>
                                            <td style="text-align: center;">100</td>
                                            <td style="text-align: center; font-weight: 700;"><?= $m ?></td>
                                            <td style="text-align: center;"><?= htmlspecialchars($sub['credits'] ?? '4.0') ?></td>
                                            <td style="text-align: center;">
                                                <span style="font-weight: 700; color: <?= $status === 'PASS' ? '#16a34a' : '#dc2626' ?>;"><?= $status ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td><strong>CS-111</strong></td>
                                        <td>Computer Science Core Theory</td>
                                        <td style="text-align: center;">100</td>
                                        <td style="text-align: center; font-weight: 700;">90</td>
                                        <td style="text-align: center;">4.0</td>
                                        <td style="text-align: center; color: #16a34a; font-weight: 700;">PASS</td>
                                    </tr>
                                <?php endif; ?>
                                <tr style="background-color: #f1f5f9; font-weight: 800;">
                                    <td colspan="2">Consolidated Academic Summary:</td>
                                    <td style="text-align: center;"><?= $maxTotalMarks > 0 ? $maxTotalMarks : '100' ?></td>
                                    <td style="text-align: center; color: #1e3a8a;"><?= $totalMarksScored > 0 ? $totalMarksScored : '90' ?></td>
                                    <td style="text-align: center;">SGPA: <?= $sgpa > 0 ? $sgpa : '9.00' ?></td>
                                    <td style="text-align: center; color: #16a34a;">PASS</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-signatures">
                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Academic Registrar</div>
                            </div>

                            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                                <div id="docQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #0f2744; border-radius: 4px;"></div>
                                <div style="font-size: 6.5pt; font-weight: 800; color: #0f2744; text-transform: uppercase; margin-top: 4px;">E-Verify Result</div>
                            </div>

                            <div class="doc-sig-block">
                                <div class="doc-sig-line"></div>
                                <div>Controller of Examinations</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

    <!-- DOCUMENT REPOSITORY DIRECTORY LIST -->
    <div class="card no-print">
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-top: 0; margin-bottom: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-folder-tree" style="color: #4f46e5;"></i> Official University Academic Documents
        </h3>

        <div>
            <!-- Degree Certificate Link -->
            <a href="degree-print.php" class="doc-item-link">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div style="color: #0f172a; font-weight: 700;">Degree Certificate (B.Sc. Computer Science)</div>
                        <small style="color: #64748b; font-weight: 500;">Official University Degree Parchment with Distinction</small>
                    </div>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square" style="color: #4f46e5;"></i>
            </a>

            <!-- Migration Certificate Link -->
            <a href="generate-certificate.php" class="doc-item-link">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="fa-solid fa-file-shield"></i>
                    </div>
                    <div>
                        <div style="color: #0f172a; font-weight: 700;">Migration Certificate</div>
                        <small style="color: #64748b; font-weight: 500;">Transfer and migration clearance for higher education</small>
                    </div>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square" style="color: #4f46e5;"></i>
            </a>

            <!-- Repository Documents: Bonafide, Fee Receipt, Admission Form, Hall Ticket, Result Statement -->
            <?php if (!empty($documents)): ?>
                <?php foreach ($documents as $doc): 
                    $isActive = ($selectedDocument && $selectedDocument['doc_id'] == $doc['doc_id']);
                    $iconClass = 'fa-file-lines';
                    $iconBg = '#fdf2f8';
                    $iconColor = '#db2777';

                    if (stripos($doc['doc_name'], 'Bonafide') !== false) {
                        $iconClass = 'fa-certificate';
                        $iconBg = '#e0f2fe';
                        $iconColor = '#0284c7';
                    } elseif (stripos($doc['doc_name'], 'Fee') !== false) {
                        $iconClass = 'fa-receipt';
                        $iconBg = '#f0fdf4';
                        $iconColor = '#16a34a';
                    } elseif (stripos($doc['doc_name'], 'Admission') !== false) {
                        $iconClass = 'fa-id-card-clip';
                        $iconBg = '#fff7ed';
                        $iconColor = '#ea580c';
                    } elseif (stripos($doc['doc_name'], 'Hall Ticket') !== false) {
                        $iconClass = 'fa-ticket';
                        $iconBg = '#fef2f2';
                        $iconColor = '#dc2626';
                    } elseif (stripos($doc['doc_name'], 'Result') !== false) {
                        $iconClass = 'fa-chart-pie';
                        $iconBg = '#f5f3ff';
                        $iconColor = '#7c3aed';
                    }
                ?>
                    <a href="print-documents.php?doc_id=<?= $doc['doc_id'] ?>" class="doc-item-link <?= $isActive ? 'active' : '' ?>">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: <?= $iconBg ?>; color: <?= $iconColor ?>; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                                <i class="fa-solid <?= $iconClass ?>"></i>
                            </div>
                            <div>
                                <div style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($doc['doc_name']) ?></div>
                                <small style="color: #64748b; font-weight: 500;"><?= htmlspecialchars($doc['doc_type'] ?? 'Academic Form') ?> <?= $isActive ? ' &bull; <strong>Viewing</strong>' : '' ?></small>
                            </div>
                        </div>
                        <i class="fa-solid fa-arrow-right" style="color: #4f46e5;"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('docQRCode');
    if (qrContainer && typeof QRCode !== 'undefined') {
        const verifyData = <?= json_encode($verifyUrl) ?>;
        new QRCode(qrContainer, {
            text: verifyData,
            width: 58,
            height: 58,
            colorDark: "#1e3a8a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
</body>
</html>
