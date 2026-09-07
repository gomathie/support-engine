<?php

namespace App\Filament\Resources\TrainerAssignmentLogs;

use App\Filament\Resources\TrainerAssignmentLogs\Pages\ListTrainerAssignmentLogs;
use App\Filament\Resources\TrainerAssignmentLogs\Tables\TrainerAssignmentLogsTable;
use App\Models\TrainerAssignmentLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Who moved cohort, when, by whose hand and why (PA-13).
 *
 * Read-only in every direction. §6 asks for an immutable log covering trainer
 * reassignments; a screen that could edit it would not be one. Assignments are
 * the provenance of a grade — "who held this cohort in March" has to stay true.
 *
 * Admin-only: `trainees.reassign` is the permission that governs making these
 * changes, and reading the history of who was moved away from whom is the same
 * sensitivity as making the move.
 */
class TrainerAssignmentLogResource extends Resource
{
    protected static ?string $model = TrainerAssignmentLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'assignment change';

    protected static ?string $navigationLabel = 'Assignment history';

    public static function table(Table $table): Table
    {
        return TrainerAssignmentLogsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('trainees.reassign') ?? false;
    }

    /** Append-only: written by the assignment action, never from this screen. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['trainee', 'fromTrainer', 'toTrainer', 'actor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainerAssignmentLogs::route('/'),
        ];
    }
}
