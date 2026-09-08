<?php

/**
 * Admin panel — Module 4: Combined Assessment.
 *
 * Content drawn from docs.pilot-gps.com.
 * This module is a capstone, combining all previous modules into a cohesive workflow.
 */

return [
    'module_subtitle' => 'Combined Assessment',

    'lessons' => [
        // 1
        'Create a prepaid contract, add an object, configure mileage by CAN' => [
            'docs' => 'Admin Panel → Account / Objects / Configuration',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>A full onboarding scenario requires assembling multiple components.</strong></p>

<h2 id="the-workflow">The workflow</h2>
<p>You will synthesize what you've learned to provision a completely new customer. This means creating their container (contract), adding their physical device (object), and setting up vehicle-specific parameters (mileage by CAN).</p>
HTML,
        ],

        // 2
        'Create a partner, assign a contract, configure SMTP' => [
            'docs' => 'Admin Panel → Partners',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Partner provisioning is a common request for large accounts.</strong></p>

<h2 id="partner-setup">Partner Setup</h2>
<p>Creating a partner involves more than just a name. You must set their currency, assign the contract to their branch in the tree, and configure their SMTP server so their customer communications are white-labeled.</p>
HTML,
        ],

        // 3
        'Activate the Video module, configure streaming in a new tab' => [
            'docs' => 'Admin Panel → Modules / Configuration',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Advanced features require specific configurations.</strong></p>

<h2 id="video-streaming">Video streaming</h2>
<p>Video telematics is an add-on module. Beyond activating it on the contract, some partners require the video broadcast to open in a new tab rather than an iframe. This is controlled via a specific contract configuration.</p>
HTML,
        ],

        // 4
        'Write user instructions: how to change the password in the personal account' => [
            'docs' => 'User Portal',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Support means translating administrative actions into user instructions.</strong></p>

<h2 id="customer-support">Customer support</h2>
<p>Customers will often ask how to do things in their personal account. You must be able to clearly communicate the steps without confusing them with admin panel terminology.</p>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Module 4 — Final Assessment',
        'description' => 'A capstone knowledge check combining concepts from the Admin Panel course. You need 75% to pass.',
        'passing_score' => 75,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'If a partner wants all notification emails to come from "alerts@theircompany.com", what needs to be configured?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Partner SMTP settings govern the outbound mail server and "From" address for all notifications under that partner.',
                'options' => [
                    ['text' => 'The partner\'s SMTP settings', 'correct' => true],
                    ['text' => 'The object\'s notification template', 'correct' => false],
                    ['text' => 'The contract\'s rebranding options', 'correct' => false],
                    ['text' => 'The user\'s personal settings', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer needs the Video module activated, but also wants the video stream to open in a new browser tab. How is this achieved?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Modules are activated in the Modules tab, and specific UI behaviors (like opening in a new tab) are set in the Configuration tab.',
                'options' => [
                    ['text' => 'Activate the module in the Modules tab, and add a setting in the Configuration tab', 'correct' => true],
                    ['text' => 'Check "Open in new tab" inside the Video module settings directly', 'correct' => false],
                    ['text' => 'This requires custom development and cannot be configured', 'correct' => false],
                    ['text' => 'Set the partner\'s rebranding theme to "Popout"', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'When a new prepaid contract is created, what determines if an object in it will generate a daily charge?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The object must have an active tariff assigned to it to generate charges.',
                'options' => [
                    ['text' => 'The tariff assigned directly to the object', 'correct' => true],
                    ['text' => 'The default tariff of the contract', 'correct' => false],
                    ['text' => 'The balance of the contract', 'correct' => false],
                    ['text' => 'Whether the Analytics module is active', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What happens to a contract\'s objects if the contract is transferred from one partner to another?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Transferring a contract moves the contract and all its contents (objects, users) to the new partner hierarchy.',
                'options' => [
                    ['text' => 'The objects move with the contract to the new partner', 'correct' => true],
                    ['text' => 'The objects remain with the original partner', 'correct' => false],
                    ['text' => 'The objects are permanently deleted', 'correct' => false],
                    ['text' => 'The objects must be individually transferred first', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'End-to-End Admin Panel Deployment',
        'lesson_title' => 'Create a prepaid contract, add an object, configure mileage by CAN',
        'brief' => 'Complete a full onboarding scenario: provision partner, create prepaid contract, transfer vehicle, configure telemetry parameters, and activate Video streaming.',
        'submission_instructions' => 'Perform the full end-to-end setup in PILOT. Submit the requested IDs and a screenshot of the fully configured contract dashboard showing the vehicle and modules live.',
        'requires_screenshot' => true,
        'estimated_minutes' => 30,
        'required_evidence' => [
            ['key' => 'partner_id', 'label' => 'Partner ID', 'hint' => 'The ID of the newly created partner'],
            ['key' => 'account_id', 'label' => 'Created Contract ID', 'hint' => 'The ID of the contract created under the partner'],
            ['key' => 'agent_id', 'label' => 'Vehicle Agent ID / IMEI', 'hint' => 'The ID of the vehicle transferred into the contract'],
        ],
    ],
];
