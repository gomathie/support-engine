<?php

namespace Database\Seeders;

use App\Models\DiagnosticTree;
use Illuminate\Database\Seeder;

/**
 * The Support Panel's decision trees.
 *
 * Each tree is one thing a customer actually says, and the steps under it are
 * the checks a 1st-line agent works through in order. The order is the method:
 * cheap, high-yield checks first, the device last — faults live near the top of
 * these lists far more often than the bottom.
 *
 * Every step carries the layer it belongs to (1 access → 7 relay) and, where
 * useful, what it means when that step turns out to be the cause. The `fix`
 * text stays hidden until the agent marks the step as the cause, so the tree
 * stays a diagnostic exercise rather than a list of answers.
 *
 * Seed content for a first install; trainers edit it in the admin panel after.
 */
class DiagnosticTreeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->trees() as $position => $data) {
            $tree = DiagnosticTree::query()->updateOrCreate(
                ['key' => $data['key']],
                [
                    'question' => $data['question'],
                    'category' => $data['category'] ?? null,
                    'layer_label' => $data['layer_label'],
                    'description' => $data['description'] ?? null,
                    'position' => $position + 1,
                    'is_published' => true,
                ],
            );

            // Rebuilt rather than merged: step text is the identity here, and a
            // re-seed should not leave stale steps behind.
            $tree->steps()->delete();

            foreach ($data['steps'] as $stepPosition => $step) {
                $tree->steps()->create([
                    'prompt' => $step[0],
                    'layer' => $step[1],
                    'fix' => $step[2] ?? null,
                    'position' => $stepPosition + 1,
                ]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function trees(): array
    {
        return [
            // ─── ACCESS ──────────────────────────────────────
            [
                'key' => 'login',
                'category' => 'Access & rights',
                'question' => "I can't log in",
                'layer_label' => '1–2',
                'description' => 'Almost always access or contract, not the platform. Establish scope first: '
                    .'one person or everyone at that company?',
                'steps' => [
                    ['Is it just them, or their whole company?', 1,
                        'Everyone = look at the contract (layer 2). One person = look at the account (layer 1). '
                        .'Asking this first saves working the wrong layer entirely.'],
                    ['Admin → Failed Login Attempts. Are their attempts arriving at all?', 1,
                        "No attempts logged means they aren't reaching the login they think they are. "
                        .'Wrong URL, an old bookmark, a cached page, or a rebranded partner portal.'],
                    ['Check Blocked Users and Disabled Users.', 1,
                        'Unblock, then find out who blocked them and why — an account blocked by a colleague '
                        .'for a reason is not yours to quietly reverse.'],
                    ['Is the account locked out from repeated bad passwords?', 1,
                        'Lockout is time-based. Tell them the wait rather than resetting the password, '
                        .'or you hide a possible credential-stuffing attempt.'],
                    ['Password Reset history — has someone already reset it?', 1,
                        'A reset they did not action means the mail went somewhere else, or a colleague '
                        .'reset it and never told them.'],
                    ['Is 2FA enabled, and is their device or email still reachable?', 1,
                        'A new phone with no backup codes is the common one. Disabling 2FA to get somebody '
                        .'in needs the approval path, not your judgement.'],
                    ['Is the contract itself blocked for non-payment?', 2,
                        'Layer 2. Route to finance rather than fixing the login — restoring access to an '
                        .'unpaid contract is a commercial decision.'],
                    ['Has the account been moved to another partner or contract?', 2,
                        'They may now belong to a different portal with a different URL.'],
                ],
            ],

            [
                'key' => 'rights',
                'category' => 'Access & rights',
                'question' => 'A user can see objects they should not',
                'layer_label' => '1',
                'description' => 'The mirror image of the Master trap, and the more urgent one — this is a '
                    .'data exposure, so establish the scope before you start changing anything.',
                'steps' => [
                    ['Read that user\'s rights directly. Do not reason from your own view.', 1,
                        'Support logins carry far more rights than a customer\'s. Your view proves nothing '
                        .'in either direction.'],
                    ['Were the rights granted directly, or through a template?', 1,
                        'A template fault affects everyone it was applied to, not just the person who '
                        .'reported it. That widens the incident considerably.'],
                    ['Is the object in a group or tag the user has blanket rights to?', 1,
                        'Rights on a group flow to whatever is put in it later. Somebody moving a vehicle '
                        .'into the wrong group grants access without anyone touching rights.'],
                    ['Does the user hold Admin or Super Admin on the contract?', 1,
                        'Then they can see everything by design, and the conversation is about whether '
                        .'they should hold that role.'],
                    ['Check Audit history: when did the rights change, and who changed them?', 1,
                        'Establishes whether this is a mistake, a misunderstanding, or something that '
                        .'needs escalating as an incident.'],
                    ['Has the customer actually viewed the data, or only had the ability to?', 1,
                        'Both matter, but they are different conversations — and the second one has a '
                        .'notification clock attached under most data-protection regimes.'],
                    ['Escalate before removing anything if real data was exposed.', 1,
                        'Removing the rights destroys the evidence of what was reachable. Capture the '
                        .'state first — screenshots of the rights, and the audit entries.'],
                ],
            ],

            // ─── VISIBILITY ──────────────────────────────────
            [
                'key' => 'gone',
                'category' => 'Visibility',
                'question' => 'An object disappeared from my list',
                'layer_label' => '1–4',
                'description' => 'Usually still there and hidden, rather than actually gone. Work outward '
                    .'from the cheapest explanation.',
                'steps' => [
                    ['Does it exist in the admin panel at all?', 4,
                        'If not, it was deleted or moved to another contract — skip straight to the last '
                        .'two checks.'],
                    ['Does this user have rights to it? Check their rights, not your view.', 1,
                        'The Master trap. It may never have been theirs to see, and "it vanished" may mean '
                        .'"a colleague corrected the rights".'],
                    ['Is a filter, group selection or column config hiding it?', 3,
                        'The most common cause by a distance. Ask them to clear filters and select all '
                        .'groups before anything else is touched.'],
                    ['Is the list filtered to "in motion" or a similar status?', 3,
                        'A parked vehicle drops out of a motion-filtered list and looks deleted.'],
                    ['Is Temporary blocking switched on?', 4,
                        'Not a fault. A colleague may have suspended it to stop the subscription charge — '
                        .'seasonal equipment does this every year.'],
                    ['Has the object exceeded its tariff or lost its subscription?', 4,
                        'Layer 2 commercially, layer 4 in the object card. Route to finance rather than '
                        .'re-enabling it yourself.'],
                    ['Was it moved to another contract, or deleted?', 4,
                        'Deleting removes all data and history and cannot be undone from the panel. '
                        .'Restoring needs a system administrator, and only from a backup.'],
                ],
            ],

            [
                'key' => 'position',
                'category' => 'Visibility',
                'question' => 'The vehicle jumps around the map',
                'layer_label' => '3–6',
                'description' => 'Either the positions really are bad, or the display is drawing good '
                    .'positions badly. Sensors tracing settles which.',
                'steps' => [
                    ['Open Sensors tracing. What is the satellite count on the jumping points?', 6,
                        'Under about four satellites the fix is unreliable. Urban canyons, underground '
                        .'parking and dense tree cover all do this, and none of it is a platform fault.'],
                    ['Do the jumps cluster at one location?', 6,
                        'A repeated jump at the same depot or tunnel is environmental. Nothing to fix in '
                        .'the platform; worth telling the customer plainly.'],
                    ['Is the jump to (0, 0) or another null island position?', 6,
                        'The device is reporting with no valid fix. A filter on invalid coordinates is the '
                        .'fix, not a device swap.'],
                    ['Check the interval between the jumping points.', 6,
                        'Points minutes apart across a city are a lost-then-reacquired fix, not teleporting. '
                        .'Points seconds apart are a real data quality problem.'],
                    ['Is data arriving out of order, with timestamps interleaved?', 6,
                        'A device that buffered offline and flushed on reconnection replays old positions. '
                        .'The track is drawn in the order received, not the order recorded.'],
                    ['Is the track filter or smoothing configured for this object type?', 5,
                        'Filtering thresholds tuned for a truck behave badly on an asset that moves slowly '
                        .'or sits still for days.'],
                    ['Does the raw data look clean while only the map looks wrong?', 3,
                        'Then it is the display: check the date range, the map layer, and whether more than '
                        .'one object is selected.'],
                ],
            ],

            // ─── DATA QUALITY ────────────────────────────────
            [
                'key' => 'numbers',
                'category' => 'Data quality',
                'question' => 'The fuel or mileage numbers are wrong',
                'layer_label' => '5–6',
                'description' => 'Work in the order the data flows: device → mapping → formula → calibration. '
                    .'Debugging out of order wastes the most time of any fault on this list.',
                'steps' => [
                    ['Open Sensors tracing. Is data arriving, and what is the raw value?', 6,
                        'This one step decides everything below it. Do it before forming any theory.'],
                    ['Raw value wrong or absent?', 6,
                        'Layer 6 — the device or the installation. Stop tuning formulas; nothing downstream '
                        .'can fix bad input.'],
                    ['Raw value fine but display wrong → check the field mapping first.', 5,
                        'The sensor may be reading the wrong parameter entirely.'],
                    ['Then the conversion formula — before the calibration table.', 5,
                        'The formula runs the moment data arrives, before calibration. Debugging the table '
                        .'while a formula mangles its input is wasted time. Use the Test button rather than '
                        .'reasoning about it.'],
                    ['Then the calibration table: "Select nearest value" and "Calibration first".', 5,
                        'These two switches quietly change results and are a common cause of "close but wrong".'],
                    ['Numbers intermittently missing rather than wrong? Look for a division in the formula.', 5,
                        'If the divisor can arrive as 0 or undefined the calculation errors and the point is '
                        .'dropped. Guard it with a conditional.'],
                    ['For fuel: are drops sharp, or gradual over hours?', 5,
                        'Sharp drops at a stop are a possible theft or a real refuel mis-signed. Gradual drift '
                        .'while parked is usually temperature affecting a float sensor.'],
                    ['For mileage: is it from GPS, odometer or CAN?', 5,
                        'The three disagree by design. GPS mileage is always slightly over; CAN matches the '
                        .'dashboard. Confirm which the customer expects before calling anything wrong.'],
                    ['Was it ever right? Check Audit history for a change on that date.', 5,
                        'Never right = configuration. Recently wrong = a change was made, or the device moved.'],
                    ['After fixing: run Recalculate for the affected period.', 5,
                        "Otherwise the customer's historical reports stay wrong and they call back. "
                        .'Saying "fixed" without it earns the callback.'],
                ],
            ],

            [
                'key' => 'temperature',
                'category' => 'Data quality',
                'question' => 'The reefer temperature readings look wrong',
                'layer_label' => '5–6',
                'description' => 'Treat this as urgent regardless of how it is reported. There is a physical '
                    .'clock on refrigerated cargo, and a sensor fault here spoils a load.',
                'steps' => [
                    ['Is cargo at risk right now? If so, raise the priority before diagnosing further.', 6,
                        'A temperature fault on a loaded reefer is a P2 at least, whatever the impact table '
                        .'says on paper. Tell the customer to verify physically while you work.'],
                    ['Sensors tracing: is the probe reporting at all, and how recently?', 6,
                        'A silent probe reads as its last value for ever on some displays, which looks like '
                        .'a stable temperature rather than a dead sensor.'],
                    ['Is the value pinned at a limit — −40, 0, 85?', 6,
                        'A rail value is a disconnected or shorted probe, not a real reading.'],
                    ['Are there several probes, and is the report using the one they mean?', 5,
                        'Multi-compartment trailers routinely have the compartments mapped in a different '
                        .'order from how the customer numbers them.'],
                    ['Check the formula and any offset applied to the probe.', 5,
                        'An offset entered to correct a previous fault, then left in place after the probe '
                        .'was replaced, gives a consistent error.'],
                    ['Compare against the reefer unit\'s own display if the customer can reach the vehicle.', 6,
                        'Settles platform-versus-hardware in one step, and is worth the wait.'],
                    ['Is the alert threshold set for the cargo actually being carried?', 2,
                        'Thresholds set for frozen goods are silent on a chilled load. Not a fault, but it '
                        .'is why nobody was warned.'],
                ],
            ],

            // ─── NOTIFICATIONS ───────────────────────────────
            [
                'key' => 'notif',
                'category' => 'Notifications & events',
                'question' => "I'm not getting notifications",
                'layer_label' => '2',
                'description' => 'Nine checks, and the time window catches more of these than the other '
                    .'eight combined. Resist testing delivery until the rule itself is confirmed sound.',
                'steps' => [
                    ['Is the Notifications module active on the contract?', 2,
                        "Without it there is no Notifications tab at all, and nothing was ever configured."],
                    ['Does the notification actually exist, and is it enabled?', 2,
                        'Somebody may have disabled it rather than deleting it.'],
                    ['Is the object or tag selected in it?', 2,
                        'A vehicle added to the fleet later is not covered automatically. This is the second '
                        .'most common cause after the time window.'],
                    ['Check the time window — time zone, days, intervals.', 2,
                        'A rule set for weekdays 09:00–18:00 is silent on a Saturday and looks broken. '
                        .'The single most-missed cause on this list.'],
                    ['Check the geofence scope: anywhere, inside selected zones, or outside them.', 2,
                        'An inverted scope fires constantly or never, and both get reported as "not working".'],
                    ['Check the "for all users" flag.', 2,
                        'If off, colleagues do not get it — exactly the shape of "my manager gets them and '
                        .'I do not".'],
                    ['Check the delivery method: SMS, push, email, alert, control room, webhook, command, Telegram.', 2,
                        'Each fails differently. Establish which one they expect before testing.'],
                    ['For email: is the address verified?', 2,
                        'Unverified addresses silently receive nothing. No error is shown anywhere.'],
                    ['Spam folder, and any corporate mail filter.', 2,
                        'Automated mail to a whole fleet office is exactly what a filter quarantines.'],
                    ['Did the underlying event even occur? Check the event list for the period.', 2,
                        'If the event never fired, the notification is innocent and the fault is in the '
                        .'sensor or geofence that should have raised it.'],
                ],
            ],

            [
                'key' => 'geofence',
                'category' => 'Notifications & events',
                'question' => 'Geofence entry and exit events are not firing',
                'layer_label' => '2–5',
                'description' => 'Split the question in two: is the event not being generated, or is it '
                    .'generated and not delivered? The event list answers it immediately.',
                'steps' => [
                    ['Check the event list for the period. Are entry/exit events being generated at all?', 2,
                        'Events present but no notification is a delivery problem — switch to the '
                        .'notifications tree instead.'],
                    ['Is the geofence assigned to this object or its group?', 2,
                        'Zones are commonly drawn and then never attached to anything.'],
                    ['Is the zone big enough for the reporting interval?', 5,
                        'A vehicle sending every two minutes at motorway speed can pass through a small zone '
                        .'entirely between two points. The zone has to be larger than the distance covered '
                        .'between reports.'],
                    ['Does the zone have the right type — polygon, circle, corridor?', 5,
                        'A corridor along a road behaves very differently from a circle around it.'],
                    ['Check for a minimum dwell time on the zone.', 2,
                        'A dwell requirement suppresses events for vehicles passing straight through, '
                        .'which is often the intent and often forgotten.'],
                    ['Are the points near the boundary accurate? Check satellite count.', 6,
                        'A poor fix near an edge produces phantom entries and exits, so customers sometimes '
                        .'raise the threshold and then wonder why real crossings are missed.'],
                    ['Was the zone edited recently? Check Audit history.', 5,
                        'A redrawn boundary that now excludes the gate is the usual story.'],
                ],
            ],

            // ─── REPORTING ───────────────────────────────────
            [
                'key' => 'reports',
                'category' => 'Reporting',
                'question' => 'My report is empty, or the totals look wrong',
                'layer_label' => '3–5',
                'description' => 'Most of these are parameters rather than data. Confirm the report can '
                    .'possibly contain what they expect before looking at the data behind it.',
                'steps' => [
                    ['Check the date range and the time zone on the report.', 3,
                        'A range in the wrong time zone quietly clips the start and end of a shift, which '
                        .'shows up as missing trips at exactly the times that matter.'],
                    ['Is the right object, group or driver selected?', 3,
                        'An empty report is far more often an empty selection than an empty database.'],
                    ['Does the object have data for that period at all? Check the track.', 6,
                        'If the vehicle was not reporting then, the report is correct and the conversation '
                        .'is about the gap, not the report.'],
                    ['Which report template is it, and what does that template actually count?', 5,
                        'Mileage-and-stops, trips, and shifts count differently by design. Two "wrong" '
                        .'totals are often two correct answers to different questions.'],
                    ['Check the movement thresholds — minimum stop duration, minimum trip distance.', 5,
                        'These decide what counts as a stop. A depot shunt is a trip under one setting and '
                        .'noise under another.'],
                    ['Are the sensors the report depends on configured on this object?', 5,
                        'An ignition-based report on an object with no ignition sensor falls back to '
                        .'movement and disagrees with the driver.'],
                    ['Was the configuration changed during the reporting period?', 5,
                        'A mid-period change gives a report that is half right, which is harder to spot '
                        .'than one that is entirely wrong.'],
                    ['Has Recalculate been run since the last configuration fix?', 5,
                        'Historical reports keep the old numbers until it has. This is the cause of most '
                        .'"you said you fixed it" callbacks.'],
                ],
            ],

            [
                'key' => 'driver',
                'category' => 'Reporting',
                'question' => 'The driver is not being identified on trips',
                'layer_label' => '2–6',
                'description' => 'Driver identification depends on a module, a device, a key and an '
                    .'assignment. Check that they all exist before suspecting any of them.',
                'steps' => [
                    ['Is the Drivers module active on the contract?', 2,
                        'Without it, driver fields exist but are never populated.'],
                    ['Does the driver record exist, with a key or card number on it?', 4,
                        'A driver created without an identifier can never be matched to anything.'],
                    ['Sensors tracing: is the reader sending the key value at all?', 6,
                        'No value means the reader, its wiring or its power — nothing in the platform can '
                        .'compensate.'],
                    ['Does the reported key value match the one on the driver record, character for character?', 4,
                        'Leading zeros, hex versus decimal, and reversed byte order all produce a value that '
                        .'looks right and matches nothing.'],
                    ['Is the driver assigned to this object, or to a group that includes it?', 4,
                        'Some setups bind drivers to vehicles; a driver in the wrong pool is never considered.'],
                    ['Are trips being attributed to the previous driver instead?', 5,
                        'A missing logout can carry a driver across shifts. Check whether the setup expects '
                        .'an explicit logout or a timeout.'],
                    ['Is the customer using one key across several vehicles simultaneously?', 4,
                        'Then attribution is ambiguous by definition, and the answer is a conversation about '
                        .'process, not a configuration change.'],
                ],
            ],

            // ─── DEVICE ──────────────────────────────────────
            [
                'key' => 'nodata',
                'category' => 'Device & connectivity',
                'question' => 'No data from a vehicle',
                'layer_label' => '4–6',
                'description' => 'Establish "never worked" versus "stopped working" early — they are two '
                    .'completely different investigations.',
                'steps' => [
                    ['Has it ever reported?', 6,
                        'Never = configuration or installation, and the installer owns it. Stopped = power, '
                        .'connection, or hardware. This question splits the tree.'],
                    ['Sensors tracing — when was the last reception, and what was the satellite count?', 6,
                        'Last reception timestamp is the single most useful number on this screen.'],
                    ['Connection Lost report — when, for how long, and at what address?', 6,
                        'Repeated drops at the same location is an installation or coverage story, not a '
                        .'platform one.'],
                    ['Is Temporary blocking on?', 4,
                        'Free to check, not a fault, and embarrassing to miss after an hour of work.'],
                    ['Does the device ID or IMEI match the physical unit? Is the device type right?', 4,
                        'A transposed digit gives a device that connects and is silently discarded.'],
                    ['Is the SIM active, in credit, and is data roaming allowed where the vehicle is?', 6,
                        'Cross-border haulage stops reporting exactly at the border. Route to whoever owns '
                        .'the SIM estate.'],
                    ['Is the vehicle parked somewhere with no coverage — underground, a container, a workshop?', 6,
                        'Ask where the vehicle physically is before escalating anything.'],
                    ['Did the last reported points show falling external voltage?', 6,
                        'A dying battery or a disconnected feed announces itself in the voltage trace before '
                        .'the device goes quiet.'],
                ],
            ],

            [
                'key' => 'command',
                'category' => 'Device & connectivity',
                'question' => 'A command I sent never reached the device',
                'layer_label' => '2–6',
                'description' => 'Commands are queued, not instant. Most of these are a device that is '
                    .'simply not connected right now.',
                'steps' => [
                    ['Is the command queued, sent, or acknowledged? Check its status.', 6,
                        'These three mean very different things, and the customer usually reports all three '
                        .'as "it did not work".'],
                    ['Is the device online right now?', 6,
                        'A queued command waits for the next connection. On a parked vehicle that can be '
                        .'days, and nothing is wrong.'],
                    ['Does this device model support this command?', 4,
                        'An unsupported command is accepted by the platform and ignored by the device, '
                        .'with no error anywhere.'],
                    ['Is the command syntax and its parameters correct for this firmware?', 4,
                        'Firmware revisions change parameter order. A command that worked on the last batch '
                        .'of devices may not on this one.'],
                    ['Does the user have rights to send commands on this object?', 1,
                        'Rights failures here are usually silent in the interface.'],
                    ['For an engine block or similar: is the relay physically installed and wired?', 6,
                        'The platform reports the command as delivered because it was. Whether anything is '
                        .'connected to the output is an installation question.'],
                    ['Has the command been sent repeatedly and queued several times over?', 6,
                        'A customer retrying will find every copy executes at once on reconnection. Clear '
                        .'the queue before it does.'],
                ],
            ],

            // ─── INTEGRATION ─────────────────────────────────
            [
                'key' => 'relay',
                'category' => 'Integration',
                'question' => "Data is fine in PILOT but missing in our own system",
                'layer_label' => '7',
                'description' => 'Layer 7. The Statistics panel tells you which side is at fault in one '
                    .'glance — read it before anything else.',
                'steps' => [
                    ['Is the endpoint enabled, and is object relay enabled for this object?', 7,
                        'Both switches exist, and both are commonly half-set.'],
                    ['External ID, Host, Port, Format, Path, credentials — all correct?', 7,
                        'Check them against what the receiving side expects, not against what looks '
                        .'plausible.'],
                    ['Statistics: is Queue growing?', 7,
                        'Packets are accumulating and cannot be delivered. The external server is '
                        .'unavailable, settings are wrong, or the far side is refusing the connection.'],
                    ['Statistics: is Err climbing?', 7,
                        'Check availability, authentication, protocol, schedule, and any IP restrictions on '
                        .'the receiving side.'],
                    ['Statistics: Send rising but Ack stuck at zero?', 7,
                        'We are transmitting and they never confirm. Protocol mismatch, External ID, JSON '
                        .'config, or credentials.'],
                    ['New object not relaying?', 7,
                        'Is an Auto add rule enabled for that contract, and was the object created after '
                        .'the rule? Objects created before it are not picked up retrospectively.'],
                    ['No statistics at all?', 7,
                        'Usually benign — nothing sent yet, the period is too short, or the endpoint is new.'],
                    ['Is the customer looking at the right period on their side?', 7,
                        'Relay delivers going forward. Data from before the endpoint was configured was '
                        .'never sent and will not backfill.'],
                    ['Does their system expect a field we are not configured to send?', 7,
                        'Route to integrations with a sample payload rather than guessing at the mapping.'],
                ],
            ],
        ];
    }
}
