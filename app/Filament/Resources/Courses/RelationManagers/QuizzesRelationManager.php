<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Assessments attached to this course, with a one-click way to add the final
 * exam — the thing most courses need and the thing that was hardest to find.
 */
class QuizzesRelationManager extends RelationManager
{
    protected static string $relationship = 'quizzes';

    protected static ?string $title = 'Assessments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->weight('bold')
                    ->wrap(),

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

                TextColumn::make('passing_score')
                    ->label('Pass')
                    ->suffix('%')
                    ->alignEnd(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->headerActions([
                /*
                 * The headline action. Creates the final exam as a draft and
                 * drops the author straight into the question editor, rather
                 * than making them find the assessments resource and work out
                 * that "leave both selects blank" means "final exam".
                 */
                Action::make('addFinalExam')
                    ->label('Add final exam')
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn () => ! $this->getOwnerRecord()->finalQuiz()->exists())
                    ->action(function () {
                        $course = $this->getOwnerRecord();

                        $quiz = $course->quizzes()->create([
                            'course_module_id' => null,
                            'lesson_id' => null,
                            'title' => $course->title.' — final exam',
                            'description' => 'Covers the whole course. Passing it completes the course '
                                .'and issues your certificate.',
                            'passing_score' => 70,
                            'max_attempts' => 3,
                            'shuffle_questions' => true,
                            'shuffle_options' => true,
                            'show_feedback' => true,

                            // Draft: an exam with no questions would otherwise
                            // be immediately live and instantly passable.
                            'is_published' => false,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Final exam created as a draft')
                            ->body('Add questions, then publish it.')
                            ->send();

                        return redirect(QuizResource::getUrl('edit', ['record' => $quiz]));
                    }),

                Action::make('addOther')
                    ->label('Add assessment')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->url(fn () => QuizResource::getUrl('create')),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Quiz $record) => QuizResource::getUrl('edit', ['record' => $record])),

                DeleteAction::make(),
            ])
            ->defaultSort('course_module_id')
            ->emptyStateHeading('No assessments on this course')
            ->emptyStateDescription(
                'Without a final exam, the course completes as soon as every lesson is ticked off.'
            );
    }
}
