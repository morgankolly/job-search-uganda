# Database Setup

The application uses a MySQL/MariaDB database named `job_search` (see `DB_DATABASE` in `.env`).

## 1. Start MySQL

Start MySQL from the XAMPP control panel, or via CLI:

```
sudo /Applications/XAMPP/xamppfiles/xampp startmysql
```

## 2. Create the database and import the schema

```
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e \
  "CREATE DATABASE IF NOT EXISTS job_search CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

/Applications/XAMPP/xamppfiles/bin/mysql -u root job_search < database/schema.sql
```

## 3. Seed admin login

The schema seeds one admin account for the `/admin` back office:

- **Email:** `admin@jobsearch.test`
- **Password:** `Admin@123`

Change this password after first login.

## Tables

- `roles`, `users` — authentication / back-office accounts
- `job_categories`, `job_types` — lookups (seeded)
- `jobs` — jobs posted by registered employers
- `guest_jobs` — jobs posted by guests (require admin approval: `pending` → `approved`)
- `applications` — job-seeker applications (`job_source` = `jobs` or `guest`)
- `contact` — contact form messages
