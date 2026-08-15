# TimeSync — Smart Timetable Management & Personalization System

TimeSync turns dozens of department Excel timetables into one personalized, searchable, conflict-checked schedule for every student.

---

## 1. Project Overview

Every semester a college publishes ~40 separate timetable spreadsheets, one per department/semester/section. Students have to manually search through them to find their own classes, and nobody notices when two sections get the same room, or a faculty member is double-booked, until it's too late.

TimeSync fixes this:

- **Admins** upload each department's Excel timetable. The system reads it, validates it, and stores it in a normalized MySQL database.
- **Conflict detection** automatically flags overlapping classes for the same section, faculty double-bookings, and room double-bookings.
- **Students** register once with their department/semester/section and instantly see their own personalized weekly timetable, today's classes, and any conflicts affecting them — no spreadsheet hunting required.

## 2. Features

**Admin**
- Secure login, dashboard with live statistics (students, records, files, conflicts)
- Drag-and-drop Excel upload with per-file progress and an import summary (imported / skipped / conflicts / row-level errors)
- Downloadable sample Excel template
- Full timetable record browser with search + filters (department, semester, day, keyword)
- Conflict report with severity (HIGH/MEDIUM/LOW), type, and department/semester/section filters, plus manual re-scan and resolve
- CRUD management for Students, Subjects, Faculty, Rooms, Sections
- Analytics dashboard (Chart.js): records by department, classes by day, busiest rooms, faculty workload, conflicts by type

**Student**
- Register/login, mapped automatically to department + semester + section
- Personalized dashboard: today's class count, next class, subject count, open conflicts
- Today's Classes as a timeline with Current / Upcoming / Completed indicators
- Full weekly timetable — a real desktop grid (Mon–Sat, 08:00–18:00) that turns into a day-by-day mobile timeline below 900px, with tabs to switch days
- Click any class for a detail modal (subject, code, faculty, room, day, time, section)
- Subjects & Faculty directories, both searchable
- Conflicts affecting their own section only
- Print/PDF the timetable (browser print, with a clean print-only header and no dashboard chrome)

**Everything is read from MySQL.** Nothing on the dashboards is hardcoded — seed data exists only to make the app usable immediately after import.

## 3. Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3 (custom design system, no framework), Vanilla JavaScript, Font Awesome, Google Fonts (Sora + Inter), Chart.js (analytics only) |
| Backend | PHP 8+ (procedural, no framework) |
| Database | MySQL 8 / MariaDB (via PDO, prepared statements) |
| Excel Processing | PHPSpreadsheet (via Composer) |
| Local Dev | XAMPP (Apache + MySQL) |
| Version Control | Git / GitHub |

No React/Vue/Angular/Node — deliberately, so every file is readable by someone who knows HTML/CSS/JS/MySQL.

## 4. System Architecture

```
Browser (HTML/CSS/JS)
        │  HTTP (forms + fetch/XHR for upload & small AJAX actions)
        ▼
Apache + PHP 8 (XAMPP)
        │
        ├── includes/auth.php       → session guards (requireLogin, requireRole)
        ├── includes/functions.php  → ALL business logic (timetable queries, Excel
        │                             normalization, conflict detection algorithms)
        ├── admin/*.php, student/*.php → thin "controller + view" pages that call
        │                                functions.php and render HTML
        ├── api/*.php               → JSON endpoints used by JS (upload, delete file,
        │                             download template)
        └── config/database.php     → single shared PDO connection
                │
                ▼
            MySQL (timesync_db)
```

Business logic lives in `includes/functions.php`, completely separate from presentation — this is what an interviewer will want to see: a services/helpers layer, not SQL scattered through every page.

## 5. Database Schema (ER overview)

```
departments 1───* sections *───1 semesters
departments 1───* subjects *───1 semesters
departments 1───* faculty
                                       
users 1───1 students *───1 sections
users 1───1 admins

sections 1───* timetables *───1 subjects
timetables *───1 faculty
timetables *───1 rooms
timetables *───1 uploaded_files

conflicts *───1 timetables (timetable_id_1)
conflicts *───1 timetables (timetable_id_2)
```

Key tables: `users`, `students`, `admins`, `departments`, `semesters`, `sections`, `subjects`, `faculty`, `rooms`, `timetables`, `uploaded_files`, `conflicts`. All foreign keys, unique constraints and indexes are defined in `database/timesync_db.sql` — see that file for exact column definitions.

## 6. Folder Structure

```
timesync/
├── index.php               Landing page
├── login.php                Split-screen login (role selector: student/admin)
├── register.php              Student self-registration
├── logout.php
│
├── config/
│   ├── config.php           Session bootstrap, constants (BASE_URL, upload limits...)
│   └── database.php         PDO connection
│
├── admin/                    All admin-only pages (guarded by requireRole('admin'))
│   ├── dashboard.php  upload.php  timetables.php  conflicts.php
│   ├── students.php   subjects.php  faculty.php  rooms.php
│   └── sections.php   analytics.php
│
├── student/                  All student-only pages (guarded by requireRole('student'))
│   ├── dashboard.php  timetable.php  today.php
│   └── subjects.php   faculty.php    conflicts.php   profile.php
│
├── api/                       JSON endpoints called from JS
│   ├── upload_process.php    Handles one Excel upload end-to-end
│   ├── delete_file.php
│   └── download_template.php Generates a sample .xlsx on the fly
│
├── includes/
│   ├── auth.php               Login/role guards
│   ├── functions.php          Business logic (see section 8)
│   ├── header.php / footer.php  Shared <head> + closing markup + toast wiring
│   └── sidebar.php             Shared sidebar (admin/student link sets)
│
├── assets/
│   ├── css/style.css          Whole design system (CSS variables, components)
│   ├── js/app.js              Toasts, modals, sidebar toggle, password visibility
│   ├── js/upload.js           Drag-and-drop upload + progress + import summary
│   └── js/timetable.js        Class detail modal + mobile day switcher
│
├── uploads/                   Uploaded spreadsheets land here (write-protected .htaccess)
├── database/
│   ├── timesync_db.sql        Full schema + seed data
│   └── sample_timetable_template.xlsx
├── vendor/                    Created by `composer install` (PHPSpreadsheet)
├── composer.json
└── README.md
```

## 7. Excel Processing Strategy

1. Admin selects a **default department/semester** (used only when the sheet itself has no Department/Semester column) and drags in one or more files.
2. `api/upload_process.php` validates extension (`xlsx`/`xls`/`csv`) and size (≤5MB), stores the file under `uploads/`, and creates an `uploaded_files` row (`status = processing`).
3. `processExcelFile()` in `includes/functions.php` loads the sheet with **PHPSpreadsheet**, reads the header row, and calls `normalizeHeader()` on every column — this maps many possible spellings ("Faculty"/"Teacher"/"Instructor", "Room"/"Classroom"/"Venue", "Time"/"Start Time"/"From", etc.) to one internal field name. If a required field (Day, Start Time, End Time, Subject) can't be identified at all, the import stops immediately with *"Unable to identify the Subject column."* rather than silently importing garbage.
4. Each row is normalized (`normalizeTimetableRow()`): times are parsed from `"9am"`, `"09:00"`, or Excel's internal float-time format; dates are converted to weekday names; end-time-before-start-time and missing-field rows are rejected with a specific reason.
5. Valid rows are inserted via `insertTimetableRecord()`, which uses a **get-or-create** pattern for Section/Subject/Faculty/Room — so the same faculty member or room referenced across many files resolves to one row, not a duplicate every time.
6. After import, `detectSectionConflicts()`, `detectFacultyConflicts()`, and `detectRoomConflicts()` run against the newly-inserted rows.
7. The browser gets back a JSON summary: total rows, imported, skipped, conflicts, and a list of row-level errors (`Row 34: Missing Subject`) — shown directly under the file in the upload queue.

## 8. Conflict Detection Strategy

All three detectors share one time-overlap rule (`timesOverlap()`), applied correctly instead of just comparing exact start times:

```php
function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool {
    return ($start1 < $end2) && ($start2 < $end1);
}
```

This correctly catches partial overlaps like `10:00–11:30` vs `11:00–12:00`.

| Detector | Groups by | Meaning | Severity |
|---|---|---|---|
| `detectSectionConflicts()` | section_id | The same section has two overlapping classes — physically impossible to attend both | HIGH |
| `detectFacultyConflicts()` | faculty_id | The same faculty member is booked for two overlapping classes in different sections | HIGH |
| `detectRoomConflicts()` | room_id | The same room is double-booked | MEDIUM |

Every detected conflict is written once to the `conflicts` table (`logConflict()` de-duplicates by checking both id orders first), with a human-readable description, so admins and affected students can filter/browse them without re-running detection.

## 9. Security

- Passwords hashed with `password_hash()` / verified with `password_verify()` — never stored in plain text
- All database access goes through **PDO prepared statements** (no string-concatenated SQL anywhere)
- `htmlspecialchars()` (via the `e()` helper) on every value echoed into HTML — no raw output
- PHP sessions + `requireRole('admin' | 'student')` guard on every admin/student page — a student hitting an admin URL is redirected to login, not shown an error
- File uploads validated by extension **and** size before being moved; `uploads/.htaccess` blocks PHP execution inside the upload folder as defense in depth
- Raw PHP/SQL errors are never shown to users — they're logged (`error_log`) and a friendly message is displayed instead

## 10. Installation (XAMPP, step by step)

1. **Install XAMPP** (https://www.apachefriends.org) if you don't already have it.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Copy this whole `timesync` folder into your XAMPP `htdocs` directory, so you end up with:
   ```
   C:\xampp\htdocs\timesync\        (Windows)
   /Applications/XAMPP/htdocs/timesync/   (Mac)
   /opt/lampp/htdocs/timesync/      (Linux)
   ```
4. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and create a database named exactly `timesync_db` — or just skip this, since the SQL file creates it for you.
5. In phpMyAdmin, go to the **Import** tab, choose `database/timesync_db.sql`, and click **Go**. This creates every table and inserts all seed data (departments, subjects, faculty, rooms, demo users, a full sample timetable, and a couple of intentional demo conflicts so the Conflicts page isn't empty on first login).
6. **Install Composer dependencies** (needed for Excel upload + template download to work). Open a terminal in the `timesync` folder and run:
   ```bash
   composer install
   ```
   This downloads PHPSpreadsheet into `vendor/`. If you don't have Composer yet, get it from https://getcomposer.org.
7. Open **`config/config.php`** and confirm `BASE_URL` matches your folder name (`/timesync` by default — change it if you renamed the folder).
8. Visit **http://localhost/timesync/** in your browser. You should see the landing page.

That's it — the database already has demo data, so you can log in immediately.

## 11. Demo Credentials

| Role | Email | Password |
|---|---|---|
| Admin | `admin@timesync.com` | `Admin@123` |
| Student | `student@timesync.com` | `Student@123` |

(Demo student is registered as CSE · Semester 5 · Section A, with a full sample weekly timetable already loaded.)

## 12. Excel Format Requirements

The importer recognizes many header spellings, but the cleanest format is:

| Day | Start Time | End Time | Subject Code | Subject | Faculty | Room | Section |
|---|---|---|---|---|---|---|---|
| Monday | 09:00 | 10:00 | CS501 | Database Management Systems | Dr. Sharma | 204 | A |

- **Required columns** (must be identifiable): Day, Start Time, End Time, Subject
- **Recommended**: Subject Code, Faculty, Room, Section (auto-generated/defaulted if missing)
- Accepted header synonyms include: `Date` for Day; `From`/`Time` for Start Time; `To` for End Time; `Course Name` for Subject; `Course Code` for Subject Code; `Teacher`/`Instructor` for Faculty; `Classroom`/`Venue` for Room; `Sec` for Section; `Dept`/`Branch` for Department; `Sem` for Semester.
- A ready-to-use template is available from **Admin → Upload Files → Sample Template**, and a static copy is included at `database/sample_timetable_template.xlsx`.

## 13. Future Improvements

- Email notifications when a new conflict affecting a student's section is detected
- Bulk multi-file upload validation summary (all files in one report, not per-file only)
- Admin ability to edit a single timetable row inline instead of only via re-upload
- Role-based sub-permissions (e.g. department coordinators who can only manage their own department)
- Native PDF export (currently uses the browser's print-to-PDF) via a PDF library
- Automated email verification on student registration

---

Built with HTML, CSS, JavaScript, PHP, and MySQL — no frameworks required.
