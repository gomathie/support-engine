<?php

namespace App\Http\Controllers;

use App\Actions\Competency\GradeRefresher;
use App\Actions\Competency\StartRefresher;
use App\Models\Refresher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Spaced-repetition refreshers (PA-18).
 *
 * Five auto-marked questions, thirty and ninety days after a level was
 * awarded. Nothing here changes what anybody holds — the point is the number
 * it produces, which is the only measurement of whether the training stuck.
 */
class RefresherController extends Controller
{
    /**
     * The paper.
     *
     * Options are sent without any indication of which is correct, exactly as
     * the quiz engine does. Marking happens server-side and the browser is
     * never told the key.
     */
    public function show(Request $request, Refresher $refresher, StartRefresher $start): Response
    {
        $this->authorize('attempt', $refresher);

        $answers = $start->handle($refresher);

        return Inertia::render('Refreshers/Take', [
            'refresher' => [
                'id' => $refresher->id,
                'label' => $refresher->label(),
                'interval_days' => $refresher->interval_days,
                'due_at' => $refresher->due_at->toDateString(),
                'closes_at' => $refresher->closesAt()->toDateString(),
                'area' => $refresher->traineeLevel->competencyArea?->name,
                'level' => $refresher->traineeLevel->level?->name,
            ],

            'questions' => $answers
                ->filter(fn ($answer) => $answer->question !== null)
                ->map(fn ($answer) => [
                    'id' => $answer->question->id,
                    'position' => $answer->position,
                    'prompt' => $answer->question->prompt,
                    'type' => $answer->question->type->value,
                    'allows_multiple' => $answer->question->type->allowsMultipleSelections(),
                    'options' => $answer->question->options
                        ->map(fn ($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                        ])->values()->all(),
                ])->values()->all(),
        ]);
    }

    public function submit(Request $request, Refresher $refresher, GradeRefresher $grade): RedirectResponse
    {
        $this->authorize('attempt', $refresher);

        $validated = $request->validate([
            'answers' => ['array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_ids' => ['array'],
            'answers.*.option_ids.*' => ['integer'],
        ]);

        $grade->handle($refresher, $validated['answers'] ?? []);

        return redirect()
            ->route('refreshers.result', $refresher)
            ->with('status', 'Refresher recorded.');
    }

    /**
     * What it said.
     *
     * Shown with the explanation for every question, right or wrong. A
     * refresher exists to find the things that have faded, so the wrong answers
     * are the useful part — hiding them would waste the only value it has.
     */
    public function result(Request $request, Refresher $refresher): Response
    {
        $this->authorize('view', $refresher);

        $refresher->load(['answers.question.options', 'traineeLevel.level', 'traineeLevel.competencyArea']);

        return Inertia::render('Refreshers/Result', [
            'refresher' => [
                'id' => $refresher->id,
                'label' => $refresher->label(),
                'score' => $refresher->score === null ? null : (float) $refresher->score,
                'baseline' => $refresher->baseline_score === null ? null : (float) $refresher->baseline_score,
                'retention' => $refresher->retention === null ? null : (float) $refresher->retention,
                'completed_at' => $refresher->completed_at?->toDateString(),
                'area' => $refresher->traineeLevel->competencyArea?->name,
                'level' => $refresher->traineeLevel->level?->name,
            ],

            'answers' => $refresher->answers
                ->filter(fn ($answer) => $answer->question !== null)
                ->map(fn ($answer) => [
                    'position' => $answer->position,
                    'prompt' => $answer->question->prompt,
                    'is_correct' => $answer->is_correct,
                    'explanation' => $answer->question->explanation,
                    'options' => $answer->question->options
                        ->map(fn ($option) => [
                            'label' => $option->label,
                            'is_correct' => (bool) $option->is_correct,
                            'chosen' => in_array($option->id, $answer->selected_option_ids ?? [], true),
                        ])->values()->all(),
                ])->values()->all(),
        ]);
    }
}
