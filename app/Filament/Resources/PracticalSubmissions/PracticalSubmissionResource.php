<?php

namespace App\Filament\Resources\PracticalSubmissions;

use App\Enums\Role;
use App\Filament\Resources\PracticalSubmissions\Pages\ListPracticalSubmissions;
use App\Filament\Resources\PracticalSubmissions\Tables\PracticalSubmissionsTable;
use App\Models\PracticalSubmission;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The marking queue for practical tasks.
 *
 * Scoped the same way the quiz grading queue is: a trainer with
 * `transcripts.view-all` may read anybody's work, but the queue shows what they
 * can actually act on — their own cohort. A list full of rows you are not
 * allowed to mark is not a queue.
 */
class PracticalSubmissionResource extends Resource
{
    protected static ?string $model = PracticalSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'practical submission';

    protected static ?string $navigationLabel = 'Practical marking';

    public static function table(Table $table): Table
    {
        return PracticalSubmissionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Submissions come from trainees doing the work, never from this side.
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['task', 'user', 'gradings']);

        $user = Filament::auth()->user();

        if (! $user || $user->hasRole(Role::Admin->value)) {
            return $query;
        }

        return $query->whereIn('user_id', $user->gradableTraineeIds());
    }

    /** The count of work actually waiting on this person. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = static::getEloquentQuery()->awaitingMarking()->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPracticalSubmissions::route('/'),
        ];
    }
}
