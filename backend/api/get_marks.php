<?php

include_once __DIR__ . '/../config/connection.php';



header('Content-Type: text/html; charset=UTF-8');



if (isset($_POST['semester_id']) && isset($_POST['branch_id']) && isset($_POST['student_id'])) {

$semester_id = (int)$_POST['semester_id'];

$branch_id = (int)$_POST['branch_id'];

$student_id = (int)$_POST['student_id'];



if (!$conn) {

echo '<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Database connection failed.</div>';

exit;

}



// Check if result already declared for this student, branch, and semester

$chk_sql = "SELECT result_id FROM results WHERE roll_no = $1 AND sem_id = $2 AND branch_id = $3 LIMIT 1";

$chk_res = pg_query_params($conn, $chk_sql, array($student_id, $semester_id, $branch_id));

if ($chk_res && pg_num_rows($chk_res) > 0) {

echo '<div class="alert alert-danger" style="margin-bottom: 16px;">

<i class="fa-solid fa-triangle-exclamation"></i>

<div><strong>Notice:</strong> Results have already been declared for Roll No <strong>' . htmlspecialchars($student_id) . '</strong> in this semester. You can edit existing marks from <a href="manage-results.php" style="text-decoration: underline; color: inherit; font-weight: bold;">Manage Results</a>.</div>

</div>';

}



$sql = "SELECT subjects.subj_id, subjects.subj_name, subjects.subj_code FROM subject_comb 

JOIN subjects ON subjects.subj_id = subject_comb.subj_id 

WHERE subject_comb.sem_id = $1 AND subject_comb.branch_id = $2

ORDER BY subjects.subj_name ASC";

$result = pg_query_params($conn, $sql, array($semester_id, $branch_id));



if ($result && pg_num_rows($result) > 0) {

echo '<div style="display: grid; gap: 14px;">';

while ($row = pg_fetch_assoc($result)) {

$codeLabel = !empty($row['subj_code']) ? ' <span style="font-size: 0.8rem; background: var(--bg-main); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color); color: var(--text-muted); font-weight: 500;">' . htmlspecialchars($row['subj_code']) . '</span>' : '';

echo '<div style="background: var(--bg-card); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">';

echo '<label style="font-weight: 600; color: var(--text-main); display: block; margin-bottom: 8px;">' . htmlspecialchars($row['subj_name']) . $codeLabel . '</label>';

echo '<input type="number" name="marks[]" min="0" max="100" step="0.5" required placeholder="Enter marks (0 - 100)" class="form-control" style="width: 100%; padding: 9px 14px; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); font-size: 0.95rem;">';

echo '</div>';

}

echo '</div>';

} else {

echo '<div class="alert alert-danger" style="margin: 0;">

<i class="fa-solid fa-circle-exclamation"></i>

<div>No subjects are mapped to this branch & semester combination yet. Please configure subjects in <a href="add-subject-combination.php" style="text-decoration: underline; color: inherit; font-weight: bold;">Subject Combination</a> first.</div>

</div>';

}

exit;

}



echo '<p style="color: var(--text-muted); margin: 0; text-align: center;">Please select branch, semester, and student to load subjects.</p>';

exit;

