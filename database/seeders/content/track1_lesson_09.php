<?php

/**
 * Lesson 9 — Contract settings and notifications.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Account settings                 /account_settings_1.html
 *   · Privacy (Email setup)            /privacy.html#Email_setup
 *   · Privacy (Notification settings)  /privacy.html#Notification_settings
 *   · Privacy (Two-factor auth)        /privacy.html#Two-factor_authentication_
 *   · Privacy (IP filtering)           /privacy.html#IP-based_protection_(IP_filtering)
 */

return [
    'module_subtitle' => 'Contract settings and notifications',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Add a test email in contract settings and send confirmation' => [
            'docs' => 'Account settings → Privacy → Email setup',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Email delivery is the primary channel for scheduled operational reports, security alerts, and system notifications. In PILOT, an email address cannot receive automated data until it has been explicitly confirmed.</strong></p>

<h2 id="why-confirmation">Why email confirmation is enforced</h2>

<p>To prevent spam penalties, protect customer privacy, and ensure notifications reach genuine inboxes, PILOT enforces a verification handshake. Unconfirmed email addresses remain inactive in the notification dispatcher.</p>

<h2 id="email-statuses">Email status badges</h2>

<p>In the <strong>User email addresses</strong> list, status badges indicate delivery readiness:</p>

<ul>
<li><strong style="color:#16a34a;">Green check badge</strong> — <strong>Confirmed</strong>. The mailbox owner clicked the verification link. Notifications and scheduled reports are actively sent.</li>
<li><strong style="color:#dc2626;">Red badge</strong> — <strong>Not confirmed</strong>. The address is on file, but automated dispatches are withheld.</li>
</ul>

<h2 id="step-by-step-email">Adding and confirming an email address</h2>

<ol>
<li>Click the <strong>Account settings</strong> gear icon on the top panel.</li>
<li>Switch to the <strong>Privacy</strong> section.</li>
<li>Find the <strong>User email addresses</strong> block and click the <strong>Add email</strong> button (plus icon).</li>
<li>Enter a valid email address you have access to.</li>
<li>Click <strong>Save</strong>. The address appears with a red unconfirmed badge.</li>
<li>Click the <strong>Send confirmation email</strong> icon (envelope with arrow) next to the address.</li>
<li>Open your email inbox, find the PILOT verification message, and click the confirmation link.</li>
<li>Return to PILOT and click the <strong>Refresh email list</strong> icon. The badge turns green.</li>
</ol>

<blockquote><p><strong>First-line diagnostic rule:</strong> When a customer reports "I set up automated daily reports but nobody is receiving them!", the very first check is Account Settings → Privacy. In 90% of cases, the email address was entered but never confirmed.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Email Setup & Address Confirmation',
                'description' => 'Test your understanding of email verification rules and status badge indicators.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What happens in PILOT if an administrator adds an email address but the mailbox owner never clicks the confirmation link?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Unconfirmed email addresses remain inactive with a red badge, and PILOT withholds all automated dispatches and scheduled reports.',
                        'options' => [
                            ['text' => 'The email remains unconfirmed and PILOT withholds all notifications and scheduled reports', 'correct' => true],
                            ['text' => 'PILOT automatically sends the emails as SMS messages instead', 'correct' => false],
                            ['text' => 'The user account is locked and deleted within 24 hours', 'correct' => false],
                            ['text' => 'All tracking hardware stops transmitting coordinates', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What does a green check badge next to an email address in Account Settings indicate?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A green check badge signifies that the email address is confirmed and active for automated reporting and alerts.',
                        'options' => [
                            ['text' => 'The email address is confirmed and actively eligible for automated dispatches', 'correct' => true],
                            ['text' => 'The email has zero spam filters applied', 'correct' => false],
                            ['text' => 'The email address is hosted on Microsoft Exchange', 'correct' => false],
                            ['text' => 'The user has super administrator privileges', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Activate "Speed Limit Exceeded" email notifications' => [
            'docs' => 'Account settings → Privacy → Notification settings',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Fleet safety and fuel efficiency depend on rapid enforcement of driving policies. The notifications engine monitors incoming GPS speed in real time and sends instant alerts when thresholds are violated.</strong></p>

<h2 id="how-notifications-work">The notification processing chain</h2>

<p>PILOT evaluates telemetry events against active alert rules. When an event fires (e.g. speed exceeds 100 km/h for 10 seconds), the engine dispatches notifications across selected delivery channels:</p>

<ul>
<li><strong>Email</strong> — detailed summary containing vehicle callsign, driver name, location address, peak speed, and time.</li>
<li><strong>Browser Pop-up</strong> — immediate audio and visual banner in the active PILOT web tab.</li>
<li><strong>SMS / Mobile Push</strong> — operational dispatch for on-duty field supervisors.</li>
</ul>

<h2 id="activating-speed-alerts">Activating Speed Limit notifications</h2>

<ol>
<li>Open <strong>Account settings</strong> → <strong>Privacy</strong> tab.</li>
<li>Scroll to the <strong>Notification settings</strong> section.</li>
<li>Locate the <strong>Speed limit exceeded</strong> notification row.</li>
<li>Check the box to enable the rule.</li>
<li>Under delivery methods, check <strong>Email</strong> and select your confirmed email address.</li>
<li>Optionally check <strong>Web notification</strong> for live on-screen pop-ups.</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<p>When any vehicle in the account exceeds the configured speed threshold, an instant alert is generated and dispatched within seconds.</p>
HTML,
            'quiz' => [
                'title' => 'Quiz: Speed Alerts & Dispatch Channels',
                'description' => 'Verify your understanding of alert rule activation, delivery channels, and notification contents.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which delivery channels can PILOT utilize to dispatch real-time speed violation alerts?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'PILOT supports email, live browser web pop-ups, and SMS/push notifications.',
                        'options' => [
                            ['text' => 'Email, browser web pop-up banners, and SMS / mobile push', 'correct' => true],
                            ['text' => 'Only printed letters sent by postal mail', 'correct' => false],
                            ['text' => 'FM radio broadcasts', 'correct' => false],
                            ['text' => 'Direct vehicle horn activation only', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What critical details are included in an automated speed limit violation alert?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The notification provides vehicle name, driver, location address, recorded peak speed, and timestamp.',
                        'options' => [
                            ['text' => 'Vehicle callsign, driver name, location address, peak speed, and timestamp', 'correct' => true],
                            ['text' => 'Only the driver\'s home address', 'correct' => false],
                            ['text' => 'A copy of the annual insurance policy', 'correct' => false],
                            ['text' => 'A photo of the company CEO', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Enable and configure 2FA for the test user (if available)' => [
            'docs' => 'Account settings → Privacy → Two-factor authentication',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Telematics platforms hold sensitive fleet location data and remote engine immobilization controls. Two-Factor Authentication (2FA) adds a critical security layer against credential theft.</strong></p>

<h2 id="supported-2fa-methods">Two 2FA methods in PILOT</h2>

<ul>
<li><strong>TOTP Authentication (Authenticator App)</strong> — Industry-standard Time-based One-Time Password using mobile apps like Google Authenticator, Microsoft Authenticator, or Twilio Authy. Generates a 6-digit code refreshing every 30 seconds. Works offline without cellular SMS reception.</li>

<li><strong>Email Authentication</strong> — Sends a temporary 6-digit one-time code to the user’s confirmed email address upon login. Useful for users who do not carry smartphone authenticator apps.</li>
</ul>

<h2 id="enabling-totp">Step-by-step: Enabling TOTP 2FA</h2>

<ol>
<li>Go to <strong>Account settings</strong> → <strong>Privacy</strong>.</li>
<li>In the <strong>Two-Factor Authentication</strong> section, click <strong>Enable</strong> next to TOTP.</li>
<li>PILOT displays a unique QR code on screen.</li>
<li>Open Google Authenticator or Microsoft Authenticator on your mobile phone and tap <em>Scan QR Code</em>.</li>
<li>Scan the screen; your app generates a rotating 6-digit code.</li>
<li>Type the current 6-digit code into the verification box in PILOT.</li>
<li>Click <strong>Confirm</strong>. TOTP is now active.</li>
<li>On next login, after typing username and password, PILOT prompts for the authenticator code.</li>
</ol>

<blockquote><p><strong>Code expiration note:</strong> The 6-digit TOTP code expires every 30 seconds. If an end user reports "My 2FA code is rejected", advise them to wait for the next 30-second cycle and enter the fresh code immediately.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Two-Factor Authentication Security',
                'description' => 'Test your proficiency configuring TOTP authenticators and troubleshooting login verification.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which two methods of Two-Factor Authentication (2FA) does PILOT support for operator accounts?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'PILOT supports TOTP (authenticator applications like Google or Microsoft Authenticator) and Email one-time verification codes.',
                        'options' => [
                            ['text' => 'TOTP authenticator app and Email verification code', 'correct' => true],
                            ['text' => 'Facial recognition camera hardware only', 'correct' => false],
                            ['text' => 'Physical smart cards only', 'correct' => false],
                            ['text' => 'Landline automated phone calls only', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'How frequently does a standard TOTP authenticator application rotate its 6-digit login token?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Standard TOTP codes expire and refresh every 30 seconds.',
                        'options' => [
                            ['text' => 'Every 30 seconds', 'correct' => true],
                            ['text' => 'Every 24 hours', 'correct' => false],
                            ['text' => 'Every 5 minutes', 'correct' => false],
                            ['text' => 'Only once a month', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 9 — knowledge check',
        'description' => 'Four questions on email verification, alert channels, speed notifications, and 2FA security.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A customer added an email address for scheduled reports, but reports are not arriving. The email has a red badge next to it. What is the problem?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT requires email addresses to be confirmed via verification link before automated messages or reports will be dispatched.',
                'options' => [
                    ['text' => 'The email address has not been confirmed via the verification link sent to the inbox', 'correct' => true],
                    ['text' => 'Red badges indicate that the recipient inbox is full', 'correct' => false],
                    ['text' => 'The email address domain must end in .pilot-gps.com', 'correct' => false],
                    ['text' => 'Reports can only be delivered via physical postal mail', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which delivery channels can be selected for Speed Limit Exceeded notifications in PILOT?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT allows notifications to be delivered via Email (to confirmed addresses), Web pop-up banners, and SMS/mobile channels.',
                'options' => [
                    ['text' => 'Email, browser web pop-ups, and SMS/mobile', 'correct' => true],
                    ['text' => 'Only fax machine transmission', 'correct' => false],
                    ['text' => 'Vehicle horn honking', 'correct' => false],
                    ['text' => 'Automated phone calls from the PILOT CEO', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'How often does a standard TOTP 6-digit code refresh in an authenticator app?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'TOTP standard codes refresh every 30 seconds. If a code changes mid-entry, entering the new code resolves validation errors.',
                'options' => [
                    ['text' => 'Every 30 seconds', 'correct' => true],
                    ['text' => 'Once every 24 hours', 'correct' => false],
                    ['text' => 'Every 5 minutes', 'correct' => false],
                    ['text' => 'Only when the user reboots their phone', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Where in the PILOT interface can a user enable IP filtering and 2-factor authentication?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Security settings — including Password changes, IP filtering, 2FA, and notification preferences — are located in Account Settings → Privacy.',
                'options' => [
                    ['text' => 'In Account settings under the Privacy tab', 'correct' => true],
                    ['text' => 'In the Map Tools layer selector', 'correct' => false],
                    ['text' => 'On the History track playback toolbar', 'correct' => false],
                    ['text' => 'In the driver profile photo gallery', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Configure email confirmation, speed notifications, and security',
        'lesson_title' => 'Add a test email in contract settings and send confirmation',
        'brief' => 'Open Account Settings → Privacy. Add an email address, verify confirmation, '
            .'activate Speed Limit Exceeded notifications, and review 2FA configuration.',
        'submission_instructions' => '1. Open Account settings and navigate to the Privacy tab.\n'
            .'2. Add your email address and click Send confirmation email.\n'
            .'3. Scroll down to Notification settings and enable Speed limit exceeded notifications with Email delivery.\n'
            .'4. Review the Two-Factor Authentication options (TOTP and Email).\n'
            .'5. Take a screenshot showing your confirmed email and active notification settings in the Privacy tab.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Your Account ID', 'hint' => 'The login you used to access PILOT'],
            ['key' => 'verified_email', 'label' => 'Configured Email', 'hint' => 'The email address added in Privacy settings'],
        ],
    ],
];
