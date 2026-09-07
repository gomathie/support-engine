<?php

/**
 * Lesson 11 — Comprehensive review and call simulation.
 *
 * Diagnostic workflows and support call decision trees for 1st-line support:
 *   · User authentication & login failures
 *   · Missing objects & permission boundaries
 *   · Alert dispatch & email delivery troubleshooting
 *   · Temporary partner access & token deployment
 */

return [
    'lesson_subtitle' => 'Comprehensive review and call simulation',

    'topics' => [

        // ─────────────────────────────────────────────────────────
        "Scenario: user can't log in — walk through diagnostic steps" => [
            'docs' => 'Account settings → Staff and groups · Privacy → IP filtering · 2FA',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A customer calls: <em>"Help, I've got an urgent dispatch and none of my operators can log in to PILOT!"</em> Don't panic or guess. Follow the systematic 5-step diagnostic protocol.</strong></p>

<h2 id="step-1-exact-screen">Step 1: Clarify the exact error on screen</h2>

<p>Ask the caller what they see. Different visual symptoms indicate different root causes:</p>

<ul>
<li><strong>"Invalid username or password"</strong> → Credential mismatch. Verify username spelling (case-sensitive) and trigger a password reset via the edit icon in the user card.</li>
<li><strong>"Access denied" / "User is blocked"</strong> → Check the <strong>Status</strong> column in <em>Staff and groups</em>. If the icon is red, an administrator blocked the user. Click to restore green active status.</li>
<li><strong>"Sign in from this IP is not allowed"</strong> → The user has <strong>IP filtering</strong> enabled in <em>Privacy</em> settings, but their public IP changed (e.g. working from home or mobile hotspot). An administrator must update or disable IP filtering for that user profile.</li>
<li><strong>"Invalid 2FA code"</strong> → Time desynchronization on the user’s phone or typing the code after the 30-second window expired. Advise them to wait for the next fresh code cycle.</li>
<li><strong>Blank white screen</strong> → Browser cache issue or browser extension interference. Instruct them to test in an Incognito window or use the <em>Clean local cache</em> tool.</li>
</ul>

<blockquote><p><strong>Support principle:</strong> Never reset a password before confirming the user’s identity. Ensure you are speaking with an authorized contract contact before modifying credentials.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Login Failure Diagnostics',
                'description' => 'Test your ability to diagnose authentication failures, IP restrictions, and blocked accounts.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A user gets the error "Sign in from this IP is not allowed". What is the root cause and immediate remedy?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'IP filtering restricts sign-in to specific static IP addresses. If a user connects from a new network or home WiFi, an administrator must update or disable the IP filter on their user profile.',
                        'options' => [
                            ['text' => 'The user has IP filtering enabled in Privacy settings, but their current public IP changed; an administrator must update the allowed IP list', 'correct' => true],
                            ['text' => 'The vehicle tracker was disconnected from the vehicle battery', 'correct' => false],
                            ['text' => 'The user must restart their computer three times to reset the network adapter', 'correct' => false],
                            ['text' => 'The contract monthly subscription was suspended', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'If a user enters correct credentials but sees "User is blocked", how can an administrator restore their access?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'User accounts can be blocked or unblocked directly by administrators in the Staff and groups table by toggling the Status icon.',
                        'options' => [
                            ['text' => 'In Staff and groups, locate the user card and click the red Status indicator to toggle it back to green active status', 'correct' => true],
                            ['text' => 'By creating a new vehicle object and assigning it to the user', 'correct' => false],
                            ['text' => 'By submitting a support ticket to the server hosting provider', 'correct' => false],
                            ['text' => 'By deleting the contract and creating a new one', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Scenario: object missing from list — walk through diagnostic steps' => [
            'docs' => 'User account interface → Top panel · Workspace · Staff and groups → Vehicles',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A customer calls: <em>"One of our trucks has vanished from the system!"</em> A database record almost never disappears on its own. Walk through the four checks in order.</strong></p>

<h2 id="four-checks">The 4-stage diagnostic checklist</h2>

<ol>
<li><strong>Check active Top Panel filters:</strong>
  Ask the user: <em>"Look at the numbers on the top panel. Is one of the colored buttons highlighted?"</em> If they clicked <em>Moving</em>, all stationary vehicles are hidden. Tell them to click <strong>Total</strong> to clear the filter.
</li>
<li><strong>Check the search and group filter:</strong>
  Look at the search input above the object list. Is there leftover text typed in? Are they inside a collapsed group folder that is closed?
</li>
<li><strong>Check the user's vehicle permissions:</strong>
  If other users can see the truck but this specific user cannot, open <strong>Staff and groups</strong> → User Card → <strong>Vehicles</strong> tab. Verify whether the checkbox next to the vehicle is checked. A newly created user starts with zero visible objects.
</li>
<li><strong>Check contract boundaries:</strong>
  If the caller operates multiple contracts, confirm which contract they are signed into. The vehicle may be registered in another contract workspace.
</li>
</ol>
HTML,
            'quiz' => [
                'title' => 'Quiz: Missing Object Diagnostics',
                'description' => 'Verify your step-by-step diagnostic reasoning when objects disappear from a user interface.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A dispatcher complains that several trucks disappeared from their object list, but another user can still see them. What is the very first check?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Clicking a top panel status button filters the list to show only vehicles matching that status. Clicking "Total" clears the filter and restores all vehicles to the list.',
                        'options' => [
                            ['text' => 'Click the "Total" objects counter on the top panel to clear active status filters', 'correct' => true],
                            ['text' => 'Reinstall Windows on the dispatcher workstation', 'correct' => false],
                            ['text' => 'Assume the vehicles have all been stolen simultaneously', 'correct' => false],
                            ['text' => 'Delete the contract and create a brand new one', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'If a user still cannot see a specific vehicle after clearing all filters and search text, what permission check is required?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Each user profile has granular vehicle visibility. If a vehicle\'s checkbox is unchecked on the Vehicles tab of the user card, the vehicle is hidden from that user.',
                        'options' => [
                            ['text' => 'Check the user\'s card in Staff and groups → Vehicles tab to ensure the checkbox for that vehicle is ticked', 'correct' => true],
                            ['text' => 'Check if the user has an active Monitor Token', 'correct' => false],
                            ['text' => 'Check if the vehicle has fuel calibration tables configured', 'correct' => false],
                            ['text' => 'Check if the vehicle license plate contains numbers', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Scenario: speed notifications not received — walk through diagnostic steps' => [
            'docs' => 'Account settings → Privacy → Notification settings · Email setup',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A safety manager calls: <em>"Our drivers are speeding on the highway and I am not receiving email notifications!"</em> Investigate the alert pipeline from device telemetry to mailbox.</strong></p>

<h2 id="notification-troubleshooting">The 4-point notification pipeline</h2>

<ol>
<li><strong>Is the email address confirmed?</strong>
  Open <strong>Account settings</strong> → <strong>Privacy</strong> → <em>User email addresses</em>. Look at the badge next to the manager's email. If it is <strong>Red</strong>, PILOT will not dispatch automated alerts. Click <em>Send confirmation email</em> and have them click the link.
</li>
<li><strong>Is the notification rule enabled with Email delivery checked?</strong>
  In <strong>Notification settings</strong>, confirm the checkbox next to <em>Speed limit exceeded</em> is ticked, and verify that the manager's specific confirmed email is selected under the delivery channels.
</li>
<li><strong>Is the hardware reporting speed accurately?</strong>
  Open the vehicle in <strong>History</strong>. Did the vehicle actually exceed the speed limit threshold during that timeframe? If the GPS device lost antenna lock or was offline, no speed violations were recorded by the server.
</li>
<li><strong>Spam and mail filter rules:</strong>
  If PILOT shows the notification was triggered, ask the recipient to check their spam or junk folder. Corporate firewall rules may need to whitelist notifications from the PILOT email domain.
</li>
</ol>
HTML,
            'quiz' => [
                'title' => 'Quiz: Speed Notification Troubleshooting',
                'description' => 'Diagnose notification delivery failures from telemetry triggers to mailbox receipt.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'An administrator set up an automated over-speed alert, but no emails arrive. In Account Settings → Privacy, the recipient email shows a red badge. What is the cause?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'PILOT will never send automated alerts or scheduled reports to unconfirmed email addresses (indicated by a red badge). The user must click the confirmation link sent to that email address.',
                        'options' => [
                            ['text' => 'The email address has not been confirmed via the verification link sent to that inbox', 'correct' => true],
                            ['text' => 'The email address domain is blocked by government regulations', 'correct' => false],
                            ['text' => 'The recipient email must end in @pilot-gps.com', 'correct' => false],
                            ['text' => 'The email address has received too many notifications and was deleted', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'If the email address is confirmed green, but over-speed emails still do not arrive, what is the next diagnostic step?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'If notifications don\'t trigger, verify whether telemetry packets recorded an actual speed violation during that period, and confirm whether corporate mail filters routed the alert to junk/spam.',
                        'options' => [
                            ['text' => 'Check the vehicle\'s telemetry history to verify if the GPS tracker actually reported speeds exceeding the threshold, and check spam filters', 'correct' => true],
                            ['text' => 'Immediately replace the vehicle engine', 'correct' => false],
                            ['text' => 'Delete all user accounts in the contract', 'correct' => false],
                            ['text' => 'Change the server time zone to UTC-12', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Scenario: give partner temporary access without an account — explain token solution' => [
            'docs' => 'Account settings → Tokens → Monitor token',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A fleet client calls: <em>"We are moving high-value cargo for a VIP client today. They want live tracking access on their phones, but our management will not allow us to share our PILOT account login. What can we do?"</em></strong></p>

<h2 id="the-token-solution">The professional solution: A Monitor Token</h2>

<p>Do not create a temporary user account. A user account grants access to the full portal, requires credential management, and risks unauthorized configuration changes.</p>

<p>Explain the <strong>Monitor Token</strong> workflow to the customer:</p>

<ol>
<li>Open <strong>Account settings</strong> → <strong>Tokens</strong> tab.</li>
<li>Under <strong>Monitor tokens</strong>, click <strong>Add token</strong>.</li>
<li>Set the <strong>Validity period</strong> to match the delivery schedule (e.g. today from 08:00 to 20:00). After 20:00, the link automatically expires.</li>
<li>Select <strong>only the specific vehicle</strong> carrying the client's shipment. All other fleet vehicles remain completely invisible.</li>
<li>Optionally enable the temperature sensor if cold-chain integrity matters.</li>
<li>Click <strong>Save</strong> and copy the generated URL.</li>
<li>The client opens the link in any mobile or desktop browser to watch the delivery move live on the map.</li>
</ol>

<blockquote><p><strong>Outcome:</strong> The VIP client gets real-time visibility without software installation or logins, while the fleet's sensitive company data and credentials remain 100% secure.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Temporary Access & Token Deployment',
                'description' => 'Demonstrate best practices for granting temporary, secure client visibility without credentials.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Why is generating a Monitor Token preferred over creating a temporary user account for a 1-day subcontractor?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Monitor tokens provide secure, scoped, time-limited map links without exposing system logins or other client assets.',
                        'options' => [
                            ['text' => 'A Monitor Token expires automatically, limits visibility strictly to selected assets, and never exposes credentials or sensitive fleet data', 'correct' => true],
                            ['text' => 'A Monitor Token is required by telematics law in all countries', 'correct' => false],
                            ['text' => 'A temporary user account can only be created by calling PILOT telephone support', 'correct' => false],
                            ['text' => 'A Monitor Token increases vehicle engine horsepower during the trip', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What happens when a Monitor Token\'s validity period expires?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Once the expiration timestamp passes, the token becomes invalid and the public tracking link no longer displays live map data.',
                        'options' => [
                            ['text' => 'The public tracking link stops showing map telemetry and access is terminated automatically', 'correct' => true],
                            ['text' => 'The GPS tracking hardware inside the vehicle powers off', 'correct' => false],
                            ['text' => 'The administrator is charged a penalty fee', 'correct' => false],
                            ['text' => 'The entire PILOT contract is locked for 24 hours', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 11 — knowledge check',
        'description' => 'Four scenario-based diagnostic questions testing real-world support reasoning.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'An operator calls saying "I entered my password correctly, but the system says Sign in from this IP is not allowed." What is the root cause and remedy?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The user has IP filtering enabled in Privacy settings, but their current public network IP does not match the allowed list. An administrator must update or disable the filter.',
                'options' => [
                    ['text' => 'The user has IP filtering enabled in Privacy settings; an administrator must update the allowed IP list', 'correct' => true],
                    ['text' => 'The user\'s keyboard language is set incorrectly', 'correct' => false],
                    ['text' => 'The tracking device in their car was disconnected', 'correct' => false],
                    ['text' => 'The user must buy a new computer with a static hardware address', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A fleet manager calls in a panic because 30 vehicles have disappeared from their screen, but another dispatcher in the same room can see all 30. What should you instruct the caller to do first?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The caller almost certainly clicked a status filter on the top panel (e.g. Moving or Parked) or typed into the search filter. Clicking "Total" resets the view.',
                'options' => [
                    ['text' => 'Click the "Total" objects counter on the top panel to clear active status filters', 'correct' => true],
                    ['text' => 'Reinstall Windows on the dispatcher workstation', 'correct' => false],
                    ['text' => 'Tell them the 30 vehicles have likely been stolen simultaneously', 'correct' => false],
                    ['text' => 'Delete the contract and create a brand new one', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A user configured an automated speed alert to their email, but has received zero alerts despite drivers exceeding the limit. In Account Settings, the email has a red badge. What is the fix?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A red badge indicates the email address has not been confirmed. The user must click "Send confirmation email" and follow the link in their inbox.',
                'options' => [
                    ['text' => 'Click "Send confirmation email" in Privacy settings and have the user verify the email link', 'correct' => true],
                    ['text' => 'Lower the vehicle speed limit threshold to 0 km/h', 'correct' => false],
                    ['text' => 'Change the vehicle device model to a sensor template', 'correct' => false],
                    ['text' => 'Disable two-factor authentication on the contract', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A logistics company needs to grant a client live tracking of a single shipment for 12 hours without providing account passwords. Which tool should you recommend?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'A Monitor Token provides a time-expiring public URL showing only selected vehicles on a live map without exposing login credentials.',
                'options' => [
                    ['text' => 'Create a 12-hour Monitor Token in the Tokens tab for that specific vehicle and share the public link', 'correct' => true],
                    ['text' => 'Create an administrator account with access to all fleet data', 'correct' => false],
                    ['text' => 'Share the account owner\'s password over WhatsApp', 'correct' => false],
                    ['text' => 'Export a PDF report every 10 minutes and email it manually', 'correct' => false],
                ],
            ],
        ],
    ],
];
