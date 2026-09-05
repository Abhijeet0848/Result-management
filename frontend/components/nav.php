<?php
// Enhanced Responsive Navigation Component
$current_page = basename($_SERVER['PHP_SELF']);
$admin_user = $_SESSION['username'] ?? 'Admin';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/common.css">

<style>
/* ==========================================================================
 Responsive Master Navbar
 ========================================================================== */
.app-navbar {
background: #1E3A5F;
color: #FFFFFF;
position: sticky;
top: 0;
z-index: 1000;
box-shadow: 0 2px 8px rgba(30, 58, 95, 0.15);
border-bottom: 1px solid #152843;
width: 100%;
}

.nav-container {
max-width: 1440px;
margin: 0 auto;
display: flex;
align-items: center;
justify-content: space-between;
padding: 0 clamp(12px, 2vw, 24px);
height: 62px;
}

/* Brand */
.nav-brand {
display: flex;
align-items: center;
gap: 10px;
text-decoration: none;
color: #FFFFFF;
font-family: 'Outfit', sans-serif;
font-weight: 700;
font-size: clamp(1.05rem, 2vw, 1.25rem);
white-space: nowrap;
flex-shrink: 0;
}

.brand-icon {
width: 36px;
height: 36px;
background: #2563EB;
border-radius: 9px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1rem;
color: #FFFFFF;
box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
}

/* Desktop Menu List */
.nav-menu {
display: flex;
align-items: center;
gap: 4px;
list-style: none;
margin: 0;
padding: 0;
}

.nav-item {
position: relative;
}

.nav-link {
display: inline-flex;
align-items: center;
gap: 6px;
color: #E5E7EB;
font-weight: 700;
font-size: 0.92rem;
padding: 7px 12px;
border-radius: 8px;
text-decoration: none;
transition: all 0.15s ease;
cursor: pointer;
background: transparent;
border: none;
line-height: 1.2;
white-space: nowrap;
}

.nav-link:hover, .nav-item:hover > .nav-link, .nav-item.open > .nav-link {
color: #FFFFFF;
background: rgba(255, 255, 255, 0.12);
}

.nav-link.active {
color: #FFFFFF !important;
background: #2563EB !important;
box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
}

.nav-link i.chevron {
font-size: 0.7rem;
opacity: 0.85;
margin-left: 2px;
transition: transform 0.2s ease;
}

.nav-item:hover .nav-link i.chevron, .nav-item.open .nav-link i.chevron {
transform: rotate(180deg);
}

/* Dropdown Menus */
.nav-dropdown {
position: absolute;
top: calc(100% + 6px);
left: 0;
min-width: 220px;
background: #FFFFFF;
border: 1px solid #E5E7EB;
border-radius: 12px;
box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
padding: 6px;
opacity: 0;
visibility: hidden;
transform: translateY(8px);
transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
z-index: 1001;
}

@media (min-width: 1151px) {
.nav-item:hover .nav-dropdown {
opacity: 1;
visibility: visible;
transform: translateY(0);
}
}

.dropdown-item {
display: flex;
align-items: center;
gap: 10px;
padding: 10px 14px;
color: #1F2937;
font-size: 0.9rem;
font-weight: 600;
text-decoration: none;
border-radius: 7px;
transition: all 0.15s ease;
}

.dropdown-item i {
font-size: 0.92rem;
width: 18px;
color: #4B5563;
text-align: center;
}

.dropdown-item:hover {
color: #2563EB;
background: #EFF6FF;
}

.dropdown-item:hover i {
color: #2563EB;
}

/* Right User Profile Dropdown */
.nav-actions {
display: flex;
align-items: center;
gap: 10px;
flex-shrink: 0;
}

.user-profile-menu {
position: relative;
}

.user-btn {
display: flex;
align-items: center;
gap: 8px;
background: rgba(255, 255, 255, 0.12);
border: 1px solid rgba(255, 255, 255, 0.2);
padding: 6px 14px;
border-radius: 20px;
color: #FFFFFF;
font-size: 0.88rem;
font-weight: 700;
cursor: pointer;
transition: all 0.2s ease;
}

.user-btn:hover, .user-profile-menu.open .user-btn {
background: #2563EB;
border-color: #2563EB;
color: #FFFFFF;
}

.user-btn i.avatar-icon {
color: #16A34A;
font-size: 0.95rem;
}

.user-dropdown {
right: 0;
left: auto;
min-width: 200px;
background: #FFFFFF;
border: 1px solid #E5E7EB;
}

.user-profile-menu.open .user-dropdown {
opacity: 1;
visibility: visible;
transform: translateY(0);
}

.dropdown-divider {
height: 1px;
background: #E5E7EB;
margin: 4px 0;
}

.dropdown-item.danger-item {
color: #DC2626;
}

.dropdown-item.danger-item:hover {
background: #FEE2E2;
color: #B91C1C;
}

.dropdown-item.danger-item:hover i {
color: #B91C1C;
}

/* Mobile Toggle & Backdrop */
.mobile-toggle {
display: none;
background: transparent;
border: none;
color: #FFFFFF;
font-size: 1.35rem;
cursor: pointer;
padding: 8px;
border-radius: 8px;
transition: background 0.2s;
}

.mobile-toggle:hover {
background: rgba(255, 255, 255, 0.15);
}

.nav-backdrop {
display: none;
position: fixed;
top: 62px;
left: 0;
right: 0;
bottom: 0;
background: rgba(15, 23, 42, 0.5);
backdrop-filter: blur(4px);
z-index: 998;
}

.nav-backdrop.show {
display: block;
}

/* ==========================================================================
 Responsive Breakpoints (Desktop, Tablet, Mobile)
 ========================================================================== */
@media (max-width: 1150px) {
.mobile-toggle {
display: block;
}

.nav-menu {
display: none;
position: fixed;
top: 62px;
left: 0;
right: 0;
background: #FFFFFF;
flex-direction: column;
align-items: stretch;
padding: 16px 20px 30px;
border-bottom: 1px solid #E5E7EB;
box-shadow: 0 20px 35px rgba(0, 0, 0, 0.12);
max-height: calc(100vh - 62px);
overflow-y: auto;
z-index: 999;
-webkit-overflow-scrolling: touch;
}

.nav-menu.show {
display: flex;
animation: slideDown 0.25s ease-out;
}

.nav-link {
width: 100%;
justify-content: space-between;
padding: 12px 16px;
font-size: 0.95rem;
border-radius: 8px;
color: #1F2937;
}

.nav-link:hover {
background: #EFF6FF;
color: #2563EB;
}

.nav-dropdown {
position: static;
opacity: 1;
visibility: visible;
transform: none;
box-shadow: none;
background: #f8fafc;
margin: 4px 0 10px 12px;
display: none;
border-left: 3px solid #4f46e5;
}

.nav-item.open .nav-dropdown {
display: block;
animation: fadeIn 0.2s ease-out;
}
}

@media (max-width: 480px) {
.nav-brand span {
display: inline-block;
}
.user-btn span {
display: none;
}
.user-btn {
padding: 6px 8px;
}
}
</style>

<header class="app-navbar">
<div class="nav-container">
<!-- Logo Brand -->
<a href="/frontend/pages/admin/dashboard.php" class="nav-brand">
<div class="brand-icon">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<span>ResultPortal</span>
</a>

<!-- Mobile Hamburger Toggle -->
<button class="mobile-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Navigation">
<i class="fa-solid fa-bars" id="menuIcon"></i>
</button>

<!-- Navigation Links Menu -->
<ul class="nav-menu" id="navMenu">
<li class="nav-item">
<a href="/frontend/pages/admin/dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
<span><i class="fa-solid fa-gauge-high"></i> Dashboard</span>
</a>
</li>

<!-- Students Dropdown -->
<li class="nav-item" onclick="toggleDropdown(this, event)">
<button class="nav-link <?= in_array($current_page, ['add-student.php', 'manage-students.php', 'edit-student.php', 'bulk-students-upload.php']) ? 'active' : '' ?>">
<span><i class="fa-solid fa-user-graduate"></i> Students</span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown">
<a href="/frontend/pages/admin/add-student.php" class="dropdown-item">
<i class="fa-solid fa-user-plus"></i> Add Student
</a>
<a href="/frontend/pages/admin/bulk-students-upload.php" class="dropdown-item">
<i class="fa-solid fa-cloud-arrow-up"></i> Bulk Student Import
</a>
<a href="/frontend/pages/admin/manage-students.php" class="dropdown-item">
<i class="fa-solid fa-users-gear"></i> Student Directory
</a>
</div>
</li>

<!-- Faculty Dropdown -->
<li class="nav-item">
<a href="/frontend/pages/admin/manage-faculty.php" class="nav-link <?= ($current_page == 'manage-faculty.php') ? 'active' : '' ?>">
<span><i class="fa-solid fa-chalkboard-user"></i> Faculty</span>
</a>
</li>

<!-- Academic Structure (Branches & Semesters) -->
<li class="nav-item" onclick="toggleDropdown(this, event)">
<button class="nav-link <?= in_array($current_page, ['add-branch.php', 'manage-branch.php', 'edit-branch.php', 'add-semester.php', 'manage-sem.php', 'edit-semester.php']) ? 'active' : '' ?>">
<span><i class="fa-solid fa-sitemap"></i> Academic</span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown">
<a href="/frontend/pages/admin/manage-branch.php" class="dropdown-item">
<i class="fa-solid fa-code-branch"></i> Branches
</a>
<a href="/frontend/pages/admin/manage-sem.php" class="dropdown-item">
<i class="fa-solid fa-calendar-days"></i> Semesters
</a>
<div class="dropdown-divider"></div>
<a href="/frontend/pages/admin/add-branch.php" class="dropdown-item">
<i class="fa-solid fa-plus-circle"></i> Add Branch
</a>
<a href="/frontend/pages/admin/add-semester.php" class="dropdown-item">
<i class="fa-solid fa-calendar-plus"></i> Add Semester
</a>
</div>
</li>

<!-- Subjects & Combinations -->
<li class="nav-item" onclick="toggleDropdown(this, event)">
<button class="nav-link <?= in_array($current_page, ['add-subjects.php', 'manage-subjects.php', 'edit-subjects.php', 'add-subjcombo.php', 'manage-subjcomb.php']) ? 'active' : '' ?>">
<span><i class="fa-solid fa-book-open"></i> Subjects</span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown">
<a href="/frontend/pages/admin/manage-subjects.php" class="dropdown-item">
<i class="fa-solid fa-book-bookmark"></i> Subjects Directory
</a>
<a href="/frontend/pages/admin/manage-subjcomb.php" class="dropdown-item">
<i class="fa-solid fa-sliders"></i> Subject Combos
</a>
<div class="dropdown-divider"></div>
<a href="/frontend/pages/admin/add-subjects.php" class="dropdown-item">
<i class="fa-solid fa-book-medical"></i> Add Subject
</a>
<a href="/frontend/pages/admin/add-subjcombo.php" class="dropdown-item">
<i class="fa-solid fa-layer-group"></i> Add Combination
</a>
</div>
</li>

<!-- Results Dropdown -->
<li class="nav-item" onclick="toggleDropdown(this, event)">
<button class="nav-link <?= in_array($current_page, ['add-results.php', 'manage-results.php', 'edit-result.php', 'bulk-marks-upload.php', 'export-reports.php']) ? 'active' : '' ?>">
<span><i class="fa-solid fa-square-poll-vertical"></i> Results</span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown">
<a href="/frontend/pages/admin/add-results.php" class="dropdown-item">
<i class="fa-solid fa-file-circle-plus"></i> Declare Result
</a>
<a href="/frontend/pages/admin/bulk-marks-upload.php" class="dropdown-item">
<i class="fa-solid fa-file-csv"></i> Bulk CSV Mark Entry
</a>
<a href="/frontend/pages/admin/manage-results.php" class="dropdown-item">
<i class="fa-solid fa-chart-column"></i> Results Ledger
</a>
<a href="/frontend/pages/admin/export-reports.php" class="dropdown-item">
<i class="fa-solid fa-chart-pie"></i> Reports & Analytics
</a>
</div>
</li>

<!-- Applications (Photocopy & Reval) -->
<li class="nav-item" onclick="toggleDropdown(this, event)">
<button class="nav-link <?= in_array($current_page, ['manage-photocopy.php', 'manage-revalution.php']) ? 'active' : '' ?>">
<span><i class="fa-solid fa-envelope-open-text"></i> Requests</span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown">
<a href="/frontend/pages/admin/manage-photocopy.php" class="dropdown-item">
<i class="fa-solid fa-copy"></i> Photocopy Queue
</a>
<a href="/frontend/pages/admin/manage-revalution.php" class="dropdown-item">
<i class="fa-solid fa-rotate-right"></i> Revaluation Queue
</a>
</div>
</li>

<!-- Notice Board -->
<li class="nav-item">
<a href="/frontend/pages/admin/publice_notice.php" class="nav-link <?= ($current_page == 'publice_notice.php') ? 'active' : '' ?>">
<span><i class="fa-solid fa-bullhorn"></i> Notice</span>
</a>
</li>
</ul>

<!-- Right User Actions & Dropdown -->
<div class="nav-actions">
<div class="nav-item user-profile-menu" id="userProfileMenu">
<button class="user-btn" onclick="toggleUserDropdown(event)">
<i class="fa-solid fa-circle-user avatar-icon"></i>
<span><?= htmlspecialchars($admin_user) ?></span>
<i class="fa-solid fa-chevron-down chevron"></i>
</button>
<div class="nav-dropdown user-dropdown">
<a href="/frontend/pages/admin/audit-logs.php" class="dropdown-item">
<i class="fa-solid fa-shield-halved"></i> Audit Trails
</a>
<a href="/frontend/pages/auth/change-password.php" class="dropdown-item">
<i class="fa-solid fa-key"></i> Security Settings
</a>
<a href="/frontend/pages/admin/register_admin.php" class="dropdown-item">
<i class="fa-solid fa-user-shield"></i> Add Administrator
</a>
<div class="dropdown-divider"></div>
<a href="/backend/auth/logout.php" class="dropdown-item danger-item">
<i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
</a>
</div>
</div>
</div>
</div>
</header>

<div class="nav-backdrop" id="navBackdrop" onclick="closeMobileMenu()"></div>

<script>
function toggleMobileMenu() {
const nav = document.getElementById('navMenu');
const icon = document.getElementById('menuIcon');
const backdrop = document.getElementById('navBackdrop');

nav.classList.toggle('show');
backdrop.classList.toggle('show');

if (nav.classList.contains('show')) {
icon.classList.remove('fa-bars');
icon.classList.add('fa-xmark');
document.body.style.overflow = 'hidden';
} else {
icon.classList.remove('fa-xmark');
icon.classList.add('fa-bars');
document.body.style.overflow = '';
}
}

function closeMobileMenu() {
const nav = document.getElementById('navMenu');
const icon = document.getElementById('menuIcon');
const backdrop = document.getElementById('navBackdrop');

nav.classList.remove('show');
backdrop.classList.remove('show');
icon.classList.remove('fa-xmark');
icon.classList.add('fa-bars');
document.body.style.overflow = '';
}

function toggleDropdown(el, e) {
if (window.innerWidth <= 1150) {
// Close other open dropdowns
document.querySelectorAll('.nav-item.open').forEach(item => {
if (item !== el) item.classList.remove('open');
});
el.classList.toggle('open');
}
}

function toggleUserDropdown(e) {
e.stopPropagation();
const menu = document.getElementById('userProfileMenu');
menu.classList.toggle('open');
}

// Close user dropdown when clicking outside
document.addEventListener('click', function(e) {
const userMenu = document.getElementById('userProfileMenu');
if (userMenu && !userMenu.contains(e.target)) {
userMenu.classList.remove('open');
}
});

// Auto-unregister any leftover/rogue service workers on localhost:8000
if ('serviceWorker' in navigator) {
navigator.serviceWorker.getRegistrations().then(function(registrations) {
for (let registration of registrations) {
registration.unregister();
}
});
}
</script>
