<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("Location: ../auth/index.php");
exit;
}

// Fetch all documents
$sql = "SELECT * FROM documents ORDER BY doc_id ASC";
$result = pg_query($conn, $sql);
$documents = [];
if ($result) {
while ($row = pg_fetch_assoc($result)) {
$documents[] = $row;
}
}

// Handle document printing
$selectedDocument = null;
$error = "";
if (isset($_GET['doc_id'])) {
$doc_id = intval($_GET['doc_id']);

$query = "SELECT * FROM documents WHERE doc_id = $1";
$docResult = pg_query_params($conn, $query, [$doc_id]);

if ($docResult && pg_num_rows($docResult) > 0) {
$selectedDocument = pg_fetch_assoc($docResult);
} else {
$error = "Requested document could not be located in university repository.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Academic Documents | ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.page-container {
max-width: 900px;
margin: 36px auto;
padding: 0 16px;
}
.doc-item-link {
display: flex;
align-items: center;
justify-content: space-between;
padding: 16px 20px;
background: #ffffff;
border: 1px solid #e2e8f0;
border-radius: 12px;
margin-bottom: 12px;
text-decoration: none;
color: #0f172a;
font-weight: 600;
transition: all 0.2s ease;
}
.doc-item-link:hover {
border-color: #6366f1;
box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12);
transform: translateY(-2px);
}
</style>
</head>
<body style="background: #f8fafc; min-height: 100vh;">

<div class="page-container">
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
<div>
<a href="dashboard.php" style="color: #6366f1; font-weight: 600; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
<i class="fa-solid fa-arrow-left"></i> Back to Student Portal
</a>
<h1 style="font-size: 1.6rem; font-weight: 700; color: #0f172a; margin: 0;">Academic Documents & Forms</h1>
</div>
<a href="dashboard.php" class="btn btn-secondary">
<i class="fa-solid fa-house"></i> Home
</a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" style="margin-bottom: 20px;">
<i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($selectedDocument): ?>
<div class="card" style="margin-bottom: 24px;">
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
<h2 style="font-size: 1.3rem; margin: 0; color: #0f172a;">
<i class="fa-solid fa-file-contract" style="color: #6366f1; margin-right: 8px;"></i> <?= htmlspecialchars($selectedDocument['doc_name']) ?>
</h2>
<button class="btn btn-primary" onclick="window.print();">
<i class="fa-solid fa-print"></i> Print Document
</button>
</div>
<div style="padding: 16px 0; color: #334155; line-height: 1.8; font-size: 1rem;">
<p><strong>Document Type:</strong> <?= htmlspecialchars($selectedDocument['doc_type'] ?? 'Official Record') ?></p>
<p><strong>Date Issued:</strong> <?= htmlspecialchars($selectedDocument['upload_date'] ?? date('Y-m-d')) ?></p>
<p><strong>Status:</strong> <span class="badge badge-success"><?= htmlspecialchars($selectedDocument['status'] ?? 'Active') ?></span></p>
</div>
</div>
<?php endif; ?>

<div class="card">
<h3 style="font-size: 1.1rem; font-weight: 700; margin-top: 0; margin-bottom: 16px; color: #0f172a;">
<i class="fa-solid fa-folder-tree" style="color: #6366f1; margin-right: 8px;"></i> Available Repository Documents
</h3>

<div>
<a href="degree-print.php" class="doc-item-link">
<div style="display: flex; align-items: center; gap: 14px;">
<div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<div>
<div style="color: #0f172a; font-weight: 700;">Degree Certificate (B.Sc. Computer Science)</div>
<small style="color: #334155; font-weight: 500;">Official University Degree Parchment</small>
</div>
</div>
<i class="fa-solid fa-arrow-up-right-from-square" style="color: #4338ca;"></i>
</a>

<a href="generate-certificate.php" class="doc-item-link">
<div style="display: flex; align-items: center; gap: 14px;">
<div style="width: 40px; height: 40px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
<i class="fa-solid fa-file-certificate"></i>
</div>
<div>
<div style="color: #0f172a; font-weight: 700;">Migration Certificate</div>
<small style="color: #334155; font-weight: 500;">Transfer and migration clearance for higher education</small>
</div>
</div>
<i class="fa-solid fa-arrow-up-right-from-square" style="color: #4338ca;"></i>
</a>

<?php if (!empty($documents)): ?>
<?php foreach ($documents as $doc): ?>
<a href="print-documents.php?doc_id=<?= $doc['doc_id'] ?>" class="doc-item-link">
<div style="display: flex; align-items: center; gap: 14px;">
<div style="width: 40px; height: 40px; border-radius: 10px; background: #fdf2f8; color: #db2777; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
<i class="fa-solid fa-file-lines"></i>
</div>
<div>
<div style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($doc['doc_name']) ?></div>
<small style="color: #334155; font-weight: 500;"><?= htmlspecialchars($doc['doc_type'] ?? 'Academic Form') ?></small>
</div>
</div>
<i class="fa-solid fa-arrow-right" style="color: #4338ca;"></i>
</a>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

</body>
</html>
