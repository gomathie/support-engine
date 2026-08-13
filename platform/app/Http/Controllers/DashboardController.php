<?php

namespace App\Http\Controllers;

use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The landing page. Reproduces the prototype's index.html — hero, stats bar,
     * course cards — but every number is a real aggregate rather than the
     * hard-coded 3 / 2 / 4 / 7.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $progress = CourseProgress::query()
            ->where('user_id', $user->id)
            ->with(['course:id,title,slug,category,summary,difficulty,estimated_minutes,is_required,thumbnail_path'])
            ->whereHas('course', fn ($q) => $q->visible())
            ->get();

        // One pass over the rollup rather than a query per tile.
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'assigned' => $progress->count(),
                'in_progress' => $byStatus->get(ProgressStatus::InProgress->value, collect())->count(),
                'completed' => $byStatus->get(ProgressStatus::Completed->value, collect())->count(),
                'overdue' => $dueSoon->filter(fn ($e) => $e->isOverdue())->count(),
                'overall_percentage' => $progress->isEmpty()
                    ? 0
                    : round($progress->avg(fn (CourseProgress $p) => (float) $p->percentage), 1),
            ],

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

            // Published, optional courses nobody has assigned to them yet.
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
