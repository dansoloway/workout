<?php

namespace App\Http\Controllers;

use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Workout;
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

        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);
        $days = [];

        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $days[] = [
                'date' => $cursor->copy(),
                'in_month' => $cursor->month === $start->month,
                'is_today' => $cursor->isSameDay(now()),
                'marker' => $this->marker($workouts->get($cursor->toDateString(), collect())),
            ];
        }

        return view('calendar.index', [
            'month' => $start,
            'days' => $days,
            'previous' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function show(string $date): View
    {
        $day = $this->date($date);

        $workouts = Workout::query()
            ->with('items')
            ->where('kind', RoutineKind::Workout)
            ->whereDate('workout_date', $day->toDateString())
            ->orderBy('id')
            ->get();

        return view('calendar.show', [
            'day' => $day,
            'workouts' => $workouts,
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

    private function date(string $value): Carbon
    {
        try {
            $day = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            abort(404);
        }

        if (! $day || $day->format('Y-m-d') !== $value) {
            abort(404);
        }

        return $day->startOfDay();
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     */
    private function marker(Collection $workouts): string
    {
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
