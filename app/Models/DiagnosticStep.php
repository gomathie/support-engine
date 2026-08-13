<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['diagnostic_tree_id', 'prompt', 'layer', 'fix', 'position'])]
class DiagnosticStep extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'layer' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $step): void {
            if ($step->position === null || $step->position === 0) {
                $step->position = (int) static::query()
                    ->where('diagnostic_tree_id', $step->diagnostic_tree_id)
                    ->max('position') + 1;
            }
        });
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTree::class, 'diagnostic_tree_id');
    }
}
