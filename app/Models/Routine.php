<?php

namespace App\Models;

use App\Enums\RoutineKind;
use App\Models\Concerns\OwnedByUser;
use Database\Factories\RoutineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'kind',
    'rounds',
    'rest_seconds',
    'is_current',
    'active',
])]
class Routine extends Model
{
    /** @use HasFactory<RoutineFactory> */
    use HasFactory, OwnedByUser;

    protected static function booted(): void
    {
        static::saved(function (Routine $routine): void {
            if (! $routine->is_current) {
                return;
            }

            $kind = $routine->kind instanceof RoutineKind ? $routine->kind->value : $routine->kind;

            static::withoutGlobalScope('owner')
                ->where('owner_scope', $routine->owner_scope)
                ->where('kind', $kind)
                ->where('id', '!=', $routine->id)
                ->update(['is_current' => false]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => RoutineKind::class,
            'rounds' => 'integer',
            'rest_seconds' => 'integer',
            'is_current' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function scopeCurrent(Builder $query, RoutineKind $kind = RoutineKind::Workout): Builder
    {
        return $query
            ->where('kind', $kind->value)
            ->where('is_current', true)
            ->where('active', true)
            ->orderBy('id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class)->orderBy('sort_order')->orderBy('id');
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
}
