<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two Admin panel practical tasks were rewritten and renamed. Retire the
 * originals rather than leaving them published beside their replacements.
 *
 * `RecalculateCourseProgress` requires every published practical task to be
 * passed, so a superseded task left behind does not sit quietly — it holds the
 * course open for everybody.
 *
 * Both originals were written against facts the documentation does not carry:
 *
 *   · "Transfer vehicle and configure object tariff and speed limit" asked for
 *     a speed-control configuration. There is no speed configuration key in
 *     PILOT — speeding is a notification, belonging to the Notification module.
 *   · "Configure partner branding and low-balance email templates" asked for a
 *     low-balance email *template*. The documented mechanism is the
 *     `Low Balance Emails` configuration key; no such template is named
 *     anywhere in the documentation.
 *
 * A task with submissions against it is never removed. Somebody's marked work
 * is history, and history does not get tidied away because the wording moved
 * on — such a task is unpublished instead, so it stops gating without
 * disappearing.
 */
return new class extends Migration
{
    /** superseded slug => the slug that replaces it, for the record. */
    private const SUPERSEDED = [
        'transfer-vehicle-and-configure-object-tariff-and-speed-limit' => 'transfer-a-vehicle-into-a-contract-and-put-it-on-a-tariff',
        'configure-partner-branding-and-low-balance-email-templates' => 'set-up-a-notification-and-a-low-balance-recipient',
    ];

    public function up(): void
    {
        foreach (array_keys(self::SUPERSEDED) as $slug) {
            $task = DB::table('practical_tasks')->where('slug', $slug)->first();

            if (! $task) {
                continue;
            }

            $submissions = DB::table('practical_submissions')
                ->where('practical_task_id', $task->id)
                ->count();

            if ($submissions > 0) {
                DB::table('practical_tasks')
                    ->where('id', $task->id)
                    ->update(['is_published' => false, 'updated_at' => now()]);

                continue;
            }

            DB::table('practical_tasks')->where('id', $task->id)->delete();
        }
    }

    /**
     * Not reversed. Restoring a task that was gating the course on an invented
     * mechanism is not a restoration.
     */
    public function down(): void
    {
        //
    }
};
