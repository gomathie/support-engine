<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'lesson_id',
    'name',
    'description',
    'disk',
    'path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'is_downloadable',
    'position',
    'uploaded_by',
])]
class LessonResource extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_downloadable' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Delete the file when the row goes. Without this the private disk
        // accumulates orphans that nothing can reach or clean up.
        static::deleted(function (self $resource): void {
            Storage::disk($resource->disk)->delete($resource->path);
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Never a public URL. Files sit on a private disk and are streamed through
     * a policy-checked controller so internal documents cannot leak by URL.
     */
    public function downloadRoute(): string
    {
        return route('resources.download', $this);
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;

        if (! $bytes) {
            return '—';
        }

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }
}
