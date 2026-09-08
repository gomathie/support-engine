<?php

/**
 * Admin panel — Lesson 1: Interface familiarisation and basic operations.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Admin Panel → Panel          /panel.html
 *   · Admin Panel → Account        /account.html
 *   · Admin Panel → Configuration  /configuration.html
 *
 * Two topics are deliberately unwritten. "Stock account" and the enumeration of
 * account types do not appear in the 7.10 documentation — the Contracts window
 * has an "account type" field but the docs never list what the types are. Those
 * are marked `needs_input` rather than filled with a plausible guess.
 *
 * Markup is limited to what Filament's rich editor round-trips: headings,
 * lists, blockquotes, tables and inline marks. No div, dl or span — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Interface familiarization and basic operations',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Log into the Administrative Panel' => [
            'docs' => 'Admin Panel → Panel',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>The admin panel is where the platform is configured, as opposed to used. Contracts are
created here, modules are switched on here, and tariffs are set here — none of which is possible
from a customer's personal account.</strong></p>

<h2 id="getting-in">Getting in</h2>

<p>Enter the platform address in the browser's address bar and you are shown an authorisation form.
Credentials for the admin panel are <em>issued by the company manager</em> — they are not the same
as a user-portal login, and having one does not imply the other.</p>

<blockquote><p>This is the single most common confusion on a support call. A customer who says
"I'm an administrator" is almost always describing their <em>personal account</em>, where
administrator means full rights in the user portal and nothing at all in the admin panel.</p></blockquote>

<h2 id="what-you-see">What the panel shows you</h2>

<p>The dashboard presents, at a glance:</p>

<ul>
<li>the number of objects in the system, and their statuses;</li>
<li>the name of the organisation;</li>
<li>the balance;</li>
<li>the tariff.</li>
</ul>

<p>Those five together answer most first-line questions about an account before you have opened
anything. A blocked object with a negative balance is a billing problem, not a device problem.</p>

<h2 id="tab-bar">The tab bar</h2>

<p>The tab bar is how you move between contracts, objects, reports, recalculations and the other
administrative actions. Everything in this course is reached from it.</p>

<blockquote><p><strong>Orientation before action.</strong> Before changing anything on a customer's
account, read the dashboard: whose organisation is this, what tariff are they on, and what is the
balance? A configuration change made against the wrong contract is difficult to notice and worse to
undo.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Update personal settings (name, password, photo)' => [
            'docs' => 'Admin Panel → Panel (personal user settings)',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>Your own account first — partly to learn where the settings live, and partly because you
will be asked to walk customers through the same screens.</strong></p>

<h2 id="what-you-can-change">What you can change</h2>

<p>Personal user settings in the admin panel let you:</p>

<ul>
<li>add a photo;</li>
<li>change your name;</li>
<li>change your password.</li>
</ul>

<h2 id="why-it-is-here">Why this is the second thing you learn</h2>

<p>"How do I change my password?" is one of the most frequent questions a support desk receives, and
the answer differs between the admin panel and the personal account. Doing it once yourself, in the
panel, means you can describe the path rather than guess at it.</p>

<blockquote><p>Note the difference for later: this changes <em>your</em> password. Resetting a
customer's password is a different operation, performed against their user record, and is covered
under user management.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create a new contract, activate modules (e.g. Video, Drivers), save' => [
            'docs' => 'Admin Panel → Account · Modules',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>A contract is the container everything else lives in. Creating one is the first
administrative act for any new customer.</strong></p>

<h2 id="contract-is-account">Contract and account are the same thing here</h2>

<blockquote><p>The contract is <strong>the account that is intended for work in the user
interface</strong>.</p></blockquote>

<p>The documentation uses the two words interchangeably, which is worth knowing before you read
further: a "contract" in the admin panel is not a commercial document. It is the workspace that
holds a customer's objects, their users, and the features they are entitled to. Every contract
carries a unique contract ID.</p>

<h2 id="creating-one">Creating one</h2>

<p>Use <strong>Add contract</strong> in the Contracts window. Creating an account involves entering:</p>

<ul>
<li>personal data;</li>
<li>the name of the contract;</li>
<li>the type of organisation;</li>
<li>the type of account;</li>
<li>the tariff;</li>
<li>and other parameters.</li>
</ul>

<p>The Contracts window itself is a working list, not just a directory: it can be filtered to find
an account by given criteria, its columns can be chosen, it shows the creation date and the number
of objects, and it exports to Excel.</p>

<h2 id="modules">Activating modules</h2>

<p>PILOT is modular — a customer only gets the parts they are entitled to, and modules are switched
on per contract. Video and Drivers are activated the same way as any other.</p>

<blockquote><p><strong>Remember this on a call.</strong> "The Notifications option isn't there" is
usually not a fault. It is a module that has not been activated on that contract, and it is fixed
here rather than in the customer's account.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: what is a stock account?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'The term does not appear anywhere in the 7.10 documentation — not in '
                .'Admin Panel → Account, not in Configuration. Igor to confirm what a stock account '
                .'is, or whether the topic should be retired.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic is not written yet.</strong></p></blockquote>

<p>"Stock account" comes from the original training plan, but it does not appear in the PILOT 7.10
documentation — not under <em>Admin Panel → Account</em>, and not under <em>Configuration</em>.</p>

<p>Rather than write a plausible-sounding definition, it has been left open. A confident but wrong
explanation of an account type is worse than an obvious gap: you would carry it onto calls and act
on it.</p>

<p>Ask your trainer, and this topic will be filled in once the definition is confirmed.</p>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: what account types exist and how do they differ?' => [
            'docs' => null,
            'estimated_minutes' => 10,
            'needs_input' => 'The docs show that the Contracts window carries an "account type" '
                .'field and that a type is chosen when creating an account, but never enumerate the '
                .'types or their differences. Igor to supply the list.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic is not written yet.</strong></p></blockquote>

<p>The documentation confirms that an account type is chosen when a contract is created, and that
the Contracts window displays organisation type and account type as separate fields. It does not
say what the available types are, or how they differ in behaviour.</p>

<p>That list is exactly the sort of thing that must be right — the type decides how an account
behaves — so it has been left for a trainer to supply rather than inferred from the field's
existence.</p>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: how do you add a configuration to a contract?' => [
            'docs' => 'Admin Panel → Configuration',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Configurations are the per-contract switches that make one customer's platform behave
differently from another's.</strong></p>

<h2 id="what-a-configuration-is">What a configuration is</h2>

<blockquote><p>Configuration is <strong>the process of adjusting system parameters to achieve
optimal performance or to meet specific customer requirements and objectives</strong>.</p></blockquote>

<h2 id="adding-one">Adding one</h2>

<p>Configurations are added through contract, partner or module settings. The steps are the same
wherever you are:</p>

<ol>
<li>go to the relevant settings area — contract, partner or module;</li>
<li>enter the configuration name and its value;</li>
<li>save, which activates it.</li>
</ol>

<h2 id="what-they-control">What they control</h2>

<p>The range is wide, and knowing it is what turns "the system is behaving oddly" into a specific
question. Configurations govern, among others:</p>

<ul>
<li><strong>Vehicles and notifications</strong> — how vehicles are added to notifications and shown;</li>
<li><strong>Privacy</strong> — hiding device information, managing what data is visible;</li>
<li><strong>Billing and access</strong> — grace periods, and blocking accounts with a negative balance;</li>
<li><strong>Interface</strong> — the default tab, the address column, map visibility;</li>
<li><strong>Drivers</strong> — restricting a vehicle to a single driver;</li>
<li><strong>Communications</strong> — video broadcast in a new tab, Telegram bot access;</li>
<li><strong>Authentication</strong> — company branding on two-factor login;</li>
<li><strong>Notifications</strong> — which addresses receive low-balance alerts;</li>
<li><strong>Documentation</strong> — linking custom training material and user guides.</li>
</ul>

<blockquote><p><strong>The diagnostic value.</strong> Several of the odder-sounding complaints —
"it blocked us even though we paid", "the address column vanished", "video won't open" — are
configurations rather than faults. Check what is set on the contract before escalating
anything.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create a Prepayment contract and activate the Drivers module' => [
            'docs' => 'Admin Panel → Account · Modules',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>The two previous topics, done together and for real. This is the pattern you will repeat
for every new customer.</strong></p>

<h2 id="the-task">The task</h2>

<ol>
<li>Create a contract, choosing <strong>Prepayment</strong> as the payment arrangement.</li>
<li>Activate the <strong>Drivers</strong> module on it.</li>
<li>Save, and confirm the contract appears in the Contracts window with the module active.</li>
</ol>

<h2 id="what-to-notice">What to notice while you do it</h2>

<ul>
<li>The contract gets a unique ID. Note it — it is how you and a colleague refer to the same
account without ambiguity.</li>
<li>Prepayment interacts with the billing configurations from the previous topic: grace periods and
blocking on a negative balance behave differently depending on the arrangement.</li>
<li>Activating a module changes what the customer sees in their personal account. If they are
logged in, they may need to sign in again before it appears.</li>
</ul>

<blockquote><p><strong>Verify, do not assume.</strong> Open the contract again after saving and
confirm the module is listed as active. "I clicked save" is not evidence — the same standard applies
here as to anything you would tell a customer you had fixed.</p></blockquote>
HTML,
        ],
    ],

    /*
     * The knowledge check at the end of the lesson.
     *
     * Scenario stems drawn from what a first-line admin actually gets asked,
     * with a rationale on every option — each wrong answer is a real
     * misconception rather than filler.
     */
    'quiz' => [
        'title' => 'Lesson 1 — knowledge check',
        'description' => 'Four questions on the panel, contracts and configurations. '
            .'You need 70% to pass, and you may retake it.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A customer emails to say the Notifications option has disappeared from '
                    .'their account. Nothing was changed at their end. Where do you look first?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT is modular and modules are activated per contract. A missing '
                    .'feature is far more often an inactive module than a fault — and it is fixed in '
                    .'the admin panel, not in the customer\'s account.',
                'options' => [
                    ['text' => 'Whether the Notifications module is active on their contract', 'correct' => true],
                    ['text' => 'Whether their objects have stopped reporting', 'correct' => false],
                    ['text' => 'Whether their browser needs its cache cleared', 'correct' => false],
                    ['text' => 'Whether the notification rule was deleted by another user', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'In the admin panel, what is a contract?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The documentation is explicit: the contract is the account intended '
                    .'for work in the user interface. It is the workspace holding a customer\'s '
                    .'objects, users and entitlements — not a commercial document.',
                'options' => [
                    ['text' => 'The account a customer works in, holding their objects, users and modules', 'correct' => true],
                    ['text' => 'The signed commercial agreement, stored for reference', 'correct' => false],
                    ['text' => 'One person\'s login to the admin panel', 'correct' => false],
                    ['text' => 'The billing record that tracks payments and balance', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer insists they paid, but their account has been blocked for a '
                    .'negative balance. Before escalating to finance, what is worth checking?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Grace periods and blocking on a negative balance are configurations '
                    .'set per contract. The behaviour a customer is complaining about may be exactly '
                    .'what their contract is configured to do.',
                'options' => [
                    ['text' => 'The billing configurations on their contract — grace period and blocking rules', 'correct' => true],
                    ['text' => 'Whether their objects are still reporting', 'correct' => false],
                    ['text' => 'Whether the Drivers module is active', 'correct' => false],
                    ['text' => 'Whether their tariff includes video', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A caller says: "I am an administrator, so I should be able to create a '
                    .'contract." They are signed in and cannot find the option. What is most likely?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Administrator in the user portal grants full rights *there* and '
                    .'nothing in the admin panel, where contracts are created. Admin panel '
                    .'credentials are issued separately by the company manager.',
                'options' => [
                    ['text' => 'They are an administrator of their personal account, not of the admin panel', 'correct' => true],
                    ['text' => 'Their administrator rights were revoked without their knowledge', 'correct' => false],
                    ['text' => 'Contract creation requires the Drivers module', 'correct' => false],
                    ['text' => 'Their contract type does not permit creating further contracts', 'correct' => false],
                ],
            ],
        ],
    ],
];
