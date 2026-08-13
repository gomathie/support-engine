# PILOT Support Training Academy — Implementation Map

> Originally written as a forward-looking architecture document before any code was written.  
> **This version reflects the current built state as of August 2026.**

---

## 0. Executive Summary

The PILOT Support Training Hub is a **fully operational** multi-user LMS for onboarding new support employees. It has been converted from a three-page static HTML/JS prototype into a production-grade Laravel application backed by PostgreSQL, with a Vue 3 + Inertia employee UI and a Filament admin portal.

**All six delivery phases are complete:**

| Phase | Scope | Status |
| --- | --- | --- |
| 1 | Foundation — auth, roles, schema, layouts | ✅ Done |
| 2 | Course management — courses, modules, lessons, resources | ✅ Done |
| 3 | Employee learning — dashboard, enrollment, completion, progress | ✅ Done |
| 4 | Quiz engine — questions, attempts, scoring, pass/fail | ✅ Done |
| 5 | Reporting — employee, department, course, quiz performance | ✅ Done |
| 6 | Certificates — generation, download, public verification | ✅ Done |

---

## 1. What Was Built vs. the Prototype

### 1.1 Prototype → Production mapping

Every prototype feature has been fully implemented. The table below maps each prototype element to its live counterpart.

| # | Prototype feature | Production implementation | Status |
| --- | --- | --- | --- |
| 1 | `DATA` const — 116 lines of curriculum JS | `courses` / `course_modules` / `lessons` tables, seeded via `TrainingContentSeeder` | ✅ |
| 2 | Checkbox toggle (in-memory `STATE`) | `POST /courses/{c}/lessons/{l}/complete` → `LessonProgress` upsert → `RecalculateCourseProgress` | ✅ |
| 3 | Per-section counter + progress bar | `course_progress.completed_lessons / total_lessons / percentage` — server-side rollup | ✅ |
| 4 | SVG circular progress gauge | `ProgressGauge.vue` — same arc math, driven by real percentages | ✅ |
| 5 | "Saved HH:MM:SS" stamp | `lesson_progress.completed_at` timestamp; Inertia partial reload | ✅ |
| 6 | Reset all progress | `DELETE /my-progress/courses/{course}` gated by policy | ✅ |
| 7 | Section collapse/expand | Vue `ref` UI state — intentionally client-side (presentation only) | ✅ |
| 8 | Landing stats bar (hard-coded) | `DashboardStat.vue` fed by real aggregates from `CourseProgress` | ✅ |
| 9 | 3 static feature cards | `CourseCard.vue` over the employee's enrolled courses | ✅ |
| 10 | Theme toggle | Client-side `data-theme` + `users.theme_preference` persisted to DB | ✅ |
| 11 | Top nav (duplicated across pages) | `EmployeeLayout.vue` — single source, active state from Inertia URL | ✅ |
| 12 | Mobile hamburger | Vue reactive toggle in `EmployeeLayout.vue` | ✅ |
| 13 | Support-panel ARIA tabs | `Tabs.vue` — ARIA semantics preserved | ✅ |
| 14 | `TREES` decision trees (JS const) | `diagnostic_trees` / `diagnostic_steps` tables, Filament-managed | ✅ |
| 15 | Step tri-state (ruled out / cause found) | `support_cases.steps` JSONB, persisted per employee case | ✅ |
| 16 | `MX` priority matrix | Static Vue reference; selection persisted on the case | ✅ |
| 17 | Case-note generator | `Support\BuildCaseNote` action — same output format, server-side | ✅ |
| 18 | Copy to clipboard | Preserved verbatim (`navigator.clipboard` + `execCommand` fallback) | ✅ |
| 19 | "New case" reset | `POST /support-cases` — cases are now a persisted history | ✅ |
| 20 | Skills-module static HTML | `lessons` with `type=rich_text`; content in DB, rendered sanitised | ✅ |
| 21 | TOC + `IntersectionObserver` scroll-spy | `LessonToc.vue` — same observer, headings from lesson content | ✅ |
| 22 | `.std` markers + slide-in drawer | `lesson_annotations` table; `AnnotationDrawer.vue` | ✅ |
| 23 | "Final test" checklist items | Real `quizzes` with questions, options, attempts, scoring, pass marks | ✅ |
| 24 | *(none in prototype)* | Auth, roles, departments, enrollments, assignment rules, certificates, notifications, reporting, Filament admin | ✅ |

### 1.2 What intentionally stays client-side

Per the original spec, these remain Vue presentation state, not server state:

- Section collapse / tab selection / drawer open-closed
- Theme toggle (mirrored to DB to follow the user across devices, applied client-side to avoid FOUC)
- Clipboard copy
- Scroll-spy (`IntersectionObserver`)
- Optimistic checkbox state, reconciled against the server response

---

## 2. Current Codebase Inventory

### 2.1 Backend (Laravel 13, PHP 8.3)

```
app/
├── Actions/
│   ├── Certificates/    IssueCertificate
│   ├── Enrollment/      EnrollEmployee, SyncAssignmentRules
│   ├── Progress/        CompleteLesson, RecalculateCourseProgress
│   ├── Quiz/            StartQuizAttempt, GradeQuizAttempt
│   └── Support/         BuildCaseNote
├── Console/Commands/    training:send-reminders, training:sync-assignments
├── Enums/               Role, CourseStatus, LessonType, ProgressStatus, …
├── Filament/
│   ├── Resources/       Courses, CourseModules, Lessons, Quizzes, Users,
│   │                    Departments, Enrollments, AssignmentRules,
│   │                    DiagnosticTrees, Certificates, Reports
│   ├── Pages/           Dashboard, Reports
│   └── Widgets/         Progress and enrollment summary widgets
├── Http/
│   ├── Controllers/     CourseController, LessonController, QuizController,
│   │                    CertificateController, SupportCaseController,
│   │                    ProgressController, ResourceDownloadController
│   └── Requests/        Form requests for every write operation
├── Jobs/                RenderCertificatePdf
├── Models/              20 Eloquent models (see §2.3)
├── Notifications/       CourseAssigned, TrainingDue
├── Policies/            Course, Lesson, Quiz, Certificate, SupportCase, …
└── Providers/           AppServiceProvider
```

### 2.2 Database (PostgreSQL 17)

**24 migrations**, all applied:

| Migration | Table(s) |
| --- | --- |
| `000000` | `users`, `password_reset_tokens`, `sessions` |
| `000001` | `cache`, `cache_locks` |
| `000002` | `jobs`, `job_batches`, `failed_jobs` |
| `000100` | `departments` |
| `000110` | Employee fields added to `users` (`role`, `department_id`, `theme_preference`, …) |
| `000120` | `courses` |
| `000130` | `course_modules` |
| `000140` | `lessons` |
| `000150` | `lesson_resources` |
| `000160` | `lesson_annotations` |
| `000170` | `quizzes` |
| `000180` | `quiz_questions` |
| `000190` | `quiz_options` |
| `000200` | `quiz_attempts` |
| `000210` | `quiz_answers` |
| `000220` | `assignment_rules` |
| `000230` | `course_enrollments` |
| `000240` | `lesson_progress` |
| `000250` | `course_progress` |
| `000260` | `certificates` |
| `000270` | `diagnostic_trees`, `diagnostic_steps`, `support_cases` |
| `000280` | `category` column added to `diagnostic_trees` |
| `102656` | Spatie permission tables |
| `102657` | `notifications` |

**Schema diagram:**

```
users ──┬── department_id → departments
        ├── role (admin|manager|employee)
        └── [managed via spatie/laravel-permission]

courses ──< course_modules ──< lessons ──< lesson_resources
                                    └──< lesson_annotations
courses ──< quizzes ──< quiz_questions ──< quiz_options
quizzes ──< quiz_attempts ──< quiz_answers

course_enrollments  (user, course, source, due_at, status)
lesson_progress     (user, lesson, completed_at)            ← unique(user, lesson)
course_progress     (user, course, %, status, started/completed_at) ← unique(user, course)
certificates        (user, course, number, score, issued_at, path)  ← unique number
assignment_rules    (course, target_type: user|department|role, target_id)
diagnostic_trees ──< diagnostic_steps
support_cases       (user, tree, fields, steps JSONB, priority)
```

### 2.3 Eloquent Models (20)

`User`, `Department`, `Course`, `CourseModule`, `Lesson`, `LessonResource`, `LessonAnnotation`, `LessonProgress`, `CourseProgress`, `CourseEnrollment`, `AssignmentRule`, `Quiz`, `QuizQuestion`, `QuizOption`, `QuizAttempt`, `QuizAnswer`, `Certificate`, `DiagnosticTree`, `DiagnosticStep`, `SupportCase`

### 2.4 Frontend (Vue 3 + Inertia 3)

```
resources/js/
├── Pages/
│   ├── Auth/            Login.vue, ForgotPassword.vue, ResetPassword.vue
│   ├── Certificates/    Index.vue, Verify.vue
│   ├── Courses/         Index.vue, Show.vue
│   ├── Dashboard.vue
│   ├── Lessons/         Show.vue
│   ├── Profile/         Edit.vue
│   ├── Progress/        Index.vue
│   ├── Quizzes/         Show.vue, Results.vue
│   └── SupportPanel/    Index.vue
└── Components/          Layouts, ProgressGauge, CourseCard, LessonToc,
                         AnnotationDrawer, Tabs, DashboardStat, …
```

### 2.5 Test Suite (10 feature test files)

| Test file | Coverage area |
| --- | --- |
| `AuthenticationTest` | Login, logout, session |
| `AuthorizationTest` | Policy enforcement across all roles |
| `AdminPanelTest` | Filament resource access and CRUD |
| `EnrollmentTest` | Manual and rule-based enrollment |
| `LessonProgressTest` | Checkbox complete/uncomplete, progress rollup |
| `QuizEngineTest` | Attempt start, answer submission, grading, pass/fail |
| `QuizScopeTest` | Answer key never reaches browser |
| `CertificateTest` | Issuance, idempotency, download, public verification |
| `NotificationTest` | `CourseAssigned`, `TrainingDue` |
| `PageRendersTest` | All employee-facing pages return 200 |

---

## 3. Architecture Notes

### 3.1 Stack

| Concern | Choice |
| --- | --- |
| Backend | Laravel 13 (PHP 8.3) |
| Admin | Filament 5 at `/admin` |
| Employee UI | Vue 3 `<script setup>` + Inertia 3 |
| Styling | Tailwind CSS 4 — brand `#1463ff`, navy `#0a2540`, green `#19a86b` |
| Database | PostgreSQL 17 |
| Auth | Laravel session auth + `spatie/laravel-permission` |
| Storage | `private` disk (S3-ready); public URLs never issued for uploads |
| Queues | `database` driver — PDF rendering, notifications |
| PDF | `barryvdh/laravel-dompdf` |

### 3.2 Route split

```
Employee (Inertia)                 Admin (Filament)
──────────────────────────         ─────────────────────────────
/dashboard                         /admin
/courses                           /admin/courses
/courses/{course}                  /admin/course-modules
/courses/{course}/lessons/{l}      /admin/lessons
/courses/{course}/quiz/{quiz}      /admin/quizzes
/my-progress                       /admin/questions
/certificates                      /admin/employees
/certificates/{cert}/download      /admin/departments
/certificates/{cert}/verify        /admin/enrollments
/support-panel                     /admin/assignment-rules
/profile                           /admin/diagnostic-trees
                                   /admin/certificates
                                   /admin/reports
```

### 3.3 Key design decisions worth preserving

- **Quiz answer keys never reach the browser.** `QuizController::start()` strips `is_correct` and `explanation` from the payload. `QuizOption` also hides `is_correct` at the model level. A test asserts the string is absent from the HTTP response.
- **The pass mark is snapshotted onto each attempt.** Raising `quizzes.passing_score` cannot retroactively fail an employee who already sat it.
- **`course_progress` is a rollup**, recalculated on every lesson tick and every graded attempt. Nothing recomputes it on read.
- **`lessons.course_id` is denormalised** from the module. `Lesson::saving()` keeps it in sync — do not set it by hand.
- **Assignment rules are rows, not code.** Changing a user's department changes what they are enrolled in on the next `training:sync-assignments` run.
- **Uploads and certificates go to the `private` disk.** Served only through `ResourceDownloadController` / `CertificateController`, both policy-gated.
- **Filament's published assets are committed** (`public/css|js|fonts/filament`). The Dockerfile does not run `filament:assets` — deleting them leaves the admin panel unstyled.

---

## 4. Scheduled Commands

Requires `php artisan schedule:work` in development, or a cron entry for `schedule:run` in production.

| Command | Schedule | Notes |
| --- | --- | --- |
| `training:send-reminders` | Weekdays 08:00 | Due-soon and overdue notifications. Supports `--dry-run`. |
| `training:sync-assignments` | Hourly | Turns `assignment_rules` rows into `course_enrollments`. Idempotent. |

---

## 5. Original Prototype Reference

The static prototype in `pages/` is preserved untouched as the visual and content reference.

```
pages/
├── training-tracker.html   + training-tracker/{script.js,styles.css}
├── support-panel.html      + support-panel/{script.js,styles.css}
└── skills-module.html      + skills-module/{script.js,styles.css}
styles/shared.css           Design tokens referenced during the port
scripts/shared.js           Theme toggle (ported to Vue)
docs/*.md                   Source curriculum (3 documents, seeded into DB)
```

Notable items **not ported** (superseded entirely):

- `.dev.vars` / Cloudflare Pages + Telegram-bot access-code plan — replaced by Laravel auth
- `window.storage` persistence calls (were broken — threw on every call, swallowed by `try/catch`)
