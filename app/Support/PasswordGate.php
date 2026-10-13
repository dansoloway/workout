<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class PasswordGate
{
    public const COOKIE = 'workout_remember';

    public const REMEMBER_MINUTES = 60 * 24 * 30;

    public static function disabled(): bool
    {
        return self::password() === '';
    }

    public static function matches(string $given): bool
    {
        return ! self::disabled() && hash_equals(self::password(), $given);
    }

    public static function unlocked(Request $request): bool
    {
        if (self::disabled() || $request->session()->get('workout_unlocked') === true) {
            return true;
        }

        if (! self::remembered($request)) {
            return false;
        }

        $request->session()->put('workout_unlocked', true);

        return true;
    }

    public static function unlock(Request $request, bool $remember): void
    {
        $request->session()->regenerate();
        $request->session()->put('workout_unlocked', true);

        if ($remember) {
            Cookie::queue(self::COOKIE, self::token(), self::REMEMBER_MINUTES);
        }
    }

    private static function remembered(Request $request): bool
    {
        $cookie = $request->cookie(self::COOKIE);

        return is_string($cookie) && hash_equals(self::token(), $cookie);
    }

    // Tied to the password so changing it signs out remembered devices.
    private static function token(): string
    {
        return hash_hmac('sha256', 'remember|'.self::password(), (string) config('app.key'));
    }

    private static function password(): string
    {
        return (string) config('workout.password');
    }
}
