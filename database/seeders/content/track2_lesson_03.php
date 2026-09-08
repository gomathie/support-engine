<?php

/**
 * Admin panel — Module 3: Modules, Notifications, Security, Rebranding.
 *
 * Content drawn from docs.pilot-gps.com:
 *   · Admin Panel → Modules
 *   · Admin Panel → Notifications
 *   · Admin Panel → Security
 *   · Admin Panel → Rebranding
 */

return [
    'module_subtitle' => 'Modules, notifications, security, rebranding',

    'lessons' => [
        // 1
        'Activate the Geofences module for a contract' => [
            'docs' => 'Admin Panel → Modules',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>Modules unlock features in the user portal.</strong></p>

<h2 id="spatial-alerting">Spatial alerting</h2>
<p>Activating the Geofences module allows users in the contract to create and manage geofences, and receive alerts when objects enter or leave them. To activate it, go to the contract's module tab, check the box next to Geofences, and save.</p>
HTML,
        ],

        // 2
        'Configure an email template for low balance notifications' => [
            'docs' => 'Admin Panel → Notifications',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Notifications keep customers informed about their account status.</strong></p>

<h2 id="low-balance-templates">Low balance templates</h2>
<p>You can configure email templates with specific variables (like `%BALANCE%` or `%CONTRACT_NAME%`) to automatically notify customers when their balance drops below a set threshold.</p>
HTML,
        ],

        // 3
        'Enable 2FA for a partner (TOTP or email)' => [
            'docs' => 'Admin Panel → Security',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Two-factor authentication adds a layer of security.</strong></p>

<h2 id="authentication-security">Authentication security</h2>
<p>For a partner, you can enforce 2FA. Users under this partner will be required to set up TOTP (via an authenticator app) or email-based codes upon their next login.</p>
HTML,
        ],

        // 4
        'Configure rebranding: logo, theme, check login page' => [
            'docs' => 'Admin Panel → Rebranding',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Rebranding allows partners to white-label the PILOT platform.</strong></p>

<h2 id="white-label-styling">White-label styling</h2>
<p>Rebranding settings can be applied per partner. You can upload custom logos, change the color theme, and configure custom login pages. This is crucial for partners reselling the service under their own brand.</p>
HTML,
        ],

        // 5
        'Answer: how do you activate the Analytics module?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'Details required on the specific steps and billing implications of activating the Analytics module.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>The process for activating the Analytics module and its associated parameters must be detailed here.</p>
HTML,
        ],

        // 6
        'Answer: how do you set up TOTP login?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'Step-by-step user flow for setting up TOTP is required.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>The exact flow a user sees when setting up TOTP (QR code scanning, secret key) needs to be documented here.</p>
HTML,
        ],

        // 7
        'Answer: what settings can rebranding change?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'A comprehensive list of all customizable elements in the Rebranding tab is needed.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>Provide the full list of customizable rebranding options (logos, palette, footer, copyright, custom domain, etc.).</p>
HTML,
        ],

        // 8
        'Configure and test Notifications; add "Low Balance Emails" config' => [
            'docs' => 'Admin Panel → Notifications',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A practical exercise to ensure notifications work.</strong></p>

<h2 id="the-task">The task</h2>
<ol>
<li>Configure a low balance notification rule.</li>
<li>Add the "Low Balance Emails" configuration to specify the recipients.</li>
<li>Trigger a test notification to verify delivery.</li>
</ol>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Module 3 — knowledge check',
        'description' => 'Four questions on modules, notifications, security, and rebranding. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Which module must be activated for users to create spatial alerts?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Geofences module enables spatial boundaries and alerts when objects cross them.',
                'options' => [
                    ['text' => 'Geofences', 'correct' => true],
                    ['text' => 'Drivers', 'correct' => false],
                    ['text' => 'Analytics', 'correct' => false],
                    ['text' => 'Video', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'If a partner wants to sell the platform under their own logo and domain, which admin panel tab should you use?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Rebranding tab allows partners to white-label the platform with custom logos, colors, and domains.',
                'options' => [
                    ['text' => 'Rebranding', 'correct' => true],
                    ['text' => 'Modules', 'correct' => false],
                    ['text' => 'Configuration', 'correct' => false],
                    ['text' => 'Security', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the purpose of the %BALANCE% variable in an email template?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Variables in templates dynamically insert specific account information, like the current balance.',
                'options' => [
                    ['text' => 'To dynamically insert the current account balance into the notification', 'correct' => true],
                    ['text' => 'To automatically deduct money from the customer', 'correct' => false],
                    ['text' => 'To calculate the monthly tariff cost', 'correct' => false],
                    ['text' => 'To limit how many emails can be sent', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'When TOTP is enforced for a partner, what happens when a user under that partner logs in?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Users must use an authenticator app (like Google Authenticator) to scan a QR code or enter a secret to generate a time-based code.',
                'options' => [
                    ['text' => 'They are prompted to set up an authenticator app via a QR code', 'correct' => true],
                    ['text' => 'They receive an SMS code on their phone', 'correct' => false],
                    ['text' => 'They must change their password', 'correct' => false],
                    ['text' => 'They are forced to answer security questions', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Configure partner branding and low-balance email templates',
        'lesson_title' => 'Configure rebranding: logo, theme, check login page',
        'brief' => 'Set up custom partner branding, configure a low-balance email notification rule, and enable 2FA policy.',
        'submission_instructions' => 'Complete the rebranding, notification setup, and 2FA enforcement in the training account. Submit a screenshot showing the configured Rebranding and Notifications tabs.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Contract ID configured', 'hint' => 'The ID of the contract where notifications were set up'],
            ['key' => 'partner_id', 'label' => 'Partner ID configured', 'hint' => 'The ID of the partner where rebranding and 2FA were applied'],
        ],
    ],
];
