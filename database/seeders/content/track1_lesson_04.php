<?php

/**
 * Lesson 4 — Working with objects (part 1).
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · How to add an object             /how_to_add_an_object_1.html
 *   · Object card                      /object_card.html
 *   · Main object settings             /main_object_settings.html
 *   · Info                             /info.html
 *   · Object menu                      /object_menu.html
 */

return [
    'lesson_subtitle' => 'Working with objects (part 1)',

    'topics' => [

        // ─────────────────────────────────────────────────────────
        'Manually create a new object (car) with General + Info filled in' => [
            'docs' => 'Objects → Adding an object · Object card → Main object settings · Info',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>An object in PILOT represents any vehicle, piece of machinery, stationary asset, or person being monitored. Knowing how to register an object and configure its hardware identifier is foundational support work.</strong></p>

<h2 id="required-fields">Mandatory fields when creating an object</h2>

<p>To add an object, click the <strong>Add object</strong> button located above the object list in the Online section. The <strong>Object Card</strong> will open with the <strong>Main</strong> tab displayed. Three fields are required before PILOT will allow you to save:</p>

<ol>
<li><strong>Vehicle number or object name</strong> — the human-readable identifier shown on maps, in lists, and across reports (e.g. <em>Ford Transit - KAA 123B</em> or <em>Generator #4</em>).</li>
<li><strong>Device ID (IMEI)</strong> — the hardware's unique international mobile equipment identity or serial number, usually printed on the physical GPS tracker label. This is what pairs incoming network telemetry with this database record.</li>
<li><strong>Device type</strong> — the tracker manufacturer and model (e.g. <em>Teltonika FMB120</em>, <em>Ruptela Pro4</em>, <em>Navtelecom SMART</em>). PILOT uses this to parse binary protocol packets correctly.</li>
</ol>

<h2 id="object-type-and-icon">Object type classification</h2>

<p>In the <strong>Object type</strong> dropdown, select the category that matches the asset: passenger car, light commercial, heavy truck, special equipment, trailer, bus, or boat. Selecting the right type selects appropriate default map icons and sets realistic maximum speed thresholds.</p>

<h2 id="info-tab">Completing vehicle metadata (Info tab)</h2>

<p>Once basic hardware connectivity is established, switch to the <strong>Info</strong> tab to record static asset details:</p>

<ul>
<li><strong>VIN (Vehicle Identification Number)</strong> — 17-character chassis number for maintenance tracking.</li>
<li><strong>License plate</strong> — registration number for automated toll or dispatch verification.</li>
<li><strong>Make and Model</strong> — manufacturer details (e.g. <em>Toyota Hilux 2.8D</em>).</li>
<li><strong>Year of manufacture</strong> and <strong>Color</strong> — assists dispatchers with visual identification.</li>
</ul>

<p>Click <strong>Save</strong> to commit the record. The vehicle appears immediately in the Online workspace tree.</p>

<blockquote><p><strong>Support tip on IMEIs:</strong> If a customer enters a device IMEI with spaces or hyphens, the tracker will fail to connect. PILOT expects the raw 15-digit numeric IMEI without punctuation. Always verify the IMEI formatting when a freshly created object stays offline.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Adding Objects & Mandatory Fields',
                'description' => 'Verify your understanding of mandatory object settings, IMEI requirements, and metadata.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which three fields are strictly required by PILOT before a newly created object can be saved?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'docs.pilot-gps.com specifies that Vehicle number/name, Device ID (unique IMEI), and Device type/model are mandatory.',
                        'options' => [
                            ['text' => 'Vehicle number/name, Device ID (IMEI), and Device type', 'correct' => true],
                            ['text' => 'License plate, driver name, and fuel capacity', 'correct' => false],
                            ['text' => 'SIM card phone number, APN, and IP address', 'correct' => false],
                            ['text' => 'Chassis number (VIN), color, and manufacture year', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why does PILOT require the device IMEI to be entered as a raw 15-digit numeric sequence without spaces or dashes?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The tracker hardware transmits its numeric IMEI in network packet headers. PILOT matches this exact string to link telemetry to the object record.',
                        'options' => [
                            ['text' => 'PILOT parses incoming telemetry by matching the exact raw numeric IMEI in packet headers', 'correct' => true],
                            ['text' => 'Dashes cause the browser to log the user out', 'correct' => false],
                            ['text' => 'Cellular providers reject SMS alerts if punctuation is present', 'correct' => false],
                            ['text' => 'It converts the object into a stationary sensor', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Assign a tag and place it in a group' => [
            'docs' => 'Objects → Object card → Settings · Object tags',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Fleets with dozens or hundreds of vehicles quickly become unmanageable as a flat list. Tags and groups provide two complementary axes for organizing assets.</strong></p>

<h2 id="groups-vs-tags">Groups vs Tags: What is the difference?</h2>

<ul>
<li><strong>Object Groups</strong> (Structural) — A hierarchical tree structure in the left sidebar. An object belongs primarily to one logical group (e.g. <em>Nairobi Branch</em>, <em>Western Region</em>, or <em>Subcontractors</em>). Groups make it easy for dispatchers to collapse sections of the fleet they are not responsible for.</li>

<li><strong>Object Tags</strong> (Cross-cutting) — Labels applied across groups. An object in the <em>Nairobi Branch</em> group can carry tags like <em>Refrigerated</em>, <em>VIP Client</em>, and <em>Diesel</em>. Rights can be assigned by tag, allowing a maintenance contractor to see all vehicles tagged <em>Refrigerated</em> across every branch.</li>
</ul>

<h2 id="assigning-tags-and-groups">Assigning tags to an object</h2>

<ol>
<li>Double-click the object to open its <strong>Object Card</strong>.</li>
<li>Go to the <strong>Settings</strong> tab.</li>
<li>Under <strong>Tags</strong>, click to select from existing tags or type a new tag name.</li>
<li>Under <strong>Group</strong>, select the target folder from the dropdown menu (e.g. <em>Test Vehicles</em>).</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<p>The object now reflects its new group in the Online tree and inherits any access policies configured for its assigned tags.</p>
HTML,
            'quiz' => [
                'title' => 'Quiz: Object Groups & Tags',
                'description' => 'Test your knowledge of organizational hierarchies and tag-based permissions.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'How do Object Groups differ from Object Tags in PILOT?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Groups provide a hierarchical folder structure in the object tree, while tags are cross-cutting labels that can span across groups to govern permissions and filtering.',
                        'options' => [
                            ['text' => 'Groups provide structural folder trees; tags provide cross-cutting labels for filtering and rights', 'correct' => true],
                            ['text' => 'Groups are only for boats, whereas tags are for trucks', 'correct' => false],
                            ['text' => 'Tags require monthly subscription fees while groups are free', 'correct' => false],
                            ['text' => 'Groups disable GPS tracking', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'In which Object Card tab are Groups and Tags configured?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Both Object Group assignment and Tags selection are configured in the Settings tab of the Object Card.',
                        'options' => [
                            ['text' => 'The Settings tab', 'correct' => true],
                            ['text' => 'The Info tab', 'correct' => false],
                            ['text' => 'The Main tab', 'correct' => false],
                            ['text' => 'The Points tab', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Find "Current Track" and "Follow Object" via right-click menu' => [
            'docs' => 'Objects → Object menu → Current track · Follow object',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>Right-clicking any object in the Online list opens the context menu — the fastest way to inspect live activity without leaving the main monitoring screen.</strong></p>

<h2 id="current-track">Current Track</h2>

<p>Selecting <strong>Current Track</strong> from the right-click menu renders the object’s recent movement history directly on the active map without needing to open the full Reports or History sections.</p>

<ul>
<li><strong>Route polyline</strong> — shows the path driven during the current shift or recent hours.</li>
<li><strong>Quick playback bar</strong> — a mini-player appears at the bottom of the map allowing you to replay movements, adjust speed (1x to 16x), and observe stops.</li>
<li><strong>Events along route</strong> — markers indicating engine starts, parking intervals, and speed spikes.</li>
<li>Click the <strong>X</strong> icon on the player bar when finished to clear the track from the map view.</li>
</ul>

<h2 id="follow-object">Follow Object</h2>

<p>When an asset is on an urgent delivery or undergoing a diagnostic road test, select <strong>Follow Object</strong> from the context menu:</p>

<ol>
<li>The map instantly centers on the object's current GPS coordinates.</li>
<li>As new telemetry packets arrive from the tracking device, the map view automatically pans to keep the vehicle icon centered on screen.</li>
<li>Clicking the vehicle icon opens a live telemetry tooltip showing current speed, ignition status, fuel level, and last packet timestamp.</li>
<li>To stop tracking, click the <strong>X</strong> on the Follow banner at the top of the map or click anywhere to drag the map manually.</li>
</ol>

<blockquote><p><strong>Call scenario:</strong> A client calls saying "Vehicle KBC 456 was reported stolen 5 minutes ago!" You do not run a report — you right-click the vehicle, click <strong>Follow Object</strong>, and read the live street location and direction of travel directly to the client.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Context Menu Tracking Tools',
                'description' => 'Test your proficiency using Current Track and Follow Object map tools.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What behavior occurs when an operator clicks "Follow Object" from the right-click context menu?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The map centers on the asset and automatically pans to keep the vehicle centered as new telemetry packets arrive.',
                        'options' => [
                            ['text' => 'The map centers on the vehicle and continuously pans to keep it centered as new GPS packets arrive', 'correct' => true],
                            ['text' => 'It deletes previous trips from the database', 'correct' => false],
                            ['text' => 'It shuts off the vehicle engine remotely', 'correct' => false],
                            ['text' => 'It switches the map basemap to terrain mode', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the fastest way to view an object\'s recent driven path and replay it without leaving the Online workspace?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Selecting "Current Track" from the right-click menu instantly displays the recent polyline and player bar on the live map.',
                        'options' => [
                            ['text' => 'Right-click the object and select "Current Track"', 'correct' => true],
                            ['text' => 'Export the user list to Excel', 'correct' => false],
                            ['text' => 'Create an SMTP notification template', 'correct' => false],
                            ['text' => 'Uncheck the vehicle in the Staff and Groups tab', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 4 — knowledge check',
        'description' => 'Four questions on creating objects, required parameters, tags, and map tracking tools.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Which three fields are mandatory when adding a new object to PILOT?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'docs.pilot-gps.com specifies three required fields: Object name/number, Unique device ID (IMEI), and Device type/model.',
                'options' => [
                    ['text' => 'Object name/number, unique device ID (IMEI), and device type', 'correct' => true],
                    ['text' => 'Object name, driver name, and fuel tank capacity', 'correct' => false],
                    ['text' => 'IMEI, SIM card phone number, and contract number', 'correct' => false],
                    ['text' => 'License plate, chassis number (VIN), and insurance policy date', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer wants a 3rd-party refrigeration contractor to only see vehicles with refrigerated units across all branches. How should this be configured?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Tags allow cross-cutting access: tag the refrigerated vehicles with a "Refrigerated" tag, and grant the contractor access to that tag in their user card.',
                'options' => [
                    ['text' => 'Tag the vehicles with a "Refrigerated" tag and assign that tag to the contractor\'s user account', 'correct' => true],
                    ['text' => 'Move all refrigerated vehicles into a single separate contract', 'correct' => false],
                    ['text' => 'Create a separate login for every single refrigerated vehicle', 'correct' => false],
                    ['text' => 'Change the device type of refrigerated vehicles to "Special Sensor"', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What happens when you click "Follow Object" in the right-click context menu?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Follow Object centers the map on the vehicle and automatically pans the viewport as new GPS coordinates arrive.',
                'options' => [
                    ['text' => 'The map centers on the vehicle and continuously pans to keep it in view as it moves', 'correct' => true],
                    ['text' => 'The system sends an automated SMS to the driver', 'correct' => false],
                    ['text' => 'The vehicle is locked and the engine is cut remotely', 'correct' => false],
                    ['text' => 'A 30-day movement report is generated and emailed to the account owner', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Why must the IMEI entered in the object card match the physical GPS device exactly without punctuation?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The GPS tracker transmits its 15-digit numeric IMEI in every network packet header. PILOT uses this identifier to route telemetry data to the correct object record.',
                'options' => [
                    ['text' => 'PILOT uses the raw numeric IMEI transmitted in packet headers to route incoming data to the object', 'correct' => true],
                    ['text' => 'Hyphens cause the database server to shut down', 'correct' => false],
                    ['text' => 'The cellular carrier blocks IMEIs with punctuation', 'correct' => false],
                    ['text' => 'Punctuation converts the object into a stationary asset', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Create and configure a vehicle object',
        'lesson_title' => 'Manually create a new object (car) with General + Info filled in',
        'brief' => 'Log in to the training PILOT account and create a new vehicle object. Fill in '
            .'the required Main settings (Name, unique IMEI, device type) and Info tab parameters. '
            .'Assign a tag and test the "Current Track" or "Follow Object" context menu options.',
        'submission_instructions' => '1. Click Add object in the Online workspace.\n'
            .'2. Enter a vehicle name (e.g. "Test-Car-[YourName]") and a valid test IMEI.\n'
            .'3. Select a device type (e.g. Teltonika FMB120) and select Object type "Car".\n'
            .'4. Fill in sample VIN and license plate on the Info tab, then Save.\n'
            .'5. Right-click the object in the list and select "Follow Object" or "Current Track".\n'
            .'6. Take a screenshot showing your new object in the vehicle list with its settings card.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Device ID / IMEI / Object ID', 'hint' => 'The unique hardware ID or IMEI entered for the vehicle'],
            ['key' => 'object_name', 'label' => 'Object Name', 'hint' => 'The vehicle name you entered (e.g. Test-Car-John)'],
        ],
    ],
];
