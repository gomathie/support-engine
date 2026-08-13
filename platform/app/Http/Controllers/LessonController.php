<?php

namespace App\Http\Controllers;

use App\Actions\Progress\CompleteLesson;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class LessonController extends Controller
{
    public function show(
        Request $request,
        Course $course,
        Lesson $lesson,
        CompleteLesson $completeLesson,
    ): Response {
        abort_unless($lesson->course_id === $course->id, 404);

        $this->authorize('view', $lesson);

        $user = $request->user();

        $lesson->load(['resources', 'annotations', 'quiz', 'module']);

        // Records the visit, and completes the lesson if that is all it takes.
        if ($user->can('complete', $lesson)) {
            $completeLesson->touch($user, $lesson);
        }

        return Inertia::render('Lessons/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
            ],

            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'description' => $lesson->description,
                'type' => $lesson->type->value,
                'type_label' => $lesson->type->label(),

                // Sanitised on the way out as well as on the way in. Lesson HTML
                // is authored by trusted staff, but a compromised admin account
                // should not become stored XSS against every employee.
                'content' => $lesson->content ? $this->sanitize($lesson->content) : null,

                'external_url' => $lesson->external_url,
                'estimated_minutes' => $lesson->estimated_minutes,
                'completion_requirement' => $lesson->completion_requirement->value,
                'module_title' => $lesson->module?->title,
            ],

            'resources' => $lesson->resources
                ->map(fn ($resource) => [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'description' => $resource->description,
                    'mime_type' => $resource->mime_type,
                    'size' => $resource->humanSize(),
                    'is_downloadable' => $resource->is_downloadable,
                    'download_url' => route('resources.download', $resource),
                    'stream_url' => route('resources.stream', $resource),
                ])->all(),

            // Replaces the runtime DOM scan in the prototype's skills module.
            'annotations' => $lesson->annotations
                ->map(fn ($annotation) => [
                    'id' => $annotation->id,
                    'type' => $annotation->type,
                    'anchor' => $annotation->anchor,
                    'section_label' => $annotation->section_label,
                    'body' => $annotation->body,
                    'is_resolved' => $annotation->is_resolved,
                ])->all(),

            'quiz' => $lesson->quiz && $lesson->quiz->is_published ? [
                'id' => $lesson->quiz->id,
                'title' => $lesson->quiz->title,
                'passing_score' => $lesson->quiz->passing_score,
                'passed' => $lesson->quiz->passedBy($user),
                'attempts_used' => $lesson->quiz->attemptsUsedBy($user),
                'max_attempts' => $lesson->quiz->max_attempts,
            ] : null,

            'navigation' => $this->navigation($course, $lesson),

            'state' => [
                'completed' => $lesson->completedBy($user),
                'can_complete' => $user->can('complete', $lesson),
            ],
        ]);
    }

    public function complete(
        Request $request,
        Course $course,
        Lesson $lesson,
        CompleteLesson $completeLesson,
    ): RedirectResponse {
        abort_unless($lesson->course_id === $course->id, 404);

        $this->authorize('complete', $lesson);

        $completeLesson->handle($request->user(), $lesson);

        return back();
    }

    public function uncomplete(
        Request $request,
        Course $course,
        Lesson $lesson,
        CompleteLesson $completeLesson,
    ): RedirectResponse {
        abort_unless($lesson->course_id === $course->id, 404);

        $this->authorize('complete', $lesson);

        $completeLesson->undo($request->user(), $lesson);

        return back();
    }

    /**
     * Previous / next across the whole course, not just the current module, so
     * an employee can read straight through.
     *
     * @return array<string, mixed>
     */
    private function navigation(Course $course, Lesson $lesson): array
    {
        $ordered = Lesson::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->orderBy('course_modules.position')
            ->orderBy('lessons.position')
            ->select('lessons.id', 'lessons.slug', 'lessons.title')
            ->get();

        $index = $ordered->search(fn ($l) => $l->id === $lesson->id);

        $link = fn ($item) => $item ? [
            'title' => $item->title,
            'url' => route('lessons.show', [$course->slug, $item->slug]),
        ] : null;

        return [
            'previous' => $link($index > 0 ? $ordered->get($index - 1) : null),
            'next' => $link($index !== false ? $ordered->get($index + 1) : null),
            'position' => $index === false ? null : $index + 1,
            'total' => $ordered->count(),
        ];
    }

    private function sanitize(string $html): string
    {
        return Purifier::clean($html, 'lesson');
    }
}
