<?php

namespace App\Http\Controllers;

use App\Services\DayLog;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function __invoke(DayLog $log): View
    {
        return view('day', [
            ...$log->for(today()),
            'isTodayPage' => true,
        ]);
    }
}
