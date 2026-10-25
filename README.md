# UPang-Email-Concerns

PHINMA UPang Urdaneta Student Email Concerns. Students submit PHINMAED email account concerns and track them; the admin office manages them from a separate console.

## Features

**Student portal** (`/`)
- Concern form: type picker (Reset Password, 2FA, Others), description (required for Others), name, student ID, PHINMAED and personal email
- Inline validation (PHINMAED domain check, personal email must differ)
- Reference number on submit (e.g. `EC-251025-K7QP`), with copy button
- Duplicate guard: a student can't open a second concern of the same type while one is still open
- Status tracker (`/track.php`): reference number + student ID shows a timeline and the office's response

**Admin console** (`/admin/`, not linked from the student portal)
- Login with rate limiting (5 failed attempts per IP locks for 10 minutes), CSRF-protected, POST-only sign out
- Dashboard: open/this week/resolved counts, average time to resolve, 14-day submission chart, breakdown by type, oldest open concerns
- Concern list with views (All, Pending, In progress, Resolved, This week), live search, type and date filters, sort, pagination
- Detail drawer: full info, copy buttons, status (Pending / In Progress / Resolved), response note shown to the student, "Email student" shortcut, delete
- Bulk select: mark in progress, resolve, reopen, delete
- CSV export of the current filter (Excel-safe)

## Structure

```
index.php, track.php     Student portal
api/                     Public endpoints: submit_concern, track, setup_database
admin/                   Admin pages (login, dashboard, concerns, export, logout)
admin/api/               Admin-only endpoints: concerns, update, delete
admin/partials/          Admin layout
includes/                Shared bootstrap (DB, session, CSRF, helpers), student layout
assets/css, assets/js    Styles and scripts
config/                  db.php, admin.php (git-ignored)
public/                  Logos and images
```

All admin endpoints require a signed-in session, and every write requires a CSRF token. `config/`, `includes/` and `admin/partials/` are blocked from direct web access by `.htaccess`. Paths are relative, so the folder can have any name.

## Setup

1. Install XAMPP/WAMP (PHP 8.1+ and MySQL/MariaDB).
2. Put the project folder in `htdocs`.
3. Set database credentials in `config/db.php`.
4. Create or upgrade the database, either:
   - `php api/setup_database.php` from the project folder, or
   - open `http://localhost/<folder>/api/setup_database.php` (only works from the same machine).

   Safe to re-run. Upgrades older tables: adds reference numbers, description, response note, `In Progress` status and timestamps.
5. Set the admin login (below).
6. Students: `http://localhost/<folder>/`. Admin: `http://localhost/<folder>/admin/`.

## Admin login

Credentials are not stored in the code. Copy `config/admin.example.php` to `config/admin.php` (git-ignored), set a username, and generate a password hash:

```
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Paste the hash into `password_hash` in `config/admin.php`.

## Tech stack

PHP (PDO), MySQL/MariaDB, vanilla JS and CSS, Poppins and Material Symbols from Google Fonts.
