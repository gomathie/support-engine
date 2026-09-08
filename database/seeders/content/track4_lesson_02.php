<?php

/**
 * Onboarding — Module 2: Service level agreements.
 *
 * Every topic in this module needs a number this repository does not hold.
 * The concepts are teachable and are taught in full — what a first-response
 * target is, why it differs from a resolution target, what the clock does when
 * a ticket is pending, and what to do when one is about to breach. **The
 * targets themselves are a commercial commitment and cannot be guessed.**
 *
 * So every lesson here carries content plus a `needs_input` note naming
 * exactly the number that is missing. A trainee learns how SLAs work now, and
 * fills in the figures the day somebody supplies them.
 *
 * The priority scheme is also unresolved: this module says **P1–P4** and
 * Module 1's curriculum says **Sev-1 / Sev-3**. Two schemes, one course.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Understanding our commitments',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Priority definitions: P1, P2, P3, and P4' => [
            'docs' => null,
            'estimated_minutes' => 25,
            'needs_input' => 'The four definitions are missing, and so is the answer to which '
                .'scheme the desk uses — this module says P1–P4, Module 1 says Sev-1/Sev-3. What '
                .'is needed: the scheme, and for each level, the condition that qualifies a '
                .'ticket for it. Igor or the desk lead.',
            'body' => <<<'HTML'
<p><strong>Priority is not how loudly somebody is asking. It is a derived number, and it exists so
that a queue can be worked in an order that is defensible.</strong></p>

<h2 id="how-priority-is-derived">How priority is derived</h2>

<p>From two judgements, made separately:</p>

<ul>
<li><strong>Impact</strong> — how much is affected. One vehicle, one depot, one whole fleet. Impact
    is about size and does not care how upset anyone is.</li>
<li><strong>Urgency</strong> — how fast the damage grows. A refrigerated load losing temperature
    monitoring is urgent because the cargo is spoiling now; a report that will not export is not,
    however irritating it is.</li>
</ul>

<blockquote><p><strong>They are meant to disagree.</strong> One vehicle with a spoiling load is low
impact and high urgency. A whole fleet's mileage being slightly wrong is high impact and low
urgency. Collapsing them into one gut feeling throws away the only useful thing about having two
dimensions.</p></blockquote>

<h2 id="the-question-that-sets-urgency">The question that sets urgency</h2>

<blockquote><p><strong>What happens in the real world while this stays broken?</strong></p></blockquote>

<p>Answer it concretely — the thing affected and the timescale — and urgency answers itself. "The
customer can't see temperatures" is vague. "A refrigerated load is unmonitored in transit and gets
written off on arrival — hours, not days" is a priority.</p>

<h2 id="what-priority-is-not">What priority is not</h2>

<ul>
<li><strong>Not a promise.</strong> It orders the queue; the SLA target is the promise.</li>
<li><strong>Not permanent.</strong> Facts change. A P3 that turns out to affect a fleet is a P1, and
    re-prioritising on evidence is correct rather than an admission.</li>
<li><strong>Not negotiable by volume.</strong> A customer insisting theirs is critical is
    information about them, not about impact or urgency.</li>
</ul>

<h2 id="not-written">What is missing here</h2>

<blockquote><p><strong>The four definitions are not written.</strong> What qualifies a ticket as P1
rather than P2 is a commercial commitment this company has made, and it is not recorded in this
system. Worse, the course contradicts itself on whether the scheme is P1–P4 at all — Module 1 talks
about Sev-1 and Sev-3. Nobody should be taught either until somebody says which is real. See the
note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'How priority is derived',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A single vehicle carrying a spoiling load has lost temperature monitoring. How does that score?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Low impact — one vehicle — and high urgency, because the damage is growing now.',
                        'options' => [
                            ['text' => 'Low impact, high urgency', 'correct' => true],
                            ['text' => 'Low impact, low urgency', 'correct' => false],
                            ['text' => 'High impact, high urgency', 'correct' => false],
                            ['text' => 'High impact, low urgency', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer insists their ticket is critical. What has that told you?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Something about them, not about impact or urgency. Volume is not an input to either dimension.',
                        'options' => [
                            ['text' => 'Something about them — not about impact or urgency', 'correct' => true],
                            ['text' => 'That the priority should be raised', 'correct' => false],
                            ['text' => 'That the ticket should be escalated', 'correct' => false],
                            ['text' => 'That the impact was assessed too low', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'First-response time targets by priority' => [
            'docs' => null,
            'estimated_minutes' => 20,
            'needs_input' => 'The targets themselves are missing — the first-response time for '
                .'each priority level, and whether the clock runs on business hours or calendar '
                .'hours. Both are commercial commitments and cannot be inferred. Igor or whoever '
                .'holds the customer contracts.',
            'body' => <<<'HTML'
<p><strong>First response is the promise that somebody has picked it up. It is not the promise that
it is fixed, and confusing the two disappoints customers on tickets that are going perfectly well.</strong></p>

<h2 id="what-counts">What counts as a first response</h2>

<blockquote><p>A real reply from a person, to the customer, that engages with what they reported.</p></blockquote>

<p>An automated acknowledgement does not count, whatever the clock in the tool says. Neither does a
holding message so generic it could have been sent before reading the ticket — "Thank you for
contacting support, we are looking into this" satisfies a metric and nobody else.</p>

<h2 id="a-good-first-response">What a good first response contains</h2>

<ol>
<li>Confirmation you have understood the problem, in one sentence, in their words.</li>
<li>What you are doing next.</li>
<li>Anything you need from them, numbered.</li>
<li>When they will hear from you again.</li>
</ol>

<p>None of that requires knowing the answer. You can hit a first-response target on a ticket you have
not begun to diagnose — that is the point of it being a separate target.</p>

<h2 id="the-clock">The clock</h2>

<p>Two things decide when the clock runs, and both are local:</p>

<ul>
<li><strong>Business hours or calendar hours?</strong> A four-hour target means something very
    different at 5pm on a Friday depending on the answer.</li>
<li><strong>When does it start?</strong> Normally when the ticket arrives — not when somebody
    assigns it, and not when the queue is next looked at.</li>
</ul>

<blockquote><p><strong>Neither is recorded here.</strong> The first-response targets per priority,
and whether they run on business or calendar hours, are commercial commitments. Find out before
your first shift and write them down — see the note on this lesson.</p></blockquote>

<h2 id="the-habit">The habit worth forming now</h2>

<p>Answer early even when you have nothing. The customer's anxiety is not about the fault; it is
about whether anybody has seen it. Two minutes of engagement in the first hour prevents most chasing
emails, and chasing emails are the expensive part of a slow ticket.</p>
HTML,
            'quiz' => [
                'title' => 'First response',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Does an automated acknowledgement satisfy a first-response target?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'No. A first response is a real reply from a person that engages with what was reported, whatever the tool\'s clock says.',
                        'options' => [
                            ['text' => 'No — it must be a person engaging with what was reported', 'correct' => true],
                            ['text' => 'Yes, that is what it is for', 'correct' => false],
                            ['text' => 'Yes, for P3 and P4 only', 'correct' => false],
                            ['text' => 'Only outside business hours', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Can you meet a first-response target on a ticket you have not diagnosed?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Yes — that is precisely why it is a separate target from resolution. It promises that somebody has picked it up, not that it is fixed.',
                        'options' => [
                            ['text' => 'Yes — first response promises pickup, not resolution', 'correct' => true],
                            ['text' => 'No — a response requires a diagnosis', 'correct' => false],
                            ['text' => 'Only if you state a likely cause', 'correct' => false],
                            ['text' => 'Only on low-priority tickets', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Resolution time targets and escalation paths' => [
            'docs' => null,
            'estimated_minutes' => 25,
            'needs_input' => 'Two gaps. (1) The resolution targets per priority. (2) The '
                .'escalation path — who a ticket goes to at each stage, and what must be true '
                .'before it may go. Module C of Support skills has the same gap for the same '
                .'reason. Igor or the desk lead.',
            'body' => <<<'HTML'
<p><strong>Resolution is the second clock, and it measures something the first one does not: whether
the customer's situation is actually over.</strong></p>

<h2 id="what-resolution-means">What counts as resolved</h2>

<blockquote><p><strong>Service restored</strong> — even by a workaround, and even if nobody yet
knows why it broke.</p></blockquote>

<p>That is the industry definition and it is not a shortcut. The customer being able to work again is
the thing they are waiting for. The cause is a separate piece of work, tracked separately, with its
own clock. Holding a ticket open until the root cause is understood leaves somebody waiting for
something they never asked for and cannot use.</p>

<h2 id="the-clock-pausing">When the clock stops</h2>

<p>Most resolution clocks pause while a ticket is <strong>pending on the customer</strong> — waiting
for information, access or a decision from them. That creates one obligation and one temptation:</p>

<ul>
<li><strong>The obligation:</strong> be specific about what you are waiting for, so they can end the
    wait. "Awaiting customer" with no question in it is a ticket that will sit for a week.</li>
<li><strong>The temptation:</strong> parking a ticket as pending to stop the clock, on a question you
    did not need the answer to. It protects a metric and costs a customer, and everybody can see it
    in the ticket.</li>
</ul>

<h2 id="when-you-will-not-make-it">When you are not going to make it</h2>

<p>Escalation is not an admission. It is the mechanism working. A ticket that breaches quietly, in
the hands of somebody who was sure they nearly had it, is worse in every respect than one handed on
early.</p>

<p>The general rule holds even without the local path:</p>

<blockquote><p><strong>Escalate on the trend, not on the deadline.</strong> If you can see you will
not make it, that is the moment — not the hour before the target expires, when nobody receiving it
can do anything either.</p></blockquote>

<h2 id="not-written">What is missing here</h2>

<blockquote><p><strong>The targets and the path are not recorded anywhere in this system.</strong>
How long each priority has, who it escalates to, and what must be true before it may be escalated
are desk policy. This lesson teaches what the clocks mean and how to behave around them; the numbers
have to come from a person. See the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Resolution and escalation',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'The customer is working again on a workaround and nobody knows the cause. Is the ticket resolved?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Yes — service is restored, which is the definition. The cause is separate work with its own clock.',
                        'options' => [
                            ['text' => 'Yes — service is restored; the cause is separate work', 'correct' => true],
                            ['text' => 'No — resolution requires the root cause', 'correct' => false],
                            ['text' => 'No — a workaround is not a resolution', 'correct' => false],
                            ['text' => 'Only if the customer agrees to close it', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'When should you escalate a ticket you are not going to resolve in time?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'When you can see you will not make it. Escalating an hour before the target leaves the receiver unable to do anything either.',
                        'options' => [
                            ['text' => 'As soon as you can see you will not make it', 'correct' => true],
                            ['text' => 'An hour before the target expires', 'correct' => false],
                            ['text' => 'After the target has been breached', 'correct' => false],
                            ['text' => 'Only if the customer asks for it to be escalated', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'What to do when an SLA is at risk of breaching' => [
            'docs' => null,
            'estimated_minutes' => 20,
            'needs_input' => 'The local procedure is missing: who must be told when a ticket is at '
                .'risk, in what timeframe, and whether the customer is proactively contacted. The '
                .'conduct below is sound anywhere; the notification path is desk policy. Igor or '
                .'the desk lead.',
            'body' => <<<'HTML'
<p><strong>The breach is not the failure. Discovering the breach after it happened is.</strong></p>

<h2 id="say-it-early">Say it early, and say it outward</h2>

<p>Two things happen the moment you can see a target will be missed, and neither is optional:</p>

<ol>
<li><strong>Tell the customer before the deadline passes, not after.</strong> A customer told at
    hour three that the four-hour target will not be met is a customer managing their day. The same
    customer told at hour five is a customer who has been let down twice — once by the miss and once
    by finding out from the clock.</li>
<li><strong>Tell whoever needs to know internally.</strong> Somebody may be able to help, and if they
    cannot, the miss should not be a surprise to them either.</li>
</ol>

<h2 id="what-to-tell-them">What to say to the customer</h2>

<table>
<thead>
<tr><th>Say</th><th>Do not say</th></tr>
</thead>
<tbody>
<tr><td>That the original commitment will not be met.</td><td>Nothing, in the hope it comes good.</td></tr>
<tr><td>Where it actually is, and what you have ruled out.</td><td>"It's still with the team."</td></tr>
<tr><td>A new time you are confident of.</td><td>The same time again, optimistically.</td></tr>
<tr><td>What you need from them, if anything.</td><td>An apology in place of a plan.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Do not set a second time you are not confident of.</strong> Missing the
revised commitment costs far more than the first miss did — that is the point at which a customer
stops believing any date you give them.</p></blockquote>

<h2 id="after">Afterwards</h2>

<p>A breach is data. Record what actually caused it, honestly — waiting on a third party, waiting on
the customer, waiting on a colleague, or simply not started. A breach log full of "complex issue"
teaches nobody anything, and the same breach happens next month.</p>

<blockquote><p>If the same cause appears repeatedly, that is a <strong>problem</strong>, in the
precise sense: an underlying cause behind several incidents, worth raising as its own piece of
work.</p></blockquote>

<h2 id="not-written">What is missing here</h2>

<blockquote><p><strong>Who to tell, and when, is not recorded.</strong> Whether at-risk tickets are
flagged automatically, who is notified, and whether the customer is contacted proactively are local
procedure. See the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Handling a breach',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'You can see at hour three that a four-hour target will be missed. When do you tell the customer?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'At hour three. Told before the deadline, they can manage their day; told afterwards, they have been let down twice.',
                        'options' => [
                            ['text' => 'Immediately — before the deadline passes', 'correct' => true],
                            ['text' => 'At hour four, once it has actually breached', 'correct' => false],
                            ['text' => 'Once you have a resolution to offer alongside it', 'correct' => false],
                            ['text' => 'Only if they ask', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why is a revised commitment you are not confident of worse than the first miss?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Missing the revised date is the point at which the customer stops believing any date you give them.',
                        'options' => [
                            ['text' => 'Missing it is when they stop believing any date you give', 'correct' => true],
                            ['text' => 'It restarts the SLA clock', 'correct' => false],
                            ['text' => 'It counts as two breaches in the report', 'correct' => false],
                            ['text' => 'It prevents the ticket being escalated', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 2 — knowledge check',
        'description' => 'How service level commitments work. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What are the two dimensions priority is derived from?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Impact — how much is affected — and urgency — how fast the damage grows. Judged separately, because they often disagree.',
                'options' => [
                    ['text' => 'Impact and urgency', 'correct' => true],
                    ['text' => 'Impact and customer size', 'correct' => false],
                    ['text' => 'Urgency and effort', 'correct' => false],
                    ['text' => 'Severity and contract value', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the difference between the first-response target and the resolution target?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'First response promises that a person has picked it up and engaged. Resolution promises the customer\'s situation is over.',
                'options' => [
                    ['text' => 'One promises pickup, the other promises service restored', 'correct' => true],
                    ['text' => 'One applies to incidents, the other to service requests', 'correct' => false],
                    ['text' => 'One is measured in business hours, the other in calendar hours', 'correct' => false],
                    ['text' => 'They are the same target measured from different points', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the temptation to avoid when marking a ticket pending on the customer?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Parking it on a question you did not need answered, to stop the clock. It protects a metric, costs the customer, and is visible in the ticket.',
                'options' => [
                    ['text' => 'Parking it on a question you did not need answered, to stop the clock', 'correct' => true],
                    ['text' => 'Asking more than one question at a time', 'correct' => false],
                    ['text' => 'Setting it pending without an assignee', 'correct' => false],
                    ['text' => 'Marking it pending before a first response', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'The same breach cause keeps appearing month after month. What is that?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A problem, in the precise sense — an underlying cause behind several incidents, worth raising as its own piece of work.',
                'options' => [
                    ['text' => 'A problem, worth raising as its own piece of work', 'correct' => true],
                    ['text' => 'A staffing issue', 'correct' => false],
                    ['text' => 'Evidence the targets are too tight', 'correct' => false],
                    ['text' => 'Normal variation in a busy queue', 'correct' => false],
                ],
            ],
        ],
    ],
];
