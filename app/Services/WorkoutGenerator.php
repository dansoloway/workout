<?php

namespace App\Services;

use App\Enums\WorkoutStatus;
use App\Models\Routine;
use App\Models\Workout;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkoutGenerator
{
    public function todaysWorkout(?Routine $routine = null): Workout
    {
        return $this->forDate(now(), $routine);
    }

    public function forDate(CarbonInterface|string $date, ?Routine $routine = null): Workout
    {
        $routine ??= Routine::query()->current()->firstOrFail();
        $day = Carbon::parse($date)->toDateString();

        try {
            $workout = DB::transaction(function () use ($routine, $day) {
                $existing = $this->findForDay($routine, $day, lock: true);

                if ($existing) {
                    return $existing;
                }

                $workout = Workout::query()->create([
                    'user_id' => $routine->user_id,
                    'routine_id' => $routine->id,
                    'routine_name' => $routine->name,
                    'kind' => $routine->kind,
                    'workout_date' => $day,
                    'rounds' => $routine->rounds,
                    'rest_seconds' => $routine->rest_seconds,
                    'status' => WorkoutStatus::Pending,
                ]);

                $this->snapshotItems($workout, $routine);

                return $workout;
            });
        } catch (UniqueConstraintViolationException) {
            $workout = $this->findForDay($routine, $day);

            if (! $workout) {
                throw new RuntimeException('The workout for '.$day.' could not be saved.');
            }
        }

        $workout->load('items');

        return $workout;
    }

    private function findForDay(Routine $routine, string $day, bool $lock = false): ?Workout
    {
        $query = Workout::query()
            ->where('routine_id', $routine->id)
            ->whereDate('workout_date', $day);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function snapshotItems(Workout $workout, Routine $routine): void
    {
        $exercises = $routine->exercises()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        for ($round = 1; $round <= $routine->rounds; $round++) {
            foreach ($exercises as $exercise) {
                $workout->items()->create([
                    'exercise_id' => $exercise->id,
                    'round_number' => $round,
                    'sort_order' => $exercise->sort_order,
                    'name' => $exercise->name,
                    'type' => $exercise->type,
                    'target' => $exercise->default_target,
                    'completed' => false,
                ]);
            }
        }
    }
}
