# WebProject-v2

Student concerns system. Students submit concerns through a form. Admins log in to review, resolve and manage them.

## Features

**Student side**
- Concern submission form (Material Design Web Components)

**Admin side**
- Login (`login.php`, `authenticate.php`)
- Dashboard
- Pending concerns
- This week's concerns
- Resolved history
- Manage students
- Search, update status, delete concerns

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

Database `users`, table `student_concerns`: name fields, student ID, email, concern text, submission date, status (`Pending` / `Resolved`).

## Setup

1. Install XAMPP/WAMP (PHP + MySQL).
2. Set credentials in `config/db.php`.
3. Open `http://localhost/WebProject-v2/api/setup_database.php` once to create the database and table.
4. Open `http://localhost/WebProject-v2/` for the form, `/login.php` for admin.
