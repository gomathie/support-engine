<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A rung on the competency ladder — Basic, Second, Third.
 *
 * Ordered by `position`, which is what "you cannot hold Second without Basic"
 * is checked against.
 */
#[Fillable(['name', 'slug', 'description', 'position'])]
class Level extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $level): void {
            $level->slug ??= Str::slug($level->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(LevelRequirement::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(TraineeLevel::class);
    }

    /** The rung immediately below this one, if any. */
    public function previous(): ?self
    {
        return static::query()
            ->where('position', '<', $this->position)
            ->orderByDesc('position')
            ->first();
    }
}
