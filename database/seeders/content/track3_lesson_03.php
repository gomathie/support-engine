<?php

/**
 * Support skills — Module C: Support workflows and escalation.
 *
 * Four topics, and two of them are **not writeable as content**: shadowing and
 * reverse shadowing happen on live calls with a senior, and the lesson can
 * only be the brief and the marking standard.
 *
 * Two things here depend on facts this repository does not hold and the PILOT
 * documentation does not contain — **the team map** (who owns what) and the
 * **escalation criteria** to second line. Both are marked `needs_input`. They
 * are exactly the kind of thing that looks harmless to guess and is not: a
 * trainee taught the wrong owning team routes every ticket wrong for a month.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Support workflows and escalation',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Route the ticket: 10 real past requests → owning team + justification' => [
            'docs' => null,
            'estimated_minutes' => 40,
            'needs_input' => 'The team map is missing. This exercise needs the actual list of '
                .'teams and what each owns — 1st line, 2nd line, development, billing, field '
                .'engineering, account management, or whatever the real structure is. Nothing in '
                .'the repository records it and the PILOT documentation has no reason to. Igor to '
                .'supply the list, and the ten real past requests to route.',
            'body' => <<<'HTML'
<p><strong>Routing is a decision, not a reflex, and the justification matters more than the
destination.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Ten real past requests. For each: name the owning team, and justify it in one sentence. You are
marked on the justification. A right answer you cannot defend is a guess that happened to land.</p>

<h2 id="what-decides-it">What decides the owner</h2>

<p>Not the symptom. Two questions:</p>

<ol>
<li><strong>Who can actually change the thing that is wrong?</strong> Not who knows about it — who
    has the access and the authority to alter it.</li>
<li><strong>What is the smallest team that can close this without handing it on again?</strong>
    Routing to a bigger team "to be safe" is how a ticket acquires three owners and no progress.</li>
</ol>

<h2 id="the-recurring-mistakes">The four recurring mistakes</h2>

<table>
<thead>
<tr><th>Mistake</th><th>What it looks like</th></tr>
</thead>
<tbody>
<tr><td><strong>Routing by vocabulary</strong></td><td>The customer said "sensor", so it went to whoever owns sensors. The fault was that their contract was blocked.</td></tr>
<tr><td><strong>Routing by difficulty</strong></td><td>"This is hard, so it is second line." Hard is not a team.</td></tr>
<tr><td><strong>Routing the symptom, not the cause</strong></td><td>Sent before establishing which layer it is at, so the receiving team's first act is to send it somewhere else.</td></tr>
<tr><td><strong>Routing to avoid it</strong></td><td>The honest version of which is: this is within my remit and I do not want to do it.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Some of the ten do not need routing at all.</strong> They are yours, and the
correct answer is to keep them. Recognising that is part of the exercise, and it is the part people
score worst on.</p></blockquote>

<h2 id="not-written-yet">What this lesson is still missing</h2>

<blockquote><p><strong>The team map is not written here.</strong> Who owns what is a fact about this
company, not about PILOT, and it is not recorded anywhere in this system. Rather than teach an
invented structure — which would have you routing every ticket wrong, confidently — the list is
left open. See the note on this lesson.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the owner can actually change the thing that is wrong.</li>
<li><strong>Method</strong> — you established the layer before choosing a destination.</li>
<li><strong>Verification</strong> — you say what would tell you the routing was wrong.</li>
<li><strong>Communication</strong> — one sentence per ticket, and it names a reason rather than a
    category.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Choosing the owner',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What actually decides which team owns a ticket?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Who can change the thing that is wrong, and the smallest team that can close it without handing it on again. Difficulty is not a team.',
                        'options' => [
                            ['text' => 'Who can change the thing that is wrong', 'correct' => true],
                            ['text' => 'How difficult the ticket looks', 'correct' => false],
                            ['text' => 'Which words the customer used', 'correct' => false],
                            ['text' => 'Which team has the shortest queue', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why is routing to a bigger team "to be safe" a mistake?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It is how a ticket acquires three owners and no progress. The target is the smallest team that can close it without handing it on again.',
                        'options' => [
                            ['text' => 'It gives the ticket several owners and no progress', 'correct' => true],
                            ['text' => 'Larger teams have longer queues', 'correct' => false],
                            ['text' => 'It breaches the SLA automatically', 'correct' => false],
                            ['text' => 'It prevents the customer being contacted', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Write an escalation handover; 2nd line grades whether they could start cold' => [
            'docs' => null,
            'estimated_minutes' => 40,
            'needs_input' => 'The escalation criteria are missing — what must be true before a '
                .'ticket may go to second line, and what second line will refuse. The handover '
                .'standard below is sound and is marked by second line themselves, but the '
                .'threshold for escalating at all is a desk policy nobody has written down. Igor '
                .'to supply it.',
            'body' => <<<'HTML'
<p><strong>The person who marks this is the person who receives it. That is the whole design: you
are not writing for a trainer, you are writing for the colleague who has to start work with your
words and nothing else.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Escalate a real ticket to second line, in writing. Second line then answers one question:</p>

<blockquote><p><strong>Could you start work on this, right now, without asking the sender anything?</strong></p></blockquote>

<p>Yes or no. There is no partial credit, because in practice there is not: a handover that needs a
follow-up question has not saved anybody any time.</p>

<h2 id="what-a-handover-contains">What it must contain</h2>

<ol>
<li><strong>The identifiers.</strong> Agent ID, account, and the times. Without these the receiver's
    first act is to ask for them.</li>
<li><strong>The symptom, and when it started.</strong> In the customer's words.</li>
<li><strong>What you have ruled out, and how.</strong> The most valuable part, and the part most
    often missing. Every check you ran that came back clean is a check they do not repeat.</li>
<li><strong>What you believe and why</strong> — flagged clearly as belief, not finding.</li>
<li><strong>What the customer has been told</strong>, including any commitment they are now holding
    us to.</li>
</ol>

<h2 id="the-distinction">The distinction that carries the whole thing</h2>

<blockquote><p><strong>Separate what you observed from what you concluded.</strong> "Last reception
16:42 Friday" is an observation. "The device has failed" is a conclusion. Presenting the second as
the first means the receiver inherits your mistake and stops looking.</p></blockquote>

<h2 id="what-is-not-here">The threshold is not written here</h2>

<blockquote><p><strong>When a ticket <em>should</em> be escalated is a desk policy, and it is not
recorded anywhere in this system.</strong> This lesson teaches the handover, which is sound whenever
you escalate. What must be true before you escalate at all is left open — see the note on this
lesson.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the observations are accurate and the identifiers are right.</li>
<li><strong>Method</strong> — the ruled-out list has outcomes, not just check names.</li>
<li><strong>Verification</strong> — belief is labelled as belief.</li>
<li><strong>Communication</strong> — second line said yes.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Handing over',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which part of a handover is most valuable, and most often missing?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'What you ruled out, and how. Every check you ran that came back clean is a check the receiver does not have to repeat.',
                        'options' => [
                            ['text' => 'What you ruled out, with the outcome of each check', 'correct' => true],
                            ['text' => 'Your view of the most likely cause', 'correct' => false],
                            ['text' => 'The customer\'s contact details', 'correct' => false],
                            ['text' => 'The time you spent on it', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why must observations be separated from conclusions?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Presenting a conclusion as an observation means the receiver inherits your mistake and stops looking.',
                        'options' => [
                            ['text' => 'Otherwise the receiver inherits your mistake and stops looking', 'correct' => true],
                            ['text' => 'Second line only accepts observations', 'correct' => false],
                            ['text' => 'Conclusions cannot be recorded in a ticket', 'correct' => false],
                            ['text' => 'It keeps the handover shorter', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Shadowing: listen to live calls' => [
            'docs' => null,
            'estimated_minutes' => 120,
            'body' => <<<'HTML'
<p><strong>Listening is not passive, and "sitting in" is not the exercise. You are working; you are
just not talking.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Listen to live calls alongside an experienced agent. For each call, and <em>while</em> it happens,
write down:</p>

<ol>
<li>The <strong>first question</strong> they asked, and what it was for.</li>
<li>The moment they <strong>decided which layer</strong> the problem was at — and what made them
    decide.</li>
<li>Anything they said that you would <strong>not</strong> have thought to say.</li>
<li>What they did when they <strong>did not know</strong> something.</li>
</ol>

<h2 id="the-fourth-one">The fourth one is the point</h2>

<blockquote><p>Watching somebody handle not knowing is worth more than watching them handle knowing.
Knowing is a matter of time on the desk. Not knowing gracefully, out loud, in front of an impatient
customer, is a technique — and it is learnable by watching somebody do it.</p></blockquote>

<h2 id="what-to-avoid">What to avoid while shadowing</h2>

<ul>
<li><strong>Do not narrate afterwards what you would have done.</strong> You had the luxury of no
    customer waiting.</li>
<li><strong>Do not only note the calls that went well.</strong> A call that went badly, and why,
    teaches more.</li>
<li><strong>Do not write it up later.</strong> Notes made afterwards are a reconstruction, and they
    tidy away exactly the hesitation you are meant to have noticed.</li>
</ul>

<h2 id="the-debrief">The debrief</h2>

<p>Afterwards, ask the agent about <strong>two</strong> moments: one where they made a decision you
did not follow, and one where they said something you would not have said. Those two questions are
worth more than an hour of general discussion.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — your notes match what actually happened on the calls.</li>
<li><strong>Method</strong> — notes made live, per call, against the four headings.</li>
<li><strong>Verification</strong> — the debrief questions were asked and the answers recorded.</li>
<li><strong>Communication</strong> — you can explain one technique you saw well enough for somebody
    else to use it.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Shadowing well',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which is the most valuable thing to watch for while shadowing?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'How they handle not knowing. Knowing comes with time on the desk; not knowing gracefully in front of an impatient customer is a technique you can only learn by watching.',
                        'options' => [
                            ['text' => 'What they do when they do not know something', 'correct' => true],
                            ['text' => 'How quickly they resolve each call', 'correct' => false],
                            ['text' => 'Which screens they use most', 'correct' => false],
                            ['text' => 'How many calls they take an hour', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why must the notes be written during the call rather than afterwards?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Notes made afterwards are a reconstruction, and they tidy away the hesitation you were meant to notice.',
                        'options' => [
                            ['text' => 'Later notes are a reconstruction and tidy away the hesitation', 'correct' => true],
                            ['text' => 'Calls cannot be recalled from the system afterwards', 'correct' => false],
                            ['text' => 'The agent needs the notes immediately', 'correct' => false],
                            ['text' => 'It proves you were present', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Reverse shadowing: handle live contacts with a senior listening in' => [
            'docs' => null,
            'estimated_minutes' => 120,
            'body' => <<<'HTML'
<p><strong>Your calls, real customers, a senior listening and not rescuing you unless they must.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>You take the contacts. The senior listens. They intervene only if the call is going somewhere that
will cost the customer something — not when you are slow, not when you are uncertain, and not when
you would have got there yourself with another thirty seconds.</p>

<blockquote><p><strong>Silence from them is not disapproval.</strong> It is the exercise working.</p></blockquote>

<h2 id="what-they-are-listening-for">What they are listening for</h2>

<table>
<thead>
<tr><th>Not this</th><th>This</th></tr>
</thead>
<tbody>
<tr><td>Whether you knew the answer.</td><td>Whether you found out, and how.</td></tr>
<tr><td>Whether you sounded confident.</td><td>Whether your confidence matched what you actually knew.</td></tr>
<tr><td>How long the call took.</td><td>Whether the customer knew, at the end, what happens next.</td></tr>
<tr><td>Whether you used the right screens.</td><td>Whether you checked before you told them.</td></tr>
</tbody>
</table>

<h2 id="the-one-rule">The one rule</h2>

<blockquote><p><strong>Do not tell a customer something you have not checked</strong>, however
confident you are and however much the silence is pressing on you. This is the single thing the
senior will stop a call for, because it is the only thing on this list the customer pays for.</p></blockquote>

<h2 id="the-debrief">The debrief</h2>

<p>After each call, before the next one:</p>

<ol>
<li>What did you say that you had not verified?</li>
<li>Where did you decide the layer, and would you decide the same again?</li>
<li>What would you say differently, in one sentence?</li>
</ol>

<p>One sentence. A long list of things to do better is a list nobody applies on the next call.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — nothing you told a customer was untrue.</li>
<li><strong>Method</strong> — you narrowed before concluding, and you checked before telling.</li>
<li><strong>Verification</strong> — you can say, for each thing you asserted, where you got it.</li>
<li><strong>Communication</strong> — the customer ended the call knowing what happens next.</li>
</ul>

<blockquote><p><strong>The pass condition is not a clean run.</strong> It is that the senior did not
have to intervene to stop you telling a customer something you had not checked.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Taking the call yourself',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'When does the senior intervene?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Only when the call is going somewhere that will cost the customer something — not when you are slow or uncertain.',
                        'options' => [
                            ['text' => 'Only when the call is heading somewhere that costs the customer', 'correct' => true],
                            ['text' => 'Whenever you hesitate', 'correct' => false],
                            ['text' => 'When the call passes a time limit', 'correct' => false],
                            ['text' => 'When you use the wrong screen', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the pass condition for this exercise?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'That the senior never had to stop you telling a customer something you had not checked. It is not a clean run.',
                        'options' => [
                            ['text' => 'You never told a customer something you had not checked', 'correct' => true],
                            ['text' => 'You resolved every call without help', 'correct' => false],
                            ['text' => 'You stayed inside the target handling time', 'correct' => false],
                            ['text' => 'The customer was satisfied on every call', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module C — knowledge check',
        'description' => 'Support workflows and escalation. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A ticket is difficult. Does that decide where it goes?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'No. Hard is not a team. Ownership follows who can change the thing that is wrong.',
                'options' => [
                    ['text' => 'No — ownership follows who can change the thing that is wrong', 'correct' => true],
                    ['text' => 'Yes — difficulty is what second line is for', 'correct' => false],
                    ['text' => 'Yes, if it has already taken more than an hour', 'correct' => false],
                    ['text' => 'Only if the customer has escalated it themselves', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Second line reads your handover and has to ask you one question before starting. How does that score?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'It fails. There is no partial credit, because in practice there is not — a handover needing a follow-up question has saved nobody any time.',
                'options' => [
                    ['text' => 'It fails — a handover that needs a question has not saved any time', 'correct' => true],
                    ['text' => 'It passes if the question was minor', 'correct' => false],
                    ['text' => 'It passes if the identifiers were all present', 'correct' => false],
                    ['text' => 'It is not marked — second line marks only the outcome', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the single rule during reverse shadowing?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Do not tell a customer something you have not checked. It is the only thing on the list the customer pays for.',
                'options' => [
                    ['text' => 'Never tell a customer something you have not checked', 'correct' => true],
                    ['text' => 'Never leave a silence longer than five seconds', 'correct' => false],
                    ['text' => 'Never escalate on the first call', 'correct' => false],
                    ['text' => 'Never end a call without a resolution', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Escalate a ticket that second line can start cold',
        'lesson_title' => 'Write an escalation handover; 2nd line grades whether they could start cold',
        'brief' => 'Write an escalation handover for a real ticket. Second line answers one question: could they start work immediately, without asking you anything?',
        'submission_instructions' => 'Submit the handover as written, plus second line\'s verdict — yes or no — and, if no, the question they had to ask. That question is the finding, and it is more useful than the handover itself. Separate what you observed from what you concluded: label your beliefs as beliefs.',
        'requires_screenshot' => false,
        'estimated_minutes' => 40,
        'required_evidence' => [
            ['key' => 'handover', 'label' => 'The handover as sent', 'hint' => 'Identifiers, symptom, what you ruled out with outcomes, belief labelled as belief, what the customer was told'],
            ['key' => 'verdict', 'label' => 'Second line\'s verdict', 'hint' => 'Could they start cold — yes or no'],
            ['key' => 'question_asked', 'label' => 'The question they had to ask, if any', 'hint' => 'Leave blank only if they asked nothing'],
        ],
    ],
];
