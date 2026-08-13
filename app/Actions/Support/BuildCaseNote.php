<?php

namespace App\Actions\Support;

use App\Models\SupportCase;

/**
 * Rebuilds the Support Panel's case note.
 *
 * This was note() in pages/support-panel/script.js, assembling a string in the
 * browser from state that never persisted. The output format is reproduced
 * line for line — support staff paste this into tickets and the shape is the
 * habit — but it is now generated server-side, so it is testable and identical
 * for everyone.
 */
class BuildCaseNote
{
    public function handle(SupportCase $case): string
    {
        $lines = [];

        $lines[] = 'Customer : '.($case->customer ?: '—');
        $lines[] = 'Object   : '.($case->object_ref ?: '—');

        $tree = $case->tree;

        if ($tree) {
            $lines[] = 'Reported : "'.$tree->question.'"';
        }

        if ($case->priority_code) {
            $lines[] = 'Priority : '.$case->priority_code
                .'  ('.($case->priority_reason ?: 'no justification recorded').')';
        }

        if ($tree) {
            $steps = $tree->steps;
            $states = $case->step_states ?? [];

            $ruledOut = [];
            $found = [];

            foreach ($steps as $step) {
                $state = $states[(string) $step->id] ?? null;

                if ($state === 'out') {
                    $ruledOut[] = $step;
                } elseif ($state === 'found') {
                    $found[] = $step;
                }
            }

            if ($ruledOut !== []) {
                $lines[] = '';
                $lines[] = 'Checked and ruled out:';
                foreach ($ruledOut as $step) {
                    $lines[] = '  - '.$step->prompt;
                }
            }

            if ($found !== []) {
                $lines[] = '';
                $lines[] = 'Cause found:';
                foreach ($found as $step) {
                    $lines[] = '  * '.$step->prompt;
                    if ($step->fix) {
                        $lines[] = '    > '.$step->fix;
                    }
                }
            }

            $worked = count($ruledOut) + count($found);
            $total = $steps->count();

            if ($worked < $total) {
                $lines[] = '';
                $lines[] = 'Still to check: '.($total - $worked).' of '.$total;
            }

            if ($found === [] && $worked === $total && $total > 0) {
                $lines[] = '';
                $lines[] = 'All layers worked, no cause found → escalate.';
            }
        }

        $lines[] = '';
        $lines[] = 'Customer has been told:';
        $lines[] = '  '.($case->customer_told ?: '');

        return implode("\n", $lines);
    }
}
