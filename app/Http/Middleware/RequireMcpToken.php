<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('workout.mcp_token');

        if ($expected === '') {
            return response()->json(['message' => 'MCP token is not configured.'], 503);
        }

        $given = $request->bearerToken() ?? $request->header('X-Workout-Token');

        if (! is_string($given) || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
