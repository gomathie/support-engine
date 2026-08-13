<?php

namespace Database\Seeders;

use App\Models\DiagnosticTree;
use Illuminate\Database\Seeder;

/**
 * The Support Panel's decision trees, carried over from the TREES const in
 * pages/support-panel/script.js. Editable in the admin panel from here on.
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
                    'layer_label' => $data['layer_label'],
                    'position' => $position + 1,
                    'is_published' => true,
                ],
            );

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
            [
                'key' => 'login',
                'question' => "I can't log in",
                'layer_label' => '1–2',
                'steps' => [
                    ['Admin → Failed Login Attempts. Are their attempts arriving at all?', 1,
                        "No attempts logged = they aren't reaching the login they think they are. Wrong URL, wrong portal, or a cached page."],
                    ['Blocked Users and Disabled Users.', 1,
                        'Unblock, or find out who blocked them and why.'],
                    ['Password Reset history — someone may have reset it already.', 1],
                    ['Is the contract itself blocked for non-payment?', 2,
                        'Layer 2. Route to finance rather than fixing the login.'],
                    ['Is it just them, or their whole company?', 1,
                        'Everyone = layer 2. One person = layer 1.'],
                ],
            ],
            [
                'key' => 'gone',
                'question' => 'An object disappeared from my list',
                'layer_label' => '1–4',
                'steps' => [
                    ['Does it exist in the admin panel at all?', 4],
                    ['Does this user have rights to it? Check their rights, not your view.', 1,
                        'Master trap. It may never have been theirs to see.'],
                    ['Is a filter, group selection or column config hiding it?', 3],
                    ['Is Temporary blocking switched on?', 4,
                        'Not a fault. A colleague may have suspended it to stop the subscription charge — seasonal equipment does this.'],
                    ['Was it moved to another contract, or deleted?', 4,
                        'Deleting removes all data and history. Restoring needs a system administrator.'],
                ],
            ],
            [
                'key' => 'numbers',
                'question' => 'The fuel / mileage numbers are wrong',
                'layer_label' => '5–6',
                'steps' => [
                    ['Open Sensors tracing. Is data arriving, and what is the raw value?', 6,
                        'This one step decides everything below it.'],
                    ['Raw value wrong or absent?', 6, 'Layer 6 — the device. Stop tuning formulas.'],
                    ['Raw value fine but display wrong → check the field mapping first.', 5],
                    ['Then the conversion formula — before the calibration table.', 5,
                        'The formula runs the moment data arrives, before calibration. Debugging the table while a formula mangles its input is wasted time. Use the Test button rather than reasoning about it.'],
                    ['Then the calibration table: Select nearest value, and Calibration first.', 5,
                        'These two switches quietly change results — a common cause of "close but wrong".'],
                    ['Numbers intermittently missing rather than wrong? Look for a division in the formula.', 5,
                        'If the divisor can arrive as 0 or undefined the calculation errors. Guard it with a conditional.'],
                    ['Was it ever right? Check Audit history for a change on that date.', 5,
                        'Never right = configuration. Recently wrong = a change, or the device.'],
                    ['After fixing: run Recalculate for the affected period.', 5,
                        "Otherwise the customer's historical reports stay wrong and they call back."],
                ],
            ],
            [
                'key' => 'notif',
                'question' => "I'm not getting notifications",
                'layer_label' => '2',
                'steps' => [
                    ['Is the Notifications module active on the contract?', 2,
                        "Without it there's no Notifications tab at all."],
                    ['Does the notification actually exist?', 2],
                    ['Is the object or tag selected in it?', 2,
                        "A vehicle added later isn't covered automatically."],
                    ['Check the time window — time zone, days, intervals.', 2,
                        'A rule set for weekdays 09:00–18:00 is silent on a Saturday and looks broken. The most-missed cause.'],
                    ['Check the geofence scope: anywhere / in selected zones / outside them.', 2],
                    ['Check the "for all users" flag.', 2,
                        'If off, colleagues don\'t get it — exactly the shape of "my manager gets them and I don\'t".'],
                    ['Check the delivery method: SMS, push, email, alert, control room, webhook, command, Telegram.', 2],
                    ['For email: is the address verified?', 2,
                        'Unverified addresses silently receive nothing.'],
                    ['Spam folder.', 2],
                ],
            ],
            [
                'key' => 'nodata',
                'question' => 'No data from a vehicle',
                'layer_label' => '4–6',
                'steps' => [
                    ["Sensors tracing — when was the last reception? What's the satellite count?", 6],
                    ['Connection Lost report — when, how long, and at what address?', 6,
                        'Repeated drops at the same location is an installation or coverage story, not a platform one.'],
                    ['Is Temporary blocking on?', 4, 'Free to check, and not a fault.'],
                    ['Does the device ID / IMEI match the physical unit? Is the device type right?', 4],
                    ['Has it ever reported?', 6,
                        'Never = configuration or installation. Stopped = power, connection, or hardware.'],
                ],
            ],
            [
                'key' => 'relay',
                'question' => "Data's fine in PILOT but missing in our own system",
                'layer_label' => '7',
                'steps' => [
                    ['Is the endpoint enabled, and is object relay enabled?', 7],
                    ['External ID, Host, Port, Format, Path, credentials — all correct?', 7],
                    ['Statistics: is Queue growing?', 7,
                        "Packets are accumulating and can't be delivered. The external server is unavailable, settings are wrong, or the far side is refusing the connection."],
                    ['Statistics: is Err climbing?', 7,
                        'Check availability, authentication, protocol, schedule, and any restrictions on the receiving side.'],
                    ['Statistics: Send rising but Ack stuck at zero?', 7,
                        "We're transmitting and they never confirm. Protocol, External ID, JSON config, credentials."],
                    ['New object not relaying?', 7,
                        'Is an Auto add rule enabled for that contract, and was the object created after the rule?'],
                    ['No statistics at all?', 7,
                        'Usually benign — nothing sent yet, period too short, or the endpoint is new.'],
                ],
            ],
        ];
    }
}
