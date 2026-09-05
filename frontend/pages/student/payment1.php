<?php

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {

header("location: ../auth/index.php");

exit;

}



// Include your database connection file

include_once __DIR__ . '/../../../backend/config/connection.php';



// Use null coalescing operator to avoid null values

$email = $_SESSION['student_username'] ?? ''; // Default to an empty string if not set

$subject = $_SESSION['subject'] ?? ''; // Default to an empty string if not set
?>



<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment Page</title>

<link rel="stylesheet" href="../../assets/css/common.css">

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<style>

body {

font-family: Arial, sans-serif;

margin: 0;

padding: 20px;

background-color: #f0f4f8;

}

h1 {

text-align: center;

color: #333;

}

form {

max-width: 400px;

margin: auto;

padding: 20px;

background: white;

border-radius: 8px;

box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);

}

label {

display: block;

margin-bottom: 8px;

font-weight: bold;

color: #555;

}

select {

width: 100%;

padding: 10px;

margin-bottom: 20px;

border: 1px solid #ccc;

border-radius: 5px;

font-size: 16px;

}

button {

background-color: #4CAF50;

color: white;

border: none;

padding: 10px 20px;

border-radius: 5px;

cursor: pointer;

transition: background-color 0.3s;

width: 100%; /* Full width button */

font-size: 16px;

}

button:hover {

background-color: #45a049;

}

</style>

</head>

<body>

<h1>Payment Page</h1>

<form id="payment-form" action="create_order1.php" method="POST">

<input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

<input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">

<input type="hidden" name="amount" id="amount" value="10000"> <!-- Set the amount here -->

<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">



<label for="payment_method">Select Payment Method:</label>

<select name="payment_method" id="payment_method" required>

<option value="">Select Payment Method</option>

<option value="credit_card">Credit Card</option>

<option value="upi_id">UPI ID</option>

<option value="qr_code">QR Code</option>

</select> 

<button type="button" id="rzp-button">Pay Now</button>

</form>



<script>

document.getElementById('rzp-button').onclick = function (e) {

var amount = document.getElementById('amount').value; // Get the amount from the hidden input

var options = {

"key": "rzp_test_nG6hRXPQ 1pJ9wE", // Enter the Key ID generated from the Dashboard

"amount": amount, // Amount is in currency subunits. For example, 50000 paise = INR 500.

"currency": "INR",

"name": "Your Company Name",

"description": "Payment for Order #1234",

"image": "https://your-logo-url.com/logo.png",

"handler": function (response) {

// Create a hidden input to send the payment ID

var paymentIdInput = document.createElement("input");

paymentIdInput.type = "hidden";

paymentIdInput.name = "razorpay_payment_id";

paymentIdInput.value = response.razorpay_payment_id;



// Append the payment ID to the form

document.getElementById('payment-form').appendChild(paymentIdInput);



// Submit the form

document.getElementById('payment-form').submit();

},

"prefill": {

"name": "Your Customer Name",

"email": "<?php echo htmlspecialchars($email); ?>",

"contact": "9999999999" // You can also fetch this from session if available

},

"notes": {

"address": "Customer Address"

},

"theme": {

"color": "#3399cc"

}

};

var rzp1 = new Razorpay(options);

rzp1.open();

e.preventDefault();

}

</script>

</body>

</html>