<?php

namespace App\Services;

use App\Enums\RoutineKind;
use App\Models\Activity;
use App\Models\Routine;
use App\Models\Workout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DayLog
{
    /**
     * @return array{day: Carbon, workouts: Collection<int, Workout>, activities: Collection<int, Activity>, practices: Collection<int, Activity>, hasRoutine: bool, canLog: bool}
     */
    public function for(Carbon $day): array
    {
        [$activities, $practices] = Activity::query()
            ->whereDate('activity_date', $day->toDateString())
            ->orderBy('id')
            ->get()
            ->partition(fn (Activity $activity) => $activity->type->isWorkout());

        return [
            'day' => $day,
            'workouts' => Workout::query()
                ->with('items')
                ->where('kind', RoutineKind::Workout)
                ->whereDate('workout_date', $day->toDateString())
                ->orderBy('id')
                ->get(),
            'activities' => $activities->values(),
            'practices' => $practices->values(),
            'hasRoutine' => Routine::query()->current()->exists(),
            'canLog' => ! $day->isAfter(today()),
        ];
    }
}
