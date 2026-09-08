<?php

/**
 * Admin panel — Module 2: Objects, Partners, and Finances.
 *
 * Content drawn from docs.pilot-gps.com:
 *   · Admin Panel → Objects
 *   · Admin Panel → Partners
 *   · Admin Panel → Finances
 */

return [
    'module_subtitle' => 'Objects, Partners, and Finances',

    'lessons' => [
        // 1
        'Transfer an object into the created contract' => [
            'docs' => 'Admin Panel → Objects',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Objects represent physical tracking devices in PILOT. Moving an object into a contract gives the contract owner access to it.</strong></p>

<h2 id="object-movement">Object movement</h2>
<p>Objects are created globally but belong to specific contracts. To move an object to the contract you created:</p>
<ol>
<li>Go to the <strong>Objects</strong> tab in the admin panel.</li>
<li>Find the object using its ID or IMEI.</li>
<li>Edit the object and change its associated contract.</li>
</ol>

<blockquote><p><strong>Rights transfer:</strong> When an object moves between contracts, historical data remains, but the new contract rules (tariffs, access rights) apply immediately.</p></blockquote>
HTML,
        ],

        // 2
        'Add a tariff to the object' => [
            'docs' => 'Admin Panel → Objects',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>An object without a tariff generates no billing events.</strong></p>

<h2 id="assigning-a-tariff">Assigning a tariff</h2>
<p>Tariffs are assigned at the object level to determine daily or monthly subscription costs.</p>
<ol>
<li>Open the object settings.</li>
<li>Navigate to the <strong>Tariff</strong> section.</li>
<li>Select the appropriate tariff from the dropdown and save.</li>
</ol>
HTML,
        ],

        // 3
        'Configure blocking with a block date' => [
            'docs' => 'Admin Panel → Objects',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Blocking stops the object from reporting or being accessed by users.</strong></p>

<h2 id="grace-periods">Grace periods and automated blocking</h2>
<p>You can set a future block date or let the system block automatically if the contract balance goes negative, depending on the configuration and grace period.</p>
HTML,
        ],

        // 4
        'Add a speed-control configuration to the object' => [
            'docs' => 'Admin Panel → Configuration',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Object-specific configurations override contract-wide settings.</strong></p>

<h2 id="speed-limit">Speed limit parameter</h2>
<p>To set a speed limit parameter for an object, add the speed limit configuration key to the object's configuration tab. This value will be used for notifications and reports for this specific vehicle.</p>
HTML,
        ],

        // 5
        'Create a partner (data, currency, tariff)' => [
            'docs' => 'Admin Panel → Partners',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Partners allow you to create a multi-tier hierarchy for reselling PILOT.</strong></p>

<h2 id="partner-tree">The Partner Tree</h2>
<p>When creating a partner, you must specify their currency and default tariff. Contracts can then be assigned to this partner, allowing them to manage their own sub-contracts and billing.</p>
HTML,
        ],

        // 6
        'Configure SMTP for the partner' => [
            'docs' => 'Admin Panel → Partners',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Custom SMTP servers allow partners to send notifications from their own email domains.</strong></p>

<h2 id="outbound-mail">Outbound mail server</h2>
<p>Configure the SMTP host, port, username, and password in the partner's settings. All notifications for contracts under this partner will use these credentials.</p>
HTML,
        ],

        // 7
        'Add a payment in the Finances section' => [
            'docs' => 'Admin Panel → Finances',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>The Finances tab tracks all billing movements.</strong></p>

<h2 id="manual-credits">Manual credits and balance recalculation</h2>
<p>If a customer pays via bank transfer, you manually add the payment here to credit their balance. This recalculates their standing and can automatically unblock suspended objects.</p>
HTML,
        ],

        // 8
        'Answer: how do you transfer an object between contracts?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'Step-by-step instructions needed for the exact precautions and edge cases when transferring objects.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>While the basic steps are known, the exact precautions (such as what happens to active notifications or assigned drivers) need to be documented here.</p>
HTML,
        ],

        // 9
        'Answer: which configurations control speed limits?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'Need the exact configuration keys and thresholds used for speed control.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>The exact configuration keys for speed limits must be supplied here.</p>
HTML,
        ],

        // 10
        'Answer: how do you add a manual subscription debit?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'Need details on manual subscription debits vs automated ones.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic requires expert input.</strong></p></blockquote>
<p>Details on performing manual debits from the Finances tab are required.</p>
HTML,
        ],

        // 11
        'Create a partner, transfer a contract to them, configure "Address in Online Tree"' => [
            'docs' => 'Admin Panel → Partners',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>A practical consolidation of partner management.</strong></p>

<h2 id="the-task">The task</h2>
<ol>
<li>Create a partner.</li>
<li>Transfer an existing contract to this new partner.</li>
<li>Set the "Address in Online Tree" configuration for the partner.</li>
</ol>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Module 2 — knowledge check',
        'description' => 'Four questions on objects, partners, and finances. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What happens immediately when a payment is manually added in the Finances tab for a blocked account?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Adding a payment credits the balance. If the balance exceeds the blocking threshold, the system automatically recalculates and unblocks the objects.',
                'options' => [
                    ['text' => 'The balance recalculates and objects may automatically unblock', 'correct' => true],
                    ['text' => 'The contract owner receives an invoice', 'correct' => false],
                    ['text' => 'Objects must be manually unblocked', 'correct' => false],
                    ['text' => 'The partner gets a commission', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'If an object is transferred to a new contract, what happens to its historical data?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Object history is tied to the object, not the contract, so it remains intact after transfer.',
                'options' => [
                    ['text' => 'Historical data remains with the object', 'correct' => true],
                    ['text' => 'Historical data is deleted permanently', 'correct' => false],
                    ['text' => 'Historical data stays in the old contract', 'correct' => false],
                    ['text' => 'Historical data is archived and must be requested', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the primary purpose of configuring SMTP settings for a partner?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Custom SMTP allows partners to send automated emails (like low balance alerts) from their own domain.',
                'options' => [
                    ['text' => 'To send notifications from the partner\'s own email domain', 'correct' => true],
                    ['text' => 'To allow users to reply to support tickets', 'correct' => false],
                    ['text' => 'To encrypt communication between tracking devices and the server', 'correct' => false],
                    ['text' => 'To log in using two-factor authentication', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is required for an object to generate billing subscription costs?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'An assigned tariff defines the subscription costs. Without one, no daily/monthly charges are generated.',
                'options' => [
                    ['text' => 'An assigned tariff', 'correct' => true],
                    ['text' => 'A positive balance on the contract', 'correct' => false],
                    ['text' => 'Active GPS tracking data', 'correct' => false],
                    ['text' => 'A designated partner', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Transfer vehicle and configure object tariff and speed limit',
        'lesson_title' => 'Transfer an object into the created contract',
        'brief' => 'Transfer a test vehicle to the newly created contract, assign an active tariff, apply speed-control configuration, and record the vehicle details.',
        'submission_instructions' => 'Complete the transfer and configuration in PILOT, then provide the IDs and a screenshot of the object list showing the active tariff and configuration.',
        'requires_screenshot' => true,
        'estimated_minutes' => 15,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Vehicle Agent ID / Object ID in PILOT', 'hint' => 'The ID of the object you transferred'],
            ['key' => 'account_id', 'label' => 'Destination Contract ID', 'hint' => 'The ID of the contract the object was transferred to'],
        ],
    ],
];
