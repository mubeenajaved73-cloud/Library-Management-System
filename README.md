# LibraTrack — Library Management System

Semester project (Software Verification & Validation) — Task 1 + Task 2.
Built with **PHP + MySQL** (no framework — plain PHP, easy to explain in a viva).

## Setup (XAMPP / WAMP / MAMP or any local PHP+MySQL server)

1. Copy the whole `library-system` folder into your server's web root
   (e.g. `htdocs/` for XAMPP).
2. Start Apache and MySQL from your control panel.
3. Open **phpMyAdmin** (or the MySQL CLI) and import `database.sql`.
   This creates the `library_system` database, all tables, one admin
   account, and 5 sample books.
4. If your MySQL username/password differ from the defaults, edit
   `config/db.php`.
5. Visit `http://localhost/library-system/` in your browser.

## Demo login

| Role  | Email               | Password  |
|-------|---------------------|-----------|
| Admin | admin@library.com   | admin123  |

Register a new account through the **Register** page to get a Member
login, or use the admin panel to view/manage members.

## Project structure

```
library-system/
├── config/db.php          # DB connection
├── includes/               # auth.php, header.php, footer.php
├── assets/css/style.css    # all styling
├── database.sql            # schema + seed data
├── login.php / register.php / logout.php
├── dashboard.php
├── books.php / book_add.php / book_edit.php / book_delete.php / book_view.php
├── members.php / member_edit.php / member_delete.php
├── profile.php
└── transactions.php / transaction_issue.php / transaction_return.php
```

## Modules implemented (Task 2)

- Authentication: Register, Login, Logout, bcrypt password hashing, session-based route protection, session ID regeneration on login
- Dashboard: live stats (total books, available copies, issued count, total members)
- Book Management: Add / View / Edit / Delete / Search — full CRUD against MySQL, with duplicate-ISBN and issued-copy safeguards on edit
- Member Management (admin): View / Edit / Delete / Search members
- Profile: view/edit own info, change password (requires current password)
- Transactions: Issue book / Return book, with automatic available-copy tracking

## Bugs found in Task 4 testing (now fixed)

Testing in Task 4 found 6 bugs, all fixed in this version:

- **Duplicate ISBN on Edit Book** now shows a clear message instead of a generic DB error.
- **Total Copies** can no longer be edited below the number of copies currently issued.
- **Password change** on the Profile page now requires the current password to be verified first.
- **Session ID is regenerated** immediately after login to prevent session fixation.
- **Phone number** fields (Register & Profile) now validate format.
- **Delete Book / Delete Member / Mark Returned** are now POST-only actions protected by a CSRF token, instead of plain GET links.

See Test_Execution_Results_Task4.xlsx and Bug_Report_Task4.xlsx for the original findings.

## Notes for future Tasks 3–5

- Every interactive element (form inputs, buttons, table rows) uses
  predictable `id`/`name` attributes so Selenium (Task 5) can target
  them reliably.
- Validation logic (registration, book add/edit, issue/return) is
  centralized per page, making it easy to write functional/negative/
  boundary test cases against in Task 3.
- The `transactions` table already tracks status (`issued` /
  `overdue` / `returned`), which Task 3–4 test cases can exercise
  directly.
