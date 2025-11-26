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
        // PRIORITY 1: Try JWT token authentication first (matches Flutter app implementation)
        // Flutter sends: Authorization, Tok, and tok headers with Bearer token
        $user = null;
        
        // Check for JWT token in multiple headers (Authorization, Tok, tok)
        $token = null;
        if ($request->header('Authorization')) {
            $token = $request->header('Authorization');
        } elseif ($request->header('Tok')) {
            $token = $request->header('Tok');
        } elseif ($request->header('tok')) {
            $token = $request->header('tok');
        }

        if ($token) {
            // Extract token from "Bearer {token}" format
            $token = str_replace('Bearer ', '', $token);
            
            try {
                // Use JWT auth to get user
                $user = auth('api')->setToken($token)->user();
                \Log::info('EnsureTokenIsValid - JWT token authentication: ' . ($user ? 'SUCCESS (User ID: '.$user->id.')' : 'FAILED'));
            } catch (\Exception $e) {
                \Log::warning('EnsureTokenIsValid - JWT token invalid: ' . $e->getMessage());
            }
        }

        // PRIORITY 2: Fallback to User-Id header if JWT fails
        if ($user == null) {
            $user_id = 0;
            
            // Check multiple header names to match frontend implementation
            if ($request->header('User-Id')) {
                $user_id = (int) $request->header('User-Id');
            } elseif ($request->header('HTTP_USER_ID')) {
                $user_id = (int) $request->header('HTTP_USER_ID');
            } elseif ($request->header('user_id')) {
                $user_id = (int) $request->header('user_id');
            }

            \Log::info('EnsureTokenIsValid - Trying User-Id header: ' . $user_id);
            
            if ($user_id > 0) {
                // Find the user in the administrators table (matching working endpoints)
                $user = Administrator::find($user_id);
                \Log::info('EnsureTokenIsValid - User-Id lookup: ' . ($user ? 'Found' : 'NOT FOUND'));
            }
        }
        
        // If still no user found, return error
        if ($user == null) {
            \Log::error('EnsureTokenIsValid - No valid authentication provided');
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Authentication required. Provide valid JWT token or User-Id header.',
                'data' => null
            ], 401);
        }

        // Add user to request for controller access
        $request->user = $user->id;
        $request->userModel = $user;

        \Log::info('EnsureTokenIsValid - Authenticated user ID: ' . $user->id);
        $response = $next($request);
        
        return $response;
    }
}
