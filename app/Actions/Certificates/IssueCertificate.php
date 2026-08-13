<?php

namespace App\Actions\Certificates;

use App\Jobs\RenderCertificatePdf;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IssueCertificate
{
    /**
     * Idempotent. Called from RecalculateCourseProgress, which runs on every
     * lesson tick and every graded attempt, so it must be safe to call
     * repeatedly for an already-certified course.
     */
    public function handle(User $user, Course $course, ?CourseProgress $progress = null): ?Certificate
    {
        return DB::transaction(function () use ($user, $course, $progress): ?Certificate {
            $existing = Certificate::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $progress ??= CourseProgress::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (! $progress?->isComplete()) {
                return null;
            }

            $certificate = Certificate::query()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'certificate_number' => $this->nextNumber(),

                // Snapshotted: the PDF handed out must keep matching the record
                // even if the person or the course is renamed later.
                'recipient_name' => $user->certificateName(),
                'course_title' => $course->title,

                'score' => $progress->final_score,
                'completed_at' => $progress->completed_at ?? now(),
                'issued_at' => now(),
            ]);

            // Rendering a PDF is slow and must not sit in the request that
            // ticked the last checkbox. The record exists immediately; the file
            // catches up.
            RenderCertificatePdf::dispatch($certificate);

            return $certificate;
        });
    }

    /**
     * Human-quotable, sequential per year: PILOT-2026-000042.
     *
     * Derived inside the same transaction as the insert, and the column is
     * unique, so a collision fails loudly rather than issuing a duplicate.
     */
    private function nextNumber(): string
    {
        $year = now()->year;
        $prefix = 'PILOT-'.$year.'-';

        $last = Certificate::query()
            ->where('certificate_number', 'like', $prefix.'%')
            ->orderByDesc('certificate_number')
            ->value('certificate_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
