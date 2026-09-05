-- =========================================================
-- PostgreSQL Schema & Seed Data for Online Result System
-- Compatible with Render.com, Supabase, Neon, Railway, and Localhost
-- =========================================================

DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS notices CASCADE;
DROP TABLE IF EXISTS documents CASCADE;
DROP TABLE IF EXISTS photocopy_requests CASCADE;
DROP TABLE IF EXISTS revaluation_requests CASCADE;
DROP TABLE IF EXISTS results CASCADE;
DROP TABLE IF EXISTS mother CASCADE;
DROP TABLE IF EXISTS student CASCADE;
DROP TABLE IF EXISTS subject_comb CASCADE;
DROP TABLE IF EXISTS subjects CASCADE;
DROP TABLE IF EXISTS semester CASCADE;
DROP TABLE IF EXISTS branch CASCADE;
DROP TABLE IF EXISTS faculty CASCADE;
DROP TABLE IF EXISTS admin CASCADE;

-- 1. Admin Table
CREATE TABLE admin (
    admin_id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- 2. Branch Table
CREATE TABLE branch (
    branch_id SERIAL PRIMARY KEY,
    branch_name VARCHAR(150) NOT NULL
);

-- 3. Semester Table
CREATE TABLE semester (
    sem_id SERIAL PRIMARY KEY,
    semester INT NOT NULL
);

-- 4. Subjects Table
CREATE TABLE subjects (
    subj_id SERIAL PRIMARY KEY,
    subj_name VARCHAR(150) NOT NULL,
    subj_code VARCHAR(50) UNIQUE NOT NULL,
    status INT DEFAULT 1,
    credits NUMERIC(4,1) DEFAULT 4.0
);

-- 5. Subject Combinations Table
CREATE TABLE subject_comb (
    id SERIAL PRIMARY KEY,
    branch_id INT REFERENCES branch(branch_id) ON DELETE CASCADE,
    sem_id INT REFERENCES semester(sem_id) ON DELETE CASCADE,
    subj_id INT REFERENCES subjects(subj_id) ON DELETE CASCADE,
    status INT DEFAULT 1
);

-- 6. Student Table
CREATE TABLE student (
    reg_id SERIAL,
    name VARCHAR(150) NOT NULL,
    roll_no VARCHAR(50) PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    gender VARCHAR(20),
    dob DATE,
    branch_id INT REFERENCES branch(branch_id) ON DELETE SET NULL,
    sem_id INT REFERENCES semester(sem_id) ON DELETE SET NULL,
    status INT DEFAULT 1,
    subj_id INT REFERENCES subjects(subj_id) ON DELETE SET NULL
);

-- 7. Mother Table
CREATE TABLE mother (
    id SERIAL PRIMARY KEY,
    student_roll_no VARCHAR(50) REFERENCES student(roll_no) ON DELETE CASCADE,
    mother_name VARCHAR(150) NOT NULL,
    mother_contact VARCHAR(20)
);

-- 8. Results Table
CREATE TABLE results (
    result_id SERIAL PRIMARY KEY,
    roll_no VARCHAR(50) REFERENCES student(roll_no) ON DELETE CASCADE,
    branch_id INT REFERENCES branch(branch_id) ON DELETE SET NULL,
    sem_id INT REFERENCES semester(sem_id) ON DELETE SET NULL,
    subj_id INT REFERENCES subjects(subj_id) ON DELETE CASCADE,
    marks INT NOT NULL
);

-- 9. Faculty Table
CREATE TABLE faculty (
    faculty_id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    branch_id INT REFERENCES branch(branch_id) ON DELETE SET NULL,
    department VARCHAR(100),
    contact_no VARCHAR(20),
    status INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Photocopy Requests Table
CREATE TABLE photocopy_requests (
    request_id SERIAL PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    subjects VARCHAR(255) NOT NULL,
    paymentid VARCHAR(100) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. Revaluation Requests Table
CREATE TABLE revaluation_requests (
    request_id SERIAL PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    subjects VARCHAR(255) NOT NULL,
    payment_id VARCHAR(100) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 12. Notices Table
CREATE TABLE notices (
    notice_id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 13. Documents Table
CREATE TABLE documents (
    doc_id SERIAL PRIMARY KEY,
    doc_name VARCHAR(255) NOT NULL,
    doc_type VARCHAR(50),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Active'
);

-- 14. Audit Logs Table
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100),
    user_role VARCHAR(50),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- Seed Data
-- =========================================================
INSERT INTO admin (admin_id, username, password) VALUES ('17', 'Abhijeet', '$2y$10$COgWWBAdbvCmYhotVg3k5.omK/1HotXNng0.QKzzUCfXjGPOu1RfK');
INSERT INTO branch (branch_id, branch_name) VALUES ('1', 'Computer Science');
INSERT INTO semester (sem_id, semester) VALUES ('2', '2');
INSERT INTO semester (sem_id, semester) VALUES ('1', '1');
INSERT INTO semester (sem_id, semester) VALUES ('3', '3');
INSERT INTO semester (sem_id, semester) VALUES ('4', '4');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('113', 'Physics', 'PHY101', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('114', 'Chemistry', 'CHE101', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('115', 'Data Structures', 'DS101', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('116', 'Algorithms', 'ALG101', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11121', 'CS-111 Problems Solving Using Computer and "C" Programming', '11121', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11122', 'CS-112 Database Management Systems', '11122', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11123', 'CS-113 Practical Course Based on CS111 and CS112', '11123', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11221', 'MTC-111 Matrix Algebra', '11221', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11222', 'MTC-112 Discrete Mathematics', '11222', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11223', 'MTC-113 Mathematics Practical', '11223', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11321', 'ELC-111 Semiconductor Devices and Basic Electronic Systems', '11321', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11322', 'ELC-112 Principles of Digital Electronics', '11322', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11323', 'ELC-113 Electronics Lab IA', '11323', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11421', 'CSST-111 Descriptive Statistics I', '11421', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11422', 'CSST-112 Mathematical Statistics', '11422', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('11423', 'CSST-113 Statistics Practical Paper I', '11423', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12121', 'CS-121 Advanced C Programming', 'CS-121', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12122', 'CS-122 Relational Database Management Systems', 'CS-122', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12123', 'CS-123 Practical Course Based on CS121 and CS122', 'CS-123', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12221', 'MTC-121 Linear Algebra', 'MTC-121', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12222', 'MTC-122 Graph Theory', 'MTC-122', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12223', 'MTC-123 Mathematics Practical', 'MTC-123', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12321', 'ELC-121 Instrumentation System', 'ELC-121', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12322', 'ELC-122 Basics of Computer Organisation', 'ELC-122', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12323', 'ELC-123 Electronics Lab IB', 'ELC-123', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12421', 'CSST-121 Methods of Applied Statistics', 'CSST-121', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12422', 'CSST-122 Continuous Probability Distributions', 'CSST-122', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12423', 'CSST-123 Statistics Practical Paper II', 'CSST-123', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('12999', 'Democracy, Election, and Governance', '12999', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('1', 'Physical Education', '1', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23121', 'CS 231 Data Structures and Algorithms - I', 'CS-231', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23122', 'CS 232 Software Engineering', 'CS-232', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23123', 'CS 233 Practical Course on CS 231 and CS 232', 'CS-233', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23221', 'MTC 231 Groups and Coding Theory', 'MTC-231', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23222', 'MTC-232 Numerical Techniques', 'MTC-232', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23223', 'MTC-233 Mathematics Practical: Python Programming Language I', 'MTC-233', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23321', 'ELC-231 Microcontroller Architecture & Programming', 'ELC-231', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23322', 'ELC-232 Digital Communication and Networking', 'ELC-232', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23323', 'ELC-233 Practical Course I', 'ELC-233', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23921', 'AECC-I Environmental Awareness', 'AECC-I', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('23922', 'AECC-II Language Communication - I', 'AECC-II', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24121', 'CS 241 Data Structures and Algorithms - II', 'CS-241', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24122', 'CS 242 Computer Networks - I', 'CS-242', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24123', 'CS 243 Practical Course on CS 241 and CS 242', 'CS-243', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24221', 'MTC 241 Computational Geometry', 'MTC-241', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24222', 'MTC-242 Operations Research', 'MTC-242', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24223', 'MTC-243 Mathematics Practical: Python Programming Language II', 'MTC-243', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24321', 'ELC-241 Embedded System Design', 'ELC-241', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24322', 'ELC-242 Wireless Communication & Internet of Things', 'ELC-242', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24323', 'ELC-243 Practical Course II', 'ELC-243', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24921', 'AECC-I Environmental Awareness - II', 'AECC-I', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('24922', 'AECC-II Language Communication - II', 'AECC-II', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('111', 'Mathematics', 'MATH101', '1', '4.0');
INSERT INTO subjects (subj_id, subj_name, subj_code, status, credits) VALUES ('112', 'Computer Science Fundamentals', 'CSF101', '1', '4.0');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('3', '1', '1', '113', '1');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('4', '1', '2', '114', '1');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('5', '1', '2', '115', '1');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('6', '1', '2', '116', '1');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('1', '1', '1', '111', '0');
INSERT INTO subject_comb (id, branch_id, sem_id, subj_id, status) VALUES ('2', '1', '1', '112', '0');
INSERT INTO student (reg_id, name, roll_no, email, password, gender, dob, branch_id, sem_id, status, subj_id) VALUES ('110', 'Sujeet', '1102208473', 'sujeet@gmail.com', 'sujeet', 'Male', '2003-06-05', '1', '1', '1', '11121');
INSERT INTO student (reg_id, name, roll_no, email, password, gender, dob, branch_id, sem_id, status, subj_id) VALUES ('1', 'Gautam Abhijeetkumar', '1102208472', 'gautam@example.com', 'password123', 'Male', '2004-05-12', '1', '1', '1', '111');
INSERT INTO mother (id, student_roll_no, mother_name, mother_contact) VALUES ('1', '1102208472', 'Chanda', '1234567890');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('1', '1102208472', '1', '1', '11121', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('2', '1102208472', '1', '1', '11122', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('3', '1102208472', '1', '1', '11123', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('4', '1102208472', '1', '1', '11221', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('5', '1102208472', '1', '1', '11222', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('6', '1102208472', '1', '1', '11223', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('7', '1102208472', '1', '1', '11321', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('8', '1102208472', '1', '1', '11322', '80');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('9', '1102208472', '1', '1', '11323', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('10', '1102208472', '1', '1', '11421', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('11', '1102208472', '1', '1', '11422', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('12', '1102208472', '1', '1', '11423', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('13', '1102208472', '1', '2', '12121', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('14', '1102208472', '1', '2', '12122', '80');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('15', '1102208472', '1', '2', '12123', '85');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('16', '1102208472', '1', '2', '12221', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('17', '1102208472', '1', '2', '12222', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('18', '1102208472', '1', '2', '12223', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('19', '1102208472', '1', '2', '12321', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('20', '1102208472', '1', '2', '12322', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('21', '1102208472', '1', '2', '12323', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('22', '1102208472', '1', '2', '12421', '95');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('23', '1102208472', '1', '2', '12422', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('24', '1102208472', '1', '2', '12423', '85');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('25', '1102208472', '1', '2', '12999', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('26', '1102208472', '1', '2', '1', '85');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('27', '1102208472', '1', '3', '23121', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('28', '1102208472', '1', '3', '23122', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('29', '1102208472', '1', '3', '23123', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('30', '1102208472', '1', '3', '23221', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('31', '1102208472', '1', '3', '23222', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('32', '1102208472', '1', '3', '23223', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('33', '1102208472', '1', '3', '23321', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('34', '1102208472', '1', '3', '23322', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('35', '1102208472', '1', '3', '23323', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('36', '1102208472', '1', '3', '23921', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('37', '1102208472', '1', '3', '23922', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('38', '1102208472', '1', '4', '24121', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('39', '1102208472', '1', '4', '24122', '80');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('40', '1102208472', '1', '4', '24123', '85');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('41', '1102208472', '1', '4', '24221', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('42', '1102208472', '1', '4', '24222', '95');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('43', '1102208472', '1', '4', '24223', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('44', '1102208472', '1', '4', '24321', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('45', '1102208472', '1', '4', '24322', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('46', '1102208472', '1', '4', '24323', '100');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('47', '1102208472', '1', '4', '24921', '90');
INSERT INTO results (result_id, roll_no, branch_id, sem_id, subj_id, marks) VALUES ('48', '1102208472', '1', '4', '24922', '90');
INSERT INTO photocopy_requests (request_id, email, subjects, paymentid, request_date) VALUES ('1', 'gautam@example.com', 'CS-111, CS-112, CS-113', 'PAY12345', '2025-04-25 11:36:18.180647');
INSERT INTO photocopy_requests (request_id, email, subjects, paymentid, request_date) VALUES ('17', 'gautam@example.com', 'ELC-243 Practical Course II', 'pay_QNDPqBsL2HxRRU', '2025-04-25 13:25:25.288684');
INSERT INTO photocopy_requests (request_id, email, subjects, paymentid, request_date) VALUES ('18', 'gautam@example.com', 'MTC 241 Computational Geometry, Physical Education', 'PAY_6A9C133B10BC7', '2026-09-05 18:33:59.735731');
INSERT INTO photocopy_requests (request_id, email, subjects, paymentid, request_date) VALUES ('19', 'gautam@example.com', 'Physical Education', 'PAY_THOZRU7YDQK', '2026-09-05 18:47:20.727175');
INSERT INTO revaluation_requests (request_id, email, subjects, payment_id, request_date) VALUES ('1', 'gautam@example.com', 'CS-111, CS-112', 'PAY12345', '2025-04-25 11:36:01.956854');
INSERT INTO notices (notice_id, title, description, created_at) VALUES ('1', 'Result Announcement', 'First Year B.Sc. (Computer Science) results for April 2023 have been announced.', '2025-04-25 11:34:30.474213');
INSERT INTO documents (doc_id, doc_name, doc_type, upload_date, status) VALUES ('1', 'Bonafide Certificate', 'Certificate', '2025-04-25 13:31:08.927334', 'Active');
INSERT INTO documents (doc_id, doc_name, doc_type, upload_date, status) VALUES ('2', 'Fee Receipt', 'Receipt', '2025-04-25 13:31:08.927334', 'Active');
INSERT INTO documents (doc_id, doc_name, doc_type, upload_date, status) VALUES ('3', 'Admission Form', 'Form', '2025-04-25 13:31:08.927334', 'Active');
INSERT INTO documents (doc_id, doc_name, doc_type, upload_date, status) VALUES ('4', 'Exam Hall Ticket', 'Hall Ticket', '2025-04-25 13:31:08.927334', 'Active');
INSERT INTO documents (doc_id, doc_name, doc_type, upload_date, status) VALUES ('5', 'Result Statement', 'Academic Document', '2025-04-25 13:31:08.927334', 'Active');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('2', 'Abhijeet', 'Guest', 'ACTIVATE_SUBJECT_COMB', 'Activated subject combination ID 1.', '::1', '2026-09-05 18:14:22.176945');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('3', 'Abhijeet', 'Guest', 'DEACTIVATE_SUBJECT_COMB', 'Deactivated subject combination ID 1.', '::1', '2026-09-05 18:14:22.338057');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('4', 'Abhijeet', 'Guest', 'TOGGLE_SUBJECT_STATUS', 'Toggled status for Subject ID 111.', '::1', '2026-09-05 18:14:22.557309');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('5', 'Abhijeet', 'Guest', 'TOGGLE_SUBJECT_STATUS', 'Toggled status for Subject ID 111.', '::1', '2026-09-05 18:14:22.707846');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('6', 'Abhijeet', 'Guest', 'DEACTIVATE_STUDENT', 'Deactivated student account ID: 1.', '::1', '2026-09-05 18:14:22.866207');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('7', 'Abhijeet', 'Guest', 'APPROVE_STUDENT', 'Approved and activated student account ID: 1.', '::1', '2026-09-05 18:14:23.172247');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('8', 'Abhijeet', 'Guest', 'ACTIVATE_SUBJECT_COMB', 'Activated subject combination ID 2.', '::1', '2026-09-05 18:15:11.783866');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('9', 'Abhijeet', 'Guest', 'DEACTIVATE_SUBJECT_COMB', 'Deactivated subject combination ID 2.', '::1', '2026-09-05 18:15:11.936356');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('10', 'Abhijeet', 'Guest', 'TOGGLE_SUBJECT_STATUS', 'Toggled status for Subject ID 112.', '::1', '2026-09-05 18:15:12.087781');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('11', 'Abhijeet', 'Guest', 'TOGGLE_SUBJECT_STATUS', 'Toggled status for Subject ID 112.', '::1', '2026-09-05 18:15:12.232639');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('12', 'Abhijeet', 'Guest', 'DEACTIVATE_STUDENT', 'Deactivated student account ID: 110.', '::1', '2026-09-05 18:15:12.396085');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('13', 'Abhijeet', 'Guest', 'APPROVE_STUDENT', 'Approved and activated student account ID: 110.', '::1', '2026-09-05 18:15:12.699499');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('14', 'Abhijeet', 'Guest', 'DEACTIVATE_STUDENT', 'Deactivated student account: Gautam Abhijeetkumar (Roll: 1102208472, ID: 1).', '::1', '2026-09-05 18:23:07.926003');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('15', 'Abhijeet', 'Guest', 'APPROVE_STUDENT', 'Approved and activated student account: Gautam Abhijeetkumar (Roll: 1102208472, ID: 1).', '::1', '2026-09-05 18:23:08.084386');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('16', 'Abhijeet', 'Guest', 'DELETE_STUDENT', 'Deleted student ''Test Delete Student'' (Roll: 9999999999, ID: 113) and associated results.', '::1', '2026-09-05 18:23:08.238668');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('17', 'Abhijeet', 'Guest', 'PUBLISH_NOTICE', 'Published public notice: Test Notice Circular.', '::1', '2026-09-05 18:25:44.934367');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('18', 'Abhijeet', 'Guest', 'PUBLISH_NOTICE', 'Published public notice: Notice To Delete.', '::1', '2026-09-05 18:29:10.941348');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('19', 'Abhijeet', 'Guest', 'DELETE_NOTICE', 'Deleted circular notice ID: 17.', '::1', '2026-09-05 18:29:11.202563');
INSERT INTO audit_logs (id, username, user_role, action, details, ip_address, created_at) VALUES ('20', 'Abhijeet', 'Guest', 'DELETE_NOTICE', 'Deleted circular notice ID: 18.', '::1', '2026-09-05 18:30:38.641087');

-- =========================================================
-- Reset and Synchronize PostgreSQL Auto-Increment Sequences
-- (Prevents "duplicate key violates unique constraint" errors in Neon)
-- =========================================================
SELECT setval('admin_admin_id_seq', (SELECT COALESCE(MAX(admin_id), 1) FROM admin));
SELECT setval('branch_branch_id_seq', (SELECT COALESCE(MAX(branch_id), 1) FROM branch));
SELECT setval('semester_sem_id_seq', (SELECT COALESCE(MAX(sem_id), 1) FROM semester));
SELECT setval('subjects_subj_id_seq', (SELECT COALESCE(MAX(subj_id), 1) FROM subjects));
SELECT setval('subject_comb_id_seq', (SELECT COALESCE(MAX(id), 1) FROM subject_comb));
SELECT setval('student_reg_id_seq', (SELECT COALESCE(MAX(reg_id), 1) FROM student));
SELECT setval('mother_id_seq', (SELECT COALESCE(MAX(id), 1) FROM mother));
SELECT setval('results_result_id_seq', (SELECT COALESCE(MAX(result_id), 1) FROM results));
SELECT setval('photocopy_requests_request_id_seq', (SELECT COALESCE(MAX(request_id), 1) FROM photocopy_requests));
SELECT setval('revaluation_requests_request_id_seq', (SELECT COALESCE(MAX(request_id), 1) FROM revaluation_requests));
SELECT setval('notices_notice_id_seq', (SELECT COALESCE(MAX(notice_id), 1) FROM notices));
SELECT setval('documents_doc_id_seq', (SELECT COALESCE(MAX(doc_id), 1) FROM documents));
SELECT setval('audit_logs_id_seq', (SELECT COALESCE(MAX(id), 1) FROM audit_logs));
