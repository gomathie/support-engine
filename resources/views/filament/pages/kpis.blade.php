{{--
    The nine KPIs from §7 of the competency plan.

    Metrics that cannot be computed are shown with the reason rather than
    hidden — a dashboard that silently drops four of nine looks complete when
    it is not.
--}}
@php
    $kpis = $this->kpis();
    $coverage = $this->coverage();
    $flagged = $this->flaggedQuestions();

    $tone = fn (string $status) => match ($status) {
        'on_target' => ['text-success-700 dark:text-success-400', 'bg-success-50 dark:bg-success-400/10', 'On target'],
        'below' => ['text-danger-700 dark:text-danger-400', 'bg-danger-50 dark:bg-danger-400/10', 'Below target'],
        'above' => ['text-warning-700 dark:text-warning-400', 'bg-warning-50 dark:bg-warning-400/10', 'Above target'],
        'proxy' => ['text-info-700 dark:text-info-400', 'bg-info-50 dark:bg-info-400/10', 'Proxy measure'],
        'awaiting_data' => ['text-gray-600 dark:text-gray-400', 'bg-gray-100 dark:bg-gray-400/10', 'No data yet'],
        default => ['text-gray-600 dark:text-gray-400', 'bg-gray-100 dark:bg-gray-400/10', 'Not measurable'],
    };
@endphp

<x-filament-panels::page>
    {{-- ─── COVERAGE ─────────────────────────────────────── --}}
    <div
        @class([
            'rounded-xl bg-white px-6 py-5 shadow-sm ring-1 ring-gray-950/5',
            'dark:bg-gray-900 dark:ring-white/10',
        ])
    >
        <p class="text-sm text-gray-600 dark:text-gray-300">
            <strong class="text-gray-950 dark:text-white">
                {{ $coverage['reporting'] }} of {{ $coverage['total'] }}
            </strong>
            metrics are reporting a number.
            @if ($coverage['awaiting'])
                {{ $coverage['awaiting'] }} can be measured but nothing has happened yet.
            @endif
            @if ($coverage['blocked'])
                {{ $coverage['blocked'] }} cannot be measured at all until the work they
                depend on exists.
            @endif
        </p>

        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Completion rate is deliberately not among them — it is what the previous
            model already optimised for, and it measures nothing about competence.
        </p>
    </div>

    {{-- ─── THE NINE ─────────────────────────────────────── --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($kpis as $kpi)
            @php([$textClass, $bgClass, $statusLabel] = $tone($kpi['status']))

            <div
                @class([
                    'flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5',
                    'dark:bg-gray-900 dark:ring-white/10',
                ])
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            KPI {{ $kpi['number'] }}
                        </p>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $kpi['label'] }}
                        </h3>
                    </div>

                    <span @class(['shrink-0 rounded-md px-2 py-1 text-xs font-medium', $textClass, $bgClass])>
                        {{ $statusLabel }}
                    </span>
                </div>

                <p class="mt-3 text-2xl font-bold {{ $kpi['value'] ? 'text-gray-950 dark:text-white' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $kpi['value'] ?? '—' }}
                </p>

                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $kpi['definition'] }} · target {{ $kpi['target'] }}
                </p>

                <p class="mt-3 flex-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                    {{ $kpi['note'] }}
                </p>

                @if ($kpi['sample'] || $kpi['cadence'])
                    <p class="mt-3 border-t border-gray-100 pt-2 text-xs text-gray-400 dark:border-white/5 dark:text-gray-500">
                        {{ $kpi['sample'] }}@if ($kpi['sample'] && $kpi['cadence']) · @endif{{ $kpi['cadence'] }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ─── ITEM DIFFICULTY DETAIL ───────────────────────── --}}
    @if ($flagged->isNotEmpty())
        <div
            @class([
                'rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                'dark:bg-gray-900 dark:ring-white/10',
            ])
        >
            <header class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Questions at the extremes
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    A question almost nobody passes is usually a defective question or an
                    untaught topic — not a weak cohort. One almost everybody passes is not
                    discriminating between people. Both are content findings.
                </p>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-6 py-2 text-left font-medium">Question</th>
                            <th class="px-6 py-2 text-right font-medium">Pass rate</th>
                            <th class="px-6 py-2 text-right font-medium">Answers</th>
                            <th class="px-6 py-2 text-left font-medium">Reading</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flagged as $question)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                <td class="px-6 py-3">
                                    <span class="block text-gray-950 dark:text-white">
                                        {{ Str::limit($question['question'], 110) }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $question['quiz'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold {{ $question['pass_rate'] < 30 ? 'text-danger-600 dark:text-danger-400' : 'text-warning-600 dark:text-warning-400' }}">
                                    {{ $question['pass_rate'] }}%
                                </td>
                                <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">
                                    {{ $question['answers'] }}
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $question['verdict'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
