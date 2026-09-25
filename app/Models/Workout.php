<?php

namespace App\Models;

use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Concerns\OwnedByUser;
use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'routine_id',
    'routine_name',
    'kind',
    'workout_date',
    'rounds',
    'rest_seconds',
    'status',
    'started_at',
    'completed_at',
])]
class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory, OwnedByUser;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => RoutineKind::class,
            'workout_date' => 'date:Y-m-d',
            'rounds' => 'integer',
            'rest_seconds' => 'integer',
            'status' => WorkoutStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkoutItem::class)
            ->orderBy('round_number')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return array{done: int, total: int}
     */
    public function syncStatus(): array
    {
        $total = $this->items()->count();
        $done = $this->items()->where('completed', true)->count();

        if ($total > 0 && $done === $total) {
            $this->status = WorkoutStatus::Completed;
            $this->completed_at ??= now();
            $this->started_at ??= now();
        } elseif ($done > 0) {
            $this->status = WorkoutStatus::Partial;
            $this->completed_at = null;
            $this->started_at ??= now();
        } else {
            $this->status = WorkoutStatus::Pending;
            $this->completed_at = null;
            $this->started_at = null;
        }

        $this->save();

        return ['done' => $done, 'total' => $total];
    }
}
