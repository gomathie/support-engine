<?php

namespace App\Filament\Resources\QuizAttempts;

use App\Enums\AttemptStatus;
use App\Filament\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Filament\Resources\QuizAttempts\Tables\QuizAttemptsTable;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The grading queue.
 *
 * Written and practical answers cannot be machine-marked, so an attempt
 * containing any of them stops at `pending_review` until an examiner has read
 * every one. This is where that happens.
 *
 * Attempts are never created or deleted here — they are a record of something
 * an employee did.
 */
class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'attempt';

    protected static ?string $pluralModelLabel = 'grading';

    protected static ?string $navigationLabel = 'Grading';

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Managers see only their own departments' attempts — the same scoping the
     * employee records and reports use.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.department', 'quiz', 'course'])
            ->whereIn('status', [
                AttemptStatus::PendingReview->value,
                AttemptStatus::Completed->value,
            ]);

        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->whereHas(
            'user',
            fn ($q) => $q->whereIn('department_id', $user->visibleDepartmentIds()),
        );
    }

    /** How many attempts are sitting unmarked. */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()
            ->where('status', AttemptStatus::PendingReview->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Attempts waiting to be marked';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
        ];
    }
}
