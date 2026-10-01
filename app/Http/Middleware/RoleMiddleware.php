<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user->loadMissing('role');
        $userRole = $user->role->name ?? null;

        if (! in_array($userRole, $roles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Required roles: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
