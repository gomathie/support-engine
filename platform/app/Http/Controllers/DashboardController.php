<?php

namespace App\Http\Controllers;

use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The employee's landing page, framed around a new starter's first weeks.
 *
 * The single most useful thing this screen can do is answer "what do I do
 * next?" in one click, so `next_lesson` is computed server-side and given the
 * primary call to action.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $progress = CourseProgress::query()
            ->where('user_id', $user->id)
            ->with(['course:id,title,slug,category,summary,difficulty,estimated_minutes,is_required,thumbnail_path'])
            ->whereHas('course', fn ($q) => $q->visible())
            ->get();

        $byStatus = $progress->groupBy(fn (CourseProgress $p) => $p->status->value);

        $dueSoon = $user->enrollments()
            ->with('course:id,title,slug')
            ->whereNotNull('due_at')
            ->whereHas('course', fn ($q) => $q->visible())
            ->orderBy('due_at')
            ->get()
            ->reject(fn ($enrollment) => $progress
                ->firstWhere('course_id', $enrollment->course_id)
                ?->isComplete() ?? false)
            ->take(5)
            ->values();

        $completedCount = $byStatus->get(ProgressStatus::Completed->value, collect())->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'assigned' => $progress->count(),
                'in_progress' => $byStatus->get(ProgressStatus::InProgress->value, collect())->count(),
                'completed' => $completedCount,
                'overdue' => $dueSoon->filter(fn ($e) => $e->isOverdue())->count(),
                'overall_percentage' => $progress->isEmpty()
                    ? 0
                    : round($progress->avg(fn (CourseProgress $p) => (float) $p->percentage), 1),
            ],

            // Drives the "start here" panel: somebody who has not opened
            // anything yet gets a different, more directive screen.
            'is_new_starter' => $progress->isNotEmpty()
                && $progress->every(fn (CourseProgress $p) => $p->status === ProgressStatus::NotStarted),

            'next_lesson' => $this->nextLesson($user, $progress),

            'courses' => $progress
                ->sortBy(fn (CourseProgress $p) => [$p->isComplete() ? 1 : 0, $p->course->title])
                ->values()
                ->map(fn (CourseProgress $p) => $this->courseCard($p))
                ->all(),

            'due_soon' => $dueSoon->map(fn ($enrollment) => [
                'course_title' => $enrollment->course->title,
                'course_slug' => $enrollment->course->slug,
                'due_at' => $enrollment->due_at?->toIso8601String(),
                'is_overdue' => $enrollment->isOverdue(),
            ])->all(),

            'recent_results' => QuizAttempt::query()
                ->where('user_id', $user->id)
                ->completed()
                ->with(['quiz:id,title', 'course:id,title,slug'])
                ->latest('completed_at')
                ->take(5)
                ->get()
                ->map(fn (QuizAttempt $attempt) => [
                    'id' => $attempt->id,
                    'quiz_title' => $attempt->quiz->title,
                    'course_title' => $attempt->course->title,
                    'score' => (float) $attempt->score,
                    'passed' => $attempt->passed,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ])->all(),

            'certificates_count' => $user->certificates()->count(),

            'recommended' => Course::query()
                ->published()
                ->where('is_required', false)
                ->whereDoesntHave('enrollments', fn ($q) => $q->where('user_id', $user->id))
                ->take(3)
                ->get(['id', 'title', 'slug', 'summary', 'category', 'difficulty'])
                ->map(fn (Course $course) => [
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'summary' => $course->summary,
                    'category' => $course->category,
                    'difficulty' => $course->difficulty,
                ])->all(),
        ]);
    }

    /**
     * The first unfinished lesson in the most urgent unfinished course.
     *
     * "Most urgent" means: whatever is already underway, then anything with a
     * deadline soonest, then required before optional. Picking up where they
     * left off beats starting something new.
     *
     * @param  \Illuminate\Support\Collection<int, CourseProgress>  $progress
     * @return array<string, mixed>|null
     */
    private function nextLesson($user, $progress): ?array
    {
        $candidates = $progress->reject(fn (CourseProgress $p) => $p->isComplete());

        if ($candidates->isEmpty()) {
            return null;
        }

        $dueDates = $user->enrollments()
            ->whereIn('course_id', $candidates->pluck('course_id'))
            ->pluck('due_at', 'course_id');

        $target = $candidates
            ->sortBy(fn (CourseProgress $p) => [
                $p->status === ProgressStatus::InProgress ? 0 : 1,
                $dueDates[$p->course_id] ?? '9999-12-31',
                $p->course->is_required ? 0 : 1,
                $p->course->title,
            ])
            ->first();

        $completedIds = $user->lessonProgress()
            ->where('course_id', $target->course_id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $lesson = Lesson::query()
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->where('lessons.course_id', $target->course_id)
            ->where('lessons.is_published', true)
            ->where('course_modules.is_published', true)
            ->whereNotIn('lessons.id', $completedIds)
            ->orderBy('course_modules.position')
            ->orderBy('lessons.position')
            ->select('lessons.id', 'lessons.slug', 'lessons.title', 'course_modules.title as module_title')
            ->first();

        if (! $lesson) {
            return null;
        }

        return [
            'title' => $lesson->title,
            'module_title' => $lesson->module_title,
            'course_title' => $target->course->title,
            'course_slug' => $target->course->slug,
            'url' => route('lessons.show', [$target->course->slug, $lesson->slug]),
            'percentage' => (float) $target->percentage,
            'completed_lessons' => $target->completed_lessons,
            'total_lessons' => $target->total_lessons,
        ];
    }

    /** @return array<string, mixed> */
    private function courseCard(CourseProgress $progress): array
    {
        $course = $progress->course;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'category' => $course->category,
            'summary' => $course->summary,
            'difficulty' => $course->difficulty,
            'estimated_minutes' => $course->estimated_minutes,
            'is_required' => $course->is_required,
            'percentage' => (float) $progress->percentage,
            'completed_lessons' => $progress->completed_lessons,
            'total_lessons' => $progress->total_lessons,
            'status' => $progress->status->value,
            'status_label' => $progress->status->label(),
            'status_tone' => $progress->status->tone(),
        ];
    }
}
