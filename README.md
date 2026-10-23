# UPang-Email-Concerns

PHINMA UPang Urdaneta Student Email Concerns. A web portal where students submit email-related concerns, and the admin office logs in to review, resolve and manage them.

## Features

**Student side**
- Concern submission form with name, student ID, personal email and PHINMAED email (Material Design Web Components)

**Admin side**
- Login (`login.php`, `authenticate.php`)
- Dashboard of all concerns
- Pending concerns
- This week's concerns
- History of resolved concerns
- Manage concerns
- Search, update status, delete

## Tech stack

- PHP, MySQL (PDO)
- Vanilla JS, CSS
- Material Design Web Components, Poppins/Roboto fonts

## Structure

```
api/              Endpoints: submit, get, search, update status, delete, DB setup/test
config/db.php     PDO connection
navigationItems/  Admin pages (dashboard, concerns, thisweek, history, manage)
public/           Logos and images
index.php         Student form
login.php         Admin login
```

## Database

Database `users`, table `student_concerns`: first, middle and last name, student ID, personal email, PHINMAED email, concern text, submission date, status (`Pending` / `Resolved`).

`api/setup_database.php` is safe to re-run. It also upgrades tables from older versions (adds `phinmaed_email`, makes `middle_name` optional).

## Setup

1. Install XAMPP/WAMP (PHP + MySQL).
2. Put the project folder in `htdocs` as `UPang-Email-Concerns`.
3. Set credentials in `config/db.php`.
4. Open `http://localhost/UPang-Email-Concerns/api/setup_database.php` once to create the database and table.
5. Set the admin login (see below).
6. Open `http://localhost/UPang-Email-Concerns/` for the form, `/login.php` for admin.

## Admin login

Credentials are not stored in the code. Copy `config/admin.example.php` to `config/admin.php` (git-ignored), set a username, and generate a password hash:

```
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

Paste the hash into `password_hash` in `config/admin.php`.
