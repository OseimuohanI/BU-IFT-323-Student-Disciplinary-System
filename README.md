# Student Discipline System

Lightweight student disciplinary system used to record incidents, review cases and apply sanctions.
This README documents setup, common workflows, key files/endpoints, debugging tips and developer notes.

---

## Table of contents
- Overview
- Prerequisites
- Quick start (Windows / XAMPP)
- Configuration
- Database notes & useful queries
- Key pages & endpoints
- Lecturer workflow
- Admin / developer tools
- Debugging & common errors
- Security & deployment notes
- Contributing

---

## Overview
This PHP + MySQL (MariaDB) application provides:
- Student listing and profile dashboards
- Incident / case discovery and analytics (adapts to existing schema)
- Lecturer dashboard to review cases and apply sanctions
- JSON endpoints for AJAX actions (delete student, apply sanction, authentication)
The code emphasises auto-detection of existing case table/columns to avoid forcing schema changes.

---

## Prerequisites
- Windows with XAMPP (Apache + PHP + MariaDB) or equivalent LAMP/WAMP
- PHP 8.x (tested with 8.2)
- MySQL / MariaDB
- Browser with DevTools for debugging network / JSON responses

---

## Quick start (Windows / XAMPP)
1. Place the project in your web root, e.g.:
   C:\xampp\htdocs\student-discipline-system
2. Start Apache & MySQL via XAMPP Control Panel.
3. Create / configure the database (if not present) and import your existing tables.
4. Ensure `config.php` exists (see Configuration).
5. Open: http://localhost/student-discipline-system/

---

## Configuration
Create `config.php` at project root or edit existing. Minimal example:

```php
// filepath: c:\xampp\htdocs\student-discipline-system\config.php
<?php
define('DB_HOST','127.0.0.1');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','student_disciplinary_system');
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');
```

Notes:
- If `config.php` is absent, scripts try sensible defaults.
- For debugging endpoints, many files include an `$EXPOSE_ERRORS` flag — set to `false` for production.

---

## Database notes & useful queries
The app attempts to auto-detect table/column names. If detection fails, either rename your tables/columns to common names, or add your names in candidate lists inside the PHP files.

Helpful queries:
- List tables:
  SHOW TABLES;
- Inspect `users`:
  SHOW COLUMNS FROM users;
- Inspect `student`:
  SHOW COLUMNS FROM student;
- Inspect case table (example `incidentreport`):
  SHOW COLUMNS FROM incidentreport;

If login fails because no password column exists, run the supplied CLI tool to add/set password (see Admin tools).

---

## Key pages & endpoints
- index.php — Student list and login modal
- student_dashboard.php — Student analytics dashboard (auto-detects case table)
- lecturer_dashboard.php — Lecturer case review and sanction UI
- lecturer_dashboard.js (embedded) — client-side handlers for sanction modal
- auth.php — login endpoint (returns JSON and redirects lecturers)
- sanction_apply.php — JSON endpoint to apply a sanction to an existing case row
- student_delete.php — JSON endpoint to delete student rows (returns detailed JSON on error)
- tools/set_password.php — CLI helper to add Password column and set user password
- tools/create_user.php — CLI helper to create users (if present)

Endpoint behavior:
- All JSON endpoints return detailed debugging info when `$EXPOSE_ERRORS = true`.
- Use DevTools → Network → Response to inspect returned JSON when debugging.

---

## Lecturer workflow
1. Lecturer logs in (server redirects lecturers to `lecturer_dashboard.php`).
2. Dashboard shows open cases (joins `student` to show names where possible).
3. Lecturer clicks a case → modal opens with full description.
4. Lecturer selects sanction, duration, effective date, notes and submits.
5. Browser POST → `/student-discipline-system/sanction_apply.php` (JSON).
6. Endpoint updates the existing case row (Sanctions / Notes / Status) — no new tables created.
7. On success UI reloads to reflect changes.

If your case table name is not auto-detected, add its name to `$candidateTables` near the top of `lecturer_dashboard.php` and `sanction_apply.php`.

---

## Admin / developer tools (CLI)
From project root (Windows CMD / Powershell):
- Ensure PHP executable is available in PATH or use full path (e.g. C:\xampp\php\php.exe).

Create or update a user password:
```
php tools\set_password.php admin newpassword
```

Create a user (if `tools/create_user.php` exists):
```
php tools\create_user.php username "password" "Full Name" role
```

Debug password verification:
```
php tools/check_password.php username password
```

---

## Debugging & common errors
- "Server returned invalid JSON" — client expected JSON but server returned HTML (404 or PHP error). Inspect DevTools → Network → Response for the raw body. Ensure endpoint path is correct. If using relative fetch, prefer absolute path: `/student-discipline-system/sanction_apply.php`.
- "Unknown column 'Password' in 'field list'" — users table doesn't have expected password column; use `tools/set_password.php` to add `Password` and set hashed password.
- "Invalid credentials" although correct — run `tools/check_password.php` to inspect stored user row and how password is stored (column names / hashing).
- SQL syntax errors due to trailing commas — updated queries build select lists safely; ensure your files are up-to-date.
- 404 on sanction endpoint — confirm `sanction_apply.php` exists in project root and that fetch uses the correct path.

When reporting errors, copy the exact JSON/HTML response body and the Request URL from DevTools.

---

## Security & deployment notes
- Set `$EXPOSE_ERRORS = false` in production.
- Use HTTPS in production.
- Ensure user passwords are hashed (use password_hash). Avoid storing plain text.
- Validate and sanitize all inputs; existing code uses prepared statements for DB updates but verify any custom changes.
- Limit file permissions for config and tools on production hosts.

---

## Contributing / Development notes
- Code is written to be resilient to variations in schema. When adding features:
  - Add new candidate column names to `pick_col` arrays to improve detection.
  - Add new candidate table names to `$candidateTables`.
- Keep JSON endpoints consistent: always return application/json and include `success` boolean.
- When adding front-end JS fetch calls, read response text first and attempt JSON.parse to provide helpful errors to users.

---

## License
Specify your chosen license here (e.g., MIT). Add LICENSE file to project root.

---

If you want, I can:
- Generate a shorter "quick start" README for non-technical users,
- Create an example `config.php` template file in the repo,
- Produce a developer checklist for adding new metrics to student_dashboard.php.

Tell me which of the above to add.