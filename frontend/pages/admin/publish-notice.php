<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

require_auth('admin');

$showAlert = '';
$showError = '';

// Handle Delete Notice (supports ?del=ID, ?id=ID, ?action=delete&id=ID, or POST del_id)
$delId = intval($_GET['del'] ?? ($_GET['id'] ?? ($_POST['del_id'] ?? 0)));
if ($delId > 0 && $conn) {
    $delSql = "DELETE FROM notices WHERE notice_id = $1";
    $delRes = pg_query_params($conn, $delSql, array($delId));
    if ($delRes) {
        logAudit($conn, "DELETE_NOTICE", "Deleted circular notice ID: $delId.");
        $showAlert = "Notice deleted successfully!";
        
        // If Ajax request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Notice deleted successfully!"]);
            exit;
        }
    } else {
        $showError = "Failed to delete notice: " . pg_last_error($conn);
    }
}

// Handle form submission for publishing a notice
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['title'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['content'] ?? '');

    if (!empty($title) && !empty($description) && $conn) {
        $sql = "INSERT INTO notices (title, description) VALUES ($1, $2)";
        $result = pg_query_params($conn, $sql, array($title, $description));

        if ($result) {
            logAudit($conn, "PUBLISH_NOTICE", "Published public notice: $title.");
            $showAlert = "Public notice published successfully!";
        } else {
            $showError = "Failed to publish notice: " . pg_last_error($conn);
        }
    } else {
        $showError = "Please enter both notice title and announcement details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notice Board Management - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.notice-layout-grid {
display: grid;
grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
gap: 24px;
align-items: start;
}
.notice-layout-grid > div {
min-width: 0;
max-width: 100%;
}
@media (max-width: 860px) {
.notice-layout-grid {
grid-template-columns: minmax(0, 1fr);
gap: 20px;
}
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container" style="max-width: 1200px; min-width: 0;">
<div class="page-header">
<div>
<h1 class="page-title">Notice Board Management</h1>
<p>Broadcast public circulars, semester schedules, and administrative notifications to students.</p>
</div>
</div>

<?php if ($showAlert): ?>
<div class="alert alert-success">
<i class="fa-solid fa-circle-check"></i>
<div><?= htmlspecialchars($showAlert) ?></div>
</div>
<?php endif; ?>

<?php if ($showError): ?>
<div class="alert alert-danger">
<i class="fa-solid fa-circle-exclamation"></i>
<div><?= htmlspecialchars($showError) ?></div>
</div>
<?php endif; ?>

<div class="notice-layout-grid">
<!-- Notice Post Form -->
<div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: clamp(16px, 3vw, 24px); min-width: 0;">
<h3 style="margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> Publish New Notice
</h3>
                <form action="publish-notice.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">

                    <div class="form-group">
                        <label for="title">Notice Title / Subject *</label>
                        <input type="text" id="title" name="title" required placeholder="Enter Notice Title">
                    </div>

<div class="form-group">
<label for="content">Notice Announcement Details</label>
<textarea id="content" name="content" rows="6" required placeholder="Enter the complete notification body here..."></textarea>
</div>

<button type="submit" class="btn btn-primary btn-block">
<i class="fa-solid fa-paper-plane"></i> Publish Circular
</button>
</form>
</div>

<!-- Existing Notices List -->
<div>
<h3 style="margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-list" style="color: var(--secondary);"></i> Active Circulars
</h3>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 35%;">Title</th>
                                <th style="width: 50%;">Announcement</th>
                                <th style="width: 15%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($conn) {
                            $res = pg_query($conn, "SELECT notice_id, title, description FROM notices ORDER BY notice_id DESC");
                            if ($res && pg_num_rows($res) > 0) {
                                while ($n = pg_fetch_assoc($res)) {
                                    ?>
                                    <tr id="notice-row-<?= $n['notice_id'] ?>">
                                        <td><strong><?= htmlspecialchars($n['title']) ?></strong></td>
                                        <td><div style="max-height: 80px; overflow-y: auto; font-size: 0.88rem; color: var(--text-muted);"><?= nl2br(htmlspecialchars($n['description'])) ?></div></td>
                                        <td style="text-align: center;">
                                            <a href="publish-notice.php?del=<?= $n['notice_id'] ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Notice" onclick="return confirm('Permanently delete this circular notice?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="3" style="text-align:center; padding: 25px; color: var(--text-muted);">No circulars posted yet.</td></tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
