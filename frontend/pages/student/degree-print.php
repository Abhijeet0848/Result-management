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
            padding: 30px 15px;
            font-family: 'Cinzel', 'Times New Roman', Georgia, serif;
            margin: 0;
        }
        .cert-wrapper {
            max-width: 900px;
            margin: auto;
            width: 100%;
            box-sizing: border-box;
        }
        .cert-actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .cert-actions .btn {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .certificate {
            border: 12px double #1e3a8a;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            box-sizing: border-box;
            width: 100%;
        }
        .cert-header {
            margin-bottom: 24px;
        }
        .logo {
            height: 85px;
            max-width: 100%;
            object-fit: contain;
            margin-bottom: 12px;
        }
        .univ-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .cert-title {
            font-size: 1.85rem;
            font-weight: 900;
            color: #0f172a;
            margin: 24px 0 16px;
            letter-spacing: 1.5px;
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding-bottom: 4px;
            line-height: 1.3;
        }
        .recipient-name {
            font-size: 2rem;
            font-weight: 800;
            color: #1e40af;
            margin: 20px 0;
            font-family: 'Georgia', serif;
            word-break: break-word;
        }
        .cert-body {
            font-size: 1.15rem;
            line-height: 1.9;
            color: #334155;
            max-width: 760px;
            margin: 0 auto;
        }
        .cert-signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 20px;
            gap: 12px;
        }
        .sig-block {
            text-align: center;
            font-size: 0.95rem;
            font-weight: 600;
            color: #475569;
            flex: 1;
            max-width: 180px;
        }
        .sig-line {
            width: 100%;
            border-top: 1.5px solid #0f172a;
            margin-bottom: 6px;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px 8px;
            }
            .certificate {
                padding: 24px 16px;
                border-width: 8px;
            }
            .logo {
                height: 70px;
            }
            .univ-name {
                font-size: 1.25rem;
            }
            .cert-title {
                font-size: 1.35rem;
                letter-spacing: 1px;
                margin: 16px 0 12px;
            }
            .recipient-name {
                font-size: 1.5rem;
                margin: 14px 0;
            }
            .cert-body {
                font-size: 0.98rem;
                line-height: 1.65;
            }
            .cert-signatures {
                margin-top: 36px;
                padding: 0 5px;
                gap: 8px;
            }
            .sig-block {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 540px) {
            .cert-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .cert-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px 4px;
            }
            .certificate {
                padding: 18px 10px;
                border-width: 6px;
            }
            .logo {
                height: 60px;
            }
            .univ-name {
                font-size: 1.05rem;
                letter-spacing: 0.5px;
            }
            .cert-title {
                font-size: 1.15rem;
                letter-spacing: 0.5px;
                margin: 12px 0 10px;
            }
            .recipient-name {
                font-size: 1.3rem;
                margin: 10px 0;
            }
            .cert-body {
                font-size: 0.88rem;
                line-height: 1.55;
            }
            .cert-signatures {
                margin-top: 28px;
                padding: 0;
                gap: 6px;
            }
            .sig-block {
                font-size: 0.72rem;
            }
        }

        @page {
            size: A4 portrait;
            margin: 0mm !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0mm !important;
            }
            body { 
                background: white !important; 
                padding: 12mm 16mm !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .cert-wrapper { max-width: 100% !important; margin: 0 !important; width: 100% !important; }
            .certificate { box-shadow: none !important; border: 10px double #1e3a8a !important; padding: 40px !important; }
            .univ-name { font-size: 1.6rem !important; }
            .cert-title { font-size: 2rem !important; }
            .recipient-name { font-size: 2rem !important; }
            .cert-body { font-size: 1.15rem !important; line-height: 2 !important; }
            .cert-signatures { margin-top: 60px !important; padding: 0 40px !important; }
            .sig-block { font-size: 0.95rem !important; max-width: 180px !important; }
        }
    </style>
</head>
<body>

<div class="cert-wrapper">
    <div class="no-print cert-actions">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Return to Portal
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Print Degree Certificate
        </button>
    </div>

    <div class="certificate">
        <div class="cert-header">
            <img src="../../assets/images/logo.webp" alt="University Seal" class="logo" onerror="this.style.display='none'">
            <h1 class="univ-name">Savitribai Phule Pune University</h1>
            <p style="margin: 4px 0; color: #64748b; font-size: 0.88rem; font-style: italic;">(Formerly University of Pune) * Ganeshkhind, Pune 411007</p>
        </div>

        <div class="cert-title">DEGREE OF BACHELOR OF SCIENCE</div>

        <p style="color: #64748b; font-size: 0.95rem; margin-top: 10px;">This is to certify that</p>
        <div class="recipient-name"><?= htmlspecialchars($student_name) ?></div>

        <div class="cert-body">
            Roll No: <strong><?= htmlspecialchars($roll_no) ?></strong>, having examined and verified by the Board of Examinations, has been admitted to the degree of <strong>Bachelor of Science</strong> in <strong><?= htmlspecialchars($branch_name) ?></strong> with distinction at the examination held in <strong>Academic Session 2023-2026</strong>.
        </div>

        <div class="cert-signatures">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div>Registrar</div>
            </div>

            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
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
