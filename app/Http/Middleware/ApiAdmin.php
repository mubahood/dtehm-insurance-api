<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Use after ApiJwtAuth: only admin/manager accounts may pass. */
class ApiAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->userModel ?? null;
        if (!$user || !method_exists($user, 'isAdmin') || !$user->isAdmin()) {
            return response()->json(['code' => 0, 'status' => 0, 'message' => 'Admin privileges required.', 'data' => null], 403);
        }
        return $next($request);
    }
}
