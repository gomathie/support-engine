# What's new

Changes to Support Training Hub, newest first.

Written for the people who use the platform rather than the people who build it —
if a change does not alter what somebody can see or do, it is not here.

<!--
  This file is the single source of truth for the "What's new" screen in the
  admin panel, which reads and renders it directly. Keep the shape:

    ## <Month> <Year>        ← one per month, newest first
    ### Added | Changed | Fixed | Known limitations

  A new `##` heading becomes a new section on that screen automatically.
-->

## September 2026

### Added

- **Competency levels.** A trainee now holds a level *per area of expertise* —
  Basic, Second or Third in Access & Rights, Objects & Sensors, Reporting,
  Notifications, Escalation or Admin Panel. Somebody can be competent at
  reporting and not at devices, and a single overall level would hide exactly
  that. Levels are awarded automatically once every course the level requires is
  complete, and never before the rung below it in the same area.
- **"Who is Level 1 in Sensors."** The people list now carries a competency
  column and a level/area filter that answers this directly.
- **Video lessons, two ways.** Either embed a YouTube or Vimeo link, or upload a
  file (MP4, MOV or WEBM, up to 500 MB) that is served from private storage.
  Embeds use `youtube-nocookie` with related videos restricted, so the end of a
  training video is not a doorway to unrelated content.
- **Transcripts on video lessons**, searchable alongside the player. Adults scan
  before they watch, and auto-captioning mangles PILOT terminology.
- **Practical tasks.** Real work, submitted with evidence, marked against a
  standard four-criterion rubric — Correctness, Method, Verification,
  Communication, scored 0–4 each. The trainee can read the full rubric *before*
  starting.
- **Independent double-marking** on tasks flagged for calibration. Two trainers
  mark without seeing each other's scores. If they disagree on the verdict the
  submission does not settle — it is flagged for them to reconcile.
- **Pass/fail override.** An admin or the assigned trainer can overturn a marked
  result with a written reason. Every override is recorded permanently.
- **Cohorts.** Every trainee has one trainer, who marks their work. Assigning,
  reassigning and unassigning happen from the people list.
- **Assignment history**, a read-only record of every change of trainer: who
  moved, from whom, to whom, by whose hand, why, and how much unmarked work went
  with them.
- **Success metrics**, an admin screen carrying the nine agreed KPIs. Completion
  rate is deliberately not among them — it is what the old model already
  optimised for and it measures nothing about competence. Four of the nine cannot
  be calculated yet; the screen says which, and what each is waiting on, rather
  than leaving them out.
- **What's new** — this screen.

### Changed

- **Roles renamed** from Manager and Employee to **Trainer** and **Trainee**. The
  names describe what somebody does on the training portal, not where they sit on
  an org chart — a trainer is not necessarily anybody's line manager. Existing
  accounts and their permissions were carried across unchanged.
- **Reading a record and marking it are now separate rights.** A trainer can read
  any trainee's transcript, but can only mark the trainees assigned to them.
  These used to be one department-shaped rule; they answer different questions.
- **Nobody marks their own paper**, including administrators. Previously an
  administrator could grade their own attempt.
- **Overturning a pass now withdraws the level it earned**, where that specific
  attempt was the evidence for the award. A level earned by another route is left
  alone.

### Fixed

- An authorised training video could be cached by a shared proxy and served to
  somebody the permission check had just refused.
- Seeking in an uploaded video did not work on large files — the whole file was
  sent instead of the requested part, which made the scrubber useless.
- A malformed video URL was silently accepted and quietly corrected instead of
  being reported to the author.
- The handover figure on a reassignment counted unmarked written answers but not
  unmarked practical submissions, so it under-reported the work an incoming
  trainer was picking up.

### Known limitations

- **Uploaded video is not yet transcoded.** A large file is served as-is, which
  is hard going on a poor connection. Multiple quality levels need processing
  capacity on the server that is not in place yet.
- **Video duration is typed in by the author**, not detected from the file.
- **The final exam's answer key is unconfirmed.** The source examination document
  contains no answer key, so results on Section A should be treated as
  provisional until it is verified — and should not be used to award a level.

---

## August 2026

### Added

- **The platform itself**, rebuilt from the prototype onto a proper foundation:
  courses, modules and lessons, with progress recorded in the database rather
  than in the browser.
- **Progress tracking** — a dashboard gauge, per-course bars and completion
  counters, all driven by one authoritative figure per person per course.
- **Quiz engine** with server-side scoring, pass marks, attempt limits and
  attempt history. Answer keys never reach the browser.
- **Written answers marked by a person.** An attempt containing one waits for an
  examiner rather than being scored on the objective half alone — a partial score
  would read as a final one.
- **The full PILOT examination**, seeded as content that can be edited in the
  admin panel rather than hard-coded.
- **Certificates**, generated as PDFs on course completion, with public
  verification links.
- **Support Panel** — 13 diagnostic decision trees covering 102 checks, a
  priority matrix and a case-note generator for use during live calls.
- **Admin panel** for courses, lessons, quizzes, people, departments,
  enrollments and assignment rules.
- **Assignment rules**, so "this department gets this training" is a record that
  is evaluated continuously — moving somebody between departments changes what
  they are assigned.
- **Reminders** for training that is due soon or overdue.
- **A training report**, filterable by person, department, course, status and
  date, with CSV export.
- **Dark mode**, and a redesign onto the academy palette.

### Changed

- Renamed to **Support Training Hub**, with the PILOT logo and favicon applied
  throughout.
- The admin panel is a full page rather than a modal — the previous version
  opened inside a dialog where none of its menus worked.

### Fixed

- The pass mark is now recorded on each attempt as it is taken, so raising a
  quiz's pass mark can no longer retroactively fail somebody who already sat it.
- Employees could not see their own quiz results, and submitting an attempt over
  the web failed.
- Certificate generation ran out of memory after the logo was added.
- Text on the dark-mode gradient was effectively invisible against its
  background.
- The lesson navigation sent people to the first lesson of a course instead of
  the last one they were on.
