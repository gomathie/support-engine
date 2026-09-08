<?php

namespace App\Actions\Competency;

use App\Enums\QuestionType;
use App\Models\LevelRequirement;
use App\Models\QuizQuestion;
use App\Models\Refresher;
use App\Models\RefresherAnswer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Draws the five questions and opens the refresher.
 *
 * The questions are chosen **now**, not when the refresher was scheduled three
 * months ago. In between, the bank will have been corrected, extended and had
 * bad items retired — a refresher pinned to a ninety-day-old selection would
 * ask about the version of the material nobody teaches any more, and could
 * point at questions since deleted.
 *
 * Re-entrant: opening an already-started refresher returns the same five
 * questions rather than re-drawing them. A trainee who reloads the page mid-way
 * gets their own paper back.
 */
class StartRefresher
{
    /**
     * @return Collection<int, RefresherAnswer> the paper, in order
     */
    public function handle(Refresher $refresher): Collection
    {
        return DB::transaction(function () use ($refresher): Collection {
            $existing = $refresher->answers()->with('question.options')->get();

            if ($existing->isNotEmpty()) {
                return $existing;
            }

            $questions = $this->draw($refresher);

            foreach ($questions as $position => $question) {
                RefresherAnswer::query()->create([
                    'refresher_id' => $refresher->getKey(),
                    'quiz_question_id' => $question->getKey(),
                    'position' => $position + 1,
                    'is_correct' => false,
                    'points_awarded' => 0,
                ]);
            }

            $refresher->forceFill(['started_at' => $refresher->started_at ?? now()])->save();

            return $refresher->answers()->with('question.options')->get();
        });
    }

    /**
     * Five questions from the passed level's bank.
     *
     * "The passed level's bank" is every question on every published quiz
     * belonging to the courses that (level, area) pair requires. Practicals and
     * written answers are excluded deliberately — see below.
     *
     * @return Collection<int, QuizQuestion>
     */
    public function draw(Refresher $refresher): Collection
    {
        $award = $refresher->traineeLevel;

        $courseIds = LevelRequirement::query()
            ->where('level_id', $award->level_id)
            ->where('competency_area_id', $award->competency_area_id)
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return QuizQuestion::query()
            ->whereHas('quiz', fn ($q) => $q
                ->whereIn('course_id', $courseIds)
                ->where('is_published', true))

            /*
             * Auto-marked questions only.
             *
             * A refresher is three minutes of somebody's Tuesday, run against
             * every trainee who holds a level, twice each. Putting a written
             * answer in one would land a trainer with a marking queue that
             * grows with headcount and never ends — and KPI 7 already watches
             * trainer workload as a burnout signal. Nothing here is worth that.
             */
            ->whereIn('type', [
                QuestionType::SingleChoice->value,
                QuestionType::MultipleChoice->value,
                QuestionType::TrueFalse->value,
            ])

            ->inRandomOrder()
            ->limit(Refresher::QUESTION_COUNT)
            ->get();
    }
}
