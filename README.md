# Online Result Management System

Online Result Management System is a PHP-based web application that streamlines the management of student examination results. The project is built with HTML, CSS, JavaScript, AJAX, PHP, and PostgreSQL and features secure authentication, student and subject management, result processing, responsive design, and dynamic content loading for improved performance.

---

## 📁 Project Directory Structure

```
Online-result-system/
├── backend/ # Backend server logic, APIs, and configurations
│ ├── api/ # Dynamic AJAX data endpoints
│ │ ├── create_order.php # Payment & order creation endpoint
│ │ ├── fetch_students.php # Dynamic student list fetcher
│ │ ├── get_marks.php# Subject marks fetcher
│ │ ├── get_std.php# Student dropdown populator
│ │ └── get_student.php# Student & subject details handler
│ ├── auth/# Authentication action handlers
│ │ └── logout.php # Session logout handler
│ └── config/# Database configuration
│ └── connection.php # PostgreSQL database connection parameters
│
├── frontend/# User interface, views, and static assets
│ ├── assets/# Static asset files
│ │ ├── css/ # Stylesheets (common.css)
│ │ ├── js/# JavaScript libraries (jQuery, Modernizr)
│ │ └── images/# Images and branding assets
│ ├── components/# Shared UI components
│ │ └── nav.php# Navigation bar header
│ └── pages/ # User-facing view pages
│ ├── admin/ # Admin management portal & CRUD views
│ │ ├── dashboard.php
│ │ ├── add-branch.php / edit-branch.php / manage-branch.php
│ │ ├── add-semester.php / edit-semester.php / manage-sem.php
│ │ ├── add-student.php / edit-student.php / manage-students.php
│ │ ├── add-subjects.php / edit-subjects.php / manage-subjects.php
│ │ ├── add-subjcombo.php / manage-subjcomb.php
│ │ ├── add-results.php / edit-result.php / manage-results.php
│ │ ├── manage-photocopy.php / manage-revalution.php
│ │ ├── publice_notice.php
│ │ └── register_admin.php
│ ├── student/ # Student portal & services
│ │ ├── s_login.php (Student Dashboard)
│ │ ├── updateprofile.php
│ │ ├── view_notices.php
│ │ ├── request-photocopy.php
│ │ ├── request-revalution.php
│ │ ├── payment.php / payment1.php / payement_success.php
│ │ ├── download_bill.php
│ │ ├── generate_certificate.php
│ │ ├── degree_print.php
│ │ └── print_documents.php
│ └── auth/# Login, registration, & password recovery
│ ├── index.php (Main Login Portal)
│ ├── student_registration.php
│ ├── change-password.php
│ ├── student-forget-password.php
│ ├── admin_forgot_password.php
│ ├── find-result.php
│ └── result.php
│
├── database/# Database schemas and seed data
│ └── final2.sql # PostgreSQL database schema & tables
│
├── index.php# Root entry point router
└── README.md
```

---

## 🚀 Getting Started

1. **Database Setup**: Import `database/final2.sql` into your PostgreSQL database.
2. **Configuration**: Configure credentials in `backend/config/connection.php`.
3. **Run Application**: Serve via Apache / PHP built-in server and navigate to `http://localhost/Online-result-system/`.

---

## 📸 Screenshots

<img width="1914" height="870" alt="image" src="https://github.com/user-attachments/assets/9b1ab53b-dfa2-43ed-999e-496b0c55e4f1" />
<img width="1902" height="811" alt="image" src="https://github.com/user-attachments/assets/75ead08e-cb15-4a08-b75d-4b66e7ecd8b9" />
<img width="1919" height="859" alt="image" src="https://github.com/user-attachments/assets/3280255a-74fe-4a1e-a14c-ec476aa78b2c" />
<img width="1919" height="869" alt="image" src="https://github.com/user-attachments/assets/4c72c294-cc4b-4144-b55a-8e75b8e670d6" />
<img width="1919" height="869" alt="image" src="https://github.com/user-attachments/assets/c3328958-7964-40b7-9ce7-940f0276d794" />
<img width="1919" height="861" alt="image" src="https://github.com/user-attachments/assets/31e2e9c8-79ff-4fe6-9051-96949c6cd474" />
<img width="1910" height="865" alt="image" src="https://github.com/user-attachments/assets/5102a3b8-704d-4290-abd2-dfdb725246b9" />
<img width="1911" height="871" alt="image" src="https://github.com/user-attachments/assets/7671300d-4187-4464-a8fb-63aa6a68244d" />
<img width="1912" height="862" alt="image" src="https://github.com/user-attachments/assets/c36f9546-6209-4668-b1d0-59741b42889a" />
<img width="1918" height="867" alt="image" src="https://github.com/user-attachments/assets/6d2db96f-11ed-4ecc-b6a7-cecc2ac23d5e" />
<img width="1919" height="865" alt="image" src="https://github.com/user-attachments/assets/2800d930-ddf4-4c23-a661-49cd931b0c43" />
<img width="1878" height="909" alt="image" src="https://github.com/user-attachments/assets/26b90a9e-823a-4e21-892c-6e4e2c27c592" />
<img width="1919" height="864" alt="image" src="https://github.com/user-attachments/assets/4b8a166d-c1cb-430b-a0a8-071828f026be" />
<img width="1914" height="857" alt="image" src="https://github.com/user-attachments/assets/c166af84-e464-4b06-b0d3-eaad337e81dc" />
<img width="1916" height="875" alt="image" src="https://github.com/user-attachments/assets/282994ba-904f-4991-859d-b3ca05c1c862" />
