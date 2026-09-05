<?php
/**
 * Academic & Grading Helpers
 * Implements 10-Point Scale University Grading, SGPA, CGPA, and Dynamic Verification QR Codes.
 */

if (!function_exists('calculateGrade')) {
function calculateGrade($marks) {
$marks = (float)$marks;
if ($marks >= 90) return ['grade' => 'O','points' => 10, 'description' => 'Outstanding'];
if ($marks >= 80) return ['grade' => 'A+', 'points' => 9,'description' => 'Excellent'];
if ($marks >= 70) return ['grade' => 'A','points' => 8,'description' => 'Very Good'];
if ($marks >= 60) return ['grade' => 'B+', 'points' => 7,'description' => 'Good'];
if ($marks >= 55) return ['grade' => 'B','points' => 6,'description' => 'Above Average'];
if ($marks >= 50) return ['grade' => 'C','points' => 5,'description' => 'Average'];
if ($marks >= 40) return ['grade' => 'P','points' => 4,'description' => 'Pass'];
return ['grade' => 'F', 'points' => 0, 'description' => 'Fail / Re-appear'];
}
}

if (!function_exists('calculateSGPA')) {
/**
 * @param array $subject_marks Array of ['marks' => float, 'credits' => float]
 * @return array ['sgpa' => float, 'total_credits' => float, 'earned_points' => float, 'has_backlog' => bool]
 */
function calculateSGPA($subject_marks) {
$totalCredits = 0;
$totalPointsEarned = 0;
$hasBacklog = false;

foreach ($subject_marks as $item) {
$credit = isset($item['credits']) && (float)$item['credits'] > 0 ? (float)$item['credits'] : 4.0;
$gradeData = calculateGrade($item['marks']);

if ($gradeData['points'] === 0) {
$hasBacklog = true;
}

$totalCredits += $credit;
$totalPointsEarned += ($gradeData['points'] * $credit);
}

$sgpa = $totalCredits > 0 ? round($totalPointsEarned / $totalCredits, 2) : 0.0;
return [
'sgpa' => $sgpa,
'total_credits' => $totalCredits,
'earned_points' => $totalPointsEarned,
'has_backlog' => $hasBacklog
];
}
}

if (!function_exists('calculateCGPA')) {
/**
 * Calculates Cumulative Grade Point Average across all completed semesters
 */
function calculateCGPA($conn, $roll_no) {
if (!$conn) return 0.0;

$sql = "SELECT r.marks, r.sem_id, COALESCE(s.credits, 4.0) as credits 
FROM results r
LEFT JOIN subjects s ON s.subj_id = r.subj_id
WHERE r.roll_no = $1";
$result = pg_query_params($conn, $sql, array($roll_no));

if (!$result || pg_num_rows($result) === 0) {
return 0.0;
}

$totalCredits = 0;
$totalPointsEarned = 0;

while ($row = pg_fetch_assoc($result)) {
$credit = (float)$row['credits'];
$gradeData = calculateGrade($row['marks']);
$totalCredits += $credit;
$totalPointsEarned += ($gradeData['points'] * $credit);
}

return $totalCredits > 0 ? round($totalPointsEarned / $totalCredits, 2) : 0.0;
}
}

if (!function_exists('getPerformanceClassification')) {
function getPerformanceClassification($cgpa) {
if ($cgpa >= 8.5) return 'First Class with Distinction';
if ($cgpa >= 7.0) return 'First Class';
if ($cgpa >= 6.0) return 'Higher Second Class';
if ($cgpa >= 5.0) return 'Second Class';
if ($cgpa >= 4.0) return 'Pass Class';
return 'Needs Improvement';
}
}

if (!function_exists('generateVerificationQRCodeURL')) {
function generateVerificationQRCodeURL($roll_no, $sem_id, $branch_id) {
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$verifyUrl = $protocol . $host . "/frontend/pages/auth/result.php?stid=" . urlencode($roll_no) . "&sem_id=" . urlencode($sem_id) . "&branch_id=" . urlencode($branch_id) . "&verified=true";
return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verifyUrl);
}
}
