<?php

namespace App\Http\Controllers;

use App\Enums\RoutineKind;
use App\Models\Routine;
use App\Models\Workout;
use App\Services\WorkoutGenerator;
use App\Support\Day;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CalisthenicsController extends Controller
{
    public function store(WorkoutGenerator $generator, string $date): RedirectResponse
    {
        $day = Day::parseLoggable($date);

        abort_unless(Routine::query()->current()->exists(), 404);

        $generator->forDate($day);

        return redirect()->route('calisthenics', $day->toDateString());
    }

    public function show(string $date): View|RedirectResponse
    {
        $day = Day::parse($date);

        $workout = Workout::query()
            ->with('items')
            ->where('kind', RoutineKind::Workout)
            ->whereDate('workout_date', $day->toDateString())
            ->orderBy('id')
            ->first();

        if (! $workout) {
            return redirect(Day::url($day));
        }

        return view('calisthenics', [
            'workout' => $workout,
            'dayUrl' => Day::url($day),
            'dayLabel' => $day->isToday() ? 'Today' : $day->format('M j'),
        ]);
    }
}
