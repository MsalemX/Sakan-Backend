<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->role || $user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Unauthorized. Housing owners only.'], 403);
        }

        return $next($request);
    }
}
