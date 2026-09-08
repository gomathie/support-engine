<?php

/**
 * Support skills — Module B: Troubleshooting methodology.
 *
 * The heaviest module in the course, and the one with the most PILOT in it.
 * Each topic is a drill done in the live sandbox rather than a page to read.
 *
 * Platform facts used here are ones already sourced against
 * docs.pilot-gps.com, and each is named where it is used:
 *
 *   · Audit / Audit history   /audit_and_audit_history.html
 *   · Raw data (two days)     /raw__data.html
 *   · Recalculate             /recalculate.html
 *   · Sensor types            /sensor_types.html
 *   · Permissions             /permissions.html
 *   · Notifications           /notifications__print.html
 *   · Modules per contract    /modules_2.html
 *
 * Deliberately **not** written: the exact column names on the Sensors, Raw
 * data and Audit screens. Those pages are image-based in the documentation and
 * the text does not enumerate them. Naming columns from memory would be
 * inventing PILOT facts, and these are drills done at the live panel anyway —
 * the trainee reads the real columns off the real screen, which is the skill.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Troubleshooting methodology',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Instrument drill: Sensors tracing — last reception, satellites, sensor value, under a minute' => [
            'docs' => 'Sensors · Raw data',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Speed here is not showing off. A customer is on the phone, and the difference between
sixty seconds and five minutes is whether you are still having a conversation or reading out silence.</strong></p>

<h2 id="the-drill">The drill</h2>

<p>Given an Agent ID, produce three facts in <strong>under a minute</strong>:</p>

<ol>
<li><strong>When did this object last report?</strong></li>
<li><strong>How many satellites was it seeing?</strong></li>
<li><strong>What is the current value of a named sensor on it?</strong></li>
</ol>

<p>You will be timed, on five objects, cold. The point is not the numbers — it is that you can get
them without navigating hesitantly while somebody listens.</p>

<h2 id="why-those-three">Why those three, in that order</h2>

<table>
<thead>
<tr><th>Fact</th><th>What it settles</th></tr>
</thead>
<tbody>
<tr><td><strong>Last reception</strong></td><td>Whether this is a data problem at all. An object reporting two minutes ago is not "offline", whatever the customer is looking at.</td></tr>
<tr><td><strong>Satellites</strong></td><td>Whether it is reporting but cannot fix its position — a completely different fault from not reporting.</td></tr>
<tr><td><strong>Sensor value</strong></td><td>Whether the device is sending the parameter the customer is complaining about, before anyone argues about what the report says.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Reporting and positioning are not the same thing.</strong> An object sending
messages with no satellite fix looks "stuck" on a map and perfectly healthy in the data. Telling
those apart in the first minute avoids sending an engineer to a working device.</p></blockquote>

<h2 id="raw-data">Where the raw truth is, and its limit</h2>

<p><strong>Raw data</strong> is the non-processed data arriving from the server, filtered by vehicle.
It is the closest you get to what the device actually sent, and it carries one constraint you must
know before you promise anything:</p>

<blockquote><p>The Raw data timeframe is limited to <strong>up to two days</strong>.</p></blockquote>

<p>So a Monday-morning question about Friday afternoon is at the edge of that window, and a question
about last month is outside it. Knowing the limit before you look is the difference between "I
checked" and "I could not check, and here is why".</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the three facts are right for the object you were given.</li>
<li><strong>Method</strong> — you went to the same place each time, in the same order, without
    hunting.</li>
<li><strong>Verification</strong> — you can say which screen each number came from.</li>
<li><strong>Communication</strong> — you can say the three facts aloud as a sentence a customer
    would understand.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Reading an object in under a minute',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'An object is reporting every two minutes but shows no satellites. What kind of fault is that?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It is reporting but cannot fix its position — a positioning fault, not a communications one. It looks stuck on the map and healthy in the data.',
                        'options' => [
                            ['text' => 'A positioning fault — the device is communicating fine', 'correct' => true],
                            ['text' => 'A communications fault — the device is offline', 'correct' => false],
                            ['text' => 'A billing fault — the object is blocked', 'correct' => false],
                            ['text' => 'A sensor fault — the parameter is missing', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer asks about an incident three weeks ago. What does the Raw data limit mean for you?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Raw data is limited to up to two days, so three weeks ago is outside it. Say what you could not check and why, rather than implying you checked.',
                        'options' => [
                            ['text' => 'It is outside the window — say so rather than implying you checked', 'correct' => true],
                            ['text' => 'Raw data can be extended on request from the customer', 'correct' => false],
                            ['text' => 'Run a recalculation first and the raw data reappears', 'correct' => false],
                            ['text' => 'The limit applies only to blocked objects', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        "Instrument drill: find an object's last settings change in Audit history" => [
            'docs' => 'Audit and Audit history',
            'estimated_minutes' => 25,
            'body' => <<<'HTML'
<p><strong>"Nothing changed" is the most commonly wrong sentence a customer says, and it is almost
never a lie. They mean <em>they</em> did not change anything.</strong></p>

<h2 id="what-audit-is">What Audit is</h2>

<blockquote><p>Audit shows any information about changes in settings on the object. Audit history is
the more detailed view of those same changes.</p></blockquote>

<p>Who, what, when. That is exactly the shape of the question "what changed around the time it
stopped working", which is one of the four questions you ask on every call.</p>

<h2 id="the-drill">The drill</h2>

<p>Given an Agent ID and a date, find:</p>

<ol>
<li>the <strong>last settings change</strong> on that object;</li>
<li><strong>who</strong> made it;</li>
<li><strong>when</strong>;</li>
<li>and <strong>what</strong> it changed.</li>
</ol>

<p>Then answer the question that matters: <strong>could that change have caused the symptom?</strong>
Sometimes the answer is no, and saying so is as valuable as saying yes.</p>

<h2 id="the-discipline">The discipline</h2>

<blockquote><p><strong>A change near the time is not a cause.</strong> It is a candidate. Somebody
renaming the object an hour before it went silent did not make it go silent. Treating coincidence as
causation is how a ticket gets closed on the wrong thing and reopened next week.</p></blockquote>

<p>State it as: <em>"X changed at 14:07, made by Y. The symptom began at 16:42. The change affects
Z, which would not produce this symptom."</em> That is a finding. "Someone changed something" is not.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — you found the actual last change, not the most recent one that
    caught your eye.</li>
<li><strong>Method</strong> — you searched by object and by time, rather than scrolling.</li>
<li><strong>Verification</strong> — you can show where the record is.</li>
<li><strong>Communication</strong> — you separate what the record says from what you infer from it.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Reading the audit trail',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Audit shows a settings change an hour before the symptom began. What have you established?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A candidate, not a cause. Whether it could produce this symptom is a separate question, and treating coincidence as causation closes tickets on the wrong thing.',
                        'options' => [
                            ['text' => 'A candidate — you still have to say whether it could produce this symptom', 'correct' => true],
                            ['text' => 'The cause, since the timing matches', 'correct' => false],
                            ['text' => 'That the customer was mistaken about not changing anything', 'correct' => false],
                            ['text' => 'That the ticket should be closed as user error', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer insists nothing changed. Why is that usually true and still not the whole story?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'They mean they did not change anything. Audit records changes by anyone — colleagues, partners, administrators — which is exactly what the customer cannot see.',
                        'options' => [
                            ['text' => 'They mean they did not change anything — Audit records changes by anyone', 'correct' => true],
                            ['text' => 'Customers routinely misremember their own actions', 'correct' => false],
                            ['text' => 'Audit only records administrator changes', 'correct' => false],
                            ['text' => 'Settings changes take 24 hours to appear', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Layer sorting: 15 symptoms → layer + first check' => [
            'docs' => null,
            'estimated_minutes' => 40,
            'body' => <<<'HTML'
<p><strong>Most wasted diagnostic time is spent at the wrong layer. This drill makes the sorting
reflexive.</strong></p>

<h2 id="the-layers">The layers</h2>

<table>
<thead>
<tr><th>Layer</th><th>Symptom belongs here when…</th><th>First check</th></tr>
</thead>
<tbody>
<tr><td><strong>Device</strong></td><td>The object is not reporting, or reports without a position.</td><td>Last reception, then satellites.</td></tr>
<tr><td><strong>Data</strong></td><td>It reports, but a value is wrong, missing or implausible.</td><td>Is the sensor configured, and what is it actually sending?</td></tr>
<tr><td><strong>Configuration</strong></td><td>One account behaves differently from another for no visible reason.</td><td>What configurations are set on the contract?</td></tr>
<tr><td><strong>Entitlement</strong></td><td>A feature is absent from the screen entirely.</td><td>Is the module active on this contract?</td></tr>
<tr><td><strong>Rights</strong></td><td>One person sees something a colleague does not.</td><td>What can that user's own profile do?</td></tr>
<tr><td><strong>Billing</strong></td><td>Objects have stopped, together, around a date.</td><td>Balance, and the two blocks — Himself and Administrator.</td></tr>
<tr><td><strong>Expectation</strong></td><td>Everything works and the customer is still unhappy.</td><td>What did they believe would happen, and where did they get that?</td></tr>
</tbody>
</table>

<h2 id="the-drill">The drill</h2>

<p>Fifteen symptoms, as customers reported them. For each: name the layer, and name the
<strong>one</strong> check you would run first. One check, not a list — the exercise is choosing.</p>

<h2 id="why-it-works">Why this is worth drilling</h2>

<p>Each layer has a cheap, decisive first check, and running the right one collapses the search
immediately. Running the wrong one produces a plausible answer at the wrong layer, which is worse
than no answer — it is confidently wrong, and it costs the next person the time to undo it.</p>

<blockquote><p><strong>The last layer is not a joke.</strong> "Expectation" is a real and common
category: nothing is broken, and the customer was told something that is not true — sometimes by
us. It needs a different response entirely, and misfiling it as a device fault wastes an engineer's
day.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the layer fits the symptom.</li>
<li><strong>Method</strong> — the first check is the cheapest one that splits the possibilities,
    not the most thorough one available.</li>
<li><strong>Verification</strong> — you say what each outcome of the check would tell you.</li>
<li><strong>Communication</strong> — one line per symptom. If you need a paragraph, you have not
    decided.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Sorting by layer',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'One user sees a report that a colleague on the same account cannot. Which layer, and what is the first check?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Rights. Two people on one account differing points at what their individual profiles can do, not at the platform.',
                        'options' => [
                            ['text' => 'Rights — check what that user\'s own profile can do', 'correct' => true],
                            ['text' => 'Entitlement — check whether the module is active', 'correct' => false],
                            ['text' => 'Data — check what the sensor is sending', 'correct' => false],
                            ['text' => 'Device — check last reception', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Every object on an account stopped on the same day. Which layer first?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'All objects stopping together, around a date, points at billing — the balance and the two blocks. A device fault does not arrive on a fleet simultaneously.',
                        'options' => [
                            ['text' => 'Billing — the balance, and the Himself and Administrator blocks', 'correct' => true],
                            ['text' => 'Device — every unit needs checking individually', 'correct' => false],
                            ['text' => 'Data — the sensors have stopped reporting', 'correct' => false],
                            ['text' => 'Expectation — the customer misunderstood', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Broken sandbox: diagnose 6 planted faults cold ⭐ highest-value exercise' => [
            'docs' => null,
            'estimated_minutes' => 90,
            'body' => <<<'HTML'
<p><strong>The exercise this whole module exists for. Six faults, planted in a sandbox account, and
nobody tells you what they are.</strong></p>

<h2 id="why-this-one">Why it is the highest-value exercise in the course</h2>

<p>Every other drill tells you what kind of problem you are looking at. Real work does not. Here you
get an account and a complaint, and the first decision — which layer this even belongs to — is
yours and unassisted. That decision is the job.</p>

<h2 id="the-brief">The brief</h2>

<p>Six faults are planted across the layers you sorted in the previous drill: at least one that is
not a fault at all, but a configuration doing exactly what it was set to do. For each:</p>

<ol>
<li>Name the <strong>symptom</strong> as a customer would report it.</li>
<li>Say which <strong>layer</strong> it is at.</li>
<li>List the <strong>checks you ran, in order, with the outcome of each</strong>.</li>
<li>Name the <strong>cause</strong>.</li>
<li>Say how you would <strong>verify the fix</strong>.</li>
</ol>

<h2 id="the-rules">The rules</h2>

<ul>
<li><strong>Work cold.</strong> No hints, and do not ask which layers were used.</li>
<li><strong>Record checks that found nothing.</strong> They are how your method is marked, and a
    negative result is a result.</li>
<li><strong>Do not fix anything until all six are diagnosed.</strong> Fixing as you go destroys the
    evidence for the ones you have not found yet.</li>
<li><strong>You are allowed to find fewer than six</strong> and say so. Reporting four found and two
    not found, honestly, marks higher than six confident answers with two wrong.</li>
</ul>

<blockquote><p><strong>The planted non-fault is the real test.</strong> At least one of the six is a
setting behaving correctly — a block that was applied deliberately, a module that was never bought,
a configuration doing its job. Recognising a working system as working, while under pressure to find
something wrong, is harder than finding a fault and matters more.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — causes named are the causes planted. This carries the higher bar:
    a diagnosis that reaches the wrong cause fails regardless of how good the method looked.</li>
<li><strong>Method</strong> — the order of checks. Cheap and decisive before slow and thorough.</li>
<li><strong>Verification</strong> — for each, how you would know the fix worked.</li>
<li><strong>Communication</strong> — a colleague could act on your write-up without you.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Working a sandbox cold',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Why must you diagnose all six before fixing any?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Fixing as you go destroys the evidence for the faults you have not found yet.',
                        'options' => [
                            ['text' => 'Fixing destroys the evidence for the faults you have not found yet', 'correct' => true],
                            ['text' => 'The sandbox locks after the first change', 'correct' => false],
                            ['text' => 'Fixes must be approved by a trainer first', 'correct' => false],
                            ['text' => 'It keeps the exercise within the time limit', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'One of the six is a setting behaving correctly. Why is that included?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Recognising a working system as working, while under pressure to find something wrong, is harder than finding a fault and matters more.',
                        'options' => [
                            ['text' => 'Recognising correct behaviour under pressure to find a fault is the harder skill', 'correct' => true],
                            ['text' => 'To make the exercise take longer', 'correct' => false],
                            ['text' => 'Because one fault always fails to plant correctly', 'correct' => false],
                            ['text' => 'To test whether you escalate quickly enough', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'The Master trap: explain a rights issue you cannot see from your own login' => [
            'docs' => 'Admin Panel → Administrator → Permissions',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Your login is not the customer's login, and the whole class of faults in this lesson is
invisible from yours.</strong></p>

<h2 id="the-trap">The trap</h2>

<p>A customer says a button is not there. You look, and it is there. Both of you are describing your
own screen accurately, and neither screen is evidence about the other.</p>

<blockquote><p><strong>"I can see it" is not a diagnosis.</strong> It is a statement about your
rights.</p></blockquote>

<h2 id="what-differs">Three things that make screens differ</h2>

<ol>
<li><strong>Access level.</strong> The platform has two: the personal account, which is a monitoring
    interface with no administrative rights, and the admin panel, whose credentials are issued
    separately. A customer describing "administrator" almost always means the first.</li>
<li><strong>Module entitlement.</strong> Modules are switched on per contract, and only the system
    administrator can manage activation. A missing feature is far more often an inactive module than
    a fault.</li>
<li><strong>Permissions.</strong> The Access Rights section adds and changes rights to use modules,
    with a checkbox per module. Rights can be set separately for viewing, editing and deleting —
    somebody can be able to read a report and not to change it.</li>
</ol>

<h2 id="the-drill">The drill</h2>

<p>You will be given a rights complaint from a sandbox account. Without changing anything:</p>

<ol>
<li>Establish <strong>which of the three</strong> is producing the difference.</li>
<li>Say <strong>how you established it</strong> — the evidence, not the hunch.</li>
<li>Explain it <strong>to the customer</strong>, in a way that does not imply they were wrong to
    report it.</li>
</ol>

<h2 id="how-you-see-it">How you actually see what they see</h2>

<p>You do not guess it from your own screen. You look at <strong>their user</strong>: an
administrator signing in with admin credentials sees the profiles of all users under the contract,
and the module and permission settings say what that user can reach. That is the evidence.</p>

<blockquote><p><strong>Never fix it by widening your own rights.</strong> It changes nothing for the
customer and removes the difference you were trying to measure.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — you identified the right one of the three.</li>
<li><strong>Method</strong> — you looked at their access rather than reasoning from yours.</li>
<li><strong>Verification</strong> — you can point at the setting that produces the difference.</li>
<li><strong>Communication</strong> — the customer is not told they imagined it.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Rights you cannot see',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A customer says a button is missing. You can see it. What have you learned?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Only something about your own rights. Both screens are described accurately, and neither is evidence about the other.',
                        'options' => [
                            ['text' => 'Only that your login can see it', 'correct' => true],
                            ['text' => 'That the customer is looking in the wrong place', 'correct' => false],
                            ['text' => 'That their browser is caching an old version', 'correct' => false],
                            ['text' => 'That the fault has already been fixed', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'How do you find out what the customer can actually reach?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Look at their user. An administrator sees the profiles of all users under the contract, and the module and permission settings say what that user can reach.',
                        'options' => [
                            ['text' => 'Look at their user profile and its module and permission settings', 'correct' => true],
                            ['text' => 'Widen your own rights until you can reproduce it', 'correct' => false],
                            ['text' => 'Ask them to send a screenshot and reason from it', 'correct' => false],
                            ['text' => 'Compare against another customer on the same tariff', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Notification hunt: find the weekday-only time window' => [
            'docs' => 'Notifications',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>"It works during the week and not at weekends" is not a fault report. It is a
configuration described from the outside.</strong></p>

<h2 id="the-scenario">The scenario</h2>

<p>A customer reports that alerts arrive on weekdays and never at weekends. Nothing is broken. Find
what is producing the pattern, and be able to explain it.</p>

<h2 id="what-a-notification-is-made-of">What you are searching through</h2>

<p>A notification has three parts, and the pattern will be in one of them:</p>

<ol>
<li>the <strong>conditions that trigger it</strong>;</li>
<li>the <strong>objects it applies to</strong>;</li>
<li>the <strong>delivery method</strong> — email, SMS, Telegram, in-system messages or mobile app
    push.</li>
</ol>

<p>The events available include ignition on and off, speeding, fuel level changes, refuellings and
drainings, geofence entry and exit, loss of connection with the device, and maintenance alerts.</p>

<h2 id="the-other-candidate">The other candidate, and why to check it</h2>

<blockquote><p>The configuration key <strong>Confirm adding vehicles to notifications</strong> is
what makes newly authorised vehicles join existing notifications. Without it, a vehicle added in
March quietly stays outside every notification that already existed.</p></blockquote>

<p>That produces a symptom that sounds like a schedule and is not: "it alerts for some vehicles and
not others" reported by somebody who only drives the new one at weekends. Rule it out before you
conclude.</p>

<h2 id="the-drill">The drill</h2>

<ol>
<li>Find the pattern's actual cause in the sandbox.</li>
<li>Say which of the three parts of the notification carries it.</li>
<li>Say what you ruled out on the way, and how.</li>
<li>Explain it to the customer in two sentences, without using the word "configuration".</li>
</ol>

<blockquote><p><strong>The habit being trained.</strong> A pattern in time is nearly always
somebody's setting. Before investigating why a system behaves differently at weekends, ask what was
configured to make it.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Patterns are settings',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Alerts arrive on weekdays and never at weekends. What should you suspect first?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A pattern in time is nearly always somebody\'s setting. Look at the notification before investigating the platform.',
                        'options' => [
                            ['text' => 'A setting on the notification', 'correct' => true],
                            ['text' => 'A weekend outage on the platform', 'correct' => false],
                            ['text' => 'Devices sleeping when vehicles are parked', 'correct' => false],
                            ['text' => 'The mail server rejecting weekend traffic', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which configuration key makes newly authorised vehicles join notifications that already exist?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Confirm adding vehicles to notifications. Without it, a vehicle added later stays outside every existing notification — which can look like a schedule.',
                        'options' => [
                            ['text' => 'Confirm adding vehicles to notifications', 'correct' => true],
                            ['text' => 'Low Balance Emails', 'correct' => false],
                            ['text' => 'Partner Grace Period (days)', 'correct' => false],
                            ['text' => 'Address in Online Tree', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        "Recalculate drill: fix calibration, then make yesterday's report correct" => [
            'docs' => 'Admin Panel → Recalculate · Sensor types',
            'estimated_minutes' => 40,
            'body' => <<<'HTML'
<p><strong>Fixing the setting is half the job. The customer's complaint is about a report that has
already been produced, and fixing the sensor does not change it.</strong></p>

<h2 id="the-two-halves">The two halves</h2>

<ol>
<li><strong>Correct the sensor.</strong> Fix the calibration so that data arriving from now on is
    right.</li>
<li><strong>Recalculate the past.</strong> Reprocess the period the customer is complaining about so
    the existing report is right too.</li>
</ol>

<p>Stopping after the first is the most common version of "fixed" that is not fixed. The customer
opens yesterday's report, sees the same wrong figure, and reasonably concludes nothing happened.</p>

<h2 id="how-recalculation-runs">How recalculation runs</h2>

<p>The Recalculate tab manually recalculates data, in five steps:</p>

<ol>
<li><strong>Specify the agent</strong> to recalculate.</li>
<li><strong>Select the recalculation type.</strong></li>
<li><strong>Specify the period</strong> — the date range.</li>
<li><strong>Run</strong> it.</li>
<li><strong>Check the report or the history</strong> for the result.</li>
</ol>

<blockquote><p>Step 5 is not optional. Running a recalculation is not evidence that it worked, and
this is a course where "I clicked the button" has never counted as verification.</p></blockquote>

<h2 id="mind-the-sensor">Mind which sensor you are correcting</h2>

<p>Mileage comes from more than one place, and the two are not interchangeable:</p>

<ul>
<li><strong>CAN Mileage</strong> — a discrete sensor that tracks vehicle mileage.</li>
<li><strong>Relative odometer</strong> — a pulse sensor that <em>calculates</em> mileage from
    speedometer data.</li>
</ul>

<p>Calibrating the wrong one produces a confident fix and an unchanged report.</p>

<h2 id="the-drill">The drill</h2>

<ol>
<li>Correct the calibration on the sandbox object you are given.</li>
<li>Recalculate the affected period.</li>
<li>Open the report for that period and confirm the figure has changed.</li>
<li>Write the two sentences you would say to the customer — what was wrong, and what is now true of
    the report they already looked at.</li>
</ol>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the right sensor, the right period.</li>
<li><strong>Method</strong> — fix, then recalculate, then verify. In that order.</li>
<li><strong>Verification</strong> — the report is shown to have changed, not assumed to have.</li>
<li><strong>Communication</strong> — the customer is told the historical report is now correct,
    because otherwise they will not look at it again.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Fixing the past as well as the future',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'You have corrected a sensor calibration. Why is the ticket not finished?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The calibration fixes data arriving from now on. The report the customer is complaining about has already been produced, and needs the period recalculating.',
                        'options' => [
                            ['text' => 'The already-produced report is still wrong until the period is recalculated', 'correct' => true],
                            ['text' => 'The customer has to confirm the new calibration value', 'correct' => false],
                            ['text' => 'Calibration changes need a second marker', 'correct' => false],
                            ['text' => 'The sensor must be re-added after calibration', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the last of the five recalculation steps?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Check the report or the history for the result. Running a recalculation is not evidence that it worked.',
                        'options' => [
                            ['text' => 'Check the report or history for the result', 'correct' => true],
                            ['text' => 'Run the recalculation', 'correct' => false],
                            ['text' => 'Specify the period', 'correct' => false],
                            ['text' => 'Notify the customer', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Write your own diagnostic tree and defend the order of checks' => [
            'docs' => null,
            'estimated_minutes' => 45,
            'body' => <<<'HTML'
<p><strong>The last exercise in the module, and the one that shows whether the rest of it stuck.
Anybody can follow a diagnostic tree. Writing one, and defending its order, is the difference.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Choose one symptom you have met in this module. Write the diagnostic tree for it: the checks, in
order, with each branch labelled by what the outcome would have told you. Then defend the order
aloud to a trainer, who will push on it.</p>

<h2 id="what-decides-the-order">What decides the order</h2>

<p>Not thoroughness. Three things, in this priority:</p>

<ol>
<li><strong>How much does this check eliminate?</strong> A check that halves the field beats one
    that removes a corner of it.</li>
<li><strong>How cheap is it?</strong> Cheap means fast, and available without asking anyone for
    anything. Last reception is cheap. Getting a customer to reproduce something is not.</li>
<li><strong>How reversible is what it leads to?</strong> Put the checks that lead to irreversible
    actions later, when you are more certain.</li>
</ol>

<blockquote><p><strong>The single best first check is nearly always "one object or all of them?"</strong>
It costs nothing, it splits the entire problem space, and every layer below it depends on the answer.</p></blockquote>

<h2 id="the-defence">The defence</h2>

<p>You will be asked, for at least two of your steps:</p>

<ul>
<li><em>Why is this before that one?</em></li>
<li><em>What would you do if this check came back inconclusive?</em></li>
<li><em>Which step here would you drop if you had ninety seconds?</em></li>
</ul>

<p>The third question is the real one. A tree you cannot compress under pressure is a tree you will
abandon on a live call.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the checks are real, and the branches lead where you say.</li>
<li><strong>Method</strong> — the order is justified by elimination and cost, not by habit.</li>
<li><strong>Verification</strong> — each branch names how you would confirm the conclusion.</li>
<li><strong>Communication</strong> — somebody in their first week could follow it.</li>
</ul>

<blockquote><p><strong>Marked on the defence, not the diagram.</strong> A rough tree whose order you
can justify beats a beautiful one you arranged by instinct.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Ordering the checks',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What most decides which check comes first?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'How much it eliminates, and how cheap it is. Thoroughness decides nothing about order.',
                        'options' => [
                            ['text' => 'How much of the field it eliminates, for how little cost', 'correct' => true],
                            ['text' => 'How thorough it is', 'correct' => false],
                            ['text' => 'How likely that cause is in general', 'correct' => false],
                            ['text' => 'Which check the customer expects you to run', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why does the trainer ask which step you would drop if you had ninety seconds?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A tree you cannot compress under pressure is one you will abandon on a live call. Knowing what to drop is what makes it usable.',
                        'options' => [
                            ['text' => 'A tree you cannot compress is one you will abandon on a live call', 'correct' => true],
                            ['text' => 'Ninety seconds is the target handling time', 'correct' => false],
                            ['text' => 'To check the tree has at least four steps', 'correct' => false],
                            ['text' => 'To find out which check is the most expensive', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module B — knowledge check',
        'description' => 'Troubleshooting methodology. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What is the single most useful first question on almost any symptom?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'One object or all of them. It costs nothing and splits the entire problem space.',
                'options' => [
                    ['text' => 'Is it one object or all of them?', 'correct' => true],
                    ['text' => 'When was the device last serviced?', 'correct' => false],
                    ['text' => 'Which browser are they using?', 'correct' => false],
                    ['text' => 'Has anyone contacted second line already?', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'An object reports regularly but shows no satellites. What is that?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'It is communicating and cannot fix its position — a positioning fault, which looks stuck on the map and healthy in the data.',
                'options' => [
                    ['text' => 'A positioning fault, not a communications one', 'correct' => true],
                    ['text' => 'A device that has gone offline', 'correct' => false],
                    ['text' => 'A blocked object', 'correct' => false],
                    ['text' => 'A sensor that has not been configured', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'How far back does Raw data go?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Up to two days. Knowing the limit before you look is the difference between "I checked" and "I could not check, and here is why".',
                'options' => [
                    ['text' => 'Up to two days', 'correct' => true],
                    ['text' => 'Up to thirty days', 'correct' => false],
                    ['text' => 'As far back as the object has existed', 'correct' => false],
                    ['text' => 'It depends on the tariff', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'You have corrected a calibration. What still has to happen before the customer\'s complaint is answered?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Recalculate the affected period and check the report. The calibration only fixes data arriving from now on.',
                'options' => [
                    ['text' => 'Recalculate the affected period, then check the report changed', 'correct' => true],
                    ['text' => 'Wait for the next daily processing run', 'correct' => false],
                    ['text' => 'Re-add the sensor so it picks up the new value', 'correct' => false],
                    ['text' => 'Ask the customer to regenerate the report', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer cannot see something you can see. What is the correct next step?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Look at their user — their access level, the modules on their contract, and their permissions. Your own screen says nothing about theirs.',
                'options' => [
                    ['text' => 'Look at their user, their contract\'s modules, and their permissions', 'correct' => true],
                    ['text' => 'Confirm that you can see it and close the ticket', 'correct' => false],
                    ['text' => 'Widen your own rights and try to reproduce it', 'correct' => false],
                    ['text' => 'Ask them to try a different browser', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Diagnose six planted faults cold',
        'lesson_title' => 'Broken sandbox: diagnose 6 planted faults cold ⭐ highest-value exercise',
        'brief' => 'Six faults are planted in a sandbox account, across the layers. Diagnose all six without hints, and without fixing anything until all six are found.',
        'submission_instructions' => 'For each fault, submit: the symptom as a customer would report it, the layer, the checks you ran in order with the outcome of each, the cause, and how you would verify the fix. Include the checks that found nothing — a negative result is a result, and it is how your method is marked. At least one of the six is a setting behaving correctly; say which and why. Reporting four found and two not found, honestly, marks higher than six confident answers with two wrong.',
        'requires_screenshot' => true,
        'estimated_minutes' => 90,
        'requires_second_marker' => true,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Sandbox account ID you worked in', 'hint' => 'The account you were given'],
            ['key' => 'findings', 'label' => 'The six write-ups', 'hint' => 'Symptom, layer, checks with outcomes, cause, verification'],
            ['key' => 'non_fault', 'label' => 'Which of the six was not a fault, and why', 'hint' => 'The setting that was behaving correctly'],
        ],
    ],
];
