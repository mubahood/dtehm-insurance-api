<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Use after ApiJwtAuth: the user id in the URL must be the caller's own, unless the caller is an admin. */
class ApiSelfOrAdmin
{
    public function handle(Request $request, Closure $next, string $parameter = 'userId')
    {
        $actor = $request->userModel;

        if (!$actor->isAdmin() && (int) $request->route($parameter) !== (int) $actor->id) {
            return response()->json([
                'code' => 0,
                'status' => 403,
                'message' => 'You can only view your own records.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
