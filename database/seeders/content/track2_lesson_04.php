<?php

/**
 * Admin panel — Module 4: Combined assessment.
 *
 * The capstone. Nothing new is taught here: each topic is a brief, with the
 * acceptance criteria stated up front so a trainee knows what "done" means
 * before starting rather than after being marked.
 *
 * Content drawn from docs.pilot-gps.com:
 *   · Sensors → Sensor types                     /sensor_types.html
 *   · Admin Panel → Account → Modules            /modules_2.html
 *   · Admin Panel → Account → Configuration      /configuration.html
 *   · Admin Panel → Partners → How to add        /how_to_add_a_partner.html
 *   · Admin Panel → Partners → SMTP              /email_sending_configuration__smtp_.html
 *   · Account settings → Privacy                 /privacy.html
 *
 * Two corrections to earlier drafts of this module, both worth keeping:
 *
 * **"Mileage by CAN" is a sensor, not a configuration.** The documented sensor
 * type is `CAN Mileage` — "discrete sensor that tracks vehicle mileage". It is
 * added to the object as a sensor.
 *
 * **The Video module is called `CMS and Video`**, and the new-tab behaviour is
 * the documented `open_in_new_tab` configuration key.
 *
 * Markup is limited to what Filament's rich editor round-trips: headings,
 * lists, blockquotes, tables and inline marks. No div, dl or span — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Combined assessment',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Create a prepaid contract, add an object, configure mileage by CAN' => [
            'docs' => 'Admin Panel → Account · Vehicles · Sensors → Sensor types',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Provision a customer from nothing: the container, the vehicle in it, and the sensor that
makes its mileage mean something. Everything you need is in Modules 1 and 2.</strong></p>

<h2 id="the-brief">The brief</h2>

<ol>
<li><strong>Create a Prepayment contract</strong> in the training account.</li>
<li><strong>Get an object into it.</strong> Transfer one from the pool: Vehicles → Edit → the
    <strong>Account</strong> field. All the entered parameters go with it.</li>
<li><strong>Add a CAN Mileage sensor</strong> to that object.</li>
</ol>

<h2 id="the-sensor">The one fact people get wrong</h2>

<blockquote><p>Mileage by CAN is a <strong>sensor</strong>, not a configuration key.
<strong>CAN Mileage</strong> is a discrete sensor that tracks vehicle mileage, and it is added to
the object the way any other sensor is.</p></blockquote>

<p>There is a second, different mileage sensor, and knowing the difference is the point of including
this at all:</p>

<table>
<thead>
<tr><th>Sensor</th><th>Where the number comes from</th></tr>
</thead>
<tbody>
<tr><td><strong>CAN Mileage</strong></td><td>The vehicle's own bus. A discrete sensor that tracks vehicle mileage.</td></tr>
<tr><td><strong>Relative odometer</strong></td><td>A pulse sensor that <em>calculates</em> mileage from speedometer data.</td></tr>
</tbody>
</table>

<p>One reports what the vehicle says; the other works it out. A customer disputing their mileage
report is asking which of those they are on, whether they know it or not.</p>

<h2 id="acceptance">What "done" means</h2>

<ul>
<li>The contract exists and is a <strong>Prepayment</strong> contract.</li>
<li>The Vehicles list shows the object with that contract in its <strong>Agreement</strong> and
    <strong>Owner</strong> columns.</li>
<li>The object carries a <strong>CAN Mileage</strong> sensor.</li>
<li>You can say, in one sentence, why CAN Mileage and Relative odometer are not interchangeable.</li>
</ul>

<h2 id="evidence">Evidence to submit</h2>

<ul>
<li>the <strong>contract / account ID</strong>;</li>
<li>the <strong>Agent ID</strong> of the object;</li>
<li>a <strong>screenshot</strong> of the Vehicles list showing that row with its Agreement and Owner
    filled in;</li>
<li>a <strong>screenshot</strong> of the object's sensors showing CAN Mileage.</li>
</ul>

<blockquote><p><strong>Training account only.</strong> Every step here is a live administrative
change. Use the account you were given.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Prepaid contract, object and CAN mileage',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'How is mileage by CAN set up on an object?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'CAN Mileage is a documented sensor type — a discrete sensor that tracks vehicle mileage — and is added to the object as a sensor. It is not a configuration key.',
                        'options' => [
                            ['text' => 'By adding a CAN Mileage sensor to the object', 'correct' => true],
                            ['text' => 'By adding a configuration key to the contract', 'correct' => false],
                            ['text' => 'By setting a field on Vehicles → Edit', 'correct' => false],
                            ['text' => 'By activating the Recalculation module', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the difference between CAN Mileage and Relative odometer?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'CAN Mileage tracks the vehicle\'s own mileage; Relative odometer is a pulse sensor that calculates mileage from speedometer data.',
                        'options' => [
                            ['text' => 'CAN Mileage reads the vehicle\'s own figure; Relative odometer calculates it from speedometer data', 'correct' => true],
                            ['text' => 'They are the same sensor under two names', 'correct' => false],
                            ['text' => 'CAN Mileage is for trailers, Relative odometer for tractors', 'correct' => false],
                            ['text' => 'Relative odometer reads the bus; CAN Mileage estimates from GPS', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Create a partner, assign a contract, configure SMTP' => [
            'docs' => 'Admin Panel → Partners → How to add a partner · SMTP',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Stand up a reseller end to end, and prove it can send mail. An untested SMTP tab is not a
finished job — it is a ticket somebody else opens next week.</strong></p>

<h2 id="the-brief">The brief</h2>

<ol>
<li><strong>Create a partner.</strong> Partners → Add → Main settings. Set at least the Name, Email,
    <strong>Currency</strong>, <strong>Price</strong> and <strong>Login</strong>.</li>
<li><strong>Assign a contract to them.</strong> Contracts are assigned after the partner exists.</li>
<li><strong>Configure SMTP.</strong> Double-click the partner → the SMTP tab → Host, Port, Login,
    Password, Email and Security.</li>
<li><strong>Run the connection test</strong>, and record the result.</li>
</ol>

<h2 id="the-traps">The two traps</h2>

<blockquote><p><strong>Price applies only to new objects.</strong> Setting a partner's plan does not
reprice the objects they already hold. If the assessment scenario expects existing objects to move
onto the new plan, that expectation is wrong, and saying so is part of passing.</p></blockquote>

<blockquote><p><strong>The test tells you which stage failed.</strong> A connection that never opens
is Host, Port or a firewall. One that opens, sends <code>EHLO</code>, and then fails is Login or
Password. Reporting "SMTP doesn't work" without saying which is a handover nobody can act on.</p></blockquote>

<h2 id="acceptance">What "done" means</h2>

<ul>
<li>The partner exists and can be found in the Partners list.</li>
<li>A contract is assigned to them.</li>
<li>The SMTP tab is complete, including <strong>SMTP Security</strong>.</li>
<li>The connection test has been <strong>run</strong>, and you can say what it returned — including
    if it failed, and at which stage.</li>
</ul>

<h2 id="evidence">Evidence to submit</h2>

<ul>
<li>the <strong>partner name</strong> and the <strong>contract / account ID</strong> assigned;</li>
<li>a <strong>screenshot</strong> of the SMTP tab with the fields filled in — obscure the
    password;</li>
<li>the <strong>test result</strong>, in your own words.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Partner provisioning',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'The SMTP test opens the connection and sends EHLO, then fails. What do you report?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Reaching the authentication stage means the host and port were reachable, so the fault is in the credentials.',
                        'options' => [
                            ['text' => 'Authentication failed — the Login or Password is wrong', 'correct' => true],
                            ['text' => 'The host is unreachable', 'correct' => false],
                            ['text' => 'SMTP Security must be set to No encryption', 'correct' => false],
                            ['text' => 'The partner has no contract assigned', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'In what order are a partner and their contract created?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The documentation is explicit: after saving the partner you can assign contracts, connect objects and create users for them.',
                        'options' => [
                            ['text' => 'The partner first — contracts are assigned after it is saved', 'correct' => true],
                            ['text' => 'The contract first, then the partner is attached to it', 'correct' => false],
                            ['text' => 'Either order works identically', 'correct' => false],
                            ['text' => 'Both are created together on the Main settings tab', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Activate the Video module, configure streaming in a new tab' => [
            'docs' => 'Admin Panel → Account → Modules · Account → Configuration',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>An entitlement and a preference — two different mechanisms, and this brief is passed by
not confusing them.</strong></p>

<h2 id="the-brief">The brief</h2>

<ol>
<li><strong>Activate the video module</strong> on the training contract. It is called
    <strong>CMS and Video</strong> — "monitoring driver and passenger behaviour inside vehicles".</li>
<li><strong>Set the configuration</strong> <strong>open_in_new_tab = true</strong> on that
    account.</li>
<li><strong>Verify</strong> that the video opens as the configuration says it should.</li>
</ol>

<h2 id="two-mechanisms">Two mechanisms, not one</h2>

<table>
<thead>
<tr><th>Question</th><th>Answered by</th></tr>
</thead>
<tbody>
<tr><td><em>May this customer use video at all?</em></td><td>The <strong>CMS and Video</strong> module, on the contract. Only the system administrator can manage activation.</td></tr>
<tr><td><em>How does the video open for them?</em></td><td>The <strong>open_in_new_tab</strong> configuration key, <code>true</code> or <code>false</code>.</td></tr>
</tbody>
</table>

<p>Setting the configuration on a contract without the module does nothing, and it does nothing
silently. That is the failure worth being able to recognise.</p>

<h2 id="acceptance">What "done" means</h2>

<ul>
<li><strong>CMS and Video</strong> is active on the contract.</li>
<li><strong>open_in_new_tab</strong> is set on the account.</li>
<li>You have opened the video and observed the behaviour, rather than assuming it.</li>
<li>You can state which of the two would be at fault for "video is missing" and which for "video
    opens in the wrong place".</li>
</ul>

<h2 id="evidence">Evidence to submit</h2>

<ul>
<li>the <strong>contract / account ID</strong>;</li>
<li>a <strong>screenshot</strong> of the module list showing CMS and Video active;</li>
<li>a <strong>screenshot</strong> of the configuration list showing open_in_new_tab;</li>
<li>one sentence on what you observed when you opened the video.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Video module and streaming behaviour',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A customer reports that video is missing entirely. Which mechanism is at fault?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Whether a customer has video at all is the CMS and Video module on their contract. open_in_new_tab only controls how it opens for a customer who already has it.',
                        'options' => [
                            ['text' => 'The CMS and Video module is not active on the contract', 'correct' => true],
                            ['text' => 'open_in_new_tab is set to false', 'correct' => false],
                            ['text' => 'The object has no CAN Mileage sensor', 'correct' => false],
                            ['text' => 'The partner has no SMTP configured', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'You set open_in_new_tab on a contract that does not have the video module, and nothing happens. What is that?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A configuration that governs how a feature behaves does nothing when the feature is not there — and it fails silently, which is why the module is always the first check.',
                        'options' => [
                            ['text' => 'Expected — the configuration governs a feature the contract does not have', 'correct' => true],
                            ['text' => 'A fault to escalate to second line', 'correct' => false],
                            ['text' => 'A sign the value should have been 1 rather than true', 'correct' => false],
                            ['text' => 'A caching problem that clears overnight', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Write user instructions: how to change the password in the personal account' => [
            'docs' => 'Account settings → Privacy',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>The last assessment is not an administrative task. It is the one skill everything else
depends on: turning what you know into something a customer can follow.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Write instructions a non-technical fleet manager can follow, unaided, to change their own
password in their personal account. No admin panel terminology. No "navigate to". Assume they are
holding a phone in one hand.</p>

<h2 id="the-facts">The facts you are working from</h2>

<p>Account settings are opened by clicking the <strong>configuration icon on the top panel</strong>.
Password changes live on the <strong>Privacy</strong> tab:</p>

<ol>
<li>Open the <strong>Privacy</strong> tab and find the <strong>Change password</strong>
    section.</li>
<li>Create a new strong password.</li>
<li>Enter it in the field provided.</li>
<li>Re-enter it in the confirmation field.</li>
<li>Click <strong>Save</strong>.</li>
<li>Optionally, tick <strong>Send to email</strong> to receive the new password at the registered
    address.</li>
</ol>

<blockquote><p><strong>One thing to be careful with.</strong> The tabs on the left of Account
settings vary depending on which modules are enabled for the account. Instructions that say "the
fourth item down" will be wrong for somebody. Name the tab.</p></blockquote>

<h2 id="what-is-being-marked">What is being marked</h2>

<ul>
<li><strong>Correctness</strong> — the steps match the platform, in the right order, with the
    real names of things.</li>
<li><strong>Method</strong> — the reader can act on it without knowing anything you know.</li>
<li><strong>Verification</strong> — you tell them how to confirm it worked.</li>
<li><strong>Communication</strong> — plain sentences. No admin-panel vocabulary, no "simply", no
    step that hides three steps inside it.</li>
</ul>

<blockquote><p><strong>The test to apply to your own draft.</strong> Read it aloud as though you
were on the phone. Anywhere you would have to add a word to make it make sense, it is missing a
step.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Changing a password in the personal account',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which tab of Account settings holds the password change?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Privacy. It carries the Change password section, the user email addresses, and the two-factor authentication settings.',
                        'options' => [
                            ['text' => 'Privacy', 'correct' => true],
                            ['text' => 'Personalization', 'correct' => false],
                            ['text' => 'Map and geocoding', 'correct' => false],
                            ['text' => 'Display settings template', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why should written instructions name the tab rather than say "the fourth item down"?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The set of tabs on the left varies depending on which modules are enabled for the account, so a positional instruction is wrong for some customers.',
                        'options' => [
                            ['text' => 'The set of tabs varies with the modules enabled on the account', 'correct' => true],
                            ['text' => 'The tabs are in a random order for each user', 'correct' => false],
                            ['text' => 'The tab order changes between browsers', 'correct' => false],
                            ['text' => 'Positional instructions are fine as long as you add a screenshot', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 4 — Final assessment',
        'description' => 'The whole admin panel course, across contracts, objects, sensors, partners, modules and configuration. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Mileage from the vehicle\'s own bus is set up how?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'CAN Mileage is a sensor type — a discrete sensor that tracks vehicle mileage — added to the object.',
                'options' => [
                    ['text' => 'A CAN Mileage sensor on the object', 'correct' => true],
                    ['text' => 'A configuration key on the contract', 'correct' => false],
                    ['text' => 'A field on Vehicles → Edit', 'correct' => false],
                    ['text' => 'The Recalculation module', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A partner\'s new pricing plan has not changed the price of the objects they already had. What is true?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Price field on the partner form applies only to new objects. This is expected behaviour, not a fault.',
                'options' => [
                    ['text' => 'That is expected — Price applies only to new objects', 'correct' => true],
                    ['text' => 'The plan will apply at the next invoice', 'correct' => false],
                    ['text' => 'The partner needs a Partner price as well', 'correct' => false],
                    ['text' => 'The objects must be transferred and transferred back', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which pair correctly separates "may they use video" from "how does video open"?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The CMS and Video module is the entitlement, on the contract. open_in_new_tab is a configuration key governing behaviour.',
                'options' => [
                    ['text' => 'The CMS and Video module, and the open_in_new_tab configuration', 'correct' => true],
                    ['text' => 'The open_in_new_tab configuration, and the Theme rebranding field', 'correct' => false],
                    ['text' => 'The Analytics (Panel) module, and the Main Tab configuration', 'correct' => false],
                    ['text' => 'The partner\'s SMTP tab, and the Notification module', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A vehicle has vanished from a customer\'s account. Which columns tell you where it went?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Agreement is the agreement number the object belongs to, and Owner is that agreement\'s name. A transfer shows up there immediately.',
                'options' => [
                    ['text' => 'Agreement and Owner on the Vehicles tab', 'correct' => true],
                    ['text' => 'Himself and Administrator on the Vehicles tab', 'correct' => false],
                    ['text' => 'Partner and Subpartner on the payment record', 'correct' => false],
                    ['text' => 'Online and Last update on the Vehicles tab', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer wants a warning before their balance runs out, and nothing arrives. Which two things must both be right?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Low Balance Emails configuration key names the recipient; the partner\'s SMTP configuration is what sends. Check SMTP first — a recipient on an account that cannot send mail tells you nothing.',
                'options' => [
                    ['text' => 'The Low Balance Emails key, and the partner\'s SMTP configuration', 'correct' => true],
                    ['text' => 'The Notification module, and a speeding notification', 'correct' => false],
                    ['text' => 'The Partner Grace Period, and the Invoices window', 'correct' => false],
                    ['text' => 'The Templates editor, and a tariff on the object', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Where does a user change their own password?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Account settings — opened from the configuration icon on the top panel — then the Privacy tab, Change password section.',
                'options' => [
                    ['text' => 'Account settings → Privacy → Change password', 'correct' => true],
                    ['text' => 'The admin panel, under Account → Users', 'correct' => false],
                    ['text' => 'Account settings → Personalization', 'correct' => false],
                    ['text' => 'It can only be reset by an administrator', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'End-to-end admin panel deployment',
        'lesson_title' => 'Create a prepaid contract, add an object, configure mileage by CAN',
        'brief' => 'Provision a customer from nothing in the training account: a Prepayment contract, an object transferred into it, and a CAN Mileage sensor on that object.',
        'submission_instructions' => 'Do the work in the training PILOT account. Submit the contract ID and the Agent ID, a screenshot of the Vehicles list showing the object with its Agreement and Owner columns filled in, and a screenshot of the object\'s sensors showing CAN Mileage. Add one sentence explaining why CAN Mileage and Relative odometer are not interchangeable — a correct configuration you cannot explain is not yet competence.',
        'requires_screenshot' => true,
        'estimated_minutes' => 30,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Contract / account ID of the Prepayment contract', 'hint' => 'The ID generated when the contract was saved'],
            ['key' => 'agent_id', 'label' => 'Agent ID of the object you transferred in', 'hint' => 'From the Agent ID column on the Vehicles tab'],
            ['key' => 'sensor_explanation', 'label' => 'Why CAN Mileage and Relative odometer are not interchangeable', 'hint' => 'One sentence, in your own words'],
        ],
    ],
];
