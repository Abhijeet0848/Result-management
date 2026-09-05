<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
header("location: ../auth/index.php");
exit;
}

include_once __DIR__ . '/../../../backend/config/connection.php';

$notices = [];
if ($conn) {
$sql = "SELECT notice_id, title, description, created_at FROM notices ORDER BY notice_id DESC";
$result = pg_query($conn, $sql);
if ($result) {
$notices = pg_fetch_all($result) ?: [];
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Notices & Announcements - Student Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.notice-card {
background: var(--bg-card);
border: 1px solid var(--border-color);
border-left: 5px solid var(--primary);
border-radius: var(--radius-md);
padding: 22px;
margin-bottom: 20px;
box-shadow: var(--shadow-sm);
transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.notice-card:hover {
transform: translateY(-2px);
box-shadow: var(--shadow-md);
}

.notice-title {
font-size: 1.25rem;
font-weight: 700;
color: var(--text-main);
margin-bottom: 8px;
display: flex;
align-items: center;
gap: 10px;
}

.notice-body {
color: var(--text-muted);
font-size: 0.95rem;
line-height: 1.7;
white-space: pre-line;
}

.notice-footer {
margin-top: 14px;
padding-top: 10px;
border-top: 1px dashed var(--border-color);
display: flex;
justify-content: space-between;
align-items: center;
font-size: 0.82rem;
color: var(--text-light);
}
</style>
</head>
<body>

<header style="background: #1E3A5F; padding: 16px 24px; color: #FFFFFF; border-bottom: 1px solid #E5E7EB; box-shadow: 0 2px 10px rgba(30,58,95,0.15); display: flex; justify-content: space-between; align-items: center;">
<a href="s_login.php" style="color: #FFFFFF; text-decoration: none; font-weight: 700; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-graduation-cap" style="color: #2563EB;"></i> Student Portal
</a>
<a href="s_login.php" class="btn btn-secondary btn-sm" style="background: #FFFFFF; color: #1E3A5F; border: 1px solid #E5E7EB;">
<i class="fa-solid fa-arrow-left"></i> Back to Dashboard
</a>
</header>

<div class="container" style="max-width: 900px;">
<div class="page-header">
<div>
<h1 class="page-title"><i class="fa-solid fa-bullhorn" style="color: var(--warning); margin-right: 8px;"></i> College Circulars & Notices</h1>
<p>Stay up to date with the latest examination timetables, holiday announcements, and academic circulars.</p>
</div>
</div>

<?php if (!empty($notices)): ?>
<?php foreach ($notices as $n): ?>
<div class="notice-card">
<div class="notice-title">
<i class="fa-solid fa-bell" style="color: var(--primary); font-size: 1rem;"></i>
<?= htmlspecialchars($n['title']) ?>
</div>
<div class="notice-body">
<?= nl2br(htmlspecialchars($n['description'])) ?>
</div>
<div class="notice-footer">
<span><i class="fa-solid fa-shield"></i> Official Notice ID: #<?= htmlspecialchars($n['notice_id']) ?></span>
<span><i class="fa-regular fa-clock"></i> Published Notice</span>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
<i class="fa-solid fa-inbox" style="font-size: 3rem; color: var(--border-color); margin-bottom: 12px; display: block;"></i>
<h3>No Notices Available</h3>
<p>There are no active public circulars posted at this time. Check back later!</p>
</div>
<?php endif; ?>
</div>
</body>
</html>
