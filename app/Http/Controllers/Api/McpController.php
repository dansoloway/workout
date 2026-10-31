<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Workout;
use App\Services\ActivitySummary;
use App\Services\DayPayload;
use App\Support\Day;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class McpController extends Controller
{
    public function day(Request $request, DayPayload $payload): JsonResponse
    {
        $value = $request->string('date')->trim()->toString();
        $day = $value === '' ? today() : Day::parse($value);

        return response()->json($payload->for($day));
    }

    public function summary(Request $request, ActivitySummary $summary): JsonResponse
    {
        $period = $request->string('period')->trim()->toString();

        if (! in_array($period, ['week', 'month'], true)) {
            return response()->json(['message' => 'period must be week or month.'], 422);
        }

        if ($period === 'week') {
            $from = today()->startOfWeek(Carbon::SUNDAY);
            $to = $from->copy()->endOfWeek(Carbon::SATURDAY);

            return response()->json([
                'period' => 'week',
                ...$summary->between($from, $to),
            ]);
        }

        $monthValue = $request->string('month')->trim()->toString();
        $month = $monthValue === ''
            ? now()->startOfMonth()
            : $this->parseMonth($monthValue);

        return response()->json([
            'period' => 'month',
            'month' => $month->format('Y-m'),
            ...$summary->between($month, $month->copy()->endOfMonth()),
        ]);
    }

    public function recent(Request $request, DayPayload $payload): JsonResponse
    {
        $days = $request->integer('days', 14);
        $days = max(1, min($days, 90));

        $from = today()->subDays($days - 1)->startOfDay();
        $to = today()->endOfDay();

        $workoutDates = Workout::query()
            ->where('kind', RoutineKind::Workout)
            ->where('status', '!=', WorkoutStatus::Pending)
            ->whereDate('workout_date', '>=', $from->toDateString())
            ->whereDate('workout_date', '<=', $to->toDateString())
            ->pluck('workout_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $activityDates = Activity::query()
            ->whereDate('activity_date', '>=', $from->toDateString())
            ->whereDate('activity_date', '<=', $to->toDateString())
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $dates = $workoutDates
            ->merge($activityDates)
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $dates->map(fn (string $date) => $payload->for(Day::parse($date)))->all(),
        ]);
    }

    private function parseMonth(string $value): Carbon
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', $value);
        } catch (\Throwable) {
            abort(422, 'month must be Y-m.');
        }

        if (! $month || $month->format('Y-m') !== $value) {
            abort(422, 'month must be Y-m.');
        }

        return $month->startOfMonth();
    }
}
