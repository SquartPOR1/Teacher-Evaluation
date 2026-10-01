# Teacher Evaluation System

A PHP 8+ and MySQL academic feedback portal with administrator, teacher, and student workspaces. It uses PDO prepared statements, role checks on protected routes, CSRF tokens for state-changing forms, session rotation, password hashing, output escaping, duplicate submission constraints, and privacy-aware aggregate results.

## Requirements

- PHP 8.0+ with PDO MySQL, JSON, and mbstring extensions
- MySQL 8.0+
- Apache, Nginx, or PHP's built-in web server for local development

## Install

1. Create a MySQL account and database privileges for the application.
2. Import `database.sql` with the MySQL client or a database UI. The script creates the `teacher_evaluation` database and its tables, standard 1–5 rating labels, starter categories/questions, and default privacy settings. It drops/recreates application tables when rerun; back up existing data first.
3. Set environment variables for the web server/PHP process: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, optionally `APP_BASE_URL` (for example `/teacher-evaluation` when hosted in a subdirectory), and `ADMIN_SIGNUP_CODE` (a long, unique secret required to create administrator accounts). `config.example.php` documents the database and base URL names. Do not put real secrets in source control.
4. Point the web root at this directory and enable HTTPS for deployment. Ensure the PHP process can write its normal session and error-log locations. Keep `uploads/` non-executable if you later enable uploads.
5. Configure `ADMIN_SIGNUP_CODE`, then visit the sign-in page and choose **Admin create account**. Enter the passcode to continue to the administrator registration form, then choose a unique email and a password of at least 12 characters. The legacy `setup.php` route redirects through the same passcode-protected flow.
6. Sign in, add departments, then users, subjects/classes, enrollments, and teacher assignments under **Academics & setup**. Assignment and enrollment creation checks department consistency. Configure questions and an evaluation period; open the period only when dates and assignments are ready.

For a local PHP development server, set the environment variables in your shell before starting it, then serve this folder using PHP's built-in server. Do not use the built-in server for production.

## First-run sequence

1. Add department records.
2. Create teacher and student accounts in **People**, including each person's department and unique employee/student number.
3. Create subjects and classes; assign teachers to a subject/class and enroll students into their class.
4. Review starter questions or manage the questionnaire.
5. Create an evaluation period with start/end dates, then set its status to **Open**.
6. Students submit one evaluation for each assigned teacher/class/subject/period. The database unique key and submission transaction prevent duplicates.
7. Teachers see aggregate results only when there are at least three submissions for the period. Admins can filter results and export CSV without student names.

## Project map

- `admin/`: user, academic, questionnaire, settings, and reporting pages
- `teacher/`: aggregate results and thresholded feedback
- `student/`: assigned evaluations, submission, and history
- `api/`: authenticated search and notification JSON endpoints
- `includes/`: shared configuration, database, auth, CSRF, validation, and layout
- `assets/`: responsive CSS and small progressive-enhancement JavaScript
- `database.sql`: schema and baseline reference data

## Security and operations

- Use HTTPS and secure database credentials; configure production PHP to log errors to a protected location.
- Keep PHP/MySQL patched and back up the database regularly. The SQL install file is destructive if rerun against existing data.
- Anonymous evaluation mode prevents teacher-facing identity disclosure; student references remain in restricted records to enforce eligibility and duplicate prevention. Teachers only receive aggregates and comments after the minimum response threshold. Restrict database/report access to authorized administrators.
- CSV export neutralizes formula-like cell values. Exports contain no student identifiers.
- No default admin password or registration passcode is included. Keep `ADMIN_SIGNUP_CODE` private and use it only to authorize administrator creation; the normal way to create teacher and student accounts is through the admin **People** page.

## Current boundaries

The app provides the core role workflows and schema; automated test coverage, email delivery, PDF generation, file uploads, and complete inline edit/delete interfaces are not bundled. CSV is Excel-compatible. Deploy behind your institution's HTTPS web server and validate local institutional privacy requirements before collecting real feedback.