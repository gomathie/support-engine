<?php

namespace App\Http\Controllers;

use App\Actions\Progress\RecalculateCourseProgress;
use App\Models\Course;
use App\Models\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Training Tracker, rebuilt. Same shape as the prototype — a gauge, a
 * total, collapsible sections of checklist items — but reading from the
 * database instead of an object that never survived a reload.
 */
class ProgressController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $progress = CourseProgress::query()
            ->where('user_id', $user->id)
            ->whereHas('course', fn ($q) => $q->visible())
            ->with([
                'course:id,title,slug,category,summary',
                'course.modules' => fn ($q) => $q->where('is_published', true),
                'course.modules.lessons' => fn ($q) => $q->where('is_published', true),
            ])
            ->get();

        // One query for every completed lesson across every course, rather than
        // one per module.
        $completed = $user->lessonProgress()
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->flip();

        $totals = [
            'completed' => $progress->sum('completed_lessons'),
            'total' => $progress->sum('total_lessons'),
        ];

        return Inertia::render('Progress/Index', [
            'overall' => [
                'completed_lessons' => $totals['completed'],
                'total_lessons' => $totals['total'],
                'percentage' => $totals['total'] > 0
                    ? round($totals['completed'] / $totals['total'] * 100)
                    : 0,
                'courses_completed' => $progress->filter->isComplete()->count(),
                'courses_total' => $progress->count(),
            ],

            'sections' => $progress
                ->sortBy(fn (CourseProgress $p) => $p->course->title)
                ->values()
                ->map(fn (CourseProgress $p) => [
                    'course_id' => $p->course->id,
                    'title' => $p->course->title,
                    'slug' => $p->course->slug,
                    'flag' => $p->course->category,
                    'percentage' => (float) $p->percentage,
                    'completed_lessons' => $p->completed_lessons,
                    'total_lessons' => $p->total_lessons,
                    'status' => $p->status->value,
                    'status_label' => $p->status->label(),
                    'can_reset' => $this->canReset($request, $p->course),

                    'modules' => $p->course->modules->map(fn ($module) => [
                        'id' => $module->id,
                        'label' => $module->title,
                        'title' => $module->subtitle ?: $module->title,
                        'topics' => $module->description,
                        'items' => $module->lessons->map(fn ($lesson) => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'slug' => $lesson->slug,
                            'completed' => $completed->has($lesson->id),
                            'url' => route('lessons.show', [$p->course->slug, $lesson->slug]),
                        ])->all(),
                        'completed_count' => $module->lessons
                            ->filter(fn ($l) => $completed->has($l->id))->count(),
                        'total_count' => $module->lessons->count(),
                    ])->all(),
                ])->all(),
        ]);
    }

    /** The prototype's "reset all progress", scoped to one course. */
    public function reset(
        Request $request,
        Course $course,
        RecalculateCourseProgress $recalculate,
    ): RedirectResponse {
        $user = $request->user();

        $enrollment = $user->enrollments()->where('course_id', $course->id)->firstOrFail();

        $this->authorize('reset', $enrollment);

        $user->lessonProgress()
            ->where('course_id', $course->id)
            ->update(['completed_at' => null]);

        $recalculate->handle($user, $course);

        return back()->with('success', 'Progress for '.$course->title.' has been reset.');
    }

    private function canReset(Request $request, Course $course): bool
    {
        $enrollment = $request->user()->enrollments()->where('course_id', $course->id)->first();

        return $enrollment !== null && $request->user()->can('reset', $enrollment);
    }
}
