<?php

namespace App\Actions\Reporting;

use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The success metrics from §7 of the competency plan.
 *
 * Completion rate is deliberately absent. It is what the old model already
 * optimised for and it measures nothing — that omission is the point of this
 * screen, not an oversight.
 *
 * Four of the nine KPIs cannot be computed from anything the platform records
 * today. They are returned as `notMeasurable()` with the reason attached rather
 * than quietly dropped: a dashboard that shows five of nine metrics and says
 * nothing about the other four looks complete when it is not.
 */
class CalculateKpis
{
    /** @return Collection<int, array<string, mixed>> */
    public function handle(): Collection
    {
        return collect([
            $this->firstTimePassRate(),
            $this->lessonsRealigned(),
            $this->retireRatio(),
            $this->videoEngagement(),
            $this->practicalTaskQuality(),
            $this->retentionAt90Days(),
            $this->trainerWorkload(),
            $this->timeToCompetency(),
            $this->itemDifficulty(),
        ]);
    }

    /**
     * KPI 1 — passed on the first sitting, out of everybody's first sitting.
     *
     * The target is a *band*. Above 80% the exam is too easy rather than the
     * training excellent; below 65% the content does not teach what the exam
     * tests. Both ends are findings, so both are reported as off-target.
     */
    private function firstTimePassRate(): array
    {
        $firstAttempts = DB::table('quiz_attempts')
            ->where('attempt_number', 1)
            ->whereNotNull('passed')
            ->count();

        if ($firstAttempts === 0) {
            return $this->awaitingData(
                1,
                'First-time pass rate',
                'Passed on attempt 1 ÷ all first attempts',
                '65–80%',
                'Nobody has sat a graded assessment yet.',
            );
        }

        $passed = DB::table('quiz_attempts')
            ->where('attempt_number', 1)
            ->where('passed', true)
            ->count();

        $rate = round($passed / $firstAttempts * 100, 1);

        return [
            'number' => 1,
            'label' => 'First-time pass rate',
            'definition' => 'Passed on attempt 1 ÷ all first attempts',
            'value' => $rate.'%',
            'target' => '65–80%',
            'status' => match (true) {
                $rate > 80 => 'above',
                $rate < 65 => 'below',
                default => 'on_target',
            },
            'note' => match (true) {
                $rate > 80 => 'Above the band: the exam is likely too easy, not the training excellent.',
                $rate < 65 => 'Below the band: the content probably does not teach what the exam tests.',
                default => 'Within the band.',
            },
            'sample' => $firstAttempts.' first attempts',
            'cadence' => 'Weekly',
        ];
    }

    /**
     * KPI 2 — how much of the back catalogue has been dispositioned.
     *
     * Needs an audit disposition on each lesson (keep / rewrite / merge /
     * retire), which is PA-5's Re-alignment Matrix. Nothing records it yet.
     */
    private function lessonsRealigned(): array
    {
        return $this->notMeasurable(
            2,
            'Modules re-aligned',
            'Dispositioned ÷ '.DB::table('lessons')->count(),
            '100% by end of Phase 3',
            'No lesson carries an audit disposition. Needs the Content Re-alignment Matrix (PA-5) to exist as data rather than a spreadsheet.',
        );
    }

    /** KPI 3 — depends on the same audit data as KPI 2. */
    private function retireRatio(): array
    {
        return $this->notMeasurable(
            3,
            'Retire ratio',
            'Retired ÷ audited',
            'Under 25%',
            'Depends on the same audit dispositions as KPI 2. A ratio above the target would mean the audit is being too aggressive, not that the content was bad.',
        );
    }

    /**
     * KPI 4 — median watch percentage, upload against embed.
     *
     * Would need playback progress reported from the browser and stored per
     * viewing. Nothing of the sort exists; embeds could not be measured at all
     * without the provider's player API.
     */
    private function videoEngagement(): array
    {
        return $this->notMeasurable(
            4,
            'Video engagement',
            'Median watch % — upload vs embed',
            'Over 70%',
            'No playback tracking. Uploaded video could report progress from the player; embedded video would need the provider\'s API, so the two halves are not equally reachable.',
        );
    }

    /** KPI 5 — mean rubric total out of 16, across settled submissions. */
    private function practicalTaskQuality(): array
    {
        $marked = DB::table('practical_submissions')->whereNotNull('total_score')->count();

        if ($marked === 0) {
            return $this->awaitingData(
                5,
                'Practical task quality',
                'Mean rubric total out of 16',
                'Over 12',
                'No practical submission has been marked yet.',
            );
        }

        $mean = round((float) DB::table('practical_submissions')->whereNotNull('total_score')->avg('total_score'), 1);

        return [
            'number' => 5,
            'label' => 'Practical task quality',
            'definition' => 'Mean rubric total out of 16',
            'value' => $mean.' / 16',
            'target' => 'Over 12',
            'status' => $mean > 12 ? 'on_target' : 'below',
            'note' => $mean > 12
                ? 'Above the threshold.'
                : 'Below the threshold — either the tasks are too hard for the training given, or the training is not preparing people for them.',
            'sample' => $marked.' marked submissions',
            'cadence' => 'Per cohort',
        ];
    }

    /** KPI 6 — needs the spaced-repetition refreshers from PA-18. */
    private function retentionAt90Days(): array
    {
        return $this->notMeasurable(
            6,
            '90-day retention',
            'Refresher score at day 90 vs original exam score',
            'Over 75% of the original',
            'Refreshers do not exist yet (PA-18). Without a second measurement there is nothing to compare the original score against.',
        );
    }

    /**
     * KPI 7 — a proxy, and labelled as one.
     *
     * The plan asks for median hours per week spent grading. Nothing times how
     * long marking takes, and guessing an hours figure from a count would be
     * inventing the number the target is checked against. What is honestly
     * available is how much unmarked work each trainer is holding.
     */
    private function trainerWorkload(): array
    {
        $trainers = User::query()->role(Role::Trainer->value)->get();

        if ($trainers->isEmpty()) {
            return $this->awaitingData(
                7,
                'Trainer workload',
                'Median unmarked items per trainer (proxy)',
                'Under 6 hrs/week grading',
                'No trainers exist yet.',
            );
        }

        $queues = $trainers->map(fn (User $trainer) => $this->unmarkedItemsFor($trainer));

        $median = $this->median($queues->all());

        return [
            'number' => 7,
            'label' => 'Trainer workload',
            'definition' => 'Median unmarked items per trainer',
            'value' => $median.' items',
            'target' => 'Under 6 hrs/week grading',
            'status' => 'proxy',
            'note' => 'A proxy, not the metric. Nothing times how long marking takes, so this reports the size of the queue rather than hours spent on it. Highest single queue: '
                .($queues->max() ?? 0).' items.',
            'sample' => $trainers->count().' trainers',
            'cadence' => 'Weekly',
        ];
    }

    /** Unmarked written answers plus unmarked practicals across a trainer's cohort. */
    private function unmarkedItemsFor(User $trainer): int
    {
        $traineeIds = $trainer->trainees()->pluck('users.id');

        if ($traineeIds->isEmpty()) {
            return 0;
        }

        $written = DB::table('quiz_answers')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_answers.quiz_attempt_id')
            ->whereIn('quiz_attempts.user_id', $traineeIds)
            ->whereNull('quiz_answers.graded_at')
            ->count();

        $practical = DB::table('practical_submissions')
            ->whereIn('user_id', $traineeIds)
            ->where('status', SubmissionStatus::Submitted->value)
            ->count();

        return $written + $practical;
    }

    /**
     * KPI 8 — assignment to level awarded, in working days.
     *
     * Measured from the trainee's earliest enrollment on any course the level
     * required, which is the moment the clock actually starts for them.
     */
    private function timeToCompetency(): array
    {
        $awards = DB::table('trainee_levels')
            ->whereNull('revoked_at')
            ->get(['user_id', 'level_id', 'competency_area_id', 'awarded_at']);

        if ($awards->isEmpty()) {
            return $this->awaitingData(
                8,
                'Time-to-competency',
                'Assignment → level awarded, in working days',
                'Under 20 working days',
                'No level has been awarded yet.',
            );
        }

        $durations = [];

        foreach ($awards as $award) {
            $courseIds = DB::table('level_requirements')
                ->where('level_id', $award->level_id)
                ->where('competency_area_id', $award->competency_area_id)
                ->pluck('course_id');

            if ($courseIds->isEmpty()) {
                continue;
            }

            $startedAt = DB::table('course_enrollments')
                ->where('user_id', $award->user_id)
                ->whereIn('course_id', $courseIds)
                ->whereNull('deleted_at')
                ->min('enrolled_at');

            if (! $startedAt) {
                continue;
            }

            $durations[] = \Illuminate\Support\Carbon::parse($startedAt)
                ->diffInWeekdays(\Illuminate\Support\Carbon::parse($award->awarded_at));
        }

        if ($durations === []) {
            return $this->awaitingData(
                8,
                'Time-to-competency',
                'Assignment → level awarded, in working days',
                'Under 20 working days',
                'Levels have been awarded, but none of them to somebody with a recorded enrollment on the required courses.',
            );
        }

        $median = $this->median($durations);

        return [
            'number' => 8,
            'label' => 'Time-to-competency',
            'definition' => 'Assignment → level awarded, in working days',
            'value' => $median.' days',
            'target' => 'Under 20 working days',
            'status' => $median < 20 ? 'on_target' : 'above',
            'note' => 'Median across '.count($durations).' awards. Counted from the earliest enrollment on a course the level required.',
            'sample' => count($durations).' awards',
            'cadence' => 'Per cohort',
        ];
    }

    /**
     * KPI 9 — per-question pass rate, flagging the extremes.
     *
     * A question nobody passes is usually a defective question or an untaught
     * lesson, not a weak cohort. One everybody passes is testing nothing.
     */
    private function itemDifficulty(): array
    {
        $questions = $this->questionDifficulties();

        if ($questions->isEmpty()) {
            return $this->awaitingData(
                9,
                'Item difficulty',
                'Per-question pass rate',
                'Flag under 30% or over 95%',
                'No question has been answered enough times to judge.',
            );
        }

        $flagged = $questions->filter(fn (array $q) => $q['flagged']);

        return [
            'number' => 9,
            'label' => 'Item difficulty',
            'definition' => 'Per-question pass rate',
            'value' => $flagged->count().' of '.$questions->count().' flagged',
            'target' => 'Flag under 30% or over 95%',
            'status' => $flagged->isEmpty() ? 'on_target' : 'below',
            'note' => $flagged->isEmpty()
                ? 'No question is sitting at either extreme.'
                : 'Flagged questions are listed below. A question everyone fails is usually a content defect, not a cohort problem.',
            'sample' => $questions->count().' questions with 5+ answers',
            'cadence' => 'Monthly',
        ];
    }

    /**
     * Per-question pass rates, for the detail table.
     *
     * Fewer than five answers is not evidence of anything, so those are left
     * out rather than shown as 0% or 100%.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function questionDifficulties(int $minimumAnswers = 5): Collection
    {
        return DB::table('quiz_answers')
            ->join('quiz_questions', 'quiz_questions.id', '=', 'quiz_answers.quiz_question_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_questions.quiz_id')
            ->whereNull('quiz_questions.deleted_at')
            ->groupBy('quiz_questions.id', 'quiz_questions.prompt', 'quiz_questions.type', 'quizzes.title')
            ->havingRaw('count(quiz_answers.id) >= ?', [$minimumAnswers])
            ->select([
                'quiz_questions.id',
                'quiz_questions.prompt',
                'quiz_questions.type',
                'quizzes.title as quiz_title',
                DB::raw('count(quiz_answers.id) as answer_count'),
                DB::raw('sum(case when quiz_answers.is_correct then 1 else 0 end) as correct_count'),
            ])
            ->get()
            ->map(function ($row): array {
                $rate = round($row->correct_count / $row->answer_count * 100, 1);

                return [
                    'id' => $row->id,
                    'question' => $row->prompt,
                    'quiz' => $row->quiz_title,
                    'type' => $row->type,
                    'answers' => (int) $row->answer_count,
                    'pass_rate' => $rate,
                    'flagged' => $rate < 30 || $rate > 95,
                    'verdict' => match (true) {
                        $rate < 30 => 'Almost nobody passes it — likely a defective question or an untaught lesson.',
                        $rate > 95 => 'Almost everybody passes it — it is not discriminating between people.',
                        default => 'Within range.',
                    },
                ];
            })
            ->sortBy('pass_rate')
            ->values();
    }

    // ------------------------------------------------------------- helpers

    /** Structurally impossible to compute today, with what it would take. */
    private function notMeasurable(int $number, string $label, string $definition, string $target, string $why): array
    {
        return [
            'number' => $number,
            'label' => $label,
            'definition' => $definition,
            'value' => null,
            'target' => $target,
            'status' => 'not_measurable',
            'note' => $why,
            'sample' => null,
            'cadence' => null,
        ];
    }

    /** Measurable, but nothing has happened yet. Different from unmeasurable. */
    private function awaitingData(int $number, string $label, string $definition, string $target, string $why): array
    {
        return [
            'number' => $number,
            'label' => $label,
            'definition' => $definition,
            'value' => null,
            'target' => $target,
            'status' => 'awaiting_data',
            'note' => $why,
            'sample' => null,
            'cadence' => null,
        ];
    }

    /** @param  array<int, int|float>  $values */
    private function median(array $values): int|float
    {
        if ($values === []) {
            return 0;
        }

        sort($values);

        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return round(($values[$middle - 1] + $values[$middle]) / 2, 1);
    }
}
