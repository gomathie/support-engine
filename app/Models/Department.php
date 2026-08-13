<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description'])]
class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $department): void {
            $department->slug ??= Str::slug($department->name);
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_manager')->withTimestamps();
    }

    public function assignmentRules(): HasMany
    {
        return $this->hasMany(AssignmentRule::class, 'target_id')
            ->where('target_type', 'department');
    }
}
