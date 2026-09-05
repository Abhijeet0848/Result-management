<?php

include_once __DIR__ . '/../config/connection.php'; // Ensure this file contains the correct PostgreSQL connection



if (isset($_POST['semester_id']) && isset($_POST['branch_id'])) {

$semester_id = (int)$_POST['semester_id']; // Cast to int for safety

$branch_id = (int)$_POST['branch_id']; // Cast to int for safety



// Prepare the SQL query using parameterized execution

$sql = "SELECT roll_no, name FROM student WHERE branch_id = $1 AND sem_id = $2 AND status = 1";

$result = pg_query_params($conn, $sql, array($branch_id, $semester_id));



// Check if the query executed successfully

if ($result) {

echo '<option value="">Select Student</option>';

while ($row = pg_fetch_assoc($result)) {

if (isset($row['name'])) { // Ensure 'name' column exists

echo '<option value="' . htmlspecialchars($row['roll_no']) . '">' . htmlspecialchars($row['name']) . '</option>';

} else {

echo '<option value="">Error: Name column not found</option>';

}

}

} else {

// Handle query execution error

echo '<option value="">Error executing query: ' . htmlspecialchars(pg_last_error($conn)) . '</option>';

}

} else {

// Handle missing POST parameters

echo '<option value="">Invalid request</option>';

}



// Close the PostgreSQL connection

pg_close($conn);

?>

