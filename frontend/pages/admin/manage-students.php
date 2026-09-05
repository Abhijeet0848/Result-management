<?php
include_once __DIR__ . '/../../../backend/config/session.php';
include_once __DIR__ . '/../../../backend/config/connection.php';
include_once __DIR__ . '/../../../backend/helpers/audit.php';

require_auth('admin');

$flashMessage = '';
$flashType = 'success';

// Handle Administrator Actions (Approve, Deactivate, Activate, Delete)
if (isset($_GET['action']) && $conn) {
    $action = strtolower(trim($_GET['action']));
    $targetId = trim($_GET['id'] ?? ($_GET['roll_no'] ?? ''));

    if (!empty($targetId)) {
        // Find student record first
        $sQuery = pg_query_params($conn, "SELECT reg_id, roll_no, name, status FROM student WHERE reg_id::text = $1 OR roll_no = $1 LIMIT 1", array($targetId));
        if ($sQuery && pg_num_rows($sQuery) > 0) {
            $sData = pg_fetch_assoc($sQuery);
            $reg_id = intval($sData['reg_id']);
            $rNo = $sData['roll_no'];
            $sName = $sData['name'];

            if ($action === 'approve' || $action === 'activate') {
                $res = pg_query_params($conn, "UPDATE student SET status = 1 WHERE reg_id = $1", array($reg_id));
                if ($res) {
                    logAudit($conn, "APPROVE_STUDENT", "Approved and activated student account: $sName (Roll: $rNo, ID: $reg_id).");
                    $flashMessage = "Student account for '$sName' (Roll: $rNo) approved and activated successfully!";
                    $flashType = "success";
                } else {
                    $flashMessage = "Error activating student: " . pg_last_error($conn);
                    $flashType = "danger";
                }
            } elseif ($action === 'deactivate') {
                $res = pg_query_params($conn, "UPDATE student SET status = 0 WHERE reg_id = $1", array($reg_id));
                if ($res) {
                    logAudit($conn, "DEACTIVATE_STUDENT", "Deactivated student account: $sName (Roll: $rNo, ID: $reg_id).");
                    $flashMessage = "Student account for '$sName' (Roll: $rNo) deactivated / moved to pending status.";
                    $flashType = "warning";
                } else {
                    $flashMessage = "Error deactivating student: " . pg_last_error($conn);
                    $flashType = "danger";
                }
            } elseif ($action === 'delete') {
                // Delete child foreign key references first
                if (!empty($rNo)) {
                    pg_query_params($conn, "DELETE FROM results WHERE roll_no = $1", array($rNo));
                    pg_query_params($conn, "DELETE FROM mother WHERE student_roll_no = $1", array($rNo));
                }
                $res = pg_query_params($conn, "DELETE FROM student WHERE reg_id = $1", array($reg_id));

                if ($res) {
                    logAudit($conn, "DELETE_STUDENT", "Deleted student '$sName' (Roll: $rNo, ID: $reg_id) and associated results.");
                    $flashMessage = "Student '$sName' (Roll: $rNo) permanently deleted.";
                    $flashType = "danger";
                } else {
                    $flashMessage = "Error deleting student: " . pg_last_error($conn);
                    $flashType = "danger";
                }
            }
        } else {
            $flashMessage = "Student record not found (ID: $targetId).";
            $flashType = "danger";
        }
    }
}

// Handle Export Student Directory to CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && $conn) {
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Student_Directory_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Reg_ID', 'Roll_No', 'Name', 'Email', 'Gender', 'DOB', 'Branch', 'Semester', 'Mother_Name', 'Status']);

$exportSql = "SELECT s.reg_id, s.roll_no, s.name, s.email, s.gender, s.dob, b.branch_name, sm.semester, m.mother_name, s.status 
FROM student s 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
LEFT JOIN mother m ON s.roll_no = m.student_roll_no 
ORDER BY s.roll_no ASC";
$expRes = pg_query($conn, $exportSql);
if ($expRes) {
while ($r = pg_fetch_assoc($expRes)) {
$statusText = (intval($r['status']) === 1) ? 'Active' : 'Pending Approval';
fputcsv($output, [
$r['reg_id'],
$r['roll_no'],
$r['name'],
$r['email'],
$r['gender'],
$r['dob'],
$r['branch_name'] ?? 'Unassigned',
$r['semester'] ?? 'Unassigned',
$r['mother_name'] ?? '',
$statusText
]);
}
}
fclose($output);
exit;
}

// Fetch counts for summary badges
$totalCount = 0;
$pendingCount = 0;
$activeCount = 0;

if ($conn) {
$countRes = pg_query($conn, "SELECT status, COUNT(*) as cnt FROM student GROUP BY status");
if ($countRes) {
while ($cRow = pg_fetch_assoc($countRes)) {
$st = intval($cRow['status']);
$num = intval($cRow['cnt']);
$totalCount += $num;
if ($st === 1) $activeCount += $num;
else $pendingCount += $num;
}
}
}
$initialFilter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all';
if (!in_array($initialFilter, ['all', 'pending', 'active'])) {
    $initialFilter = 'all';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Directory & Account Approvals - ResultPortal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">
<style>
.filter-tab-bar {
display: flex;
gap: 10px;
margin-bottom: 20px;
flex-wrap: wrap;
}

.filter-tab {
padding: 8px 18px;
border-radius: 8px;
font-size: 0.9rem;
font-weight: 700;
color: #4B5563;
background: #FFFFFF;
border: 1.5px solid #E5E7EB;
cursor: pointer;
text-decoration: none;
display: inline-flex;
align-items: center;
gap: 8px;
transition: all 0.2s ease;
}

.filter-tab:hover {
border-color: #2563EB;
color: #1E3A5F;
}

.filter-tab.active {
background: #1E3A5F;
color: #FFFFFF;
border-color: #1E3A5F;
}

.filter-count {
background: rgba(0, 0, 0, 0.08);
padding: 2px 7px;
border-radius: 12px;
font-size: 0.78rem;
}

.filter-tab.active .filter-count {
background: rgba(255, 255, 255, 0.25);
color: #FFFFFF;
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../components/nav.php'; ?>

<div class="container">
<div class="page-header">
<div>
<h1 class="page-title">Student Directory & Approvals</h1>
<p>Manage enrolled students, review self-registration requests, and approve accounts.</p>
</div>
        <div class="header-actions">
            <a href="manage-students.php?action=export_csv" class="btn btn-secondary">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <a href="bulk-students-upload.php" class="btn btn-secondary">
                <i class="fa-solid fa-cloud-arrow-up"></i> Bulk Import
            </a>
            <a href="add-student.php" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Enroll Student
            </a>
        </div>
    </div>

<?php if (!empty($flashMessage)): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="margin-bottom: 20px;">
<i class="fa-solid <?= ($flashType === 'success') ? 'fa-circle-check' : (($flashType === 'warning') ? 'fa-triangle-exclamation' : 'fa-circle-exclamation') ?>"></i>
<div><?= htmlspecialchars($flashMessage) ?></div>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="filter-tab-bar">
<button type="button" class="filter-tab <?= ($initialFilter === 'all') ? 'active' : '' ?>" id="tabAll" onclick="setFilter('all', this)">
<i class="fa-solid fa-users"></i> All Students <span class="filter-count"><?= $totalCount ?></span>
</button>
<button type="button" class="filter-tab <?= ($initialFilter === 'pending') ? 'active' : '' ?>" id="tabPending" onclick="setFilter('pending', this)" style="<?= ($pendingCount > 0) ? 'border-color: #F59E0B; color: #D97706;' : '' ?>">
<i class="fa-solid fa-clock"></i> Pending Approval <span class="filter-count" style="<?= ($pendingCount > 0) ? 'background: #FEF3C7; color: #B45309; font-weight: 800;' : '' ?>"><?= $pendingCount ?></span>
</button>
<button type="button" class="filter-tab <?= ($initialFilter === 'active') ? 'active' : '' ?>" id="tabActive" onclick="setFilter('active', this)">
<i class="fa-solid fa-circle-check"></i> Active Accounts <span class="filter-count"><?= $activeCount ?></span>
</button>
</div>

<!-- Toolbar with Quick Search -->
<div class="table-toolbar">
<div class="search-input-wrapper">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="studentSearch" placeholder="Search by name, roll no, branch..." onkeyup="filterStudents()">
</div>
</div>

<div class="table-responsive">
<table id="studentsTable">
<thead>
<tr>
<th style="width: 5%;">Sr. No.</th>
<th>Student Name</th>
<th>Roll No</th>
<th>Branch</th>
<th>Semester</th>
<th>Account Status</th>
<th style="text-align: center; width: 180px;">Actions</th>
</tr>
</thead>
<tbody>
<?php
if ($conn) {
$sql = "SELECT s.name, s.roll_no, s.email, s.status, s.reg_id, 
 b.branch_name, sm.semester 
FROM student s 
LEFT JOIN branch b ON s.branch_id = b.branch_id 
LEFT JOIN semester sm ON s.sem_id = sm.sem_id 
ORDER BY (s.status = 0) DESC, s.roll_no ASC";
$result = pg_query($conn, $sql);
$cnt = 1;

if ($result && pg_num_rows($result) > 0) {
while ($row = pg_fetch_assoc($result)) {
$isPending = (intval($row['status']) === 0);
$statusBadge = $isPending 
? '<span class="badge badge-warning" style="background: #FEF3C7; color: #B45309;"><i class="fa-solid fa-clock"></i> Pending Approval</span>' 
: '<span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>';
?>
<tr data-status="<?= $isPending ? 'pending' : 'active' ?>">
<td style="font-weight: 700; color: #4B5563;"><?php echo $cnt; ?></td>
<td>
<div style="font-weight: 700; color: #1F2937;"><?php echo htmlspecialchars($row['name']); ?></div>
<div style="font-size: 0.8rem; color: #6B7280;"><?php echo htmlspecialchars($row['email']); ?></div>
</td>
<td><code style="color: #2563EB; font-weight: 700; background: #EFF6FF; padding: 3px 8px; border-radius: 6px; border: 1px solid #DBEAFE;"><?php echo htmlspecialchars($row['roll_no']); ?></code></td>
<td style="color: #1F2937; font-weight: 500;"><?php echo htmlspecialchars($row['branch_name'] ?? 'Not Assigned'); ?></td>
<td style="color: #1F2937; font-weight: 500;"><?php echo !empty($row['semester']) ? 'Semester ' . htmlspecialchars($row['semester']) : 'Not Assigned'; ?></td>
<td><?php echo $statusBadge; ?></td>
<td style="text-align: center;">
<div style="display: inline-flex; align-items: center; gap: 6px;">
<?php if ($isPending): ?>
<a href="manage-students.php?action=approve&id=<?= urlencode($row['reg_id']) ?>" class="btn btn-sm btn-success" style="padding: 5px 10px; font-size: 0.8rem;" title="Approve & Activate Account">
<i class="fa-solid fa-user-check"></i> Approve
</a>
<?php else: ?>
<a href="manage-students.php?action=deactivate&id=<?= urlencode($row['reg_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Deactivate Account" onclick="return confirm('Deactivate student account?');">
<i class="fa-solid fa-user-xmark"></i>
</a>
<?php endif; ?>

<a href="edit-student.php?stid=<?php echo urlencode($row['reg_id']); ?>" class="btn-action" title="Edit Student Profile">
<i class="fa-solid fa-pen-to-square"></i>
</a>

<a href="manage-students.php?action=delete&id=<?= urlencode($row['reg_id']) ?>" class="btn-action" style="color: #DC2626; border-color: #FEE2E2; background: #FEF2F2;" title="Delete Student" onclick="return confirm('Are you sure you want to permanently delete this student record?');">
<i class="fa-solid fa-trash"></i>
</a>
</div>
</td>
</tr>
<?php
$cnt++;
}
} else {
echo '<tr><td colspan="7" style="text-align: center; color: #4B5563; font-weight: 600; padding: 32px;">No student records found.</td></tr>';
}
}
?>
</tbody>
</table>
</div>
</div>

<script>
let currentStatusFilter = '<?= $initialFilter ?>';

function setFilter(status, btn) {
    currentStatusFilter = status;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    if (btn) {
        btn.classList.add('active');
    }
    filterStudents();
}

function filterStudents() {
    const input = document.getElementById("studentSearch");
    const searchFilter = input.value.toLowerCase();
    const table = document.getElementById("studentsTable");
    const trs = table.getElementsByTagName("tr");

    for (let i = 1; i < trs.length; i++) {
        const row = trs[i];
        const rowStatus = row.getAttribute('data-status');
        const text = row.textContent || row.innerText;
        const matchesSearch = text.toLowerCase().indexOf(searchFilter) > -1;
        const matchesStatus = (currentStatusFilter === 'all' || rowStatus === currentStatusFilter);

        if (matchesSearch && matchesStatus) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (currentStatusFilter !== 'all') {
        filterStudents();
    }
});
</script>
</body>
</html>
