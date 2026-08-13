# Support Training Hub

Internal training platform for new support employees learning the PILOT
platform. Laravel 13 + Filament 5 admin at `/admin`, Vue 3 + Inertia employee
portal, Tailwind 4, PostgreSQL 17.

The look follows academy.pilot-gps.com — brand `#1463ff`, navy `#0a2540`,
green `#19a86b`, Inter, flat white cards on slate-50 — so the internal academy
reads as the same product family as the customer-facing one.

Converted from the static HTML/CSS/JS prototype in [`../pages`](../pages),
which is kept untouched as the visual reference. The audit and the
feature-by-feature mapping are in
[`../docs/IMPLEMENTATION_MAP.md`](../docs/IMPLEMENTATION_MAP.md).

---

## Running it

### Docker (recommended)

```bash
cd ..                 # the repository root, where docker-compose.yml lives
docker compose up -d
docker compose exec app php artisan migrate --seed
```

| Service | | |
| --- | --- | --- |
| `app` | http://localhost:8080 | Laravel, `artisan serve` |
| `db` | localhost:5433 | PostgreSQL 17 |
| `queue` | — | `queue:work`, renders certificates and sends mail |

The `queue` service is not optional in practice: certificate PDFs and
notification emails are dispatched to a queue, so without a worker draining it
a completed course never produces a downloadable certificate.

Application config for the containers lives in
[`../docker/app.env`](../docker/app.env), mounted over `.env`. It is
deliberately **not** in docker-compose's `environment:` block — see the note in
that file for why.

### On the host

The app needs `pdo_pgsql`, which is often not enabled in a stock Windows PHP
build, and `php.ini` usually sits in a directory that needs an elevated shell to
write to. Rather than changing PHP globally — which would break other projects
sharing that install — this project ships its own ini and points PHP at it with
`PHPRC`:

```powershell
.\setup-php-ini.ps1   # once: generates platform/php.ini with the extensions on
.\dev.ps1             # starts PostgreSQL, migrations, artisan serve, queue, vite
```

---

## Accounts

One per role, created by `UserSeeder`. All three use the password `password`;
they exist for local development only.

| Email | Role | Sees |
| --- | --- | --- |
| `admin@pilot.test` | Admin | Everything |
| `manager@pilot.test` | Manager | Technical Support only |
| `employee@pilot.test` | Employee | Their own training |

---

## Tests

```bash
docker compose exec app php artisan test
```

They run against `pilot_lms_testing` in the same container, configured by
[`.env.testing`](.env.testing). Note that `.env.testing` *replaces* `.env`
rather than layering on it, so anything needed to boot — `APP_KEY` in
particular — has to be present in it.

`TestCase` refuses to run if the database name does not contain `test`.
`RefreshDatabase` runs `migrate:fresh`, and a misconfigured environment
pointing at the development database will destroy it silently otherwise.

PostgreSQL rather than SQLite in memory, on purpose: the schema uses `jsonb`
and the course search uses `ILIKE`, neither of which SQLite reproduces
faithfully.

---

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

- **Quiz answer keys never reach the browser.** `QuizController::start()`
  assembles the payload by hand rather than serialising the model, so
  `is_correct` and `explanation` cannot leak. `QuizOption` also hides
  `is_correct` at the model level as a backstop. There is a test asserting the
  string `is_correct` is absent from the taking-a-quiz response.
- **The pass mark is snapshotted onto each attempt.** Raising a quiz's
  `passing_score` cannot retroactively fail somebody who already sat it.
- **Uploads and certificates go to the `private` disk**, which has no URL.
  They are served only through policy-checked controllers.
- **`course_progress` is a rollup**, recalculated on every lesson tick and
  every graded attempt. The dashboard and every report read it directly rather
  than recomputing percentages.
- **`lessons.course_id` is denormalised** from the module. `Lesson::saving()`
  keeps it in sync; do not set it by hand.
- **Assignment rules are rows, not code.** "Operations gets Fleet Safety
  Training" is a record in `assignment_rules`, evaluated live so moving
  somebody between departments changes what they are assigned.

### Scheduled work

Needs `php artisan schedule:work` in development, or a cron entry calling
`schedule:run` in production.

| Command | When | |
| --- | --- | --- |
| `training:send-reminders` | Weekdays 08:00 | Due-soon and overdue notifications |
| `training:sync-assignments` | Hourly | Enrols anyone the rules now match |

Both take `--dry-run` / are idempotent, so they are safe to run by hand.
