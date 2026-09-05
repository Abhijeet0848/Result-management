--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

-- Started on 2025-04-25 14:19:08

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 232 (class 1259 OID 19018)
-- Name: admin; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.admin (
admin_id integer NOT NULL,
username character varying(50) NOT NULL,
password character varying(255) NOT NULL
);


ALTER TABLE public.admin OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 19017)
-- Name: admin_admin_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.admin_admin_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.admin_admin_id_seq OWNER TO postgres;

--
-- TOC entry 5039 (class 0 OID 0)
-- Dependencies: 231
-- Name: admin_admin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.admin_admin_id_seq OWNED BY public.admin.admin_id;


--
-- TOC entry 220 (class 1259 OID 18913)
-- Name: branch; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.branch (
branch_id integer NOT NULL,
branch_name character varying(255) NOT NULL
);


ALTER TABLE public.branch OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 18912)
-- Name: branch_branch_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.branch_branch_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.branch_branch_id_seq OWNER TO postgres;

--
-- TOC entry 5040 (class 0 OID 0)
-- Dependencies: 219
-- Name: branch_branch_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.branch_branch_id_seq OWNED BY public.branch.branch_id;


--
-- TOC entry 242 (class 1259 OID 19092)
-- Name: documents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.documents (
doc_id integer NOT NULL,
doc_name character varying(255) NOT NULL,
doc_type character varying(100) NOT NULL,
upload_date timestamp without time zone DEFAULT now(),
status character varying(20) DEFAULT 'Active'::character varying
);


ALTER TABLE public.documents OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 19091)
-- Name: documents_doc_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.documents_doc_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.documents_doc_id_seq OWNER TO postgres;

--
-- TOC entry 5041 (class 0 OID 0)
-- Dependencies: 241
-- Name: documents_doc_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.documents_doc_id_seq OWNED BY public.documents.doc_id;


--
-- TOC entry 240 (class 1259 OID 19057)
-- Name: mother; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mother (
id integer NOT NULL,
student_roll_no character varying(50),
mother_name character varying(100),
mother_contact character varying(15)
);


ALTER TABLE public.mother OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 19056)
-- Name: mother_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mother_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.mother_id_seq OWNER TO postgres;

--
-- TOC entry 5042 (class 0 OID 0)
-- Dependencies: 239
-- Name: mother_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mother_id_seq OWNED BY public.mother.id;


--
-- TOC entry 238 (class 1259 OID 19047)
-- Name: notices; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notices (
notice_id integer NOT NULL,
title character varying(255) NOT NULL,
description text NOT NULL,
created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.notices OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 19046)
-- Name: notices_notice_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notices_notice_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.notices_notice_id_seq OWNER TO postgres;

--
-- TOC entry 5043 (class 0 OID 0)
-- Dependencies: 237
-- Name: notices_notice_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notices_notice_id_seq OWNED BY public.notices.notice_id;


--
-- TOC entry 234 (class 1259 OID 19027)
-- Name: photocopy_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.photocopy_requests (
request_id integer NOT NULL,
email character varying(255) NOT NULL,
subjects character varying(255) NOT NULL,
paymentid character varying(255),
request_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.photocopy_requests OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 19026)
-- Name: photocopy_requests_request_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.photocopy_requests_request_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.photocopy_requests_request_id_seq OWNER TO postgres;

--
-- TOC entry 5044 (class 0 OID 0)
-- Dependencies: 233
-- Name: photocopy_requests_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.photocopy_requests_request_id_seq OWNED BY public.photocopy_requests.request_id;


--
-- TOC entry 230 (class 1259 OID 18991)
-- Name: results; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.results (
result_id integer NOT NULL,
roll_no character varying(50),
branch_id integer,
sem_id integer,
subj_id integer,
marks integer
);


ALTER TABLE public.results OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 18990)
-- Name: results_result_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.results_result_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.results_result_id_seq OWNER TO postgres;

--
-- TOC entry 5045 (class 0 OID 0)
-- Dependencies: 229
-- Name: results_result_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.results_result_id_seq OWNED BY public.results.result_id;


--
-- TOC entry 236 (class 1259 OID 19037)
-- Name: revaluation_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.revaluation_requests (
request_id integer NOT NULL,
email character varying(255) NOT NULL,
subjects character varying(255) NOT NULL,
payment_id character varying(255),
request_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.revaluation_requests OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 19036)
-- Name: revaluation_requests_request_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.revaluation_requests_request_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.revaluation_requests_request_id_seq OWNER TO postgres;

--
-- TOC entry 5046 (class 0 OID 0)
-- Dependencies: 235
-- Name: revaluation_requests_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.revaluation_requests_request_id_seq OWNED BY public.revaluation_requests.request_id;


--
-- TOC entry 222 (class 1259 OID 18922)
-- Name: semester; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.semester (
sem_id integer NOT NULL,
semester integer NOT NULL
);


ALTER TABLE public.semester OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 18921)
-- Name: semester_sem_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.semester_sem_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.semester_sem_id_seq OWNER TO postgres;

--
-- TOC entry 5047 (class 0 OID 0)
-- Dependencies: 221
-- Name: semester_sem_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.semester_sem_id_seq OWNED BY public.semester.sem_id;


--
-- TOC entry 226 (class 1259 OID 18939)
-- Name: student; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student (
reg_id integer NOT NULL,
name character varying(255) NOT NULL,
roll_no character varying(50) NOT NULL,
email character varying(255) NOT NULL,
password character varying(255) NOT NULL,
gender character varying(10),
dob date,
branch_id integer,
sem_id integer,
status integer DEFAULT 1 NOT NULL,
subj_id integer
);


ALTER TABLE public.student OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 18938)
-- Name: student_reg_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.student_reg_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.student_reg_id_seq OWNER TO postgres;

--
-- TOC entry 5048 (class 0 OID 0)
-- Dependencies: 225
-- Name: student_reg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.student_reg_id_seq OWNED BY public.student.reg_id;


--
-- TOC entry 228 (class 1259 OID 18968)
-- Name: subject_comb; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.subject_comb (
id integer NOT NULL,
branch_id integer,
sem_id integer,
subj_id integer,
status integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.subject_comb OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 18967)
-- Name: subject_comb_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.subject_comb_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.subject_comb_id_seq OWNER TO postgres;

--
-- TOC entry 5049 (class 0 OID 0)
-- Dependencies: 227
-- Name: subject_comb_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.subject_comb_id_seq OWNED BY public.subject_comb.id;


--
-- TOC entry 224 (class 1259 OID 18931)
-- Name: subjects; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.subjects (
subj_id integer NOT NULL,
subj_name character varying(255) NOT NULL,
subj_code character varying(50) NOT NULL,
status integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.subjects OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 18930)
-- Name: subjects_subj_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.subjects_subj_id_seq
AS integer
START WITH 1
INCREMENT BY 1
NO MINVALUE
NO MAXVALUE
CACHE 1;


ALTER SEQUENCE public.subjects_subj_id_seq OWNER TO postgres;

--
-- TOC entry 5050 (class 0 OID 0)
-- Dependencies: 223
-- Name: subjects_subj_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.subjects_subj_id_seq OWNED BY public.subjects.subj_id;


--
-- TOC entry 4808 (class 2604 OID 19021)
-- Name: admin admin_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin ALTER COLUMN admin_id SET DEFAULT nextval('public.admin_admin_id_seq'::regclass);


--
-- TOC entry 4799 (class 2604 OID 18916)
-- Name: branch branch_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch ALTER COLUMN branch_id SET DEFAULT nextval('public.branch_branch_id_seq'::regclass);


--
-- TOC entry 4816 (class 2604 OID 19095)
-- Name: documents doc_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents ALTER COLUMN doc_id SET DEFAULT nextval('public.documents_doc_id_seq'::regclass);


--
-- TOC entry 4815 (class 2604 OID 19060)
-- Name: mother id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mother ALTER COLUMN id SET DEFAULT nextval('public.mother_id_seq'::regclass);


--
-- TOC entry 4813 (class 2604 OID 19050)
-- Name: notices notice_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notices ALTER COLUMN notice_id SET DEFAULT nextval('public.notices_notice_id_seq'::regclass);


--
-- TOC entry 4809 (class 2604 OID 19030)
-- Name: photocopy_requests request_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.photocopy_requests ALTER COLUMN request_id SET DEFAULT nextval('public.photocopy_requests_request_id_seq'::regclass);


--
-- TOC entry 4807 (class 2604 OID 18994)
-- Name: results result_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results ALTER COLUMN result_id SET DEFAULT nextval('public.results_result_id_seq'::regclass);


--
-- TOC entry 4811 (class 2604 OID 19040)
-- Name: revaluation_requests request_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revaluation_requests ALTER COLUMN request_id SET DEFAULT nextval('public.revaluation_requests_request_id_seq'::regclass);


--
-- TOC entry 4800 (class 2604 OID 18925)
-- Name: semester sem_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester ALTER COLUMN sem_id SET DEFAULT nextval('public.semester_sem_id_seq'::regclass);


--
-- TOC entry 4803 (class 2604 OID 18942)
-- Name: student reg_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student ALTER COLUMN reg_id SET DEFAULT nextval('public.student_reg_id_seq'::regclass);


--
-- TOC entry 4805 (class 2604 OID 18971)
-- Name: subject_comb id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subject_comb ALTER COLUMN id SET DEFAULT nextval('public.subject_comb_id_seq'::regclass);


--
-- TOC entry 4801 (class 2604 OID 18934)
-- Name: subjects subj_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subjects ALTER COLUMN subj_id SET DEFAULT nextval('public.subjects_subj_id_seq'::regclass);


--
-- TOC entry 5023 (class 0 OID 19018)
-- Dependencies: 232
-- Data for Name: admin; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.admin (admin_id, username, password) FROM stdin;
17	Abhijeet	Abhijeet0848
\.


--
-- TOC entry 5011 (class 0 OID 18913)
-- Dependencies: 220
-- Data for Name: branch; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.branch (branch_id, branch_name) FROM stdin;
1	Computer Science
\.


--
-- TOC entry 5033 (class 0 OID 19092)
-- Dependencies: 242
-- Data for Name: documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.documents (doc_id, doc_name, doc_type, upload_date, status) FROM stdin;
1	Bonafide Certificate	Certificate	2025-04-25 13:31:08.927334	Active
2	Fee Receipt	Receipt	2025-04-25 13:31:08.927334	Active
3	Admission Form	Form	2025-04-25 13:31:08.927334	Active
4	Exam Hall Ticket	Hall Ticket	2025-04-25 13:31:08.927334	Active
5	Result Statement	Academic Document	2025-04-25 13:31:08.927334	Active
\.


--
-- TOC entry 5031 (class 0 OID 19057)
-- Dependencies: 240
-- Data for Name: mother; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mother (id, student_roll_no, mother_name, mother_contact) FROM stdin;
1	1102208472	Chanda	1234567890
\.


--
-- TOC entry 5029 (class 0 OID 19047)
-- Dependencies: 238
-- Data for Name: notices; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notices (notice_id, title, description, created_at) FROM stdin;
1	Result Announcement	First Year B.Sc. (Computer Science) results for April 2023 have been announced.	2025-04-25 11:34:30.474213
\.


--
-- TOC entry 5025 (class 0 OID 19027)
-- Dependencies: 234
-- Data for Name: photocopy_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.photocopy_requests (request_id, email, subjects, paymentid, request_date) FROM stdin;
1	gautam@example.com	CS-111, CS-112, CS-113	PAY12345	2025-04-25 11:36:18.180647
17	gautam@example.com	ELC-243 Practical Course II	pay_QNDPqBsL2HxRRU	2025-04-25 13:25:25.288684
\.


--
-- TOC entry 5021 (class 0 OID 18991)
-- Dependencies: 230
-- Data for Name: results; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.results (result_id, roll_no, branch_id, sem_id, subj_id, marks) FROM stdin;
1	1102208472	1	1	11121	90
2	1102208472	1	1	11122	90
3	1102208472	1	1	11123	90
4	1102208472	1	1	11221	100
5	1102208472	1	1	11222	90
6	1102208472	1	1	11223	100
7	1102208472	1	1	11321	90
8	1102208472	1	1	11322	80
9	1102208472	1	1	11323	90
10	1102208472	1	1	11421	100
11	1102208472	1	1	11422	100
12	1102208472	1	1	11423	100
13	1102208472	1	2	12121	90
14	1102208472	1	2	12122	80
15	1102208472	1	2	12123	85
16	1102208472	1	2	12221	100
17	1102208472	1	2	12222	100
18	1102208472	1	2	12223	90
19	1102208472	1	2	12321	100
20	1102208472	1	2	12322	100
21	1102208472	1	2	12323	90
22	1102208472	1	2	12421	95
23	1102208472	1	2	12422	90
24	1102208472	1	2	12423	85
25	1102208472	1	2	12999	100
26	1102208472	1	2	1	85
27	1102208472	1	3	23121	90
28	1102208472	1	3	23122	90
29	1102208472	1	3	23123	90
30	1102208472	1	3	23221	100
31	1102208472	1	3	23222	100
32	1102208472	1	3	23223	100
33	1102208472	1	3	23321	90
34	1102208472	1	3	23322	100
35	1102208472	1	3	23323	90
36	1102208472	1	3	23921	100
37	1102208472	1	3	23922	90
38	1102208472	1	4	24121	90
39	1102208472	1	4	24122	80
40	1102208472	1	4	24123	85
41	1102208472	1	4	24221	90
42	1102208472	1	4	24222	95
43	1102208472	1	4	24223	100
44	1102208472	1	4	24321	90
45	1102208472	1	4	24322	90
46	1102208472	1	4	24323	100
47	1102208472	1	4	24921	90
48	1102208472	1	4	24922	90
\.


--
-- TOC entry 5027 (class 0 OID 19037)
-- Dependencies: 236
-- Data for Name: revaluation_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.revaluation_requests (request_id, email, subjects, payment_id, request_date) FROM stdin;
1	gautam@example.com	CS-111, CS-112	PAY12345	2025-04-25 11:36:01.956854
\.


--
-- TOC entry 5013 (class 0 OID 18922)
-- Dependencies: 222
-- Data for Name: semester; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.semester (sem_id, semester) FROM stdin;
2	2
1	1
3	3
4	4
\.


--
-- TOC entry 5017 (class 0 OID 18939)
-- Dependencies: 226
-- Data for Name: student; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student (reg_id, name, roll_no, email, password, gender, dob, branch_id, sem_id, status, subj_id) FROM stdin;
1	Gautam Abhijeetkumar	1102208472	gautam@example.com	password123	Male	2004-05-12	1	1	1	111
110	Sujeet	1102208473	sujeet@gmail.com	sujeet	Male	2003-06-05	1	1	1	11121
\.


--
-- TOC entry 5019 (class 0 OID 18968)
-- Dependencies: 228
-- Data for Name: subject_comb; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.subject_comb (id, branch_id, sem_id, subj_id, status) FROM stdin;
1	1	1	111	1
2	1	1	112	1
3	1	1	113	1
4	1	2	114	1
5	1	2	115	1
6	1	2	116	1
\.


--
-- TOC entry 5015 (class 0 OID 18931)
-- Dependencies: 224
-- Data for Name: subjects; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.subjects (subj_id, subj_name, subj_code, status) FROM stdin;
111	Mathematics	MATH101	1
112	Computer Science Fundamentals	CSF101	1
113	Physics	PHY101	1
114	Chemistry	CHE101	1
115	Data Structures	DS101	1
116	Algorithms	ALG101	1
11121	CS-111 Problems Solving Using Computer and "C" Programming	11121	1
11122	CS-112 Database Management Systems	11122	1
11123	CS-113 Practical Course Based on CS111 and CS112	11123	1
11221	MTC-111 Matrix Algebra	11221	1
11222	MTC-112 Discrete Mathematics	11222	1
11223	MTC-113 Mathematics Practical	11223	1
11321	ELC-111 Semiconductor Devices and Basic Electronic Systems	11321	1
11322	ELC-112 Principles of Digital Electronics	11322	1
11323	ELC-113 Electronics Lab IA	11323	1
11421	CSST-111 Descriptive Statistics I	11421	1
11422	CSST-112 Mathematical Statistics	11422	1
11423	CSST-113 Statistics Practical Paper I	11423	1
12121	CS-121 Advanced C Programming	CS-121	1
12122	CS-122 Relational Database Management Systems	CS-122	1
12123	CS-123 Practical Course Based on CS121 and CS122	CS-123	1
12221	MTC-121 Linear Algebra	MTC-121	1
12222	MTC-122 Graph Theory	MTC-122	1
12223	MTC-123 Mathematics Practical	MTC-123	1
12321	ELC-121 Instrumentation System	ELC-121	1
12322	ELC-122 Basics of Computer Organisation	ELC-122	1
12323	ELC-123 Electronics Lab IB	ELC-123	1
12421	CSST-121 Methods of Applied Statistics	CSST-121	1
12422	CSST-122 Continuous Probability Distributions	CSST-122	1
12423	CSST-123 Statistics Practical Paper II	CSST-123	1
12999	Democracy, Election, and Governance	12999	1
1	Physical Education	1	1
23121	CS 231 Data Structures and Algorithms - I	CS-231	1
23122	CS 232 Software Engineering	CS-232	1
23123	CS 233 Practical Course on CS 231 and CS 232	CS-233	1
23221	MTC 231 Groups and Coding Theory	MTC-231	1
23222	MTC-232 Numerical Techniques	MTC-232	1
23223	MTC-233 Mathematics Practical: Python Programming Language I	MTC-233	1
23321	ELC-231 Microcontroller Architecture & Programming	ELC-231	1
23322	ELC-232 Digital Communication and Networking	ELC-232	1
23323	ELC-233 Practical Course I	ELC-233	1
23921	AECC-I Environmental Awareness	AECC-I	1
23922	AECC-II Language Communication - I	AECC-II	1
24121	CS 241 Data Structures and Algorithms - II	CS-241	1
24122	CS 242 Computer Networks - I	CS-242	1
24123	CS 243 Practical Course on CS 241 and CS 242	CS-243	1
24221	MTC 241 Computational Geometry	MTC-241	1
24222	MTC-242 Operations Research	MTC-242	1
24223	MTC-243 Mathematics Practical: Python Programming Language II	MTC-243	1
24321	ELC-241 Embedded System Design	ELC-241	1
24322	ELC-242 Wireless Communication & Internet of Things	ELC-242	1
24323	ELC-243 Practical Course II	ELC-243	1
24921	AECC-I Environmental Awareness - II	AECC-I	1
24922	AECC-II Language Communication - II	AECC-II	1
\.


--
-- TOC entry 5051 (class 0 OID 0)
-- Dependencies: 231
-- Name: admin_admin_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.admin_admin_id_seq', 17, true);


--
-- TOC entry 5052 (class 0 OID 0)
-- Dependencies: 219
-- Name: branch_branch_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.branch_branch_id_seq', 15, true);


--
-- TOC entry 5053 (class 0 OID 0)
-- Dependencies: 241
-- Name: documents_doc_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.documents_doc_id_seq', 5, true);


--
-- TOC entry 5054 (class 0 OID 0)
-- Dependencies: 239
-- Name: mother_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mother_id_seq', 1, false);


--
-- TOC entry 5055 (class 0 OID 0)
-- Dependencies: 237
-- Name: notices_notice_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notices_notice_id_seq', 15, true);


--
-- TOC entry 5056 (class 0 OID 0)
-- Dependencies: 233
-- Name: photocopy_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.photocopy_requests_request_id_seq', 17, true);


--
-- TOC entry 5057 (class 0 OID 0)
-- Dependencies: 229
-- Name: results_result_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.results_result_id_seq', 87, true);


--
-- TOC entry 5058 (class 0 OID 0)
-- Dependencies: 235
-- Name: revaluation_requests_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.revaluation_requests_request_id_seq', 16, true);


--
-- TOC entry 5059 (class 0 OID 0)
-- Dependencies: 221
-- Name: semester_sem_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.semester_sem_id_seq', 15, true);


--
-- TOC entry 5060 (class 0 OID 0)
-- Dependencies: 225
-- Name: student_reg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.student_reg_id_seq', 110, true);


--
-- TOC entry 5061 (class 0 OID 0)
-- Dependencies: 227
-- Name: subject_comb_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.subject_comb_id_seq', 5, true);


--
-- TOC entry 5062 (class 0 OID 0)
-- Dependencies: 223
-- Name: subjects_subj_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.subjects_subj_id_seq', 15, true);


--
-- TOC entry 4840 (class 2606 OID 19023)
-- Name: admin admin_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin
ADD CONSTRAINT admin_pkey PRIMARY KEY (admin_id);


--
-- TOC entry 4842 (class 2606 OID 19025)
-- Name: admin admin_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin
ADD CONSTRAINT admin_username_key UNIQUE (username);


--
-- TOC entry 4820 (class 2606 OID 18920)
-- Name: branch branch_branch_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch
ADD CONSTRAINT branch_branch_name_key UNIQUE (branch_name);


--
-- TOC entry 4822 (class 2606 OID 18918)
-- Name: branch branch_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch
ADD CONSTRAINT branch_pkey PRIMARY KEY (branch_id);


--
-- TOC entry 4852 (class 2606 OID 19099)
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents
ADD CONSTRAINT documents_pkey PRIMARY KEY (doc_id);


--
-- TOC entry 4850 (class 2606 OID 19062)
-- Name: mother mother_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mother
ADD CONSTRAINT mother_pkey PRIMARY KEY (id);


--
-- TOC entry 4848 (class 2606 OID 19055)
-- Name: notices notices_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notices
ADD CONSTRAINT notices_pkey PRIMARY KEY (notice_id);


--
-- TOC entry 4844 (class 2606 OID 19035)
-- Name: photocopy_requests photocopy_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.photocopy_requests
ADD CONSTRAINT photocopy_requests_pkey PRIMARY KEY (request_id);


--
-- TOC entry 4838 (class 2606 OID 18996)
-- Name: results results_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT results_pkey PRIMARY KEY (result_id);


--
-- TOC entry 4846 (class 2606 OID 19045)
-- Name: revaluation_requests revaluation_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revaluation_requests
ADD CONSTRAINT revaluation_requests_pkey PRIMARY KEY (request_id);


--
-- TOC entry 4824 (class 2606 OID 18927)
-- Name: semester semester_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester
ADD CONSTRAINT semester_pkey PRIMARY KEY (sem_id);


--
-- TOC entry 4826 (class 2606 OID 18929)
-- Name: semester semester_semester_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.semester
ADD CONSTRAINT semester_semester_key UNIQUE (semester);


--
-- TOC entry 4830 (class 2606 OID 18951)
-- Name: student student_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_email_key UNIQUE (email);


--
-- TOC entry 4832 (class 2606 OID 18947)
-- Name: student student_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_pkey PRIMARY KEY (reg_id);


--
-- TOC entry 4834 (class 2606 OID 18949)
-- Name: student student_roll_no_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_roll_no_key UNIQUE (roll_no);


--
-- TOC entry 4836 (class 2606 OID 18974)
-- Name: subject_comb subject_comb_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subject_comb
ADD CONSTRAINT subject_comb_pkey PRIMARY KEY (id);


--
-- TOC entry 4828 (class 2606 OID 18937)
-- Name: subjects subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subjects
ADD CONSTRAINT subjects_pkey PRIMARY KEY (subj_id);


--
-- TOC entry 4859 (class 2606 OID 19083)
-- Name: results fk_branch; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT fk_branch FOREIGN KEY (branch_id) REFERENCES public.branch(branch_id);


--
-- TOC entry 4864 (class 2606 OID 19063)
-- Name: mother mother_student_roll_no_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mother
ADD CONSTRAINT mother_student_roll_no_fkey FOREIGN KEY (student_roll_no) REFERENCES public.student(roll_no) ON DELETE CASCADE;


--
-- TOC entry 4860 (class 2606 OID 19002)
-- Name: results results_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT results_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(branch_id) ON DELETE CASCADE;


--
-- TOC entry 4861 (class 2606 OID 18997)
-- Name: results results_roll_no_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT results_roll_no_fkey FOREIGN KEY (roll_no) REFERENCES public.student(roll_no) ON DELETE CASCADE;


--
-- TOC entry 4862 (class 2606 OID 19007)
-- Name: results results_sem_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT results_sem_id_fkey FOREIGN KEY (sem_id) REFERENCES public.semester(sem_id) ON DELETE CASCADE;


--
-- TOC entry 4863 (class 2606 OID 19012)
-- Name: results results_subj_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.results
ADD CONSTRAINT results_subj_id_fkey FOREIGN KEY (subj_id) REFERENCES public.subjects(subj_id) ON DELETE CASCADE;


--
-- TOC entry 4853 (class 2606 OID 18952)
-- Name: student student_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(branch_id) ON DELETE SET NULL;


--
-- TOC entry 4854 (class 2606 OID 18957)
-- Name: student student_sem_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_sem_id_fkey FOREIGN KEY (sem_id) REFERENCES public.semester(sem_id) ON DELETE SET NULL;


--
-- TOC entry 4855 (class 2606 OID 18962)
-- Name: student student_subj_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
ADD CONSTRAINT student_subj_id_fkey FOREIGN KEY (subj_id) REFERENCES public.subjects(subj_id) ON DELETE SET NULL;


--
-- TOC entry 4856 (class 2606 OID 18975)
-- Name: subject_comb subject_comb_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subject_comb
ADD CONSTRAINT subject_comb_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(branch_id) ON DELETE CASCADE;


--
-- TOC entry 4857 (class 2606 OID 18980)
-- Name: subject_comb subject_comb_sem_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subject_comb
ADD CONSTRAINT subject_comb_sem_id_fkey FOREIGN KEY (sem_id) REFERENCES public.semester(sem_id) ON DELETE CASCADE;


--
-- TOC entry 4858 (class 2606 OID 18985)
-- Name: subject_comb subject_comb_subj_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.subject_comb
ADD CONSTRAINT subject_comb_subj_id_fkey FOREIGN KEY (subj_id) REFERENCES public.subjects(subj_id) ON DELETE CASCADE;


-- Completed on 2025-04-25 14:19:09

--
-- PostgreSQL database dump complete
--

