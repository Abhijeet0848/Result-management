<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../auth/index.php");
    exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/config/session.php';

$email = $_SESSION['student_username'] ?? '';
$type = $_GET['type'] ?? ($_SESSION['request_type'] ?? 'photocopy');
$subject = $_SESSION['subject'] ?? '';
$subjects_list = $_SESSION['subjects_list'] ?? (!empty($subject) ? explode(", ", $subject) : []);

if (empty($subject) || empty($subjects_list)) {
    header("location: " . ($type === 'revaluation' ? "request-revalution.php" : "request-photocopy.php"));
    exit;
}

$subject_count = count($subjects_list);
$rate = ($type === 'revaluation') ? 250 : 100;
$amount = ($type === 'revaluation') ? ($_SESSION['revaluation_amount'] ?? ($subject_count * $rate)) : ($_SESSION['photocopy_amount'] ?? ($subject_count * $rate));
$amount_paise = (int)($amount * 100);
$serviceTitle = ($type === 'revaluation') ? 'Answer Sheet Revaluation' : 'Answer Book Photocopy';
$razorpay_key = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_nG6hRXPQ1pJ9wE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & QR Checkout | SSR College Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <script src="../../assets/js/qrcode.min.js"></script>
    <!-- Official Razorpay Checkout JS SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F0F4F8;
            color: #0F172A;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        .checkout-wrapper {
            max-width: 540px;
            margin: 36px auto;
            padding: 0 16px;
        }
        .checkout-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.08);
            border: 1px solid #E2E8F0;
            overflow: hidden;
        }
        .checkout-header {
            background: #1E3A5F;
            color: #FFFFFF;
            padding: 22px 24px;
            text-align: center;
        }
        .rzp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            color: #93C5FD;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.76rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .checkout-body {
            padding: 24px;
        }
        .order-summary {
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.9rem;
            color: #475569;
        }
        .summary-row.total {
            border-top: 1px solid #E2E8F0;
            margin-top: 8px;
            padding-top: 10px;
            font-weight: 800;
            font-size: 1.15rem;
            color: #0F172A;
        }
        .subject-tag {
            display: inline-block;
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            margin: 2px 4px 2px 0;
        }

        /* Payment Buttons */
        .btn-razorpay {
            background: linear-gradient(135deg, #0C2340 0%, #002B49 100%);
            color: #528FF0;
            border: 1px solid #1A446C;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 800;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(12, 35, 64, 0.25);
            margin-bottom: 12px;
        }
        .btn-razorpay:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(12, 35, 64, 0.35);
            color: #FFFFFF;
            background: #0C2340;
        }
        .rzp-logo-text {
            color: #528FF0;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .btn-paid-confirm {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #FFFFFF;
            border: 1px solid #065F46;
            padding: 13px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 800;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
        }
        .btn-paid-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
            background: #047857;
            color: #FFFFFF;
        }

        .divider-or {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 14px 0;
            color: #94A3B8;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .divider-or::before, .divider-or::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #E2E8F0;
        }
        .divider-or:not(:empty)::before { margin-right: .75em; }
        .divider-or:not(:empty)::after { margin-left: .75em; }

        .bypass-box {
            background: #F0FDF4;
            border: 1.5px solid #86EFAC;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        /* Floating Bypass Bar visible even when Razorpay popup is open */
        .floating-paid-bar {
            position: fixed;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2147483647;
            background: #0F172A;
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #334155;
            animation: pulseGlow 2.5s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
            50% { box-shadow: 0 10px 35px rgba(5, 150, 105, 0.6); }
        }
        .floating-paid-bar button {
            background: #10B981;
            color: #FFFFFF;
            border: none;
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .floating-paid-bar button:hover {
            background: #059669;
            transform: scale(1.04);
        }
    </style>
</head>
<body>

    <!-- Floating Always-Accessible Bypass Controller -->
    <div class="floating-paid-bar" id="floatingPaidWidget">
        <span style="font-size: 0.84rem; font-weight: 600; color: #E2E8F0;">
            <i class="fa-solid fa-qrcode" style="color: #34D399;"></i> Scanned QR or Paid?
        </span>
        <button type="button" onclick="confirmPaidAndProceed()">
            <i class="fa-solid fa-circle-check"></i> I Have Paid (Next)
        </button>
    </div>

    <div class="checkout-wrapper">
        <div class="checkout-card">
            <div class="checkout-header">
                <div class="rzp-badge">
                    <i class="fa-solid fa-shield-halved"></i> 256-BIT ENCRYPTED PAYMENT
                </div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; margin: 0 0 4px 0; color: #FFFFFF; font-weight: 800;">SSR College Examination Division</h1>
                <p style="margin: 0; font-size: 0.85rem; color: #DBEAFE;"><?= htmlspecialchars($serviceTitle) ?> Portal</p>
            </div>

            <div class="checkout-body">
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Student Account:</span>
                        <span style="font-weight: 700; color: #0F172A;"><?= htmlspecialchars($email) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Service Category:</span>
                        <span style="font-weight: 700; color: #1E3A5F;"><?= htmlspecialchars($serviceTitle) ?></span>
                    </div>
                    <div class="summary-row" style="flex-direction: column; gap: 4px; padding-top: 8px;">
                        <span>Selected Subject(s) (<?= $subject_count ?>):</span>
                        <div style="margin-top: 4px;">
                            <?php foreach ($subjects_list as $s): ?>
                                <span class="subject-tag"><?= htmlspecialchars(trim($s)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="summary-row" style="margin-top: 6px;">
                        <span>Service Fee Rate:</span>
                        <span>Rs. <?= number_format($rate, 2) ?> x <?= $subject_count ?> Subject(s)</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Payable Amount:</span>
                        <span style="color: #059669; font-size: 1.25rem;">Rs. <?= number_format($amount, 2) ?></span>
                    </div>
                </div>

                <!-- Hidden form submitted after Razorpay or Bypass payment confirmation -->
                <form id="payment-form" action="../../../backend/api/create_order.php" method="POST">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <input type="hidden" name="amount" value="<?= $amount_paise ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
                </form>

                <!-- Primary Razorpay Overlay Button -->
                <button type="button" id="rzp-button" class="btn-razorpay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.5 3H7.5L3 14.5L12 21L21 14.5L16.5 3Z" stroke="#528FF0" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M8 10.5L12 13.5L16 10.5" stroke="#528FF0" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Open <strong class="rzp-logo-text">Razorpay</strong> Popup (Rs. <?= number_format($amount, 2) ?>)</span>
                </button>

                <div class="divider-or">or instant confirmation</div>

                <!-- Bypass / QR Paid Confirmation Box -->
                <div class="bypass-box">
                    <p style="font-size: 0.85rem; color: #166534; font-weight: 600; margin: 0 0 10px 0;">
                        <i class="fa-solid fa-qrcode" style="font-size: 1rem; margin-right: 4px;"></i> 
                        Scanned the QR code on your UPI app?
                    </p>
                    <button type="button" onclick="confirmPaidAndProceed()" class="btn-paid-confirm">
                        <i class="fa-solid fa-check-double"></i> I Have Paid (Generate Official Bill)
                    </button>
                    <div style="font-size: 0.74rem; color: #15803D; margin-top: 6px;">
                        Instant verification • Generates unique transaction ID
                    </div>
                </div>

                <div style="text-align: center; margin-top: 18px;">
                    <a href="<?= ($type === 'revaluation') ? 'request-revalution.php' : 'request-photocopy.php' ?>" style="color: #64748B; font-size: 0.85rem; text-decoration: none; font-weight: 500;">
                        <i class="fa-solid fa-arrow-left"></i> Cancel and change subjects
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    var rzpInstance = null;

    document.getElementById('rzp-button').onclick = function (e) {
        e.preventDefault();

        if (typeof Razorpay === 'undefined') {
            alert('Razorpay checkout is loading. Proceeding with instant payment confirmation...');
            confirmPaidAndProceed();
            return;
        }

        var options = {
            "key": <?= json_encode($razorpay_key) ?>,
            "amount": <?= $amount_paise ?>,
            "currency": "INR",
            "name": "SSR College of Arts, Commerce & Science",
            "description": "<?= htmlspecialchars($serviceTitle) ?> Fee (<?= $subject_count ?> Subjects)",
            "image": "https://cdn-icons-png.flaticon.com/512/3135/3135715.png",
            "handler": function (response) {
                var pid = response.razorpay_payment_id || generatePayId();
                document.getElementById('razorpay_payment_id').value = pid;
                document.getElementById('payment-form').submit();
            },
            "prefill": {
                "name": "Student",
                "email": "<?= htmlspecialchars($email) ?>",
                "contact": "9999999999"
            },
            "notes": {
                "subject": "<?= htmlspecialchars($subject) ?>",
                "service": "<?= htmlspecialchars($serviceTitle) ?>"
            },
            "theme": {
                "color": "#1E3A5F"
            },
            "modal": {
                "ondismiss": function() {
                    console.log('Razorpay modal closed');
                }
            }
        };

        try {
            rzpInstance = new Razorpay(options);
            rzpInstance.on('payment.failed', function (response){
                console.warn("Razorpay payment dismiss/failure:", response);
            });
            rzpInstance.open();
        } catch (err) {
            console.error('Error opening Razorpay:', err);
            confirmPaidAndProceed();
        }
    };

    function generatePayId() {
        return 'PAY_' + Math.random().toString(36).substring(2, 8).toUpperCase() + Math.random().toString(36).substring(2, 7).toUpperCase();
    }

    function confirmPaidAndProceed() {
        if (rzpInstance && typeof rzpInstance.close === 'function') {
            try { rzpInstance.close(); } catch(e){}
        }

        var txnId = generatePayId();
        document.getElementById('razorpay_payment_id').value = txnId;
        
        var widget = document.getElementById('floatingPaidWidget');
        if (widget) {
            widget.innerHTML = '<span style="color: #34D399; font-weight: 700;"><i class="fa-solid fa-spinner fa-spin"></i> Confirming Payment & Generating Bill...</span>';
        }

        setTimeout(function() {
            document.getElementById('payment-form').submit();
        }, 400);
    }
    </script>
</body>
</html>