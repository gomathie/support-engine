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

**Filament's stylesheet does not know about your markup.** Filament ships a
*precompiled* CSS file built from its own source, so a Tailwind class used only
in `resources/views/filament/**` compiles to nothing. Nothing errors and nothing
warns — the page simply arrives unstyled while the source looks correct. Two
custom pages were shipped this way; 215 of 257 classes on one of them did not
exist. `resources/css/filament/admin/theme.css` re-runs Tailwind over the
panel's views and is registered with `->viteTheme()`. **If you add Blade under a
new directory in the panel, add an `@source` for it.** `AdminThemeTest` holds
the three pieces together.

**The content seeder matches by title, and a retitle is therefore a duplicate.**
This has now bitten four times: course slugs (`2026_09_08_000420`), knowledge
checks twice (`…000470`, `…000490`), and practical tasks (`…000480`). The
seeder now keys knowledge checks on the module, lesson quizzes on the lesson,
and practical tasks on the slug, so a retitle is a rename. **Keep it that way,
and never add a title to a `updateOrCreate` match array.** A duplicate is not
cosmetic: every published module-scoped quiz and every published practical task
gates course completion, so the second copy doubles the requirement in silence.

**Bash eats backslashes.**

 Writing PHP namespaces through `echo`/`sed`/heredocs
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

Branch: `main`, with `hub-version2` kept in step with it. (`hub-version2` is the
old `feat/competency-phase-0-rbac`, renamed 2026-09-08 and fast-forwarded into
`main` the same day. Nothing has been pushed — `main` is ahead of `gitlab/main`.)

**Suite: 374 passing, 1276 assertions** as of the last full green run.

| Ticket | State |
| --- | --- |
| PA-3 RBAC, PA-4 cohorts | Done |
| PA-8 competency ladder | Done |
| PA-9 video embed · PA-10 upload | Done, **minus transcoding** (needs ffmpeg, blocked on PA-1) |
| PA-11 cohort grading · PA-12 override | Done |
| PA-13 reassignment UI | Done |
| PA-14 practical tasks + rubric | Done, both sides |
| PA-19 KPI dashboard | Done — 5 of 9 metrics reporting |
| PA-18 spaced repetition | Done — 30- and 90-day refreshers, and KPI 6 with them |
| PA-19 KPI dashboard | **6 of 9 metrics reporting**, up from 5 |
| PA-5/6/7 content audit | **Now possible.** The 105 lessons it audits exist as of 2026-09-08 |
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
- **Ignore the documentation's version numbers.** Do not hunt for "the 7.10
  page" or hold a lesson up because you cannot confirm which release a page
  belongs to. Take docs.pilot-gps.com as you find it: where there is a newer
  page, use it; where there is not, the older one is the source. Version
  numbers are not part of the content and do not need recording in the lesson.
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
2. ~~**Does Level 1 require exam Sections A+B+C, or A alone?**~~ — **DECIDED
   2026-09-08: all three.** All are published and scoped to the "Final
   examination" lesson, so all three gate course completion. The cost is
   deliberate: Sections B and C are **33 hand-marked answers**, so no trainee
   finishes the course until a trainer has read every one. That is the §6(a)
   bottleneck — **watch KPI 7 (trainer workload) as the cohort grows**, and
   revisit if the queue outruns the trainers. `ExaminationGatesLevelOneTest`
   pins the 33 so the assumption cannot drift unnoticed.
3. **Are the six competency areas correct?** Access & Rights · Objects & Sensors ·
   Reporting · Notifications · Escalation · Admin Panel. Seeded provisionally,
   awaiting Igor.
4. **Retake cap** — currently 3, undecided.
5. ~~**The content is empty.**~~ — **CLOSED 2026-09-08.** All four courses are
   written: 105 lessons, 103 with a body, 98 carrying a lesson quiz, 23 module
   knowledge checks and 19 practical tasks. The two unwritten lessons are
   `needs_input` topics, not gaps in the work.

6. **Is this company the controller or the processor** for the data on the
   platform — particularly drivers' location data? Nothing else in §5 blocks as
   much: it decides who answers a data subject request and who may delete
   anything. Blocks Support skills D5–D6 and Onboarding M3.

7. **The data subject request route, and its deadline.** Where a request goes,
   in what time, and what the person is told meanwhile. Statutory clocks can
   start when the organisation becomes aware, which may be the agent who took
   the call.

8. **The security incident reporting path**, including out of hours. Onboarding
   M3 teaches "report before you investigate" and cannot say to whom.

9. **The approved caller-verification questions.** What a caller must produce
   before account details are shared, and what to do when they cannot.

10. **The severity scheme, and the required fields per severity.** The
    curriculum contradicts itself — Onboarding M1 says Sev-1/Sev-3, M2 says
    P1–P4. Somebody must say which is real before either is taught.

11. **First-response and resolution targets per priority**, and whether the
    clock runs on business or calendar hours. Commercial commitments; cannot be
    inferred.

12. **The team map** — who owns what — and the **escalation criteria** to second
    line. Blocks Support skills C1 and C2, and Onboarding M2.

13. **The desk's real five-point ticket standard.** The curriculum names it;
    nothing defines it. The five points now taught were written for the lesson
    and are marked as provisional.

14. **Four PILOT topics the documentation does not support**, all flagged in the
    Admin panel course: *stock account*, *account types*, and two speed
    questions. The speed pair is a finding rather than a gap — there is no speed
    configuration in PILOT, speeding is a notification, and the lessons say so.
    They should probably be reworded or retired rather than answered.

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

### 2026-09-08 — Examination split out, and two silent bugs (Claude)

The examination is now its own lesson rather than sharing "Final testing and
consultation" with the review topics:

| Lesson 12 | Review and consultation — 2 topics + knowledge check |
| Final examination | Section A (live) · Sections B and C (draft) |

Sections B and C remain unpublished. Whether Level 1 requires A+B+C or A alone
is Appendix B item 2 and still undecided — do not publish them on a guess.

Two bugs surfaced, both of the kind that fail without complaining:

- **Renaming a course title without its slug creates duplicate courses.**
  `2026_09_07_000380` dropped the time caps from course titles and left the
  slugs. `TrainingContentSeeder` keys on `slug => Str::slug($title)`, so the
  next seed matched nothing and created a *second* "1st-line support" and
  "Admin panel" — empty, alongside the real ones. Fixed in
  `2026_09_08_000420`, which realigns the slugs and deletes a duplicate only
  after confirming it has no enrolments, progress, attempts or certificates.
  **If you retitle a course, move its slug too, or accept the old slug forever.**
- **`WrittenExamSeeder` could not be re-run once anyone had sat the exam.** It
  force-deleted every question, but `quiz_answers.quiz_question_id` is
  `restrictOnDelete` deliberately — deleting a question must not rewrite the
  history of attempts graded against it. Now it clears only unanswered
  questions and matches the rest by prompt with `updateOrCreate`. Note the trap
  in the obvious fix: clearing the unanswered ones and then re-creating
  everything leaves the paper with two of each. Verified idempotent over three
  consecutive runs.

Also: `WrittenExamSeeder` now finds its lesson **by subtitle**, not by "whichever
sorts last" — that was only accidentally correct, and adding a lesson after it
silently relocated Sections B and C.

### 2026-09-08 — What's new redesigned to standard

Redesigned the "What's new" admin page (`app/Filament/Pages/WhatsNew.php` and `resources/views/filament/pages/whats-new.blade.php`) to a modern, interactive, and beautifully categorized changelog timeline:

- **Hero header and KPI metrics:** Radial ambient gradient banner with platform release badge, subtitle, and dynamic KPI stat cards (Total Releases, Total Updates, and Latest Live Release indicator).
- **Structured section and item parsing:** Upgraded `WhatsNew::releases()` to parse level-3 sections (`### Added`, `### Changed`, `### Fixed`, `### Known limitations`) and individual bullet points, while retaining `heading` and `body` HTML for complete backward compatibility.
- **Client-side instant search & category filtering:** Powered by Alpine.js with zero network overhead. Trainees and trainers can filter updates in real time by typing keywords (e.g. `video`, `rubric`, `dark mode`) or clicking category pills (`All`, `Added`, `Changed`, `Fixed`, `Limitations`).
- **Timeline card layout:** Desktop connecting rail with milestone nodes, distinct category color coding (emerald, sky, amber, purple), left accent indicators, and enhanced typography for inline code, strong leads, and links in both light and dark modes.
- **Cleaned test warnings:** Aligned class names with filenames in `TopicProgressTest`, `VideoTopicTest`, and `VideoUploadTopicTest` so the test suite runs with 0 warnings.


### 2026-09-08 — A lesson gets a summary, a cover and its sources (Claude)

Three fields from the reference screenshot the trainee had no way to see:

| `summary` | one or two sentences, above the body and in the course outline |
| `cover_image_path` | a banner, shown only when the lesson has no video |
| `doc_links` | `[{title, url}]` — the docs.pilot-gps.com pages it came from |

Migration `2026_09_08_000460`. All three are authorable in the admin panel —
`doc_links` is a Repeater, so a lesson drawn from four pages links to four.

**The link filter lives in the model, not the template.** `documentationLinks()`
drops anything that is not http or https. These are author-supplied URLs going
into an `href`, and an `href` runs a `javascript:` URL on click. The form
refuses those too, but the form is not the only way a row gets written — a
seeder, a console command and a tampered row all reach the same template.

**Two pieces of rename fallout, both silent:**

- `LessonProgressTest.php` still declared `class TopicProgressTest`, and
  `LessonQuizGatesLessonTest.php` still declared `class TopicQuizGatesTopicTest`.
  PHPUnit prints `WARN Class X cannot be found in …` and then **skips the whole
  file**. 13 tests had not run since the rename. They pass. If you rename a test
  file, rename the class in the same breath, and treat a WARN line as a failure.
- `Quiz::SCOPE_LESSON` held `'module'` and `Quiz::SCOPE_TOPIC` held `'lesson'`
  — the worst possible arrangement, and a trap for the next reader. Now
  `SCOPE_MODULE` and `SCOPE_LESSON`. Stored values are unchanged.

344 passed, 1185 assertions.

### 2026-09-08 — The admin panel had no stylesheet of its own (Claude)

The "What's new" and "Success metrics" pages were written in Tailwind classes
that **did not exist in the CSS the panel loads**. Filament's shipped stylesheet
is precompiled from Filament's own source; utilities used only in this app's
Blade views are absent from it. Counted before the fix: **215 of 257** classes
on What's new and **41 of 61** on the KPI dashboard resolved to nothing. Both
pages had been rendering naked, and nothing anywhere said so.

`resources/css/filament/admin/theme.css` now re-runs Tailwind over the panel's
views (`@source` on `resources/views/filament/**` and `app/Filament/**`),
registered with `->viteTheme()` and built by Vite. It also defines `[x-cloak]`,
which Filament does not. `AdminThemeTest` pins all of it, because removing any
one of the three pieces silently unstyles every custom page again.

**What's new was then rebuilt.** The old version filtered only the *items*, so
searching left empty section headings and empty month cards standing behind the
results, and there was no empty state at all — `hasSearchResults()` was written
and never called. Filtering now runs over a matrix built server-side, so a
section with nothing left in it and a month with nothing left in it both
disappear, and "nothing matches X" is said once. The dark gradient hero with
radial glows and backdrop blur is gone: it contradicted the house style stated
at the top of `resources/css/app.css`, and it duplicated the page title
Filament already renders. 434 lines down to 215.

The KPI page needed no redesign — it was already in the house style, and was
only ever invisible.

**One rule added to §4a, from the user:** ignore the documentation's version
numbers. Take docs.pilot-gps.com as you find it — newer page where there is
one, the older page where there is not.

### 2026-09-08 — Admin panel content, and the duplicates under it (Claude)

**The course is written.** All 30 lessons of Admin panel now have bodies, and
26 of them carry a quiz — the four without are the four topics the
documentation does not support. 4 module knowledge checks, 26 lesson quizzes,
4 practical tasks.

Modules 2, 3 and 4 were rewritten from the documentation. The drafts they
replaced averaged about 300 characters a lesson and several of their facts were
not in the docs at all. What the sources actually say:

| Claim in the draft | What docs.pilot-gps.com says |
| --- | --- |
| "Go to the Objects tab, find the object by ID or IMEI, change its contract" | **Vehicles → Edit → the `Account` field**, and the transfer carries all the entered parameters |
| "Add the speed limit configuration key to the object" | **There is no speed configuration.** Speeding is a *notification*, needing the Notification module |
| "Activate the Geofences module" | The module is called **Geozone** |
| "Configure mileage by CAN" as a parameter | **CAN Mileage is a sensor type**, added to the object |
| "Historical data remains with the object" (a quiz answer) | Not documented anywhere |

Four topics are now `needs_input` rather than answered: *stock account*,
*account types*, *speed-control configuration* and *which configurations
control speed limits*. The last two are the same finding — the question has no
correct answer, and the lesson says so and gives the answer to use instead.
Two more carry partial content plus a `needs_input` note: the **block date**
(the two blocks and the grace period are documented; a date field is not) and
the **low-balance email template** (the `Low Balance Emails` configuration key
is documented; no template is named).

**Three duplicate-by-retitle bugs, found on the way and all live:**

- **Thirteen duplicate knowledge checks.** The lesson-to-module rename meant
  "Lesson 7 — knowledge check" no longer matched the content file's "Module 7 —
  knowledge check", so the next seed created a second one beside it. Both
  published, both module-scoped, and **every published module-scoped quiz gates
  the course** — 1st-line support had silently gone from 12 knowledge checks to
  24, the same questions twice each. `2026_09_08_000470`.
- **Then a fourteenth**, on "Final Assessment" against "Final assessment".
  `2026_09_08_000490` clears any module carrying more than one, and the seeder
  now keys on the module so a retitle is a rename.
- **Practical tasks the same way.** Retitling one either collided with the
  existing row's slug and killed the seeder mid-run, or slipped past and left
  two tasks gating the course. Now keyed on the slug. `2026_09_08_000480`
  retires the two Admin panel tasks written against the invented speed
  configuration and the undocumented low-balance template.

**And one that was hiding behind them:** re-seeding a revised quiz *added* its
questions instead of replacing them, because questions are matched by prompt.
Two Admin panel checks were sitting at nine questions — five current, four
superseded. `seedQuestions()` now prunes what the file no longer names, and
never touches a question somebody has answered.

`KnowledgeCheckUniquenessTest` runs the seeder twice and asserts the second run
changed nothing — count of checks, count of lesson quizzes, and count of
questions per quiz. That is the property that actually matters, and it had
never been asserted.

**Still empty:** Onboarding (12 lessons) and Support skills (23). Neither has a
single body, a quiz or a docs reference.

### 2026-09-08 — Support skills and Onboarding written (Claude)

Both remaining courses now have content. **Every lesson in the product has a
body and, except where a topic is unwritable, a quiz.**

**Support skills (23 lessons, all 23 with quizzes).** This course is not
documentation — every topic is an exercise, so the lesson is the *brief*: what
you do, what good looks like, how it is marked against the four rubric
criteria, and what to submit. A role-play brief asserts no PILOT facts, so
there is nothing in it to get wrong. Where a drill does need a platform fact it
reuses one already sourced in the other courses — Audit, the two-day Raw data
window, the five recalculation steps, CAN Mileage against Relative odometer,
module gating, the two blocks, `Confirm adding vehicles to notifications`.

Deliberately **not** written: the exact column names on the Sensors, Raw data
and Audit screens. Those documentation pages are image-based and the text does
not enumerate them. These are drills done at the live panel, so the trainee
reads the real columns off the real screen — which is the skill anyway.

**Onboarding (12 lessons, 10 with quizzes).** The hardest of the three to write
honestly, because almost every topic needs a company fact this repository does
not hold. The approach taken: teach the part that is generic and true in full,
and stop where policy begins, with a `needs_input` note naming exactly the
missing fact. A new starter learns how SLAs work now and fills in the figures
when somebody supplies them.

The tooling was recoverable — the module subtitle says **Zendesk and Jira**, so
public reply against internal note, one Jira issue to many Zendesk tickets, and
what belongs in which system are all teachable. Data privacy is the most
complete module: verify before disclosing, never verify using the thing being
requested, data minimisation, and report an incident before investigating it.

**A contradiction inside the curriculum, surfaced rather than smoothed over:**
Onboarding Module 1 says **Sev-1 / Sev-3** and Module 2 says **P1–P4**. Two
severity schemes in one course. Neither is taught as fact until somebody says
which is real.

**Open items this added to §5** — all of them things an agent must not invent:

| Needed | Blocks |
| --- | --- |
| Is this company controller or processor for driver data? | Support skills D5–D6, Onboarding M3 |
| The data subject request route and its deadline | Onboarding M3, Support skills D6 |
| The security incident reporting path, including out of hours | Onboarding M3 |
| The approved caller-verification questions | Onboarding M3 |
| The severity scheme, and the required fields per severity | Onboarding M1, M2 |
| First-response and resolution targets; business or calendar hours | Onboarding M2 |
| The team map — who owns what | Support skills C1 |
| Escalation criteria to second line | Support skills C2, Onboarding M2 |
| The desk's real five-point ticket standard | Support skills A5 |

That table is the shopping list. Every row is a fact somebody at the company
holds and nobody has written down, and every one of them is currently a hole a
trainee can see.

### 2026-09-08 — Spaced repetition, and the sixth metric (Claude)

PA-18, the last open dev ticket. §2 asks for "a 5-question refresher at 30 and
90 days, drawn from the passed level's bank" — and KPI 6, *90-day retention*,
has been reporting **not measurable** since the dashboard was built, because
nothing produced a second measurement to compare the first against. It reports
now.

**Refreshers are not `Quiz` rows, and that was the whole design decision.**
Modelling them as quizzes would have reused the entire engine for free, and it
would have been a disaster: a course-scoped quiz with no module and no lesson
*is* the course's final exam as far as `Course::finalQuizzes()`,
`RecalculateCourseProgress` and `QuizPolicy` are concerned — so every refresher
would have silently re-opened a course the trainee finished three months
earlier. Three bugs of exactly that shape were fixed earlier the same day.
`refreshers` and `refresher_answers` keep them out of every gating query by
construction, and a test asserts a refresher creates no quiz.

Two decisions that decide whether the metric means anything:

- **The baseline is frozen at the award.** KPI 6 is a percentage *of the
  original exam score*. Looked up at measurement time, a re-sat exam or a
  revised paper would move the denominator underneath the comparison, and
  retention would change without anybody's knowledge changing.
- **A missed refresher is not a zero.** It measures the process, not the
  person. Averaging it in as nought would report a collapse nobody has
  observed. `training:close-lapsed-refreshers` marks them missed after the
  three-week window, and the KPI's sample line says how many were excluded —
  a figure computed from half a cohort should say so.

Also: questions are drawn **when the refresher is sat**, not when it was
scheduled ninety days earlier, so it asks about the material as it stands; and
only auto-marked questions are eligible, because a written one would put a
trainer on a marking queue that grows with headcount and never ends. KPI 7
already watches trainer workload as a burnout signal.

The trainee sees it on the dashboard — nowhere else would do, since a refresher
arrives when they have no reason to be looking at that course at all — and the
result screen shows every question with its explanation, right or wrong. The
wrong ones are the entire point.

**Refreshers have an admin list** (`Reporting → Refreshers`), added the same
day. KPI 6 gives one number for the organisation; this is the same data with
names against it, which is the half somebody can act on. Read-only on purpose —
scheduling is done by the award, marking by the trainee sitting it, and editing
a score afterwards would make the retention figure a matter of opinion. A
trainer sees their own cohort, an admin sees everybody, which is the line the
grading queue already draws. The filter worth knowing is **Below the retention
target**: a cluster in one area is a finding about the material, not about the
people.
