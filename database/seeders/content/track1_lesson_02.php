<?php

/**
 * Lesson 2 — Interface and navigation.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Top panel         /top_panel.html
 *   · Map tools         /map_tools.html
 *   · Map               /map.html
 *   · Interface overview /interface_overview_1.html
 */

return [
    'lesson_subtitle' => 'Interface and navigation',

    'topics' => [

        // ─────────────────────────────────────────────────────────
        'Log in to the system' => [
            'docs' => 'Interface overview',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Before you can help anyone, you need to know how to get in yourself — and what
to look for the moment the screen loads.</strong></p>

<h2 id="login">Logging in</h2>

<p>Open the PILOT platform URL in your browser. You will see the login screen.
Enter your <strong>username</strong> and <strong>password</strong>, then click
<strong>Sign In</strong>.</p>

<blockquote><p><strong>Support tip:</strong> if a customer says they cannot log in, the first
question is always "What exactly do you see?" — a blank page, a credentials error, and a
blocked-account message all point to different causes.</p></blockquote>

<h2 id="first-screen">What you see after login</h2>

<p>After login, the system loads the <strong>Online</strong> section — the main
workspace. It is divided into three areas:</p>

<ul>
<li><strong>Top panel</strong> — runs across the top of the screen. Contains the
main navigation menu, object status indicators, your username, the notifications
icon, the account configuration gear icon, the AI Assistant, and the additional
settings menu (theme, language, news).</li>
<li><strong>Left sidebar</strong> (section menu) — changes depending on which main
section you select. In the Online section, it shows tabs like Objects, Groups and
other context-specific tools.</li>
<li><strong>Map area</strong> — the largest part of the screen, showing all
monitored objects on a live map.</li>
</ul>

<blockquote><p><strong>On a call:</strong> knowing the three-area layout lets you guide someone
without seeing their screen: "Look at the top bar… now click the left sidebar tab…"</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: System Login & Workspace Layout',
                'description' => 'Verify your understanding of system login and the three primary screen areas.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which section of the PILOT platform loads automatically as the main workspace immediately after successful login?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'docs.pilot-gps.com specifies that upon logging in, the system immediately loads the Online section as the primary monitoring workspace.',
                        'options' => [
                            ['text' => 'The Online section', 'correct' => true],
                            ['text' => 'The Reports archive', 'correct' => false],
                            ['text' => 'The Admin Billing panel', 'correct' => false],
                            ['text' => 'The Sensor Calibration editor', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What are the three main structural areas of the PILOT workspace?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The PILOT workspace is divided into the Top panel (navigation/statuses), Left sidebar (section menu and tabs), and the central Map area.',
                        'options' => [
                            ['text' => 'Top panel, Left sidebar (section menu), and Map area', 'correct' => true],
                            ['text' => 'Header, Footer, and Terminal window', 'correct' => false],
                            ['text' => 'Admin panel, Billing ledger, and Driver chat', 'correct' => false],
                            ['text' => 'Sensor table, Geofence list, and Video stream', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Describe each element of the top panel' => [
            'docs' => 'Top panel',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>The top panel is the horizontal bar that never changes, regardless of which
section you are in. Learn its elements and you can guide a customer to any part of the system.</strong></p>

<h2 id="nav-menu">Navigation menu</h2>

<p>The navigation menu has two levels:</p>

<ul>
<li><strong>Main menu</strong> — Located on the top bar. Contains the system's main sections: <strong>Online</strong>,
<strong>Reports</strong>, <strong>History</strong>, and others. The set of sections depends on
the modules enabled for the account. You can reorder sections by dragging them — place
frequently used sections first for quick access.</li>

<li><strong>Section menu</strong> (left sidebar) — Displayed on the left side and changes depending on the main section selected. Contains
tabs for working with detailed information and tools. It has two view modes:
<em>Full view</em> (icons + names) and <em>Compact view</em> (icons only). Click the
arrow at the bottom of the menu to switch between modes.</li>
</ul>

<h2 id="statuses">Object statuses on the top panel</h2>

<p>The top panel shows the number of objects and their current statuses:</p>

<ul>
<li><strong>Total</strong> — total number of objects in the account</li>
<li><strong>Moving</strong> (green) — objects currently in motion</li>
<li><strong>Parked</strong> (blue) — objects that are stationary</li>
<li><strong>Idling</strong> (orange) — objects idling in a zone</li>
<li><strong>Inactive</strong> (grey) — objects not sending data</li>
</ul>

<p>Clicking a status icon automatically <strong>filters the object list</strong> to show
only objects in that status. Clicking the inactive status opens a window listing all
inactive objects with their inactivity duration — you can export this list to Excel.</p>

<h2 id="username-balance">Username and balance</h2>

<p>Your username is displayed on the top panel. Next to it, you'll see the
<strong>account balance</strong> — the funds available in the account.</p>

<h2 id="notifications">Notifications icon</h2>

<p>PILOT can send notifications about events: speeding, idling, refueling, entering or
exiting a geofence, and more. Click the bell icon to view received notifications.
Requirements: the Notifications module must be enabled in the contract, and notification
parameters must be configured in advance.</p>

<h2 id="account-settings">Account configuration settings</h2>

<p>Click the gear icon to open <strong>Account settings</strong>, where you can configure:</p>

<ul>
<li>Privacy and data access</li>
<li>Interface appearance and units of measurement</li>
<li>Display of addresses and events on the map</li>
<li>Access to reports</li>
<li>Object tags</li>
<li>User account management</li>
<li>Scheduled report distribution</li>
<li>Access tokens</li>
</ul>

<h2 id="ai-assistant">AI Assistant</h2>

<p>The AI bot is a smart helper that acts as a technical consultant. Click its icon on the
top panel to open a chat. It can help with how to use platform features, where to find
information, and how to solve technical tasks. It is available in all languages.</p>

<blockquote><p>As with any AI-based system, the bot may sometimes make mistakes. Always
double-check important details or contact Support for clarification.</p></blockquote>

<h2 id="additional-menu">Additional settings menu</h2>

<p>Located on the right side of the top panel (three-dot icon). Contains:</p>

<ul>
<li><strong>Interface theme</strong> — light or dark mode</li>
<li><strong>Language</strong> — select your preferred interface language</li>
<li><strong>News center</strong> — platform and company updates</li>
<li><strong>Feedback button</strong> — report issues or suggest improvements</li>
</ul>

<blockquote><p><strong>On a call:</strong> "Can you see a row of colored numbers near the top?
Those are your object statuses. What numbers do you see?" — this quickly reveals whether
objects are present and active, or if something is wrong.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Top Panel Elements & Status Counters',
                'description' => 'Test your knowledge of the top panel counters, navigation menu, and configuration icons.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What happens when a user clicks on one of the colored object status numbers (e.g. green for Moving) on the top panel?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Clicking a status icon in the top panel automatically filters the object list to display only the vehicles matching that specific status.',
                        'options' => [
                            ['text' => 'It automatically filters the object list to show only vehicles in that status', 'correct' => true],
                            ['text' => 'It immediately reboots the tracking devices', 'correct' => false],
                            ['text' => 'It generates a PDF mileage invoice', 'correct' => false],
                            ['text' => 'It deletes all inactive objects from the contract', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which top panel element allows an operator to change interface language, date/time formatting, and personal password?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The gear icon opens the Configuration window where personal info, language, password, and date/time regional preferences are maintained.',
                        'options' => [
                            ['text' => 'The Gear icon (Configuration)', 'correct' => true],
                            ['text' => 'The AI Assistant button', 'correct' => false],
                            ['text' => 'The Map Selector tool', 'correct' => false],
                            ['text' => 'The Geocoder search box', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Change map type to Yandex Sat' => [
            'docs' => 'Map tools',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>PILOT offers several map types — called basemaps. Different types suit different
needs: navigation, terrain exploration, or finding infrastructure.</strong></p>

<h2 id="change-map">How to change the map type</h2>

<ol>
<li>Click the <strong>Map selector</strong> button in the map tools panel (bottom-right
corner of the screen).</li>
<li>In the menu that opens, select the desired map type.</li>
<li>The selected map becomes your default and loads automatically when you open the system.</li>
</ol>

<p>Available map types include street maps, satellite views (including Yandex Sat), and
hybrid views that overlay street names on satellite imagery.</p>

<blockquote><p><strong>On a call:</strong> "Which map are you using?" matters when a customer
says objects appear in the wrong place. Satellite view makes it easier to verify whether a
vehicle is parked inside a yard or on the road next to it.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Map Layers & Satellite Basemaps',
                'description' => 'Verify your understanding of selecting basemaps and default view persistence.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Where is the Map Selector button located in the PILOT workspace?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The Map Selector is situated inside the map tools control dock in the bottom-right corner of the map viewport.',
                        'options' => [
                            ['text' => 'In the Map Tools dock in the bottom-right corner of the map', 'correct' => true],
                            ['text' => 'Inside the browser address bar', 'correct' => false],
                            ['text' => 'Under the user password setting in Admin Panel', 'correct' => false],
                            ['text' => 'In the Report Scheduler queue', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'When an operator switches the map basemap to Yandex Sat, when does this preference apply?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'docs.pilot-gps.com notes that the chosen basemap becomes the user\'s persistent default and loads automatically on subsequent logins.',
                        'options' => [
                            ['text' => 'It becomes the default basemap and persists across subsequent logins', 'correct' => true],
                            ['text' => 'It resets back to default OpenStreetMap immediately upon page refresh', 'correct' => false],
                            ['text' => 'It only stays active for 15 minutes', 'correct' => false],
                            ['text' => 'It applies globally to every other user in the company', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Measure distance between two points on the map' => [
            'docs' => 'Map tools',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>The map tools panel sits in the bottom-right corner and groups all tools
for working with the map in one place.</strong></p>

<h2 id="zoom">Zoom</h2>

<p>You can change the map scale in several ways:</p>
<ul>
<li><strong>Zoom buttons</strong> (+/−) in the top-left corner of the map</li>
<li><strong>Mouse scroll wheel</strong> — scroll forward to zoom in, backward to zoom out</li>
<li><strong>Double-click</strong> — double-click anywhere on the map to zoom in</li>
</ul>

<p>Current coordinates are always visible in the bottom-right corner and update as you
move the cursor.</p>

<h2 id="measure-distance">Measure distance between points</h2>

<ol>
<li>Open the map tools panel and select <strong>Measure distance</strong>.</li>
<li>Click on the map to set the first point.</li>
<li>Click again to set the second point — a line appears showing the distance.</li>
<li>To measure multiple segments, keep clicking to add points. The length of each
segment is shown next to it.</li>
</ol>

<h2 id="measure-road">Measure distance along roads</h2>

<p>This tool calculates <em>road distance</em> rather than straight-line distance:</p>
<ol>
<li>Click the first point on the map.</li>
<li>Mark intermediate waypoints along the route.</li>
<li>Finish with a final click at the endpoint.</li>
<li>The system calculates the path along existing roads and displays total distance
and estimated travel time.</li>
</ol>

<h2 id="nearest-vehicle">Distance to the nearest vehicle</h2>

<p>Click any point on the map to find the nearest vehicle. The system displays the vehicle
name, the distance from the selected point, and the estimated time for the vehicle to
reach that point.</p>

<h2 id="geocoder">Geocoder</h2>

<p>Find a point on the map by address:</p>
<ol>
<li>Enter an address or coordinates in the geocoder field.</li>
<li>Select the refined address from the dropdown suggestions.</li>
<li>The map centers on the desired point.</li>
</ol>

<h2 id="other-tools">Other map tools</h2>

<ul>
<li><strong>Measure area</strong> — click vertices on the map to draw a shape; the system
calculates its area automatically.</li>
<li><strong>Print map</strong> — prints the current map view in Portrait, Landscape, or
Auto orientation.</li>
</ul>

<blockquote><p><strong>On a call:</strong> "Measure distance along roads" is useful when a
customer asks "how far is my truck from the delivery point?" — it gives a real driving
distance, not a straight-line estimate.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Map Tools & Distance Measurement',
                'description' => 'Test your proficiency using distance measurement, road routing, and proximity calculation tools.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'How does the "Measure distance along roads" tool differ from the standard straight-line "Measure distance" tool?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Measure distance along roads snaps waypoints to actual road networks and provides real driving distance with estimated travel time rather than direct line-of-sight distance.',
                        'options' => [
                            ['text' => 'It routes along real road networks and computes estimated travel time', 'correct' => true],
                            ['text' => 'It requires GPS hardware to be uninstalled', 'correct' => false],
                            ['text' => 'It only works inside geofences', 'correct' => false],
                            ['text' => 'It measures fuel consumption instead of kilometers', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which tool allows a dispatcher to click a delivery warehouse and identify which vehicle is closest with its estimated arrival time?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The "Distance to the nearest vehicle" tool calculates the closest monitored asset to a clicked map point along with estimated transit time.',
                        'options' => [
                            ['text' => 'Distance to the nearest vehicle', 'correct' => true],
                            ['text' => 'Geocoder search', 'correct' => false],
                            ['text' => 'Print map tool', 'correct' => false],
                            ['text' => 'Object status counter', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 2 — knowledge check',
        'description' => 'Four questions on the interface layout and map tools. '
            .'You need 70% to pass, and you may retake it.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A customer says "I can see my trucks on the map but I cannot find '
                    .'the Reports section." Where should you direct them?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The main menu on the top bar contains the system\'s main '
                    .'sections: Online, Reports, History, etc. If Reports is not visible, it '
                    .'may not be enabled for their account\'s modules.',
                'options' => [
                    ['text' => 'The main menu on the top bar — click "Reports" in the navigation', 'correct' => true],
                    ['text' => 'The left sidebar — Reports is always the second tab', 'correct' => false],
                    ['text' => 'The additional settings menu (three-dot icon) on the right', 'correct' => false],
                    ['text' => 'The map tools panel in the bottom-right corner', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What do the colored numbers on the top panel represent?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The top panel shows object statuses: total objects, moving, '
                    .'parked, idling, and inactive. Clicking a status filters the object list.',
                'options' => [
                    ['text' => 'The number of objects in each status: total, moving, parked, idling, inactive', 'correct' => true],
                    ['text' => 'The number of active sensors on all objects', 'correct' => false],
                    ['text' => 'The number of unread notifications by category', 'correct' => false],
                    ['text' => 'The number of users currently logged in, grouped by role', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer wants to know the actual driving distance from their '
                    .'warehouse to a parked vehicle. Which map tool should they use?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => '"Measure distance along roads" calculates the road distance '
                    .'and estimated travel time, unlike the straight-line ruler which gives '
                    .'an as-the-crow-flies number.',
                'options' => [
                    ['text' => 'Measure distance along roads', 'correct' => true],
                    ['text' => 'Measure distance between points (straight-line ruler)', 'correct' => false],
                    ['text' => 'Geocoder', 'correct' => false],
                    ['text' => 'Measure area', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'How does a user switch the map to satellite view?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Map selector button in the map tools panel lets you choose '
                    .'between street, satellite, and hybrid map types. The selection becomes '
                    .'the default and persists across logins.',
                'options' => [
                    ['text' => 'Click the Map selector button in the map tools panel and choose a satellite basemap', 'correct' => true],
                    ['text' => 'Open Account settings and change the map type under Appearance', 'correct' => false],
                    ['text' => 'Right-click anywhere on the map and select "Switch to satellite"', 'correct' => false],
                    ['text' => 'Satellite view is only available in the admin panel', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Navigate the PILOT interface',
        'lesson_title' => 'Measure distance between two points on the map',
        'brief' => 'Log in to the training PILOT account, explore the top panel, change the '
            .'map type, and measure a distance between two points on the map.',
        'submission_instructions' => 'Complete all steps below in the training account, then '
            .'submit a screenshot showing the distance measurement result on the map.',
        'requires_screenshot' => true,
        'estimated_minutes' => 15,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Account ID (from top panel)', 'hint' => 'Your username shown on the top panel'],
        ],
    ],
];
