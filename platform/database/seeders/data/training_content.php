<?php

/*
| The curriculum, carried over verbatim from the prototype's DATA const in
| pages/training-tracker/script.js.
|
| The prototype's shape maps straight onto the new hierarchy:
|
|   section  ->  Course        (flag becomes the category)
|   day      ->  CourseModule  (n = title, t = subtitle, topics = description)
|   item     ->  Lesson        (completion_requirement = acknowledge)
|
| Content, not code: this file is the seed for a first install. Once it is in
| the database, trainers edit it in the admin panel and this file is not
| consulted again.
*/

return [
    [
        'title' => 'Before delivery — information to gather',
        'category' => 'PREP',
        'summary' => 'The company-specific answers that block Modules C and D until they exist.',
        'description' => 'Most of these need answers from team leads rather than from the trainer. '
            .'The Skills Module proposes an industry-standard default for each; confirm them, and '
            .'escalate anything unresolved to your head of technical support.',
        'difficulty' => 'beginner',
        'is_required' => true,
        'estimated_minutes' => 90,
        'modules' => [
            [
                'title' => 'Blocking',
                'subtitle' => 'Needed before Module C or D can be taught',
                'description' => 'These are the company-specific gaps. Ordered by how much they block.',
                'lessons' => [
                    'Ticketing system: name, required fields, priority definitions, response targets',
                    'Team contact map — names, channels, ownership boundaries — including who owns layer 7 / relay tickets',
                    'Escalation triggers and the out-of-hours path',
                    'Does 1st line retain customer contact after escalation?',
                    'Approval path for admin actions (new contracts, module activation, tariff changes)',
                    'Incident procedure — who gets alerted when several customers report the same issue',
                    'Where known issues / solutions are documented',
                    'Investigation time limit before escalation',
                ],
            ],
            [
                'title' => 'Data protection',
                'subtitle' => 'Needed before Module D5 can be taught',
                'description' => 'Which regime applies depends on where our customers operate — '
                    .'Ghana (Act 843), EU/UK (GDPR), Gulf states. This needs a real answer, not a placeholder.',
                'lessons' => [
                    'Which data-protection regimes apply to our contracts; controller vs processor in each',
                    'Is admin-panel access logged and reviewed? (Trainees must be told plainly either way)',
                    'Where does a data-subject request from a driver get routed?',
                    'Policy on ad-hoc surveillance-shaped requests ("where was driver X on Saturday?")',
                    'Retention policy, and who authorises deletion of an object',
                    'Our answer on private/personal mode for out-of-hours vehicle use',
                ],
            ],
            [
                'title' => 'Materials',
                'subtitle' => 'Practical setup',
                'description' => 'Without these, the highest-value exercises cannot run at all.',
                'lessons' => [
                    '10–12 anonymized past tickets (routing + queue-sorting exercises)',
                    'Breakable test environment with admin-panel visibility',
                    'Test user with restricted rights (for the Master-trap drill)',
                    'Trainer briefed on our actual data-protection policy before the D5 role-plays',
                ],
            ],
        ],
    ],

    [
        'title' => '1st-line support — 2 week plan',
        'category' => 'TRACK 1',
        'summary' => 'The core PILOT platform: objects, sensors, rights, reports and notifications.',
        'description' => 'Two weeks from first login to handling live contacts, ending in a final '
            .'assessment and an open Q&A.',
        'difficulty' => 'beginner',
        'is_required' => true,
        'estimated_minutes' => 2400,
        'due_days' => 21,
        'modules' => [
            [
                'title' => 'Day 1',
                'subtitle' => 'Introduction to PILOT and basic concepts',
                'description' => 'Object, Sensor, Contract, Account, Alert, Geofence; roles (User / Admin / Super Admin).',
                'lessons' => [
                    'Define: Object, Sensor, Contract, Account',
                    'Explain Personal Account vs Admin Panel',
                    'Explain what a Mapping Contract is and its use case',
                ],
            ],
            [
                'title' => 'Day 2',
                'subtitle' => 'Interface and navigation',
                'description' => 'Top panel, workspace, map, map tools.',
                'lessons' => [
                    'Log in to the system',
                    'Describe each element of the top panel',
                    'Change map type to Yandex Sat',
                    'Measure distance between two points on the map',
                ],
            ],
            [
                'title' => 'Day 3',
                'subtitle' => 'User and rights management',
                'description' => 'Creating users, assigning rights to objects/labels, rights templates.',
                'lessons' => [
                    'Create a new user with role "User"',
                    'Assign rights to 2 test objects',
                    'Create a rights template for the role',
                    'Block, then unblock, the created user',
                ],
            ],
            [
                'title' => 'Day 4',
                'subtitle' => 'Working with objects (part 1)',
                'description' => 'Object card, mandatory fields, device IMEI.',
                'lessons' => [
                    'Manually create a new object (car) with General + Info filled in',
                    'Assign a tag and place it in a group',
                    'Find "Current Track" and "Follow Object" via right-click menu',
                ],
            ],
            [
                'title' => 'Day 5',
                'subtitle' => 'Working with objects (part 2) and object list',
                'description' => 'Filtering, sorting, columns, groups, color indicators.',
                'lessons' => [
                    'Configure color status indicators',
                    'Create group "Test Vehicles" and move objects into it',
                    'Configure columns: Name, Speed, Status, Driver',
                    'Filter list to objects "in motion"',
                ],
            ],
            [
                'title' => 'Day 6',
                'subtitle' => 'Sensors (part 1)',
                'description' => 'Sensor types by purpose and by principle of operation.',
                'lessons' => [
                    'Add an ignition sensor (two-position); verify via Points tab',
                    'Add a fuel level sensor (discrete) and fill main parameters',
                ],
            ],
            [
                'title' => 'Day 7',
                'subtitle' => 'Sensors (part 2)',
                'description' => 'Calibration tables, formulas, sensor templates.',
                'lessons' => [
                    'Set up a 3–4 point calibration table for the fuel sensor',
                    'Apply formula /1000 to the battery voltage sensor',
                    'Save sensor config as a template and apply it to another object',
                ],
            ],
            [
                'title' => 'Day 8',
                'subtitle' => 'History and reports',
                'description' => 'Track/player/events/graph, report types and parameters.',
                'lessons' => [
                    'Build 24h movement history for an object; review with the player',
                    'Create a "Mileage and Stops" report for yesterday',
                    'Save the report in PDF and Excel formats',
                ],
            ],
            [
                'title' => 'Day 9',
                'subtitle' => 'Contract settings and notifications',
                'description' => 'Password/email settings, 2FA, notification types.',
                'lessons' => [
                    'Add a test email in contract settings and send confirmation',
                    'Activate "Speed Limit Exceeded" email notifications',
                    'Enable and configure 2FA for the test user (if available)',
                ],
            ],
            [
                'title' => 'Day 10',
                'subtitle' => 'Additional modules and tools',
                'description' => 'Modular architecture, report scheduler, tokens.',
                'lessons' => [
                    'Describe the Notifications module and its requirements',
                    "Explain what a Token is and when it's used",
                    'Describe what a user can do with the Report Scheduler',
                ],
            ],
            [
                'title' => 'Day 11–12',
                'subtitle' => 'Comprehensive review and call simulation',
                'description' => 'Applying knowledge to realistic support scenarios.',
                'lessons' => [
                    "Scenario: user can't log in — walk through diagnostic steps",
                    'Scenario: object missing from list — walk through diagnostic steps',
                    'Scenario: speed notifications not received — walk through diagnostic steps',
                    'Scenario: give partner temporary access without an account — explain token solution',
                ],
            ],
            [
                'title' => 'Day 13–14',
                'subtitle' => 'Final testing and consultation',
                'description' => 'Assessment and open Q&A.',
                'lessons' => [
                    'Attend error review / Q&A session',
                    'Resolve one non-standard case using documentation',
                ],
            ],
        ],
    ],

    [
        'title' => 'Admin panel — 3 day plan',
        'category' => 'TRACK 2',
        'summary' => 'Contracts, objects, partners, finances, modules and rebranding.',
        'description' => 'Three days in the Administrative Panel, ending in a combined end-to-end assessment.',
        'difficulty' => 'intermediate',
        'is_required' => false,
        'estimated_minutes' => 1440,
        'modules' => [
            [
                'title' => 'Day 1',
                'subtitle' => 'Interface familiarization and basic operations',
                'description' => 'Panel structure, personal settings, contracts.',
                'lessons' => [
                    'Log into the Administrative Panel',
                    'Update personal settings (name, password, photo)',
                    'Create a new contract, activate modules (e.g. Video, Drivers), save',
                    'Answer: what is a stock account?',
                    'Answer: what account types exist and how do they differ?',
                    'Answer: how do you add a configuration to a contract?',
                    'Create a Prepayment contract and activate the Drivers module',
                ],
            ],
            [
                'title' => 'Day 2',
                'subtitle' => 'Objects, partners, and finances',
                'description' => 'Object transfer/configuration, partner setup, payments.',
                'lessons' => [
                    'Transfer an object into the created contract',
                    'Add a tariff to the object',
                    'Configure blocking with a block date',
                    'Add a speed-control configuration to the object',
                    'Create a partner (data, currency, tariff)',
                    'Configure SMTP for the partner',
                    'Add a payment in the Finances section',
                    'Answer: how do you transfer an object between contracts?',
                    'Answer: which configurations control speed limits?',
                    'Answer: how do you add a manual subscription debit?',
                    'Create a partner, transfer a contract to them, configure "Address in Online Tree"',
                ],
            ],
            [
                'title' => 'Day 3',
                'subtitle' => 'Modules, notifications, security, rebranding',
                'description' => 'Geofences, email templates, 2FA, white-labeling.',
                'lessons' => [
                    'Activate the Geofences module for a contract',
                    'Configure an email template for low balance notifications',
                    'Enable 2FA for a partner (TOTP or email)',
                    'Configure rebranding: logo, theme, check login page',
                    'Answer: how do you activate the Analytics module?',
                    'Answer: how do you set up TOTP login?',
                    'Answer: what settings can rebranding change?',
                    'Configure and test Notifications; add "Low Balance Emails" config',
                ],
            ],
            [
                'title' => 'Final test',
                'subtitle' => 'Combined assessment',
                'description' => 'End-to-end tasks covering all three days.',
                'lessons' => [
                    'Create a prepaid contract, add an object, configure mileage by CAN',
                    'Create a partner, assign a contract, configure SMTP',
                    'Activate the Video module, configure streaming in a new tab',
                    'Write user instructions: how to change the password in the personal account',
                ],
            ],
        ],
    ],

    [
        'title' => 'Support skills — communication, troubleshooting, escalation, standards',
        'category' => 'TRACK 3',
        'summary' => 'The four skill modules: how to ask, how to diagnose, how to hand over, how to classify.',
        'description' => 'Method rather than product knowledge. Module C is largely company-specific '
            .'and depends on the prep course being filled in first.',
        'difficulty' => 'intermediate',
        'is_required' => true,
        'estimated_minutes' => 1200,
        'due_days' => 30,
        'modules' => [
            [
                'title' => 'Module A',
                'subtitle' => 'Communication with customers',
                'description' => 'Acknowledge → Diagnose → Act → Close. The question ladder, difficult conversations, ticket notes.',
                'lessons' => [
                    'Question drill: 5 questions before proposing a cause (×5 symptoms)',
                    'Rewrite 5 manual sentences for a non-technical fleet manager',
                    'Role-play: angry customer, vehicle dark all weekend — full cycle',
                    'Role-play: deliver a "no" plus the alternative',
                    'Write up role-play 3 to the 5-point ticket standard',
                ],
            ],
            [
                'title' => 'Module B',
                'subtitle' => 'Troubleshooting methodology',
                'description' => 'Three instruments (Sensors tracing, Audit history, Admin reports). '
                    .'The 7-layer model. Master-status trap. Recalculate.',
                'lessons' => [
                    'Instrument drill: Sensors tracing — last reception, satellites, sensor value, under a minute',
                    "Instrument drill: find an object's last settings change in Audit history",
                    'Layer sorting: 15 symptoms → layer + first check',
                    'Broken sandbox: diagnose 6 planted faults cold ⭐ highest-value exercise',
                    'The Master trap: explain a rights issue you cannot see from your own login',
                    'Notification hunt: find the weekday-only time window',
                    "Recalculate drill: fix calibration, then make yesterday's report correct",
                    'Write your own diagnostic tree and defend the order of checks',
                ],
            ],
            [
                'title' => 'Module C',
                'subtitle' => 'Support workflows and escalation',
                'description' => 'Ticket lifecycle, team map, escalation triggers, special cases. '
                    .'Mostly company-specific — needs filling first.',
                'lessons' => [
                    'Route the ticket: 10 real past requests → owning team + justification',
                    'Write an escalation handover; 2nd line grades whether they could start cold',
                    'Shadowing: listen to live calls',
                    'Reverse shadowing: handle live contacts with a senior listening in',
                ],
            ],
            [
                'title' => 'Module D',
                'subtitle' => 'Industry standard practice',
                'description' => 'ITIL ticket types and priority matrix, KCS knowledge capture, '
                    .'support metrics, driver data as personal data.',
                'lessons' => [
                    'Sort the queue: 12 tickets → Incident / Service request / Problem + priority, impact and urgency justified separately',
                    'Find the problem: spot the recurring symptom in a month of tickets',
                    "Write a KCS article titled in the customer's words — then find it by searching as a customer would",
                    'Physical clock drill: 6 faults → "what happens in the real world while this stays broken?"',
                    'Role-play: fleet manager wants a weekend location report on one named driver',
                    'Role-play: a driver calls asking what data is held on him and demands deletion',
                ],
            ],
        ],
    ],
];
