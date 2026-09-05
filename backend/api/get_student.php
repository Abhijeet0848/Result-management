<?php

include_once __DIR__ . '/../config/connection.php';



if (!empty($_POST["classid"])) {

$id_1 = $_POST['classid'];

$dta = explode("$", $id_1);

$branch_id = intval($dta[0]);

$cid = intval($dta[1]);



if (!is_numeric($cid) || !is_numeric($branch_id)) {

echo htmlentities("Invalid");

exit;

} else {

$sql1 = "SELECT name, roll_no FROM student WHERE sem_id = $1 AND branch_id = $2 AND status = 1 ORDER BY name";

$result = pg_query_params($conn, $sql1, array($cid, $branch_id));

?>

<option value="">Select Name</option>

<?php

while ($row = pg_fetch_assoc($result)) {

?>

<option value="<?php echo htmlspecialchars($row['roll_no']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>

<?php

}

}

}



// Code for Subjects

if (!empty($_POST["classid1"])) {

$id_2 = $_POST['classid1'];

$dta = explode("$", $id_2);

$branch_id1 = intval($dta[0]);

$cid1 = intval($dta[1]);



if (!is_numeric($cid1) || !is_numeric($branch_id1)) {

echo htmlentities("Invalid Class");

exit;

} else {

$sql2 = "SELECT subjects.subj_name FROM subject_comb 

 JOIN subjects ON subjects.subj_id = subject_comb.subj_id 

 WHERE subject_comb.sem_id = $1 AND subject_comb.branch_id = $2 

 ORDER BY subjects.subj_name";

$result1 = pg_query_params($conn, $sql2, array($cid1, $branch_id1));



while ($row = pg_fetch_assoc($result1)) {

?>

<p style="margin-left:120px; font-size: 17px;">

<?php echo htmlspecialchars($row['subj_name']); ?><br>

<input type="text" name="marks[]" value="" required placeholder="Enter marks out of 100" autocomplete="off" style="width: 96%; padding:5px; font-size:17px; margin-top:5px">

</p>

<?php

}

}

}



if (!empty($_POST["studclass"])) {

$id = $_POST['studclass'];

$dta = explode("$", $id);

$id = intval($dta[0]);

$id1 = intval($dta[1]);

$id2 = intval($dta[2]);



$sql3 = "SELECT roll_no, sem_id FROM results WHERE roll_no = $1 AND sem_id = $2 AND branch_id = $3";

$result2 = pg_query_params($conn, $sql3, array($id1, $id, $id2));

$num = pg_num_rows($result2);



if ($num > 0) {

?>

<p>

<?php

echo "<span style='color:red'>Result Already Declared.</span>";

echo "<script>$('#submit').prop('disabled',true);</script>";

?>

</p>

<?php

}

}

?>

