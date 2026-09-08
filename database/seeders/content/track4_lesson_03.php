<?php

/**
 * Onboarding — Module 3: Data privacy and security.
 *
 * The most teachable module in this course and the one with the sharpest
 * limits. The principles a support agent works to — verify before disclosing,
 * collect the minimum, never store a credential, report early — are settled
 * practice and are taught here in full.
 *
 * What is **not** written, in every lesson, is the company's own position:
 * whether this company is controller or processor for a driver's data, the
 * verification questions this desk is authorised to use, and the incident
 * reporting path with its deadline. Those are not training gaps, they are
 * legal exposure, and inventing them would be worse than leaving them open.
 * Support skills Module D has the same dependency for the same reason.
 *
 * Nothing here is legal advice. It is desk practice, described so that a new
 * starter behaves safely while the policy is confirmed.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Protecting customer information',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'GDPR and CCPA basics for support agents' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'needs_input' => 'One fact decides how most of this applies and it is not recorded '
                .'anywhere: **is this company the controller or the processor** for the data on '
                .'the platform — particularly drivers\' location data? The answer changes who '
                .'answers a subject request, who may delete anything, and what support is allowed '
                .'to do. Igor and whoever owns data protection.',
            'body' => <<<'HTML'
<p><strong>You do not need to be a lawyer. You need to recognise the small number of moments where
the ordinary instinct to be helpful is the wrong one.</strong></p>

<h2 id="why-this-matters-here">Why this applies to telematics especially</h2>

<p>Most of what this platform holds is <strong>personal data about people who did not choose to be
tracked</strong>. A driver's location, minute by minute, including at the end of a shift, is about as
personal as operational data gets. That is not a reason to be nervous about the job — it is the
reason the rules exist and the reason they are taken seriously here.</p>

<h2 id="the-ideas">The four ideas that actually reach your desk</h2>

<table>
<thead>
<tr><th>Idea</th><th>What it means when you are on a call</th></tr>
</thead>
<tbody>
<tr><td><strong>Purpose limitation</strong></td><td>Data collected to run a fleet is for running a fleet. A request to use it for something else — checking on one person's weekend — is a different question, not a bigger version of the same one.</td></tr>
<tr><td><strong>Data minimisation</strong></td><td>Ask for what you need to diagnose, not everything that might help. Every extra field you request is data you are now responsible for.</td></tr>
<tr><td><strong>Access control</strong></td><td>Being able to see something is not permission to share it. Your access exists to let you do your job.</td></tr>
<tr><td><strong>Individual rights</strong></td><td>People can ask what is held about them, and can ask for things to be corrected or removed. The request is legitimate even when it arrives at the wrong desk — yours.</td></tr>
</tbody>
</table>

<h2 id="controller-processor">Controller and processor — why the distinction decides everything</h2>

<p>In the usual shape of a telematics arrangement, the fleet operator decides what is collected and
why — the <strong>controller</strong> — and the platform holds and processes it on their instructions
— the <strong>processor</strong>. Those two have very different obligations, and which one this
company is decides who answers a driver's request and who may delete anything.</p>

<blockquote><p><strong>That arrangement is not confirmed here.</strong> It is the usual shape, not
necessarily this company's. Until somebody says, treat any request from a data subject as something
to <em>record and route</em>, never to answer on the spot. See the note on this lesson.</p></blockquote>

<h2 id="the-practical-rules">The four rules that keep you safe</h2>

<ol>
<li><strong>Verify before you disclose.</strong> Every time, including when it is obviously them.</li>
<li><strong>Collect the minimum.</strong> If a ticket does not need a driver's name, do not put one
    in it.</li>
<li><strong>Never store a credential.</strong> Not in a ticket, not in a note, not "temporarily".</li>
<li><strong>When you are unsure, stop and ask.</strong> The delay costs a phone call. The
    alternative can cost considerably more.</li>
</ol>

<blockquote><p><strong>The instinct to fix things is what makes you good at this job, and it is the
thing to override here.</strong> These are the few situations where being immediately helpful is the
wrong move — and recognising them is most of what this module is for.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Data protection at the desk',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What does purpose limitation mean when a fleet manager asks for a weekend report on one named driver?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Data collected to run a fleet is for running a fleet. Using it to check on one person outside working hours is a different question, not a larger version of the same one.',
                        'options' => [
                            ['text' => 'It is a different question from ordinary fleet reporting, not a bigger one', 'correct' => true],
                            ['text' => 'It is fine, because the customer owns the data', 'correct' => false],
                            ['text' => 'It is fine if the driver is told afterwards', 'correct' => false],
                            ['text' => 'It is fine as long as the report is not stored', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why does the controller-versus-processor question matter to you?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It decides who answers a data subject request and who may delete anything — which is exactly what you need to know when one arrives at your desk.',
                        'options' => [
                            ['text' => 'It decides who answers a subject request and who may delete anything', 'correct' => true],
                            ['text' => 'It decides which SLA applies', 'correct' => false],
                            ['text' => 'It decides whether tickets can be linked to Jira', 'correct' => false],
                            ['text' => 'It only matters to the legal team', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        "How to verify a caller's identity before sharing account details" => [
            'docs' => null,
            'estimated_minutes' => 25,
            'needs_input' => 'The approved verification questions are missing, and they must come '
                .'from the desk rather than be invented: what a caller must produce before account '
                .'details are shared, and what to do when they cannot. The principles below hold '
                .'regardless of which questions are chosen. Igor or the desk lead.',
            'body' => <<<'HTML'
<p><strong>The person on the phone is confident, knows the company name, and is in a hurry. None of
that is verification.</strong></p>

<h2 id="the-one-rule">The rule that catches almost everything</h2>

<blockquote><p><strong>Never verify somebody using the information they are asking you for.</strong></p></blockquote>

<p>If a caller wants the account ID, the account ID cannot be the thing that proves who they are. It
sounds obvious written down, and it is the single most common way social engineering succeeds — the
caller supplies half of something, you helpfully supply the other half, and now they have both.</p>

<h2 id="what-does-not-verify">What does not verify anybody</h2>

<ul>
<li><strong>Knowing the company name.</strong> It is on their website.</li>
<li><strong>Calling from a plausible number.</strong> Numbers are trivially faked.</li>
<li><strong>Urgency.</strong> Manufactured urgency is a technique, not a coincidence. "I'm about to
    go into a meeting and the lorry is stuck" is designed to make you skip a step.</li>
<li><strong>Anger.</strong> Same technique, different lever. Being pressured into cutting a corner is
    the corner you must not cut.</li>
<li><strong>Having called before.</strong> They may have got further last time than they should have.</li>
</ul>

<h2 id="the-mechanics">The mechanics</h2>

<ol>
<li><strong>Verify before you disclose anything</strong>, including whether an account exists.
    "I can't find that account" and "I can't discuss that account" say very different things to
    somebody fishing.</li>
<li><strong>Ask open questions.</strong> "What's the account name?" not "is it Northfield
    Haulage?" — never hand them the answer to check.</li>
<li><strong>If verification fails, the answer is no</strong>, and it stays no however reasonable the
    explanation is.</li>
<li><strong>Offer a safe route out.</strong> Call back on the number held on the account, or route
    through their own administrator. That way a genuine caller is not stranded.</li>
</ol>

<blockquote><p><strong>Failing verification is not an accusation.</strong> Say it as procedure —
"I'm not able to go through the account on this call, but here's what we can do" — and most genuine
callers are reassured rather than offended. They have accounts of their own to protect.</p></blockquote>

<h2 id="not-written">What is missing</h2>

<blockquote><p><strong>The approved questions are not recorded here.</strong> What this desk accepts
as verification is policy, and using questions you have chosen yourself is exactly the improvisation
this lesson warns against. See the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Verifying a caller',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A caller wants the account ID. Can you verify them using the account ID?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'No. Never verify somebody using the information they are asking for — it is the most common way social engineering succeeds.',
                        'options' => [
                            ['text' => 'No — never verify using the thing being requested', 'correct' => true],
                            ['text' => 'Yes, if they get it right first time', 'correct' => false],
                            ['text' => 'Yes, if they also know the company name', 'correct' => false],
                            ['text' => 'Yes, if they are calling from a number on the account', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why should you not say "I can\'t find that account"?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It discloses whether the account exists. To somebody fishing, that is information — "I can\'t discuss that account" says nothing either way.',
                        'options' => [
                            ['text' => 'It discloses whether the account exists', 'correct' => true],
                            ['text' => 'It sounds unhelpful', 'correct' => false],
                            ['text' => 'It invites them to guess another name', 'correct' => false],
                            ['text' => 'It breaches the first-response target', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Redacting sensitive data (passwords, credit cards) from tickets' => [
            'docs' => null,
            'estimated_minutes' => 25,
            'body' => <<<'HTML'
<p><strong>A ticket is a long-lived, widely-readable, exportable record. Whatever lands in it is
there for years and is read by people who were never on the call.</strong></p>

<h2 id="who-reads-a-ticket">Who actually reads a ticket</h2>

<ul>
<li>Every agent who touches it, now and in three years.</li>
<li>Whoever runs a search that happens to match it.</li>
<li>Anyone reviewing the account later.</li>
<li>Potentially <strong>the data subject themselves</strong>, if they make a request — which is why
    "internal" is not the same as "private".</li>
</ul>

<h2 id="what-comes-out">What must come out, always</h2>

<table>
<thead>
<tr><th>Remove</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td><strong>Passwords</strong>, in any form.</td><td>Including "temporary" ones. A password in a ticket is a password that has been shared with everyone above.</td></tr>
<tr><td><strong>Card numbers</strong>, even partial.</td><td>Fragments combine across tickets.</td></tr>
<tr><td><strong>API keys and tokens</strong>.</td><td>They are credentials. They are also easy to miss inside a pasted log.</td></tr>
<tr><td><strong>Two-factor codes and recovery codes</strong>.</td><td>Same reason, and pasted more often than you would expect.</td></tr>
<tr><td><strong>Personal data not needed to diagnose</strong> — a driver's home address, a phone number nobody needs.</td><td>Data minimisation. If it is not needed, it should not be there.</td></tr>
</tbody>
</table>

<h2 id="when-a-customer-sends-one">When the customer sends you a password anyway</h2>

<p>They will. Regularly, helpfully, unprompted.</p>

<ol>
<li><strong>Do not use it.</strong></li>
<li><strong>Get it out of the ticket</strong> by whatever means your tooling allows.</li>
<li><strong>Tell them it has been removed and that it should be changed</strong>, because it has
    already travelled through email.</li>
<li><strong>Do not repeat it back</strong> in your reply confirming you removed it. This happens, and
    it undoes the entire exercise.</li>
</ol>

<h2 id="screenshots">Screenshots and log files are the hard part</h2>

<blockquote><p><strong>The dangerous data is nearly always in an attachment, not in the text.</strong>
A screenshot taken to show one error message also contains every browser tab, the whole object list,
and sometimes a password manager. A log pasted to show one line contains a thousand others.</p></blockquote>

<p>Look at what you attach before you attach it, and look at what arrives before you forward it on to
Jira. A screenshot moved into Jira has been copied into a system with a wider audience — the check
matters most at exactly that moment.</p>

<h2 id="the-habit">The habit</h2>

<p>Before submitting anything, ask: <em>if this ticket were read aloud, is there anything in it that
should not be?</em> It takes two seconds and it catches nearly everything.</p>
HTML,
            'quiz' => [
                'title' => 'Keeping tickets clean',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A customer emails you their password. What do you do?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Do not use it, remove it from the ticket, tell them it was removed and should be changed — and do not repeat it back in the reply saying so.',
                        'options' => [
                            ['text' => 'Remove it, tell them to change it, and do not repeat it back', 'correct' => true],
                            ['text' => 'Use it to reproduce the issue, then delete the ticket', 'correct' => false],
                            ['text' => 'Move it to an internal note where the customer cannot see it', 'correct' => false],
                            ['text' => 'Leave it — they chose to send it', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Where does sensitive data most often hide in a ticket?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'In attachments. A screenshot of one error also captures every open tab; a log pasted for one line carries a thousand others.',
                        'options' => [
                            ['text' => 'In screenshots and log files', 'correct' => true],
                            ['text' => 'In the ticket subject', 'correct' => false],
                            ['text' => 'In the requester field', 'correct' => false],
                            ['text' => 'In the public reply', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Reporting a potential security incident or data breach' => [
            'docs' => null,
            'estimated_minutes' => 25,
            'needs_input' => 'The reporting path is missing and it is the one thing this lesson '
                .'most needs: who to tell, by what route, within what time, and what to do out of '
                .'hours. Breach notification carries statutory deadlines that start when the '
                .'organisation becomes aware — which can mean the moment you noticed. Igor and '
                .'whoever owns security to supply the path and the deadline.',
            'body' => <<<'HTML'
<p><strong>You are more likely to be the person who notices than the person who investigates. What
matters is what you do in the first ten minutes.</strong></p>

<h2 id="what-counts">What counts — and it is broader than people expect</h2>

<ul>
<li>Data sent to the wrong customer. An email, a report, an attachment.</li>
<li>An account showing data it should not have access to.</li>
<li>A credential exposed — in a ticket, a screenshot, a repository, a chat.</li>
<li>A device or laptop lost.</li>
<li>A convincing phishing attempt, <strong>including one that failed</strong>.</li>
<li>Anything you cannot explain that involves somebody seeing data they should not.</li>
</ul>

<blockquote><p><strong>"It was probably nothing" is not a judgement you are being asked to make.</strong>
Reporting something that turns out to be harmless costs somebody ten minutes. Not reporting something
that turns out not to be harmless costs considerably more, and the cost lands on people who had no
say in it.</p></blockquote>

<h2 id="the-first-ten-minutes">The first ten minutes</h2>

<ol>
<li><strong>Report it.</strong> Immediately, and before you investigate. This is the opposite of
    every other instinct this course has trained into you, and it is deliberate.</li>
<li><strong>Write down the time you noticed.</strong> Deadlines can start from the moment the
    organisation becomes aware, and that may be you.</li>
<li><strong>Do not investigate alone.</strong> Poking at it can destroy the evidence of what
    happened and how far it went.</li>
<li><strong>Do not tell the customer yet.</strong> Notification is a controlled process with legal
    content. A well-meant early warning can be both wrong and damaging.</li>
<li><strong>Do not discuss it in open channels.</strong> Not in the ticket, not in a general chat.</li>
</ol>

<h2 id="if-you-caused-it">If you caused it</h2>

<blockquote><p><strong>Report it faster, not slower.</strong> Sending a report to the wrong customer
is a mistake anybody can make. Sitting on it for an hour, hoping nobody noticed, turns a manageable
incident into a serious one — and every hour of delay makes it worse and harder to explain.</p></blockquote>

<p>Nobody at this company will be worse off for reporting their own error quickly. That is worth
believing on day one, because the moment you need it, you will not have time to work it out.</p>

<h2 id="not-written">What is missing</h2>

<blockquote><p><strong>Who to tell is not recorded anywhere in this system.</strong> Find out the
route and the out-of-hours route <em>before</em> you need them, and keep them somewhere you can reach
without logging into anything. See the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Reporting an incident',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'You are not sure whether what you have seen is a real incident. What do you do?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Report it. Deciding whether it is serious is not your call, and a harmless report costs somebody ten minutes.',
                        'options' => [
                            ['text' => 'Report it — the judgement is not yours to make', 'correct' => true],
                            ['text' => 'Investigate until you are sure, then report', 'correct' => false],
                            ['text' => 'Ask the customer whether anything looks wrong', 'correct' => false],
                            ['text' => 'Note it in the ticket and carry on', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why write down the time you noticed?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Breach notification deadlines can start when the organisation becomes aware — and that may be the moment you noticed.',
                        'options' => [
                            ['text' => 'Deadlines can start from when the organisation became aware', 'correct' => true],
                            ['text' => 'It shows you responded promptly', 'correct' => false],
                            ['text' => 'It is needed for the SLA calculation', 'correct' => false],
                            ['text' => 'It identifies which shift was on duty', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 3 — knowledge check',
        'description' => 'Data privacy and security at the support desk. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What is the one rule that catches most social engineering?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Never verify somebody using the information they are asking you for.',
                'options' => [
                    ['text' => 'Never verify using the information being requested', 'correct' => true],
                    ['text' => 'Never take calls from withheld numbers', 'correct' => false],
                    ['text' => 'Never discuss an account without a ticket open', 'correct' => false],
                    ['text' => 'Never share anything by email', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Urgency and anger from a caller are best understood as what?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Levers designed to make you skip a step. Being pressured into cutting a corner is the corner you must not cut.',
                'options' => [
                    ['text' => 'Pressure to skip a step — which is the step to keep', 'correct' => true],
                    ['text' => 'A sign the ticket should be re-prioritised', 'correct' => false],
                    ['text' => 'Evidence the caller is genuine', 'correct' => false],
                    ['text' => 'A reason to escalate to a senior agent', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Why is "internal note" not the same as "private"?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Notes can surface in a data subject request, and the public reply control is one click away.',
                'options' => [
                    ['text' => 'Notes can surface in a data subject request', 'correct' => true],
                    ['text' => 'Notes are copied to the account manager', 'correct' => false],
                    ['text' => 'Notes become public when a ticket is solved', 'correct' => false],
                    ['text' => 'Notes are visible to the requester by default', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'You have sent a report to the wrong customer. What do you do?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Report it faster, not slower. Every hour of delay turns a manageable incident into a serious one and a harder thing to explain.',
                'options' => [
                    ['text' => 'Report it immediately, before doing anything else', 'correct' => true],
                    ['text' => 'Ask the recipient to delete it, then decide whether to report', 'correct' => false],
                    ['text' => 'Recall the email and note it in the ticket', 'correct' => false],
                    ['text' => 'Tell the affected customer first', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Clean a ticket before it is forwarded',
        'lesson_title' => 'Redacting sensitive data (passwords, credit cards) from tickets',
        'brief' => 'You are given a ticket, with attachments, that is about to be linked to a Jira issue. Find everything in it that should not travel, and say what you would do about each.',
        'submission_instructions' => 'List everything you found, where it was, and what you would do about each. Look hardest at the attachments — a screenshot taken to show one error also captures every open tab, and a pasted log carries a thousand lines nobody read. For anything that is already exposed, say what you would tell the customer, and say whether it is a security incident that needs reporting rather than only a redaction.',
        'requires_screenshot' => false,
        'estimated_minutes' => 25,
        'required_evidence' => [
            ['key' => 'findings', 'label' => 'What you found, and where', 'hint' => 'Include anything in attachments'],
            ['key' => 'actions', 'label' => 'What you would do about each', 'hint' => 'Remove, ask to be changed, or report as an incident'],
            ['key' => 'incident_call', 'label' => 'Is any of it a reportable incident?', 'hint' => 'And why — the judgement is what is being marked'],
        ],
    ],
];
