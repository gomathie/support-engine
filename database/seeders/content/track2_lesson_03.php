<?php

/**
 * Admin panel — Module 3: Modules, notifications, security and rebranding.
 *
 * Content drawn from docs.pilot-gps.com:
 *   · Admin Panel → Account → Modules            /modules_2.html
 *   · Admin Panel → Administrator → Modules      /modules_3.html
 *   · Admin Panel → Administrator → Templates    /templates_editor.html
 *   · Admin Panel → Administrator → Rebranding   /rebranding.html
 *   · Admin Panel → Security                     /security.html
 *   · Admin Panel → Account → Configuration      /configuration.html
 *   · Notifications                              /notifications__print.html
 *
 * Two things to know before editing this file.
 *
 * **The module is called Geozone, not Geofences.** The curriculum titles say
 * "Geofences" because that is what the training plan called it; the platform,
 * and therefore the customer's screen, says Geozone. The lesson teaches the
 * platform's word and says so, rather than quietly teaching the wrong one.
 *
 * **The click-path for activating a module is not documented.** The module
 * catalogue is, and so is who may do it — "only the system administrator can
 * manage activation via the admin panel". The steps are not, so they are not
 * invented here.
 *
 * Markup is limited to what Filament's rich editor round-trips: headings,
 * lists, blockquotes, tables and inline marks. No div, dl or span — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Modules, notifications, security, rebranding',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Activate the Geofences module for a contract' => [
            'docs' => 'Admin Panel → Account → Modules · Administrator → Modules',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Half of "the feature is missing" tickets are a module that was never switched on. This is
the lesson that stops you investigating a fault that does not exist.</strong></p>

<h2 id="the-name">First, the name</h2>

<blockquote><p>The module is called <strong>Geozone</strong> — "territorial control and zone
monitoring". "Geofence" is the industry word and the word in this course's title, but it is not what
the customer sees on their screen. Use the platform's word when you are telling somebody where to
look.</p></blockquote>

<h2 id="modular">PILOT is modular</h2>

<p>A customer gets the parts they are entitled to and no more, and modules are switched on per
contract. The catalogue is long — these are the ones that come up most on a support desk:</p>

<table>
<thead>
<tr><th>Module</th><th>What it does</th></tr>
</thead>
<tbody>
<tr><td><strong>Geozone</strong></td><td>Territorial control and zone monitoring.</td></tr>
<tr><td><strong>Notification</strong></td><td>Sending user notifications.</td></tr>
<tr><td><strong>Analytics (Panel)</strong></td><td>Collecting and analysing monitoring data.</td></tr>
<tr><td><strong>CMS and Video</strong></td><td>Monitoring driver and passenger behaviour inside vehicles.</td></tr>
<tr><td><strong>Driver Module</strong></td><td>Managing and tracking driver work.</td></tr>
<tr><td><strong>Command</strong></td><td>Sending commands to devices.</td></tr>
<tr><td><strong>Service</strong></td><td>Planning vehicle inspections.</td></tr>
<tr><td><strong>Fuel Card</strong></td><td>Fuel management.</td></tr>
<tr><td><strong>Recalculation</strong></td><td>Data transformations for zones and trips.</td></tr>
<tr><td><strong>Eco Driving</strong></td><td>Monitoring driving quality.</td></tr>
</tbody>
</table>

<p>There are more than thirty in total, including Climate Control, RFID Tag, Engine Immobilizer,
Time Control, Violation Monitoring, Logistics, Trailer, Relay, Event, Flights, Routes, Pilot Task
Manager, Schedule, Tachograph, Crash Detected, Rent Car, Garbage Collection, Agro, Equipment
Performance Monitoring and LogBook.</p>

<h2 id="who-may">Who may switch one on</h2>

<blockquote><p>Only the system administrator can manage activation via the admin panel.</p></blockquote>

<p>That is worth saying plainly to a customer: this is not something they can turn on themselves,
and it is not something every member of staff can do either.</p>

<h2 id="two-screens">Two screens called Modules</h2>

<ul>
<li><strong>Account → Modules</strong> — the modules on <em>a contract</em>. This is where a
    customer's entitlement is switched on.</li>
<li><strong>Administrator → Modules</strong> — the system's own register of modules, used
    <em>to view and change rights in the Pilot system</em>. Its columns are <strong>id</strong>,
    <strong>Name</strong>, <strong>Description</strong>, <strong>Paid</strong> (the payment status
    for the module) and <strong>Only for administrators</strong> (the status of rights to use it).</li>
</ul>

<p>Reading <strong>Paid</strong> and <strong>Only for administrators</strong> answers a question
that comes up constantly: is this module missing because it was not bought, or because it is not
meant to be in the customer's hands at all?</p>

<blockquote><p><strong>The first question on any "it is not there" call.</strong> Not "what does your
screen show" — <em>is the module active on this contract?</em> Check that before anything
else.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Activating a module',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A customer asks where to find geofencing. What is the module actually called in PILOT?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The module is Geozone — "territorial control and zone monitoring". Sending a customer to look for "Geofences" sends them to something that is not on their screen.',
                        'options' => [
                            ['text' => 'Geozone', 'correct' => true],
                            ['text' => 'Geofences', 'correct' => false],
                            ['text' => 'Territory', 'correct' => false],
                            ['text' => 'Zones', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which column on Administrator → Modules tells you whether a module is withheld from customers rather than simply unpaid?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Only for administrators is the status of rights to use the module. Paid is the payment status — a different reason for the same symptom.',
                        'options' => [
                            ['text' => 'Only for administrators', 'correct' => true],
                            ['text' => 'Paid', 'correct' => false],
                            ['text' => 'Description', 'correct' => false],
                            ['text' => 'id', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Configure an email template for low balance notifications' => [
            'docs' => 'Admin Panel → Account → Configuration · Administrator → Templates editor',
            'estimated_minutes' => 15,
            'needs_input' => 'The mechanism that sends low-balance mail is documented — the '
                .'Low Balance Emails configuration key. The Templates editor is documented only in '
                .'outline ("creating and displaying templates", a Type dropdown, an add option); '
                .'no list of template types is given and no low-balance template is named. Igor to '
                .'confirm whether a low-balance email template exists in the editor, and what it '
                .'is called.',
            'body' => <<<'HTML'
<p><strong>Two separate things have to be right before a customer is warned that their balance is
running out: somebody has to receive the mail, and the partner has to be able to send mail at
all.</strong></p>

<h2 id="who-receives">Who receives it</h2>

<p>The recipient is a configuration key on the account:</p>

<ul>
<li><strong>Low Balance Emails</strong> = the email address, or addresses, to notify.</li>
</ul>

<p>It sits in <em>Admin Panel → Account → Configuration</em> alongside the other keys. No address,
no warning — and the first anyone knows about it is the block.</p>

<h2 id="can-it-send">Can the partner send at all</h2>

<p>A recipient is useless if nothing can reach them. Low-balance mail goes out over the partner's
<strong>SMTP</strong> configuration, which is the previous module's lesson: Partners → double-click
the partner → the SMTP tab, then run the connection test. If that test fails, no configuration key
on any account will produce an email.</p>

<blockquote><p><strong>Diagnose in that order.</strong> SMTP first, recipient second. Checking the
recipient on an account whose partner cannot send mail tells you nothing.</p></blockquote>

<h2 id="the-templates-editor">The Templates editor</h2>

<p>Templates live in <em>Admin Panel → Administrator → Templates editor</em>, a tab for creating and
displaying templates. Both the text and the template type are chosen from the <strong>Type</strong>
dropdown, and new templates can be added from the same screen.</p>

<blockquote><p><strong>What is not documented, and so is not taught here.</strong> The documentation
does not list which template types exist, and does not name a low-balance template. Look in the live
panel before telling a customer one can be edited — see the note on this lesson.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Low balance notifications',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which configuration key sets who is warned about a low balance?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Low Balance Emails takes the email address or addresses to notify, and lives in Account → Configuration.',
                        'options' => [
                            ['text' => 'Low Balance Emails', 'correct' => true],
                            ['text' => 'Partner Grace Period (days)', 'correct' => false],
                            ['text' => 'SMTP Email', 'correct' => false],
                            ['text' => 'Confirm adding vehicles to notifications', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer has Low Balance Emails set correctly and still receives nothing. What do you check next?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The mail goes out over the partner\'s SMTP configuration. If the SMTP test fails, no configuration key on any account will produce an email.',
                        'options' => [
                            ['text' => 'The partner\'s SMTP configuration, using the connection test', 'correct' => true],
                            ['text' => 'Whether the objects on the account are blocked', 'correct' => false],
                            ['text' => 'The Receipt date on the last payment', 'correct' => false],
                            ['text' => 'Whether the Notification module is paid for', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Enable 2FA for a partner (TOTP or email)' => [
            'docs' => 'Admin Panel → Security · Account → Configuration',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>PILOT supports two second factors, and they are enabled in different ways. Knowing which
one an account is on decides what you tell a locked-out user.</strong></p>

<h2 id="two-methods">Two methods</h2>

<ul>
<li><strong>TOTP</strong> — a time-based one-time password, set up by scanning a QR code with an
    authenticator app.</li>
<li><strong>Email</strong> — a code sent to the user by email.</li>
</ul>

<h2 id="totp">Enabling TOTP</h2>

<p>TOTP is turned on with a configuration key, added for <strong>either a partner or an individual
account</strong>:</p>

<ul>
<li><strong>TOTP = 1</strong> — makes two-factor authentication mandatory. All users of that account
    will then be asked to add TOTP authorisation to their accounts.</li>
<li><strong>TOTP Label</strong> — the name shown against the entry in the authenticator app.</li>
</ul>

<p>Note what "enabling" means here: it is <em>enforcement</em>. Setting TOTP = 1 does not configure
anything for one person; it puts every user on that account through enrolment at their next login.</p>

<h2 id="email-2fa">Enabling email 2FA</h2>

<p>Email two-factor authentication has a prerequisite and then a key:</p>

<ol>
<li><strong>Configure SMTP in the partner settings.</strong> Without it there is no way to deliver a
    code.</li>
<li>Add the <strong>2FA_EMAIL</strong> configuration parameter.</li>
</ol>

<h2 id="user-side">What the user does</h2>

<p>For TOTP, the user's side is four steps:</p>

<ol>
<li>Download an authenticator app — Google Authenticator, Microsoft Authenticator or Authy.</li>
<li>Scan the QR code the service provides.</li>
<li>Enter the generated code to confirm.</li>
<li>Use the one-time password at each login.</li>
</ol>

<p>TOTP works offline, which is the practical advantage worth mentioning: a user with no signal and
no mail can still log in.</p>

<blockquote><p><strong>On a call.</strong> "I have a new phone and I cannot log in" means the TOTP
secret went with the old one. That is an enrolment problem, not a password problem, and no password
reset fixes it.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Two-factor authentication',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What does setting TOTP = 1 on an account do?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It makes two-factor authentication mandatory: all users of that account are then asked to add TOTP authorisation to their accounts.',
                        'options' => [
                            ['text' => 'Makes 2FA mandatory for every user on that account', 'correct' => true],
                            ['text' => 'Enrols one named user in TOTP', 'correct' => false],
                            ['text' => 'Sends every user a one-time code by email', 'correct' => false],
                            ['text' => 'Allows users to opt into TOTP if they want it', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What must be in place before email-based 2FA will work?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'SMTP must be configured in the partner settings, and the 2FA_EMAIL configuration parameter added. Without SMTP there is no way to deliver a code.',
                        'options' => [
                            ['text' => 'SMTP configured in the partner settings, plus the 2FA_EMAIL parameter', 'correct' => true],
                            ['text' => 'The TOTP Label configuration key', 'correct' => false],
                            ['text' => 'The Notification module active on the contract', 'correct' => false],
                            ['text' => 'An authenticator app installed by each user', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Configure rebranding: logo, theme, check login page' => [
            'docs' => 'Admin Panel → Administrator → Rebranding',
            'estimated_minutes' => 15,
            'needs_input' => 'The seven rebranding fields are documented (Header, Partner, URL, '
                .'Theme, Logo, iOS, Android), as are the Add and Delete actions. Nothing is '
                .'documented about the login page or a favicon, so the "check login page" half of '
                .'this topic is unwritten. Igor to confirm what rebranding changes on the login '
                .'page, if anything.',
            'body' => <<<'HTML'
<p><strong>Rebranding is how a partner's customers see the partner's product rather than PILOT. It
is seven fields, and support gets asked about all of them.</strong></p>

<h2 id="the-fields">What rebranding can change</h2>

<table>
<thead>
<tr><th>Field</th><th>What it sets</th></tr>
</thead>
<tbody>
<tr><td><strong>Header</strong></td><td>The partner name displayed in the system.</td></tr>
<tr><td><strong>Partner</strong></td><td>Designation of the main partner entity.</td></tr>
<tr><td><strong>URL</strong></td><td>An alternative link for accessing the pilot version.</td></tr>
<tr><td><strong>Theme</strong></td><td>A colour scheme, chosen from several.</td></tr>
<tr><td><strong>Logo</strong></td><td>A link to the image file used for the brand.</td></tr>
<tr><td><strong>iOS</strong></td><td>A link to the Apple App Store application.</td></tr>
<tr><td><strong>Android</strong></td><td>A link to the Google Play Market application.</td></tr>
</tbody>
</table>

<p>Rebranding configurations are managed with <strong>Add</strong> and <strong>Delete</strong>.</p>

<h2 id="links-not-uploads">Logo is a link, not an upload</h2>

<p>The Logo field takes a <strong>link to an image file</strong>. So does iOS, and so does Android.
That is the difference between a logo that is broken for the customer and one that is broken for
everybody: if the image is hosted somewhere the customer's browser cannot reach, the field is set
correctly and the logo still does not appear.</p>

<blockquote><p><strong>The question to ask.</strong> Not "did you upload the logo" but "what is the
URL, and does it open in a browser?"</p></blockquote>

<h2 id="login-page">The login page</h2>

<blockquote><p><strong>Not documented.</strong> The rebranding documentation covers the seven fields
above and nothing about the login page or a favicon. Whether the login screen picks up the theme and
logo is a question for the live panel, not for this lesson — see the note on it.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Rebranding',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A partner says their logo is not showing. The Logo field is filled in. What is the useful next question?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The Logo field takes a link to an image file. A filled-in field whose URL does not resolve looks correct in the admin panel and shows nothing to the user.',
                        'options' => [
                            ['text' => 'What is the URL, and does it open in a browser?', 'correct' => true],
                            ['text' => 'Which file format did you upload?', 'correct' => false],
                            ['text' => 'Have you cleared the Theme field?', 'correct' => false],
                            ['text' => 'Is the Header field set to the same name?', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which of these is NOT a documented rebranding field?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The documented fields are Header, Partner, URL, Theme, Logo, iOS and Android. Favicon is not among them.',
                        'options' => [
                            ['text' => 'Favicon', 'correct' => true],
                            ['text' => 'Theme', 'correct' => false],
                            ['text' => 'Header', 'correct' => false],
                            ['text' => 'Android', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: how do you activate the Analytics module?' => [
            'docs' => 'Admin Panel → Account → Modules',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>The short answer.</strong></p>

<blockquote><p>It is called <strong>Analytics (Panel)</strong> — "collecting and analysing monitoring
data" — and it is switched on per contract from <strong>Account → Modules</strong>, by a system
administrator.</p></blockquote>

<h2 id="the-three-facts">The three facts behind that</h2>

<ul>
<li><strong>The name.</strong> Analytics (Panel), as it appears in the module catalogue.</li>
<li><strong>The scope.</strong> Per contract. Activating it for one customer does nothing for
    another.</li>
<li><strong>The authority.</strong> Only the system administrator can manage activation via the
    admin panel — so if you are not one, this is a handover, not a task.</li>
</ul>

<h2 id="before-you-escalate">Before you escalate it</h2>

<p>Check <strong>Administrator → Modules</strong> for the module's <strong>Paid</strong> status and
its <strong>Only for administrators</strong> flag. A module the customer has not paid for and a
module they are not permitted to have produce the same complaint and need different answers.</p>
HTML,
            'quiz' => [
                'title' => 'Analytics, in one line',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'At what level is a module activated?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Modules are switched on per contract, from Account → Modules, and only the system administrator can manage activation via the admin panel.',
                        'options' => [
                            ['text' => 'Per contract', 'correct' => true],
                            ['text' => 'Per user', 'correct' => false],
                            ['text' => 'Per object', 'correct' => false],
                            ['text' => 'Per region', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: how do you set up TOTP login?' => [
            'docs' => 'Admin Panel → Security · Account → Configuration',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>The short answer, in two halves — because the administrator's half and the user's half
are different jobs.</strong></p>

<h2 id="administrator">The administrator</h2>

<blockquote><p>Add the <strong>TOTP = 1</strong> configuration key, for either a partner or an
individual account. Every user on that account is then asked to add TOTP authorisation. Set
<strong>TOTP Label</strong> to control the name that appears in their authenticator app.</p></blockquote>

<h2 id="the-user">The user</h2>

<ol>
<li>Download an authenticator app — Google Authenticator, Microsoft Authenticator or Authy.</li>
<li>Scan the QR code the service provides.</li>
<li>Enter the generated code to confirm.</li>
<li>Use the one-time password at login from then on.</li>
</ol>

<h2 id="say-this">Say this to the customer</h2>

<p>TOTP works <strong>offline</strong> — the code is generated on the phone, not sent to it. A user
who says "I never received the code" has probably been enrolled in TOTP and is waiting for an SMS
that is never coming. Point them at the app.</p>
HTML,
            'quiz' => [
                'title' => 'TOTP, in one line',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A user on a TOTP account says they never received their code. What is happening?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'TOTP codes are generated on the device by the authenticator app and work offline. Nothing is sent, so nothing can fail to arrive.',
                        'options' => [
                            ['text' => 'Nothing is sent — the code is generated in their authenticator app', 'correct' => true],
                            ['text' => 'The SMTP configuration on the partner has failed', 'correct' => false],
                            ['text' => 'The 2FA_EMAIL parameter is missing', 'correct' => false],
                            ['text' => 'Their TOTP Label has not been set', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: what settings can rebranding change?' => [
            'docs' => 'Admin Panel → Administrator → Rebranding',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>Seven fields, and it is worth knowing them by heart — the answer to "can you change X for
us?" is usually yes or no on this list.</strong></p>

<ul>
<li><strong>Header</strong> — the partner name displayed in the system.</li>
<li><strong>Partner</strong> — designation of the main partner entity.</li>
<li><strong>URL</strong> — an alternative link for accessing the pilot version.</li>
<li><strong>Theme</strong> — a colour scheme, chosen from several.</li>
<li><strong>Logo</strong> — a link to the brand image file.</li>
<li><strong>iOS</strong> — a link to the App Store application.</li>
<li><strong>Android</strong> — a link to the Play Market application.</li>
</ul>

<p>Configurations are added and deleted from the same screen.</p>

<blockquote><p><strong>What is not on the list is the useful part.</strong> Rebranding changes the
name, the colours, the logo and the links. It is not a redesign: layout, wording and the position of
anything on the screen are not on this list, and promising them is how a small request becomes a
complaint.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Rebranding, in one line',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A partner asks whether rebranding can move the reports menu to the top of the screen. What do you tell them?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Rebranding covers Header, Partner, URL, Theme, Logo, iOS and Android. Layout is not among them.',
                        'options' => [
                            ['text' => 'No — rebranding changes name, colours, logo and links, not layout', 'correct' => true],
                            ['text' => 'Yes, using the Theme field', 'correct' => false],
                            ['text' => 'Yes, but only for the mobile apps', 'correct' => false],
                            ['text' => 'Yes, using the Header field', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Configure and test Notifications; add "Low Balance Emails" config' => [
            'docs' => 'Notifications · Admin Panel → Account → Configuration',
            'estimated_minutes' => 25,
            'body' => <<<'HTML'
<p><strong>This module's skills in one sequence, done in the training account.</strong></p>

<h2 id="what-a-notification-is">What a notification is made of</h2>

<p>Every notification has three parts, and a notification that never fires is nearly always one of
them:</p>

<ol>
<li><strong>The conditions that trigger it.</strong></li>
<li><strong>The objects it applies to.</strong></li>
<li><strong>The delivery method</strong> — email, SMS, Telegram, in-system messages, or mobile app
    push notifications.</li>
</ol>

<p>The events available include engine start or stop (ignition on/off), speeding or exceeding speed
limits, changes in fuel level, refuellings and drainings, entering or exiting a geofence, loss of
connection with the device, and maintenance alerts.</p>

<blockquote><p><strong>New vehicles are not added automatically unless you ask.</strong> The
configuration key <em>Confirm adding vehicles to notifications</em> is what makes newly authorised
vehicles join existing notifications. Without it, a customer who adds a lorry in March keeps getting
alerts for everything except the lorry.</p></blockquote>

<h2 id="the-task">The task</h2>

<ol>
<li>Confirm the <strong>Notification</strong> module is active on the training contract. If it is
    not, nothing below will work, and noticing that is the first skill.</li>
<li>Create a notification: choose the trigger, choose the objects, choose the delivery.</li>
<li>Test it, and record what actually arrived rather than what should have.</li>
<li>Add the <strong>Low Balance Emails</strong> configuration key to the account, with an address
    you can check.</li>
</ol>

<h2 id="evidence">What to submit</h2>

<ul>
<li>the <strong>account / contract ID</strong> you worked on;</li>
<li>the <strong>Agent ID</strong> of an object the notification applies to;</li>
<li>a <strong>screenshot</strong> showing the notification and the delivery method you chose;</li>
<li>a <strong>screenshot</strong> of the configuration list showing Low Balance Emails set.</li>
</ul>

<blockquote><p><strong>Training account only.</strong> A notification with a real delivery method
sends real mail. Use the account you were given for training and an address you own.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Notifications',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What are the three things that define a notification?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The conditions that trigger it, the objects it applies to, and the delivery method.',
                        'options' => [
                            ['text' => 'The trigger conditions, the objects, and the delivery method', 'correct' => true],
                            ['text' => 'The trigger conditions, the tariff, and the recipient', 'correct' => false],
                            ['text' => 'The module, the partner, and the template', 'correct' => false],
                            ['text' => 'The objects, the geofence, and the schedule', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer says a vehicle they added last month is the only one not raising alerts. What is the likely cause?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The configuration key "Confirm adding vehicles to notifications" is what makes newly authorised vehicles join existing notifications. Without it, new vehicles are left out.',
                        'options' => [
                            ['text' => '"Confirm adding vehicles to notifications" is not set on the account', 'correct' => true],
                            ['text' => 'The vehicle has no tariff', 'correct' => false],
                            ['text' => 'The Notification module was activated after the vehicle', 'correct' => false],
                            ['text' => 'The vehicle is blocked in the Himself column', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 3 — knowledge check',
        'description' => 'Modules, notifications, security and rebranding. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What is the first thing to check when a customer reports that a feature is missing from their screen?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Modules are switched on per contract. A missing feature is far more often an inactive module than a fault.',
                'options' => [
                    ['text' => 'Whether the module is active on that contract', 'correct' => true],
                    ['text' => 'Whether their objects are online', 'correct' => false],
                    ['text' => 'Whether their balance is negative', 'correct' => false],
                    ['text' => 'Whether they are using a supported browser', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Who can activate a module on a contract?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Only the system administrator can manage activation via the admin panel.',
                'options' => [
                    ['text' => 'Only the system administrator', 'correct' => true],
                    ['text' => 'Any admin panel user', 'correct' => false],
                    ['text' => 'The partner, from their own settings', 'correct' => false],
                    ['text' => 'The account owner, from their personal account', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Setting TOTP = 1 on an account has which effect?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'It makes two-factor authentication mandatory: all users of that account are asked to add TOTP authorisation to their accounts.',
                'options' => [
                    ['text' => 'Every user on the account must enrol in TOTP', 'correct' => true],
                    ['text' => 'TOTP becomes available for users who want it', 'correct' => false],
                    ['text' => 'Codes start being emailed to users', 'correct' => false],
                    ['text' => 'The account is locked until an administrator enrols it', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which two things must both be right before a low-balance email reaches anybody?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Low Balance Emails configuration key names the recipient; the partner\'s SMTP configuration is what actually sends. Either one wrong and nothing arrives.',
                'options' => [
                    ['text' => 'The Low Balance Emails key, and the partner\'s SMTP configuration', 'correct' => true],
                    ['text' => 'The Notification module, and a geofence', 'correct' => false],
                    ['text' => 'The Partner Grace Period, and a tariff', 'correct' => false],
                    ['text' => 'The Templates editor, and the TOTP Label', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which is a documented rebranding field?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Header, Partner, URL, Theme, Logo, iOS and Android are the seven documented fields.',
                'options' => [
                    ['text' => 'Theme', 'correct' => true],
                    ['text' => 'Favicon', 'correct' => false],
                    ['text' => 'Login page background', 'correct' => false],
                    ['text' => 'Menu order', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Set up a notification and a low-balance recipient',
        'lesson_title' => 'Configure and test Notifications; add "Low Balance Emails" config',
        'brief' => 'In the training account, confirm the Notification module is active, create and test one notification, and set the Low Balance Emails configuration key.',
        'submission_instructions' => 'Do the work in the training PILOT account, using an email address you own. Submit the contract ID and the Agent ID of an object the notification covers, a screenshot of the notification showing its trigger, objects and delivery method, and a screenshot of the configuration list showing Low Balance Emails. Say what actually arrived when you tested it — including "nothing", which is a finding rather than a failure.',
        'requires_screenshot' => true,
        'estimated_minutes' => 25,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Contract / account ID you worked on', 'hint' => 'The training contract'],
            ['key' => 'agent_id', 'label' => 'Agent ID of an object the notification applies to', 'hint' => 'From the Agent ID column on the Vehicles tab'],
            ['key' => 'delivery_method', 'label' => 'Delivery method you chose', 'hint' => 'Email, SMS, Telegram, in-system message or push'],
            ['key' => 'test_result', 'label' => 'What actually arrived when you tested it', 'hint' => 'Describe what you received, or say that nothing did'],
        ],
    ],
];
