<?php

namespace App\Models;

use App\Enums\ExerciseType;
use App\Models\Concerns\OwnedByUser;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'routine_id',
    'name',
    'type',
    'default_target',
    'sort_order',
    'active',
])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory, OwnedByUser;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'default_target' => 'integer',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function workoutItems(): HasMany
    {
        return $this->hasMany(WorkoutItem::class);
    }

    public function targetLabel(): string
    {
        return $this->type->targetLabel($this->default_target);
    }
}
