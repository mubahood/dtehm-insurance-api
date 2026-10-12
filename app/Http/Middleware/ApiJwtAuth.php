<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Strict API authentication.
 *
 * Identity comes ONLY from a valid JWT (Authorization / Tok / tok header).
 * The spoofable "User-Id" header is never trusted here.
 *
 * Rollout switch: config('auth.strict_api') / env STRICT_API_AUTH (default true).
 * When false, the legacy User-Id fallback is allowed (and logged) so already-installed
 * app builds with expired tokens keep working until a client update ships.
 */
class ApiJwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = $this->userFromJwt($request);

        if ($user === null && !config('auth.strict_api', true)) {
            $legacyId = (int) ($request->header('User-Id') ?: 0);
            if ($legacyId > 0) {
                $user = User::find($legacyId);
                if ($user) {
                    \Log::warning('ApiJwtAuth: legacy User-Id fallback used', ['user_id' => $legacyId, 'path' => $request->path()]);
                }
            }
        }

        if ($user === null) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                // Exactly 'Unauthenticated': installed app builds treat this message as "log out and show the login screen"
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        if (isset($user->status) && in_array(strtolower((string) $user->status), ['blocked', 'suspended', 'inactive', 'deleted', '0'], true)) {
            return response()->json(['code' => 0, 'status' => 0, 'message' => 'Your account is not active.', 'data' => null], 403);
        }

        $request->user = $user->id;
        $request->userModel = $user;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        auth('api')->setUser($user);

        return $next($request);
    }

    private function userFromJwt(Request $request): ?User
    {
        $u = \App\Models\Utils::jwt_user($request);
        return $u instanceof User ? $u : null;
    }
}
