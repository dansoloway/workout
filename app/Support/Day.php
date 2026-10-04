<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Day
{
    public static function parse(string $value): Carbon
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

    public static function parseLoggable(string $value): Carbon
    {
        $day = self::parse($value);

        abort_if($day->isAfter(today()), 404);

        return $day;
    }

    public static function url(Carbon $day): string
    {
        return $day->isToday()
            ? route('today')
            : route('calendar.show', $day->toDateString());
    }
}
