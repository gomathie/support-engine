# Support Training Hub

An interactive onboarding platform for new support employees. Trainees work through structured curriculum tracks — 1st-line support (2 weeks), admin panel (3 days), and support skills modules — **ticking checkboxes** as they complete each lesson to show their progress in real time.

## What it does

| Feature | Description |
| --- | --- |
| **Training Tracker** | Curriculum tracks broken into modules and lessons, with interactive checkbox completion |
| **Progress Dashboard** | Circular gauge, per-course progress bars and completion counters — all driven by checked-off lessons |
| **Support Panel** | 13 diagnostic decision trees (102 checks), a priority matrix, and a case-note generator for live calls |
| **Quiz Engine** | Timed assessments with server-side scoring, pass marks and attempt history |
| **Certificates** | Auto-generated PDF certificates on course completion, with public verification links |
| **Admin Portal** | Filament admin for courses, employees, departments, enrollments, assignment rules and reporting |

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.3) |
| Admin | Filament 5 |
| Frontend | Vue 3 + Inertia 3 |
| Styling | Tailwind CSS 4 — academy palette (`#1463ff` brand, `#0a2540` navy, `#19a86b` green) |
| Database | PostgreSQL 17 |
| Auth | Laravel session auth + Spatie Permission (Admin, Manager, Employee) |
| PDF | barryvdh/laravel-dompdf |

## User Roles

1. **Admin** — creates users, assigns roles, authors all content, sees every department.
2. **Manager** — sees only the departments they run; assigns training and reads their reports.
3. **Employee** — enrols in courses, ticks off progress, takes assessments, earns certificates.

Authorization is enforced server-side by policies, not by hiding links. Managers are scoped in SQL as well as per-record, so they cannot learn the shape of departments they do not run.

## Quick Start (Docker)

```bash
docker compose up --build -d
docker compose exec app php artisan migrate --seed
```

| Service | | |
| --- | --- | --- |
| `app` | http://localhost:8080 | Laravel |
| `db` | localhost:5433 | PostgreSQL 17 |
| `queue` | — | `queue:work` — renders certificates, sends mail |

The `queue` service is not optional in practice: certificate PDFs and notification emails are dispatched to a queue, so without a worker draining it a completed course never produces a downloadable certificate.

Container config lives in [`docker/app.env`](docker/app.env), mounted over `.env`. It is deliberately **not** in docker-compose's `environment:` block — variables set there land in `$_SERVER`, which Laravel's env repository reads *before* `$_ENV`, so they silently outrank `phpunit.xml`. See the note in that file.

### Test Accounts (seeded)

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@pilot.test` | `password` |
| Manager | `manager@pilot.test` | `password` |
| Employee | `employee@pilot.test` | `password` |

Sign in at `/login` — there is one login page for everyone, and admins and managers are redirected into `/admin` automatically.

## Local Development (without Docker)

The app needs `pdo_pgsql`, which is often not enabled in a stock Windows PHP build, and `php.ini` usually sits in a directory needing an elevated shell to write to. Rather than changing PHP globally — which would break other projects sharing that install — this project generates its own ini and points PHP at it with `PHPRC`:

```powershell
.\setup-php-ini.ps1   # once: generates ./php.ini with the extensions enabled
.\dev.ps1             # PostgreSQL, migrations, artisan serve, queue, vite
```

### If the app serves a blank page

Check for `public/hot`:

```bash
rm -f public/hot
```

Vite writes that file while the dev server runs and deletes it on a clean exit. If it is killed abruptly — Ctrl+C in the wrong place, a closed terminal, a crash — the file survives, and `@vite` then points every page at `http://[::1]:5173` instead of the built assets. Nothing is listening there, so you get a blank page with no CSS, no JS, and no error explaining why. `dev.ps1` now clears it on exit, but a bare `npm run dev` does not.

## Running Tests

```bash
docker compose exec app php artisan test
```

Tests run against `pilot_lms_testing`, configured by [`.env.testing`](.env.testing). Note that `.env.testing` **replaces** `.env` rather than layering on it, so anything needed to boot — `APP_KEY` in particular — has to be present in it.

`TestCase` refuses to run unless the database name contains `test`. `RefreshDatabase` runs `migrate:fresh`, and a misconfigured environment pointing at the development database would destroy it silently otherwise.

PostgreSQL rather than SQLite in memory, on purpose: the schema uses `jsonb` and the course search uses `ILIKE`, neither of which SQLite reproduces faithfully.

## Project Structure

```
support-engine/
├── app/
│   ├── Actions/           ← Business logic (RecalculateCourseProgress, GradeQuizAttempt, …)
│   ├── Console/Commands/  ← training:send-reminders, training:sync-assignments
│   ├── Enums/             ← Role, CourseStatus, LessonType, ProgressStatus, …
│   ├── Filament/          ← Admin resources, relation managers, widgets, reports
│   ├── Http/              ← Controllers, form requests, middleware
│   ├── Jobs/              ← RenderCertificatePdf
│   ├── Models/            ← Eloquent models
│   ├── Notifications/     ← CourseAssigned, TrainingDue
│   └── Policies/          ← Authorization
├── database/
│   ├── migrations/        ← 24 migrations
│   └── seeders/           ← Roles, users, curriculum, diagnostic trees, assignment rules
├── resources/
│   ├── css/app.css        ← Tailwind theme + design tokens
│   ├── js/                ← Vue components, layouts and Inertia pages
│   └── views/             ← Inertia root + certificate PDF template
├── tests/Feature/         ← Auth, authorization, progress, quizzes, certificates, admin
├── docker/                ← app.env, init-test-db.sql
├── docs/                  ← Implementation map + source training plans
├── docker-compose.yml     ← app + db + queue
└── Dockerfile             ← PHP 8.3 + Node 22
```

## Architecture

Business logic lives in `app/Actions`, never in Vue components:

| | |
| --- | --- |
| `Progress\RecalculateCourseProgress` | The only place a course percentage is decided |
| `Progress\CompleteLesson` | Ticking a lesson off, and undoing it |
| `Quiz\StartQuizAttempt` | Starts or resumes an attempt, enforces the attempt limit |
| `Quiz\GradeQuizAttempt` | Scores server-side; the browser only ever submits option ids |
| `Enrollment\EnrollEmployee` | Assignment, restoring revoked enrollments, due dates |
| `Enrollment\SyncAssignmentRules` | Turns assignment rules into enrollments |
| `Certificates\IssueCertificate` | Idempotent issuance; queues the PDF render |
| `Support\BuildCaseNote` | Rebuilds the Support Panel case note |

### Things worth knowing before changing them

- **Quiz answer keys never reach the browser.** `QuizController::start()` assembles the payload by hand rather than serialising the model, so `is_correct` and `explanation` cannot leak. `QuizOption` also hides `is_correct` at the model level as a backstop, and a test asserts the string is absent from the response.
- **The pass mark is snapshotted onto each attempt.** Raising a quiz's `passing_score` cannot retroactively fail somebody who already sat it.
- **A quiz's scope is derived, not stored.** Final exam / module test / lesson check comes from whether `course_module_id` and `lesson_id` are set, so the two cannot drift apart.
- **Uploads and certificates go to the `private` disk**, which has no URL. They are served only through policy-checked controllers.
- **`course_progress` is a rollup**, recalculated on every lesson tick and every graded attempt. The dashboard and every report read it directly rather than recomputing percentages.
- **`lessons.course_id` is denormalised** from the module. `Lesson::saving()` keeps it in sync; do not set it by hand.
- **Assignment rules are rows, not code.** "Operations gets Fleet Safety Training" is a record in `assignment_rules`, evaluated live, so moving somebody between departments changes what they are assigned.
- **Filament's published assets are committed** (`public/css|js|fonts/filament`). The Dockerfile does not run `filament:assets`, so deleting them leaves the admin panel unstyled in a fresh build.

### Scheduled work

Needs `php artisan schedule:work` in development, or a cron entry calling `schedule:run` in production.

| Command | When | |
| --- | --- | --- |
| `training:send-reminders` | Weekdays 08:00 | Due-soon and overdue notifications |
| `training:sync-assignments` | Hourly | Enrols anyone the rules now match |

Both are idempotent and `training:send-reminders` takes `--dry-run`, so they are safe to run by hand.

## How Progress Tracking Works

1. Employee signs in and sees assigned courses, plus a **next lesson** call to action.
2. Each course contains modules, and each module holds lessons.
3. The employee studies the material and **ticks the checkbox** on each completed lesson.
4. That posts to `/courses/{course}/lessons/{lesson}/complete`, creating a `LessonProgress` row.
5. `RecalculateCourseProgress` recomputes the rollup — counters, percentage, status — and issues a certificate if the course is now complete.
6. Managers and admins monitor it through the Filament report, filterable by department, course, status and date, with CSV export.

## Documentation

- [PILOT GPS Platform Docs](https://docs.pilot-gps.com/)
- [Implementation Map](docs/IMPLEMENTATION_MAP.md) — prototype audit and architecture mapping
- [1st-Line Support Training Plan](<docs/PILOT%20System%20Training%20Plan%20for%201st-Line%20Support%20(2%20Weeks).md>)
- [Admin Panel Training Plan](docs/Training%20Plan%20for%203%20Days_%20PILOT%20Administrative%20Panel.md)
