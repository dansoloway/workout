<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Activity;
use App\Models\Workout;
use App\Services\DayLog;
use App\Support\Day;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->month($request);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $workouts = Workout::query()
            ->where('kind', RoutineKind::Workout)
            ->whereDate('workout_date', '>=', $start->toDateString())
            ->whereDate('workout_date', '<=', $end->toDateString())
            ->get()
            ->groupBy(fn (Workout $workout) => $workout->workout_date->toDateString());

        $activities = Activity::query()
            ->whereDate('activity_date', '>=', $start->toDateString())
            ->whereDate('activity_date', '<=', $end->toDateString())
            ->get()
            ->groupBy(fn (Activity $activity) => $activity->activity_date->toDateString());

        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);
        $days = [];

        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();

            $days[] = [
                'date' => $cursor->copy(),
                'in_month' => $cursor->month === $start->month,
                'is_today' => $cursor->isSameDay(now()),
                'marker' => $this->marker(
                    $workouts->get($key, collect()),
                    $activities->get($key, collect()),
                ),
            ];
        }

        $weekStart = today()->startOfWeek(Carbon::SUNDAY);

        return view('calendar.index', [
            'month' => $start,
            'days' => $days,
            'previous' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'week' => $start->isSameMonth(today())
                ? $this->summary($weekStart, $weekStart->copy()->endOfWeek(Carbon::SATURDAY))
                : null,
            'monthSummary' => $this->summary($start, $end),
        ]);
    }

    public function show(DayLog $log, string $date): View
    {
        return view('day', [
            ...$log->for(Day::parse($date)),
            'isTodayPage' => false,
        ]);
    }

    private function month(Request $request): Carbon
    {
        $value = $request->string('month')->trim()->toString();

        if ($value === '') {
            return now()->startOfMonth();
        }

        try {
            $month = Carbon::createFromFormat('!Y-m', $value);
        } catch (\Throwable) {
            abort(404);
        }

        if (! $month || $month->format('Y-m') !== $value) {
            abort(404);
        }

        return $month->startOfMonth();
    }

    /**
     * @return array{total: int, calisthenics: int, tennis: int, swimming: int, swim_minutes: int}
     */
    private function summary(Carbon $from, Carbon $to): array
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

        return [
            'total' => $calisthenics + $activities->count(),
            'calisthenics' => $calisthenics,
            'tennis' => $tennis->count(),
            'swimming' => $swimming->count(),
            'swim_minutes' => (int) $swimming->sum('duration_minutes'),
        ];
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @param  Collection<int, Activity>  $activities
     */
    private function marker(Collection $workouts, Collection $activities): string
    {
        if ($activities->isNotEmpty()) {
            return 'completed';
        }

        $recorded = $workouts->filter(
            fn (Workout $workout) => $workout->status !== WorkoutStatus::Pending,
        );

        if ($recorded->isEmpty()) {
            return 'none';
        }

        if ($recorded->every(fn (Workout $workout) => $workout->status === WorkoutStatus::Completed)) {
            return 'completed';
        }

        return 'partial';
    }
}
