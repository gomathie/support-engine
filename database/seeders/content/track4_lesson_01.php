<?php

/**
 * Onboarding — Module 1: Ticketing system basics (Zendesk and Jira).
 *
 * The first course a new starter takes, and the one with the least PILOT in
 * it. Nothing here comes from docs.pilot-gps.com; it is tooling and desk
 * practice.
 *
 * **What is teachable and what is not.** Zendesk and Jira are widely
 * documented products, and how they work — public reply against internal note,
 * requester and assignee, an issue key, a link between the two systems — is
 * stable and safe to teach. What is *not* knowable from here is how this desk
 * configures them: which fields are required on which form, the severity
 * scheme, and the linking convention. Those are marked `needs_input`, with
 * exactly what is needed.
 *
 * One inconsistency is flagged rather than smoothed over: this module's
 * curriculum says **Sev-1 / Sev-3** and the next module's says **P1–P4**.
 * Those are two different schemes in one course, and somebody has to say which
 * is real before either is taught as fact.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Navigating Zendesk and Jira',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Introduction to the ticketing interface' => [
            'docs' => null,
            'estimated_minutes' => 25,
            'body' => <<<'HTML'
<p><strong>Two systems, two audiences. Getting them the wrong way round is the single most common
mistake in a new starter's first fortnight, and it is visible to the customer.</strong></p>

<h2 id="two-systems">What each one is for</h2>

<table>
<thead>
<tr><th></th><th>Zendesk</th><th>Jira</th></tr>
</thead>
<tbody>
<tr><td><strong>Audience</strong></td><td>The customer.</td><td>Engineering.</td></tr>
<tr><td><strong>Holds</strong></td><td>The conversation and the commitment.</td><td>The work on the product itself.</td></tr>
<tr><td><strong>Answers</strong></td><td>"What has this customer been told, and what are we going to do?"</td><td>"What is being changed, by whom, and when will it ship?"</td></tr>
<tr><td><strong>Closed when</strong></td><td>The customer's situation is resolved.</td><td>The change is done.</td></tr>
</tbody>
</table>

<blockquote><p><strong>They close at different times, and that is correct.</strong> A customer can be
working again — on a workaround — long before the underlying defect ships. Holding the Zendesk
ticket open until Jira is done leaves somebody waiting for something they never asked for.</p></blockquote>

<h2 id="the-anatomy">The parts of a ticket</h2>

<ul>
<li><strong>Requester</strong> — whose problem it is. Not always the person who wrote in.</li>
<li><strong>Assignee</strong> — who owns it now. A ticket with no assignee is a ticket nobody is
    working.</li>
<li><strong>Status</strong> — where it is in its life. Open, pending on the customer, solved.</li>
<li><strong>Priority</strong> — how it competes with everything else in the queue.</li>
<li><strong>The conversation</strong> — every message, in order, permanently.</li>
</ul>

<h2 id="public-vs-internal">The distinction that matters most</h2>

<blockquote><p><strong>A public reply goes to the customer. An internal note does not.</strong> They
sit in the same place on the screen and they are one click apart.</p></blockquote>

<p>Everybody who has done this job has, at some point, put "this customer is being unreasonable, I
think their kit is just old" into the wrong box. It cannot be unsent. Before every submission, look
at which one is selected — and write internal notes as though they might be read by the customer
anyway, because occasionally they are, in a data subject request.</p>

<h2 id="what-is-not-here">What this lesson cannot tell you</h2>

<blockquote><p><strong>How this desk has configured Zendesk is not recorded anywhere.</strong> The
views you work from, the fields on the form, the statuses in use and the queue you own are local
choices. Walk through the live instance with your buddy in your first session, and write down what
you see — see the note on this lesson.</p></blockquote>
HTML,
            'needs_input' => 'The generic tooling is teachable and is taught. What is missing is '
                .'the local configuration: which Zendesk views a new agent works from, the '
                .'statuses actually in use, and who owns which queue. Igor or the desk lead to '
                .'supply, or confirm that a buddy walkthrough covers it.',
            'quiz' => [
                'title' => 'Zendesk and Jira',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'The customer is working again on a workaround, but the underlying defect has not shipped. What happens to the Zendesk ticket?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It closes. The customer\'s situation is resolved; the Jira issue tracks the product change and closes on its own schedule.',
                        'options' => [
                            ['text' => 'It closes — the Jira issue stays open on its own schedule', 'correct' => true],
                            ['text' => 'It stays open until the Jira issue ships', 'correct' => false],
                            ['text' => 'It is merged into the Jira issue', 'correct' => false],
                            ['text' => 'It is reassigned to engineering', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why should an internal note be written as though the customer might read it?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Two reasons: the public reply and internal note controls are one click apart, and notes can surface in a data subject request.',
                        'options' => [
                            ['text' => 'It is one click from being public, and it can surface in a data subject request', 'correct' => true],
                            ['text' => 'Internal notes are emailed to the account manager', 'correct' => false],
                            ['text' => 'Customers can see notes once a ticket is solved', 'correct' => false],
                            ['text' => 'Notes are copied into Jira automatically', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Required fields for Sev-1 vs Sev-3 tickets' => [
            'docs' => null,
            'estimated_minutes' => 20,
            'needs_input' => 'Two things are needed before this can be written. (1) **Which '
                .'severity scheme is real.** This lesson says Sev-1/Sev-3 and the SLA module says '
                .'P1–P4 — two schemes in one course, and nobody has said which the desk uses. (2) '
                .'The **required fields per severity** on the live Zendesk forms. Both are local '
                .'configuration and neither is recorded anywhere. Igor or the desk lead.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic is not written yet, and there are two reasons rather than one.</strong></p></blockquote>

<h2 id="which-scheme">Which severity scheme are we using?</h2>

<p>This course contradicts itself. This module refers to <strong>Sev-1 and Sev-3</strong>; the SLA
module refers to <strong>P1, P2, P3 and P4</strong>. Those are different schemes, and until somebody
says which one the desk actually runs, teaching either would be teaching a new starter the wrong
vocabulary for their first month.</p>

<h2 id="which-fields">Which fields are required?</h2>

<p>Required fields are a local Zendesk configuration. They are not a property of Zendesk and they
are not written down here.</p>

<h2 id="what-you-can-learn-now">What you can take from this anyway</h2>

<p>The <em>reason</em> severity has different required fields is worth understanding before you learn
which they are:</p>

<ul>
<li><strong>A high-severity ticket is going to be handed over.</strong> Possibly several times,
    possibly out of hours, possibly to somebody who cannot ring you. Everything the next person
    needs has to be captured at the point of raising, because there is no second chance to ask.</li>
<li><strong>A low-severity ticket is likely to be worked by the person who raised it.</strong>
    Demanding the same detail is friction with no benefit.</li>
<li><strong>The fields are not paperwork.</strong> Each one exists because somebody, once, could not
    start work without it.</li>
</ul>

<blockquote><p><strong>Until this is confirmed, ask.</strong> On your first high-severity ticket,
ask your buddy what must be filled in and write the answer down. Do not infer it from a previous
ticket — that ticket may be wrong too.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'How to properly link Jira issues to customer tickets' => [
            'docs' => null,
            'estimated_minutes' => 20,
            'needs_input' => 'The local convention is missing: whether the link is made with the '
                .'Zendesk–Jira integration or by pasting keys, which direction is authoritative, '
                .'and who is responsible for updating the customer when the Jira issue moves. The '
                .'principles below hold regardless. Igor or the desk lead to supply the '
                .'convention.',
            'body' => <<<'HTML'
<p><strong>One Jira issue, many Zendesk tickets. That relationship is the whole reason linking
matters, and getting it backwards creates work rather than saving it.</strong></p>

<h2 id="the-shape">The shape of it</h2>

<blockquote><p>Eleven customers reporting one defect is <strong>one</strong> Jira issue and
<strong>eleven</strong> Zendesk tickets. Not eleven issues.</p></blockquote>

<p>Raising a new Jira issue for a defect that is already tracked is the most common linking mistake.
It splits the evidence, hides how many customers are affected, and means engineering sees eleven
small annoyances instead of one thing worth fixing. <strong>Search before you raise.</strong></p>

<h2 id="what-links-are-for">What the link is actually for</h2>

<ul>
<li><strong>Counting.</strong> The number of tickets attached to an issue is the argument for
    prioritising it. An unlinked ticket is a customer who does not count.</li>
<li><strong>Telling people.</strong> When the issue ships, the links are how you know who to go back
    to. Nobody remembers eleven customers a month later.</li>
<li><strong>Not re-diagnosing.</strong> The next agent seeing the twelfth report should find the
    issue in one search, not repeat your afternoon.</li>
</ul>

<h2 id="what-belongs-where">What belongs in which system</h2>

<table>
<thead>
<tr><th>In the Jira issue</th><th>In the Zendesk ticket</th></tr>
</thead>
<tbody>
<tr><td>What is technically wrong, reproduction steps, the conditions.</td><td>What this customer experienced and what they have been told.</td></tr>
<tr><td>Written for an engineer who has never spoken to a customer.</td><td>Written for the next agent who picks up this customer.</td></tr>
<tr><td>No customer names or account details unless they are needed to reproduce it.</td><td>The identifiers — the account, the Agent ID.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Do not paste a customer's contact details into a Jira issue</strong> because
it is convenient. Jira is usually visible to more people than a Zendesk ticket, and personal data
put there is data you have quietly copied into a second system.</p></blockquote>

<h2 id="the-part-that-fails">The part that always fails</h2>

<p>Linking is easy. <strong>Going back afterwards is what gets skipped</strong> — the issue ships and
eleven customers hear nothing, because closing the Jira issue felt like finishing. It is not
finishing. The tickets are the work.</p>

<blockquote><p><strong>Who does that here is not recorded</strong> — whether the fixer notifies, or
the original agent does, is a local convention. See the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Linking Jira and Zendesk',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Eleven customers report the same defect. What should exist?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'One Jira issue and eleven Zendesk tickets, all linked. Eleven issues splits the evidence and hides how many customers are affected.',
                        'options' => [
                            ['text' => 'One Jira issue and eleven linked Zendesk tickets', 'correct' => true],
                            ['text' => 'Eleven Jira issues and eleven Zendesk tickets', 'correct' => false],
                            ['text' => 'One Jira issue and one merged Zendesk ticket', 'correct' => false],
                            ['text' => 'Eleven Jira issues linked to one master ticket', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why should customer contact details stay out of the Jira issue?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Jira is usually visible to more people than a Zendesk ticket. Putting personal data there copies it into a second system, quietly.',
                        'options' => [
                            ['text' => 'It copies personal data into a system more people can see', 'correct' => true],
                            ['text' => 'Jira cannot store personal data', 'correct' => false],
                            ['text' => 'It breaks the Zendesk link', 'correct' => false],
                            ['text' => 'Engineering will contact the customer directly', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Writing clear and concise public ticket responses' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>A public reply is a permanent, forwardable, quotable record. Write every one as though it
will be read aloud in a meeting you are not in, because sometimes it is.</strong></p>

<h2 id="the-shape">The shape of a good reply</h2>

<ol>
<li><strong>What you understand the problem to be</strong>, in one sentence, in their words. It
    gives them the chance to correct you before you spend a day on the wrong thing.</li>
<li><strong>What you have found</strong> — including "nothing yet", if that is the truth.</li>
<li><strong>What happens next</strong>, who does it, and by when.</li>
<li><strong>What you need from them</strong>, if anything — as a short list, not buried in a
    paragraph.</li>
</ol>

<h2 id="the-rules">Four rules that do most of the work</h2>

<ul>
<li><strong>Lead with the answer.</strong> If they can act, say so first. The reasoning goes
    underneath, where they can read it if they want it.</li>
<li><strong>One question at a time, and number them.</strong> Three questions in a paragraph reliably
    get one answer, and you have lost a day.</li>
<li><strong>Never say "as I mentioned previously".</strong> It says "you did not read my last
    message" and adds nothing.</li>
<li><strong>Give a time, not a feeling.</strong> "I'll come back to you by 4pm today" is a
    commitment. "Shortly", "ASAP" and "as soon as I can" are not, and they are heard as either
    sooner or never.</li>
</ul>

<h2 id="the-hard-cases">The three hard ones</h2>

<table>
<thead>
<tr><th>Situation</th><th>What to write</th></tr>
</thead>
<tbody>
<tr><td>You do not know yet.</td><td>Say so, say what you have ruled out, and give a time. Silence is worse than "no news".</td></tr>
<tr><td>It was our fault.</td><td>Say it plainly, once. What is being done about it matters more to them than the apology, and a long apology reads as anxiety.</td></tr>
<tr><td>They are wrong.</td><td>Correct the fact without correcting the person. "The report covers 1–31 March" rather than "you've misread the report".</td></tr>
</tbody>
</table>

<h2 id="the-test">The test before you send</h2>

<blockquote><p><strong>Could they forward this to their own boss without explaining it?</strong> If
it needs your voice to make sense, it needs rewriting. Public replies are forwarded constantly, and
they travel without you.</p></blockquote>

<h2 id="one-more">One more, before you press send</h2>

<p><strong>Check which box you are typing in.</strong> The distinction between a public reply and an
internal note is one click, and this is the message that goes to the customer.</p>
HTML,
            'quiz' => [
                'title' => 'Writing to the customer',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Why is "I\'ll come back to you as soon as I can" a poor commitment?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It is not a commitment at all. It is heard as either sooner than you meant or never, and it gives the customer nothing to plan around.',
                        'options' => [
                            ['text' => 'It has no time in it, so it is heard as either sooner or never', 'correct' => true],
                            ['text' => 'It is too informal for a public reply', 'correct' => false],
                            ['text' => 'It implies the ticket is low priority', 'correct' => false],
                            ['text' => 'It cannot be measured against the SLA', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the test to apply before sending a public reply?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Could they forward it to their own boss without explaining it? Public replies travel without you.',
                        'options' => [
                            ['text' => 'Could the customer forward it to their boss without explaining it?', 'correct' => true],
                            ['text' => 'Is it under two hundred words?', 'correct' => false],
                            ['text' => 'Does it contain the ticket reference?', 'correct' => false],
                            ['text' => 'Has a senior agent reviewed it?', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 1 — knowledge check',
        'description' => 'Zendesk, Jira, and writing to customers. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What is the difference in purpose between a Zendesk ticket and a Jira issue?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Zendesk holds the customer\'s situation and what they have been told; Jira holds the work on the product. They close at different times.',
                'options' => [
                    ['text' => 'Zendesk holds the customer\'s situation; Jira holds the product work', 'correct' => true],
                    ['text' => 'Zendesk is for incidents and Jira is for service requests', 'correct' => false],
                    ['text' => 'Zendesk is first line and Jira is second line', 'correct' => false],
                    ['text' => 'They hold the same thing for different audiences', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'You find a defect that already has a Jira issue. What do you do?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Link your ticket to the existing issue. Raising a second one splits the evidence and hides how many customers are affected.',
                'options' => [
                    ['text' => 'Link your ticket to the existing issue', 'correct' => true],
                    ['text' => 'Raise a new issue so yours is tracked separately', 'correct' => false],
                    ['text' => 'Add your customer\'s details to the existing issue', 'correct' => false],
                    ['text' => 'Close your ticket and point the customer at the issue', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which is the better correction to a customer who has misread a report?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Correct the fact without correcting the person.',
                'options' => [
                    ['text' => '"The report covers 1–31 March."', 'correct' => true],
                    ['text' => '"You\'ve misread the report."', 'correct' => false],
                    ['text' => '"As I mentioned previously, the dates are on the report."', 'correct' => false],
                    ['text' => '"That\'s not what the report says."', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Rewrite three public replies',
        'lesson_title' => 'Writing clear and concise public ticket responses',
        'brief' => 'Take three real public replies from closed tickets and rewrite them to the four-part shape, then apply the forward test.',
        'submission_instructions' => 'Submit the three originals and your three rewrites. For each, say which of the four rules the original broke. Then apply the forward test to your rewrite — could the customer send it to their own boss without explaining it — and say honestly whether it passes. Remove customer names and account identifiers from anything you paste in.',
        'requires_screenshot' => false,
        'estimated_minutes' => 30,
        'required_evidence' => [
            ['key' => 'rewrites', 'label' => 'The three originals and three rewrites', 'hint' => 'Anonymised — no customer names or account identifiers'],
            ['key' => 'rules_broken', 'label' => 'Which rule each original broke', 'hint' => 'Lead with the answer · one question at a time · no "as I mentioned" · give a time'],
        ],
    ],
];
