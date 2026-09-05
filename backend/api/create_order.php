<?php
session_start();

include_once __DIR__ . '/../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? ('PAY_' . strtoupper(uniqid()));
    $email = $_SESSION['student_username'] ?? ($_POST['email'] ?? '');
    $subject = $_SESSION['subject'] ?? ($_POST['subject'] ?? '');
    $type = $_POST['type'] ?? ($_SESSION['request_type'] ?? 'photocopy');

    $_SESSION['razorpay_payment_id'] = $razorpay_payment_id;
    $_SESSION['payment_id'] = $razorpay_payment_id;

    if ($type === 'revaluation') {
        $addsql = "INSERT INTO revaluation_requests (email, subjects, payment_id) VALUES ($1, $2, $3)";
        $result = pg_query_params($conn, $addsql, array($email, $subject, $razorpay_payment_id));
        $redirectUrl = "../../frontend/pages/student/download-receipt.php?type=revaluation&pid=" . urlencode($razorpay_payment_id);
    } else {
        $addsql = "INSERT INTO photocopy_requests (email, subjects, paymentid) VALUES ($1, $2, $3)";
        $result = pg_query_params($conn, $addsql, array($email, $subject, $razorpay_payment_id));
        $redirectUrl = "../../frontend/pages/student/download-receipt.php?type=photocopy&pid=" . urlencode($razorpay_payment_id);
    }

    if ($result) {
        header("location: " . $redirectUrl);
        exit;
    } else {
        echo "Database Error: " . pg_last_error($conn);
    }
} else {
    header("location: ../../frontend/pages/student/payment.php");
    exit;
}
