<?php

namespace App\Filament\Pages;

use App\Actions\Reporting\CalculateKpis;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * The success metrics from §7 of the competency plan.
 *
 * Organisation-wide and admin-only. KPI 7 reports how much unmarked work
 * trainers are holding, which is a management signal rather than something to
 * show every trainer about their colleagues.
 *
 * Completion rate is deliberately not here. It is what the old model already
 * optimised for, and a screen that led with it would undo the point of the
 * whole exercise.
 */
class Kpis extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Success metrics';

    protected static ?string $title = 'Success metrics';

    protected string $view = 'filament.pages.kpis';

    /**
     * Admin only. `reports.view-all-departments` is held by the Admin role and
     * not by Trainer, which is the same line the org-wide reporting already
     * draws.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view-all-departments') ?? false;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function kpis(): Collection
    {
        return app(CalculateKpis::class)->handle();
    }

    /** The flagged questions behind KPI 9, worst first. */
    public function flaggedQuestions(): Collection
    {
        return app(CalculateKpis::class)
            ->questionDifficulties()
            ->filter(fn (array $q) => $q['flagged'])
            ->values();
    }

    /**
     * How many of the nine can be computed at all.
     *
     * Shown at the top because it is the honest headline: the metrics that
     * cannot be measured are blocked on content and infrastructure work, not on
     * this screen.
     */
    public function coverage(): array
    {
        $kpis = $this->kpis();

        return [
            'total' => $kpis->count(),
            'reporting' => $kpis->whereNotNull('value')->count(),
            'awaiting' => $kpis->where('status', 'awaiting_data')->count(),
            'blocked' => $kpis->where('status', 'not_measurable')->count(),
        ];
    }
}
