<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserRoleController extends Controller
{
    /**
     * Get user's roles
     * GET /api/user-roles
     * 
     * Headers: User-Id: {user_id}
     */
    public function index(Request $request)
    {
        try {
            $userId = $this->getUserIdFromHeader($request);
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User authentication required. Please provide User-Id header.'
                ], 401);
            }

            // Get user's roles
            $roles = DB::table('admin_role_users')
                ->join('admin_roles', 'admin_role_users.role_id', '=', 'admin_roles.id')
                ->where('admin_role_users.user_id', $userId)
                ->select([
                    'admin_roles.id',
                    'admin_roles.name',
                    'admin_roles.slug',
                    'admin_roles.created_at',
                    'admin_roles.updated_at'
                ])
                ->get();

            $formatted = $roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ];
            });

            Log::info('User roles retrieved', [
                'user_id' => $userId,
                'roles_count' => $formatted->count(),
            ]);

            return response()->json([
                'code' => 1,
                'message' => 'User roles retrieved successfully',
                'data' => $formatted
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user roles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve user roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has specific role by slug
     * GET /api/user-roles/check/{slug}
     * 
     * Headers: User-Id: {user_id}
     */
    public function checkRole(Request $request, $slug)
    {
        try {
            $userId = $this->getUserIdFromHeader($request);
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User authentication required'
                ], 401);
            }

            $hasRole = DB::table('admin_role_users')
                ->join('admin_roles', 'admin_role_users.role_id', '=', 'admin_roles.id')
                ->where('admin_role_users.user_id', $userId)
                ->where('admin_roles.slug', $slug)
                ->exists();

            return response()->json([
                'code' => 1,
                'message' => $hasRole ? 'User has the role' : 'User does not have the role',
                'data' => [
                    'slug' => $slug,
                    'has_role' => $hasRole
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check user role', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to check user role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user ID from request header
     */
    protected function getUserIdFromHeader(Request $request)
    {
        // Try to get from User-Id header
        $userId = $request->header('User-Id');
        
        if ($userId) {
            return (int) $userId;
        }

        // Try to get from authenticated user if using JWT
        if ($request->user()) {
            return $request->user()->id;
        }

        return null;
    }
}
