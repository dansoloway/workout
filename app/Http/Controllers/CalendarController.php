<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Activity;
use App\Models\Workout;
use App\Services\ActivitySummary;
use App\Services\DayLog;
use App\Support\Day;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(private ActivitySummary $summary) {}

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
            [$workoutActivities, $practices] = $activities->get($key, collect())
                ->partition(fn (Activity $activity) => $activity->type->isWorkout());

            $days[] = [
                'date' => $cursor->copy(),
                'in_month' => $cursor->month === $start->month,
                'is_today' => $cursor->isSameDay(now()),
                'marker' => $this->marker($workouts->get($key, collect()), $workoutActivities),
                'practices' => array_values(array_filter(
                    ActivityType::practices(),
                    fn (ActivityType $type) => $practices->contains('type', $type),
                )),
            ];
        }

        $weekStart = today()->startOfWeek(Carbon::SUNDAY);

        return view('calendar.index', [
            'month' => $start,
            'days' => $days,
            'previous' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'week' => $start->isSameMonth(today())
                ? $this->summary->between($weekStart, $weekStart->copy()->endOfWeek(Carbon::SATURDAY))
                : null,
            'monthSummary' => $this->summary->between($start, $end),
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
