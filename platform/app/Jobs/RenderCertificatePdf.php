<?php

namespace App\Jobs;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class RenderCertificatePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly Certificate $certificate,
    ) {}

    public function handle(): void
    {
        if ($this->certificate->isRendered()) {
            return;
        }

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $this->certificate,
        ])->setPaper('a4', 'landscape');

        // Private disk. Certificates carry an employee's name and score, so they
        // are served through a policy-checked route, never a public URL.
        $disk = 'private';
        $path = 'certificates/'.$this->certificate->certificate_number.'.pdf';

        Storage::disk($disk)->put($path, $pdf->output());

        $this->certificate->forceFill([
            'disk' => $disk,
            'path' => $path,
        ])->save();
    }
}
