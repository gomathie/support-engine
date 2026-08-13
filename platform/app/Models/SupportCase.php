<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'diagnostic_tree_id',
    'customer',
    'object_ref',
    'step_states',
    'priority_code',
    'priority_reason',
    'customer_told',
    'status',
    'closed_at',
])]
class SupportCase extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'step_states' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTree::class, 'diagnostic_tree_id');
    }

    /** @return array<int, int> Step ids the employee has ruled out. */
    public function ruledOutStepIds(): array
    {
        return array_keys(array_filter($this->step_states ?? [], fn ($v) => $v === 'out'));
    }

    /** @return array<int, int> Step ids the employee marked as the cause. */
    public function foundStepIds(): array
    {
        return array_keys(array_filter($this->step_states ?? [], fn ($v) => $v === 'found'));
    }
}
