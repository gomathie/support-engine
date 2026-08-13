<?php

namespace App\Filament\Resources\QuizAttempts\Tables;

use App\Actions\Quiz\GradeWrittenAnswer;
use App\Enums\AttemptStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (QuizAttempt $record) => $record->user?->department?->name),

                TextColumn::make('quiz.title')
                    ->label('Assessment')
                    ->searchable()
                    ->wrap()
                    ->description(fn (QuizAttempt $record) => $record->course?->title),

                TextColumn::make('attempt_number')
                    ->label('#')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AttemptStatus $state) => $state->label())
                    ->color(fn (AttemptStatus $state) => match ($state) {
                        AttemptStatus::PendingReview => 'warning',
                        AttemptStatus::Completed => 'success',
                        default => 'gray',
                    }),

                // How much is left to mark, which is what decides whether this
                // row needs picking up.
                TextColumn::make('outstanding')
                    ->label('To mark')
                    ->state(fn (QuizAttempt $record) => $record->ungradedAnswers()->count() ?: '—')
                    ->badge()
                    ->color(fn ($state) => $state === '—' ? 'gray' : 'warning')
                    ->alignEnd(),

                TextColumn::make('score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : round((float) $state).'%')
                    ->color(fn (QuizAttempt $record) => match (true) {
                        $record->passed === true => 'success',
                        $record->passed === false => 'danger',
                        default => 'gray',
                    })
                    ->weight('bold')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Marked by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        AttemptStatus::PendingReview->value => 'Awaiting review',
                        AttemptStatus::Completed->value => 'Completed',
                    ])
                    ->default(AttemptStatus::PendingReview->value),

                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('user', fn ($u) => $u->where('department_id', $id)),
                    )),
            ])
            ->recordActions([
                static::gradeAction(),
            ])
            ->defaultSort('completed_at', 'asc')
            ->emptyStateHeading('Nothing to mark')
            ->emptyStateDescription(
                'Attempts containing written or practical answers appear here when an employee '
                .'submits them. Multiple-choice questions are marked automatically.'
            );
    }

    /**
     * Marks every outstanding answer on one attempt in a single pass.
     *
     * The employee's answer, the question and the rubric are all on screen
     * together — marking a written answer against a rubric you have to remember
     * is how inconsistent grading happens.
     */
    private static function gradeAction(): Action
    {
        return Action::make('grade')
            ->label(fn (QuizAttempt $record) => $record->awaitsReview() ? 'Mark' : 'Review')
            ->icon('heroicon-o-pencil-square')
            ->color(fn (QuizAttempt $record) => $record->awaitsReview() ? 'warning' : 'gray')
            ->modalHeading(fn (QuizAttempt $record) => $record->user->name.' — '.$record->quiz->title)
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Save marks')
            ->fillForm(function (QuizAttempt $record): array {
                $data = [];

                foreach ($record->answers()->with('question')->get() as $answer) {
                    if (! $answer->question->requiresManualGrading()) {
                        continue;
                    }

                    $data["points_{$answer->id}"] = $answer->graded_at ? $answer->points_awarded : null;
                    $data["feedback_{$answer->id}"] = $answer->grader_feedback;
                }

                return $data;
            })
            ->schema(function (QuizAttempt $record): array {
                $sections = [];

                $answers = $record->answers()
                    ->with('question')
                    ->get()
                    ->filter(fn ($a) => $a->question->requiresManualGrading())
                    ->sortBy(fn ($a) => $a->question->position);

                foreach ($answers as $answer) {
                    $question = $answer->question;

                    $sections[] = Section::make(mb_strimwidth($question->prompt, 0, 90, '…'))
                        ->description($question->points.' points')
                        ->collapsible()
                        ->collapsed(fn () => $answer->graded_at !== null)
                        ->schema([
                            Text::make($question->prompt)
                                ->color('gray'),

                            Text::make(
                                filled($answer->text_answer)
                                    ? $answer->text_answer
                                    : 'No answer submitted.'
                            )->weight('bold'),

                            Text::make($question->marking_guidance ?: 'No rubric recorded.')
                                ->color('gray'),

                            TextInput::make("points_{$answer->id}")
                                ->label('Points awarded')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($question->points)
                                ->required()
                                ->helperText("0 to {$question->points}"),

                            Textarea::make("feedback_{$answer->id}")
                                ->label('Feedback for the employee')
                                ->rows(2)
                                ->helperText('Shown on their results screen.'),
                        ]);
                }

                if ($sections === []) {
                    return [
                        Text::make('This attempt has no written answers — it was marked automatically.'),
                    ];
                }

                return $sections;
            })
            ->action(function (array $data, QuizAttempt $record, GradeWrittenAnswer $grade): void {
                $marked = 0;

                foreach ($record->answers()->with('question')->get() as $answer) {
                    if (! $answer->question->requiresManualGrading()) {
                        continue;
                    }

                    $points = $data["points_{$answer->id}"] ?? null;

                    if ($points === null || $points === '') {
                        continue;
                    }

                    $grade->handle(
                        answer: $answer,
                        grader: auth()->user(),
                        points: (int) $points,
                        feedback: $data["feedback_{$answer->id}"] ?? null,
                    );

                    $marked++;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title($marked.' answer'.($marked === 1 ? '' : 's').' marked')
                    ->body($record->awaitsReview()
                        ? $record->ungradedAnswers()->count().' still outstanding on this attempt.'
                        : 'Attempt finalised at '.round((float) $record->score).'% — '
                          .($record->passed ? 'passed' : 'not passed').'.')
                    ->send();
            });
    }
}
