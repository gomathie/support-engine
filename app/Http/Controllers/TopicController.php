<?php

namespace App\Http\Controllers;

use App\Actions\Progress\CompleteTopic;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class TopicController extends Controller
{
    public function show(
        Request $request,
        Course $course,
        Topic $topic,
        CompleteTopic $completeLesson,
    ): Response {
        abort_unless($topic->course_id === $course->id, 404);

        $this->authorize('view', $topic);

        $user = $request->user();

        $topic->load(['resources', 'annotations', 'quiz', 'lesson']);

        // Records the visit, and completes the topic if that is all it takes.
        if ($user->can('complete', $topic)) {
            $completeLesson->touch($user, $topic);
        }

        return Inertia::render('Topics/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
            ],

            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'slug' => $topic->slug,
                'description' => $topic->description,
                'type' => $topic->type->value,
                'type_label' => $topic->type->label(),

                // Sanitised on the way out as well as on the way in. Topic HTML
                // is authored by trusted staff, but a compromised admin account
                // should not become stored XSS against every employee.
                'content' => $topic->content ? $this->sanitize($topic->content) : null,

                'external_url' => $topic->external_url,

                // Rebuilt from the stored provider and id, never from a stored
                // URL — the iframe src must not be author-controlled text.
                'video' => $topic->videoEmbed()?->toArray(),

                // A route, not a file path or a storage URL. The bytes are on
                // the private disk and only this route reaches them, after the
                // same policy that let the topic render at all.
                'video_src' => $topic->hasUploadedVideo()
                    ? route('topics.video', [$course->slug, $topic->slug])
                    : null,
                'video_mime' => $topic->hasUploadedVideo() ? $topic->video_mime_type : null,

                'video_duration' => $topic->videoDurationForHumans(),

                // Plain text. Rendered with interpolation rather than v-html,
                // so it needs no sanitising pass.
                'video_transcript' => $topic->video_transcript,

                'estimated_minutes' => $topic->estimated_minutes,
                'completion_requirement' => $topic->completion_requirement->value,
                'module_title' => $topic->lesson?->title,
            ],

            'resources' => $topic->resources
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

            // Replaces the runtime DOM scan in the prototype's skills lesson.
            'annotations' => $topic->annotations
                ->map(fn ($annotation) => [
                    'id' => $annotation->id,
                    'type' => $annotation->type,
                    'anchor' => $annotation->anchor,
                    'section_label' => $annotation->section_label,
                    'body' => $annotation->body,
                    'is_resolved' => $annotation->is_resolved,
                ])->all(),

            'quiz' => $topic->quiz && $topic->quiz->is_published ? [
                'id' => $topic->quiz->id,
                'title' => $topic->quiz->title,
                'passing_score' => $topic->quiz->passing_score,
                'passed' => $topic->quiz->passedBy($user),
                'attempts_used' => $topic->quiz->attemptsUsedBy($user),
                'max_attempts' => $topic->quiz->max_attempts,
            ] : null,

            'navigation' => $this->navigation($course, $topic),

            'state' => [
                'completed' => $topic->completedBy($user),
                'can_complete' => $user->can('complete', $topic),
            ],
        ]);
    }

    public function complete(
        Request $request,
        Course $course,
        Topic $topic,
        CompleteTopic $completeLesson,
    ): RedirectResponse {
        abort_unless($topic->course_id === $course->id, 404);

        $this->authorize('complete', $topic);

        $completeLesson->handle($request->user(), $topic);

        return back();
    }

    public function uncomplete(
        Request $request,
        Course $course,
        Topic $topic,
        CompleteTopic $completeLesson,
    ): RedirectResponse {
        abort_unless($topic->course_id === $course->id, 404);

        $this->authorize('complete', $topic);

        $completeLesson->undo($request->user(), $topic);

        return back();
    }

    /**
     * Previous / next across the whole course, not just the current module, so
     * an employee can read straight through.
     *
     * @return array<string, mixed>
     */
    private function navigation(Course $course, Topic $topic): array
    {
        // Both tables carry a course_id, so every column here is qualified —
        // an unqualified one makes PostgreSQL reject the query as ambiguous.
        $ordered = Topic::query()
            ->join('lessons', 'lessons.id', '=', 'topics.lesson_id')
            ->where('topics.course_id', $course->id)
            ->where('topics.is_published', true)
            ->where('lessons.is_published', true)
            ->orderBy('lessons.position')
            ->orderBy('topics.position')
            ->select('topics.id', 'topics.slug', 'topics.title')
            ->get();

        $index = $ordered->search(fn ($l) => $l->id === $topic->id);

        $link = fn ($item) => $item ? [
            'title' => $item->title,
            'url' => route('topics.show', [$course->slug, $item->slug]),
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
        return Purifier::clean($html, 'topic');
    }
}
