<?php
session_start();
include_once __DIR__ . '/backend/config/connection.php';

// Fetch metrics
$totalStudents = 0;
$totalResults = 0;
$totalBranches = 0;
$totalNotices = 0;

$branches = [];
$semesters = [];
$latestNotices = [];

if ($conn) {
$rStudents = @pg_query($conn, "SELECT COUNT(*) FROM student");
if ($rStudents) $totalStudents = (int)pg_fetch_result($rStudents, 0, 0);

$rResults = @pg_query($conn, "SELECT COUNT(DISTINCT roll_no) FROM results");
if ($rResults) $totalResults = (int)pg_fetch_result($rResults, 0, 0);

$rBranches = @pg_query($conn, "SELECT COUNT(*) FROM branch");
if ($rBranches) $totalBranches = (int)pg_fetch_result($rBranches, 0, 0);

$rNotices = @pg_query($conn, "SELECT COUNT(*) FROM notices");
if ($rNotices) $totalNotices = (int)pg_fetch_result($rNotices, 0, 0);

// Fetch branches for quick search
$bRes = @pg_query($conn, "SELECT branch_id, branch_name FROM branch ORDER BY branch_name ASC");
if ($bRes) {
while ($row = pg_fetch_assoc($bRes)) {
$branches[] = $row;
}
}

// Fetch semesters for quick search
$sRes = @pg_query($conn, "SELECT sem_id, semester FROM semester ORDER BY semester ASC");
if ($sRes) {
while ($row = pg_fetch_assoc($sRes)) {
$semesters[] = $row;
}
}

// Fetch latest active notices
$nRes = @pg_query($conn, "SELECT * FROM notices ORDER BY created_at DESC LIMIT 4");
if ($nRes) {
while ($row = pg_fetch_assoc($nRes)) {
$latestNotices[] = $row;
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Examination & Result Management System</title>
<meta name="description" content="Official Online Examination & Result Management Portal. Verify mark sheets, request photocopies, revaluation and official academic certificates online.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="frontend/assets/css/common.css">
<style>
/* ==========================================================================
 Landing Page Premium Styles
 ========================================================================== */
:root {
--hero-bg: #edf2f7;
--hero-accent: #4338ca;
--hero-accent-cyan: #0284c7;
}

/* Top Notification Bar */
.top-ticker {
background: #1E3A5F;
color: #FFFFFF;
font-size: 0.82rem;
padding: 7px 16px;
display: flex;
align-items: center;
justify-content: space-between;
border-bottom: 1px solid #152843;
gap: 12px;
overflow: hidden;
min-height: 36px;
}

.ticker-content {
display: flex;
align-items: center;
gap: 8px;
overflow: hidden;
text-overflow: ellipsis;
white-space: nowrap;
min-width: 0;
flex: 1;
}

.ticker-text {
overflow: hidden;
text-overflow: ellipsis;
white-space: nowrap;
min-width: 0;
font-size: 0.82rem;
color: #E5E7EB;
}

.ticker-badge {
background: #DC2626;
color: #FFFFFF;
font-size: 0.7rem;
font-weight: 700;
padding: 2px 8px;
border-radius: 6px;
text-transform: uppercase;
letter-spacing: 0.5px;
flex-shrink: 0;
display: inline-flex;
align-items: center;
gap: 4px;
}

.ticker-right-link {
white-space: nowrap;
flex-shrink: 0;
display: flex;
align-items: center;
gap: 8px;
}

.ticker-right-link a {
color: #DBEAFE;
font-weight: 700;
text-decoration: none;
font-size: 0.82rem;
display: inline-flex;
align-items: center;
gap: 6px;
transition: color 0.15s ease;
}

.ticker-right-link a:hover {
color: #FFFFFF;
}

/* Public Header */
.home-navbar {
background: rgba(255, 255, 255, 0.95);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
position: sticky;
top: 0;
z-index: 1000;
border-bottom: 1px solid #E5E7EB;
box-shadow: 0 1px 3px rgba(31, 41, 55, 0.05);
}

.home-nav-container {
max-width: 1320px;
margin: 0 auto;
display: flex;
align-items: center;
justify-content: space-between;
padding: 0 clamp(12px, 3vw, 24px);
height: 64px;
gap: 12px;
}

.home-brand {
display: flex;
align-items: center;
gap: 12px;
text-decoration: none;
color: #1F2937;
min-width: 0;
flex-shrink: 1;
}

.home-brand-logo {
width: 40px;
height: 40px;
background: #1E3A5F;
border-radius: 12px;
display: flex;
align-items: center;
justify-content: center;
color: #FFFFFF;
font-size: 1.2rem;
box-shadow: 0 4px 14px rgba(30, 58, 95, 0.25);
flex-shrink: 0;
}

.home-brand-text {
min-width: 0;
display: flex;
flex-direction: column;
}

.home-brand-text h2 {
font-size: clamp(1.1rem, 2.8vw, 1.3rem);
margin: 0;
font-weight: 800;
color: #1E3A5F;
letter-spacing: -0.3px;
line-height: 1.15;
white-space: nowrap;
}

.home-brand-text span {
font-size: 0.72rem;
color: #4B5563;
font-weight: 500;
margin-top: 1px;
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}

.home-nav-links {
display: flex;
align-items: center;
gap: 28px;
list-style: none;
margin: 0;
padding: 0;
}

.home-nav-link {
color: #1F2937;
font-size: 0.94rem;
font-weight: 700;
text-decoration: none;
transition: all 0.2s ease;
display: inline-flex;
align-items: center;
gap: 8px;
padding: 6px 4px;
}

.home-nav-link:hover {
color: #2563EB;
}

.mobile-only-btn {
display: none;
}

.home-nav-actions {
display: flex;
align-items: center;
gap: 12px;
margin-left: 8px;
}

/* Hero Section */
.hero-section {
background: linear-gradient(135deg, #EFF6FF 0%, #F8FAFC 50%, #EEF2F6 100%);
color: #1F2937;
padding: clamp(48px, 8vw, 96px) clamp(16px, 4vw, 32px);
position: relative;
overflow: hidden;
border-bottom: 1px solid #E5E7EB;
}

.hero-section::before {
content: '';
position: absolute;
top: -20%;
right: -10%;
width: 600px;
height: 600px;
background: radial-gradient(circle, rgba(37, 99, 235, 0.08) 0%, rgba(248, 250, 252, 0) 70%);
border-radius: 50%;
pointer-events: none;
}

.hero-section::after {
content: '';
position: absolute;
bottom: -20%;
left: -10%;
width: 500px;
height: 500px;
background: radial-gradient(circle, rgba(30, 58, 95, 0.08) 0%, rgba(248, 250, 252, 0) 70%);
border-radius: 50%;
pointer-events: none;
}

.hero-container {
max-width: 1280px;
margin: 0 auto;
display: grid;
grid-template-columns: 1.15fr 0.85fr;
gap: clamp(32px, 5vw, 64px);
align-items: center;
position: relative;
z-index: 2;
}

.hero-badge {
display: inline-flex;
align-items: center;
gap: 8px;
background: #DBEAFE;
border: 1px solid #BFDBFE;
color: #1E3A5F;
padding: 6px 14px;
border-radius: 20px;
font-size: 0.86rem;
font-weight: 700;
margin-bottom: 20px;
}

.hero-title {
font-size: clamp(2.2rem, 5vw, 3.4rem);
font-weight: 800;
line-height: 1.15;
color: #1E3A5F;
margin-bottom: 18px;
letter-spacing: -0.5px;
}

.hero-title span {
color: #2563EB;
}

.hero-desc {
font-size: clamp(1.02rem, 2vw, 1.18rem);
color: #4B5563;
font-weight: 500;
line-height: 1.65;
margin-bottom: 32px;
max-width: 600px;
}

.hero-cta-group {
display: flex;
align-items: center;
gap: 16px;
flex-wrap: wrap;
margin-bottom: 36px;
}

/* Quick Search Card (Hero) */
.quick-search-box {
background: #FFFFFF;
color: #1F2937;
border-radius: 20px;
padding: clamp(24px, 4vw, 36px);
box-shadow: 0 10px 25px rgba(31, 41, 55, 0.08);
border: 1px solid #E5E7EB;
}

.quick-search-box h3 {
font-size: 1.45rem;
margin: 0 0 6px;
color: #1E3A5F;
font-weight: 800;
}

.quick-search-box p {
font-size: 0.94rem;
color: #4B5563;
font-weight: 500;
margin-bottom: 20px;
}

/* Stats Bar */
.stats-strip {
background: #1E3A5F;
color: #FFFFFF;
border-top: 1px solid #152843;
border-bottom: 1px solid #152843;
padding: 32px 16px;
}

.stats-strip-container {
max-width: 1280px;
margin: 0 auto;
display: grid;
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
gap: 24px;
text-align: center;
}

.stat-item h4 {
font-size: clamp(2rem, 4vw, 2.75rem);
font-weight: 800;
color: #FFFFFF;
margin: 0;
font-family: 'Outfit', sans-serif;
}

.stat-item span {
color: #DBEAFE;
font-size: 0.9rem;
font-weight: 700;
text-transform: uppercase;
letter-spacing: 0.5px;
}

/* Feature Sections */
.section-wrap {
padding: clamp(48px, 6vw, 84px) clamp(16px, 3vw, 24px);
max-width: 1280px;
margin: 0 auto;
}

.section-header {
text-align: center;
max-width: 720px;
margin: 0 auto 48px;
}

.section-tag {
display: inline-block;
color: #2563EB;
font-size: 0.88rem;
font-weight: 800;
text-transform: uppercase;
letter-spacing: 1px;
margin-bottom: 8px;
}

.section-title {
font-size: clamp(1.8rem, 3.5vw, 2.4rem);
font-weight: 800;
color: #1E3A5F;
margin-bottom: 12px;
}

.features-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
gap: 24px;
}

.feature-card {
background: #FFFFFF;
border: 1px solid #E5E7EB;
border-radius: 16px;
padding: 30px 26px;
box-shadow: 0 4px 12px rgba(31, 41, 55, 0.05);
transition: all 0.25s ease;
display: flex;
flex-direction: column;
justify-content: space-between;
}

.feature-card:hover {
transform: translateY(-4px);
box-shadow: 0 12px 20px -3px rgba(31, 41, 55, 0.1);
border-color: #2563EB;
}

.feature-icon {
width: 52px;
height: 52px;
border-radius: 14px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.35rem;
margin-bottom: 20px;
}

.fc-indigo .feature-icon { background: #DBEAFE; color: #1E3A5F; }
.fc-emerald .feature-icon { background: #DCFCE7; color: #16A34A; }
.fc-cyan .feature-icon { background: #EFF6FF; color: #2563EB; }
.fc-amber .feature-icon { background: #FEF3C7; color: #D97706; }

.feature-card h3 {
font-size: 1.3rem;
font-weight: 800;
color: #1E3A5F;
margin-bottom: 8px;
}

.feature-card p {
color: #4B5563;
font-size: 0.95rem;
font-weight: 500;
line-height: 1.6;
margin-bottom: 20px;
}

/* Gateways Section */
.gateway-section {
background: #F1F5F9;
padding: clamp(48px, 6vw, 84px) clamp(16px, 3vw, 24px);
border-top: 1px solid #E5E7EB;
border-bottom: 1px solid #E5E7EB;
}

.gateways-grid {
max-width: 1100px;
margin: 0 auto;
display: grid;
grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
gap: 28px;
}

.gateway-card {
background: #FFFFFF;
border-radius: 20px;
padding: 36px 30px;
border: 1px solid #E5E7EB;
box-shadow: 0 4px 15px rgba(31, 41, 55, 0.06);
display: flex;
flex-direction: column;
justify-content: space-between;
transition: all 0.25s ease;
position: relative;
overflow: hidden;
}

.gateway-card:hover {
transform: translateY(-4px);
box-shadow: 0 14px 25px rgba(31, 41, 55, 0.1);
}

.gateway-card::before {
content: '';
position: absolute;
top: 0;
left: 0;
right: 0;
height: 5px;
}

.gw-student::before { background: linear-gradient(90deg, #16A34A, #15803D); }
.gw-admin::before { background: linear-gradient(90deg, #1E3A5F, #2563EB); }

.gateway-icon {
width: 60px;
height: 60px;
border-radius: 16px;
display: flex;
align-items: center;
justify-content: center;
font-size: 1.6rem;
margin-bottom: 22px;
}

.gw-student .gateway-icon { background: #DCFCE7; color: #16A34A; }
.gw-admin .gateway-icon { background: #DBEAFE; color: #1E3A5F; }

/* Notice Board Section */
.notices-list-card {
background: #FFFFFF;
border-radius: 16px;
border: 1px solid #E5E7EB;
box-shadow: 0 2px 8px rgba(31, 41, 55, 0.05);
overflow: hidden;
}

.notice-row {
padding: 20px 24px;
border-bottom: 1px solid #E5E7EB;
display: flex;
align-items: flex-start;
justify-content: space-between;
gap: 20px;
transition: background 0.15s ease;
}

.notice-row:hover {
background: #F8FAFC;
}

.notice-row:last-child {
border-bottom: none;
}

/* Footer */
.site-footer {
background: #1E3A5F;
color: #FFFFFF;
padding: 64px 20px 24px;
border-top: 1px solid #152843;
}

.footer-container {
max-width: 1280px;
margin: 0 auto;
display: grid;
grid-template-columns: 2fr 1fr 1fr 1.5fr;
gap: 40px;
margin-bottom: 48px;
}

.footer-col h4 {
color: #FFFFFF;
font-size: 1.1rem;
margin-bottom: 18px;
font-weight: 800;
}

.footer-links {
list-style: none;
padding: 0;
margin: 0;
}

.footer-links li {
margin-bottom: 12px;
}

.footer-links a {
color: #DBEAFE;
text-decoration: none;
font-size: 0.92rem;
font-weight: 600;
display: inline-flex;
align-items: center;
transition: color 0.15s;
}

.footer-links a:hover {
color: #FFFFFF;
}

.footer-bottom {
max-width: 1280px;
margin: 0 auto;
padding-top: 24px;
border-top: 1px solid rgba(255, 255, 255, 0.15);
display: flex;
justify-content: space-between;
align-items: center;
flex-wrap: wrap;
gap: 16px;
font-size: 0.9rem;
color: #E5E7EB;
font-weight: 500;
}

.footer-bottom a:hover {
color: #FFFFFF !important;
}

/* Mobile Drawer & Responsiveness */
.mobile-nav-btn {
display: none;
background: #EFF6FF;
border: 1px solid #DBEAFE;
color: #1E3A5F;
font-size: 1.15rem;
cursor: pointer;
width: 42px;
height: 42px;
border-radius: 10px;
align-items: center;
justify-content: center;
transition: all 0.2s ease;
flex-shrink: 0;
margin-left: auto;
}

.mobile-nav-btn:hover, .mobile-nav-btn:active {
background: #DBEAFE;
color: #1E3A5F;
}

.home-drawer-backdrop {
display: none;
position: fixed;
top: 64px;
left: 0;
right: 0;
bottom: 0;
background: rgba(15, 23, 42, 0.45);
backdrop-filter: blur(4px);
-webkit-backdrop-filter: blur(4px);
z-index: 998;
}

@media (max-width: 1024px) {
.hero-container {
grid-template-columns: 1fr;
gap: 36px;
}
.footer-container {
grid-template-columns: 1fr 1fr;
gap: 30px;
}
}

@media (max-width: 900px) {
.mobile-nav-btn {
display: inline-flex;
}
.home-nav-actions {
display: none !important;
}
.home-nav-links {
display: none;
position: fixed;
top: 64px;
left: 0;
right: 0;
background: #FFFFFF;
flex-direction: column;
align-items: stretch;
padding: 16px 20px 24px;
gap: 8px;
border-bottom: 1px solid #E5E7EB;
box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
z-index: 999;
max-height: calc(100vh - 64px);
overflow-y: auto;
-webkit-overflow-scrolling: touch;
}
.home-nav-links.show {
display: flex;
animation: slideDown 0.25s ease-out;
}
.home-nav-links li {
width: 100%;
}
.home-nav-links .mobile-only-btn {
display: block;
}
.home-nav-links .home-nav-link {
display: flex;
align-items: center;
gap: 12px;
padding: 12px 16px;
background: #F8FAFC;
border-radius: 10px;
font-size: 0.95rem;
font-weight: 600;
color: #1F2937;
border: 1px solid #E5E7EB;
}
.home-nav-links .home-nav-link:hover {
background: #EFF6FF;
border-color: #BFDBFE;
color: #2563EB;
}
.home-drawer-backdrop.show {
display: block;
}
}

@keyframes tickerMarquee {
  0% { transform: translateX(0); }
  100% { transform: translateX(-100%); }
}

@media (max-width: 640px) {
  .top-ticker {
    padding: 6px 10px;
    font-size: 0.78rem;
    min-height: 32px;
    gap: 8px;
  }
  .ticker-badge {
    font-size: 0.65rem;
    padding: 2px 7px;
    border-radius: 4px;
    letter-spacing: 0.3px;
    flex-shrink: 0;
  }
  .ticker-content {
    overflow: hidden;
    position: relative;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
  }
  .ticker-text {
    display: inline-block;
    padding-left: 100%;
    animation: tickerMarquee 16s linear infinite;
    white-space: nowrap;
    overflow: visible;
    text-overflow: unset;
    font-size: 0.78rem;
  }
  .ticker-text:hover, .ticker-text:active {
    animation-play-state: paused;
  }
  .ticker-right-link {
    display: none !important;
  }
  .home-nav-container {
    height: 60px;
    padding: 0 12px;
    gap: 8px;
  }
  .home-brand {
    gap: 8px;
    min-width: 0;
    flex: 1;
  }
  .home-brand-logo {
    width: 36px;
    height: 36px;
    font-size: 1.05rem;
    border-radius: 9px;
    flex-shrink: 0;
  }
  .home-brand-text {
    min-width: 0;
    flex: 1;
  }
  .home-brand-text h2 {
    font-size: 1.08rem;
    line-height: 1.15;
  }
  .home-brand-text span {
    font-size: 0.65rem;
    line-height: 1.15;
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    max-width: 100%;
    color: #4B5563;
  }
  .mobile-nav-btn {
    width: 38px;
    height: 38px;
    font-size: 1.05rem;
    flex-shrink: 0;
  }
  .hero-section {
    padding: 28px 14px 36px;
  }
  .hero-title {
    font-size: clamp(1.75rem, 6vw, 2.3rem);
    margin-bottom: 12px;
  }
  .hero-desc {
    font-size: 0.95rem;
    margin-bottom: 22px;
  }
  .hero-cta-group {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    margin-bottom: 24px;
  }
  .hero-cta-group .btn {
    width: 100%;
    justify-content: center;
    padding: 13px 20px;
    font-size: 0.95rem;
  }
  .quick-search-box {
    padding: 18px 14px;
    border-radius: 14px;
  }
  .stats-strip {
    padding: 24px 14px;
  }
  .stats-strip-container {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px 12px;
  }
  .stat-item h4 {
    font-size: 1.85rem;
  }
  .stat-item span {
    font-size: 0.78rem;
  }
  .features-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  .gateways-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .gateway-card {
    padding: 24px 18px;
    border-radius: 16px;
  }
  .notice-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 14px 16px;
  }
  .footer-container {
    grid-template-columns: 1fr;
    gap: 28px;
    margin-bottom: 32px;
  }
  .footer-bottom {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  .footer-bottom div[style*="display: flex"] {
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px !important;
  }
}

@media (max-width: 360px) {
  .stats-strip-container {
    grid-template-columns: 1fr;
  }
  .home-brand-text span {
    font-size: 0.6rem;
    -webkit-line-clamp: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>
</head>
<body style="background: #F8FAFC; color: #1F2937;">

<!-- Top Announcement Bar -->
<div class="top-ticker">
<div class="ticker-content">
<span class="ticker-badge"><i class="fa-solid fa-bell"></i> Notice</span>
<span class="ticker-text">Official Examination Results for Academic Session 2024-2026 are now live and published online.</span>
</div>
<div class="ticker-right-link">
<a href="frontend/pages/auth/find-result.php">
<i class="fa-solid fa-magnifying-glass"></i> Direct Result Search
</a>
</div>
</div>

<!-- Main Navigation Header -->
<header class="home-navbar">
<div class="home-nav-container">
<a href="index.php" class="home-brand">
<div class="home-brand-logo">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<div class="home-brand-text">
<h2>ResultPortal</h2>
<span>Examination & Result Management System</span>
</div>
</a>

<!-- Mobile Hamburger Toggle -->
<button class="mobile-nav-btn" onclick="toggleHomeNav()" aria-label="Toggle Navigation">
<i class="fa-solid fa-bars" id="homeMenuIcon"></i>
</button>

<!-- Navigation Links -->
<ul class="home-nav-links" id="homeNavLinks">
<li><a href="#search" class="home-nav-link" onclick="closeHomeNav()"><i class="fa-solid fa-magnifying-glass"></i> Check Results</a></li>
<li><a href="#services" class="home-nav-link" onclick="closeHomeNav()"><i class="fa-solid fa-list-check"></i> Examination Services</a></li>
<li><a href="#notices" class="home-nav-link" onclick="closeHomeNav()"><i class="fa-solid fa-bullhorn"></i> Notices & Circulars</a></li>
<li><a href="#portals" class="home-nav-link" onclick="closeHomeNav()"><i class="fa-solid fa-shield-halved"></i> Login Portals</a></li>
<li class="mobile-only-btn" style="margin-top: 8px;">
<a href="frontend/pages/auth/index.php" class="btn btn-primary" style="padding: 12px 18px; width: 100%; justify-content: center; font-size: 0.92rem; font-weight: 600; border-radius: 10px; background: #1E3A5F; color: #FFFFFF;">
<i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
</a>
</li>
</ul>

<div class="home-nav-actions">
<a href="frontend/pages/auth/index.php" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.88rem; font-weight: 700; border-radius: 8px; background: #1E3A5F; color: #FFFFFF;">
<i class="fa-solid fa-right-to-bracket"></i> Sign In
</a>
</div>
</div>
</header>

<div class="home-drawer-backdrop" id="homeDrawerBackdrop" onclick="closeHomeNav()"></div>

<!-- Hero Section with Dual Columns -->
<section class="hero-section">
<div class="hero-container">
<!-- Left Hero Content -->
<div>
<div class="hero-badge">
<i class="fa-solid fa-shield-halved"></i> Official Examination Management System
</div>
<h1 class="hero-title">
Empowering Academics with <span>Instant Digital Results</span>
</h1>
<p class="hero-desc">
Access certified university mark sheets, transparent evaluation records, answer book photocopies, and revaluation services directly through our high-speed digital examination portal.
</p>

<div class="hero-cta-group">
<a href="#search" class="btn btn-primary btn-lg" style="background: #1E3A5F; padding: 14px 28px; font-weight: 700; color: #FFFFFF; box-shadow: 0 4px 14px rgba(30, 58, 95, 0.35);">
<i class="fa-solid fa-magnifying-glass"></i> Check My Result
</a>
<a href="frontend/pages/auth/index.php" class="btn btn-secondary btn-lg" style="background: #FFFFFF; color: #1F2937; border: 1.5px solid #E5E7EB; font-weight: 700; padding: 14px 28px; box-shadow: 0 2px 6px rgba(31, 41, 55, 0.06);">
<i class="fa-solid fa-user-graduate" style="color: #2563EB;"></i> Student Portal
</a>
</div>

<!-- Floating Highlights -->
<div style="display: flex; gap: 24px; flex-wrap: wrap; color: #1F2937; font-size: 0.92rem; font-weight: 600;">
<div style="display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-circle-check" style="color: #16A34A; font-size: 1rem;"></i> 100% Encrypted & Verified
</div>
<div style="display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-circle-check" style="color: #16A34A; font-size: 1rem;"></i> SPPU University Affiliated
</div>
<div style="display: flex; align-items: center; gap: 8px;">
<i class="fa-solid fa-circle-check" style="color: #16A34A; font-size: 1rem;"></i> Instant Transcript Generation
</div>
</div>
</div>

<!-- Right Hero Quick Result Search Box -->
<div class="quick-search-box" id="search">
<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
<div style="width: 36px; height: 36px; background: #e0e7ff; color: #4f46e5; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
<i class="fa-solid fa-magnifying-glass"></i>
</div>
<div>
<h3 style="font-size: 1.3rem; margin: 0;">Instant Mark Sheet Search</h3>
</div>
</div>
<p>Select your branch, semester, and input your roll number to view official marks.</p>

<form action="frontend/pages/auth/result.php" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
<div class="form-group" style="margin-bottom: 0;">
<label class="form-label" for="home_branch"><i class="fa-solid fa-code-branch"></i> Academic Stream</label>
<select name="branch_id" id="home_branch" class="form-control" required>
<option value="">-- Choose Branch --</option>
<?php foreach ($branches as $b): ?>
<option value="<?= htmlspecialchars($b['branch_id']) ?>">
<?= htmlspecialchars($b['branch_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group" style="margin-bottom: 0;">
<label class="form-label" for="home_sem"><i class="fa-solid fa-calendar-days"></i> Semester</label>
<select name="sem_id" id="home_sem" class="form-control" required>
<option value="">-- Choose Semester --</option>
<?php foreach ($semesters as $s): ?>
<option value="<?= htmlspecialchars($s['sem_id']) ?>">
Semester <?= htmlspecialchars($s['semester']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group" style="margin-bottom: 0;">
<label class="form-label" for="home_roll"><i class="fa-solid fa-id-card"></i> Student Roll Number</label>
<input type="text" name="stid" id="home_roll" class="form-control" placeholder="Enter Roll Number" required autocomplete="off">
</div>

<button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px; justify-content: center; padding: 12px;">
<i class="fa-solid fa-file-lines"></i> View Examination Mark Sheet
</button>
</form>
</div>
</div>
</section>

<!-- Key Statistics Strip -->
<div class="stats-strip">
<div class="stats-strip-container">
<div class="stat-item">
<h4><?= number_format($totalStudents) ?>+</h4>
<span>Registered Students</span>
</div>
<div class="stat-item">
<h4><?= number_format($totalResults) ?>+</h4>
<span>Declared Results</span>
</div>
<div class="stat-item">
<h4><?= number_format($totalBranches) ?></h4>
<span>Academic Streams</span>
</div>
<div class="stat-item">
<h4>100%</h4>
<span>Digital Verification</span>
</div>
</div>
</div>

<!-- Core Examination Services Section -->
<section class="section-wrap" id="services">
<div class="section-header">
<span class="section-tag">Digital Services</span>
<h2 class="section-title">Comprehensive Examination & Academic Suite</h2>
<p style="color: #64748b; font-size: 1rem;">Complete transparency and automated workflows designed to deliver fast academic credentials to every candidate.</p>
</div>

<div class="features-grid">
<div class="feature-card fc-indigo">
<div>
<div class="feature-icon"><i class="fa-solid fa-file-circle-check"></i></div>
<h3>Certified Digital Marksheets</h3>
<p>Generate authenticated electronic grade sheets with precise course-wise breakdown, SGPA/percentage calculations, and printable security headers.</p>
</div>
<a href="#search" style="font-weight: 700; color: #4f46e5; display: inline-flex; align-items: center; gap: 6px;">
Search Marksheet <i class="fa-solid fa-arrow-right"></i>
</a>
</div>

<div class="feature-card fc-emerald">
<div>
<div class="feature-icon"><i class="fa-solid fa-copy"></i></div>
<h3>Answer Book Photocopy</h3>
<p>Apply online for certified scanned copies of evaluated answer booklets with instant Razorpay transaction acknowledgement and status tracking.</p>
</div>
<a href="frontend/pages/auth/index.php" style="font-weight: 700; color: #059669; display: inline-flex; align-items: center; gap: 6px;">
Apply for Photocopy <i class="fa-solid fa-arrow-right"></i>
</a>
</div>

<div class="feature-card fc-cyan">
<div>
<div class="feature-icon"><i class="fa-solid fa-rotate-right"></i></div>
<h3>Subject Revaluation</h3>
<p>Submit papers for re-assessment and verification by academic subject experts with minimal turnaround time and downloadable digital bill receipts.</p>
</div>
<a href="frontend/pages/auth/index.php" style="font-weight: 700; color: #0891b2; display: inline-flex; align-items: center; gap: 6px;">
Request Revaluation <i class="fa-solid fa-arrow-right"></i>
</a>
</div>

<div class="feature-card fc-amber">
<div>
<div class="feature-icon"><i class="fa-solid fa-certificate"></i></div>
<h3>Official Degree & Migration</h3>
<p>Instant issuance of University Degree Certificates and Migration Clearances tailored for higher education enrollments and employment verifications.</p>
</div>
<a href="frontend/pages/auth/index.php" style="font-weight: 700; color: #d97706; display: inline-flex; align-items: center; gap: 6px;">
Generate Documents <i class="fa-solid fa-arrow-right"></i>
</a>
</div>
</div>
</section>

<!-- Dual Role Login Gateways Section -->
<section class="gateway-section" id="portals">
<div class="section-header">
<span class="section-tag">Access Gateways</span>
<h2 class="section-title">Dedicated Portals for Students & Faculty</h2>
<p style="color: #64748b; font-size: 1rem;">Choose your designated gateway below to sign in to your customized dashboard.</p>
</div>

<div class="gateways-grid">
<!-- Student Portal Gateway -->
<div class="gateway-card gw-student">
<div>
<div class="gateway-icon">
<i class="fa-solid fa-user-graduate"></i>
</div>
<h3 style="font-size: 1.5rem; color: #0f172a; margin-bottom: 8px;">Student Services Portal</h3>
<p style="color: #64748b; line-height: 1.6; margin-bottom: 24px;">
Access your academic profile, view semester performance ledger, submit photocopy/revaluation requests, and download official certificates.
</p>
<ul style="list-style: none; padding: 0; margin-bottom: 28px; color: #334155; font-size: 0.92rem; display: flex; flex-direction: column; gap: 8px;">
<li><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 8px;"></i> View all semester marks & SGPA</li>
<li><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 8px;"></i> Request photocopy & revaluation online</li>
<li><i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 8px;"></i> Access university circulars & notices</li>
</ul>
</div>
<a href="frontend/pages/auth/index.php" class="btn btn-success btn-lg btn-block" style="justify-content: center; padding: 13px;">
<i class="fa-solid fa-right-to-bracket"></i> Open Student Portal
</a>
</div>

<!-- Admin Portal Gateway -->
<div class="gateway-card gw-admin">
<div>
<div class="gateway-icon">
<i class="fa-solid fa-user-shield"></i>
</div>
<h3 style="font-size: 1.5rem; color: #0f172a; margin-bottom: 8px;">Faculty & Admin Gateway</h3>
<p style="color: #64748b; line-height: 1.6; margin-bottom: 24px;">
Centralized administrative management for declaring results, managing branches and semesters, managing student directories, and issuing notices.
</p>
<ul style="list-style: none; padding: 0; margin-bottom: 28px; color: #334155; font-size: 0.92rem; display: flex; flex-direction: column; gap: 8px;">
<li><i class="fa-solid fa-circle-check" style="color: #6366f1; margin-right: 8px;"></i> Declare & publish examination results</li>
<li><i class="fa-solid fa-circle-check" style="color: #6366f1; margin-right: 8px;"></i> Manage student directory & enrollments</li>
<li><i class="fa-solid fa-circle-check" style="color: #6366f1; margin-right: 8px;"></i> Audit photocopy & revaluation queues</li>
</ul>
</div>
<a href="frontend/pages/auth/index.php" class="btn btn-primary btn-lg btn-block" style="justify-content: center; padding: 13px;">
<i class="fa-solid fa-lock"></i> Open Admin Gateway
</a>
</div>
</div>
</section>

<!-- Latest Notices Section -->
<section class="section-wrap" id="notices">
<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
<div>
<span class="section-tag">Campus Circulars</span>
<h2 class="section-title" style="margin-bottom: 4px;">Latest Notices & Announcements</h2>
<p style="color: #64748b; margin: 0;">Stay up to date with official examination notifications and academic schedules.</p>
</div>
<a href="frontend/pages/student/view-notices.php" class="btn btn-secondary">
<i class="fa-solid fa-list-check"></i> View Notice Archives
</a>
</div>

<div class="notices-list-card">
<?php if (!empty($latestNotices)): ?>
<?php foreach ($latestNotices as $notice): ?>
<div class="notice-row">
<div>
<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
<span class="badge badge-primary" style="font-size: 0.72rem;">Circular</span>
<h4 style="font-size: 1.1rem; color: #0f172a; margin: 0; font-weight: 700;">
<?= htmlspecialchars($notice['title']) ?>
</h4>
</div>
<p style="color: #64748b; font-size: 0.92rem; margin: 0; line-height: 1.6;">
<?= nl2br(htmlspecialchars($notice['description'])) ?>
</p>
</div>
<div style="white-space: nowrap; color: #94a3b8; font-size: 0.82rem; font-weight: 600;">
<i class="fa-regular fa-clock"></i> <?= htmlspecialchars(substr($notice['created_at'] ?? date('Y-m-d'), 0, 10)) ?>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div style="padding: 40px; text-align: center; color: #64748b;">
<i class="fa-solid fa-bullhorn" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
No circulars published at the moment. Please check back later.
</div>
<?php endif; ?>
</div>
</section>

<!-- Footer -->
<footer class="site-footer">
<div class="footer-container">
<div class="footer-col">
<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
<div style="width: 38px; height: 38px; background: #2563EB; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #FFFFFF; font-size: 1.15rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);">
<i class="fa-solid fa-graduation-cap"></i>
</div>
<h3 style="color: #FFFFFF; margin: 0; font-size: 1.35rem; font-weight: 800; font-family: 'Outfit', sans-serif;">ResultPortal</h3>
</div>
<p style="color: #E2E8F0; font-size: 0.95rem; line-height: 1.65; font-weight: 500;">
Online Examination & Result Management System. Delivering digital examination services with academic excellence, high security, and absolute transparency.
</p>
</div>

<div class="footer-col">
<h4>Examination Services</h4>
<ul class="footer-links">
<li><a href="#search"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Online Marksheet</a></li>
<li><a href="frontend/pages/auth/index.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Answer Book Photocopy</a></li>
<li><a href="frontend/pages/auth/index.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Paper Revaluation</a></li>
<li><a href="frontend/pages/auth/index.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Degree & Migration</a></li>
</ul>
</div>

<div class="footer-col">
<h4>Quick Portals</h4>
<ul class="footer-links">
<li><a href="frontend/pages/auth/index.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Student Login</a></li>
<li><a href="frontend/pages/auth/index.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Admin Sign In</a></li>
<li><a href="frontend/pages/auth/student-registration.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Student Signup</a></li>
<li><a href="frontend/pages/auth/find-result.php"><i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; margin-right: 8px; color: #60A5FA;"></i> Search Verification</a></li>
</ul>
</div>

<div class="footer-col">
<h4>Contact & Location</h4>
<p style="font-size: 0.95rem; color: #E2E8F0; line-height: 1.65; margin-bottom: 12px; font-weight: 500;">
<i class="fa-solid fa-location-dot" style="color: #60A5FA; margin-right: 8px; font-size: 1rem;"></i> Central Examination Cell, University Campus
</p>
<p style="font-size: 0.95rem; color: #E2E8F0; line-height: 1.65; font-weight: 500;">
<i class="fa-solid fa-envelope" style="color: #60A5FA; margin-right: 8px; font-size: 1rem;"></i> support@examination-portal.edu
</p>
</div>
</div>

<div class="footer-bottom">
<div style="color: #E2E8F0;">
&copy; <?= date("Y") ?> Online Examination & Result Management System. All Rights Reserved.
</div>
<div style="display: flex; gap: 20px;">
<a href="#" style="color: #DBEAFE; font-weight: 600; text-decoration: none; transition: color 0.15s;">Examination Ordinances</a>
<a href="#" style="color: #DBEAFE; font-weight: 600; text-decoration: none; transition: color 0.15s;">Privacy Policy</a>
<a href="#" style="color: #DBEAFE; font-weight: 600; text-decoration: none; transition: color 0.15s;">Terms of Service</a>
</div>
</div>
</footer>

<script>
function toggleHomeNav() {
const nav = document.getElementById('homeNavLinks');
const icon = document.getElementById('homeMenuIcon');
const backdrop = document.getElementById('homeDrawerBackdrop');

nav.classList.toggle('show');
backdrop.classList.toggle('show');

if (nav.classList.contains('show')) {
icon.classList.remove('fa-bars');
icon.classList.add('fa-xmark');
} else {
icon.classList.remove('fa-xmark');
icon.classList.add('fa-bars');
}
}

function closeHomeNav() {
const nav = document.getElementById('homeNavLinks');
const icon = document.getElementById('homeMenuIcon');
const backdrop = document.getElementById('homeDrawerBackdrop');

nav.classList.remove('show');
backdrop.classList.remove('show');
icon.classList.remove('fa-xmark');
icon.classList.add('fa-bars');
}

// Auto-unregister any leftover/rogue service workers on localhost:8000
if ('serviceWorker' in navigator) {
navigator.serviceWorker.getRegistrations().then(function(registrations) {
for (let registration of registrations) {
registration.unregister();
}
});
}
</script>
</body>
</html>
