<?php

include_once __DIR__ . '/../config/connection.php';



header('Content-Type: text/html; charset=UTF-8');



if (isset($_POST['branch_id']) && isset($_POST['semester_id'])) {

$branch_id = (int)$_POST['branch_id'];

$semester_id = (int)$_POST['semester_id'];



if (!$conn) {

echo '<option value="">Error connecting to database</option>';

exit;

}



$sql = "SELECT roll_no, name FROM student WHERE branch_id = $1 AND sem_id = $2 AND status = 1 ORDER BY roll_no ASC";

$result = pg_query_params($conn, $sql, array($branch_id, $semester_id));



if ($result && pg_num_rows($result) > 0) {

$options = '<option value="">-- Select Student --</option>';

while ($row = pg_fetch_assoc($result)) {

$displayName = !empty($row['name']) ? htmlspecialchars($row['roll_no']) . ' - ' . htmlspecialchars($row['name']) : htmlspecialchars($row['roll_no']);

$options .= '<option value="' . htmlspecialchars($row['roll_no']) . '">' . $displayName . '</option>';

}

echo $options;

} else {

echo '<option value="">-- No Approved Students Found for this Branch & Semester --</option>';

}

exit;

}



echo '<option value="">-- Invalid Request --</option>';

exit;



