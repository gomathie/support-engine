<?php

namespace App\Http\Controllers;

use App\Actions\Practical\SubmitPracticalTask;
use App\Enums\RubricCriterion;
use App\Models\Course;
use App\Models\PracticalGrading;
use App\Models\PracticalSubmission;
use App\Models\PracticalSubmissionFile;
use App\Models\PracticalTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The trainee's side of a practical task: read the brief, write it up, attach
 * the evidence, hand it in — and afterwards, read the marks.
 *
 * The rubric is shown *before* they start, not only after they are marked
 * against it. Assessing people against criteria they have not seen is how you
 * get the "I didn't know that counted" conversation.
 */
class PracticalTaskController extends Controller
{
    public function show(Request $request, Course $course, PracticalTask $task): Response
    {
        abort_unless($task->course_id === $course->id, 404);

        $this->authorize('view', $task);

        $user = $request->user();

        $submission = $task->latestSubmissionFor($user);
        $submission?->load(['files', 'gradings.scores', 'gradings.grader']);

        return Inertia::render('Practical/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
            ],

            'task' => [
                'title' => $task->title,
                'slug' => $task->slug,

                // Sanitised on the way out as well as in, same as lesson bodies.
                'brief' => Purifier::clean($task->brief, 'lesson'),

                'submission_instructions' => $task->submission_instructions,
                'expected_evidence' => $task->expected_evidence,
                'estimated_minutes' => $task->estimated_minutes,
                'lesson_title' => $task->lesson?->title,
            ],

            // Shown up front. They are entitled to know the standard.
            'rubric' => collect(RubricCriterion::cases())
                ->map(fn (RubricCriterion $c) => [
                    'key' => $c->value,
                    'label' => $c->label(),
                    'minimum' => $c->minimumToPass(),
                    'is_critical' => $c->isCritical(),
                    'descriptors' => $c->descriptors(),
                ])->all(),

            // camelCase because the Vue prop is passRule — Inertia passes prop
            // names through unchanged.
            'passRule' => [
                'total_needed' => RubricCriterion::PASS_TOTAL,
                'max_total' => RubricCriterion::maxTotal(),
            ],

            'submission' => $submission ? $this->presentSubmission($submission) : null,

            'can' => [
                'attempt' => $user->can('attempt', $task),
            ],
        ]);
    }

    public function start(Request $request, Course $course, PracticalTask $task, SubmitPracticalTask $submit): RedirectResponse
    {
        abort_unless($task->course_id === $course->id, 404);

        $this->authorize('attempt', $task);

        $submit->draftFor($request->user(), $task);

        return back();
    }

    public function saveDraft(Request $request, Course $course, PracticalTask $task, SubmitPracticalTask $submit): RedirectResponse
    {
        $submission = $this->openSubmission($request, $course, $task);

        $this->authorize('update', $submission);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:20000'],
        ]);

        $submit->saveDraft($submission, $data['body'] ?? '');

        return back()->with('status', 'Draft saved.');
    }

    public function submit(Request $request, Course $course, PracticalTask $task, SubmitPracticalTask $submit): RedirectResponse
    {
        $submission = $this->openSubmission($request, $course, $task);

        $this->authorize('submit', $submission);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:20', 'max:20000'],
        ], [
            'body.required' => 'Write up what you did before handing it in.',
            'body.min' => 'A sentence or two at minimum — the write-up is scored.',
        ]);

        $submit->submit($submission, $data['body']);

        return back()->with('status', 'Handed in. Your trainer will mark it.');
    }

    /**
     * Attach evidence.
     *
     * Straight to the private disk. Verification is a scored criterion, so this
     * is not an optional extra — but the file still never gets a public URL.
     */
    public function attach(Request $request, Course $course, PracticalTask $task): RedirectResponse
    {
        $submission = $this->openSubmission($request, $course, $task);

        $this->authorize('update', $submission);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt,csv,log',
            ],
        ], [
            'file.max' => 'Attachments are capped at 20 MB.',
            'file.mimes' => 'Screenshots, PDFs and plain logs only.',
        ]);

        $file = $request->file('file');

        $path = $file->store('practical-evidence/'.$submission->getKey(), 'private');

        $submission->files()->create([
            'disk' => 'private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return back()->with('status', 'Evidence attached.');
    }

    public function detach(Request $request, Course $course, PracticalTask $task, PracticalSubmissionFile $file): RedirectResponse
    {
        $submission = $this->openSubmission($request, $course, $task);

        abort_unless($file->practical_submission_id === $submission->getKey(), 404);

        $this->authorize('update', $submission);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return back()->with('status', 'Attachment removed.');
    }

    /**
     * The only route to an attachment's bytes.
     *
     * Authorised against the submission, so a trainee reaches their own and a
     * trainer reaches what they are allowed to read — the same rule the marking
     * screen uses.
     */
    public function download(PracticalSubmissionFile $file): StreamedResponse
    {
        $this->authorize('view', $file->submission);

        $disk = Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404);

        return $disk->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type ?: 'application/octet-stream',

                // Inline rendering of an upload is a stored-XSS risk if a
                // browser decides the "screenshot" is really HTML.
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=0, no-store',
            ],
            'attachment',
        );
    }

    /** The submission currently open for editing, or a 404. */
    private function openSubmission(Request $request, Course $course, PracticalTask $task): PracticalSubmission
    {
        abort_unless($task->course_id === $course->id, 404);

        $submission = $task->latestSubmissionFor($request->user());

        abort_unless($submission, 404);

        return $submission;
    }

    /** @return array<string, mixed> */
    private function presentSubmission(PracticalSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'status' => $submission->status->value,
            'status_label' => $submission->status->label(),
            'attempt_number' => $submission->attempt_number,
            'body' => $submission->body,
            'is_editable' => $submission->isEditable(),
            'submitted_at' => $submission->submitted_at?->toDayDateTimeString(),
            'returned_reason' => $submission->returned_reason,

            'total_score' => $submission->total_score,
            'passed' => $submission->passed,

            'files' => $submission->files->map(fn (PracticalSubmissionFile $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => $file->humanSize(),
                'download_url' => route('practical.files.download', $file),
            ])->all(),

            /*
             * Marks are released only once the submission has actually settled.
             * On a double-marked task that means both trainers agreeing — a
             * trainee must not read one marker's provisional view, still less
             * two that contradict each other.
             */
            'feedback' => $submission->finalised_at
                ? $submission->gradings->map(fn (PracticalGrading $grading) => [
                    'grader' => $grading->grader?->name,
                    'total' => $grading->total_score,
                    'passed' => $grading->passed,
                    'summary' => $grading->summary,
                    'explanation' => $grading->verdictExplanation(),
                    'scores' => $grading->scores->map(fn ($score) => [
                        'criterion' => $score->criterion->label(),
                        'score' => $score->score,
                        'descriptor' => $score->descriptor(),
                        'comment' => $score->comment,
                        'meets_standard' => $score->meetsStandard(),
                    ])->all(),
                ])->all()
                : [],
        ];
    }
}
