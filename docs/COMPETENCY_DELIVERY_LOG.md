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
| PA-9 | Video module — embed | **Done** | YouTube / Vimeo, privacy-preserving |
| PA-10 | Video module — native upload | **Done, minus transcoding** | Upload + HTML5 player + Range streaming; HLS deferred |
| PA-11 | Trainer grading interface — cohort scoping | **Partial** | `QuizAttemptResource` scopes by cohort; rubric UI outstanding |
| PA-12 | Pass/fail override + reason + audit log | **Done** | `grade_override_logs`; see below |
| PA-13 | Trainee reassignment logic + grading inheritance | **Done** | Assign/reassign UI + read-only audit screen |
| PA-14 | Practical task submission + rubric UI | **Done** | Both sides: trainee submission and trainer marking |
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

## PA-9 / PA-10 — the two video methods

Both methods the brief asked for now exist as distinct lesson types:
`video_embed` (YouTube / Vimeo) and `video_upload` (a file on the private disk).

### Embed — the parsing boundary

An author pastes whatever URL the browser gave them. That string is **never
stored and never rendered**. `App\Support\Video\VideoEmbed` parses it to a
provider and an id, validates both against strict patterns, and the embed URL is
**rebuilt from a fixed template** — so the iframe `src` is never author-controlled
text. A tampered row yields no embed rather than an arbitrary `src`, which is
covered by a test.

Two things the tests caught during the build:

- The patterns were not anchored, so `…/dQw4w9WgXcQ" onload="alert(1)` matched
  the prefix and the junk was silently discarded. The rebuilt URL was safe either
  way, but a malformed paste should be reported, not quietly corrected. Added an
  id-boundary lookahead.
- That lookahead's `#` then closed the `#`-delimited regex. Escaped.

Privacy per §4.1: `youtube-nocookie.com` with `rel=0` — so the end of a training
video is not a doorway to unrelated content — and Vimeo with `dnt=1`.

### Upload — the authorisation boundary

Files land on the `private` disk (no URL, no public visibility), exactly as
`lesson_resources` already do. `LessonVideoController` is the only route to the
bytes and runs the lesson policy first; knowing the URL is not authorisation.
`video_disk` is recorded per row, so pointing `PRIVATE_FILESYSTEM_DRIVER` at S3
later will not strand files already written locally.

**Range requests are answered with 206.** Seeking in a `<video>` element sends
`Range`, and a server that returns 200 with the whole file makes the scrubber
useless on anything large. Local disks use `BinaryFileResponse`, which handles
`Range`, `If-Range` and multipart properly; a remote disk falls back to
progressive download (playback works, seeking past the buffer does not) until the
PA-1 storage decision is made.

One bug worth recording: `response()->file()` sets its own `Cache-Control` and
overrode the intended `private` with **`public`** — a shared proxy could have
served an authorised video to somebody the policy had just refused. Now set
explicitly on the built response, and asserted in a test.

### What is NOT built

- **No transcoding.** §5.1 specifies a queued probe → HLS (360/720/1080) →
  poster → captions pipeline. None of it exists; an upload goes straight to
  `ready`. The `video_status` column is in place so that pipeline needs no
  further migration. This means a 500 MB 1080p file is served as-is to a support
  desk on a poor connection — the exact case HLS was meant to solve.
- **No duration probe.** The author types the duration; neither provider reports
  it without an API key and there is no ffmpeg on the box.
- **The 5–7 minute ceiling is advisory.** Over 420s the author is told to
  consider splitting, and `videoExceedsRecommendedLength()` makes it reportable
  in the audit — but it is not enforced, because legitimate longer walkthroughs
  exist.

## PA-12 — Pass/fail override

Promotion stays automatic. This is the exception path §3 describes: a pass
secured on a question later found defective, or a fail overturned on appeal.

Four decisions worth recording:

- **The override is stored, not written into `passed` and forgotten.** Re-marking
  a written answer re-runs `FinaliseQuizAttempt`, and a human decision to uphold
  an appeal must not evaporate when it does. Finalisation reads
  `override_passed ?? ($score >= $passMark)`, so everything downstream keeps
  using one authoritative column. There is a test for exactly this.
- **The score is never rewritten.** *"Scored 30% but passed on appeal"* is the
  truth; flattening it to the pass mark would erase the appeal. The table shows
  the marked score and the override side by side, so the two cannot silently
  disagree.
- **Overturning a pass revokes only the levels that attempt was the evidence
  for** — matched on `trainee_levels.quiz_attempt_id`, not a blanket sweep. A
  level earned by another route stands. Without this, an overturned pass would
  leave somebody holding a competency level the overturned exam bought them. The
  count of revoked awards goes into the log, because it cannot be reconstructed
  afterwards.
- **`override` is excluded from the administrator bypass in
  `QuizAttemptPolicy::before()`**, alongside `grade` and `submit`. An
  administrator must not be able to overturn their own fail. It additionally
  requires `grades.override`, which is a named permission rather than something
  the Trainer role implies.

Withdrawing an override also requires a reason and writes its own log row —
reverting is as consequential as the original decision.

The admin table gains an Override column, an "Overridden results only" filter for
audit and calibration review, and a secondary action with a 15-character minimum
on the reason. It is deliberately not the obvious button on the row.

## PA-14 — Practical tasks and the rubric

The Apply/Analyze end of §4.1's Bloom mapping — what a multiple-choice question
cannot reach. Five tables: `practical_tasks`, `practical_submissions`,
`practical_submission_files`, `practical_gradings`, `practical_grading_scores`.

**Kept separate from quizzes on purpose.** §3 puts the practical before the exam
and marks it differently — not points out of a maximum, but four criteria with
descriptors and a threshold rule. A `QuizQuestion` type whose scoring shares
nothing with the other types would have been a worse fit than a separate model.

### The threshold is not a total

`PracticalGrading::passes()` holds the §4.3 rule in one place: **3+ on
Correctness, 2+ on every other criterion, and 10+ overall.** All three must hold.
4/4/2/0 sums to 10 and still fails — somebody who cannot explain what they did
has not met the standard, and a test pins exactly that case. Correctness carries
the higher bar because a tidy route to the wrong answer is still the wrong
answer.

### A low score must be explained

Scores of 2 or below require a comment, enforced in the action rather than the
form so it holds for any caller. "Why did this fail" is the only part of a mark a
trainee can learn from, and a bare 1 teaches nothing.

### Two markers who disagree are not averaged

`requires_second_marker` is per task, so double-marking can be switched on while
a task's standard is being calibrated and off once it settles — rather than being
a permanent burden. With it on:

- one grading does not settle the submission; it stays awaiting marking;
- if the two markers reach **different verdicts, the submission still does not
  settle.** It is flagged as a split verdict and waits for the trainers to
  reconcile.

That last one is the important call. Averaging a pass and a fail would produce a
false consensus and hide precisely the rubric drift the calibration exercise
exists to catch. The admin queue has a "Split verdicts only" filter, which is the
calibration meeting's agenda.

Gradings are stored per grader rather than as columns on the submission, because
"independent" means neither marker can see the other's scores first — which is
only possible if they are separate rows.

### Returning is not failing

`Returned` is a distinct status from a graded fail. "You forgot the verification
screenshot" is a gap in the evidence, not a judgement on the work — and it
reopens **the same attempt** rather than starting a new one, so the retry
statistics stay meaningful. A fail is what starts a fresh attempt.

### Authority

Same split PA-4 established: a trainer may read any submission with
`transcripts.view-all`, but marking is cohort-scoped, and `grade`/`return`/
`submit`/`update` are excluded from the administrator bypass — nobody marks their
own practical. The marking queue itself is filtered to what the trainer can act
on, because a list full of rows you cannot mark is not a queue.

### The trainee's side

`Practical/Show.vue` plus `PracticalTaskController`. Three decisions:

- **The rubric is shown before they start**, collapsible, with all five
  descriptors per criterion and the minimum marked. Assessing somebody against
  criteria they never saw is how you get the "I didn't know that counted"
  conversation.
- **Marks are released only once the submission has settled.** On a
  double-marked task that means both trainers agreeing — a trainee must not read
  one marker's provisional view, still less two that contradict each other. The
  controller keys feedback off `finalised_at`, and a test covers the withheld
  case.
- **Evidence goes to the private disk** with a mime allowlist (images, PDF,
  plain logs; a `.php` upload is refused) and is reachable only through a route
  authorised against the submission — so a trainee reaches their own and a
  trainer reaches what they may read, under one rule. Tested for the
  cross-trainee case.

Practical tasks are surfaced on the course page alongside the modules rather
than inside them, because a task may hang off one lesson *or* stand for the whole
course, and burying it in a module would hide the second kind.

## PA-13 — Reassignment UI and audit

The action and log tables existed from PA-4; this is the screen an administrator
actually uses, plus one correctness fix.

**The inherited-work count was under-reporting.** `pendingGradingCount()` counted
ungraded written answers only. Since PA-14 a trainee can also have practical
submissions awaiting marking, and those move to the incoming trainer exactly the
same way. A handover number that under-counts is worse than no number, because it
will be trusted — so it now counts both. Found by reading the action while
building the UI, not by a failing test.

**Assign / reassign / unassign** is one action on the user row, visible only for
trainees and only to somebody holding `trainees.assign` — which the Trainer role
deliberately does not have. Leaving the trainer field empty unassigns.

**The reason is required on a reassignment but not a first assignment.** There is
nothing to explain about giving a new starter their first trainer; moving
somebody mid-course is precisely what an audit asks about later.

**`Assignment history` is a read-only resource** — no create, no edit, no delete,
gated on `trainees.reassign`. §6 asks for an immutable log of reassignments, and
a screen that could edit it would not be one. It carries a "trainer (either
side)" filter, because the question when reviewing a handover is everything that
moved *to or from* one person.

### RBAC note — "revocable per person" was wrong

The plan's §3 footnote described the Trainer capabilities as revocable per
person. They are not: `videos.manage` is held by the Trainer **role**, and
`spatie/laravel-permission` has no per-user deny, so revoking it from one Trainer
would take it from all of them. **Decision: role-level is the intended
granularity** — per-user grants would leave no single place to see what a Trainer
can do. The plan text has been corrected and a test pins the behaviour.

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
