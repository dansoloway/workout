<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * V1 is a single personal app, so rows are stored with a null user.
 * Signing in later scopes each person to their own routines and workouts.
 */
trait OwnedByUser
{
    public static function bootOwnedByUser(): void
    {
        static::addGlobalScope('owner', function (Builder $builder): void {
            $table = $builder->getModel()->getTable();

            if (auth()->check()) {
                $builder->where($table.'.user_id', auth()->id());
            } else {
                $builder->whereNull($table.'.user_id');
            }
        });

        static::saving(function ($model): void {
            if (! $model->exists && $model->user_id === null && auth()->check()) {
                $model->user_id = auth()->id();
            }

            $model->owner_scope = $model->user_id ?? 0;
        });
    }
}
