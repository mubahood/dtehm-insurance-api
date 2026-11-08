<?php

namespace App\Http\Middleware;

use App\Models\MultipleOrder;
use Closure;
use Illuminate\Http\Request;

/**
 * Use after ApiJwtAuth on /multiple-orders/{id} routes.
 *
 * "owner": the buyer or an admin (paying, cancelling, converting).
 * "view":  also the order's sponsor and stockist, who earn commission on it.
 *
 * An order that does not exist passes through, so the controller still answers its own 404.
 */
class ApiOrderAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'view')
    {
        $order = MultipleOrder::find($request->route('id'));
        if (!$order) {
            return $next($request);
        }

        $actor = $request->userModel;
        $actorId = (int) $actor->id;
        $allowed = $actor->isAdmin() || (int) $order->user_id === $actorId;
        if (!$allowed && $mode === 'view') {
            $allowed = (int) $order->sponsor_user_id === $actorId || (int) $order->stockist_user_id === $actorId;
        }

        if (!$allowed) {
            return response()->json([
                'code' => 0,
                'status' => 403,
                'message' => 'You do not have access to this order.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
