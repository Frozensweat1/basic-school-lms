# Basic School LMS

Basic School LMS is a school management and learning platform built with Laravel and Livewire. It combines a dynamic public school website with a secure, role-based LMS for administrators, teachers, students, and parents/guardians.

The application is intended for basic and primary schools that want one system for school records, teaching and learning, assessments, communication, reporting, and public-facing content.

## Main capabilities

### Public school website

- Responsive public pages for home, about, academics, admissions, teachers, news, events, gallery, and contact.
- CMS-managed page copy, hero images, programmes, statistics, values, admissions steps, and requirements.
- Configurable school name, logo, colours, contact details, location, and social links.
- School-name initials are used as a fallback brand mark when no logo has been uploaded.
- Featured teachers, news articles, event detail pages, galleries, enquiries, and newsletter subscriptions.
- Search-engine metadata, structured data, `robots.txt`, and a generated sitemap.

### School administration

- Academic years, terms, classes, streams, subjects, and class-subject assignments.
- Student, teacher, parent/guardian, user, role, and permission management.
- Manual student registration and bulk student import workflows.
- School branding, operational preferences, website content, backups, and controlled database reset.

### Teaching and learning

- Topics, lessons, rich lesson content, learning resources, and lesson-progress tracking.
- Role-specific lesson views for teachers, students, parents, and administrators.
- Assignments with attachments, submissions, grading, feedback, and submission tracking.
- Subject/topic/lesson-aware question bank.
- Quizzes with question management, scheduled attempts, answers, scoring, and grading.
- Examinations with examination questions and score entry.

### Assessment and school operations

- Assessment components, assessments, assessment scores, grading scales, and subject results.
- Report-card generation, review, publication, student/parent access, and print-friendly views.
- Attendance entry, summaries, and role-specific attendance views.
- Schedule periods, timetable management, calendar/grid views, and automatic conflict-aware timetable generation.
- Announcements, attachments, queued notifications, and an in-app notification centre.
- Role-specific dashboards with attendance and academic-performance charts.

## User roles

The application uses Spatie Laravel Permission and model policies to enforce access boundaries.

| Role | Typical access |
| --- | --- |
| `super_admin` | Full system administration, roles, settings, backups, and database maintenance |
| `school_admin` | School setup, people, academic records, reports, communications, and website CMS |
| `teacher` | Assigned teaching content, attendance, assignments, quizzes, assessments, and grading |
| `student` | Own lessons, progress, assignments, quizzes, attendance, results, and reports |
| `parent` | Linked wards' learning progress, attendance, timetables, results, and reports |

## Technology stack

- PHP 8.2 or newer
- Laravel 12
- Livewire 4
- Laravel Fortify authentication
- Spatie Laravel Permission
- Tailwind CSS 4 and Vite 7
- Alpine.js through Livewire
- Chart.js dashboards
- Quill rich-text editing
- SweetAlert2/Livewire Alert notifications
- SQLite for the default local setup; MySQL is also supported
- Database-backed queues, cache, and sessions by default

## Requirements

Install the following before setting up the project:

- PHP 8.2+ with the common Laravel extensions, including PDO, Mbstring, OpenSSL, Fileinfo, DOM, XML, Ctype, Tokenizer, and ZIP. Enable `pdo_sqlite` for SQLite or `pdo_mysql` for MySQL. ZIP is required for XLSX student imports.
- Composer 2.x.
- Node.js 20.19+ or 22.12+ and npm.
- SQLite, or MySQL 8+ if you prefer MySQL.
- Git.

For Windows/XAMPP, add `C:\xampp\php` to `PATH` or replace `php` in the commands below with `C:\xampp\php\php.exe`.

## Local setup

### 1. Clone and install PHP dependencies

```bash
git clone <repository-url> basic-school-lms
cd basic-school-lms
composer install
```

### 2. Create the environment file

On macOS/Linux:

```bash
cp .env.example .env
```

On Windows Command Prompt:

```bat
copy .env.example .env
```

Then generate the application key:

```bash
php artisan key:generate
```

At minimum, update these values in `.env`:

```dotenv
APP_NAME="Basic School LMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

### 3. Configure the database

#### Option A: SQLite (quickest local setup)

The example environment already uses SQLite. Create the database file with this cross-platform PHP command:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Keep this in `.env`:

```dotenv
DB_CONNECTION=sqlite
```

#### Option B: MySQL

Create an empty database, then replace the SQLite setting with your MySQL connection:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basic_school_lms
DB_USERNAME=root
DB_PASSWORD=
```

Use a dedicated, least-privileged database user outside local development.

### 4. Run migrations and seed demo data

```bash
php artisan migrate --seed
```

The main seeder creates roles and permissions, school structure, academic data, users for every role, lessons, assignments, quizzes, examinations, assessments, attendance, timetables, reports, notifications, and public website content.

Demo seeding is intended for local development and evaluation. Do not run it in production.

### 5. Link public uploads

```bash
php artisan storage:link
```

This is required for website logos, hero images, news/event images, teacher photos, and gallery images stored on the `public` disk.

### 6. Install and build frontend assets

```bash
npm install
npm run build
```

If PowerShell blocks `npm.ps1`, run the Windows command shim instead:

```powershell
npm.cmd install
npm.cmd run build
```

### 7. Start the application

The provided Composer development command starts the Laravel server, queue listener, log viewer, and Vite development server together:

```bash
composer run dev
```

Then open:

- Public website: `http://127.0.0.1:8000`
- Portal login: `http://127.0.0.1:8000/login`

Alternatively, use separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

```bash
php artisan queue:work --tries=3
```

The queue worker is important for queued announcement and notification delivery.

## Demo accounts

After `php artisan migrate --seed`, the following representative accounts are available:

| Role | Email | Password |
| --- | --- | --- |
| Super administrator | `admin1@brightstar.test` | `password` |
| School administrator | `test@example.com` | `password` |
| Teacher | `ama.mensah@brightstar.test` | `password` |
| Student | `kojo.owusu@brightstar.test` | `password` |
| Parent/guardian | `adwoa.owusu@brightstar.test` | `password` |

Additional numbered demo accounts are seeded for administrators, teachers, students, and parents. These credentials are development-only and must be removed or changed before any deployment.

## Seeders and database refresh

Run all configured seeders:

```bash
php artisan db:seed
```

Rebuild a local database from scratch:

```bash
php artisan migrate:fresh --seed
```

`migrate:fresh` deletes every table and all existing data. Never run it against a database containing information you need to preserve.

For a setup without the full demo dataset, run only the foundation seeders and create the first administrator yourself:

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=SchoolSetupSeeder
```

## Environment and service configuration

### Queue

The default configuration uses the database queue:

```dotenv
QUEUE_CONNECTION=database
```

Keep a worker running during development and configure Supervisor, systemd, or an equivalent process manager in production:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

### Email

Local email uses the log driver, so messages are written to `storage/logs/laravel.log`:

```dotenv
MAIL_MAILER=log
```

Configure a real SMTP or transactional mail provider in production and set `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`.

### Files

Private assignment submissions and learning files use protected application storage. Public website media uses the `public` disk. For a multi-server production deployment, configure an S3-compatible filesystem and review all private-download authorization rules before launch.

### Cache and sessions

The default local environment uses database-backed cache and sessions. Their tables are included in the migrations. Redis can be configured for production cache and queues when required.

After changing `.env`, clear stale configuration:

```bash
php artisan optimize:clear
```

## Testing and code quality

Run the complete automated test suite:

```bash
composer test
```

Or run Laravel tests directly:

```bash
php artisan test
```

Run one test class or method:

```bash
php artisan test --filter=QuizCrudTest
```

Format PHP code with Laravel Pint:

```bash
./vendor/bin/pint
```

Verify the production frontend bundle:

```bash
npm run build
```

Tests use an in-memory SQLite database, an array cache/session, synchronous queues, and the array mail driver as configured in `phpunit.xml`.

## Useful application paths

```text
app/Livewire/Website/             Public website components
app/Livewire/LMS/                 Authenticated role-based LMS components
app/Models/                       Domain and CMS models
app/Policies/                     Authorization policies
app/Services/                     Reporting and timetable services
app/Support/                      Shared application services
database/migrations/              Database schema
database/seeders/                 Foundation, demo, and website seeders
resources/views/livewire/website/ Public website views
resources/views/livewire/lms/     LMS views
resources/views/components/       Reusable Blade/UI components
resources/css/app.css             Tailwind and application styles
resources/js/                     LMS and public website entry points
routes/web.php                    Public, authentication, and LMS routes
tests/Feature/                    Feature and authorization tests
```

## Backups and database reset

Administrators can create a JSON data backup from **LMS Settings → Data maintenance**. Backups are written to the local disk under `storage/app/private/backups` and downloaded through the settings screen.

Only a super administrator can run the in-app database reset. It deletes school data and non-super-admin users while preserving existing super administrator accounts. Treat both operations as administrative safeguards, not as a replacement for encrypted, automated, off-site production backups.

## Production deployment checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a correct HTTPS `APP_URL`, and a strong `APP_KEY`.
- Do not run demo seeders and replace every development credential.
- Point the web server document root to the project's `public` directory.
- Install optimized PHP dependencies with `composer install --no-dev --optimize-autoloader`.
- Install deterministic frontend dependencies with `npm ci` and run `npm run build`.
- Run `php artisan migrate --force` during a controlled deployment.
- Run `php artisan storage:link` or configure the production object-storage disk.
- Configure a persistent queue worker and restart it after deployment with `php artisan queue:restart`.
- Configure real mail, cache, session, and filesystem services.
- Ensure `storage` and `bootstrap/cache` are writable by the web-server user.
- Enable HTTPS, secure cookies, monitoring, log rotation, and automated off-site backups.
- Cache stable framework configuration with `php artisan config:cache` and views with `php artisan view:cache` after deployment.

## Troubleshooting

### `php` is not recognized on Windows

Use the XAMPP executable explicitly:

```powershell
C:\xampp\php\php.exe artisan migrate --seed
```

### Frontend styles or scripts are missing

Run `npm install` and `npm run build`, or keep `npm run dev` running while developing. Then clear the browser cache.

### Uploaded public images return 404

Run `php artisan storage:link`, confirm `APP_URL`, and verify write permissions for `storage/app/public` and `public/storage`.

### Jobs or notifications remain pending

Confirm `QUEUE_CONNECTION`, then run `php artisan queue:work`. Inspect failed jobs with:

```bash
php artisan queue:failed
```

### Environment changes do not take effect

```bash
php artisan optimize:clear
```

### Roles or permissions appear stale

```bash
php artisan permission:cache-reset
```

## Project status

The project is under active development. Review migrations, authorization policies, tests, backup strategy, mail delivery, object storage, and environment-specific security before using it with real student data.
