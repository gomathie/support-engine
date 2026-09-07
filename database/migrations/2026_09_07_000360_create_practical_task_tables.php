<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Practical tasks and the rubric they are marked against (PA-14).
 *
 * This is the piece that separates "watched the lesson" from "can do the job" —
 * the Apply and Analyze end of §4.1's Bloom mapping, which a multiple-choice
 * question cannot reach.
 *
 * Kept apart from quizzes on purpose. §3's progression puts the practical task
 * before the exam and marks it differently: not points out of a maximum, but
 * four criteria with descriptors and a threshold rule. Bolting that onto
 * QuizQuestion would have meant a question type whose scoring shares nothing
 * with the others.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practical_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // Optional: a task may hang off one lesson, or stand for the course.
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->string('slug');

            // Sanitised HTML, same allowlist as lesson content.
            $table->longText('brief');

            $table->text('submission_instructions')->nullable();

            // What the trainee must produce. Free text, because "a screenshot of
            // the sensor tab plus your reasoning" is not a schema.
            $table->text('expected_evidence')->nullable();

            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(false);

            /*
             * Calibration (§4.3): the first submissions on a task are marked
             * independently by two trainers and reconciled. Set per task so the
             * requirement can be lifted once the standard is settled, rather
             * than being a permanent double-marking burden.
             */
            $table->boolean('requires_second_marker')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'is_published']);
            $table->index('lesson_id');
        });

        Schema::create('practical_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practical_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('attempt_number')->default(1);

            // draft | submitted | graded | returned
            $table->string('status', 20)->default('draft');

            $table->longText('body')->nullable();

            $table->timestamp('submitted_at')->nullable();

            // The reconciled outcome. Null until enough gradings exist —
            // with a second marker required, one grading is not an outcome.
            $table->unsignedTinyInteger('total_score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('finalised_at')->nullable();

            // Set when a grader sends it back for another go rather than
            // failing it outright.
            $table->text('returned_reason')->nullable();

            $table->timestamps();

            $table->unique(['practical_task_id', 'user_id', 'attempt_number'], 'practical_submission_attempt_unique');
            $table->index(['user_id', 'status']);
            $table->index(['practical_task_id', 'status']);
        });

        /*
         * Evidence files. Same private-disk rule as lesson videos and resources:
         * no public URL, reached only through a policy-checked controller.
         */
        Schema::create('practical_submission_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practical_submission_id')->constrained()->cascadeOnDelete();

            $table->string('disk', 30)->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamps();

            $table->index('practical_submission_id');
        });

        /*
         * One grader's assessment of one submission.
         *
         * A row per grader rather than columns on the submission, because §4.3
         * requires two trainers to mark the first submissions independently and
         * then reconcile. Independent means neither can see the other's marks
         * until both are in, which needs them stored separately.
         */
        Schema::create('practical_gradings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practical_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grader_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('total_score');
            $table->boolean('passed');

            // Overall remarks, separate from the per-criterion comments.
            $table->text('summary')->nullable();

            $table->timestamp('graded_at');
            $table->timestamps();

            // One assessment per grader per submission. Revising means updating
            // the row, which keeps "who marked this" unambiguous.
            $table->unique(['practical_submission_id', 'grader_id'], 'practical_grading_unique');
        });

        Schema::create('practical_grading_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practical_grading_id')->constrained()->cascadeOnDelete();

            $table->string('criterion', 30);          // App\Enums\RubricCriterion
            $table->unsignedTinyInteger('score');     // 0–4

            // Mandatory at 2 or below — enforced in the action, because "why did
            // this fail" is the only part of a rubric a trainee can learn from.
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->unique(['practical_grading_id', 'criterion'], 'practical_score_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practical_grading_scores');
        Schema::dropIfExists('practical_gradings');
        Schema::dropIfExists('practical_submission_files');
        Schema::dropIfExists('practical_submissions');
        Schema::dropIfExists('practical_tasks');
    }
};
