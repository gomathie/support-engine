# Agent briefing — read this first, and log before you finish

**Every agent working in this repository must read this file before starting and
append to the Work log before finishing.** The next agent starts cold. What is
written here is the only thing that carries across.

---

## 1. What this project is

Support Training Hub: an internal training platform for PILOT support staff,
built on Laravel 13 · Filament 5 · Vue 3 + Inertia · PostgreSQL 17.

It began as an HTML/CSS/JS prototype and was converted to production. It is now
part-way through a second, larger change of direction.

### The one thing to understand before changing anything

> **The platform is moving from "completion" to "competence."**

The old model let a trainee tick a checkbox to say they had learned something,
and reported that assertion as progress. Everything currently being built exists
to replace that with evidence somebody else assessed:

| A finished lesson means | A finished course means |
| --- | --- |
| The material was opened and read | Lessons read **and** every practical task passed **and** the final exam passed |
| It is a **reading record** | It is a **competence claim** |
| The learner records it | A trainer marks it |

If a change you are making would let somebody claim competence without a second
person agreeing, it is the wrong change. This is the axis the whole codebase is
organised around.

### Documents, in order of authority

| File | What it is |
| --- | --- |
| [`docs/COMPETENCY_IMPLEMENTATION_PLAN.md`](docs/COMPETENCY_IMPLEMENTATION_PLAN.md) | The agreed plan. Section numbers (§3, §4.3, §7) are referenced throughout the code. |
| [`docs/COMPETENCY_DELIVERY_LOG.md`](docs/COMPETENCY_DELIVERY_LOG.md) | What has shipped against that plan, and **why each decision was made**. Read before changing anything in that area. |
| [`README.md`](README.md) | Setup, architecture, and "things worth knowing before changing them". |
| [`CHANGELOG.md`](CHANGELOG.md) | User-facing changes by month. Rendered by the **What's new** admin page — keep the `## Month Year` heading shape or that screen breaks. |

---

## 2. House rules

1. **Business logic lives in `app/Actions`, never in a Vue component or a
   Filament resource.** Filament and Inertia are delivery, not logic.
2. **Authorisation is server-side in policies.** Hiding a link is not access
   control.
3. **Nobody marks their own paper.** `grade`, `override`, `submit` are excluded
   from the admin bypass in `QuizAttemptPolicy::before()` and
   `PracticalSubmissionPolicy::before()`. Do not "simplify" that away.
4. **Reading a record and marking it are separate rights.** A trainer may read
   any transcript (`transcripts.view-all`) but may only mark their own cohort.
5. **Never render author-supplied text into a script-capable sink.** A pasted
   video URL is parsed to a provider and id, and the embed URL is rebuilt from a
   fixed template — see `app/Support/Video/VideoEmbed.php`.
6. **Private files have no public URL.** Uploads and certificates live on the
   `private` disk and are served only through a policy-checked controller.
7. **Write tests that state the rule, not the implementation.** The test names in
   this repo read as sentences on purpose.

---

## 3. Working practice — the traps in this specific repo

**Do not edit application files while the test suite is running.** Filament
auto-discovers resources and pages. Creating a resource whose page class does not
exist yet breaks *every* admin panel test in the run. This has happened twice.
Either wait for the run, or only add files nothing references yet.

**Blade resolves component tags at compile time.** An unknown
`<x-some::component>` breaks the whole template, not just the branch it sits in.

**Bash eats backslashes.** Writing PHP namespaces through `echo`/`sed`/heredocs
in Git Bash has corrupted files repeatedly. Use the Write/Edit tools for PHP.

**Tests run against `pilot_lms_testing`.** `TestCase` refuses to run unless the
database name contains `test`, because `RefreshDatabase` runs `migrate:fresh` and
a misconfigured environment would silently destroy the dev database.

```bash
docker compose exec app php artisan test          # ~4½ minutes, 261 tests
docker compose exec app php artisan test --filter=SomeTest
```

**Blank page?** Delete `public/hot`. Vite leaves it behind on an unclean exit and
`@vite` then points at a dev server that is not running.

---

## 4. Where things stand

Branch: `feat/competency-phase-0-rbac`. The user commits themselves — **do not
commit unless asked.**

**Suite: 261 passing, 891 assertions** as of the last full green run.

| Ticket | State |
| --- | --- |
| PA-3 RBAC, PA-4 cohorts | Done |
| PA-8 competency ladder | Done |
| PA-9 video embed · PA-10 upload | Done, **minus transcoding** (needs ffmpeg, blocked on PA-1) |
| PA-11 cohort grading · PA-12 override | Done |
| PA-13 reassignment UI | Done |
| PA-14 practical tasks + rubric | Done, both sides |
| PA-19 KPI dashboard | Done — 5 of 9 metrics reporting |
| PA-18 spaced repetition | **Not started** — the only remaining dev ticket |
| PA-5/6/7 content audit | **Not started** — Igor. The real bottleneck. |
| PA-1/2 server + queue | **Not started** — Nazih |

---

## 5. Open questions — do not invent answers to these

1. **PA-16 — the exam answer key is unconfirmed, and this is the important one.**
   The source document (`docs/PILOT_Technical_Support_Employee_Exam_EN2.md`) has
   an empty "Answer Key for the Examiner" heading and 15 blank `Answer:` fields.
   `PilotExamSeeder`'s key is labelled "from the document" but **the document has
   none**. Section A is published and live. Until Igor confirms all 40 answers,
   Section A results are provisional and **must not gate a level award**. Do not
   fabricate an answer key.
2. **Does Level 1 require exam Sections A+B+C, or A alone?** Unanswered, so the
   seeded `level_requirements` use whole courses rather than exam sections.
3. **Are the six competency areas correct?** Access & Rights · Objects & Sensors ·
   Reporting · Notifications · Escalation · Admin Panel. Seeded provisionally,
   awaiting Igor.
4. **Retake cap** — currently 3, undecided.
5. **The content is empty.** ~104 seeded lessons are titles with no body. The
   assessment engine is built; the material to assess is not. This is the single
   largest gap in the product and no amount of engineering closes it.

---

## 6. Work log

Newest last. **Append an entry before you finish.** Say what you changed, what
you decided and why, and what you left undone — the "why" is the part that does
not survive in a diff.

### 2026-09-07 — Phase 2 build (Claude)

Delivered PA-8 through PA-14 and PA-19. Detail and reasoning in
`docs/COMPETENCY_DELIVERY_LOG.md`; the decisions worth knowing before touching
that code:

- **A level is held per competency area, not globally.** A pair with no
  configured requirements is never awarded — an unconfigured level is not an
  automatic pass.
- **A pass/fail override is stored, not written into `passed` and forgotten**, so
  re-marking a written answer cannot silently undo a human decision. The score is
  never rewritten: "scored 30% but passed on appeal" is the truth.
- **Two markers who disagree are not averaged.** A split verdict leaves the
  submission unsettled and flagged. Averaging would manufacture a false consensus
  and hide the rubric drift that calibration exists to surface.
- **The rubric threshold is not a total.** 3+ on Correctness, 2+ on every other
  criterion, **and** 10+ overall. `4/4/2/0` sums to 10 and still fails.
- **The KPI screen shows the four metrics it cannot compute, with reasons.** A
  dashboard that silently displays five of nine looks complete when it is not,
  and the missing four are the ones blocked on content work.

Bugs found and fixed along the way: an authorised video was cacheable by a shared
proxy (`Cache-Control: public`); `Range` requests were answered 200, making the
scrubber useless on large files; the reassignment handover count omitted
practical submissions.

Left undone: video transcoding, and the content itself.

### 2026-09-07 — Completion model + lesson renumbering (Claude)

Two changes requested by the user, plus one gap found while making them.

- **Self-attested lesson completion is retired.** All 104 lessons were
  `acknowledge` — the learner ticked a box and the platform reported it as
  progress. Migrated to `view`, which records honestly that the material was
  opened and claims nothing more. `CompletionRequirement::Acknowledge` survives
  for genuine policy sign-off ("I have read the data protection policy") but is
  no longer the default and is used by no training lesson. Added
  `CompletionRequirement::Task`.
- **Gap found: practical tasks did not gate course completion.** A trainee could
  finish a course, collect a certificate and be awarded a competency level
  without ever passing the practical. `RecalculateCourseProgress` now requires
  every published practical to be passed, and `FinalisePracticalSubmission`
  triggers a recalculation so a passing mark completes the course.
  **This is a behaviour change:** any course carrying a practical will show as
  incomplete for people who had previously "finished" it. That is correct, but it
  will surprise somebody.
- **"Day 1 … Day 14" renamed to "Lesson 1 … Lesson 12"**, and the "2 week plan" /
  "3 day plan" caps dropped from course titles. A calendar is the wrong frame:
  somebody who needs three days for Lesson 2 is not behind, and somebody who
  finishes on time has not thereby learned anything. `Day 11–12` and `Day 13–14`
  collapsed into single units so the sequence runs 1..12 with no gaps.
- **Added `course_modules.docs_reference`**, naming the chapter of
  docs.pilot-gps.com each unit is drawn from. Stored as a chapter name rather
  than a URL: the documentation is versioned (7.10 currently) and deep links
  would rot at the next release.

Left undone / needs a human:

- The docs mapping is at **module level and derived from the module subtitles**
  against the real 7.10 table of contents. Per-lesson mapping was not attempted —
  it needs somebody who knows the material, and guessing would produce
  confident-looking nonsense.
- **Track 3 still uses "Module A–D".** It is not day-based so it was left alone,
  but it is now inconsistent with Lesson 1..12 elsewhere. Worth a decision.

### 2026-09-07 — Trainers author courses (Claude)

The user described the intended shape: *trainers create courses with questions
and answers; courses contain lessons; trainees must reach the pass mark the
trainer sets.* Most of it already existed. One piece did not.

- **Gap: a trainer could not create or edit a course.** They held
  `lessons.manage` and `quizzes.manage` — able to write the lessons and the exam,
  but not the course those sit in, so every new course needed an administrator.
  Granted `courses.create`, `courses.update` and `courses.publish`.
- **`courses.delete` was deliberately withheld.** Deleting a course takes other
  people's training records, certificates and level awards with it. That stays an
  administrator's decision. Flag this if somebody asks why trainers cannot
  remove a course.
- The pass mark was already the trainer's to set, per quiz, and is snapshotted
  onto each attempt so raising it later cannot retroactively fail somebody.
- **Added `Course::isAssessed()` and an "Assessed" column.** A course with
  neither a published exam nor a published practical is completed by opening the
  lessons — the old model in new clothes. Not blocked, because a policy briefing
  is a legitimate case, but shown as *"Reading only"* so it is a decision rather
  than an oversight.
