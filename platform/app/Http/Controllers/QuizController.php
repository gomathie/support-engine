<?php

namespace App\Http\Controllers;

use App\Actions\Quiz\GradeQuizAttempt;
use App\Actions\Quiz\StartQuizAttempt;
use App\Enums\QuestionType;
use App\Http\Requests\SubmitQuizRequest;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    /** The quiz landing page: rules, history, and the button to begin. */
    public function show(Request $request, Course $course, Quiz $quiz): Response
    {
        abort_unless($quiz->course_id === $course->id, 404);

        $this->authorize('view', $quiz);

        $user = $request->user();

        $inProgress = $quiz->attempts()
            ->where('user_id', $user->id)
            ->where('status', \App\Enums\AttemptStatus::InProgress->value)
            ->first();

        return Inertia::render('Quizzes/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
            ],

            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'passing_score' => $quiz->passing_score,
                'max_attempts' => $quiz->max_attempts,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question_count' => $quiz->questions()->count(),
                'total_points' => $quiz->totalPoints(),
            ],

            'history' => $quiz->attempts()
                ->where('user_id', $user->id)
                ->completed()
                ->latest('completed_at')
                ->get()
                ->map(fn (QuizAttempt $attempt) => [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'score' => (float) $attempt->score,
                    'passed' => $attempt->passed,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ])->all(),

            'state' => [
                'attempts_used' => $quiz->attemptsUsedBy($user),
                'attempts_remaining' => $quiz->max_attempts === null
                    ? null
                    : max(0, $quiz->max_attempts - $quiz->attemptsUsedBy($user)),
                'passed' => $quiz->passedBy($user),
                'can_attempt' => $user->can('attempt', $quiz),
                'resume_attempt_id' => $inProgress?->id,
            ],
        ]);
    }

    /**
     * Begins (or resumes) an attempt and renders the questions.
     *
     * This is the security-critical payload. The question list is assembled by
     * hand rather than serialised from the model, so `is_correct` and
     * `explanation` cannot reach the browser before the attempt is graded —
     * no amount of poking at the Inertia props will reveal the answer key.
     */
    public function start(
        Request $request,
        Course $course,
        Quiz $quiz,
        StartQuizAttempt $start,
    ): Response {
        abort_unless($quiz->course_id === $course->id, 404);

        $this->authorize('attempt', $quiz);

        $attempt = $start->handle($request->user(), $quiz);

        $questions = $quiz->questions()->with('options')->get();

        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        return Inertia::render('Quizzes/Take', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
            ],

            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score,
                'time_limit_minutes' => $quiz->time_limit_minutes,
            ],

            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at->toIso8601String(),
                'expires_at' => $quiz->time_limit_minutes
                    ? $attempt->started_at->addMinutes($quiz->time_limit_minutes)->toIso8601String()
                    : null,
            ],

            'questions' => $questions->map(function ($question) use ($quiz) {
                $options = $question->options;

                if ($quiz->shuffle_options) {
                    $options = $options->shuffle();
                }

                return [
                    'id' => $question->id,
                    'type' => $question->type->value,
                    'prompt' => $question->prompt,
                    'points' => $question->points,
                    'multiple' => $question->type->allowsMultipleSelections(),

                    // Short-answer questions send no options at all — their
                    // "options" rows are the accepted answers.
                    'options' => $question->type->usesOptions()
                        ? $options->map(fn ($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                        ])->values()->all()
                        : [],
                ];
            })->values()->all(),
        ]);
    }

    public function submit(
        SubmitQuizRequest $request,
        Course $course,
        Quiz $quiz,
        GradeQuizAttempt $grade,
    ): RedirectResponse {
        abort_unless($quiz->course_id === $course->id, 404);

        $attempt = QuizAttempt::query()
            ->where('id', $request->integer('attempt_id'))
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        $this->authorize('submitAttempt', $attempt);

        $grade->handle($attempt, $request->submissions());

        return redirect()->route('attempts.show', $attempt);
    }

    /** The marked paper. Answers and explanations are only ever shown here. */
    public function result(Request $request, QuizAttempt $attempt): Response
    {
        $this->authorize('viewAttempt', $attempt);

        $attempt->load(['quiz.course', 'answers.question.options']);

        $showFeedback = $attempt->quiz->show_feedback;

        return Inertia::render('Quizzes/Result', [
            'course' => [
                'title' => $attempt->course->title,
                'slug' => $attempt->course->slug,
            ],

            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
                'show_feedback' => $showFeedback,
            ],

            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'score' => (float) $attempt->score,
                'points_earned' => $attempt->points_earned,
                'points_possible' => $attempt->points_possible,
                'passed' => $attempt->passed,
                'passing_score' => $attempt->passing_score,
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ],

            'answers' => $showFeedback
                ? $attempt->answers->map(function ($answer) {
                    $question = $answer->question;
                    $selected = collect($answer->selected_option_ids ?? []);

                    return [
                        'question_id' => $question->id,
                        'prompt' => $question->prompt,
                        'type' => $question->type->value,
                        'is_correct' => $answer->is_correct,
                        'points_awarded' => $answer->points_awarded,
                        'points' => $question->points,
                        'explanation' => $question->explanation,
                        'text_answer' => $answer->text_answer,

                        'options' => $question->type === QuestionType::ShortAnswer
                            ? []
                            : $question->options->map(fn ($option) => [
                                'label' => $option->label,
                                'selected' => $selected->contains($option->id),
                                'is_correct' => $option->is_correct,
                            ])->all(),

                        'accepted_answers' => $question->type === QuestionType::ShortAnswer
                            ? $question->options->where('is_correct', true)->pluck('label')->all()
                            : [],
                    ];
                })->all()
                : [],
        ]);
    }
}
