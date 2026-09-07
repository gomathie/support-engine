# Support Training Hub

A training platform for support staff. Trainees work through structured curriculum tracks — 1st-line support (2 weeks), admin panel (3 days), and support skills modules — and are then **assessed** on them.

> **Completion is not competence.** Ticking off a lesson records that somebody read it. Whether they can do the job is decided by a graded exam and a practical task marked against a rubric — and only that awards a competency level. The two are deliberately separate, and the work in [`docs/COMPETENCY_IMPLEMENTATION_PLAN.md`](docs/COMPETENCY_IMPLEMENTATION_PLAN.md) exists to keep them that way.

## What it does

| Feature | Description |
| --- | --- |
| **Training Tracker** | Curriculum tracks broken into modules and lessons, with checkbox completion |
| **Lesson types** | Rich text, PDF, image, external link, download — plus **video**, either embedded (YouTube / Vimeo) or uploaded and streamed from private storage |
| **Progress Dashboard** | Circular gauge, per-course progress bars and completion counters |
| **Quiz Engine** | Timed assessments with server-side scoring, pass marks, attempt history, and written answers marked by a person |
| **Practical tasks** | Real work, submitted with evidence and marked against a four-criterion rubric — with optional independent double-marking |
| **Competency ladder** | Levels held *per area of expertise*, awarded automatically when the courses a level requires are complete |
| **Cohorts** | Every trainee has one trainer, who marks their work; reassignments are logged with a reason |
| **Support Panel** | 13 diagnostic decision trees (102 checks), a priority matrix, and a case-note generator for live calls |
| **Certificates** | Auto-generated PDF certificates on course completion, with public verification links |
| **Admin Portal** | Filament admin for courses, lessons, practicals, people, cohorts, enrollments, assignment rules and reporting |

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.3) |
| Admin | Filament 5 |
| Frontend | Vue 3 + Inertia 3 |
| Styling | Tailwind CSS 4 — academy palette (`#1463ff` brand, `#0a2540` navy, `#19a86b` green) |
| Database | PostgreSQL 17 |
| Auth | Laravel session auth + Spatie Permission (Admin, Trainer, Trainee) |
| PDF | barryvdh/laravel-dompdf |

## User Roles

1. **Admin** — creates users, assigns roles, authors content, decides who trains whom, sees everything.
2. **Trainer** — authors content and **marks the work of their own assigned cohort**.
3. **Trainee** — takes courses, sits assessments, submits practical tasks, earns levels and certificates.

The roles describe what somebody does on the training portal, not where they sit on an org chart — a Trainer is not necessarily anybody's line manager.

Authorization is enforced server-side by policies, not by hiding links. Two boundaries are worth knowing:

- **Reading and marking are separate rights.** A Trainer with `transcripts.view-all` may read any trainee's record, but may only *mark* the trainees assigned to them. These used to be one department-shaped rule and are now two, because they answer different questions.
- **Nobody marks their own paper.** `grade`, `override` and `submit` are excluded from the administrator bypass in the attempt and submission policies, so the rule holds for everyone including an Admin.

Every Trainer capability is a named permission on the role rather than an implied tier, so "can build quizzes" is separable from "can assign trainees" — which is what prevents accidental privilege creep. Grants are per **role**, not per person: `spatie/laravel-permission` has no per-user deny, so revoking a permission from one Trainer would take it from all of them.

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
| Trainer | `manager@pilot.test` | `password` |
| Trainee | `employee@pilot.test` | `password` |

> The two addresses still read `manager@` and `employee@`. The roles were renamed in place so existing assignments survived; the seeded email addresses were left alone so the known logins kept working. Cosmetic, but worth not being confused by.

Sign in at `/login` — there is one login page for everyone, and admins and trainers are redirected into `/admin` automatically.

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
│   ├── Enums/             ← Role, CourseStatus, LessonType, RubricCriterion, …
│   ├── Filament/          ← Admin resources, relation managers, widgets, reports
│   ├── Http/              ← Controllers, form requests, middleware
│   ├── Jobs/              ← RenderCertificatePdf
│   ├── Models/            ← Eloquent models
│   ├── Notifications/     ← CourseAssigned, TrainingDue
│   └── Policies/          ← Authorization
├── database/
│   ├── migrations/        ← 32 migrations
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
| `Quiz\GradeWrittenAnswer` | One examiner marking one written answer |
| `Quiz\FinaliseQuizAttempt` | Turns a fully-marked attempt into a score and a verdict |
| `Quiz\OverrideAttemptResult` | Overturns a pass or fail, with a reason on the record |
| `Practical\SubmitPracticalTask` | Draft, save, hand in |
| `Practical\GradePracticalSubmission` | Marking against the four-criterion rubric |
| `Practical\FinalisePracticalSubmission` | Reconciles one or two independent gradings into an outcome |
| `Competency\AwardCompetencyLevel` | Decides whether a level has been earned, and records it |
| `Cohorts\AssignTrainee` | The single place a trainee's trainer changes |
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
- **A video URL is never stored or rendered.** `Support\Video\VideoEmbed` parses the pasted URL to a provider and an id, and the embed URL is rebuilt from a fixed template — an iframe `src` must never be author-controlled text. Uploaded video is served from the private disk through a policy-checked route that answers `Range` requests with 206, because a 200 makes the scrubber useless on a large file.
- **A pass/fail override is stored, not written into `passed` and forgotten.** Re-marking a written answer re-runs finalisation, and a human decision to uphold an appeal must survive it. The score is never rewritten either — *"scored 30% but passed on appeal"* is the truth.
- **The practical rubric threshold is not a total.** Passing needs 3+ on Correctness, 2+ on every other criterion, *and* 10+ overall. `4/4/2/0` sums to 10 and still fails.
- **Two markers who disagree are not averaged.** A split verdict leaves the submission unsettled and flagged, because the disagreement is the signal that calibration exists to surface.
- **A level is held per competency area, not globally**, and a (level, area) pair with no configured requirements is never awarded — an unconfigured level is not an automatic pass.

### Scheduled work

Needs `php artisan schedule:work` in development, or a cron entry calling `schedule:run` in production.

| Command | When | |
| --- | --- | --- |
| `training:send-reminders` | Weekdays 08:00 | Due-soon and overdue notifications |
| `training:sync-assignments` | Hourly | Enrols anyone the rules now match |

Both are idempotent and `training:send-reminders` takes `--dry-run`, so they are safe to run by hand.

## How Progress Tracking Works

1. A trainee signs in and sees assigned courses, plus a **next lesson** call to action.
2. Each course contains modules, and each module holds lessons.
3. They study the material and tick off each completed lesson.
4. That posts to `/courses/{course}/lessons/{lesson}/complete`, creating a `LessonProgress` row.
5. `RecalculateCourseProgress` recomputes the rollup — counters, percentage, status — and, if the course is now complete, issues a certificate and evaluates any competency level it may have earned.
6. Trainers and admins monitor it through the Filament report, filterable by department, course, status and date, with CSV export.

`course_progress` is the single authority on "how far along is this person". Nothing in Vue is allowed to disagree with it.

## How Assessment Works

Separate from the above, and deliberately so — completion says a lesson was read, assessment says the job can be done.

1. The trainee sits the course's **final exam**. Multiple-choice questions are marked instantly; written answers park the attempt at `pending_review`.
2. Their **trainer** — the one person assigned to them — marks the written answers, with the marking guidance on screen beside the answer.
3. Where the course has a **practical task**, they do the work, write it up, attach evidence, and hand it in. It is marked against the four-criterion rubric (Correctness · Method · Verification · Communication, 0–4 each). A score of 2 or below needs a written reason.
4. On a task flagged for calibration, **two trainers mark independently**. If they disagree on the verdict the submission does not settle — it is flagged for them to reconcile rather than averaged.
5. Passing the exam completes the course, which issues a certificate and runs `AwardCompetencyLevel`. A **level in an area** is granted once *every* course that (level, area) pair requires is complete, and never before the rung below it in the same area.
6. An Admin or the assigned Trainer can **override** a pass or fail, with a mandatory reason. The score is left as it was, the override survives any later re-marking, and any level that attempt was the evidence for is revoked.

Every step of that leaves an audit row: `grade_override_logs` for overturned results, `trainer_assignment_logs` for cohort changes.

## Documentation

- [PILOT GPS Platform Docs](https://docs.pilot-gps.com/)
- [Competency Implementation Plan](docs/COMPETENCY_IMPLEMENTATION_PLAN.md) — the phased plan for moving from checkbox completion to assessed competency
- [Competency Delivery Log](docs/COMPETENCY_DELIVERY_LOG.md) — what has actually shipped against that plan, and the decisions behind it
- [Implementation Map](docs/IMPLEMENTATION_MAP.md) — prototype audit and architecture mapping
- [1st-Line Support Training Plan](<docs/PILOT%20System%20Training%20Plan%20for%201st-Line%20Support%20(2%20Weeks).md>)
- [Admin Panel Training Plan](docs/Training%20Plan%20for%203%20Days_%20PILOT%20Administrative%20Panel.md)
