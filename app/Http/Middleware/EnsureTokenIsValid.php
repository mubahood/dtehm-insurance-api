<?php

namespace App\Http\Middleware;

use App\Models\Utils;
use App\Models\User;
use Closure;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Identity comes from the verified JWT (Authorization / Tok / tok header).
        $user = Utils::jwt_user($request);

        // Legacy fallback: the spoofable User-Id header. Anyone can send "User-Id: <admin id>", so this is
        // honoured only while STRICT_API_AUTH=false (temporary migration switch) and every use is logged.
        if ($user == null && !config('auth.strict_api', true)) {
            $user_id = (int) ($request->header('User-Id') ?: ($request->header('user_id') ?: 0));
            if ($user_id > 0) {
                $user = Administrator::find($user_id);
                if ($user) {
                    \Log::warning('EnsureTokenIsValid: legacy User-Id fallback used', ['user_id' => $user_id, 'path' => $request->path()]);
                }
            }
        }

        // If still no user found, return error
        if ($user == null) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                // Exactly 'Unauthenticated': installed app builds treat this message as "log out and show the login screen"
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        // Add user to request for controller access
        $request->user = $user->id;
        $request->userModel = $user;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $next($request);
        
        return $response;
    }
}
