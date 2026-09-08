<?php

/**
 * Admin panel — Module 2: Objects, partners and finances.
 *
 * Content drawn from docs.pilot-gps.com:
 *   · Admin Panel → Vehicles                 /vehicles_2.html
 *   · Admin Panel → Vehicles → Edit          /edit_3.html
 *   · Admin Panel → Finance                  /finance.html
 *   · Admin Panel → Partners → How to add    /how_to_add_a_partner.html
 *   · Admin Panel → Partners → SMTP          /email_sending_configuration__smtp_.html
 *   · Admin Panel → Account → Configuration  /configuration.html
 *   · Notifications                          /notifications__print.html
 *
 * Three topics are deliberately unwritten, and all three are about the same
 * thing: **speed control is not a configuration.** The documented configuration
 * list carries no speed key of any kind, and speeding appears only as a
 * notification type belonging to the Notification module. The block *date* is
 * the fourth gap — the Vehicles tab documents two independent blocks but no
 * date field. Those are marked `needs_input` rather than filled with a
 * plausible guess: an invented PILOT fact is worse than an obvious hole,
 * because a trainee will carry it onto a call.
 *
 * Markup is limited to what Filament's rich editor round-trips: headings,
 * lists, blockquotes, tables and inline marks. No div, dl or span — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Objects, partners, and finances',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Transfer an object into the created contract' => [
            'docs' => 'Admin Panel → Vehicles · Vehicles → Edit',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>A contract with no objects in it is an empty shell. Moving a vehicle into it is the act
that makes the account real — and it is done from the Vehicles tab, not from the contract.</strong></p>

<h2 id="the-vehicles-tab">The Vehicles tab</h2>

<p>Vehicles is the monitoring hub for every tracking device already in the system. Before changing
anything, learn to read the row — most first-line questions are answered by it without opening
anything:</p>

<table>
<thead>
<tr><th>Column</th><th>What it tells you</th></tr>
</thead>
<tbody>
<tr><td><strong>Agent ID</strong></td><td>The identifier of the monitoring object. This is the number to quote in a ticket.</td></tr>
<tr><td><strong>Name</strong></td><td>The name of the monitoring object in the system.</td></tr>
<tr><td><strong>Folder</strong></td><td>The folder of the monitoring object in the system.</td></tr>
<tr><td><strong>Type</strong></td><td>The type of the installed unit.</td></tr>
<tr><td><strong>Unique</strong></td><td>The IMEI of the unit.</td></tr>
<tr><td><strong>Msisdn</strong></td><td>The SIM card number on the device.</td></tr>
<tr><td><strong>Agreement</strong></td><td>The number of the agreement the object belongs to.</td></tr>
<tr><td><strong>Owner</strong></td><td>The name of that agreement.</td></tr>
<tr><td><strong>Partner</strong></td><td>The partner the object belongs to.</td></tr>
<tr><td><strong>Tariff</strong> · <strong>Partner tariff</strong></td><td>The tariff plan and price per month, and the partner's own plan.</td></tr>
<tr><td><strong>Data volume</strong></td><td>The volume of data.</td></tr>
<tr><td><strong>Himself</strong> · <strong>Administrator</strong></td><td>Blocking by the user, and blocking by the administrator — two separate switches.</td></tr>
<tr><td><strong>Online</strong> · <strong>Last update</strong></td><td>The current state, and when the object last reported.</td></tr>
</tbody>
</table>

<h2 id="the-transfer">Making the transfer</h2>

<p>The Edit tab <em>is used to enter various parameters for the vehicle</em>, and one of those
parameters is which account owns it:</p>

<blockquote><p>You can transfer cars from one contract to another <strong>with all the entered
parameters</strong> by selecting the <strong>Account</strong> field.</p></blockquote>

<ol>
<li>Open <strong>Admin Panel → Vehicles</strong>.</li>
<li>Find the vehicle. The Agent ID and the Unique (IMEI) are the two identifiers a customer or a
    colleague will usually give you.</li>
<li>Open <strong>Edit</strong>.</li>
<li>Change the <strong>Account</strong> field to the destination contract, and save.</li>
</ol>

<h2 id="what-moves">What moves with it</h2>

<p>The documentation is explicit on the one point that matters: the transfer carries
<strong>all the entered parameters</strong> across with the vehicle. You are moving a configured
object, not a blank one, so check afterwards that the parameters it arrived with are the ones the
new contract should have — particularly the tariff, which now bills against a different agreement.</p>

<blockquote><p><strong>On a call.</strong> "The vehicle has disappeared from my account" is very
often a transfer somebody made deliberately. The Agreement and Owner columns on the Vehicles tab
tell you where it went in one look, before you start investigating the device.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Transferring an object between contracts',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which field on the Vehicles → Edit tab moves a vehicle from one contract to another?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The documentation states that you can transfer cars from one contract to another with all the entered parameters by selecting the Account field.',
                        'options' => [
                            ['text' => 'The Account field', 'correct' => true],
                            ['text' => 'The Owner field', 'correct' => false],
                            ['text' => 'The Partner field', 'correct' => false],
                            ['text' => 'The Folder field', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'A customer gives you an IMEI. Which column on the Vehicles tab carries it?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Unique is documented as "the IMEI unit". Msisdn is the SIM card number, and Agent ID is the system identifier of the monitoring object.',
                        'options' => [
                            ['text' => 'Unique', 'correct' => true],
                            ['text' => 'Agent ID', 'correct' => false],
                            ['text' => 'Msisdn', 'correct' => false],
                            ['text' => 'Type', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Add a tariff to the object' => [
            'docs' => 'Admin Panel → Vehicles · Finance → Tariffs list',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>The tariff is what turns a tracked vehicle into a billed one. Every object row carries
two of them, and they are not the same thing.</strong></p>

<h2 id="two-tariffs">Two tariffs on every row</h2>

<ul>
<li><strong>Tariff</strong> — the tariff plan and the price per month, for the object.</li>
<li><strong>Partner tariff</strong> — the tariff plan for the partner.</li>
</ul>

<p>Both are columns on the Vehicles tab, so you can read what an object is on without opening it.
Two customers on the same plan seeing different prices is usually this pair diverging, not a fault.</p>

<h2 id="where-tariffs-come-from">Where tariffs come from</h2>

<p>Tariffs are not created on the object. They are created once, in <strong>Finance</strong>, and
then applied:</p>

<ol>
<li>Open the <strong>Tariffs list</strong> in the Finance section.</li>
<li>Click <strong>+ add</strong> for a new tariff.</li>
<li><strong>Specify a partner</strong> — a tariff belongs to a partner.</li>
<li>Add a <strong>price list</strong>, and add new prices to it.</li>
</ol>

<p>Finance also holds the <strong>Partner currencies list</strong>, which is why a price only means
something alongside the currency the partner bills in.</p>

<h2 id="applying-it">Applying it to the object</h2>

<p>The object's own parameters — the tariff among them — are entered on
<strong>Vehicles → Edit</strong>, the same tab that carries the Account field. Set it, save, and
confirm on the Vehicles list that the Tariff column now reads what you expect.</p>

<blockquote><p><strong>Check, do not assume.</strong> The Vehicles list is the verification step.
A change that has not appeared in the Tariff column has not been made, whatever the form said when
you pressed save.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Tariffs on an object',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Where is a new tariff created?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The Tariffs list lives in the Finance section: click + add for a new tariff, specify a partner, then add a price list and new prices.',
                        'options' => [
                            ['text' => 'In Finance, on the Tariffs list', 'correct' => true],
                            ['text' => 'On the object, in Vehicles → Edit', 'correct' => false],
                            ['text' => 'In Administrator → Settings', 'correct' => false],
                            ['text' => 'On the contract, in Account → Configuration', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What must be specified when adding a new tariff?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A tariff belongs to a partner — the documented steps are to specify a partner, then add a price list and new prices.',
                        'options' => [
                            ['text' => 'A partner', 'correct' => true],
                            ['text' => 'An Agent ID', 'correct' => false],
                            ['text' => 'A grace period', 'correct' => false],
                            ['text' => 'An IMEI range', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Configure blocking with a block date' => [
            'docs' => 'Admin Panel → Vehicles · Account → Configuration',
            'estimated_minutes' => 10,
            'needs_input' => 'The two blocks (Himself, Administrator) and the Partner Grace Period '
                .'configuration are documented. A block *date* field is not — neither the Vehicles '
                .'tab nor the Configuration list mentions one. Igor to confirm whether a scheduled '
                .'block date exists, and where, or whether the topic should be reworded to the '
                .'grace period.',
            'body' => <<<'HTML'
<p><strong>Blocking is how an unpaid or withdrawn object stops working without being deleted. There
are two blocks on every object, they are independent, and confusing them wastes a call.</strong></p>

<h2 id="two-blocks">Two blocks, two owners</h2>

<p>The Vehicles tab carries both as separate columns:</p>

<ul>
<li><strong>Himself</strong> — blocking the monitoring object <em>by the user</em>.</li>
<li><strong>Administrator</strong> — blocking the monitoring object <em>by the administrator</em>.</li>
</ul>

<p>They are not one switch shown twice. An object can be blocked by the administrator while the
customer has not blocked it themselves, and the customer clearing their own block will not bring it
back. Read both columns before telling anyone why their vehicle is dark.</p>

<h2 id="grace-period">The grace period</h2>

<p>Blocking is not always immediate. The account configuration carries:</p>

<ul>
<li><strong>Partner Grace Period (days)</strong> — documented with an example value of
    <strong>60</strong>.</li>
</ul>

<p>That is the window a partner is given before the consequence lands, and it is set per account in
<em>Admin Panel → Account → Configuration</em>, alongside the other configuration keys.</p>

<h2 id="not-documented">What is not written here, and why</h2>

<blockquote><p><strong>A scheduled block date is not in the documentation.</strong> The Vehicles tab
documents the two blocks above and no date field; the Configuration list documents the grace period
and no block date. Rather than describe a field that may not exist, this part of the topic is left
open — see the note on this lesson.</p></blockquote>

<p>If a customer asks you to schedule a block for a future date, do not promise it from this
lesson. Check the live panel, and escalate if it is not there.</p>
HTML,
            'quiz' => [
                'title' => 'Blocking an object',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'An object shows a block in the Administrator column. The customer clears their own block and calls to say nothing changed. What has happened?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Himself and Administrator are two independent blocks — one is set by the user, the other by the administrator. Clearing one does not clear the other.',
                        'options' => [
                            ['text' => 'The administrator block is separate and is still in place', 'correct' => true],
                            ['text' => 'The change takes effect after the grace period', 'correct' => false],
                            ['text' => 'The object needs to reconnect before the block lifts', 'correct' => false],
                            ['text' => 'Clearing the user block also requires a tariff', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which configuration key sets the window a partner is given before the consequence of non-payment lands?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Partner Grace Period (days) is a documented configuration key, shown with an example value of 60.',
                        'options' => [
                            ['text' => 'Partner Grace Period (days)', 'correct' => true],
                            ['text' => 'Block date', 'correct' => false],
                            ['text' => 'Low Balance Emails', 'correct' => false],
                            ['text' => 'Only private prices', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Add a speed-control configuration to the object' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'There is no speed configuration. The documented Account → '
                .'Configuration list contains no speed key of any kind, and speeding appears only '
                .'as a notification type under the Notification module. Igor to confirm whether '
                .'this topic should be rewritten as "set up a speeding notification" — which is '
                .'documented — or retired.',
            'body' => <<<'HTML'
<blockquote><p><strong>This topic is not written yet, and the reason is worth reading.</strong></p></blockquote>

<h2 id="not-a-configuration">Speed control is not a configuration</h2>

<p>The configuration keys documented under <em>Admin Panel → Account → Configuration</em> are a
short, closed list — things like <em>Address in Online Tree</em>, <em>Partner Grace Period
(days)</em>, <em>Low Balance Emails</em>, <em>Main Tab</em> and <em>TOTP Label</em>.
<strong>None of them concerns speed, a speed limit, or speeding.</strong></p>

<h2 id="where-speed-lives">Where speed actually lives</h2>

<p>Speed appears in the documentation in one place: as a <strong>notification</strong>. The
Notifications module lists <em>"speeding or exceeding speed limits"</em> among the events it can
alert on, alongside ignition on/off, fuel level changes, geofence entry and exit, and loss of
connection with the device.</p>

<p>A notification is set up with the conditions that trigger it, the objects it applies to, and the
delivery method — email, SMS, Telegram, in-system messages or mobile app push. That is a different
screen, a different module, and a different conversation with the customer.</p>

<blockquote><p><strong>Why this matters on a call.</strong> If a customer asks for "the speed
setting", sending them to look for a configuration key sends them somewhere it is not. The right
answer is a speeding notification, and the right first question is whether the
<strong>Notification</strong> module is active on their contract at all.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create a partner (data, currency, tariff)' => [
            'docs' => 'Admin Panel → Partners → How to add a partner',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>A partner is the level above a contract: an organisation that holds accounts, bills in
its own currency and prices on its own plan. Creating one is the first step of standing up a
reseller.</strong></p>

<h2 id="the-form">The form</h2>

<p>In the <strong>Partners</strong> section, click <strong>Add</strong>, then open the
<strong>Main settings</strong> tab of the dialog that appears.</p>

<h3 id="general">General information</h3>

<ul>
<li><strong>Name</strong> — the agreement or company name.</li>
<li><strong>Email</strong> — the partner's email address.</li>
<li><strong>Phone</strong> — the contact number.</li>
<li><strong>Deposit</strong> — an advance payment, if applicable.</li>
<li><strong>IIN</strong> — individual identification number.</li>
<li><strong>KPP</strong> — company registration code.</li>
<li><strong>VAT</strong> — specified as a percentage.</li>
<li><strong>1C Code</strong> — for synchronisation with the accounting system.</li>
<li><strong>Region</strong> — chosen from a dropdown.</li>
</ul>

<h3 id="access">Access and assignment</h3>

<ul>
<li><strong>Block Partner</strong>.</li>
<li><strong>Block users of accounts partner</strong>.</li>
<li><strong>Partner</strong> — used to build a sub-partner structure, by naming the partner this one
    sits under.</li>
</ul>

<h3 id="money">Financial and pricing</h3>

<ul>
<li><strong>Currency</strong> — the main billing currency.</li>
<li><strong>Additional currencies</strong> — optional.</li>
<li><strong>Price</strong> — the pricing plan. <strong>It applies only to new objects.</strong></li>
<li><strong>Partner price</strong> — an additional tariff.</li>
<li><strong>Organization type</strong> — the legal form.</li>
<li><strong>Account type</strong> — the billing model.</li>
</ul>

<h3 id="login">Login credentials</h3>

<ul>
<li><strong>Password complexity level</strong> — simple or complex.</li>
<li><strong>Show user password</strong> — a toggle.</li>
<li><strong>Login</strong> — required.</li>
<li><strong>Password</strong> — the administrator account password.</li>
</ul>

<p>Click <strong>Save</strong> when the required fields are complete. After saving you can assign
contracts, connect objects, and create users for that partner.</p>

<blockquote><p><strong>The one to remember.</strong> <em>Price</em> applies only to
<strong>new</strong> objects. Changing a partner's plan does not reprice what they already have, and
a customer expecting it to is not seeing a bug. Say so before they escalate.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Creating a partner',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A partner asks why their new pricing plan did not change the price of vehicles they already had. What do you tell them?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The Price field on the partner form is documented as applying only to new objects.',
                        'options' => [
                            ['text' => 'The Price plan applies only to new objects', 'correct' => true],
                            ['text' => 'The change applies after the grace period expires', 'correct' => false],
                            ['text' => 'Existing objects reprice at the next invoice', 'correct' => false],
                            ['text' => 'Only the Partner price field affects existing objects', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'How is a sub-partner structure created?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The partner form carries its own Partner field, used to name the partner this one sits under.',
                        'options' => [
                            ['text' => 'By setting the Partner field on the new partner', 'correct' => true],
                            ['text' => 'By setting Organization type to sub-partner', 'correct' => false],
                            ['text' => 'By adding the partner to the parent\'s Additional currencies', 'correct' => false],
                            ['text' => 'Sub-partners are created from the Finance section', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Configure SMTP for the partner' => [
            'docs' => 'Admin Panel → Partners → Email sending configuration (SMTP)',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Without SMTP, a partner sends no email at all — no low-balance warning, no email
two-factor code, nothing. It is one tab and six fields, and it is a frequent cause of "the system
never emailed me".</strong></p>

<h2 id="getting-there">Getting there</h2>

<ol>
<li>Open the <strong>Partners</strong> section of the admin panel.</li>
<li><strong>Double-click</strong> the partner to open its settings.</li>
<li>Open the <strong>SMTP</strong> tab.</li>
</ol>

<h2 id="the-fields">The fields</h2>

<table>
<thead>
<tr><th>Field</th><th>What it is</th></tr>
</thead>
<tbody>
<tr><td><strong>SMTP Host</strong></td><td>The mail server address.</td></tr>
<tr><td><strong>SMTP Port</strong></td><td>The server port number.</td></tr>
<tr><td><strong>SMTP Login</strong></td><td>The authentication username.</td></tr>
<tr><td><strong>SMTP Password</strong></td><td>The authentication credential.</td></tr>
<tr><td><strong>SMTP Email</strong></td><td>The sender address on outgoing messages.</td></tr>
<tr><td><strong>SMTP Security</strong></td><td>The encryption method: <em>No encryption</em>, <em>SSL</em> or <em>TLS</em>.</td></tr>
</tbody>
</table>

<h2 id="testing">Testing it</h2>

<p>Do not leave it unverified. The system tests connectivity and authentication over SMTP itself: it
resolves the server address, uses the port given (<strong>defaulting to 25</strong> if none is
specified), opens the connection, sends <code>EHLO</code>, discovers the ESMTP extensions, upgrades
to TLS and re-sends <code>EHLO</code> if the server supports it, and then attempts to authenticate
with the credentials supplied.</p>

<p>A failure is logged, comes back as an HTTP 400, and carries a JSON error message describing what
went wrong. The connection is closed cleanly either way.</p>

<blockquote><p><strong>Read the error, do not re-type the password.</strong> The test tells you
which stage failed. A connection that never opens is a host, port or firewall problem; one that
opens and then fails to authenticate is the login or the password. They are different tickets.</p></blockquote>

<blockquote><p>The documentation's own warning: verify all entries carefully — email delivery
depends on their accuracy.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Partner SMTP configuration',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which port does the SMTP test use when no port has been specified?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The documented test procedure retrieves the port and defaults to 25 if none is specified.',
                        'options' => [
                            ['text' => '25', 'correct' => true],
                            ['text' => '465', 'correct' => false],
                            ['text' => '587', 'correct' => false],
                            ['text' => '110', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'The SMTP test connects and sends EHLO successfully, then fails. Which fields does that point at?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Authentication is the last stage of the test. Reaching it means the host and port were fine, so the fault is in the credentials.',
                        'options' => [
                            ['text' => 'SMTP Login and SMTP Password', 'correct' => true],
                            ['text' => 'SMTP Host and SMTP Port', 'correct' => false],
                            ['text' => 'SMTP Email only', 'correct' => false],
                            ['text' => 'SMTP Security only', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Add a payment in the Finances section' => [
            'docs' => 'Admin Panel → Finance',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>The Finance tab serves to monitor existing payments and add new ones. It is where a
customer's "I have paid, why am I still blocked?" gets answered.</strong></p>

<h2 id="four-windows">Four windows, four jobs</h2>

<ul>
<li><strong>Payment list</strong> — money in. Click <strong>+ add</strong> for a new payment.</li>
<li><strong>Write-offs (Expense)</strong> — the list of write-offs of the subscription fee for
    monitoring services. Money out.</li>
<li><strong>Invoices</strong> — the list of invoices for monitoring services.</li>
<li><strong>Tariffs list</strong> and <strong>Partner currencies list</strong> — what the money is
    calculated from.</li>
</ul>

<h2 id="a-payment-record">What a payment record carries</h2>

<table>
<thead>
<tr><th>Field</th><th>What it is</th></tr>
</thead>
<tbody>
<tr><td><strong>ID</strong></td><td>The single payment number.</td></tr>
<tr><td><strong>External ID</strong></td><td>External identification of the payment.</td></tr>
<tr><td><strong>Agreement</strong></td><td>The agreement number the payment was made under.</td></tr>
<tr><td><strong>Amount</strong> · <strong>Currency</strong></td><td>How much, and in what.</td></tr>
<tr><td><strong>Type</strong></td><td>The payment type.</td></tr>
<tr><td><strong>Description</strong></td><td>Additional description for the payment.</td></tr>
<tr><td><strong>Date</strong></td><td>The payment creation date.</td></tr>
<tr><td><strong>Receipt date</strong></td><td>The date the funds were received.</td></tr>
<tr><td><strong>Organization</strong></td><td>The organisation that made the payment.</td></tr>
<tr><td><strong>Partner</strong> · <strong>Subpartner</strong></td><td>Who the client belongs to. Subpartner may be empty.</td></tr>
</tbody>
</table>

<blockquote><p><strong>Date and Receipt date are two different dates</strong>, and the gap between
them is usually the answer. A payment created today for funds that arrived last week reads
differently from one where the money has not landed at all.</p></blockquote>

<h2 id="adding-one">Adding one</h2>

<p>Open the <strong>Payment list</strong> window and click <strong>+ add</strong>. Complete the
fields above — the Agreement is what ties the payment to the right account, so check it against the
contract you were given rather than against the customer's name.</p>
HTML,
            'quiz' => [
                'title' => 'Adding a payment',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which two fields on a payment record can legitimately differ, and are usually the explanation when a customer says their payment "has not gone through"?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Date is the payment creation date; Receipt date is the date of receipt of funds for the payment. A payment can exist before the money has landed.',
                        'options' => [
                            ['text' => 'Date and Receipt date', 'correct' => true],
                            ['text' => 'ID and External ID', 'correct' => false],
                            ['text' => 'Amount and Currency', 'correct' => false],
                            ['text' => 'Partner and Subpartner', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which field ties a payment to the correct account?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Agreement is documented as the agreement number under which the payment was made.',
                        'options' => [
                            ['text' => 'Agreement', 'correct' => true],
                            ['text' => 'Organization', 'correct' => false],
                            ['text' => 'External ID', 'correct' => false],
                            ['text' => 'Description', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: how do you transfer an object between contracts?' => [
            'docs' => 'Admin Panel → Vehicles → Edit',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>The short answer, in the words you would give a colleague.</strong></p>

<blockquote><p>Admin Panel → <strong>Vehicles</strong> → find the vehicle → <strong>Edit</strong> →
change the <strong>Account</strong> field → save.</p></blockquote>

<h2 id="the-detail">The two details that matter</h2>

<ul>
<li><strong>It is the Account field</strong>, not Owner and not Partner. Owner and Partner are
    columns that <em>show</em> where an object sits; Account is the field that <em>moves</em> it.</li>
<li><strong>All the entered parameters go with it.</strong> The vehicle arrives configured, so
    check the tariff afterwards — it is now billing against a different agreement.</li>
</ul>

<h2 id="verify">Verify before you close the ticket</h2>

<p>Go back to the Vehicles list and read the <strong>Agreement</strong> and <strong>Owner</strong>
columns for that Agent ID. If they name the destination contract, the transfer happened. If they do
not, it did not, whatever the form said.</p>
HTML,
            'quiz' => [
                'title' => 'Transfer, in one line',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which columns on the Vehicles list confirm that a transfer actually landed?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Agreement is the number of the agreement the object belongs to, and Owner is the name of that agreement. Together they say where the object now sits.',
                        'options' => [
                            ['text' => 'Agreement and Owner', 'correct' => true],
                            ['text' => 'Online and Last update', 'correct' => false],
                            ['text' => 'Unique and Msisdn', 'correct' => false],
                            ['text' => 'Himself and Administrator', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: which configurations control speed limits?' => [
            'docs' => null,
            'estimated_minutes' => 5,
            'needs_input' => 'None do. The documented configuration list carries no speed key, and '
                .'speeding is a notification type belonging to the Notification module. The '
                .'question as worded has no correct answer; Igor to confirm whether it should '
                .'become "how do you set up a speeding notification?" or be retired.',
            'body' => <<<'HTML'
<blockquote><p><strong>This question does not have the answer it expects.</strong></p></blockquote>

<h2 id="none">None of them</h2>

<p>The configuration keys documented under <em>Admin Panel → Account → Configuration</em> are a
short list, and speed is not on it. In full, the documented keys are: <em>Confirm adding vehicles to
notifications</em>, <em>hide_device</em>, <em>Partner Grace Period (days)</em>, <em>Address in
Online Tree</em>, <em>single_driver</em>, <em>open_in_new_tab</em>, <em>TOTP Label</em>, <em>Only
private prices</em>, <em>Main Tab</em>, <em>additional_fields</em>, <em>Low Balance Emails</em>,
<em>Maps</em>, <em>Link to manual</em> and <em>Telegram bot</em>.</p>

<h2 id="the-real-answer">The answer to give instead</h2>

<p>Speeding is a <strong>notification</strong>, not a configuration. The Notifications module
covers <em>"speeding or exceeding speed limits"</em> along with ignition on and off, fuel level
changes, refuellings and drainings, geofence entry and exit, loss of connection with the device, and
maintenance alerts. A notification is defined by the conditions that trigger it, the objects it
applies to, and how it is delivered.</p>

<p>So the useful reply to a customer is: <em>"Speed limits are set up as a notification, not as an
account setting — and the Notification module has to be active on your contract first."</em></p>

<blockquote><p><strong>Why this lesson exists at all.</strong> Being asked a question built on a
wrong assumption is normal on a support desk. The skill is noticing the assumption instead of
inventing an answer that fits it.</p></blockquote>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Answer: how do you add a manual subscription debit?' => [
            'docs' => 'Admin Panel → Finance → Write-offs (Expense)',
            'estimated_minutes' => 5,
            'body' => <<<'HTML'
<p><strong>The short answer.</strong></p>

<blockquote><p>Admin Panel → <strong>Finance</strong> → the <strong>Write-offs (Expense)</strong>
window → <strong>+ add</strong> for a manual write-off.</p></blockquote>

<h2 id="what-the-window-is">What that window is</h2>

<p>Write-offs (Expense) is the list of write-offs of the <strong>subscription fee for monitoring
services</strong> — the automatic daily charges the platform makes against an account. It can be
filtered by date to see the write-offs for a given day, which is how you check whether an account
was actually charged on the day a customer is disputing.</p>

<p>Adding one by hand puts a charge into that same list. It is the opposite operation to
<strong>+ add</strong> on the Payment list, which puts money in.</p>

<blockquote><p><strong>Know which direction you are moving money.</strong> Payment list adds credit.
Write-offs adds a charge. Both are one click called "add", in the same section, and they are not
undone by adding the other one — you will have created two records, not cancelled one.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Manual subscription debits',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which Finance window holds the subscription-fee charges, and is where a manual debit is added?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Write-offs (Expense) is documented as the list of write-offs of the subscription fee for monitoring services, with + add for a manual write-off.',
                        'options' => [
                            ['text' => 'Write-offs (Expense)', 'correct' => true],
                            ['text' => 'Payment list', 'correct' => false],
                            ['text' => 'Invoices', 'correct' => false],
                            ['text' => 'Tariffs list', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'You added a write-off to the wrong account. Does adding a payment of the same amount undo it?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It does not. A payment and a write-off are separate records in separate windows; adding one to offset the other leaves two entries on the account, both wrong.',
                        'options' => [
                            ['text' => 'No — you now have two records, not none', 'correct' => true],
                            ['text' => 'Yes, the balance nets to zero and both are removed', 'correct' => false],
                            ['text' => 'Yes, provided it is done on the same day', 'correct' => false],
                            ['text' => 'Yes, if the Receipt date is left blank', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Create a partner, transfer a contract to them, configure "Address in Online Tree"' => [
            'docs' => 'Admin Panel → Partners · Account → Configuration',
            'estimated_minutes' => 25,
            'body' => <<<'HTML'
<p><strong>The three skills of this module in one sequence, done in the training account. Everything
you need is in the lessons above.</strong></p>

<h2 id="the-task">The task</h2>

<ol>
<li><strong>Create a partner.</strong> Partners → Add → Main settings. Give it a name, an email, a
    currency and a login. Note the required fields — Login is one of them.</li>
<li><strong>Transfer a contract to them.</strong> A contract is assigned to a partner after the
    partner exists; that is the documented order.</li>
<li><strong>Set the configuration.</strong> Add <strong>Address in Online Tree = 1</strong>.</li>
</ol>

<h2 id="what-that-config-does">What that configuration actually does</h2>

<p>Setting <strong>Address in Online Tree</strong> to <strong>1</strong> displays the address in the
online tab, by adding an <strong>Address</strong> column to the object tree view. That is the
verification step: open the online tree afterwards and look for the column. If it is not there, the
configuration did not take.</p>

<h2 id="evidence">What to submit</h2>

<p>Record as you go — reconstructing this afterwards is harder than noting it at the time:</p>

<ul>
<li>the <strong>partner name</strong> you created;</li>
<li>the <strong>account / contract ID</strong> you transferred;</li>
<li>a <strong>screenshot</strong> of the online tree showing the Address column present.</li>
</ul>

<blockquote><p><strong>Work in the training account only.</strong> Partners, transfers and
configurations are live administrative changes. Everything in this task is done against the account
you were given for training, never against a customer's.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Address in Online Tree',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What does setting "Address in Online Tree" to 1 do?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'It displays the address in the online tab, by adding an Address column to the object tree view.',
                        'options' => [
                            ['text' => 'Adds an Address column to the object tree view', 'correct' => true],
                            ['text' => 'Turns on reverse geocoding for reports', 'correct' => false],
                            ['text' => 'Shows the partner\'s postal address on invoices', 'correct' => false],
                            ['text' => 'Adds the address to notification emails', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'You set the configuration and the Address column does not appear in the online tree. What does that tell you?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The column is the observable effect of the configuration. No column means the configuration has not taken on that account — which is a finding, not something to report as done.',
                        'options' => [
                            ['text' => 'The configuration has not taken — do not report the task as complete', 'correct' => true],
                            ['text' => 'The column appears only after the next daily write-off', 'correct' => false],
                            ['text' => 'The column is only visible to partner users', 'correct' => false],
                            ['text' => 'The value should have been "true" rather than 1', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module 2 — knowledge check',
        'description' => 'Objects, partners and finances, end to end. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Which field transfers a vehicle from one contract to another, carrying all its entered parameters?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Vehicles → Edit → the Account field. Owner and Partner are display columns, not the control.',
                'options' => [
                    ['text' => 'Account, on Vehicles → Edit', 'correct' => true],
                    ['text' => 'Owner, on the Vehicles list', 'correct' => false],
                    ['text' => 'Agreement, on the Finance payment record', 'correct' => false],
                    ['text' => 'Partner, on the partner form', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'An object shows blocks in both the Himself and Administrator columns. What is true?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'They are two independent blocks — one applied by the user, one by the administrator. Clearing either leaves the other in place.',
                'options' => [
                    ['text' => 'Both must be cleared before the object works again', 'correct' => true],
                    ['text' => 'Clearing the administrator block clears both', 'correct' => false],
                    ['text' => 'The Himself block always overrides the administrator one', 'correct' => false],
                    ['text' => 'They are the same block shown in two columns', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer wants to be alerted when a vehicle exceeds a speed limit. Where does that live?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Speeding is a notification type, and the Notification module has to be active on the contract. There is no speed configuration key.',
                'options' => [
                    ['text' => 'A notification, which needs the Notification module active', 'correct' => true],
                    ['text' => 'A configuration key on the account', 'correct' => false],
                    ['text' => 'A parameter on Vehicles → Edit', 'correct' => false],
                    ['text' => 'A setting on the tariff', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Where is a manual subscription debit added?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Finance → Write-offs (Expense) → + add. The Payment list is the opposite direction: money in.',
                'options' => [
                    ['text' => 'Finance → Write-offs (Expense)', 'correct' => true],
                    ['text' => 'Finance → Payment list', 'correct' => false],
                    ['text' => 'Finance → Invoices', 'correct' => false],
                    ['text' => 'Vehicles → Edit', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A partner reports that no email of any kind reaches their users. What do you check first?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'SMTP is configured per partner, on the partner\'s SMTP tab, and nothing is sent without it. The built-in test says which stage fails.',
                'options' => [
                    ['text' => 'The partner\'s SMTP tab, and run the connection test', 'correct' => true],
                    ['text' => 'The Low Balance Emails configuration key', 'correct' => false],
                    ['text' => 'Whether the objects are blocked', 'correct' => false],
                    ['text' => 'The Receipt date on their last payment', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Transfer a vehicle into a contract and put it on a tariff',
        'lesson_title' => 'Transfer an object into the created contract',
        'brief' => 'In the training account, transfer a vehicle into the contract you created, put it on a tariff, and verify both on the Vehicles list.',
        'submission_instructions' => 'Do the work in the training PILOT account. Submit the Agent ID and the destination contract ID, plus a screenshot of the Vehicles list showing that row with its Agreement, Owner and Tariff columns filled in. The screenshot is the verification step — a form you pressed save on is not evidence that the change landed.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Agent ID of the vehicle you transferred', 'hint' => 'The identifier of the monitoring object, from the Agent ID column'],
            ['key' => 'account_id', 'label' => 'Destination contract / account ID', 'hint' => 'The contract you set in the Account field'],
            ['key' => 'tariff_name', 'label' => 'Tariff now showing on the Vehicles list', 'hint' => 'Copy it exactly as the Tariff column reads it'],
        ],
    ],
];
