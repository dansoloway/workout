<?php

namespace App\Models;

use App\Enums\ExerciseType;
use Database\Factories\WorkoutItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workout_id',
    'exercise_id',
    'round_number',
    'sort_order',
    'name',
    'type',
    'target',
    'completed',
    'completed_at',
])]
class WorkoutItem extends Model
{
    /** @use HasFactory<WorkoutItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $builder): void {
            $builder->whereHas('workout');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'sort_order' => 'integer',
            'type' => ExerciseType::class,
            'target' => 'integer',
            'completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function targetLabel(): string
    {
        return $this->type->targetLabel($this->target);
    }
}
