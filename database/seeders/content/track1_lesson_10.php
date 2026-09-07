<?php

/**
 * Lesson 10 — Additional modules and tools.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Tokens                           /tokens.html
 *   · Report scheduler                 /report_scheduler.html
 *   · Privacy (Notifications)          /privacy.html
 *   · About the platform (Modules)     /concepts.html
 */

return [
    'module_subtitle' => 'Additional modules and tools',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Describe the Notifications module and its requirements' => [
            'docs' => 'About the platform → Modules · Account settings → Privacy',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>PILOT is built around a modular core architecture. Features like Advanced Notifications, Video Streaming, and Maintenance Scheduling are modular components that can be activated per contract.</strong></p>

<h2 id="modular-architecture">How modules work in PILOT</h2>

<p>A contract owner or system administrator in the Admin Panel chooses which modules to activate for a given customer contract. If a module is inactive on the contract, its buttons, menu options, and settings tabs are hidden entirely from the user interface.</p>

<h2 id="notifications-requirements">Requirements for the Notifications module</h2>

<p>To run automated event notifications successfully, three requirements must be satisfied:</p>

<ol>
<li><strong>Module Activation</strong> — the <em>Notifications</em> module must be enabled on the client's contract in the Administrative Panel.</li>
<li><strong>Confirmed Delivery Channel</strong> — the recipient email address must carry a verified green check badge in Account Settings → Privacy.</li>
<li><strong>Sensor and Telemetry Preconditions</strong> — the underlying hardware parameters must be reporting. For example, a <em>Low Fuel</em> alert requires a functioning fuel sensor; an <em>Over-speed</em> alert requires reliable GPS speed packets.</li>
</ol>

<blockquote><p><strong>Support tip:</strong> If a customer asks "Why don't I see the Notifications tab in my settings?", don't spend 20 minutes debugging their browser cache. Check the Admin Panel to see if the module was ever activated for their contract.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        "Explain what a Token is and when it's used" => [
            'docs' => 'Account settings → Tokens',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Often a logistics company must give a customer, partner, or temporary subcontractor live visibility over specific delivery trucks — without creating a permanent account or exposing credentials. Tokens provide secure, time-bound, scoped access.</strong></p>

<h2 id="two-token-types">The two types of tokens in PILOT</h2>

<ul>
<li><strong>1. Monitor Token (Public Live Tracking Link)</strong> — Generates a public web link or iframe embed code. Anyone opening the link sees a lightweight map showing only the specific vehicles and sensors selected by the creator. The recipient never receives login credentials and cannot see other fleet assets or account settings. Can have an automatic expiration timestamp.</li>

<li><strong>2. Access Token (Passwordless Auto-Login)</strong> — Generates a direct authentication URL tied to a specific user profile. Clicking the link automatically authenticates the user into PILOT without entering a password. Ideal for integrating PILOT dashboards inside third-party internal enterprise portals or automated kiosk screens.</li>
</ul>

<h2 id="token-permissions">Managing token permissions</h2>

<p>To protect security, standard users cannot create tokens unless explicitly authorized. An administrator must grant two rights on the user card's Rights tab:</p>

<ul>
<li><strong>Edit Tokens</strong> — allows the user to create new Monitor/Access tokens and share tracking links.</li>
<li><strong>Delete Tokens</strong> — allows the user to revoke or delete active tokens.</li>
</ul>

<h2 id="creating-monitor-token">Step-by-step: Creating a Monitor Token</h2>

<ol>
<li>Navigate to <strong>Account settings</strong> → <strong>Tokens</strong> tab.</li>
<li>In the <strong>Monitor tokens</strong> section, click <strong>Add token</strong> (plus icon).</li>
<li>Set the <strong>Validity period</strong> (e.g. today from 08:00 to 18:00).</li>
<li>Choose which map basemap to display (Standard, Satellite).</li>
<li>Select the specific vehicle(s) for this shipment.</li>
<li>Choose whether to show sensor telemetry (temperature, fuel) or only map position.</li>
<li>Click <strong>Save</strong>.</li>
<li>Copy the generated URL or iframe code and send it to the client.</li>
</ol>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Describe what a user can do with the Report Scheduler' => [
            'docs' => 'Account settings → Report scheduler',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Nobody wants to log in at 7:00 AM every Monday morning just to click "Build Report" and email a spreadsheet to the CEO. The Report Scheduler automates report generation and delivery completely.</strong></p>

<h2 id="scheduler-capabilities">Core capabilities of the Report Scheduler</h2>

<p>Located in <strong>Account settings</strong> → <strong>Report scheduler</strong>, this tool lets users automate recurring reporting tasks:</p>

<ul>
<li><strong>Flexible Schedules</strong> — run reports daily (e.g. at 06:00 covering yesterday), weekly (every Monday covering the previous week), or monthly (on the 1st covering the previous calendar month).</li>
<li><strong>Template Binding</strong> — pair any saved report template (e.g. <em>Mileage and Stops</em>, <em>Fuel Consumption Audit</em>, <em>Geofence Activity</em>) with any vehicle, group, or tag.</li>
<li><strong>Multi-Recipient Email Dispatch</strong> — automatically email finished Excel (XLSX) or PDF attachments to a list of confirmed internal and external stakeholders.</li>
<li><strong>Zero Manual Intervention</strong> — reports generate in background worker queues even if no user is signed in to PILOT.</li>
</ul>

<h2 id="setting-up-schedule">Setting up a scheduled task</h2>

<ol>
<li>Go to <strong>Account settings</strong> → <strong>Report scheduler</strong> tab.</li>
<li>Click <strong>Add task</strong>.</li>
<li>Select the report template and choose the target fleet objects.</li>
<li>Set the schedule recurrence: <em>Weekly on Monday at 07:00 AM</em>.</li>
<li>Set the reporting interval: <em>Previous week</em>.</li>
<li>Choose the export format: <em>Excel (XLSX)</em>.</li>
<li>Select confirmed recipient email addresses.</li>
<li>Click <strong>Save</strong>.</li>
</ol>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 10 — knowledge check',
        'description' => 'Four questions on modular features, Monitor tokens vs Access tokens, and the Report Scheduler.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A client wants to share live tracking of a single delivery truck with a customer for 24 hours without giving them account credentials. What is the recommended solution in PILOT?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A Monitor Token provides an expiring public tracking URL showing only selected vehicles and sensors, without exposing login credentials or other fleet data.',
                'options' => [
                    ['text' => 'Create a Monitor Token with a 24-hour expiration for that vehicle and share the public link', 'correct' => true],
                    ['text' => 'Give the customer the master administrator username and password', 'correct' => false],
                    ['text' => 'Take screenshots of the map every 5 minutes and email them manually', 'correct' => false],
                    ['text' => 'Reinstall the GPS device in the customer\'s own office', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What does an Access Token allow someone to do in PILOT?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'An Access Token is an authentication URL that logs the user into PILOT as that user profile without prompting for a password.',
                'options' => [
                    ['text' => 'Sign in to PILOT as a specific user without typing credentials, ideal for portal integrations', 'correct' => true],
                    ['text' => 'Remotely unlock vehicle doors without an ignition key', 'correct' => false],
                    ['text' => 'Bypass contract monthly subscription billing', 'correct' => false],
                    ['text' => 'Delete all historical database backups', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'If a customer cannot find the Notifications or Video menu options anywhere in their account, where must the feature be activated?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT operates on a modular architecture: optional modules must be activated on the client\'s contract in the Administrative Panel.',
                'options' => [
                    ['text' => 'In the Administrative Panel, where modules are enabled per contract', 'correct' => true],
                    ['text' => 'In the user\'s local Windows Control Panel', 'correct' => false],
                    ['text' => 'By upgrading the browser to a newer version', 'correct' => false],
                    ['text' => 'By restarting the vehicle battery', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What can a fleet manager accomplish with the Report Scheduler?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Report Scheduler automatically runs saved report templates on a recurring schedule (daily/weekly/monthly) and emails PDF/Excel attachments to recipients.',
                'options' => [
                    ['text' => 'Automate recurring report generation and deliver PDF or Excel attachments by email', 'correct' => true],
                    ['text' => 'Automatically schedule oil changes at third-party garages', 'correct' => false],
                    ['text' => 'Control vehicle speed limits based on the time of day', 'correct' => false],
                    ['text' => 'Send automated speeding tickets directly to government police servers', 'correct' => false],
                ],
            ],
        ],
    ],
];
