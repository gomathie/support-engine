# Competency model — delivery log

Running record of what has actually shipped against
[COMPETENCY_IMPLEMENTATION_PLAN.md](COMPETENCY_IMPLEMENTATION_PLAN.md), on branch
`feat/competency-phase-0-rbac`.

Status is what is **merged and tested**, not what is designed.

| Key | Ticket | Status | Notes |
| --- | --- | --- | --- |
| PA-1 | Server deployment & storage setup | Not started | Nazih; blocks nothing in the codebase |
| PA-2 | Queue worker + scheduler in production | Not started | Queue service exists in `docker-compose.yml` for dev |
| PA-3 | RBAC — role rename admin/trainer/trainee | **Done** | Roles renamed in place; all user assignments preserved |
| PA-4 | Trainer↔Trainee cohort tables + audit log | **Done** | `trainer_trainee`, `trainer_assignment_logs`, `AssignTrainee` |
| PA-5 | Content audit & re-alignment matrix | Not started | Igor |
| PA-6 | Pilot audit — 5–10 lessons | Not started | Igor + Trainer |
| PA-7 | Full audit — remaining ~95 lessons | Not started | Sized after PA-6 |
| PA-8 | Levels & competency-area schema | **Done** | See below |
| PA-9 | Video module — embed | Not started | Next dev ticket |
| PA-10 | Video module — native upload + HLS | Not started | Waiting on storage sign-off |
| PA-11 | Trainer grading interface — cohort scoping | **Partial** | `QuizAttemptResource` scopes by cohort; rubric UI outstanding |
| PA-12 | Pass/fail override + reason + audit log | Not started | `grades.override` permission exists; no UI |
| PA-13 | Trainee reassignment logic + grading inheritance | **Partial** | Action + log done; no admin UI |
| PA-14 | Practical task submission + rubric UI | Not started | |
| PA-15 | Pilot rollout | Not started | |
| PA-16 | Verify Section A answer key | **Blocked** | The source document contains no answer key — see §6 risk (g) |
| PA-17–19 | Phase 3 | Not started | |

---

## PA-3 — RBAC role rename

- `App\Enums\Role` is now `Admin` / `Trainer` / `Trainee`.
- Migration renames `roles.name` **in place** rather than recreating rows, so
  every existing assignment survives. Guarded against a half-applied state.
- New permissions: `videos.manage`, `content.audit`, `trainees.assign`,
  `trainees.reassign`, `transcripts.view-all`, `grades.override`.
- **Trainer deliberately does not hold `trainees.assign`.** Only an Admin decides
  who trains whom; otherwise a Trainer could assign themselves work or quietly
  hand off a struggling trainee.

## PA-4 — Cohorts

- `trainer_trainee` carries `ended_at`, so a closed assignment stays readable and
  a historical grade remains attributable to whoever held the cohort at the time.
- A Postgres partial unique index enforces one active trainer per trainee:
  `CREATE UNIQUE INDEX … ON trainer_trainee (trainee_id) WHERE ended_at IS NULL`.
- `trainer_assignment_logs` records actor, reason and how much pending grading
  the incoming trainer inherited.
- `User::canGrade()` is the single authority: nobody marks their own paper; an
  Admin may grade anyone; a Trainer only their current cohort.
- **Reading and grading are now separate rules.** A Trainer may read any
  transcript (`transcripts.view-all`) but grade only their own cohort. These used
  to be one department-shaped rule.

## PA-8 — Levels & competency areas

**Schema.** `levels` · `competency_areas` · `level_requirements` ·
`trainee_levels`, plus `level_id` / `competency_area_id` on `courses`.

Design decisions worth keeping:

- **A level is held per area, not globally.** Somebody can be competent at
  reporting and not at devices; a single overall level would hide exactly the gap
  a trainer needs to see.
- **Requirements are a table, not a column.** A rung is earned by completing a
  *set* of courses, so `level_requirements` is one row per required course.
- **A pair with no requirements is never awarded.** An unconfigured level is not
  an automatic pass — otherwise the Phase 1 audit backlog would read as everybody
  being fully competent in every area.
- **The ladder is ordered.** Second is withheld until Basic is held in the same
  area, so a mis-assigned advanced course cannot vault a trainee past the
  foundation. Only enforced where the lower rung is actually configured for that
  area.
- **Awards are revoked, never deleted.** "She held Level 1 until March" is a fact
  worth keeping, and a revoked award is not silently restored by the next lesson
  tick — restoring it is a deliberate act.
- **`awarded_by` is null for a system award**, set when a human granted it.
  `quiz_attempt_id` is the evidence trail.

**Award timing.** `AwardCompetencyLevel` runs from `RecalculateCourseProgress` at
the same point that issues a certificate, so every path that can finish a course
— a lesson tick, a passing quiz, an admin backfill — is covered. It is
idempotent, which is cheaper than tracking which course is the last of a set.

**Two editing surfaces, kept in step.** A course carries its own level and area
(the everyday case, edited on the course form), and `Course::saved()` syncs the
matching requirement row. Requirements for *other* pairs — a course counting
towards more than one — are authored on the level and are left alone. Without
that sync, retagging a course would leave a stale row still awarding the old
level.

**Authoring is Admin-only** (`competency.manage`). A Trainer gets
`competency.view`: they can read the ladder but not change it, because somebody
who can edit the requirements can lower the bar for their own cohort.

**Reporting.** The Users list carries a Competency column (one badge per rung
held, "Basic · Objects & Sensors") and a two-select filter — level, area, either
or both. That filter is the literal answer to *"who is Level 1 in Sensors"*.

**Seeded ladder.** Three levels (Basic / Second / Third) and the six areas
proposed in Appendix B item 4: Access & Rights · Objects & Sensors · Reporting ·
Notifications · Escalation · Admin Panel.

> **The course→requirement mapping in `CompetencySeeder` is provisional.** Only
> four courses exist, and which rung each earns is a PA-5/PA-6 question. Four are
> mapped as a starting point; Reporting and Notifications have no requirements
> yet and therefore cannot be awarded — which is the correct behaviour, not a
> gap. The "Awardable" column on the Competency Areas screen shows this state
> plainly rather than leaving it quietly true.

---

## Still open

- **Appendix B item 4** (the real list of competency areas) is answered
  provisionally in code. Igor's confirmation would close it.
- **Appendix B item 2** (does Level 1 need Sections A+B+C or A alone) directly
  determines the `level_requirements` rows for Basic. Currently unanswered, so
  the seeded mapping uses whole courses rather than exam sections.
- **PA-16 remains blocked.** The exam document has an empty
  "Answer Key for the Examiner" heading and all 15 Section B `Answer:` fields are
  blank. `PilotExamSeeder`'s key is labelled "from the document" but the document
  has none. Section A is published and live. Until Igor confirms the 40 answers,
  Section A results are provisional and must not gate a level award.
