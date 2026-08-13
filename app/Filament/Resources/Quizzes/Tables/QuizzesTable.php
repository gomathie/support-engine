<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Models\Course;
use App\Models\Quiz;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->description(fn (Quiz $record) => $record->course?->title),

                TextColumn::make('scope')
                    ->label('Type')
                    ->state(fn (Quiz $record) => $record->scopeLabel())
                    ->badge()
                    ->color(fn (Quiz $record) => match ($record->scope()) {
                        Quiz::SCOPE_FINAL => 'primary',
                        Quiz::SCOPE_MODULE => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (Quiz $record) => $record->module?->title ?? $record->lesson?->title),

                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->alignEnd(),

                // A choice question with nothing ticked as correct can never be
                // answered right. The imported exam arrives in exactly that
                // state, so it needs to be visible rather than discovered when
                // everybody scores zero.
                TextColumn::make('needs_key')
                    ->label('No answer key')
                    ->state(fn (Quiz $record) => $record->questions
                        ->filter(fn ($q) => $q->needsAnswerKey())
                        ->count() ?: null)
                    ->badge()
                    ->color('danger')
                    ->placeholder('—')
                    ->tooltip('Multiple-choice questions with no correct option ticked')
                    ->alignEnd(),

                TextColumn::make('points')
                    ->label('Points')
                    ->state(fn (Quiz $record) => $record->totalPoints())
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('passing_score')
                    ->label('Pass')
                    ->suffix('%')
                    ->alignEnd(),

                TextColumn::make('attempts_count')
                    ->label('Attempts')
                    ->counts('attempts')
                    ->alignEnd(),

                // Average of graded attempts — the quickest read on whether an
                // assessment is pitched right.
                TextColumn::make('average')
                    ->label('Avg score')
                    ->state(function (Quiz $record): string {
                        $avg = $record->attempts()->completed()->avg('score');

                        return $avg === null ? '—' : round($avg).'%';
                    })
                    ->alignEnd(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                Filter::make('final_only')
                    ->label('Final exams only')
                    ->query(fn ($query) => $query->whereNull('course_module_id')->whereNull('lesson_id')),

                TernaryFilter::make('is_published'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('course_id')
            ->emptyStateHeading('No assessments yet')
            ->emptyStateDescription(
                'A final exam gates course completion and the certificate. Module tests and '
                .'lesson knowledge checks are optional.'
            );
    }
}
