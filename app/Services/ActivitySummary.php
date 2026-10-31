<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Activity;
use App\Models\Workout;
use Illuminate\Support\Carbon;

class ActivitySummary
{
    /**
     * @return array{from: string, to: string, total: int, calisthenics: int, tennis: int, swimming: int, swim_minutes: int, stretching: int, stretch_minutes: int, meditation: int, meditation_minutes: int}
     */
    public function between(Carbon $from, Carbon $to): array
    {
        $calisthenics = Workout::query()
            ->where('kind', RoutineKind::Workout)
            ->where('status', '!=', WorkoutStatus::Pending)
            ->whereDate('workout_date', '>=', $from->toDateString())
            ->whereDate('workout_date', '<=', $to->toDateString())
            ->count();

        $activities = Activity::query()
            ->whereDate('activity_date', '>=', $from->toDateString())
            ->whereDate('activity_date', '<=', $to->toDateString())
            ->get();

        $tennis = $activities->where('type', ActivityType::Tennis);
        $swimming = $activities->where('type', ActivityType::Swimming);
        $stretching = $activities->where('type', ActivityType::Stretching);
        $meditation = $activities->where('type', ActivityType::Meditation);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => $calisthenics + $tennis->count() + $swimming->count(),
            'calisthenics' => $calisthenics,
            'tennis' => $tennis->count(),
            'swimming' => $swimming->count(),
            'swim_minutes' => (int) $swimming->sum('duration_minutes'),
            'stretching' => $stretching->count(),
            'stretch_minutes' => (int) $stretching->sum('duration_minutes'),
            'meditation' => $meditation->count(),
            'meditation_minutes' => (int) $meditation->sum('duration_minutes'),
        ];
    }
}
