<?php

session_start();

include_once __DIR__ . '/../../../backend/config/connection.php'; // Ensure this contains the correct PostgreSQL connection setup



// Fetch student details securely

$email = $_SESSION['student_email'] ?? null;



if ($email) {

$sql = "SELECT * FROM student WHERE email = $1";

$result = pg_query_params($conn, $sql, array($email));



if ($result && pg_num_rows($result) > 0) {

$student = pg_fetch_assoc($result);

} else {

echo "Student not found.";

exit;

}

} else {

echo "User not logged in.";

exit;

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

<link rel="stylesheet" href="../../assets/css/common.css"> <!-- Link to your CSS file -->

<link rel="stylesheet" href="../../assets/css/common.css">

<style>

.options ul {

list-style-type: none; /* Remove bullet points */

padding: 0; /* Remove padding */

}

.options li {

margin: 10px 0; /* Add some space between buttons */

}

.button {

display: inline-block; /* Make the link behave like a button */

padding: 10px 20px; /* Add padding */

font-size: 16px; /* Font size */

color: white; /* Text color */

background-color: ForestGreen; /* Button color */

border: none; /* Remove border */

border-radius: 5px; /* Rounded corners */

cursor: pointer; /* Change cursor to pointer */

transition: background-color 0.3s; /* Smooth transition */

}

.button:hover {

background-color: darkgreen; /* Darker color on hover */

}

</style>

<script>

function navigateTo(url) {

window.location.href = url; // Navigate to the specified URL

}

</script>

</head>

<body>

<nav>

<h1>SSR College of Arts, Commerce and Science</h1>

<div>

<button class="button" onclick="window.location.href="../../../backend/auth/logout.php"">Logout</button>

</div>

</nav>



<div class="container">

<h2>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h2>

<p>Email: <?php echo htmlspecialchars($student['email']); ?></p>



<div class="options">

<h3>Options</h3>

<ul>

<li><button class="button" onclick="navigateTo('../auth/find-result.php')">View Results</button></li>

<li><button class="button" onclick="navigateTo('request-photocopy.php')">Request Photocopy</button></li>

<li><button class="button" onclick="navigateTo('revaluation.php')">Request Revaluation</button></li>

<li><button class="button" onclick="navigateTo('update-profile.php')">Update Profile</button></li>

<li><button class="button" onclick="navigateTo('view-notices.php')">View Notices</button></li>

</ul>

</div>



<div class="footer">

<p>&copy; <?php echo date("Y"); ?> TYBSC Computer Science, SSR College. All rights reserved.</p>

</div>

</div>

</body>

</html>

