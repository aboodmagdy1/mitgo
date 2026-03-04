<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthenticated')
            ], 401);
        }

        // Check if user has any of the required roles using Spatie's hasRole method
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'message' => __('You are not authorized to access this resource')
            ], 403);
        }

        return $next($request);
    }
}