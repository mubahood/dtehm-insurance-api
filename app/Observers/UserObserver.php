<?php

namespace App\Observers;

use App\Models\User;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * This fires AFTER a new user is created
     */
    public function created(User $user)
    {
        Log::info('============ USER OBSERVER: CREATED EVENT ============', [
            'user_id' => $user->id,
            'is_dtehm_member' => $user->is_dtehm_member,
            'is_dip_member' => $user->is_dip_member,
        ]);
        
        $this->handleMembershipCreation($user);
    }

    /**
     * Handle the User "updated" event.
     * This fires AFTER an existing user is updated
     */
    public function updated(User $user)
    {
        Log::info('============ USER OBSERVER: UPDATED EVENT ============', [
            'user_id' => $user->id,
            'is_dtehm_member' => $user->is_dtehm_member,
            'is_dip_member' => $user->is_dip_member,
            'isDirty_dtehm' => $user->isDirty('is_dtehm_member'),
            'isDirty_dip' => $user->isDirty('is_dip_member'),
        ]);
        
        // Only create memberships if the membership status changed
        // OR if it's set to Yes but no membership exists yet
        if ($user->isDirty('is_dtehm_member') || $user->isDirty('is_dip_member')) {
            $this->handleMembershipCreation($user);
        } else {
            // Even if not dirty, check if membership should exist but doesn't
            $this->ensureMembershipsExist($user);
        }
    }

    /**
     * Handle membership creation for both DTEHM and DIP
     */
    protected function handleMembershipCreation(User $user)
    {
        $adminUser = \Admin::user();
        $adminId = $adminUser ? $adminUser->id : 1; // Fallback to admin ID 1
        
        // Handle DTEHM Membership (76,000 UGX)
        if ($user->is_dtehm_member == 'Yes') {
            $this->createDtehmMembershipIfNeeded($user, $adminId);
        }
        
        // Handle DIP Membership (20,000 UGX)
        if ($user->is_dip_member == 'Yes') {
            $this->createDipMembershipIfNeeded($user, $adminId);
        }
    }

    /**
     * Ensure memberships exist even if status hasn't changed
     */
    protected function ensureMembershipsExist(User $user)
    {
        $adminUser = \Admin::user();
        $adminId = $adminUser ? $adminUser->id : 1;
        
        if ($user->is_dtehm_member == 'Yes') {
            $this->createDtehmMembershipIfNeeded($user, $adminId);
        }
        
        if ($user->is_dip_member == 'Yes') {
            $this->createDipMembershipIfNeeded($user, $adminId);
        }
    }

    /**
     * Create DTEHM membership if it doesn't exist
     */
    protected function createDtehmMembershipIfNeeded(User $user, int $adminId)
    {
        // Check if DTEHM membership already exists
        $existingDtehm = DtehmMembership::where('user_id', $user->id)
            ->where('status', 'CONFIRMED')
            ->first();
        
        if (!$existingDtehm) {
            Log::info('OBSERVER: Creating DTEHM membership', ['user_id' => $user->id]);
            
            try {
                // Create DTEHM Membership (76,000 UGX)
                $dtehm = DtehmMembership::create([
                    'user_id' => $user->id,
                    'amount' => 76000,
                    'status' => 'CONFIRMED',
                    'payment_method' => 'CASH',
                    'registered_by_id' => $adminId,
                    'created_by' => $adminId,
                    'confirmed_by' => $adminId,
                    'confirmed_at' => now(),
                    'payment_date' => now(),
                    'description' => 'Auto-created by Observer during user registration',
                ]);
                
                // Update user model with DTEHM membership info (use saveQuietly to avoid infinite loop)
                $user->dtehm_membership_paid_at = now();
                $user->dtehm_membership_amount = 76000;
                $user->dtehm_membership_payment_id = $dtehm->id;
                $user->dtehm_membership_is_paid = 'Yes';
                $user->dtehm_membership_paid_date = now();
                $user->dtehm_member_membership_date = now();
                $user->saveQuietly(); // Save without triggering observers again
                
                Log::info('OBSERVER: DTEHM membership created successfully', [
                    'dtehm_id' => $dtehm->id,
                    'user_id' => $user->id,
                ]);
                
                // Create sponsor commission (10,000 UGX)
                $this->createSponsorCommission($user, $dtehm->id);
                
                admin_success('Success', 'DTEHM membership (UGX 76,000) created and marked as PAID');
            } catch (\Exception $e) {
                Log::error('OBSERVER: Failed to create DTEHM membership', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('OBSERVER: DTEHM membership already exists', [
                'user_id' => $user->id,
                'dtehm_id' => $existingDtehm->id,
            ]);
        }
    }

    /**
     * Create DIP membership if it doesn't exist
     */
    protected function createDipMembershipIfNeeded(User $user, int $adminId)
    {
        // Check if DIP membership already exists
        $existingDip = MembershipPayment::where('user_id', $user->id)
            ->where('status', 'CONFIRMED')
            ->first();
        
        if (!$existingDip) {
            Log::info('OBSERVER: Creating DIP membership', ['user_id' => $user->id]);
            
            try {
                // Create Regular DIP Membership (20,000 UGX)
                $membership = MembershipPayment::create([
                    'user_id' => $user->id,
                    'amount' => 20000,
                    'membership_type' => 'LIFE',
                    'status' => 'CONFIRMED',
                    'payment_method' => 'CASH',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'registered_by_id' => $adminId,
                    'description' => 'Auto-created by Observer during user registration',
                ]);
                
                Log::info('OBSERVER: DIP membership created successfully', [
                    'membership_id' => $membership->id,
                    'user_id' => $user->id,
                ]);
                
                admin_success('Success', 'DIP membership (UGX 20,000) created and marked as PAID');
            } catch (\Exception $e) {
                Log::error('OBSERVER: Failed to create DIP membership', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('OBSERVER: DIP membership already exists', [
                'user_id' => $user->id,
                'membership_id' => $existingDip->id,
            ]);
        }
    }
    
    /**
     * Create sponsor commission for DTEHM membership payment
     * 
     * @param User $user The user who paid the membership
     * @param int $membershipId The DTEHM membership ID
     * @return void
     */
    protected function createSponsorCommission(User $user, int $membershipId)
    {
        try {
            Log::info('============ OBSERVER: CREATING SPONSOR COMMISSION ============', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'sponsor_id' => $user->sponsor_id,
                'membership_id' => $membershipId,
            ]);
            
            // Check if user has a sponsor
            if (empty($user->sponsor_id)) {
                Log::warning('OBSERVER: User has no sponsor ID - skipping commission', ['user_id' => $user->id]);
                return;
            }
            
            // Find the sponsor user
            $sponsor = User::where('business_name', $user->sponsor_id)->first();
            if (!$sponsor) {
                $sponsor = User::where('dtehm_member_id', $user->sponsor_id)->first();
            }
            
            if (!$sponsor) {
                Log::error('OBSERVER: Sponsor not found in system', [
                    'sponsor_id' => $user->sponsor_id,
                    'user_id' => $user->id,
                ]);
                return;
            }
            
            Log::info('OBSERVER: Sponsor found', [
                'sponsor_user_id' => $sponsor->id,
                'sponsor_name' => $sponsor->name,
                'sponsor_dip_id' => $sponsor->business_name,
                'sponsor_dtehm_id' => $sponsor->dtehm_member_id,
            ]);
            
            // Check if commission already exists for this membership
            $existingCommission = \App\Models\AccountTransaction::where('user_id', $sponsor->id)
                ->where('source', 'deposit')
                ->where('description', 'LIKE', '%DTEHM Referral Commission%')
                ->where('description', 'LIKE', '%Membership ID: ' . $membershipId . '%')
                ->first();
            
            if ($existingCommission) {
                Log::warning('OBSERVER: Commission already exists for this membership', [
                    'transaction_id' => $existingCommission->id,
                    'sponsor_id' => $sponsor->id,
                    'membership_id' => $membershipId,
                ]);
                return;
            }
            
            // Create commission transaction (10,000 UGX)
            $adminUser = \Admin::user();
            $commission = \App\Models\AccountTransaction::create([
                'user_id' => $sponsor->id,
                'amount' => 10000,
                'transaction_date' => now(),
                'description' => "DTEHM Referral Commission: {$user->name} (Phone: {$user->phone_number}) paid DTEHM membership. Membership ID: {$membershipId}",
                'source' => 'deposit',
                'created_by_id' => $adminUser ? $adminUser->id : 1, // Fallback to admin ID 1
            ]);
            
            Log::info('OBSERVER: Sponsor commission created successfully', [
                'transaction_id' => $commission->id,
                'sponsor_id' => $sponsor->id,
                'sponsor_name' => $sponsor->name,
                'amount' => 10000,
                'referred_user' => $user->name,
                'membership_id' => $membershipId,
            ]);
            
        } catch (\Exception $e) {
            Log::error('OBSERVER: Failed to create sponsor commission', [
                'user_id' => $user->id,
                'sponsor_id' => $user->sponsor_id ?? 'NONE',
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
