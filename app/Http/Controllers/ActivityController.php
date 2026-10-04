<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Support\Day;
use Illuminate\Http\RedirectResponse;

class ActivityController extends Controller
{
    public function store(StoreActivityRequest $request, string $date): RedirectResponse
    {
        $day = Day::parseLoggable($date);
        $type = ActivityType::from($request->string('type')->toString());

        Activity::query()->create([
            'activity_date' => $day->toDateString(),
            'type' => $type,
            'partner' => $type === ActivityType::Tennis ? $request->input('partner') : null,
            'duration_minutes' => $type === ActivityType::Swimming ? $request->integer('duration_minutes') : null,
        ]);

        return back(fallback: Day::url($day));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $day = $activity->activity_date->copy();

        $activity->delete();

        return back(fallback: Day::url($day));
    }
}
