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

if (!empty($email) && $conn) {
    $sql = "SELECT s.name, s.roll_no, s.dob, b.branch_name 
            FROM student s 
            LEFT JOIN branch b ON s.branch_id = b.branch_id 
            WHERE s.email = $1";
    $res = pg_query_params($conn, $sql, array($email));
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        if (!empty($row['name'])) $student_name = $row['name'];
        if (!empty($row['roll_no'])) $roll_no = $row['roll_no'];
        if (!empty($row['branch_name'])) $branch_name = $row['branch_name'];
        if (!empty($row['dob'])) $dob = $row['dob'];
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/student/generate-certificate.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Certificate - <?= htmlspecialchars($student_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <script src="../../assets/js/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Georgia', serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 30px 15px;
            color: #1e293b;
        }
        .cert-wrapper {
            max-width: 820px;
            margin: 0 auto;
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
        .certificate-container {
            max-width: 820px;
            margin: 0 auto;
            padding: 40px;
            background: #ffffff;
            border: 10px double #047857;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            box-sizing: border-box;
            width: 100%;
        }
        .certificate-header img {
            height: 75px;
            max-width: 100%;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .certificate-header h2 {
            font-size: 1.6rem;
            margin: 0;
            color: #047857;
            text-transform: uppercase;
            line-height: 1.25;
            letter-spacing: 0.5px;
        }
        .cert-sub {
            margin: 4px 0;
            color: #64748b;
            font-size: 0.88rem;
            font-style: italic;
        }
        .certificate-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 22px 0 16px;
            color: #0f172a;
            letter-spacing: 1px;
            line-height: 1.3;
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding-bottom: 4px;
        }
        .cert-intro {
            color: #64748b;
            font-size: 0.95rem;
            margin: 12px 0 4px;
            text-align: center;
        }
        .recipient-name {
            font-size: 1.95rem;
            font-weight: 800;
            color: #047857;
            margin: 14px 0;
            text-align: center;
            word-break: break-word;
        }
        .cert-body {
            text-align: center;
            font-size: 1.08rem;
            line-height: 1.85;
            max-width: 720px;
            margin: 0 auto 20px;
            color: #334155;
        }
        .cert-issue-date {
            display: inline-block;
            margin-top: 10px;
            font-size: 0.95rem;
            color: #475569;
        }
        .signatures {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 20px;
            gap: 12px;
        }
        .signature-block {
            text-align: center;
            font-size: 0.92rem;
            font-weight: 600;
            flex: 1;
            max-width: 180px;
            color: #475569;
        }
        .signature-line {
            width: 100%;
            border-top: 1.5px solid #334155;
            margin-bottom: 6px;
        }

        @media (max-width: 768px) {
            body {
                padding: 16px 8px;
            }
            .certificate-container {
                padding: 24px 16px;
                border-width: 6px;
            }
            .certificate-header img {
                height: 60px;
            }
            .certificate-header h2 {
                font-size: 1.25rem;
            }
            .certificate-title {
                font-size: 1.35rem;
                margin: 16px 0 12px;
            }
            .recipient-name {
                font-size: 1.5rem;
                margin: 10px 0;
            }
            .cert-body {
                font-size: 0.96rem;
                line-height: 1.65;
            }
            .signatures {
                margin-top: 36px;
                padding: 0 5px;
                gap: 8px;
            }
            .signature-block {
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
            .certificate-container {
                padding: 18px 10px;
                border-width: 5px;
            }
            .certificate-header img {
                height: 50px;
            }
            .certificate-header h2 {
                font-size: 1.05rem;
            }
            .certificate-title {
                font-size: 1.15rem;
                margin: 12px 0 8px;
            }
            .recipient-name {
                font-size: 1.25rem;
                margin: 8px 0;
            }
            .cert-body {
                font-size: 0.88rem;
                line-height: 1.55;
            }
            .signatures {
                margin-top: 28px;
                padding: 0;
                gap: 6px;
            }
            .signature-block {
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
            .certificate-container { box-shadow: none !important; border: 8px double #047857 !important; padding: 40px !important; }
            .certificate-header h2 { font-size: 1.6rem !important; }
            .certificate-title { font-size: 1.8rem !important; }
            .recipient-name { font-size: 1.95rem !important; }
            .cert-body { font-size: 1.1rem !important; line-height: 1.9 !important; }
            .signatures { margin-top: 50px !important; padding: 0 40px !important; }
            .signature-block { font-size: 0.95rem !important; max-width: 180px !important; }
        }
    </style>
</head>
<body>

<div class="cert-wrapper">
    <div class="no-print cert-actions">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Return to Portal
        </a>
        <button class="btn btn-primary" onclick="window.print();">
            <i class="fa-solid fa-print"></i> Print Migration Certificate
        </button>
    </div>

    <div class="certificate-container">
        <div class="certificate-header">
            <img src="../../assets/images/logo.webp" alt="University Logo" onerror="this.style.display='none'">
            <h2>Savitribai Phule Pune University</h2>
            <p class="cert-sub">(Formerly University of Pune) * Ganeshkhind, Pune 411007</p>
        </div>

        <div class="certificate-title">MIGRATION CERTIFICATE</div>

        <p class="cert-intro">This is to certify that</p>
        <div class="recipient-name"><?= htmlspecialchars($student_name) ?></div>

        <div class="cert-body">
            bearing Student Roll Number <strong><?= htmlspecialchars($roll_no) ?></strong>, enrolled under the Department of <strong><?= htmlspecialchars($branch_name) ?></strong> (Date of Birth: <strong><?= htmlspecialchars($dob) ?></strong>), has no dues pending with this institution and is hereby granted this Migration Certificate on recommendation of the Academic Council for pursuing higher studies.
            <br>
            <span class="cert-issue-date">Issued on: <strong><?= date('d-M-Y') ?></strong> at Pune.</span>
        </div>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div>Deputy Registrar (Examinations)</div>
            </div>

            <div style="text-align: center; margin: 0 6px; flex-shrink: 0;">
                <div id="migrationQRCode" style="display: inline-flex; padding: 3px; background: #ffffff; border: 1.5px solid #047857; border-radius: 4px; box-shadow: 0 2px 8px rgba(4, 120, 87, 0.15);"></div>
                <div style="font-size: 6.5pt; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">E-Verify Migration</div>
            </div>

            <div class="signature-block">
                <div class="signature-line"></div>
                <div>Principal / Dean</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('migrationQRCode');
    if (qrContainer && typeof QRCode !== 'undefined') {
        const verifyData = <?= json_encode($verifyUrl) ?>;
        new QRCode(qrContainer, {
            text: verifyData,
            width: 60,
            height: 60,
            colorDark: "#047857",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
</body>
</html>
