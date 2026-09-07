<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A domain of expertise — Objects & Sensors, Reporting, Escalation.
 *
 * Levels are held per area, because somebody can be competent at reporting and
 * not at devices, and a single overall level would hide that.
 */
#[Fillable(['name', 'slug', 'description', 'position'])]
class CompetencyArea extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $area): void {
            $area->slug ??= Str::slug($area->name);
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
}
