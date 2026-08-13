<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'course_id',
    'certificate_number',
    'recipient_name',
    'course_title',
    'score',
    'completed_at',
    'issued_at',
    'disk',
    'path',
    'verification_token',
])]
class Certificate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'completed_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $certificate): void {
            $certificate->verification_token ??= Str::random(48);
        });

        static::deleted(function (self $certificate): void {
            if ($certificate->path) {
                Storage::disk($certificate->disk)->delete($certificate->path);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'certificate_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** False while the queued render is still pending. */
    public function isRendered(): bool
    {
        return $this->path !== null && Storage::disk($this->disk)->exists($this->path);
    }

    public function verificationUrl(): string
    {
        return route('certificates.verify', $this->verification_token);
    }
}
