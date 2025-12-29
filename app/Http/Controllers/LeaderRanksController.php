<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LeaderRanksController extends Controller
{
    /**
     * Get leader ranks information for a user
     * GET /api/leader-ranks/{user_id}
     */
    public function getUserRanks(Request $request, $user_id = null)
    {
        try {
            // If specific user_id provided in URL, use that
            // Otherwise, get authenticated user
            if (!$user_id) {
                $authUser = Utils::get_user($request);
                if (!$authUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not authenticated'
                    ], 401);
                }
                $user_id = $authUser->id;
            }

            $user = User::findOrFail($user_id);
            
            // Get total points (assuming you have a points column)
            // You can modify this based on your actual points calculation logic
            $totalPoints = $this->calculateUserPoints($user);

            // Define all ranks with their requirements
            $ranks = $this->getRankDefinitions();

            // Determine current rank
            $currentRank = $this->determineRank($totalPoints, $ranks);

            // Calculate progress to next rank
            $nextRank = $this->getNextRank($currentRank, $ranks);
            $progress = $this->calculateProgress($totalPoints, $currentRank, $nextRank, $ranks);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name ?? '',
                        'last_name' => $user->last_name ?? '',
                        'dtehm_member_id' => $user->dtehm_member_id ?? '',
                        'phone_number' => $user->phone_number ?? '',
                        'avatar' => $user->avatar ?? '',
                    ],
                    'total_points' => $totalPoints,
                    'current_rank' => $currentRank,
                    'next_rank' => $nextRank,
                    'progress' => $progress,
                    'ranks' => $ranks,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get leader ranks', [
                'user_id' => $user_id,
                'request_user' => $request->user ?? 'not set',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load leader ranks data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate user's total points including all downline points
     * Leader points = own points + all downline member points
     * Points are calculated from ordered_items.points_earned (actual sales)
     */
    private function calculateUserPoints($user)
    {
        // Calculate user's own points from their sales (ordered_items table)
        $ownPoints = (float) DB::table('ordered_items')
            ->where('dtehm_user_id', $user->id)
            ->where('item_is_paid', 'Yes')
            ->sum('points_earned');
        
        // Get all downline member IDs recursively
        $downlineIds = $this->getAllDownlineIds($user->id);
        
        // Calculate total downline points from their sales
        $downlinePoints = 0;
        if (!empty($downlineIds)) {
            $downlinePoints = (float) DB::table('ordered_items')
                ->whereIn('dtehm_user_id', $downlineIds)
                ->where('item_is_paid', 'Yes')
                ->sum('points_earned');
        }
        
        $totalPoints = $ownPoints + $downlinePoints;
        
        Log::info('Leader points calculation from sales', [
            'user_id' => $user->id,
            'own_points' => $ownPoints,
            'downline_count' => count($downlineIds),
            'downline_points' => $downlinePoints,
            'total_points' => $totalPoints,
        ]);
        
        return $totalPoints;
    }
    
    /**
     * Get all downline member IDs recursively
     * Returns array of user IDs who are in this user's network
     */
    private function getAllDownlineIds($userId, &$processed = [])
    {
        // Prevent infinite loops
        if (in_array($userId, $processed)) {
            return [];
        }
        
        $processed[] = $userId;
        $downlineIds = [];
        
        // Get direct referrals (users where sponsor_id = $userId)
        $directReferrals = User::where('sponsor_id', $userId)->pluck('id')->toArray();
        
        foreach ($directReferrals as $referralId) {
            $downlineIds[] = $referralId;
            
            // Recursively get their downlines
            $subDownline = $this->getAllDownlineIds($referralId, $processed);
            $downlineIds = array_merge($downlineIds, $subDownline);
        }
        
        return array_unique($downlineIds);
    }

    /**
     * Get rank definitions with requirements and rewards
     */
    private function getRankDefinitions()
    {
        return [
            [
                'id' => 1,
                'name' => 'Member',
                'short_name' => 'Member',
                'points_required' => 0,
                'reward' => 'Welcome to DTEHM',
                'color' => '#9E9E9E',
                'icon' => 'person',
            ],
            [
                'id' => 2,
                'name' => 'DTEHM Leader',
                'short_name' => 'Leader',
                'points_required' => 12000,
                'reward' => 'Smartphone',
                'color' => '#2196F3',
                'icon' => 'star',
            ],
            [
                'id' => 3,
                'name' => 'Star Leader',
                'short_name' => 'Star',
                'points_required' => 50000,
                'reward' => 'Motorcycle',
                'color' => '#FF9800',
                'icon' => 'star_border',
            ],
            [
                'id' => 4,
                'name' => 'Diamond Leader',
                'short_name' => 'Diamond',
                'points_required' => 80000,
                'reward' => 'International Trip',
                'color' => '#00BCD4',
                'icon' => 'diamond',
            ],
            [
                'id' => 5,
                'name' => 'Crown Leader',
                'short_name' => 'Crown',
                'points_required' => 120000,
                'reward' => 'Small Car',
                'color' => '#FFD700',
                'icon' => 'emoji_events',
            ],
            [
                'id' => 6,
                'name' => 'Senior Crown Leader',
                'short_name' => 'Sr. Crown',
                'points_required' => 300000,
                'reward' => 'Luxury Car',
                'color' => '#9C27B0',
                'icon' => 'military_tech',
            ],
            [
                'id' => 7,
                'name' => 'Parlaw Leader',
                'short_name' => 'Parlaw',
                'points_required' => 600000,
                'reward' => 'House Award',
                'color' => '#E91E63',
                'icon' => 'home',
            ],
            [
                'id' => 8,
                'name' => 'DTEHM Executive Director',
                'short_name' => 'Director',
                'points_required' => 1000000,
                'reward' => '2% Profit Share',
                'color' => '#4CAF50',
                'icon' => 'workspace_premium',
            ],
        ];
    }

    /**
     * Determine current rank based on points
     */
    private function determineRank($totalPoints, $ranks)
    {
        $currentRank = $ranks[0]; // Default to first rank (Member)

        foreach ($ranks as $rank) {
            if ($totalPoints >= $rank['points_required']) {
                $currentRank = $rank;
            } else {
                break;
            }
        }

        return $currentRank;
    }

    /**
     * Get next rank
     */
    private function getNextRank($currentRank, $ranks)
    {
        $currentIndex = array_search($currentRank, $ranks);
        
        if ($currentIndex !== false && $currentIndex < count($ranks) - 1) {
            return $ranks[$currentIndex + 1];
        }

        return null; // No next rank (already at highest)
    }

    /**
     * Calculate progress to next rank
     */
    private function calculateProgress($totalPoints, $currentRank, $nextRank, $ranks)
    {
        if (!$nextRank) {
            return [
                'percentage' => 100,
                'points_earned' => $totalPoints - $currentRank['points_required'],
                'points_needed' => 0,
                'is_max_rank' => true,
            ];
        }

        $pointsInCurrentRank = $totalPoints - $currentRank['points_required'];
        $pointsNeededForNext = $nextRank['points_required'] - $currentRank['points_required'];
        $percentage = ($pointsInCurrentRank / $pointsNeededForNext) * 100;

        return [
            'percentage' => min(100, max(0, $percentage)),
            'points_earned' => $pointsInCurrentRank,
            'points_needed' => $nextRank['points_required'] - $totalPoints,
            'is_max_rank' => false,
        ];
    }
}
