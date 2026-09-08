<?php

namespace App\Filament\Resources\Refreshers;

use App\Enums\RefresherStatus;
use App\Filament\Resources\Refreshers\Pages\ListRefreshers;
use App\Filament\Resources\Refreshers\Tables\RefreshersTable;
use App\Models\Refresher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Who is due a refresher, and what the ones already sat say (PA-18).
 *
 * KPI 6 gives one number for the whole organisation. This is the same data with
 * names against it, which is what somebody can actually act on: an area where
 * retention is falling is a content finding, and the people who need a
 * conversation are the ones in the list.
 *
 * **Read-only, deliberately.** Refreshers are scheduled by the award and marked
 * by the trainee sitting them. There is no case for creating one by hand, and
 * editing a score after the fact would make the retention figure a matter of
 * opinion. The one thing an administrator might reasonably want — re-opening a
 * missed refresher — is not offered either: it would be measuring retention at
 * an interval nobody chose, and the honest answer is that the refresher was
 * missed.
 */
class RefresherResource extends Resource
{
    protected static ?string $model = Refresher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'refresher';

    protected static ?string $navigationLabel = 'Refreshers';

    public static function table(Table $table): Table
    {
        return RefreshersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * A trainer sees their own cohort; an admin sees everybody.
     *
     * The same line the grading queue draws, and for the same reason: a list of
     * people you cannot follow up with is noise.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.department', 'traineeLevel.level', 'traineeLevel.competencyArea']);

        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('user_id', $user->gradableTraineeIds());
    }

    /** How many are due now and still open — the number worth chasing. */
    public static function getNavigationBadge(): ?string
    {
        $open = static::getEloquentQuery()->open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Refreshers due now and not yet sat';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /*
     * No canViewAny() override, matching the grading queue.
     *
     * Panel access is already the gate — only Admin and Trainer reach the
     * panel at all — and the query above scopes a trainer to their own cohort.
     * A second permission check here would be a different line in a third
     * place, which is how they drift apart.
     */

    public static function getPages(): array
    {
        return [
            'index' => ListRefreshers::route('/'),
        ];
    }
}
