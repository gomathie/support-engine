# From Checkbox Completion to Assessment-Driven Competency
## Implementation Plan — PILOT Support Training Hub

**Version 1.0 · Prepared for:** Igor (content), Nazih (infrastructure), Matthias (development), Leadership
**Status:** Draft for review at the Wednesday 10:00 checkpoint

---

## 1. Executive Summary

### This is a re-alignment project, not a greenfield build

Two things are true at once, and conflating them has already distorted the conversation:

**The platform is further along than the meeting assumed.** A complete assessment engine already exists and is running — question types (single/multiple choice, true-false, short answer, long-form written), attempts with snapshotted pass marks, time limits, full-paper retakes, attempt history, a human grading queue where a mentor marks written work with points and feedback, and automatic certificate issue with a public verification link. The meeting minute stating *"certificates have not yet been added"* is out of date; they were built and are live.

**The content is exactly as weak as Igor said.** All 103 seeded lessons carry a title and nothing else — no body, no video, no questions — and every one is set to `completion_requirement = acknowledge`, which is the literal checkbox. An employee can complete the entire 1st-line curriculum without demonstrating a single thing.

> **The bottleneck is curriculum, not code.** Roughly 70% of the effort in this plan is instructional design and content authoring. That changes who sits on the critical path: it is Igor and Matthias writing and re-aligning material, not a development sprint.

### Current → Target

| Dimension | Current State | Target State |
| --- | --- | --- |
| Completion evidence | Self-attested checkbox | Graded assessment + practical deliverable |
| Learning objectives | None stated | Explicit, mapped to Bloom's |
| Media | Text placeholders only | Video (embed **and** native upload), theory, practice |
| Structure | Flat course → module → lesson | Level 1/2/3 competency ladder with expertise areas |
| Verification | None | Formative knowledge checks + summative graded exam |
| Human judgement | None | Trainer-graded practical tasks with rubric |
| Progression | Implicit | Level awarded on exam pass; full retake on failure |
| Failure handling | N/A | Mandatory complete re-sit |

### Guiding standards

- **ADDIE** governs the phase structure — Analyse (Phase 1 audit) precedes Design and Development, which is why no new content is built before the audit completes.
- **Bloom's Taxonomy** governs question and objective design — the deliberate move is from *Remember* to *Apply* and *Analyze*.
- **Kirkpatrick** governs measurement — we report at Level 2 (learning) and Level 3 (behaviour), not just Level 1 (reaction) or raw completion.
- **Andragogy** (Knowles) governs format — problem-centred, immediately applicable, respecting that these are working adults on a support desk.

---

## 2. Phased Rollout Strategy

| Phase | Name | Duration | Exit criterion |
| --- | --- | --- | --- |
| **0** | Foundation & stabilisation | Week 1 | Portal reachable on Nazih's server, RBAC migrated, storage sized |
| **1** | Content audit & re-alignment | Weeks 1–3 | Every existing lesson dispositioned; matrix signed off |
| **2** | Pilot build | Weeks 3–5 | 5–10 lessons live in new model, 10 trainees through end-to-end |
| **3** | Scaling & refinement | Weeks 5–10 | All Level 1 re-aligned; question banks Bloom-balanced |

> **Wednesday 10:00 is a progress checkpoint, not a launch.** See §6(b). What is realistically demonstrable Wednesday is: the portal on Nazih's server, the audit methodology agreed, and the first 5 audited lessons. Not a finished Level 1.

---

### Phase 0 — Foundation & Stabilisation
*Absolute prerequisite — no new features until complete.*

The portal is slow because it runs in Docker on Matthias's laptop, not because of GitHub. GitHub is version control; it serves nothing. Correcting this misdiagnosis matters because it changes what Nazih is being asked for.

#### 0.1 Server deployment (Nazih)

| Item | Requirement | Notes |
| --- | --- | --- |
| Runtime | PHP 8.3+, PostgreSQL 17, Node 22 for asset build | `docker-compose.yml` already defines app + db + queue |
| Queue worker | Must run persistently | Certificates and notification email are queued; without a worker no certificate is ever produced |
| Scheduler | `schedule:run` every minute via cron | Drives `training:send-reminders` and `training:sync-assignments` |
| TLS | Required | Video, personal data, exam integrity |
| Backups | Nightly DB dump + storage snapshot, 30-day retention | Exam results are a compliance record |

#### 0.2 Storage & bandwidth sizing
*Must be decided before any Phase 2 upload work.*

Sizing at 5–7 minute microlearning, 1080p H.264, ~8 Mbps:

| Scenario | Per video | 50 videos | 200 videos |
| --- | --- | --- | --- |
| Source upload | ~300 MB | 15 GB | 60 GB |
| HLS renditions (360/720/1080) | ~450 MB | 22 GB | 90 GB |
| **Total storage** | ~750 MB | **~37 GB** | **~150 GB** |

Bandwidth: 30 concurrent trainees at 720p ≈ 150 Mbps sustained. **Nazih must confirm both figures before native upload is committed.** If the server cannot carry it, embed-only ships in Phase 2 and upload moves behind a CDN decision.

#### 0.3 RBAC migration — role rename and cohort model

The current implementation uses `admin` / `manager` / `employee` via `spatie/laravel-permission`, with trainers scoped by **department** (`managedDepartments`, `visibleDepartmentIds()`). The target model scopes by **assigned cohort**, which does not exist yet.

Required work:

- Rename roles to `admin` / `trainer` / `trainee` (data migration on the `roles` table; the three names are referenced in policies and the `Role` enum).
- New table `trainer_trainee` — `trainer_id`, `trainee_id`, `assigned_by`, `assigned_at`, `ended_at` (nullable, for history).
- New table `trainer_assignment_logs` — every assign/reassign with actor, timestamp, reason.
- Split the two visibility rules that are currently one: **view all transcripts (read-only)** vs **grade only assigned cohort**. Today a manager sees only their department for both.
- Add permissions: `trainees.assign`, `trainees.reassign`, `transcripts.view-all`, `content.audit`, `videos.manage`, `grades.override`.

#### 0.4 Trainee accounts

Every trainee needs an individual login — already supported. The meeting item *"separate logins so they can complete practical tasks"* most likely means **PILOT test-environment credentials**, not portal accounts. Clarify with Igor: the practical tasks in Section C require a sandbox with admin-panel visibility and a restricted test user. That is a PILOT platform provisioning task, not a portal one.

**Phase 0 exit:** portal reachable over TLS on Nazih's server · queue and scheduler running · roles migrated · cohort tables created · storage decision recorded.

---

### Phase 1 — Content Audit & Re-alignment
*The critical path — PRIORITY 1.*

**No new content is authored until a lesson has passed audit.** This is the ADDIE "Analyse" gate, and skipping it is how organisations end up maintaining two parallel curricula.

#### The "No Orphaned Content" Rule

Every one of the 103 existing lessons receives exactly one disposition. Nothing stays a checkbox-only dead end.

| Disposition | Meaning | Expected share |
| --- | --- | --- |
| **UPGRADE** | Sound objective, needs media / questions / practice added | ~50% |
| **MERGE** | Overlaps another lesson; combine into one coherent unit | ~25% |
| **RETIRE** | Obsolete, trivial, or duplicated elsewhere — archived, not deleted | ~15% |
| **SPLIT** | Covers several objectives; break into separate lessons | ~10% |

Retired content is **archived** (`is_published = false`), never destroyed — existing progress records reference it.

#### Audit method — six questions per lesson

For each lesson the auditor answers, in order:

1. **What should the learner be able to *do* afterwards?** Write it as a verb phrase. If you cannot, the lesson has no objective and is a MERGE or RETIRE candidate.
2. **Which Bloom's level does that verb sit at?**
3. **Is that the right level for Level 1?** Level 1 should centre on *Understand* and *Apply*. A lesson stuck at *Remember* needs an application component or should merge into a larger unit.
4. **What evidence would prove it?** This becomes the assessment item.
5. **What is missing?** Video / theory body / knowledge check / practical task.
6. **Does it overlap anything else?** Drives MERGE.

#### Bloom's mapping for Level 1

| Bloom's level | Verbs | Use at Level 1 | Assessment form |
| --- | --- | --- | --- |
| Remember | define, list, name | Sparingly — foundations only | Auto-marked MCQ |
| Understand | explain, describe, distinguish | Core | MCQ, short answer |
| **Apply** | configure, calculate, use, resolve | **Core — the Level 1 target** | Scenario MCQ, practical task |
| **Analyze** | diagnose, differentiate, troubleshoot | Stretch for Level 1 | Scenario, written answer |
| Evaluate | justify, prioritise, critique | Level 2 | Written answer |
| Create | design, author, build | Level 3 | Practical deliverable |

> Support work is overwhelmingly *Apply* and *Analyze*. A Level 1 exam that only tests *Remember* certifies people who cannot do the job — which is precisely Igor's objection.

#### Content Re-alignment Matrix — template

Maintain as a shared sheet. One row per existing lesson. **This is the Phase 1 deliverable.**

| # | Lesson Name | Course / Module | Current Format | Learning Objective (verb phrase) | Bloom's | Gaps Identified | Required Upgrade | Disposition | Merge Target | Owner | Priority | Est. hrs | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Define: Object, Sensor, Contract, Account | 1st-line / Day 1 | Checkbox | *Distinguish* the four core PILOT entities and state what each governs | Understand | No body, no check | 3-min video + 4 MCQ | UPGRADE | — | Igor | P1 | 3 | Not started |
| 2 | Explain Personal Account vs Admin Panel | 1st-line / Day 1 | Checkbox | *Determine* which interface a given task requires | Apply | No body, no scenario | Theory + 2 scenario MCQ | UPGRADE | — | Igor | P1 | 2 | Not started |
| 3 | Log in to the system | 1st-line / Day 2 | Checkbox | — (trivial) | Remember | No standalone value | Fold into Day 1 orientation | MERGE | #1 | Igor | P3 | 0.5 | Not started |
| 4 | Add an ignition sensor; verify via Points | 1st-line / Day 6 | Checkbox | *Configure* a two-position sensor and *verify* it from raw data | Apply | No video, no practical | Screen-capture video + practical task w/ rubric | UPGRADE | — | Matthias | P1 | 5 | Not started |
| 5 | Answer: what is a stock account? | Admin / Day 1 | Checkbox | *Explain* stock account purpose | Understand | Q&A framing, not a lesson | Convert to exam question | RETIRE | Q-bank | Igor | P2 | 0.5 | Not started |

**Column rules**

- *Learning Objective* — must start with a Bloom's verb. "Understand X" is not acceptable; "Distinguish X from Y" is.
- *Priority* — P1 = required for the Level 1 exam · P2 = Level 1 supporting · P3 = defer to Level 2
- *Owner* — a named person, never a team
- *Est. hrs* — authoring time including video capture

#### Phase 1 sequencing

| Step | Activity | Owner | Output |
| --- | --- | --- | --- |
| 1.1 | Agree matrix columns and Bloom's verb list | Igor + Matthias | Signed-off template |
| 1.2 | **Pilot audit: 5–10 lessons** (see §6e) | Igor + 1 Trainer | Calibrated method, revised time estimate |
| 1.3 | Full audit of remaining ~95 lessons | Igor + Trainers | Completed matrix |
| 1.4 | Disposition review — challenge every RETIRE | Igor + Matthias | Approved dispositions |
| 1.5 | Sequence UPGRADE work by priority | Matthias | Phase 2 backlog |

**Phase 1 exit:** 100% of existing lessons dispositioned · matrix signed off · no lesson left as checkbox-only.

---

### Phase 2 — Pilot Build

Build the machinery on a deliberately small slice: **5–10 audited lessons, 10 trainees, 2 trainers.**

#### 2.1 Levels & competency model
*New — the largest build item.*

```
levels                 id, name (Basic/Second/Third), position, description
competency_areas       id, name (Sensors, Contracts, Reporting, Escalation…)
level_requirements     level_id, course_id, competency_area_id
trainee_levels         user_id, level_id, competency_area_id, awarded_at,
                       awarded_by, quiz_attempt_id, revoked_at
```

Attach `level_id` and `competency_area_id` to `courses`. Award a level on passing its exam. Surface on the trainee record, the trainer's cohort view, and reporting — *"who is Level 1 in Sensors"* is the question this must be able to answer.

#### 2.2 Video module — both methods
*Full specification in §5.*

- **Embed:** new `LessonType::VideoEmbed` — URL field, validated host allowlist, rendered as a privacy-enhanced iframe.
- **Upload:** new `LessonType::VideoUpload` — reuses the existing `lesson_resources` private-disk pattern; transcode to HLS; served through a policy-checked route. **Gated on the Phase 0 storage decision.**

#### 2.3 Formative knowledge checks

Embedded per lesson, low stakes, unlimited attempts, immediate feedback — these teach rather than judge. Already supported: a quiz scoped to a lesson, with `completion_requirement = quiz`.

#### 2.4 Summative graded exam

Already supported. Configuration for Level 1: pass mark 70% · full-paper retake · time limit · feedback released after grading.

#### 2.5 Trainer review workflow

The grading queue already exists. Phase 2 adds cohort filtering (grade only assigned trainees), the rubric fields below, and a pass/fail override action.

**Phase 2 exit:** 10 trainees complete a re-aligned lesson → knowledge check → practical task → graded exam → level awarded, end to end.

---

### Phase 3 — Scaling & Refinement

- Roll the audited backlog through the Phase 2 pipeline, in priority order.
- **Bloom-balance the question bank** — target for Level 1: ≤30% Remember/Understand, ≥50% Apply, ≥20% Analyze. Audit the existing 40-question Section A against this; it currently skews to recall.
- **Spaced repetition** — a 5-question refresher at 30 and 90 days, drawn from the passed level's bank. Feeds the 90-day retention KPI.
- **Admin heatmaps** — completion and first-time-pass by department, course and question, exposing which items everyone fails (usually a content defect, not a cohort defect).
- Begin the Level 2 audit using the now-calibrated method.

---

## 3. Role-Based Access Control & Workflow Definitions

### Permission matrix — target, with current build status

| Capability | Admin | Trainer | Trainee | Status in current build |
| --- | :---: | :---: | :---: | --- |
| Assign Trainees to Trainers | ✅ | ❌ | ❌ | **Missing** — scoping is by department, not cohort |
| Reassign Trainees mid-course (audit logged) | ✅ | ❌ | ❌ | **Missing** — no assignment log |
| View all Trainee transcripts (read-only) | ✅ | ✅ | ❌ own only | **Partial** — trainers see own department only |
| Grade / interact ONLY with assigned Trainees | ✅ | ✅ | ❌ | **Partial** — department-scoped, not cohort |
| Create / upload / embed video content | ✅ | ✅ *(if granted)* | ❌ | **Missing** — no video lesson type |
| Build quizzes / exams | ✅ | ✅ *(if granted)* | ❌ | ✅ Built (`quizzes.manage`) |
| **Audit & re-align existing checkbox lessons** | ✅ | ✅ *(if granted)* | ❌ | ✅ Built (`lessons.manage`) |
| Consume courses, take exams, submit tasks | ✅ | ✅ | ✅ | ✅ Built |
| Override pass/fail for assigned Trainees | ✅ | ✅ | ❌ | **Partial** — awards points; no direct override |
| Retake failed exam (mandatory full re-sit) | N/A | N/A | ✅ | ✅ Built — a new attempt is always the whole paper |

*"if granted"* = the capability is a permission on the Trainer role, revocable per person. `spatie/laravel-permission` is already wired for exactly this.

### Progression logic

```
Admin assigns Trainee → Trainer
      ↓
Trainer (or Admin) assigns Level 1 courses
      ↓
Trainee completes modules — video, theory, knowledge checks
      ↓
Trainee submits practical task(s)
      ↓
Trainer grades practical against the rubric
      ↓
Trainee sits the graded exam
      ↓
   ┌── PASS → Level 1 awarded automatically
   │          (Trainer override available for edge cases)
   │
   └── FAIL → full re-sit required; no partial credit carried
```

**Decision — auto-promote, not manual.** You asked us to choose one.

*Rationale:* the Trainer already exercises judgement where judgement belongs — grading the practical task and the written answers, both of which feed the score. Requiring a second manual promotion after that adds a queue without adding rigour, and at 30+ trainees it becomes the bottleneck described in §6(a). The override covers the genuine exceptions: a pass secured on a question later found defective, or a fail a Trainer wants to overturn with a recorded reason.

**Reassignment.** Admin changes the Trainer mid-course → row written to `trainer_assignment_logs` (actor, timestamp, reason) → prior assignment closed with `ended_at` → **the new Trainer inherits all pending grading**. Completed grades keep their original grader for audit.

---

## 4. Content Re-alignment Standards

### 4.1 Video strategy

| Rule | Requirement | Rationale |
| --- | --- | --- |
| Length | 5–7 min hard ceiling | Microlearning; attention falls sharply past ~6 min |
| One objective per video | Enforced at audit | Enables reuse and targeted re-watch |
| Uploads | Transcode to HLS, 360/720/1080 | Support desks on poor connections |
| Embeds | `youtube-nocookie.com`, `rel=0`, no suggested videos | Privacy; stops drift to unrelated content |
| Captions | Auto-generated, **manually corrected** | Accessibility; PILOT terminology defeats auto-captioning |
| Transcript | Interactive, searchable | Adults scan before they watch |
| Intro | ≤5 seconds | No branding preamble on internal training |

### 4.2 Question Design Template

Every summative question uses this form:

```
STEM        A realistic support scenario, 2–4 sentences.
            Include the noise a real ticket contains.
ASK         One clear question. No "which of the following is NOT…".
OPTIONS     4 options, one BEST answer.
            Distractors must be plausible — each should be
            the answer to a common misconception.
BLOOM'S     Apply | Analyze   (Remember only for foundations)
RATIONALE   Why the key is right AND why each distractor is wrong.
            Shown after grading.
MAPS TO     Lesson ID + learning objective
```

**Worked example**

> **Stem:** A customer reports fuel readings dropped to zero overnight on one vehicle. Sensors tracing shows the raw value arriving normally and the satellite count is 9.
>
> **Ask:** What should you check next?
>
> **A.** Replace the fuel probe — *(distractor: jumping to hardware; raw data is fine)*
> **B.** The calibration table — *(distractor: right area, wrong order)*
> **C.** The field mapping, then the conversion formula — *(**key**: raw good ⇒ configuration; correct order)*
> **D.** Escalate to 2nd line — *(distractor: premature)*
>
> **Bloom's:** Apply · **Maps to:** L-042 *Diagnose a fuel discrepancy using the layer model*

Anti-patterns to reject at review: "all of the above" · negative stems · options of tell-tale unequal length · anything answerable without having taken the lesson.

### 4.3 Practical Task Rubric

Standard four-criterion rubric, 0–4 each. **A comment is mandatory on any criterion scoring ≤2.**

| Criterion | 0 — Not shown | 1–2 — Developing | 3 — Proficient | 4 — Exemplary |
| --- | --- | --- | --- | --- |
| **Correctness** | Result wrong or absent | Partially correct; needed prompting | Correct result | Correct, with edge cases handled |
| **Method / sequence** | No discernible order | Steps out of order | Logical, efficient order | Order justified against the layer model |
| **Verification** | None | Claimed without evidence | Verified and shown | Verified plus a negative check |
| **Communication** | Cannot explain | Explains with prompting | Explains clearly | Explains to a non-technical audience |

**Pass threshold:** ≥3 on *Correctness* **and** ≥2 on every other criterion. Total ≥10/16.

Two Trainers must independently score the first five submissions and reconcile — calibration prevents rubric drift.

---

## 5. Technical & Workflow Requirements

### 5.1 Video Upload Spec Sheet

| Property | Specification |
| --- | --- |
| Accepted formats | MP4 (H.264/AAC), MOV, WEBM |
| **Max file size** | **500 MB** — reject with: *"This file is 640 MB. The limit is 500 MB. Trim to 5–7 minutes or export at 1080p."* |
| Max duration | 10 min hard limit (7 min guideline) |
| Storage | Private disk, no public URL |
| Processing | Queued job → probe → transcode HLS (360/720/1080) → poster frame → captions |
| Delivery | Signed, expiring URL via a policy-checked controller |
| Retention | Source kept 30 days post-transcode, then renditions only |
| Permissions | Upload: Admin, Trainer *(if granted)* · View: any enrolled Trainee |

**Upload workflow**

```
Trainer selects file
  → client-side size / type / duration check (fail fast)
  → chunked upload to private disk
  → queued: probe → transcode → captions → poster
  → status: processing | ready | failed   (visible in admin)
  → on ready, the lesson becomes publishable
```

This reuses the existing private-disk pattern from `lesson_resources` — those files already never get a public URL and are streamed through a policy check. The video module extends that rather than inventing a second mechanism.

**Embed workflow**

```
Trainer pastes URL
  → validate against host allowlist (YouTube, Vimeo only)
  → extract the video ID; reject anything else
  → store the canonical ID, not the raw URL
  → render youtube-nocookie iframe, rel=0, modestbranding
```

Storing the ID rather than the pasted URL prevents tracking-parameter and open-redirect smuggling.

### 5.2 Assessment logic

| Behaviour | Specification | Status |
| --- | --- | --- |
| Auto-scoring | Objective questions marked on submission | ✅ Built |
| Written / practical | Park attempt at `pending_review`; **no score shown** until marked | ✅ Built |
| Partial score suppression | The objective half alone must never display as a result | ✅ Built |
| Trainer override | Set pass/fail directly; reason mandatory; logged | **To build** |
| Full retake | New attempt = entire paper; nothing carried forward | ✅ Built |
| Retake cap | **Decision needed** — currently 3 |
| Pass mark snapshot | Frozen at attempt start | ✅ Built |
| Cohort restriction | Grade only assigned trainees | **To build** |

### 5.3 Audit logging

An immutable log for: grading actions · pass/fail overrides (with reason) · trainer reassignments · level awards and revocations · exam configuration changes · content publish/retire.

Each entry records actor, subject, action, before/after, timestamp, IP. Append-only; never editable from the UI.

---

## 6. Risk Mitigation

**(a) Trainer bottleneck at scale.** Written and practical grading is linear in trainee count. At 30 trainees × 15 written answers × ~4 min ≈ 30 hours per cohort.

*Mitigation:* a tiered model — **Senior Trainer** delegates who can grade outside their own cohort during surges; peer review for formative (not summative) work; a rubric tight enough that grading is judgement-light; and a grading queue with age-based prioritisation so nothing sits. Track KPI #7 as an early warning.

**(b) Wednesday is a checkpoint, not a launch.** State this explicitly at the meeting. A defensible Wednesday demonstration is: portal live on Nazih's server · audit methodology agreed · first 5 lessons audited · video module demonstrable on one lesson. A complete, assessed Level 1 by Wednesday is **not achievable**, and committing to it produces either a missed date or content that repeats the current problem in a new wrapper.

**(c) YouTube rights and availability.** Third-party videos can be removed, made private, or carry restrictive licences.

*Mitigation:* only embed content the company owns or has written permission for; record the licence per embed in the matrix; run a quarterly link check; for anything load-bearing, host it natively.

**(d) Uploaded video file size.** 500 MB ceiling with the explicit error message in §5.1. Validate client-side *before* the upload starts — a rejection after a five-minute upload is the worst possible outcome. Publish a one-page export preset (1080p, H.264, ~8 Mbps).

**(e) Content re-alignment takes longer than estimated.** The single largest schedule risk: 103 lessons at an unknown per-lesson cost.

*Mitigation:* **the Pilot Group is the control.** Audit 5–10 lessons first, measure the actual time per lesson, then extrapolate before committing to a date. If the pilot shows 45 min/lesson, the full audit is ~75 hours and the plan must be re-timed openly rather than quietly slipped.

**(f) Accidental role escalation.** A trainer granted content permissions must not thereby gain user management or cross-cohort grading.

*Mitigation:* capabilities are separate named permissions, not role tiers; policies deny by default; **nobody grades their own attempt** — already enforced and covered by tests, including for admins; the matrix in §3 is backed by automated tests asserting each denial.

**(g) Unverified exam answer key.** *Live issue.* The 40-question Section A exam is **published** with an answer key whose source document contains no key — the "Answer Key for the Examiner" heading is present but empty. The answers read as sound reasoning but are unverified.

*Mitigation:* Igor confirms all 40 answers **before** the exam awards Level 1. Until then, treat Section A results as provisional.

---

## 7. Success Metrics (KPIs)

Completion rate is deliberately excluded as a headline metric — it is what the current model already optimises, and it measures nothing.

| # | KPI | Definition | Target | Kirkpatrick | Cadence |
| --- | --- | --- | --- | --- | --- |
| 1 | **First-time pass rate** | Passed on attempt 1 ÷ all first attempts | 65–80% | L2 | Weekly |
| 2 | **Lessons re-aligned** | Dispositioned ÷ 103 | 100% by end of Phase 3 | — | Weekly |
| 3 | **Retire ratio** | Retired ÷ audited | <25% *(higher ⇒ audit too aggressive)* | — | Phase 1 |
| 4 | **Video engagement** | Median watch % — upload vs embed | >70% | L1 | Monthly |
| 5 | **Practical task quality** | Mean rubric total /16 | >12 | L2/L3 | Per cohort |
| 6 | **90-day retention** | Refresher score at day 90 | >75% of original exam score | L2 | Quarterly |
| 7 | **Trainer workload** | Median hrs/week grading per Trainer | <6 *(burnout signal)* | — | Weekly |
| 8 | **Time-to-competency** | Assignment → Level 1 awarded | <20 working days | L3 | Per cohort |
| 9 | **Item difficulty** | Per-question pass rate | Flag <30% or >95% | — | Monthly |

> **Reading #1 correctly.** A first-time pass rate above 90% means the exam is too easy, not that training succeeded. Below 50% means the content does not teach what the exam tests. The band is the target.

> **#9 finds content defects.** A question everyone fails is usually a bad question or an untaught topic — not a bad cohort.

---

## 8. Stakeholder Communication & Jira Task Breakdown

### Meeting cadence

| Meeting | When | Attendees | Purpose |
| --- | --- | --- | --- |
| **Level 1 checkpoint** | Wednesdays 10:00 | Igor, Matthias, Nazih | Progress vs plan; unblock |
| Content working session | Mon + Thu, 90 min | Igor, Matthias, Trainers | Audit lessons together — calibration matters more than throughput early on |
| Deployment sync | Weekly until Phase 0 closes | Nazih, Matthias | Server, storage, TLS, backups |
| Rubric calibration | Once, then monthly | All Trainers | Score the same submissions; reconcile |
| Leadership readout | Fortnightly | Leadership + Igor | KPIs from §7 only |

### Jira project — `PILOT-ACADEMY`

| Key | Ticket | Priority | Phase | Owner | Est. | Depends on |
| --- | --- | --- | --- | --- | --- | --- |
| PA-1 | Server deployment & storage setup | **P0** | 0 | Nazih | 3 d | — |
| PA-2 | Queue worker + scheduler in production | P0 | 0 | Nazih | 0.5 d | PA-1 |
| PA-3 | RBAC — role rename admin/trainer/trainee | P0 | 0 | Dev | 1 d | PA-1 |
| PA-4 | Trainer↔Trainee cohort tables + audit log | P0 | 0 | Dev | 2 d | PA-3 |
| PA-5 | **Content Audit & Re-alignment Matrix creation** | **P1** | 1 | Igor | 2 d | — |
| PA-6 | **Pilot audit — 5–10 lessons (calibration)** | **P1** | 1 | Igor + Trainer | 2 d | PA-5 |
| PA-7 | Full audit — remaining ~95 lessons | P1 | 1 | Igor + Trainers | TBD *(size after PA-6)* | PA-6 |
| PA-8 | Levels & competency-area schema | P1 | 2 | Dev | 3 d | PA-4 |
| PA-9 | Video module — embed (YouTube/Vimeo) | P1 | 2 | Dev | 2 d | PA-1 |
| PA-10 | Video module — native upload + HLS | P2 | 2 | Dev | 5 d | PA-1, storage sign-off |
| PA-11 | Trainer grading interface — cohort scoping | P1 | 2 | Dev | 2 d | PA-4 |
| PA-12 | Pass/fail override + reason + audit log | P1 | 2 | Dev | 1 d | PA-11 |
| PA-13 | Trainee reassignment logic + grading inheritance | P2 | 2 | Dev | 2 d | PA-4 |
| PA-14 | Practical task submission + rubric UI | P1 | 2 | Dev | 3 d | PA-11 |
| PA-15 | **Pilot rollout — 5 lessons, 10 trainees, 2 trainers** | **P1** | 2 | Igor + Matthias | 5 d | PA-6…PA-14 |
| PA-16 | Verify Section A answer key (40 questions) | **P0** | 1 | Igor | 0.5 d | — |
| PA-17 | Bloom-balance the question bank | P2 | 3 | Igor | TBD | PA-15 |
| PA-18 | Spaced-repetition refreshers | P3 | 3 | Dev | 3 d | PA-15 |
| PA-19 | Admin heatmaps & KPI dashboard | P2 | 3 | Dev | 3 d | PA-15 |

**Definition of Done** — every content ticket: objective written and Bloom-mapped · media attached or embedded · knowledge check authored · mapped to an exam item · peer-reviewed by a second Trainer · published.

---

## Appendix A — Immediate next actions

| # | Action | Owner | By |
| --- | --- | --- | --- |
| 1 | Confirm server specs, storage and bandwidth | Nazih | Wednesday |
| 2 | Confirm all 40 Section A answers | Igor | Wednesday |
| 3 | Approve the Re-alignment Matrix template | Igor + Matthias | Wednesday |
| 4 | Select the 5–10 pilot lessons | Igor | Wednesday |
| 5 | Nominate 2 pilot Trainers and 10 Trainees | Leadership | Wednesday |
| 6 | Create the Jira project and load PA-1…PA-19 | Matthias | Wednesday |
| 7 | Decide the retake cap (currently 3) | Igor + Leadership | Wednesday |
| 8 | Decide: does Level 1 require Sections A+B+C, or A alone? | Igor | Wednesday |

## Appendix B — Open decisions

1. **Retake cap** — unlimited, or capped at 3? Unlimited risks answer-memorisation against a fixed 40-question bank; a cap needs a documented exception path.
2. **Level 1 scope** — Section A alone (auto-marked, fast) or A+B+C (rigorous, but puts a Trainer on every trainee's critical path)?
3. **Native video upload** — commit now, or embed-only until a CDN decision? Depends on Nazih's storage answer.
4. **Competency areas** — what is the actual list? Proposed: Access & Rights · Objects & Sensors · Reporting · Notifications · Escalation · Admin Panel.
5. **Level 1 exam weighting** — if practical tasks count toward the level, what share of the total?
