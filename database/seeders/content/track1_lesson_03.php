<?php

/**
 * Lesson 3 — User and rights management.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Creating users                   /creating_users.html
 *   · User card                        /user_card.html
 *   · Rights                           /rights.html
 *   · Blocking and unblocking a user   /blocking_and_unblocking_a_user.html
 *   · Staff and groups                 /staff_and_groups.html
 */

return [
    'module_subtitle' => 'User and rights management',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Create a new user with role "User"' => [
            'docs' => 'Account settings → Staff and groups → Creating users',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<div class="lede"><p>Administrators create and manage user profiles in PILOT. When creating an account, you enter the user’s login credentials, configure contact details, and assign their user type.</p></div>

<h2 id="access-staff">Opening Staff and Groups</h2>

<p>To manage users, click the <strong>Account settings</strong> gear icon on the top panel and select <strong>Staff and groups</strong>. You will see a list of all existing users associated with the contract, along with their roles, status indicators, and contact information.</p>

<h2 id="create-user-steps">Step-by-step: Creating a new user</h2>

<ol>
<li>Open <strong>Account settings</strong> and select the <strong>Staff and groups</strong> tab.</li>
<li>Click the <strong>Add user</strong> (plus icon) button above the list.</li>
<li>Fill in the user details. Mandatory fields are outlined in red:
  <ul>
    <li><strong>Login</strong> — the unique username the person enters when signing in to PILOT.</li>
    <li><strong>Password</strong> — the sign-in password. Click the pencil/edit icon in the field to type and confirm the password.</li>
    <li><strong>Name</strong> — the employee's full name or a descriptive role title (e.g. <em>John Dispatcher</em>).</li>
    <li><strong>Type</strong> — the user's base access level:
      <ul>
        <li><strong>Administrator</strong> — full access to add users, create objects, configure drivers, and grant permissions.</li>
        <li><strong>User</strong> — restricted access. Can only view and work with the specific objects, tags, and features explicitly assigned to them.</li>
      </ul>
    </li>
    <li><strong>Email</strong> — the user's email address. Clicking the send icon will email the user an invitation link with login instructions.</li>
    <li><strong>Mobile</strong> — contact phone number for SMS notifications and operational communication.</li>
    <li><strong>Information</strong> — optional notes, department, or employee reference number.</li>
  </ul>
</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<div class="note"><p><strong>Support tip:</strong> A caller reporting "I created an account for my colleague but they can't see anything on the map" almost always created a <strong>User</strong> type without assigning any objects on the Vehicles tab. A new User starts with zero visible objects until you explicitly grant access.</p></div>

<h2 id="protection-rules">Account protection rules</h2>

<p>The account owner is highlighted in green in the user list. Neither the account owner nor the currently signed-in user can be deleted directly in the user interface. This safeguard ensures contract access can never be accidentally severed.</p>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Assign rights to 2 test objects' => [
            'docs' => 'Account settings → Staff and groups → User card → Vehicles',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<div class="lede"><p>Creating a user account is only half the job. In PILOT, access to objects is completely segregated: a user only sees the vehicles explicitly assigned to their account.</p></div>

<h2 id="user-card-tabs">The User Card tabs</h2>

<p>Double-click any user in the <strong>Staff and groups</strong> list to open their <strong>User Card</strong>. The card contains multiple configuration tabs:</p>

<ul>
<li><strong>Settings</strong> — basic login details, password, user type, and personal preferences.</li>
<li><strong>Vehicles</strong> (Objects) — which vehicles and monitoring units this user can see and track.</li>
<li><strong>Drivers</strong> — which registered drivers the user can monitor and link to trips.</li>
<li><strong>Object tags</strong> — groups of objects by tag (e.g. <em>Delivery Vans</em>, <em>Heavy Trucks</em>).</li>
<li><strong>Rights</strong> — granular functional permissions (editing objects, running reports, managing geofences).</li>
</ul>

<h2 id="assigning-vehicles">Assigning objects to a user</h2>

<p>To give a user visibility over monitored objects:</p>

<ol>
<li>Open the user card and switch to the <strong>Vehicles</strong> tab.</li>
<li>You will see a tree of all available objects in the contract.</li>
<li>Check the boxes next to the objects you want this user to access. For testing, select at least <strong>two test objects</strong>.</li>
<li>Alternatively, if objects are tagged, you can assign an entire tag on the <strong>Object tags</strong> tab — any vehicle carrying that tag will automatically become visible to the user.</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<div class="hint"><p><strong>Diagnosing missing objects:</strong> When a user complains they cannot find a specific vehicle in the Online list or Reports dropdown, check their User Card → <strong>Vehicles</strong> tab first. If the checkbox next to the vehicle is unchecked, PILOT behaves as if the vehicle does not exist for that user.</p></div>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create a rights template for the role' => [
            'docs' => 'Account settings → Staff and groups → Rights → Rights templates',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<div class="lede"><p>Configuring dozens of granular permissions for every new employee is slow and error-prone. Rights templates allow you to save a standard permission profile and apply it in one click.</p></div>

<h2 id="rights-categories">Granular functional permissions</h2>

<p>On the <strong>Rights</strong> tab of the user card, permissions are organized into collapsible categories:</p>

<ul>
<li><strong>Working with vehicles</strong> — viewing location, reading sensor telemetry, tracking history.</li>
<li><strong>Creating and editing objects</strong> — permission to modify object cards, install virtual sensors, or delete items.</li>
<li><strong>Managing sensors and tags</strong> — configuring fuel calibration, temperature thresholds, and sensor formulas.</li>
<li><strong>Viewing and generating reports</strong> — running operational reports and exporting PDF/Excel files.</li>
<li><strong>Working with geofences</strong> — creating zones, drawing boundaries, setting arrival triggers.</li>
<li><strong>Tokens</strong> — creating Monitor tokens and shareable map links for external clients.</li>
</ul>

<p>Checking a top-level category box enables all permissions within it. Expanding the category lets you pick individual checkboxes to enforce the principle of least privilege.</p>

<h2 id="creating-template">Creating a rights template</h2>

<ol>
<li>Open the user card of a user who has the exact desired set of permissions configured.</li>
<li>Go to the <strong>Rights</strong> tab.</li>
<li>Click the <strong>Save template</strong> icon (floppy disk icon) at the top of the tab.</li>
<li>In the dialog that appears, enter a clear, descriptive name (e.g. <em>Standard Dispatcher</em> or <em>Fleet Viewer</em>).</li>
<li>Click <strong>Save</strong>. The template is now stored in the contract's template catalog.</li>
</ol>

<h2 id="applying-template">Applying a rights template</h2>

<p>When creating a new user or editing an existing one:</p>

<ol>
<li>Go to the <strong>Settings</strong> tab of the user card.</li>
<li>Open the <strong>Type</strong> dropdown list.</li>
<li>Below the standard types (Administrator, User), you will see your saved templates marked with <code>(template)</code>, e.g. <em>Standard Dispatcher (template)</em>.</li>
<li>Select the template and click <strong>Save</strong>. All checkboxes in the Rights tab will update immediately to match the template.</li>
</ol>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Block, then unblock, the created user' => [
            'docs' => 'Account settings → Staff and groups → Blocking and unblocking a user',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<div class="lede"><p>When an employee is on leave, suspended, or under investigation, you should block their account rather than delete it. Blocking immediately revokes system access while keeping all settings and audit trails intact.</p></div>

<h2 id="status-indicators">Status column indicators</h2>

<p>In the <strong>Staff and groups</strong> list, each user row includes a color-coded icon in the <strong>Status</strong> column:</p>

<ul>
<li><strong style="color:#16a34a;">Green icon</strong> — the account is <strong>Active</strong>. The user can sign in normally.</li>
<li><strong style="color:#dc2626;">Red icon</strong> — the account is <strong>Blocked</strong>. Any active session is terminated, and sign-in attempts return an access denied notification.</li>
</ul>

<h2 id="how-to-block">How to block a user</h2>

<ol>
<li>Navigate to <strong>Account settings</strong> → <strong>Staff and groups</strong>.</li>
<li>Locate the user in the list.</li>
<li>Click the green icon in the <strong>Status</strong> column of their row.</li>
<li>A confirmation prompt will ask: <em>"Are you sure you want to block this user?"</em></li>
<li>Click <strong>Yes</strong>. The icon turns red immediately.</li>
</ol>

<h2 id="how-to-unblock">How to unblock a user</h2>

<ol>
<li>Find the blocked user (marked with the red icon).</li>
<li>Click the red icon in the <strong>Status</strong> column.</li>
<li>Confirm the action by clicking <strong>Yes</strong>.</li>
<li>The icon returns to green, and the employee can sign in immediately using their previous password.</li>
</ol>

<div class="note"><p><strong>Why block instead of delete?</strong> Deleting a user permanently destroys their personal workspace, custom views, assigned report schedules, and saved token permissions. If the employee returns or access is restored, rebuilding that configuration from scratch wastes hours. Always block first.</p></div>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 3 — knowledge check',
        'description' => 'Four questions on user roles, object permissions, rights templates, and account status.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'A customer created a new user with the "User" role, but the employee reports that the vehicle list on their screen is completely empty. What is the cause?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'In PILOT, a newly created User has zero visible vehicles by default. An administrator must explicitly check the allowed vehicles on the Vehicles tab of the user card.',
                'options' => [
                    ['text' => 'The user has not been assigned any objects on the Vehicles tab of their user card', 'correct' => true],
                    ['text' => 'The tracking devices installed in the vehicles are switched off', 'correct' => false],
                    ['text' => 'The user must wait 24 hours for their database cache to sync', 'correct' => false],
                    ['text' => 'Only Administrators can view vehicles on the live map', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the key advantage of blocking an account over deleting it?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Blocking immediately revokes sign-in access while preserving the user profile, assigned vehicles, custom reports, and configuration.',
                'options' => [
                    ['text' => 'Blocking revokes access immediately while preserving all assigned rights, objects, and settings', 'correct' => true],
                    ['text' => 'Blocking allows the user to view history but not live tracking', 'correct' => false],
                    ['text' => 'Deleting an account requires approval from PILOT support, whereas blocking does not', 'correct' => false],
                    ['text' => 'Blocked users can still log in using the mobile application', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'How does an administrator apply a saved rights template to a user?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Saved rights templates appear in the "Type" dropdown on the user card settings tab, marked with (template). Selecting one applies its saved permissions.',
                'options' => [
                    ['text' => 'Select the template marked with (template) in the Type dropdown on the user card', 'correct' => true],
                    ['text' => 'Export the template as a JSON file and upload it to the user profile', 'correct' => false],
                    ['text' => 'Drag and drop the user row onto the template folder in the sidebar', 'correct' => false],
                    ['text' => 'Type the template name into the user Information field', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Can the contract owner user account be deleted directly from the Staff and groups tab?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The contract owner (highlighted in green) cannot be deleted in the user interface to prevent losing contract ownership. Ownership must first be transferred in the admin panel.',
                'options' => [
                    ['text' => 'No, the contract owner is protected against deletion to prevent accidental loss of contract access', 'correct' => true],
                    ['text' => 'Yes, any administrator can delete the contract owner at any time', 'correct' => false],
                    ['text' => 'Yes, but only after all other users have been deleted first', 'correct' => false],
                    ['text' => 'Only if the contract has no active vehicles', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Create and configure user accounts and permissions',
        'lesson_title' => 'Create a new user with role "User"',
        'brief' => 'In the training PILOT account, create a new user with the "User" role, '
            .'assign access to two test objects on the Vehicles tab, create a reusable rights template, '
            .'and verify the blocking and unblocking workflow.',
        'submission_instructions' => '1. Open Account Settings → Staff and groups.\n'
            .'2. Click the Add user icon and create a user with Type "User".\n'
            .'3. Open their User Card, go to the Vehicles tab, and check at least two test vehicles.\n'
            .'4. Configure permissions on the Rights tab and save as a template named "Trainee Dispatcher".\n'
            .'5. Click the green status icon to block the user, then click again to unblock.\n'
            .'6. Take a screenshot showing the user in the Staff and groups table with their assigned details.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'account_id', 'label' => 'Your Account ID', 'hint' => 'The username you used to log into PILOT'],
            ['key' => 'created_user_login', 'label' => 'Created User Login', 'hint' => 'Login name of the user you created in Staff and groups'],
        ],
    ],
];
