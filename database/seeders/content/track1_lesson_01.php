<?php

/**
 * Lesson 1 — Introduction to PILOT and basic concepts.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · About the platform  /concepts.html
 *   · Before you start → Glossary  /glossary.html
 *   · Before you start → Roles and access rights  /roles_and_access_rights.html
 *
 * Definitions in <blockquote> are quoted verbatim from the glossary so a trainee
 * learns the platform's own wording — the words they will see in the docs and
 * hear from customers. Everything around them is written for a new starter.
 *
 * THE PATTERN, for whoever writes lesson 2:
 *   - `lessons` is keyed by the existing lesson title, so bodies attach to the
 *     curriculum already seeded rather than replacing it.
 *   - Every lesson names its source page. If it is not in the docs, do not
 *     write it — see the `needs_input` entry below for how to leave a gap
 *     honestly.
 *   - `quiz` is the knowledge check at the end of the lesson. Scenario stems,
 *     four options, one best answer, and a rationale on every option including
 *     the wrong ones — §4.2 of the implementation plan.
 */

return [
    'module_subtitle' => 'Introduction to PILOT and basic concepts',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Define: Object, Sensor, Contract, Account' => [
            'docs' => 'Before you start → Glossary · About the platform',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Four words do most of the work in PILOT. Almost every ticket you take will be
about one of them, and customers use them loosely — so it is worth being precise from day one.</strong></p>

<h2 id="object">Object</h2>

<blockquote><p>An object is <strong>a vehicle, equipment, person, animal, or any other moving or
stationary item being monitored</strong>.</p></blockquote>

<p>Note what this does <em>not</em> say. An object is not necessarily a vehicle, and it is not the
tracking device. The device reports data; the object is the thing the customer cares about. A
customer saying "my object is offline" usually means the device attached to it has stopped
reporting — those are different problems with different fixes.</p>

<h2 id="sensor">Sensor</h2>

<blockquote><p>A sensor is <strong>a device that collects and transmits data about a specific
parameter or the state of an object</strong>.</p></blockquote>

<p>One object can carry many sensors: ignition, fuel level, temperature, door position. Each
reports one parameter. When somebody reports "wrong fuel readings", the useful first question is
which sensor, because the fault is usually in one sensor's configuration rather than the object.</p>

<h2 id="contract">Contract</h2>

<blockquote><p>A contract is <strong>the main element in the system that brings together all
monitored objects, users, and available features for a client or company</strong>.</p></blockquote>

<blockquote><p>This is the one new starters most often get wrong. A contract is not a piece of
paperwork — it is the container. Objects live in a contract, users are granted access within a
contract, and the modules a customer can use are activated on a contract. If somebody cannot see an
object, the first question is almost always <em>which contract is it in, and does this user have
access to that contract?</em></p></blockquote>

<h2 id="account">Account</h2>

<blockquote><p>An account is <strong>a personal user profile in the monitoring
system</strong>.</p></blockquote>

<p>It is where a person obtains access to data and system capabilities, and where their personal
settings are stored. An account is a <em>person</em>. A contract is an <em>organisation's
workspace</em>. One person may hold access within several contracts.</p>

<h2 id="how-they-fit">How they fit together</h2>

<ul>
<li>A <strong>contract</strong> holds objects, users and activated modules.</li>
<li>An <strong>object</strong> is the thing being monitored, and sits inside a contract.</li>
<li>A <strong>sensor</strong> reports one parameter about one object.</li>
<li>An <strong>account</strong> is a person, granted access within a contract.</li>
</ul>

<blockquote><p><strong>On a call:</strong> when a customer describes a problem, work out which of
these four they are actually talking about before you start looking. "I can't see my truck" could be
an object problem, a contract problem or an access problem, and they are investigated in different
places.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Object, Sensor, Contract, Account',
                'description' => 'Verify your understanding of core PILOT entity terminology.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which PILOT entity serves as the container bringing together monitored assets, user logins, and activated modules for a client?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The glossary defines a Contract as the primary entity containing all objects, users, and available software modules for a client or organization.',
                        'options' => [
                            ['text' => 'Contract', 'correct' => true],
                            ['text' => 'Object', 'correct' => false],
                            ['text' => 'Sensor', 'correct' => false],
                            ['text' => 'Account', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer says: "My vehicle is online, but I cannot see its fuel level." What is the first entity type you should investigate?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A Sensor is attached to an object and translates raw telemetry into a specific measured parameter, such as fuel level or temperature.',
                        'options' => [
                            ['text' => 'Sensor configuration on the object', 'correct' => true],
                            ['text' => 'The client\'s billing contract', 'correct' => false],
                            ['text' => 'The user\'s browser cache', 'correct' => false],
                            ['text' => 'The vehicle license plate in the Info tab', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Explain Personal Account vs Admin Panel' => [
            'docs' => 'About the platform · Before you start → Roles and access rights',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>PILOT has two front doors. Knowing which one a customer is standing at saves half
the confusion on a support call.</strong></p>

<h2 id="two-tiers">Two interfaces, two audiences</h2>

<p>The platform separates access into two tiers:</p>

<ul>
<li><strong>The user dashboard</strong> (personal account) — the day-to-day interface. Personnel
observe vehicles, equipment, people and other monitored resources, <em>without administrative
privileges</em>. This is where a fleet manager watches their trucks and runs reports.</li>

<li><strong>The admin panel</strong> — configuration access. Administrators manage accounts, users,
system assets and the overall platform structure. This is where contracts are created, modules
activated and tariffs set.</li>
</ul>

<h2 id="roles">Who holds what</h2>

<p>Within the <strong>user dashboard</strong>:</p>

<ul>
<li><strong>Administrator</strong> — full access to all features in the user portal.</li>
<li><strong>User</strong> — permissions decided by the administrator. The administrator chooses
which actions they can perform and which are restricted.</li>
</ul>

<p>Within the <strong>admin panel</strong>:</p>

<ul>
<li><strong>Main administrator</strong> — usually technical support staff, with unlimited access to
all data and settings, including managing roles and permissions.</li>
<li><strong>Administrator</strong> — a partner-level role that sets up and integrates the system for
different companies and fleets. Limited rights, but able to see all the fleets, contracts and
vehicles they work with.</li>
<li><strong>User</strong> — additional staff a partner creates, with specific access rights inside
the partner's own permissions.</li>
</ul>

<blockquote><p><strong>Master</strong> is a status rather than a role. It is assigned when logging
in <em>through the admin panel</em>, and carries unlimited rights in the system.</p></blockquote>

<h2 id="why-it-matters">Why this matters on a call</h2>

<p>A customer saying "I'm an admin and I still can't do it" may be an administrator of their
<em>user portal</em> — which grants nothing in the admin panel. Establish which interface they are
in before you start diagnosing a permissions problem, or you will spend ten minutes looking in the
wrong place.</p>

<blockquote><p><strong>The question to ask:</strong> "What is the address in your browser's bar?"
It settles it faster than "are you an administrator?", because everybody thinks they are.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Personal Account vs Admin Panel',
                'description' => 'Test your ability to distinguish user dashboard operations from admin panel tasks.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Where are new contracts created and platform modules (such as Video or Eco Driving) activated?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Administrative Panel is where contracts are provisioned, modules activated, and partners configured. The user dashboard only accesses already-activated features.',
                        'options' => [
                            ['text' => 'In the Administrative Panel', 'correct' => true],
                            ['text' => 'In the Personal Account User Dashboard', 'correct' => false],
                            ['text' => 'In the Map Tools layer menu', 'correct' => false],
                            ['text' => 'Inside the individual object card', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A fleet manager calls stating they have "full Administrator rights" but cannot see billing options or module licenses. What is the cause?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Being an administrator in the user portal grants full rights over monitored resources, but zero access to the administrative panel where billing and contract modules are managed.',
                        'options' => [
                            ['text' => 'They have admin rights in the user dashboard, which does not confer admin panel access', 'correct' => true],
                            ['text' => 'Their account is locked due to overdue invoices', 'correct' => false],
                            ['text' => 'Their browser does not support WebSockets', 'correct' => false],
                            ['text' => 'The GPS trackers are configured with wrong APN settings', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Explain what a Mapping Contract is and its use case' => [
            'docs' => null,
            'estimated_minutes' => 10,

            /*
             * Deliberately not written.
             *
             * "Mapping Contract" appears in the original training plan but not
             * in the 7.10 documentation — it is not in the glossary, not in
             * "About the platform", and not in "Roles and access rights".
             * Writing a plausible-sounding definition would put invented PILOT
             * behaviour in front of new starters, which is worse than a gap.
             */
            'needs_input' => 'The term does not appear anywhere in the 7.10 documentation. '
                .'Igor to confirm what a Mapping Contract is, or whether the topic should be '
                .'retired from the lesson.',

            'body' => <<<'HTML'
<blockquote><p><strong>This lesson is not written yet.</strong></p></blockquote>

<p>The term <em>Mapping Contract</em> comes from the original training plan, but it does not appear
in the PILOT 7.10 documentation — not in the glossary, not in <em>About the platform</em>, and not
in <em>Roles and access rights</em>.</p>

<p>Rather than write a plausible-sounding definition, it has been left open. A confident but wrong
explanation of a PILOT concept is worse than an obvious gap: you would carry it onto calls.</p>

<p>Ask your trainer, and this lesson will be filled in once the definition is confirmed.</p>
HTML,
            'quiz' => [
                'title' => 'Quiz: Handling Unconfirmed Terminology & Escalations',
                'description' => 'Test your understanding of support integrity when encountering undocumented concepts.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'If a customer or training checklist mentions a term not found in official documentation (e.g., docs.pilot-gps.com), what is the correct protocol?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'An invented explanation is worse than an honest gap. Support engineers must verify unconfirmed terms with senior staff or trainers before advising clients.',
                        'options' => [
                            ['text' => 'Flag the gap and verify the precise meaning with a trainer or product lead before advising clients', 'correct' => true],
                            ['text' => 'Invent a plausible guess based on other GPS platforms', 'correct' => false],
                            ['text' => 'Tell the client the feature does not exist in PILOT without checking', 'correct' => false],
                            ['text' => 'Close the ticket immediately', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
     * The knowledge check.
     *
     * Module-scoped, so it sits at the end of this lesson rather than acting as
     * the course's final exam. Every option carries a rationale — the wrong ones
     * are each a common misconception, which is what makes the feedback useful.
     */
    'quiz' => [
        'title' => 'Module 1 — knowledge check',
        'description' => 'Four questions on the vocabulary and the two interfaces. '
            .'You need 70% to pass, and you may retake it.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A customer calls and says "one of my objects has stopped reporting". '
                    .'Before you look anything up, what have they most likely described?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'An object is the thing being monitored; the device attached to it '
                    .'is what reports. "The object stopped reporting" almost always means the device '
                    .'has stopped sending data — a different problem from the object being '
                    .'misconfigured or invisible to their user.',
                'options' => [
                    ['text' => 'The tracking device on a monitored item has stopped sending data', 'correct' => true],
                    ['text' => 'Their contract has expired', 'correct' => false],
                    ['text' => 'Their user account has been blocked', 'correct' => false],
                    ['text' => 'A sensor has been deleted from the object', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which statement about a contract is correct?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The glossary defines a contract as the main element bringing '
                    .'together all monitored objects, users and available features for a client or '
                    .'company. It is the container, not paperwork and not a person.',
                'options' => [
                    ['text' => 'It brings together the objects, users and available features for a client', 'correct' => true],
                    ['text' => 'It is the signed commercial agreement stored as a PDF', 'correct' => false],
                    ['text' => 'It is one person\'s login and personal settings', 'correct' => false],
                    ['text' => 'It is the hardware fitted to a vehicle', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A caller insists "I am an administrator, so I should be able to '
                    .'activate the Video module." They are logged into their personal account and '
                    .'cannot find the option. What is the most likely explanation?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Administrator in the user dashboard means full access to the user '
                    .'portal — it grants nothing in the admin panel, where modules are activated. '
                    .'Establishing which interface somebody is in comes before diagnosing '
                    .'permissions.',
                'options' => [
                    ['text' => 'They are an administrator of the user dashboard, and module activation happens in the admin panel', 'correct' => true],
                    ['text' => 'Their administrator rights have been revoked without their knowledge', 'correct' => false],
                    ['text' => 'The Video module is incompatible with their objects', 'correct' => false],
                    ['text' => 'They need to clear their browser cache and sign in again', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Match the relationship: which of these is true?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A sensor reports one parameter about one object; objects sit '
                    .'inside a contract. Getting this hierarchy the right way round is what lets you '
                    .'work out where to look when something is missing.',
                'options' => [
                    ['text' => 'A sensor reports one parameter about an object, and objects sit inside a contract', 'correct' => true],
                    ['text' => 'An object contains contracts, and each contract carries one sensor', 'correct' => false],
                    ['text' => 'A contract belongs to an object, and accounts belong to sensors', 'correct' => false],
                    ['text' => 'A sensor is a type of account with restricted permissions', 'correct' => false],
                ],
            ],
        ],
    ],
];
