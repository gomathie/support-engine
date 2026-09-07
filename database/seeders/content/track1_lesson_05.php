<?php

/**
 * Lesson 5 — Working with objects (part 2) and object list.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Object list                      /object_list.html
 *   · Top panel (status indicators)    /top_panel.html
 *   · Object menu (color highlight)    /object_menu.html
 *   · Personalization                  /personalization.html
 */

return [
    'module_subtitle' => 'Working with objects (part 2) and object list',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Configure color status indicators' => [
            'docs' => 'User account interface → Top panel · Object list · Personalization',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>At a glance, a dispatcher needs to know which vehicles are actively delivering goods, which are taking mandatory driver rest breaks, and which are offline. PILOT uses a standardized color coding system across the entire interface.</strong></p>

<h2 id="core-status-colors">The standard status colors</h2>

<p>Every vehicle in the object list and on the map displays a colored status icon indicating its current telemetry state:</p>

<ul>
<li><strong style="color:#16a34a;">Moving (Green)</strong> — the vehicle is in motion (ignition ON, GPS speed exceeds motion threshold, typically &gt; 3–5 km/h).</li>
<li><strong style="color:#2563eb;">Parked (Blue)</strong> — the vehicle is stationary with ignition OFF (parking interval).</li>
<li><strong style="color:#ea580c;">Idling (Orange)</strong> — the engine is running (ignition ON) but the vehicle has not moved for a configured interval. High idling times indicate fuel waste or unauthorized cabin climate control.</li>
<li><strong style="color:#6b7280;">Inactive / Offline (Grey)</strong> — the tracking device has not transmitted telemetry packets to PILOT for longer than the defined inactivity timeout (e.g. &gt; 24 or 48 hours).</li>
</ul>

<h2 id="custom-color-highlighting">Custom color highlighting</h2>

<p>In addition to automatic operational states, you can highlight individual vehicles with custom colors to mark them for special attention:</p>

<ol>
<li>Right-click the vehicle in the object list.</li>
<li>Select <strong>Highlight with color</strong>.</li>
<li>Choose a palette color (e.g. Red for a flagged maintenance issue, Yellow for high-priority dispatch).</li>
<li>The object row and map label will immediately show the chosen color.</li>
</ol>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create group "Test Vehicles" and move objects into it' => [
            'docs' => 'User account interface → Workspace → Object list → Groups',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Grouping objects enables multi-vehicle operations: generating batch reports, applying sensor templates to an entire division, and structuring dispatcher workspaces.</strong></p>

<h2 id="creating-groups">Creating an object group</h2>

<p>To create a new organizational group:</p>

<ol>
<li>In the left sidebar of the Online section, click the <strong>Groups</strong> tab.</li>
<li>Click the <strong>Add group</strong> button (plus icon).</li>
<li>Enter the group name: <code>Test Vehicles</code>.</li>
<li>Optionally, select a parent group if building a nested branch hierarchy.</li>
<li>Click <strong>Save</strong>. The new folder appears in the group tree.</li>
</ol>

<h2 id="moving-objects">Moving objects into a group</h2>

<p>There are two ways to populate a group:</p>

<ul>
<li><strong>Via Drag and Drop:</strong> In the left sidebar tree, simply drag an object from the ungrouped list and drop it directly onto the <em>Test Vehicles</em> group folder.</li>
<li><strong>Via Object Card:</strong> Double-click the object to open its settings, go to the <strong>Settings</strong> tab, choose <em>Test Vehicles</em> in the <strong>Group</strong> dropdown, and click <strong>Save</strong>.</li>
</ul>

<p>To perform actions on the whole group, right-click the group name to build group history, open all group objects on the map, or send commands.</p>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Configure columns: Name, Speed, Status, Driver' => [
            'docs' => 'User account interface → Workspace → Object list → Column settings',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>The object table can display dozens of telemetry fields. Different teams need different views: dispatchers need speed and driver, while fuel managers need tank volume and consumption.</strong></p>

<h2 id="customizing-columns">Configuring table columns</h2>

<p>To customize the columns visible in the object list:</p>

<ol>
<li>Look at the top-right corner of the object list table header.</li>
<li>Click the <strong>Table settings</strong> (gear icon) button.</li>
<li>A dialog opens listing all available data columns. Check the required fields:
  <ul>
    <li><strong>Name</strong> — vehicle callsign or asset identifier.</li>
    <li><strong>Speed</strong> — current velocity reported by GPS or CAN bus (km/h).</li>
    <li><strong>Status</strong> — operational state (Moving, Parked, Idling, Inactive) with duration counter.</li>
    <li><strong>Driver</strong> — the driver currently assigned to the vehicle (manually or via iButton / RFID / Face ID).</li>
  </ul>
</li>
<li>Uncheck irrelevant fields (e.g. altitude, satellites count, battery voltage) to keep the screen uncluttered.</li>
<li>Drag column rows up or down in the dialog to set your preferred left-to-right order.</li>
<li>Click <strong>Apply</strong>.</li>
</ol>

<blockquote><p><strong>Display persistence:</strong> Column configurations are saved automatically in your user account profile in the database. When you log in from another browser or workstation, your custom table layout is preserved.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Filter list to objects "in motion"' => [
            'docs' => 'User account interface → Top panel · Object list → Filters',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>During peak business hours, a dispatcher managing 500 vehicles cannot scroll a massive list. Instant status filters allow you to isolate moving vehicles in a single click.</strong></p>

<h2 id="top-panel-filter">Method 1: The Top Panel quick filter</h2>

<p>The fastest way to filter by motion state:</p>

<ol>
<li>Look at the colored numbers on the horizontal top bar.</li>
<li>Click directly on the <strong>Moving</strong> counter (the green number).</li>
<li>The object list below immediately refreshes to display <strong>only</strong> vehicles whose current state is Moving.</li>
<li>The map view also hides stationary units, focusing exclusively on active routes.</li>
<li>To reset the filter and view all assets again, click the <strong>Total</strong> counter on the top panel.</li>
</ol>

<h2 id="table-filter">Method 2: The Object List filter dropdown</h2>

<p>Inside the object list header, open the <strong>Status filter</strong> dropdown menu and select <em>Moving</em>. You can combine this with the text search filter (e.g. filter to <em>Moving</em> AND type <em>Nairobi</em> in the search box) to find active vehicles in a specific region.</p>

<blockquote><p><strong>Support troubleshooting:</strong> When a customer calls panicked saying "Half my fleet has vanished from PILOT!", the very first thing to check is whether they inadvertently clicked one of the top panel status buttons. Clicking the "Idling" or "Moving" counter filters out all other vehicles.</p></blockquote>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 5 — knowledge check',
        'description' => 'Four questions on status colors, object groups, table column configuration, and list filters.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What does an orange status indicator on a vehicle signify in PILOT?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Orange indicates "Idling": the engine is running (ignition ON), but the vehicle is stationary.',
                'options' => [
                    ['text' => 'Idling — the engine is on but the vehicle is not moving', 'correct' => true],
                    ['text' => 'Low battery warning on the GPS device', 'correct' => false],
                    ['text' => 'The vehicle has breached a geofence', 'correct' => false],
                    ['text' => 'Emergency SOS button was pressed', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer calls and says "20 of my vehicles are missing from the list!" What is the most common user mistake that causes this?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Clicking any status count on the top panel (Moving, Parked, Idling, Inactive) activates an instant filter, hiding all other objects from the list and map.',
                'options' => [
                    ['text' => 'They clicked a status button on the top panel (e.g. Moving), which filtered out all other objects', 'correct' => true],
                    ['text' => 'Their contract subscription has automatically expired', 'correct' => false],
                    ['text' => 'The PILOT server deleted the vehicles due to inactivity', 'correct' => false],
                    ['text' => 'Their browser does not support GPS rendering', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'How do you customize the visible columns in the object list table?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Click the gear icon in the top-right corner of the object table header to open column settings, check desired fields, and reorder them.',
                'options' => [
                    ['text' => 'Click the gear/table settings icon on the object list header and check the desired columns', 'correct' => true],
                    ['text' => 'Edit the raw CSS stylesheet in browser developer tools', 'correct' => false],
                    ['text' => 'Contact PILOT database administrators to update your account schema', 'correct' => false],
                    ['text' => 'Right-click each vehicle one by one and click "Add Column"', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the difference between an Object Group and an Object Tag?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Groups provide hierarchical folders for organizing assets in the sidebar tree, while tags are cross-cutting labels used for multi-group filtering and permission assignment.',
                'options' => [
                    ['text' => 'Groups provide a tree folder structure; tags are cross-cutting labels for multi-group filtering and permissions', 'correct' => true],
                    ['text' => 'Groups are only for trucks; tags are only for passenger cars', 'correct' => false],
                    ['text' => 'Groups can only contain one object, while tags contain many', 'correct' => false],
                    ['text' => 'Tags require a physical hardware barcode on the vehicle', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Organize, customize, and filter the object list',
        'lesson_title' => 'Configure columns: Name, Speed, Status, Driver',
        'brief' => 'In the training PILOT account, create a new object group named "Test Vehicles", '
            .'move at least one vehicle into it, configure table columns to show Name, Speed, '
            .'Status, and Driver, and filter the view to moving or parked assets.',
        'submission_instructions' => '1. Open the Groups tab in the Online section left sidebar.\n'
            .'2. Create a group named "Test Vehicles" and move your test vehicle into it.\n'
            .'3. Click the gear icon on the object table header to show Name, Speed, Status, Driver.\n'
            .'4. Click the "Moving" or "Parked" status button on the top panel to apply a filter.\n'
            .'5. Take a screenshot showing the customized table columns and active group view.',
        'requires_screenshot' => true,
        'estimated_minutes' => 15,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Your Account ID', 'hint' => 'The login you used to access PILOT'],
            ['key' => 'group_name', 'label' => 'Created Group Name', 'hint' => 'Name of the group created (e.g. Test Vehicles)'],
        ],
    ],
];
