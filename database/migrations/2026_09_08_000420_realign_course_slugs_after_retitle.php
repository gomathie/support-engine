<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bring course slugs back in line with their titles, and remove the duplicates
 * the mismatch produced.
 *
 * `2026_09_07_000380` dropped the time caps from the course titles
 * ("1st-line support — 2 week plan" → "1st-line support") but left the slugs
 * alone, on the reasoning that a slug is a URL and changing it breaks links.
 *
 * That was half right and wholly wrong in effect. `TrainingContentSeeder` keys
 * on `slug => Str::slug($title)`, so once the data file carried the new title
 * the seeder matched nothing and **created a second course** — same title, same
 * category, no enrolments, quietly sitting alongside the real one. Nobody would
 * have noticed until two "1st-line support" cards appeared on a dashboard.
 *
 * The slug is realigned so the seeder is idempotent again, and the empty
 * duplicates are removed. A duplicate is only deleted when it holds nothing:
 * no enrolments, no progress, no attempts, no certificates. Anything with a
 * trace of a real person is left for a human.
 */
return new class extends Migration
{
    /** slug as it was => the title it should now derive from */
    private const REALIGN = [
        '1st-line-support-2-week-plan' => '1st-line-support',
        'admin-panel-3-day-plan' => 'admin-panel',
    ];

    public function up(): void
    {
        foreach (self::REALIGN as $oldSlug => $newSlug) {
            $original = DB::table('courses')->where('slug', $oldSlug)->first();

            if (! $original) {
                continue;
            }

            $this->removeEmptyDuplicate($newSlug, $original->id);

            // Only take the name if nothing else still holds it.
            if (! DB::table('courses')->where('slug', $newSlug)->exists()) {
                DB::table('courses')
                    ->where('id', $original->id)
                    ->update(['slug' => $newSlug, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::REALIGN as $oldSlug => $newSlug) {
            DB::table('courses')
                ->where('slug', $newSlug)
                ->update(['slug' => $oldSlug, 'updated_at' => now()]);
        }
    }

    /**
     * Delete the accidental copy, but only if it is genuinely untouched.
     *
     * Lessons, topics and quizzes cascade from the course, so they go with it —
     * that is correct for a duplicate nobody has used, and exactly why the
     * emptiness check has to be thorough first.
     */
    private function removeEmptyDuplicate(string $slug, int $keepId): void
    {
        $duplicate = DB::table('courses')
            ->where('slug', $slug)
            ->where('id', '!=', $keepId)
            ->first();

        if (! $duplicate) {
            return;
        }

        $isUntouched = ! DB::table('course_enrollments')->where('course_id', $duplicate->id)->exists()
            && ! DB::table('course_progress')->where('course_id', $duplicate->id)->exists()
            && ! DB::table('quiz_attempts')->where('course_id', $duplicate->id)->exists()
            && ! DB::table('certificates')->where('course_id', $duplicate->id)->exists();

        if (! $isUntouched) {
            return;
        }

        DB::table('courses')->where('id', $duplicate->id)->delete();
    }
};
