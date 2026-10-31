<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Workout;
use Illuminate\Support\Carbon;

class DayPayload
{
    public function __construct(private DayLog $log) {}

    /**
     * @return array{date: string, calisthenics: list<array<string, mixed>>, activities: list<array<string, mixed>>, practices: list<array<string, mixed>>}
     */
    public function for(Carbon $day): array
    {
        $log = $this->log->for($day);

        return [
            'date' => $day->toDateString(),
            'calisthenics' => $log['workouts']->map(fn (Workout $workout) => $this->workout($workout))->all(),
            'activities' => $log['activities']->map(fn (Activity $activity) => $this->activity($activity))->all(),
            'practices' => $log['practices']->map(fn (Activity $activity) => $this->activity($activity))->all(),
        ];
    }

    /**
     * @return array{type: string, name: string, status: string, done: int, total: int}
     */
    private function workout(Workout $workout): array
    {
        $done = $workout->items->where('completed', true)->count();

        return [
            'type' => 'calisthenics',
            'name' => $workout->routine_name,
            'status' => $workout->status->value,
            'done' => $done,
            'total' => $workout->items->count(),
        ];
    }

    /**
     * @return array{id: int, type: string, label: string, detail: string, partner: ?string, duration_minutes: ?int}
     */
    private function activity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'label' => $activity->type->label(),
            'detail' => $activity->detail(),
            'partner' => $activity->partner?->value,
            'duration_minutes' => $activity->duration_minutes,
        ];
    }
}
