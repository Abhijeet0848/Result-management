<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$data = json_decode(file_get_contents('php://input'), true);

$order_id = $data['order_id'];

$payment_id = $data['payment_id'];

$signature = $data['signature'];

$student_id = intval($_SESSION['reg_id']); // Ensure integer for safety



// Simulate payment processing

// In real-world scenario, verify the payment signature and details using Razorpay API



// Update payment status in database

include_once __DIR__ . '/../../../backend/config/connection.php';

$payment_status = "success"; // Example status

$sql = "INSERT INTO payments (student_id, order_id, payment_id, payment_status) VALUES ($1, $2, $3, $4)";

$result = pg_query_params($conn, $sql, array($student_id, $order_id, $payment_id, $payment_status));



if ($result) {

// Now handle the photocopy request if it exists

if (isset($_SESSION['student_username'])) {

$email = $_SESSION['student_username']; // Get the email from the session



// Insert the photocopy request into the database

$sql = "INSERT INTO photocopy_requests (email) VALUES ($1)";

$result = pg_query_params($conn, $sql, array($email)); // Parameterized query for safety



if ($result) {

echo json_encode(['message' => 'Photocopy request submitted successfully.']);

} else {

echo json_encode(['error' => 'Error submitting photocopy request: ' . pg_last_error($conn)]);

}

} else {

echo json_encode(['error' => 'Please provide an email for the photocopy request.']);

}

} else {

echo json_encode(['error' => 'Error processing payment. Please try again.']);

}

}

