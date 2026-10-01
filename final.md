TEACHER EVALUATION SYSTEM
STACK

PHP8+ PDO MySQL HTML5 CSS3 JS AJAX

Build from scratch. Use secure, responsive, modern academic UI.

ROLES

ADMIN = full control
TEACHER = profile, subjects, results, feedback
STUDENT = assigned evaluations, history

FLOW

Admin configures → Student evaluates assigned Teacher → System calculates → Teacher/Admin views results

Rating:

5 Excellent | 4 Very Good | 3 Good | 2 Needs Improvement | 1 Poor

Categories:

Knowledge | Teaching | Management | Communication | Assessment | Professionalism

DB
roles
users
teachers
students
departments
subjects
classes
class_students
teacher_subjects
question_categories
questions
rating_scales
evaluation_periods
evaluations
evaluation_answers
evaluation_comments
notifications
settings
audit_logs


Relations:

Student→Class→Subject→Teacher

Evaluation→Student+Teacher+Class+Subject+Period

Evaluation→Questions→Answers

Use PK/FK/indexes/unique constraints/timestamps.

Create database.sql.

STRUCTURE
teacher-evaluation/
├── index.php
├── login.php
├── logout.php
├── database.sql
├── README.md
├── config.example.php
├── admin/
├── teacher/
├── student/
├── api/
├── includes/
│   ├── config.php
│   ├── database.php
│   ├── auth.php
│   ├── permissions.php
│   ├── functions.php
│   ├── validation.php
│   ├── csrf.php
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── uploads/

PHASES
P01 — Foundation

/HTML /CSS /PHP /UI

Project structure, layout, login, header, sidebar, footer, dashboards.

P02 — Database

/MYSQL /PHP

Complete schema, relationships, indexes, constraints, seed data.

P03 — Authentication

/AUTH /SEC

Login/logout, sessions, password hashing, RBAC, protected routes.

P04 — Users

/U /CRUD

User CRUD, roles, status, search, password reset.

P05 — Teachers

/H /CRUD

Teacher CRUD, profile, department, employee number, status.

P06 — Students

/I /CRUD

Student CRUD, profile, student number, department, year, section.

P07 — Academics

/C /D /J /Y /CRUD

Departments, subjects, classes, sections, school year, semester, enrollment, teacher assignments.

P08 — Questionnaire

/K /Q /G /CRUD

Categories, questions, rating scale, ordering, required fields, question types.

P09 — Evaluation Period

/T /E

Create/open/close evaluation periods with date control.

P10 — Assignments

/W /E

Only valid Student→Class→Subject→Teacher evaluations.

P11 — Evaluation Form

/E /Q /G /F /AJAX

Responsive student form, ratings, comments, validation, progress, submit, duplicate protection.

P12 — Feedback

/F

Comments, anonymity, visibility settings.

P13 — Results

/R /G /O

Question/category/overall averages, response count/rate, charts, history.

P14 — Teacher Dashboard

/H /R /O

Subjects, evaluation results, averages, categories, feedback. Read-only.

P15 — Student Dashboard

/I /E

Available, pending, completed evaluations and history.

P16 — Admin Dashboard

/B /M /O

Users, teachers, students, classes, periods, evaluations, response rate, analytics.

P17 — Reports

/L /R

Filter by year, semester, period, department, teacher, subject, class.

P18 — Export

/X

CSV, Excel-compatible, print/PDF-ready reports.

P19 — Notifications

/N /AJAX

Evaluation opening, deadlines, completion, announcements.

P20 — Search

/M /AJAX

Search, filtering, sorting, pagination.

P21 — Security

/SEC

PDO/prepared statements, CSRF, XSS protection, validation, escaping, RBAC, session security, upload validation, login protection.

P22 — Responsive

/CSS /UI

Desktop, tablet, mobile.

P23 — UI Polish

/UI

Professional academic theme, cards, tables, forms, modals, alerts, loading/empty/error states.

P24 — Settings

/S

School information, logo, contact, anonymity, comments, rating, result visibility.

P25 — Audit

/Z

Log important user/admin actions with user, action, module, record, IP, timestamp.

P26 — Errors

/PHP /SEC

401, 403, 404, 500, validation/database/auth errors. Never expose secrets.

P27 — Performance

/PHP /MYSQL /AJAX

Indexes, efficient queries, pagination, optimized dashboards/reports.

P28 — Testing

/TEST /SEC

Auth, RBAC, CRUD, evaluations, duplicates, dates, assignments, reports, exports, CSRF, XSS, SQL injection, sessions.

P29 — Installation

/MYSQL /PHP

README, database.sql, config.example.php, setup instructions, initial admin.

P30 — Final Audit

/AUDIT /SEC /TEST

Verify every feature, security, database, UI, reports, exports, installation. Fix all issues.

RULES

Build sequentially.

For each phase:

implement → test → fix → continue

Use reusable components.

Use PDO + prepared statements.

Hash passwords.

Validate server-side.

Escape output.

Use CSRF protection.

Enforce RBAC server-side.

Prevent duplicate evaluations.

Respect anonymity.

Do not expose sensitive data.

Do not add unnecessary dependencies.

Keep code simple and maintainable.

COMMANDS

/BUILD = next phase

/P01 ... /P30 = specific phase

/ALL = build all phases

/STATUS = progress

/FIX = fix current phase

/SEC = security audit

/TEST = test

/UI = UI work

/DB = database work

/AUDIT = final audit

OUTPUT

After each phase:

Phase: PXX
Status: DONE/PARTIAL
Files:
Database:
Tests:
Issues:

START

/P01