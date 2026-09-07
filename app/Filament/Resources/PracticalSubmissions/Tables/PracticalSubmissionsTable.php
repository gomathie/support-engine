<?php

namespace App\Filament\Resources\PracticalSubmissions\Tables;

use App\Actions\Practical\GradePracticalSubmission;
use App\Enums\RubricCriterion;
use App\Enums\SubmissionStatus;
use App\Models\PracticalSubmission;
use App\Models\PracticalTask;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PracticalSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trainee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PracticalSubmission $record) => $record->user?->department?->name),

                TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->wrap()
                    ->description(fn (PracticalSubmission $record) => $record->task?->course?->title),

                TextColumn::make('attempt_number')
                    ->label('#')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SubmissionStatus $state) => $state->label())
                    ->color(fn (SubmissionStatus $state) => match ($state) {
                        SubmissionStatus::Submitted => 'warning',
                        SubmissionStatus::Graded => 'success',
                        SubmissionStatus::Returned => 'info',
                        SubmissionStatus::Draft => 'gray',
                    }),

                /*
                 * Marking progress on a double-marked task. "1 of 2" is the
                 * state a trainer needs to see before deciding whether to pick
                 * the row up — and "split" is the one that needs a conversation
                 * rather than another mark.
                 */
                TextColumn::make('marking')
                    ->label('Marking')
                    ->state(function (PracticalSubmission $record): string {
                        $have = $record->gradings->count();
                        $need = $record->requiredGradings();

                        if ($have === 0) {
                            return 'Unmarked';
                        }

                        if ($record->markersDisagree()) {
                            return 'Split verdict';
                        }

                        return $have >= $need ? 'Complete' : $have.' of '.$need;
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Split verdict' => 'danger',
                        'Complete' => 'success',
                        'Unmarked' => 'gray',
                        default => 'warning',
                    })
                    ->tooltip(fn (PracticalSubmission $record) => $record->markersDisagree()
                        ? 'Two markers disagreed — reconcile before this settles. Spread: '.$record->scoreSpread().' points.'
                        : null),

                TextColumn::make('total_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state.'/'.RubricCriterion::maxTotal())
                    ->color(fn (PracticalSubmission $record) => match ($record->passed) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    })
                    ->weight('bold')
                    ->alignEnd(),

                TextColumn::make('submitted_at')
                    ->label('Handed in')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubmissionStatus::options())
                    ->default(SubmissionStatus::Submitted->value),

                SelectFilter::make('practical_task_id')
                    ->label('Task')
                    ->options(fn () => PracticalTask::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                // The calibration review: everything two trainers scored
                // differently, which is the meeting §4.3 asks for.
                Filter::make('split')
                    ->label('Split verdicts only')
                    ->query(fn ($query) => $query->whereHas('gradings', fn ($q) => $q->where('passed', true))
                        ->whereHas('gradings', fn ($q) => $q->where('passed', false)))
                    ->toggle(),
            ])
            ->recordActions([
                static::markAction(),
                static::returnAction(),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->emptyStateHeading('Nothing to mark')
            ->emptyStateDescription('Practical submissions from your cohort appear here when a trainee hands one in.');
    }

    /**
     * The rubric, on screen, beside the work.
     *
     * Every criterion shows its 0–4 descriptors in the option labels rather than
     * bare numbers. A rubric you have to remember is how two trainers end up
     * applying two different standards — which is the drift §4.3 exists to stop.
     */
    private static function markAction(): Action
    {
        return Action::make('mark')
            ->label(fn (PracticalSubmission $record) => $record->gradingBy(auth()->user()) ? 'Revise marks' : 'Mark')
            ->icon('heroicon-o-check-badge')
            ->color('warning')
            ->modalWidth('5xl')
            ->modalHeading(fn (PracticalSubmission $record) => $record->user->name.' — '.$record->task->title)
            ->modalSubmitActionLabel('Save marks')
            ->visible(fn (PracticalSubmission $record) => auth()->user()?->can('grade', $record))
            ->fillForm(function (PracticalSubmission $record): array {
                $existing = $record->gradingBy(auth()->user());

                if (! $existing) {
                    return [];
                }

                $data = ['summary' => $existing->summary];

                foreach ($existing->scores as $score) {
                    $data['score_'.$score->criterion->value] = $score->score;
                    $data['comment_'.$score->criterion->value] = $score->comment;
                }

                return $data;
            })
            ->schema(function (PracticalSubmission $record): array {
                $sections = [
                    Section::make('What they submitted')
                        ->collapsible()
                        ->schema([
                            Text::make($record->body ?: 'No write-up submitted.'),

                            ...($record->files->map(fn ($file) => Text::make(
                                'Attached: '.$file->original_name.' ('.$file->humanSize().')'
                            ))->all()),
                        ]),
                ];

                /*
                 * The identifiers, shown before the rubric.
                 *
                 * These are the point of Verification: the trainee worked in a
                 * live PILOT account, so the marker can paste the agent ID into
                 * PILOT and see whether the object is really there. Marking
                 * Verification without checking them is marking the prose.
                 */
                $evidence = $record->task?->evidenceFields() ?? [];

                if ($evidence !== []) {
                    $sections[] = Section::make('Check these in PILOT')
                        ->description('Look them up before scoring Verification.')
                        ->schema(array_map(
                            fn (array $item) => Text::make(
                                $item['label'].': '
                                .(filled($record->evidence[$item['key']] ?? null)
                                    ? $record->evidence[$item['key']]
                                    : '— not supplied —')
                            ),
                            $evidence,
                        ));
                }

                foreach (RubricCriterion::cases() as $criterion) {
                    $sections[] = Section::make($criterion->label())
                        ->description($criterion->isCritical()
                            ? 'Needs 3 or more to pass — a tidy route to the wrong answer is still the wrong answer.'
                            : 'Needs 2 or more to pass.')
                        ->columns(2)
                        ->schema([
                            Select::make('score_'.$criterion->value)
                                ->label('Score')
                                ->options(collect($criterion->descriptors())
                                    ->map(fn (string $text, int $score) => $score.' — '.$text)
                                    ->all())
                                ->required()
                                ->native(false)
                                ->live(),

                            Textarea::make('comment_'.$criterion->value)
                                ->label('Comment')
                                ->rows(2)
                                ->required(fn ($get) => $get('score_'.$criterion->value) !== null
                                    && (int) $get('score_'.$criterion->value) <= RubricCriterion::COMMENT_REQUIRED_AT_OR_BELOW)
                                ->helperText('Required at 2 or below — it is the only part of the mark they can learn from.'),
                        ]);
                }

                $sections[] = Textarea::make('summary')
                    ->label('Overall remarks')
                    ->rows(3);

                return $sections;
            })
            ->action(function (array $data, PracticalSubmission $record, GradePracticalSubmission $grade): void {
                $scores = [];
                $comments = [];

                foreach (RubricCriterion::cases() as $criterion) {
                    $scores[$criterion->value] = (int) ($data['score_'.$criterion->value] ?? 0);
                    $comments[$criterion->value] = $data['comment_'.$criterion->value] ?? null;
                }

                $grading = $grade->handle(
                    submission: $record,
                    grader: auth()->user(),
                    scores: $scores,
                    comments: $comments,
                    summary: $data['summary'] ?? null,
                );

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Marks saved — '.$grading->total_score.'/'.RubricCriterion::maxTotal())
                    ->body(match (true) {
                        $record->markersDisagree() => 'The other marker reached a different verdict. This does not settle until you reconcile.',
                        ! $record->hasEnoughGradings() => 'Waiting on the second marker before this settles.',
                        default => $grading->verdictExplanation(),
                    })
                    ->send();
            });
    }

    /** Send it back without recording a fail against it. */
    private static function returnAction(): Action
    {
        return Action::make('return')
            ->label('Return for revision')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->link()
            ->visible(fn (PracticalSubmission $record) => $record->awaitsMarking()
                && auth()->user()?->can('return', $record))
            ->modalHeading('Return for revision')
            ->modalDescription('Use this when evidence is missing rather than wrong. It reopens the same attempt and records no fail.')
            ->schema([
                Textarea::make('reason')
                    ->label('What needs to change')
                    ->required()
                    ->minLength(10)
                    ->rows(3),
            ])
            ->action(function (array $data, PracticalSubmission $record, GradePracticalSubmission $grade): void {
                $grade->returnForRevision($record, auth()->user(), $data['reason']);

                Notification::make()
                    ->success()
                    ->title('Returned for revision')
                    ->body('They can edit and hand it in again as the same attempt.')
                    ->send();
            });
    }
}
