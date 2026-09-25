<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->allowed($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password required.'], 401);
        }

        return redirect()->route('enter');
    }

    private function allowed(Request $request): bool
    {
        if ((string) config('workout.password') === '') {
            return true;
        }

        if ($request->routeIs('enter', 'enter.store', 'manifest')) {
            return true;
        }

        return $request->session()->get('workout_unlocked') === true;
    }
}
