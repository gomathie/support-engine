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

**Lesson content must survive the rich editor.** Filament's editor is TipTap-based
and silently drops nodes it has no extension for. Its toolbar covers headings,
lists, blockquotes, tables, links and inline marks — **not `div`, `dl/dt/dd`,
`span` or `figure`**. Content using those looks right until a trainer presses
save, then loses structure with no warning. `LessonContentEditableTest` asserts
no seeded body contains them; keep it that way when writing new lessons.

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

## 4a. Writing lesson content

Content lives in `database/seeders/content/`, one file per lesson, applied by
`LessonContentSeeder`. `track1_lesson_01.php` is the worked example and its
header carries the rules. Three that matter:

- **Write only what the documentation says.** Definitions are quoted verbatim
  from docs.pilot-gps.com so trainees learn the platform's own wording. If a
  topic is not in the docs, set `needs_input` and leave the gap visible — see the
  "Mapping Contract" entry. An invented PILOT fact is worse than an obvious hole,
  because a trainee will carry it onto a call.
- **Use only markup the rich editor round-trips** (see §3).
- **The seeder never overwrites existing content.** Once a lesson has a body it
  belongs to whoever has been maintaining it. `LESSON_CONTENT_OVERWRITE=1`
  reapplies the source files, and is for revising them during development — not
  something to run against a live database without saying so.

## 5. Open questions — do not invent answers to these

1. ~~**PA-16 — the exam answer key**~~ — **CLOSED 2026-09-08.** The user
   confirmed the Section A key is verified and correct. Section A is now
   published and gates the final lesson. It is scoped to the lesson, **not the
   course**: the course already has a final exam, and `finalQuiz()->first()`
   means a second course-scoped exam gates nothing while looking like it does.
   Sections B and C remain unpublished — publishing them is a separate decision
   nobody has taken.
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

### 2026-09-07 — Lesson 1 written, and verifiable evidence (Claude)

**Lesson 1 of the 1st-line track now has real content**, drawn from
docs.pilot-gps.com 7.10 with the glossary definitions quoted verbatim, plus a
four-question knowledge check at 70%. This is the pattern for the remaining ~100
lessons — see §4a.

**"Mapping Contract" was left unwritten on purpose.** The term is in the original
training plan but appears nowhere in the 7.10 documentation. The lesson says so
rather than carrying an invented definition. Needs Igor.

**Practical tasks can now demand verifiable evidence.** Trainees work in a live
PILOT account, so a task saying "create an object" leaves a real object with a
real agent ID. A task declares the identifiers it wants (`required_evidence`,
each {key, label, hint}) and whether a screenshot is mandatory; a submission
missing either cannot be handed in, enforced in the action so it holds for any
caller. The marking screen shows them under *"Check these in PILOT"*. This closes
a genuine weakness: Verification is one of four scored criteria and trainers were
marking it against prose.

Two bugs found by asking whether content was really editable:

- **The content seeder overwrote trainer edits.** It rewrote bodies on every run,
  and the quiz path deleted and recreated options — so an edited question would
  not even have merged. Now skips anything that already has content.
- **The seeded markup could not survive the rich editor.** Callout `div`s and a
  definition list would have been stripped on a trainer's first save. Rewritten
  to blockquotes and lists, with a test to stop it recurring.

### 2026-09-07 — Knowledge checks actually gate the course (Claude)

Each lesson already ended with a module-scoped quiz. **It gated nothing** — only
the final exam counted, so a trainee could skip every knowledge check and still
complete the course, earn a certificate and be awarded a level.

- Course completion now requires **passing every published knowledge check**, on
  top of lessons, practicals and the final exam.
- Exhausting the attempts on a check marks the course **Failed**, rather than
  leaving it "in progress" waiting for something that cannot happen.
- `CourseModule::hasKnowledgeCheck()` drives a **Yes / Missing** column on the
  modules list, so lessons without one are visible. Fifteen are currently
  missing; they need their lesson content written first, because questions have
  to test what the lesson actually taught.

The full completion rule is now: **lessons read · every knowledge check passed ·
every published practical passed · final exam passed.**

Two mistakes in the test for it, both mine, both worth avoiding:

- The markup assertion queried *every* lesson in the database rather than the
  ones the seeder wrote. It passed alone and failed in the full suite — the
  signature of an assertion reaching beyond its subject.
- `require path(...)['key']` indexes the *path string*: `require` binds looser
  than array access. Assign the result first.

Also: **do not run `artisan test --filter=...` while a full suite is running.**
Both use `pilot_lms_testing`, and the collision surfaces as an unrelated-looking
"select * from permissions" failure.

### 2026-09-08 — Model rename, and two exam bugs (Claude)

**`CourseModule` → `Lesson`, `Lesson` → `Topic`**, schema and code. The admin
panel had two different things called a lesson once the modules were retitled.
Done as a rename migration, not rewritten history — see
`2026_09_07_000400`. Ordering matters: `lessons` must vacate the name before
`course_modules` can take it.

Failure modes of a mechanical rename, all hit and all fixed: the perl pass
rewrote migration *history* (restored from git); `\bLesson\b` never matched
`Lessons`, leaving stale namespaces that collided fatally; `SCOPE_MODULE` →
`SCOPE_LESSON` duplicated an existing constant and silently collapsed two
distinct tests into identical ones; a loop variable rename left `position`
reading the outer loop.

**Two exam bugs found while answering a question about lesson structure:**

- **The course had two published final exams.** `RecalculateCourseProgress`
  reads `finalQuiz()->first()`, so the second one — the official 40-question
  Section A — was sittable, looked authoritative, and gated nothing.
  `OneFinalExamPerCourseTest` now guards this; it has regressed once before.
- **Section A is now lesson-scoped and published**, gating the final lesson,
  after the key was confirmed.

**Per-topic quizzes** (authored in parallel) are verified end to end by
`TopicQuizGatesTopicTest`, including an assertion over the real seeded
curriculum that every topic carrying a quiz is actually gated on it.
