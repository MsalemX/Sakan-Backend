<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StudentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->role || $user->role->name !== 'Student') {
            return response()->json(['message' => 'Unauthorized. Students only.'], 403);
        }

        return $next($request);
    }
}
