# PILOT Support Training Academy — Prototype Analysis & Implementation Map

Written before any code was changed. This is the audit of the existing
HTML/CSS/JS prototype and the mapping of every existing feature onto the
target Laravel / Inertia+Vue / Filament / PostgreSQL architecture.

---

## 0. Executive summary
The PILOT platform is a **Support Training Academy for new support employees**.
The prototype was a **three-page static training interface**, not a fully integrated LMS. It had no
users, no authentication, no server, no database, and no notion of an assigned course,
an enrollment, a quiz score or a certificate. Persistence was per-browser and
currently **broken** (see §1.4).

We are converting this system into an interactive **Support Training Academy** where new support employees log in, access structured onboarding curriculum (1st-line support 2-week plan, admin panel 3-day plan, support skills, and prep materials), and **tick interactive checkboxes** as they complete each material or lesson to show their learning progress.

Consequently this work is not a 1:1 port. It splits into two parts:

1. **Conversion** — the existing curriculum materials, checklist items, diagnostic tools, layout, interactions and design become real, persisted, multi-user features. As new support employees complete lessons/materials, ticking the checkbox updates their `LessonProgress` state and recalculates course completion percentage in real time. This covers roughly §1–§3 of the prototype below.
2. **Net-new** — authentication, roles, departments, employee enrollments, the quiz engine, certificates, notifications, reporting and the Filament admin management portal have no prototype counterpart and are built from the target spec.

The prototype's value is as the **product specification and visual reference**: its content is real curriculum for new support employees, and its design is the intended product design. Both are preserved.

---

## 1. Inventory of the existing application

### 1.1 Files

```
index.html                        Landing / hub page
pages/training-tracker.html       + training-tracker/{script.js,styles.css}
pages/support-panel.html          + support-panel/{script.js,styles.css}
pages/skills-module.html          + skills-module/{script.js,styles.css}
styles/shared.css                 Design tokens, top nav, theme, responsive
scripts/shared.js                 Theme toggle + mobile nav
pages/index.css                   Landing-page-only styles
assets/images/{logo.png,White.png}
docs/*.md                         Source curriculum (3 documents)
.dev.vars / .env                  Cloudflare Pages + Telegram secrets (unused)
```

Total: ~2,290 lines across 4 HTML pages, 4 JS files, 5 CSS files.

### 1.2 Pages, components and interactions

| Page | Elements | JS behaviour |
| --- | --- | --- |
| `index.html` | Sticky top nav, hero with gradient headline + badge, 4-cell stats bar, 3 feature cards with icon/tag/title/body/CTA-arrow, footer | Theme toggle, mobile nav only |
| `training-tracker.html` | SVG circular progress gauge (`stroke-dasharray` 264), title block, done/total counters, "last saved" stamp, collapsible sections with flag chip + count + progress bar, day cards with `n`/`t`/`topics`, toggle-switch checklist items, reset button | Full client-side render from a `DATA` const; per-item toggle; per-section and global percentage recalculation; collapse/expand; reset-all |
| `support-panel.html` | Sub-brand bar, 4-tab ARIA tablist (Diagnose / Ask / Classify / Escalate), symptom buttons, numbered decision-tree steps with layer tags + "Ruled out"/"Cause found" actions, 3 static reference tables, 3×3 priority matrix, info cards, sticky sidebar case-note with 2 inputs + `<pre>` output + Copy/New-case | Tab switching; symptom selection; per-step tri-state; matrix cell selection; **case-note text generated from state**; clipboard copy w/ `execCommand` fallback; new-case reset |
| `skills-module.html` | Long-form reference doc, sticky sidebar TOC (4 modules, ~25 headings), `.std` "standard default" inline markers, slide-in drawer listing every marker, mobile TOC toggle | Auto-index of `.std` nodes; `sectionOf()` DOM walk to label each; drawer open/close (click + Escape); scroll-to + flash highlight; `IntersectionObserver` TOC scroll-spy |

### 1.3 Data structures (mock data)

All content is hard-coded in JS consts:

- `training-tracker/script.js` → `DATA`: 4 sections × N "days" × M items.
  **116 lines of real curriculum.** Shape:
  `{id, flag, title, days:[{n, t, topics, items:[string]}]}`
  Totals: 4 sections, 24 days, 96 checklist items.
- `support-panel/script.js` → `TREES`: 6 symptoms × 5–9 diagnostic steps.
  Shape: `{id, q, lay, steps:[{t, l, fix?}]}`
- `support-panel/script.js` → `MX`: 3×3 impact/urgency priority matrix.
  Shape: `{r, sub, cells:[{p, ex}]}`
- `skills-module.html` → content is HTML, not data. `.std` markers are
  discovered from the DOM at runtime.

### 1.4 Persistence — currently broken

Both stateful pages call an undefined global:

```js
await window.storage.get('pilot-tracker-state', false)
await window.storage.set('pilot-panel-case', JSON.stringify(S), false)
```

`window.storage` is **not defined anywhere in the codebase** and is not a
browser API. Every call throws, is swallowed by the surrounding
`try/catch`, and the state silently resets on reload. The only working
persistence is `localStorage.pilot_theme` in `scripts/shared.js`.

This is the strongest argument for the conversion: progress is *supposed*
to persist and today does not.

### 1.5 Validation, notifications, search, filters

- **Validation:** none. Two free-text inputs, no constraints.
- **Notifications:** none. "Saved at HH:MM:SS" text stamps only.
- **Search:** none.
- **Filters:** none — the tracker's collapse and the panel's tabs are
  show/hide, not filtering.
- **Modals:** one — the skills-module drawer (overlay + Escape + click-out).
- **Tables:** 5 static reference tables + 1 interactive matrix.

### 1.6 Design system

`styles/shared.css` defines the whole visual language as CSS custom
properties, with a full light/dark pair:

| Token | Light | Dark |
| --- | --- | --- |
| `--primary` | `#0284c7` | `#38bdf8` |
| `--primary-hover` | `#0369a1` | `#7dd3fc` |
| `--primary-bg` | `#e0f2fe` | `#0c4a6e` |
| `--bg` | `#f1f5f9` | `#020617` |
| `--surface` | `#ffffff` | `#0f172a` |
| `--surface-alt` | `#f8fafc` | `#1e293b` |
| `--text-pr` | `#334155` | `#f8fafc` |
| `--text-sec` | `#64748b` | `#94a3b8` |
| `--text-dis` | `#cbd5e1` | `#475569` |
| `--line` | `#e2e8f0` | `#1e293b` |
| `--line-strong` | `#cbd5e1` | `#334155` |
| `--positive` / `-bg` | `#22c55e` / `#dcfce7` | `#4ade80` / `#14532d` |
| `--negative` / `-bg` | `#ef4444` / `#fee2e2` | `#f87171` / `#7f1d1d` |
| `--warning` / `-dim` / `-bg` | `#eab308` / `#ca8a04` / `#fef9c3` | `#facc15` / `#b45309` / `#713f12` |
| `--nav-bg` | `rgba(255,255,255,.96)` | `rgba(15,23,42,.96)` |

Other invariants to preserve:

- Fonts: **Inter** 400/500/600/700/800 (body), **Courier New** (mono —
  used for the eyebrow, nav links, flags, logo text; uppercase +
  2.5px/1.2px letter-spacing).
- Layout: `max-width: 1200px`, `padding: 0 24px`, nav height `54px`.
- Nav: sticky, `backdrop-filter: blur(14px)`, 1px bottom border.
- Radii: 5px (nav/buttons), cards larger.
- Theme switch: `data-theme="dark"` on `<html>`, persisted, default light.
- Breakpoints: `768px` (nav collapses to hamburger), `1000px` (skills TOC).
- `prefers-reduced-motion` kills all transitions and animations.

### 1.7 Dead / obsolete material

- `.dev.vars`, `.env`, `docs/telfeature.md` describe an **unimplemented**
  Cloudflare Pages + Telegram-bot access-code plan. No `functions/`
  directory exists. Superseded entirely by Laravel authentication —
  **not ported.**
- `assets/images/logo.png` and `White.png` are unreferenced; both pages
  hot-link the logo from `pilot-telematics.com`. The new app self-hosts.
- `README.md` names the project `training-tracker`.

---

## 2. Feature → implementation map

### 2.1 Structural mapping

The tracker's own hierarchy matches the Support Training Academy course and material hierarchy:

```
Prototype (Academy Material) Target
─────────────────────────    ──────────────────────────────
DATA[] section          →    Course          (4 courses / academy tracks)
  .flag / .title        →      category / title
  section.days[]        →    CourseModule    (24 modules / day plans)
    .n                  →      title ("Day 1", "Module A")
    .t                  →      subtitle
    .topics             →      description
    .items[]            →    Lesson          (96 training materials & lessons)
                        →    LessonProgress  (checkbox completion state)
"Complete the final test"    Quiz + QuizAttempt
```

### 2.2 Full map

| # | Existing feature | Current implementation | New implementation |
| --- | --- | --- | --- |
| 1 | Curriculum content | `DATA` const, 116 lines JS | `courses` / `course_modules` / `lessons` tables, seeded from the same content via `TrainingContentSeeder` |
| 2 | Material/Lesson Checkbox Toggle | `STATE[key] = !STATE[key]` in memory | Employee ticks checkbox when completing a lesson/material → `POST /courses/{c}/lessons/{l}/complete` → `LessonProgress` upsert → `RecalculateCourseProgress` action |
| 3 | Per-section counter + bar | `reduce()` over `STATE` | `course_progress.completed_lessons / total_lessons / percentage`, computed server-side from completed checkboxes, sent via Inertia props |
| 4 | Global % gauge (SVG arc) | `stroke-dashoffset` from client count | Same SVG preserved in `ProgressGauge.vue`; percentage calculated from checked-off lessons |
| 5 | "Saved HH:MM:SS" stamp | `Date.toLocaleTimeString()` | Inertia partial reload; `lesson_progress.completed_at` timestamp recorded upon checking the box |
| 6 | Reset all progress | `STATE = {}` | `DELETE /my-progress/courses/{course}` guarded by policy; admin-configurable per course |
| 7 | Section collapse | `style.display` toggle | Same interaction, Vue `ref` UI state (presentation only — stays client-side) |
| 8 | Landing stats bar | Hard-coded `3 / 2 / 4 / 7` | `DashboardStat.vue` fed by real aggregates (assigned courses, in progress, completed materials, overdue) |
| 9 | 3 feature cards | Static `<a class="card">` | `CourseCard.vue` over the employee's assigned academy courses |
| 10 | Theme toggle | `localStorage.pilot_theme` | Kept client-side (pure UI preference) **and** mirrored to `users.theme_preference` so it follows the employee across devices |
| 11 | Top nav (4 links) | Duplicated in every HTML file | `EmployeeLayout.vue` — single source, active state from Inertia `usePage().url` |
| 12 | Mobile hamburger | `classList.toggle('open')` | Same behaviour in `EmployeeLayout.vue` |
| 13 | Support-panel tabs | ARIA tablist + `.view.on` | `Tabs.vue`, ARIA semantics preserved |
| 14 | `TREES` decision trees | JS const | `diagnostic_trees` / `diagnostic_steps` tables, Filament-managed |
| 15 | Step tri-state (out / found) | `S.state[key]` | `support_cases.steps` JSONB, persisted per employee case |
| 16 | `MX` priority matrix | JS const + click-to-select | Static reference in Vue (it is doctrine, not data); selection persisted on the case |
| 17 | Case-note generator | `note()` string builder | `SupportCaseNoteService` (server) — same output format, reusable and testable |
| 18 | Copy to clipboard | `navigator.clipboard` + fallback | Preserved verbatim (pure browser concern) |
| 19 | "New case" | `S = {…}` reset | `POST /support-cases` — cases become a persisted history |
| 20 | Skills-module doc | 551 lines static HTML | `lessons` with `type=rich_text`; content moved into the DB, rendered sanitised |
| 21 | TOC + scroll-spy | `IntersectionObserver` | `LessonToc.vue` — same observer, headings derived from lesson content |
| 22 | `.std` markers + drawer | DOM scan + `sectionOf()` walk | `lesson_annotations` table (type `standard_default`); drawer becomes `AnnotationDrawer.vue` |
| 23 | "Final test" checklist items | Plain checkboxes | Real `quizzes` with questions, options, attempts, scoring, pass mark |
| 24 | *(none)* | — | Authentication, roles, departments, enrollments, assignment rules, certificates, notifications, reporting, Filament admin — all net-new |

### 2.3 What deliberately stays on the client

Per §15 of the brief, business logic moves to Laravel. These remain in Vue
because they are presentation state, not business state:

- Section collapse/expand, tab selection, drawer open/closed
- Theme toggle (mirrored to the DB, but applied client-side to avoid FOUC)
- Clipboard copy
- Scroll-spy
- Optimistic checkbox state, reconciled against the server response

Everything else — scoring, progress %, completion, eligibility,
certificate issuance — is computed server-side and treated as
authoritative.

---

## 3. Target architecture

### 3.1 Stack

| Concern | Choice |
| --- | --- |
| Backend | Laravel 12 (PHP 8.3) |
| Admin | Filament 4 at `/admin` |
| Employee UI | Vue 3 `<script setup>` + Inertia 2 |
| Styling | Tailwind CSS 4, theme built from the prototype's tokens |
| Database | PostgreSQL 17 |
| Auth | Laravel Breeze (Inertia/Vue stack) |
| Authorization | Policies + Gates, `role` enum + `spatie/laravel-permission` |
| Storage | `Storage` facade, `local` (private) disk now, S3-ready |
| Queues | `database` driver — certificates, notifications, report exports |
| PDF | `barryvdh/laravel-dompdf` |

### 3.2 Route split

```
Employee (Inertia)                 Admin (Filament)
──────────────────────────         ─────────────────────────
/dashboard                         /admin
/courses                           /admin/courses
/courses/{course}                  /admin/modules
/courses/{course}/lessons/{l}      /admin/lessons
/courses/{course}/quiz/{quiz}      /admin/quizzes
/my-progress                       /admin/questions
/certificates                      /admin/employees
/certificates/{cert}/download      /admin/departments
/support-panel                     /admin/roles
/profile                           /admin/enrollments
                                   /admin/reports
                                   /admin/certificates
```

### 3.3 Schema

```
users ──┬── department_id → departments
        ├── role (admin|manager|employee)
        └── manages departments (manager_id)

courses ──< course_modules ──< lessons ──< lesson_resources
                                  └──< lesson_annotations
courses ──< quizzes ──< quiz_questions ──< quiz_options
quizzes ──< quiz_attempts ──< quiz_answers

course_enrollments (user, course, source, due_at, status)
lesson_progress    (user, lesson, completed_at)
course_progress    (user, course, %, status, started_at, completed_at)
certificates       (user, course, number, score, issued_at, path)
notifications      (Laravel default)
assignment_rules   (course, target_type: user|department|role, target_id)
diagnostic_trees ──< diagnostic_steps
support_cases      (user, tree, fields, steps JSONB, priority)
```

Every FK indexed; `(user_id, lesson_id)` and `(user_id, course_id)` unique;
`certificates.number` unique; soft deletes on courses/modules/lessons/users;
`RESTRICT` on delete where history must survive, `CASCADE` on
composition edges (module → lesson → resource).

### 3.4 Security posture

- Correct answers live in `quiz_options.is_correct` and are **stripped from
  every payload** sent to the browser before submission (dedicated
  `QuizQuestionResource` for the taking-a-quiz view).
- Scoring happens in `GradeQuizAttempt`; the client submits option IDs only.
- Uploaded resources go to the **private** disk and are served through
  `ResourceDownloadController`, gated by policy — never a public URL.
- Managers are scoped to their own department(s) by policy plus a global
  Eloquent scope on reporting queries.
- Mass assignment guarded by explicit `$fillable`; all writes go through
  Form Requests.

---

## 4. Delivery order

Follows the phases in the brief:

1. **Foundation** — Laravel, PostgreSQL, auth, roles, Filament, Inertia/Vue,
   Tailwind theme, migrations, models, layouts.
2. **Course management** — courses, modules, lessons, resources, publishing.
3. **Employee learning** — dashboard, enrollment, lesson viewing, completion,
   progress.
4. **Quiz engine** — creation, questions, attempts, scoring, pass/fail.
5. **Reporting** — employee, department, course, quiz performance.
6. **Certificates** — generation, history, download, verification.

The prototype in `pages/` is left in place, untouched, as the visual
reference for the duration of the port.
