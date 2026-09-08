<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spaced repetition (PA-18): a five-question refresher at 30 and 90 days.
 *
 * §2 of the plan: "a 5-question refresher at 30 and 90 days, drawn from the
 * passed level's bank. Feeds the 90-day retention KPI." Nothing measures
 * whether training *stuck* until this exists — KPI 6 has been sitting at "not
 * measurable" for want of a second measurement to compare against.
 *
 * **Refreshers are not quizzes, on purpose.** They could have been modelled as
 * `Quiz` rows and reused the whole engine, and that would have been a mistake:
 * a course-scoped quiz with no module and no lesson *is* the course's final
 * exam as far as `Course::finalQuizzes()`, `RecalculateCourseProgress` and
 * `QuizPolicy` are concerned, so every refresher would silently have re-gated
 * a course the trainee finished three months earlier. Three separate bugs of
 * exactly that shape were fixed earlier the same day. These tables keep
 * refreshers out of every gating query by construction.
 *
 * They do share the question bank. `refresher_answers.quiz_question_id` points
 * at the real questions, with the same `restrictOnDelete` as `quiz_answers`:
 * deleting a question must not rewrite the history of anything graded against
 * it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refreshers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The award being refreshed. Cascades: a deleted award has nothing
            // left to refresh, and the retention figure would be meaningless.
            $table->foreignId('trainee_level_id')->constrained()->cascadeOnDelete();

            // 30 or 90. Stored as a number rather than an enum so a third
            // interval is a seeder change rather than a migration.
            $table->unsignedSmallInteger('interval_days');

            $table->timestamp('due_at')->index();

            /*
             * The score of the sitting that earned the level, captured when the
             * refresher is scheduled rather than read back later.
             *
             * KPI 6 is "refresher score at day 90 vs original exam score". If
             * the original were looked up at measurement time, a re-sat exam or
             * a revised paper would move the baseline underneath the
             * comparison, and retention would change without anybody's
             * knowledge changing. Null when the award has no attempt behind it
             * — a hand-granted level — in which case there is nothing to
             * compare and the refresher still runs, unscored against a
             * baseline.
             */
            $table->decimal('baseline_score', 5, 2)->nullable();

            // scheduled | completed | missed
            $table->string('status', 20)->default('scheduled')->index();

            $table->decimal('score', 5, 2)->nullable();

            // score ÷ baseline × 100. Stored rather than derived so the KPI
            // reads one column and the figure survives a baseline that was
            // never captured.
            $table->decimal('retention', 5, 2)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // One 30-day and one 90-day refresher per award, ever. Scheduling
            // runs on every progress recalculation, so this is what makes it
            // idempotent rather than a growing pile.
            $table->unique(['trainee_level_id', 'interval_days']);

            $table->index(['user_id', 'status']);
        });

        Schema::create('refresher_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('refresher_id')->constrained()->cascadeOnDelete();

            // Restricted, like quiz_answers, and for the same reason.
            $table->foreignId('quiz_question_id')->constrained()->restrictOnDelete();

            $table->jsonb('selected_option_ids')->nullable();

            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('points_awarded')->default(0);

            // The order the questions were put in front of the trainee.
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['refresher_id', 'quiz_question_id']);
            $table->index(['refresher_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresher_answers');
        Schema::dropIfExists('refreshers');
    }
};
