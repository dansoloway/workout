<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Services\WorkoutGenerator;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function __invoke(WorkoutGenerator $generator): View
    {
        if (! Routine::query()->current()->exists()) {
            return view('today-empty');
        }

        return view('today', [
            'workout' => $generator->todaysWorkout(),
        ]);
    }
}
