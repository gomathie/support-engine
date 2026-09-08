<?php

/**
 * Support skills — Module A: Communication with customers.
 *
 * This course is different from the other two. It is not documentation: every
 * topic is an **exercise**, and the lesson is the brief for it — what you do,
 * what good looks like, and how it is marked. There is nothing here to learn
 * by reading and then recite.
 *
 * That is also why almost nothing in this file cites docs.pilot-gps.com. A
 * role-play brief asserts no PILOT facts, so there is nothing to get wrong.
 * Where a drill does depend on a platform fact, the fact is one already
 * sourced and taught in 1st-line support or Admin panel — the two access
 * levels, module gating, the two blocks, the Notification module — and the
 * exercise leans on the earlier lesson rather than restating it.
 *
 * One thing here is **not** sourced and is flagged as such: the five-point
 * ticket standard. The curriculum refers to it by name and nothing in the
 * repository defines it. The definition below is a reasonable one, consistent
 * with the "verify, do not assume" thread running through the other courses,
 * and it is marked `needs_input` so the desk can confirm or replace it rather
 * than inheriting a standard an agent invented.
 *
 * Markup is limited to what Filament's rich editor round-trips: headings,
 * lists, blockquotes, tables and inline marks. No div, dl or span — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Communication with customers',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Question drill: 5 questions before proposing a cause (×5 symptoms)' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>The most expensive habit on a support desk is answering before you have asked. This drill
makes the asking automatic.</strong></p>

<h2 id="the-rule">The rule</h2>

<blockquote><p><strong>Five questions before you propose a cause.</strong> Not four, and not a guess
dressed as a question.</p></blockquote>

<p>It feels artificial, and that is the point. The first plausible cause that comes to mind is
usually the one you saw last week, and the customer in front of you is not last week. Five questions
force the difference into the open.</p>

<h2 id="the-drill">The drill</h2>

<p>You will be given <strong>five symptoms</strong>, one at a time, as a customer would report them —
in their words, not yours. For each, write the five questions you would ask <em>before</em> naming a
cause. Then, and only then, write the cause you would investigate first.</p>

<h2 id="what-a-good-question-is">What counts as a good question</h2>

<table>
<thead>
<tr><th>A good question</th><th>A wasted question</th></tr>
</thead>
<tbody>
<tr><td>Changes what you would do next depending on the answer.</td><td>Confirms what you already assumed.</td></tr>
<tr><td>Is answerable by the person on the phone.</td><td>Needs them to look at something they cannot reach.</td></tr>
<tr><td>Establishes <em>when</em> it started and <em>what changed</em> around then.</td><td>Asks for a symptom you have already been told.</td></tr>
<tr><td>Separates "all objects" from "this one object".</td><td>Assumes the scope without checking it.</td></tr>
<tr><td>Gets an identifier — the Agent ID, the account.</td><td>Relies on the vehicle's nickname.</td></tr>
</tbody>
</table>

<h2 id="the-four-that-always-earn-their-place">The four that nearly always earn their place</h2>

<ol>
<li><strong>When did it last work?</strong> A date turns "it is broken" into a window to search.</li>
<li><strong>Is it one object or all of them?</strong> One object is the device or its settings. All
    of them is the account, the contract or the platform — completely different investigations.</li>
<li><strong>What changed around then?</strong> Somebody almost always changed something. Audit
    records changes to an object's settings, so this question has an answer you can check.</li>
<li><strong>Who is reporting it, and what can they see?</strong> Rights differ. What the caller
    cannot see is not proof it is not there.</li>
</ol>

<p>The fifth is the one that belongs to <em>this</em> symptom. Finding it is the skill being drilled.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the cause you name is consistent with the answers your questions
    would have produced.</li>
<li><strong>Method</strong> — the five questions narrow the field. Five questions that all point the
    same way is one question asked five times.</li>
<li><strong>Verification</strong> — you say how you would confirm the cause, not just name it.</li>
<li><strong>Communication</strong> — the questions are ones a fleet manager can answer without being
    made to feel stupid.</li>
</ul>

<blockquote><p><strong>The failure this prevents.</strong> "It's the device" is right often enough
to be dangerous. Said on question one, it sends an engineer to a vehicle whose contract was
blocked for non-payment.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Asking before answering',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which question most changes what you do next?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'One object points at the device or its settings; all objects points at the account, contract or platform. The two lead to completely different investigations, so this question splits the work before it starts.',
                        'options' => [
                            ['text' => 'Is it one object or all of them?', 'correct' => true],
                            ['text' => 'Have you tried logging out and in again?', 'correct' => false],
                            ['text' => 'Is the vehicle definitely switched on?', 'correct' => false],
                            ['text' => 'Would you like me to raise a ticket?', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A caller says they cannot see a feature. Why is that not evidence the feature is absent?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Rights and module activation both change what a given person sees. What the caller cannot see may be present and withheld from them, which is a different fix.',
                        'options' => [
                            ['text' => 'Rights and module activation change what each person sees', 'correct' => true],
                            ['text' => 'Customers routinely misremember their screen', 'correct' => false],
                            ['text' => 'The interface differs between browsers', 'correct' => false],
                            ['text' => 'Features are hidden until the balance is positive', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Rewrite 5 manual sentences for a non-technical fleet manager' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>You will spend your career translating. The documentation is written for the platform; the
customer is running a haulage business.</strong></p>

<h2 id="the-drill">The drill</h2>

<p>You will be given <strong>five sentences</strong> taken verbatim from the PILOT documentation.
Rewrite each one for a fleet manager who has never opened an admin panel, is on the phone, and is
holding a clipboard.</p>

<h2 id="the-test">The test to apply</h2>

<blockquote><p>Could they act on it without asking you a follow-up question?</p></blockquote>

<p>That is the whole standard. Not "is it simpler" — simpler is easy and often useless. Could they
<em>act</em> on it.</p>

<h2 id="what-to-do">What the rewriting actually involves</h2>

<ul>
<li><strong>Name the thing they will see, not the thing it is.</strong> "The Account field" is a
    label on their screen. "The parent entity association" is not.</li>
<li><strong>Keep the platform's own words for anything they must click.</strong> If the button says
    <em>Geozone</em>, do not tell them to look for geofences. Translating the vocabulary of the
    screen is how you lose them.</li>
<li><strong>Put the sequence in order and number it.</strong> Prose describing three actions is
    three actions the reader has to extract.</li>
<li><strong>Say how they will know it worked.</strong> Almost no documentation sentence includes
    this, and almost every customer needs it.</li>
<li><strong>Cut the qualifier that protects you rather than helps them.</strong> "It should
    generally be possible to" is not caution, it is hedging.</li>
</ul>

<h2 id="the-trap">The trap</h2>

<blockquote><p><strong>Simplifying is not the same as being vague.</strong> "Go into your settings
and turn it on" is simpler than the documentation and worse than it — the reader now has to guess
which settings and which switch. Plain language is <em>more</em> specific than jargon, not less.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the rewrite still says what the original said. Losing a condition
    to make a sentence read nicely is a fail, not a style choice.</li>
<li><strong>Method</strong> — sequence, numbering, and the names of things as they appear on screen.</li>
<li><strong>Verification</strong> — the reader is told how to confirm the result.</li>
<li><strong>Communication</strong> — read it aloud. Anywhere you would add a word to make it make
    sense, a word is missing.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Writing for the customer',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which rewrite is better, and why?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Plain language is more specific than jargon, not less. "Go into settings and turn it on" is simpler and worse — the reader now has to guess which settings and which switch.',
                        'options' => [
                            ['text' => '"Open Account settings, then the Privacy tab" — it names what they will see', 'correct' => true],
                            ['text' => '"Go into your settings and turn it on" — it is shorter', 'correct' => false],
                            ['text' => '"Modify the relevant configuration entity" — it is precise', 'correct' => false],
                            ['text' => '"It should generally be possible to change this" — it is cautious', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'The documentation calls a module "Geozone". The customer keeps saying "geofences". What do you write?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Use the platform\'s word for anything they must find or click. Translating the vocabulary of the screen sends them looking for something that is not there.',
                        'options' => [
                            ['text' => 'Geozone — the word on their screen, noting that it is what they call geofences', 'correct' => true],
                            ['text' => 'Geofences, to match how the customer speaks', 'correct' => false],
                            ['text' => 'Either — the platform accepts both terms', 'correct' => false],
                            ['text' => 'Neither — describe its position on the menu instead', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Role-play: angry customer, vehicle dark all weekend — full cycle' => [
            'docs' => null,
            'estimated_minutes' => 45,
            'body' => <<<'HTML'
<p><strong>A live role-play, start to finish, with a trainer playing the customer. It is recorded
against the same rubric as everything else, and the anger is not the exercise — it is the
condition the exercise happens under.</strong></p>

<h2 id="the-scenario">The scenario</h2>

<p>A fleet manager rings on Monday morning. One vehicle has shown nothing since Friday afternoon.
They have a customer of their own demanding to know where a delivery went, they believe they have
been ignored all weekend, and they say so.</p>

<h2 id="full-cycle">"Full cycle" means all of it</h2>

<ol>
<li><strong>Open.</strong> Acknowledge the impact before the detail. They are not angry about
    telematics; they are angry about a delivery they cannot account for.</li>
<li><strong>Establish the facts.</strong> Five questions, as drilled. Get the Agent ID.</li>
<li><strong>Investigate while they are on the line</strong>, saying what you are doing rather than
    going silent.</li>
<li><strong>State what you have found</strong> — including "I do not know yet", if that is true.</li>
<li><strong>Say what happens next</strong>, by when, and who does it.</li>
<li><strong>Close</strong> with what they should expect and what would make them contact you again.</li>
</ol>

<h2 id="what-is-being-marked">What is actually being marked</h2>

<blockquote><p><strong>Not whether you stayed calm.</strong> Whether the customer, at the end, knows
more than they did at the start and believes something will happen.</p></blockquote>

<ul>
<li><strong>Correctness</strong> — the checks you ran are the right ones for "one object, silent
    since a known time". Last reception, then whether it is one object or the account, then whether
    anything changed on it.</li>
<li><strong>Method</strong> — you narrowed before you concluded, and you did not go quiet.</li>
<li><strong>Verification</strong> — you say how you confirmed what you are telling them, or you say
    that you have not confirmed it yet.</li>
<li><strong>Communication</strong> — the impact is acknowledged once, early, without grovelling;
    no jargon; a commitment with a time on it.</li>
</ul>

<h2 id="the-two-failures">The two failures this exercise catches</h2>

<ul>
<li><strong>Apologising instead of diagnosing.</strong> Three apologies and no Agent ID is a call
    that has gone nowhere pleasantly.</li>
<li><strong>Diagnosing instead of acknowledging.</strong> Correct technical work delivered to
    somebody who does not yet believe you have understood the problem does not land.</li>
</ul>

<blockquote><p><strong>You are allowed to not know.</strong> "I do not have the answer yet, here is
what I have ruled out, and I will call you by eleven" is a strong outcome. A confident wrong cause
is the weakest one available.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Handling the angry call',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What is actually being marked in this role-play?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Not whether you stayed calm — whether the customer ends the call knowing more than they did at the start, and believing something will happen.',
                        'options' => [
                            ['text' => 'Whether the customer ends up better informed and believing something will happen', 'correct' => true],
                            ['text' => 'Whether you stayed calm throughout', 'correct' => false],
                            ['text' => 'Whether you resolved the fault on the call', 'correct' => false],
                            ['text' => 'Whether you apologised for the weekend', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'You have not found the cause by the end of the call. What is the strongest close?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Saying what you have ruled out, and committing to a time, is a strong outcome. A confident wrong cause is the weakest thing you can offer.',
                        'options' => [
                            ['text' => 'What you have ruled out, what happens next, and a time', 'correct' => true],
                            ['text' => 'The most likely cause, stated confidently', 'correct' => false],
                            ['text' => 'An apology and a promise to look into it', 'correct' => false],
                            ['text' => 'A referral to second line without further comment', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Role-play: deliver a "no" plus the alternative' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Saying no is a skill, and the half people skip is the second half.</strong></p>

<h2 id="the-scenario">The scenario</h2>

<p>A customer asks for something the platform does not do. The trainer will pick from real ones —
rebranding that moves the menu, a speed threshold set as an account configuration, a scheduled block
date, repricing objects a partner already holds. Every one of those is a genuine "no", and you have
met all of them in the other courses.</p>

<h2 id="the-shape">The shape of a good no</h2>

<ol>
<li><strong>Say it plainly, early.</strong> "That is not something the platform does" in the first
    thirty seconds, not extracted from you in the fifth minute.</li>
<li><strong>Say why, in one sentence</strong>, at the level they care about. Not the internals.</li>
<li><strong>Give the nearest thing that does exist.</strong> This is the part that gets skipped, and
    it is the part that makes the call a success. Rebranding will not move the menu — it will change
    the name, the colours and the logo. There is no speed configuration — there is a speeding
    notification.</li>
<li><strong>Be clear about which is which.</strong> Do not let "here is an alternative" be heard as
    "yes, eventually".</li>
</ol>

<h2 id="what-not-to-do">The three failures</h2>

<table>
<thead>
<tr><th>Failure</th><th>What it does</th></tr>
</thead>
<tbody>
<tr><td><strong>The soft no.</strong> "I'm not sure that's currently possible."</td><td>Heard as a maybe. They ring back in a week expecting progress.</td></tr>
<tr><td><strong>The passed no.</strong> "I'll raise it with the team."</td><td>Moves the refusal to somebody who is not on the call and will not be thanked for it.</td></tr>
<tr><td><strong>The bare no.</strong> Correct, complete, and offering nothing.</td><td>Technically right, and the reason the customer escalates.</td></tr>
</tbody>
</table>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — it really is a no, and the alternative you offered really exists.
    Inventing an alternative is worse than offering none.</li>
<li><strong>Method</strong> — no first, reason second, alternative third.</li>
<li><strong>Verification</strong> — you can say where the limit comes from, not just that it exists.</li>
<li><strong>Communication</strong> — the customer could repeat your answer accurately to their own
    manager.</li>
</ul>

<blockquote><p><strong>The last test is the real one.</strong> If they would repeat it as "they are
looking into it", you said the wrong thing.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Saying no well',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which part of a "no" is most often skipped, and matters most?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The nearest thing that does exist. A bare no is technically right and is the reason customers escalate.',
                        'options' => [
                            ['text' => 'The alternative that does exist', 'correct' => true],
                            ['text' => 'The apology', 'correct' => false],
                            ['text' => 'The technical reason', 'correct' => false],
                            ['text' => 'The offer to raise it with the team', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer asks for a speed threshold as an account setting. What is the correct no-plus-alternative?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'There is no speed configuration key. Speeding is a notification, and the Notification module must be active on the contract.',
                        'options' => [
                            ['text' => 'There is no speed setting — it is a speeding notification, and the Notification module must be active', 'correct' => true],
                            ['text' => 'It can be done, but only by second line', 'correct' => false],
                            ['text' => 'It is set per object on Vehicles → Edit', 'correct' => false],
                            ['text' => 'It requires the Analytics (Panel) module', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Write up role-play 3 to the 5-point ticket standard' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'needs_input' => 'The curriculum names a "five-point ticket standard" and nothing in the '
                .'repository defines it. The five points below are a reasonable standard, '
                .'consistent with the "verify, do not assume" thread running through the other '
                .'courses — but they were written for this lesson, not taken from the desk. Igor '
                .'to confirm they match the ticket standard the desk actually uses, or replace '
                .'them with it.',
            'body' => <<<'HTML'
<p><strong>The call is over. What survives it is the ticket, and the ticket is read by somebody who
was not there.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Write up the angry-customer role-play you just did, to the standard below. You are writing for a
colleague picking it up cold at eight tomorrow morning, with the customer already on the phone.</p>

<h2 id="the-standard">The five points</h2>

<table>
<thead>
<tr><th>#</th><th>Point</th><th>What it must contain</th></tr>
</thead>
<tbody>
<tr><td>1</td><td><strong>What was reported</strong></td><td>The symptom in the customer's words, and when it started. Not your interpretation of it.</td></tr>
<tr><td>2</td><td><strong>What was observed</strong></td><td>What you actually saw, with identifiers — the Agent ID, the account, the times.</td></tr>
<tr><td>3</td><td><strong>What was checked</strong></td><td>Each check and what it ruled out. A check with no outcome recorded may as well not have happened.</td></tr>
<tr><td>4</td><td><strong>What was done</strong></td><td>Every change you made, including the ones that changed nothing.</td></tr>
<tr><td>5</td><td><strong>How it was verified, and what the customer was told</strong></td><td>The evidence it worked, and the commitment they are now holding you to.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Point 3 is the one that is always thin, and the one that saves the most
time.</strong> "Checked the device" tells the next person nothing. "Last reception 16:42 Friday,
so the device was reporting until then" tells them where to start.</p></blockquote>

<h2 id="the-test">The test</h2>

<p>Hand it to somebody who was not on the call. Ask them what they would do first. If they have to
ask you a question before they can start, the ticket is not finished.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the write-up matches what actually happened, including the parts
    that went badly.</li>
<li><strong>Method</strong> — all five points, in order, with point 3 carrying outcomes.</li>
<li><strong>Verification</strong> — point 5 contains evidence, not a claim.</li>
<li><strong>Communication</strong> — readable cold. No shorthand only you use.</li>
</ul>

<blockquote><p><strong>Write the unflattering parts.</strong> A ticket that records a check you ran
that turned out to be irrelevant is more useful than one that quietly implies you went straight to
the answer — the next person will otherwise run it again.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'The five-point ticket',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which point is habitually thin, and saves the most time when it is not?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'What was checked — with the outcome of each check. A check with no outcome recorded may as well not have happened, and the next person runs it again.',
                        'options' => [
                            ['text' => 'What was checked, and what each check ruled out', 'correct' => true],
                            ['text' => 'What was reported', 'correct' => false],
                            ['text' => 'What was done', 'correct' => false],
                            ['text' => 'What the customer was told', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the test for whether a ticket write-up is finished?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Hand it to somebody who was not on the call and ask what they would do first. If they must ask you a question before starting, it is not finished.',
                        'options' => [
                            ['text' => 'Somebody who was not there can say what they would do first, without asking you anything', 'correct' => true],
                            ['text' => 'It contains the Agent ID and the account', 'correct' => false],
                            ['text' => 'It fits on one screen', 'correct' => false],
                            ['text' => 'The customer has confirmed they are satisfied', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module A — knowledge check',
        'description' => 'Communication with customers. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Why five questions before naming a cause?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The first plausible cause is usually the one you saw last week. Five questions force the difference between that case and this one into the open.',
                'options' => [
                    ['text' => 'The first cause that comes to mind is usually the last one you saw, not this one', 'correct' => true],
                    ['text' => 'It gives the customer time to calm down', 'correct' => false],
                    ['text' => 'It is required before a ticket can be raised', 'correct' => false],
                    ['text' => 'Five is the minimum for second line to accept an escalation', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer cannot see a feature you know exists. What has that told you?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Rights and module activation both change what a given person sees. Their screen is evidence about their access, not about the platform.',
                'options' => [
                    ['text' => 'Something about their access — not about whether the feature exists', 'correct' => true],
                    ['text' => 'That the feature has been withdrawn', 'correct' => false],
                    ['text' => 'That their browser is out of date', 'correct' => false],
                    ['text' => 'That their account is blocked', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which is the worst way to deliver a "no"?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A soft no is heard as a maybe. The customer rings back next week expecting progress, and the refusal has to be delivered again by somebody else.',
                'options' => [
                    ['text' => '"I\'m not sure that\'s currently possible"', 'correct' => true],
                    ['text' => '"That is not something the platform does — here is what it does instead"', 'correct' => false],
                    ['text' => '"No, and the reason is that rebranding only changes name, colours and logo"', 'correct' => false],
                    ['text' => '"No. The nearest thing is a speeding notification."', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What makes a ticket write-up finished?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Somebody who was not on the call can say what they would do first, without asking you anything.',
                'options' => [
                    ['text' => 'A colleague can pick it up cold and start work without asking you a question', 'correct' => true],
                    ['text' => 'It records the resolution', 'correct' => false],
                    ['text' => 'It has been read by the customer', 'correct' => false],
                    ['text' => 'It contains all five headings', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Write up the angry-customer role-play to the five-point standard',
        'lesson_title' => 'Write up role-play 3 to the 5-point ticket standard',
        'brief' => 'Write up the angry-customer role-play as a ticket a colleague could pick up cold, to the five-point standard.',
        'submission_instructions' => 'Submit the write-up as text, under the five headings. Then hand it to somebody who was not on the call, ask them what they would do first, and record their answer — including any question they had to ask you before they could start. That question, if there is one, is the finding.',
        'requires_screenshot' => false,
        'estimated_minutes' => 30,
        'required_evidence' => [
            ['key' => 'write_up', 'label' => 'The five-point write-up', 'hint' => 'All five headings, with outcomes against each check'],
            ['key' => 'cold_read_result', 'label' => 'What the cold reader said they would do first', 'hint' => 'And any question they had to ask you before they could start'],
        ],
    ],
];
