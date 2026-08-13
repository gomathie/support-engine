<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $certificates = $request->user()
            ->certificates()
            ->with('course:id,title,slug,category')
            ->latest('issued_at')
            ->get();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates->map(fn (Certificate $certificate) => [
                'id' => $certificate->id,
                'number' => $certificate->certificate_number,
                'course_title' => $certificate->course_title,
                'course_category' => $certificate->course?->category,
                'recipient_name' => $certificate->recipient_name,
                'score' => $certificate->score !== null ? (float) $certificate->score : null,
                'completed_at' => $certificate->completed_at->toIso8601String(),
                'issued_at' => $certificate->issued_at->toIso8601String(),

                // False while the queued render is still catching up.
                'is_ready' => $certificate->isRendered(),

                'download_url' => route('certificates.download', $certificate),
                'verification_url' => $certificate->verificationUrl(),
            ])->all(),
        ]);
    }

    public function download(Certificate $certificate): StreamedResponse
    {
        $this->authorize('download', $certificate);

        abort_unless($certificate->isRendered(), 404, 'This certificate is still being generated.');

        return Storage::disk($certificate->disk)->response(
            $certificate->path,
            $certificate->certificate_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Public verification. Deliberately shows the minimum that makes a
     * certificate checkable — who, what, when — and never the score, the email
     * address or anything else about the employee.
     */
    public function verify(string $token): Response
    {
        $certificate = Certificate::query()
            ->where('verification_token', $token)
            ->first();

        return Inertia::render('Certificates/Verify', [
            'certificate' => $certificate ? [
                'number' => $certificate->certificate_number,
                'recipient_name' => $certificate->recipient_name,
                'course_title' => $certificate->course_title,
                'completed_at' => $certificate->completed_at->toFormattedDateString(),
                'issued_at' => $certificate->issued_at->toFormattedDateString(),
            ] : null,
            'valid' => $certificate !== null,
        ]);
    }
}
