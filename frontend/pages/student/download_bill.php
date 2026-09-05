<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/index.php");
    exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';

$email = $_SESSION['student_username'] ?? '';
$type = $_GET['type'] ?? ($_SESSION['request_type'] ?? 'photocopy');
$pid = $_GET['pid'] ?? ($_SESSION['razorpay_payment_id'] ?? ($_SESSION['payment_id'] ?? ''));

$bill = null;

if (!empty($pid)) {
    if ($type === 'revaluation') {
        $sql = "SELECT * FROM revaluation_requests WHERE payment_id = $1";
    } else {
        $sql = "SELECT * FROM photocopy_requests WHERE paymentid = $1";
    }
    $res = pg_query_params($conn, $sql, array($pid));
    if ($res && pg_num_rows($res) > 0) {
        $bill = pg_fetch_assoc($res);
    }
}

if (!$bill && !empty($email)) {
    // Try to find the latest request for this student
    if ($type === 'revaluation') {
        $sql = "SELECT * FROM revaluation_requests WHERE email = $1 ORDER BY request_date DESC LIMIT 1";
    } else {
        $sql = "SELECT * FROM photocopy_requests WHERE email = $1 ORDER BY request_date DESC LIMIT 1";
    }
    $res = pg_query_params($conn, $sql, array($email));
    if ($res && pg_num_rows($res) > 0) {
        $bill = pg_fetch_assoc($res);
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/student/download_bill.php?type=" . urlencode($type) . (!empty($pid) ? "&pid=" . urlencode($pid) : "");

function amountToWords($number) {
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    );
    $number = (int)$number;
    if ($number === 0) return 'Zero Rupees Only';
    $res = '';
    if ($number >= 1000) {
        $th = (int)($number / 1000);
        $res .= ($words[$th] ?? $th) . ' Thousand ';
        $number %= 1000;
    }
    if ($number >= 100) {
        $h = (int)($number / 100);
        $res .= ($words[$h] ?? $h) . ' Hundred ';
        $number %= 100;
    }
    if ($number > 0) {
        if ($res !== '') $res .= 'and ';
        if ($number < 20) {
            $res .= $words[$number];
        } else {
            $t = (int)($number / 10) * 10;
            $u = $number % 10;
            $res .= $words[$t];
            if ($u > 0) $res .= ' ' . $words[$u];
        }
    }
    return 'Rupees ' . trim($res) . ' Only';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Fee Receipt | Examination Division</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <script src="../../assets/js/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #1E3A5F;
            --primary-dark: #0F172A;
            --accent: #2563EB;
            --success: #059669;
            --border: #CBD5E1;
            --bg-light: #F8FAFC;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #EEF2F6;
            color: #1E293B;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Action Bar (Screen Only) */
        .top-action-bar {
            background: #1E3A5F;
            color: #FFFFFF;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .receipt-wrapper {
            max-width: 800px;
            margin: 24px auto 36px auto;
            background: #FFFFFF;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            padding: 28px 36px;
            box-sizing: border-box;
            position: relative;
        }

        /* University Letterhead */
        .inst-header {
            text-align: center;
            border-bottom: 2px solid #1E3A5F;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .inst-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            color: #1E3A5F;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 3px 0;
        }
        .inst-affil {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 500;
            margin: 0 0 3px 0;
        }
        .inst-dept {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2563EB;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0;
        }

        /* Document Title Banner */
        .doc-title-bar {
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #1E3A5F;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-radius: 4px;
        }
        .doc-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0F172A;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.3px;
        }
        .badge-paid {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #10B981;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        /* Two-Column Info Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
        .meta-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .meta-box-title {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 3px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.84rem;
            margin-bottom: 4px;
        }
        .meta-row:last-child {
            margin-bottom: 0;
        }
        .meta-label {
            color: #475569;
            font-weight: 500;
        }
        .meta-val {
            color: #0F172A;
            font-weight: 700;
            text-align: right;
        }

        /* Fee Statement Table */
        .table-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1E3A5F;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .fee-table th {
            background: #1E3A5F;
            color: #FFFFFF;
            font-size: 0.78rem;
            font-weight: 700;
            text-align: left;
            padding: 6px 10px;
            letter-spacing: 0.3px;
        }
        .fee-table th:last-child {
            text-align: right;
        }
        .fee-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 0.84rem;
            color: #1E293B;
        }
        .fee-table td:last-child {
            text-align: right;
            font-weight: 700;
        }
        .fee-table tr.total-row td {
            background: #F8FAFC;
            border-top: 2px solid #1E3A5F;
            border-bottom: 2px solid #1E3A5F;
            font-weight: 800;
            font-size: 0.9rem;
            color: #0F172A;
        }
        .fee-table tr.total-row td:last-child {
            color: #059669;
            font-size: 1rem;
        }

        .words-box {
            background: #F8FAFC;
            border-left: 3px solid #059669;
            padding: 6px 12px;
            font-size: 0.78rem;
            color: #334155;
            margin-bottom: 16px;
            border-radius: 0 4px 4px 0;
        }

        /* Footer / Authenticity */
        .receipt-footer-grid {
            display: grid;
            grid-template-columns: 70px 1fr 170px;
            gap: 14px;
            align-items: center;
            border-top: 1px solid #E2E8F0;
            padding-top: 12px;
        }
        .qr-section {
            text-align: center;
        }
        .inst-notes {
            font-size: 0.72rem;
            color: #64748B;
            line-height: 1.4;
        }
        .sign-section {
            text-align: center;
            font-size: 0.75rem;
            color: #334155;
        }
        .sign-seal {
            border: 1px dashed #94A3B8;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 3px;
            font-size: 0.68rem;
            color: #1E3A5F;
            font-weight: 700;
            background: #F8FAFC;
        }

        /* Strict Single-Page Print Setup */
        @page {
            size: A4 portrait;
            margin: 0mm !important;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }
            html, body {
                background: #FFFFFF !important;
                color: #000000 !important;
                padding: 10mm 14mm !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-wrapper {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .inst-header {
                border-bottom: 2px solid #000000 !important;
                padding-bottom: 8px !important;
                margin-bottom: 12px !important;
            }
            .inst-name {
                color: #000000 !important;
                font-size: 1.25rem !important;
            }
            .doc-title-bar {
                background: #F1F5F9 !important;
                border: 1px solid #000000 !important;
                border-left: 5px solid #000000 !important;
                padding: 6px 10px !important;
                margin-bottom: 12px !important;
            }
            .meta-grid {
                gap: 10px !important;
                margin-bottom: 12px !important;
            }
            .meta-box {
                border: 1px solid #CBD5E1 !important;
                background: #F8FAFC !important;
                padding: 8px 10px !important;
            }
            .fee-table {
                margin-bottom: 10px !important;
            }
            .fee-table th {
                background: #1E3A5F !important;
                color: #FFFFFF !important;
                padding: 5px 8px !important;
            }
            .fee-table td {
                padding: 5px 8px !important;
            }
            .fee-table tr.total-row td {
                border-top: 2px solid #000000 !important;
                border-bottom: 2px solid #000000 !important;
                color: #000000 !important;
                padding: 6px 8px !important;
            }
            .words-box {
                padding: 5px 10px !important;
                margin-bottom: 12px !important;
            }
            .receipt-footer-grid {
                padding-top: 8px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print top-action-bar">
    <div style="font-weight: 700; font-family: 'Outfit', sans-serif; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-graduation-cap" style="color: #60A5FA;"></i>
        <span>Student Examination Portal</span>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="s_login.php" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.85rem; background: #FFFFFF; color: #1E3A5F; border-color: #E2E8F0; font-weight: 600;">
            <i class="fa-solid fa-house"></i> Student Home
        </a>
        <button onclick="window.print()" class="btn btn-primary" style="padding: 6px 16px; font-size: 0.85rem; font-weight: 700; background: #2563EB; border: none; box-shadow: 0 2px 6px rgba(37,99,235,0.3);">
            <i class="fa-solid fa-print"></i> Print Official Receipt
        </button>
    </div>
</div>

<div class="receipt-wrapper" id="receiptPrintArea">
    <!-- Institutional Letterhead -->
    <div class="inst-header">
        <h1 class="inst-name">Online Examination & Result Management</h1>
        <p class="inst-affil">Affiliated to Savitribai Phule Pune University • Autonomous Examination Cell</p>
        <p class="inst-dept">Examination Division & Student Services</p>
    </div>

    <!-- Receipt Header Strip -->
    <div class="doc-title-bar">
        <span class="doc-title">FEE PAYMENT & APPLICATION ACKNOWLEDGEMENT</span>
        <span class="badge-paid"><i class="fa-solid fa-circle-check"></i> PAID / SUCCESS</span>
    </div>

    <?php if ($bill): 
        $subjsRaw = $bill['subjects'] ?? '';
        $subjsArr = !empty($subjsRaw) ? array_filter(array_map('trim', explode(',', $subjsRaw))) : [];
        if (empty($subjsArr) && !empty($subjsRaw)) {
            $subjsArr = [$subjsRaw];
        }
        $subCount = max(1, count($subjsArr));
        $rate = ($type === 'revaluation') ? 250 : 100;
        $totalCalc = $subCount * $rate;
        $serviceTitle = ($type === 'revaluation') ? 'Answer Sheet Revaluation' : 'Answer Book Photocopy';

        $rawDate = $bill['request_date'] ?? date('Y-m-d H:i:s');
        $formattedDate = !empty($rawDate) ? date('d M Y, h:i A', strtotime($rawDate)) : date('d M Y, h:i A');
        $txnId = $bill['paymentid'] ?? ($bill['payment_id'] ?? 'PAY_DEMO10294');
    ?>

        <!-- Two-Column Meta Details -->
        <div class="meta-grid">
            <div class="meta-box">
                <div class="meta-box-title">Receipt & Student Information</div>
                <div class="meta-row">
                    <span class="meta-label">Transaction Reference:</span>
                    <span class="meta-val" style="font-family: monospace; color: #2563EB;"><?= htmlspecialchars($txnId) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Student Account / Email:</span>
                    <span class="meta-val"><?= htmlspecialchars($bill['email'] ?? $email) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Transaction Date & Time:</span>
                    <span class="meta-val"><?= htmlspecialchars($formattedDate) ?></span>
                </div>
            </div>

            <div class="meta-box">
                <div class="meta-box-title">Application Summary</div>
                <div class="meta-row">
                    <span class="meta-label">Service Category:</span>
                    <span class="meta-val"><?= htmlspecialchars($serviceTitle) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Subjects Count:</span>
                    <span class="meta-val"><?= $subCount ?> Subject(s)</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Payment Mode:</span>
                    <span class="meta-val">Online Payment Gateway</span>
                </div>
            </div>
        </div>

        <!-- Itemized Subject Fee Table -->
        <div class="table-title">Itemized Fee Statement</div>
        <table class="fee-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Sr. No.</th>
                    <th style="width: 50%;">Subject Description</th>
                    <th style="width: 25%;">Service Category</th>
                    <th style="width: 15%;">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                foreach ($subjsArr as $sName): 
                ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($sName) ?></td>
                        <td><?= htmlspecialchars($serviceTitle) ?></td>
                        <td>Rs. <?= number_format($rate, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Total Amount Paid:</td>
                    <td>Rs. <?= number_format($totalCalc, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Amount In Words -->
        <div class="words-box">
            <strong>Amount in Words:</strong> <?= amountToWords($totalCalc) ?>
        </div>

        <!-- Official Security & Verification Footer -->
        <div class="receipt-footer-grid">
            <div class="qr-section">
                <div id="billQRCode" style="display: inline-flex; padding: 2px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 4px;"></div>
                <div style="font-size: 5.5pt; font-weight: 700; color: #1E3A5F; text-transform: uppercase; margin-top: 2px;">Scan to Verify</div>
            </div>
            <div class="inst-notes">
                <strong>Important Instructions:</strong>
                <div>1. This is a computer-generated official receipt; no physical signature required.</div>
                <div>2. Please preserve this transaction reference for all future examination correspondence.</div>
                <div>3. The <?= strtolower($serviceTitle) ?> processing cycle is completed within 7 to 10 working days.</div>
            </div>
            <div class="sign-section">
                <div class="sign-seal">DIGITALLY VERIFIED<br>EXAM DIVISION</div>
                <strong>Controller of Examinations</strong>
                <div style="font-size: 0.68rem; color: #64748B;">Central Examination Cell</div>
            </div>
        </div>

    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #64748B;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.5rem; color: #F59E0B; margin-bottom: 12px; display: block;"></i>
            <h3 style="font-size: 1.15rem; color: #0F172A; margin: 0 0 6px 0;">No Active Payment Record Found</h3>
            <p style="margin: 0 0 16px 0;">There are no completed photocopy or revaluation submissions found for this session.</p>
            <a href="s_login.php" class="btn btn-primary">Return to Student Portal</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('billQRCode');
    if (qrContainer && typeof QRCode !== 'undefined') {
        const verifyData = <?= json_encode($verifyUrl) ?>;
        new QRCode(qrContainer, {
            text: verifyData,
            width: 58,
            height: 58,
            colorDark: "#0F172A",
            colorLight: "#FFFFFF",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
</body>
</html>
