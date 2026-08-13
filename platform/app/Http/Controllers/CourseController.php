<?php

namespace App\Http\Controllers;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\EnrollmentSource;
use App\Models\Course;
use App\Models\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $courses = Course::query()
            ->visible()
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $user->id))
            ->with(['instructor:id,name'])
            ->withCount(['lessons as lesson_count' => fn ($q) => $q->where('is_published', true)])
            // Filters are applied in SQL, not by loading everything and
            // filtering in Vue — the list grows with the training catalogue.
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('title', 'ilike', "%{$search}%")
                        ->orWhere('summary', 'ilike', "%{$search}%")
                        ->orWhere('category', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('category')->value(), fn ($q, $c) => $q->where('category', $c))
            ->orderByDesc('is_required')
            ->orderBy('title')
            ->get();

        $progress = CourseProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $courses->pluck('id'))
            ->get()
            ->keyBy('course_id');

        $rows = $courses->map(function (Course $course) use ($progress) {
            $p = $progress->get($course->id);

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'category' => $course->category,
                'summary' => $course->summary,
                'difficulty' => $course->difficulty,
                'estimated_minutes' => $course->estimated_minutes,
                'is_required' => $course->is_required,
                'instructor' => $course->instructor?->name,
                'lesson_count' => $course->lesson_count,
                'percentage' => (float) ($p?->percentage ?? 0),
                'status' => $p?->status->value ?? 'not_started',
                'status_label' => $p?->status->label() ?? 'Not started',
                'status_tone' => $p?->status->tone() ?? 'neutral',
            ];
        });

        // The status filter runs after the rollup join, since status lives on
        // course_progress rather than on the course.
        if ($status = $request->string('status')->value()) {
            $rows = $rows->where('status', $status)->values();
        }

        return Inertia::render('Courses/Index', [
            'courses' => $rows->all(),
            'filters' => [
                'search' => $request->string('search')->value(),
                'category' => $request->string('category')->value(),
                'status' => $status,
            ],
            'categories' => Course::query()
                ->visible()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->all(),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        $this->authorize('view', $course);

        $user = $request->user();

        $course->load([
            'modules' => fn ($q) => $q->where('is_published', true),
            'modules.lessons' => fn ($q) => $q->where('is_published', true),
            'modules.lessons.quiz',
            'instructor:id,name',
        ]);

        // One query for the whole tree rather than one per lesson.
        $completed = $user->lessonProgress()
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->flip();

        $progress = CourseProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $finalQuiz = $course->finalQuiz()->where('is_published', true)->first();

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'category' => $course->category,
                'summary' => $course->summary,
                'description' => $course->description,
                'difficulty' => $course->difficulty,
                'estimated_minutes' => $course->estimated_minutes,
                'is_required' => $course->is_required,
                'instructor' => $course->instructor?->name,
            ],

            'modules' => $course->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'subtitle' => $module->subtitle,
                'description' => $module->description,
                'lessons' => $module->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'type' => $lesson->type->value,
                    'type_label' => $lesson->type->label(),
                    'estimated_minutes' => $lesson->estimated_minutes,
                    'completion_requirement' => $lesson->completion_requirement->value,
                    'has_quiz' => $lesson->quiz !== null,
                    'completed' => $completed->has($lesson->id),
                ])->all(),
                'completed_count' => $module->lessons
                    ->filter(fn ($l) => $completed->has($l->id))
                    ->count(),
                'lesson_count' => $module->lessons->count(),
            ])->all(),

            'progress' => [
                'percentage' => (float) ($progress?->percentage ?? 0),
                'completed_lessons' => $progress?->completed_lessons ?? 0,
                'total_lessons' => $progress?->total_lessons ?? 0,
                'status' => $progress?->status->value ?? 'not_started',
                'status_label' => $progress?->status->label() ?? 'Not started',
                'status_tone' => $progress?->status->tone() ?? 'neutral',
                'completed_at' => $progress?->completed_at?->toIso8601String(),
            ],

            'final_quiz' => $finalQuiz ? [
                'id' => $finalQuiz->id,
                'title' => $finalQuiz->title,
                'description' => $finalQuiz->description,
                'passing_score' => $finalQuiz->passing_score,
                'max_attempts' => $finalQuiz->max_attempts,
                'attempts_used' => $finalQuiz->attemptsUsedBy($user),
                'passed' => $finalQuiz->passedBy($user),
                'best_score' => (float) ($finalQuiz->bestAttemptFor($user)?->score ?? 0),

                // Unlocked only once the lessons are done, so the assessment
                // cannot be skipped ahead to.
                'unlocked' => ($progress?->total_lessons ?? 0) > 0
                    && ($progress?->completed_lessons ?? 0) >= ($progress?->total_lessons ?? 0),
            ] : null,

            'can' => [
                'reset' => $request->user()->can(
                    'reset',
                    $user->enrollments()->where('course_id', $course->id)->first()
                        ?? new \App\Models\CourseEnrollment(['user_id' => $user->id, 'course_id' => $course->id]),
                ),
            ],
        ]);
    }

    public function enroll(Request $request, Course $course, EnrollEmployee $enroll): RedirectResponse
    {
        $this->authorize('enrollSelf', $course);

        $enroll->handle($request->user(), $course, EnrollmentSource::Self);

        return back()->with('success', 'You have been enrolled in '.$course->title.'.');
    }
}
