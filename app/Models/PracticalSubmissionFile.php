<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence attached to a practical submission — usually a screenshot.
 *
 * Same private-disk rule as lesson videos and resources: no public URL, reached
 * only through a controller that has run a policy first. The disk is recorded
 * per row so a later move to S3 does not strand what is already written.
 */
#[Fillable([
    'practical_submission_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
])]
class PracticalSubmissionFile extends Model
{
    use HasFactory;

    public function submission(): BelongsTo
    {
        return $this->belongsTo(PracticalSubmission::class, 'practical_submission_id');
    }

    public function humanSize(): ?string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $power > 1 ? 1 : 0).' '.$units[$power];
    }
}
