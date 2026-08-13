<?php

namespace App\Http\Controllers;

use App\Actions\Support\BuildCaseNote;
use App\Models\DiagnosticTree;
use App\Models\SupportCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportPanelController extends Controller
{
    public function index(Request $request, BuildCaseNote $buildNote): Response
    {
        $user = $request->user();

        $trees = DiagnosticTree::query()
            ->published()
            ->with('steps')
            ->orderBy('position')
            ->get();

        // Resume the case they were working on, the way the prototype tried to
        // and could not.
        $case = SupportCase::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        return Inertia::render('SupportPanel/Index', [
            'trees' => $trees->map(fn (DiagnosticTree $tree) => [
                'id' => $tree->id,
                'key' => $tree->key,
                'question' => $tree->question,
                'layer_label' => $tree->layer_label,
                'steps' => $tree->steps->map(fn ($step) => [
                    'id' => $step->id,
                    'prompt' => $step->prompt,
                    'layer' => $step->layer,

                    // The prototype held every fix in the DOM and revealed it
                    // with CSS. Sending it is fine — this is reference material
                    // the agent is meant to read, not an answer key.
                    'fix' => $step->fix,
                ])->all(),
            ])->all(),

            'case' => $case ? [
                'id' => $case->id,
                'diagnostic_tree_id' => $case->diagnostic_tree_id,
                'customer' => $case->customer,
                'object_ref' => $case->object_ref,
                'step_states' => (object) ($case->step_states ?? []),
                'priority_code' => $case->priority_code,
                'priority_reason' => $case->priority_reason,
                'customer_told' => $case->customer_told,
                'note' => $buildNote->handle($case),
            ] : null,

            'priority_matrix' => $this->priorityMatrix(),
        ]);
    }

    public function store(Request $request, BuildCaseNote $buildNote): RedirectResponse
    {
        $this->authorize('create', SupportCase::class);

        $case = SupportCase::query()->create([
            'user_id' => $request->user()->id,
            'status' => 'open',
            'step_states' => [],
        ]);

        return back()->with('success', 'New case started.');
    }

    public function update(Request $request, SupportCase $case): RedirectResponse
    {
        $this->authorize('update', $case);

        $validated = $request->validate([
            'diagnostic_tree_id' => ['nullable', 'integer', Rule::exists('diagnostic_trees', 'id')],
            'customer' => ['nullable', 'string', 'max:255'],
            'object_ref' => ['nullable', 'string', 'max:255'],
            'step_states' => ['nullable', 'array'],
            'step_states.*' => [Rule::in(['out', 'found'])],
            'priority_code' => ['nullable', 'string', Rule::in(['P1', 'P2', 'P3', 'P4', 'P5'])],
            'priority_reason' => ['nullable', 'string', 'max:255'],
            'customer_told' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['open', 'escalated', 'closed'])],
        ]);

        $case->fill($validated);

        if (($validated['status'] ?? null) === 'closed') {
            $case->closed_at = now();
        }

        $case->save();

        return back();
    }

    /**
     * Impact x urgency. This is doctrine rather than data — it is the same for
     * every desk and does not change — so it stays in code rather than becoming
     * a table somebody has to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function priorityMatrix(): array
    {
        return [
            [
                'row' => 'Impact: high',
                'sub' => 'whole contract / many customers',
                'cells' => [
                    ['priority' => 'P1', 'example' => 'Nobody at a contract can log in'],
                    ['priority' => 'P2', 'example' => 'Reports wrong fleet-wide, fleet still runs'],
                    ['priority' => 'P3', 'example' => 'Cosmetic, affects everyone'],
                ],
            ],
            [
                'row' => 'Impact: medium',
                'sub' => 'a group, or a VIP',
                'cells' => [
                    ['priority' => 'P2', 'example' => 'Reefer temperature alerts not firing'],
                    ['priority' => 'P3', 'example' => "One depot's geofence reports wrong"],
                    ['priority' => 'P4', 'example' => 'A group wants a column added'],
                ],
            ],
            [
                'row' => 'Impact: low',
                'sub' => 'one user / one object',
                'cells' => [
                    ['priority' => 'P3', 'example' => 'One vehicle dark during a theft'],
                    ['priority' => 'P4', 'example' => 'One sensor mis-calibrated'],
                    ['priority' => 'P5', 'example' => 'How-to question'],
                ],
            ],
        ];
    }
}
