<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateOldUsers extends Command
{
    protected $signature = 'migrate:old-users {--dry-run : Run without saving to database} {--force : Skip confirmation prompts}';
    protected $description = 'Migrate users from old system (dtehmhea_app.json)';
    
    private $old_data;
    private $users_data = [];
    private $members_data = [];
    private $referrals_data = [];
    private $stats = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'orphaned' => 0,
        'orphaned_assigned' => 0,
    ];
    private $migration_log = [];
    
    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║          DTEHM USER MIGRATION - OLD SYSTEM → NEW SYSTEM        ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        // Phase 1: Load and validate data
        $this->info('📂 Phase 1: Loading old system data...');
        if (!$this->loadOldData()) {
            return 1;
        }
        
        // Phase 2: Pre-migration checks
        $this->info('✓ Phase 2: Validating data integrity...');
        if (!$this->validateOldData()) {
            return 1;
        }
        
        // Phase 3: Confirm truncate
        if (!$this->option('dry-run') && !$this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  WARNING: This will TRUNCATE the users table and delete all existing users!');
            $this->warn('⚠️  All commissions, memberships, and related data will also be cleared!');
            $this->newLine();
            
            if (!$this->confirm('Are you absolutely sure you want to proceed?', false)) {
                $this->error('❌ Migration cancelled by user.');
                return 1;
            }
            
            if (!$this->confirm('Type YES to confirm again', false)) {
                $this->error('❌ Migration cancelled by user.');
                return 1;
            }
        } elseif ($this->option('force') && !$this->option('dry-run')) {
            $this->warn('⚠️  Running with --force flag, skipping confirmations...');
        }
        
        // Phase 4: Execute migration
        $this->newLine();
        $this->info('🚀 Phase 3: Executing migration...');
        
        try {
            // Truncate outside of transaction (TRUNCATE doesn't support transactions)
            $this->truncateUsers();
            
            // Now start transaction for data insertion
            DB::beginTransaction();
            
            $this->migrateUsers();
            $this->updateNetworkConnections();
            $this->validateMigration();
            
            if ($this->option('dry-run')) {
                DB::rollback();
                $this->newLine();
                $this->warn('🔄 DRY RUN: All changes rolled back. No data was saved.');
            } else {
                DB::commit();
                $this->newLine();
                $this->info('✅ Migration completed successfully!');
            }
            
            $this->newLine();
            $this->displayStats();
            $this->saveMigrationLog();
            
            return 0;
            
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }
            $this->newLine();
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            $this->saveMigrationLog();
            return 1;
        }
    }
    
    private function loadOldData()
    {
        $json_file = base_path('dtehmhea_app.json');
        
        if (!file_exists($json_file)) {
            $this->error("❌ JSON file not found: {$json_file}");
            return false;
        }
        
        $this->info("   Loading: {$json_file}");
        $json_content = file_get_contents($json_file);
        $this->old_data = json_decode($json_content, true);
        
        if (!$this->old_data) {
            $this->error('❌ Failed to parse JSON file');
            return false;
        }
        
        // Extract tables
        foreach ($this->old_data as $record) {
            if (isset($record['type']) && $record['type'] == 'table') {
                $table_name = $record['name'] ?? '';
                $table_data = $record['data'] ?? [];
                
                if ($table_name == 'users') {
                    $this->users_data = $table_data;
                } elseif ($table_name == 'members') {
                    $this->members_data = $table_data;
                } elseif ($table_name == 'referrals') {
                    $this->referrals_data = $table_data;
                }
            }
        }
        
        $this->info("   ✓ Found " . count($this->users_data) . " users");
        $this->info("   ✓ Found " . count($this->members_data) . " members");
        $this->info("   ✓ Found " . count($this->referrals_data) . " referrals");
        
        return true;
    }
    
    private function validateOldData()
    {
        $errors = [];
        
        // Check for duplicate memberIDs and remove them (keep first occurrence)
        $member_ids = array_column($this->users_data, 'memberID');
        $duplicates = array_diff_assoc($member_ids, array_unique($member_ids));
        
        if (!empty($duplicates)) {
            $this->warn("   ⚠️  Duplicate memberIDs found - will keep first occurrence only");
            $seen = [];
            $cleaned_users = [];
            
            foreach ($this->users_data as $user) {
                $member_id = $user['memberID'];
                if (!isset($seen[$member_id])) {
                    $cleaned_users[] = $user;
                    $seen[$member_id] = true;
                } else {
                    $this->warn("      - Skipping duplicate: {$member_id} (userID: {$user['userID']})");
                    $this->migration_log[] = [
                        'member_id' => $member_id,
                        'status' => 'skipped_duplicate',
                        'message' => "Duplicate userID {$user['userID']} skipped, kept first occurrence",
                    ];
                }
            }
            
            $this->users_data = $cleaned_users;
            $this->info("   ✓ Removed " . (count($member_ids) - count($cleaned_users)) . " duplicate records");
        }
        
        // Verify DTEHM001 exists
        $dtehm001 = array_filter($this->users_data, function($u) {
            return $u['memberID'] == 'DTEHM001';
        });
        
        if (empty($dtehm001)) {
            $errors[] = "DTEHM001 not found in users data!";
        }
        
        // Check that all level1 sponsors exist
        $missing_sponsors = [];
        foreach ($this->referrals_data as $ref) {
            $level1 = $ref['level1'] ?? null;
            if ($level1 && $level1 != '') {
                $exists = array_filter($this->users_data, function($u) use ($level1) {
                    return $u['memberID'] == $level1;
                });
                if (empty($exists)) {
                    $missing_sponsors[] = "{$ref['referrer_id']} → {$level1}";
                }
            }
        }
        
        if (!empty($missing_sponsors)) {
            $this->warn("   ⚠️  Missing sponsors (will be assigned to DTEHM001): " . implode(', ', array_slice($missing_sponsors, 0, 5)));
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("   ❌ {$error}");
            }
            return false;
        }
        
        $this->info('   ✓ Data validation passed');
        return true;
    }
    
    private function truncateUsers()
    {
        $this->info('   🗑️  Truncating users table...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        DB::table('users')->truncate();
        
        // Truncate related tables if they exist
        $tables_to_truncate = ['commissions', 'memberships', 'account_transactions'];
        foreach ($tables_to_truncate as $table) {
            try {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->info("   ✓ Cleared {$table} table");
                }
            } catch (\Exception $e) {
                // Table doesn't exist or can't be truncated, skip it
            }
        }
        
        DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info('   ✓ Users table cleared and reset');
    }
    
    private function migrateUsers()
    {
        $this->info('   👥 Inserting users (Pass 1 - without network connections)...');
        
        // Sort users: DTEHM001 first, then by memberID
        usort($this->users_data, function($a, $b) {
            if ($a['memberID'] == 'DTEHM001') return -1;
            if ($b['memberID'] == 'DTEHM001') return 1;
            return strcmp($a['memberID'], $b['memberID']);
        });
        
        $this->stats['total'] = count($this->users_data);
        $bar = $this->output->createProgressBar($this->stats['total']);
        $bar->start();
        
        foreach ($this->users_data as $old_user) {
            try {
                $member_id = $old_user['memberID'];
                
                // Find corresponding member record
                $member_record = $this->findMember($member_id);
                
                // Prepare user data
                $user_data = [
                    'dtehm_member_id' => $member_id,
                    'username' => $old_user['username'] ?? $member_id,
                    'password' => bcrypt('111111'), // Default password
                    'first_name' => $member_record['fname'] ?? $old_user['fname'] ?? 'Unknown',
                    'last_name' => $member_record['lname'] ?? $old_user['lname'] ?? 'User',
                    'email' => $member_record['email'] ?? null,
                    'phone_number' => $this->normalizePhone($member_record['phone'] ?? ''),
                    'user_type' => ($member_id == 'DTEHM001') ? 'Admin' : 'Member',
                    'is_dtehm_member' => 'Yes',
                    'dtehm_membership_is_paid' => ($member_record && $member_record['membership'] == 'paid') ? 'Yes' : 'No',
                    'dtehm_membership_paid_amount' => 76000,
                    'status' => 'Active',
                    'sponsor_id' => null, // Will be filled in Pass 2
                    'parent_1' => null,
                    'parent_2' => null,
                    'parent_3' => null,
                    'parent_4' => null,
                    'parent_5' => null,
                    'parent_6' => null,
                    'parent_7' => null,
                    'parent_8' => null,
                    'parent_9' => null,
                    'parent_10' => null,
                    'reg_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Use DB::table to bypass Eloquent Observer which interferes with dtehm_member_id
                DB::table('users')->insert($user_data);
                
                $this->stats['success']++;
                $this->migration_log[] = [
                    'member_id' => $member_id,
                    'status' => 'success',
                    'message' => 'User created successfully',
                ];
                
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->migration_log[] = [
                    'member_id' => $old_user['memberID'] ?? 'unknown',
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Inserted {$this->stats['success']} users");
        
        if ($this->stats['failed'] > 0) {
            $this->error("   ❌ Failed to insert {$this->stats['failed']} users");
        }
    }
    
    private function updateNetworkConnections()
    {
        $this->info('   🔗 Updating network connections (Pass 2)...');
        
        // Clear any model cache
        DB::connection()->disableQueryLog();
        
        // Get DTEHM001 as root - use fresh query
        $root_user = DB::table('users')->where('dtehm_member_id', 'DTEHM001')->first();
        
        if (!$root_user) {
            $this->error('   Debugging: Total users in DB: ' . DB::table('users')->count());
            $this->error('   First 5 users: ');
            foreach (DB::table('users')->limit(5)->get() as $u) {
                $this->error("     - ID:{$u->id}, DTEHM:{$u->dtehm_member_id}");
            }
            throw new \Exception('CRITICAL: DTEHM001 not found after insertion!');
        }
        
        $root_user_id = $root_user->id;
        
        $bar = $this->output->createProgressBar(count($this->referrals_data));
        $bar->start();
        
        foreach ($this->referrals_data as $referral) {
            try {
                $member_id = $referral['referrer_id'];
                $user = User::where('dtehm_member_id', $member_id)->first();
                
                if (!$user) {
                    $this->migration_log[] = [
                        'member_id' => $member_id,
                        'status' => 'warning',
                        'message' => 'User not found in referrals update',
                    ];
                    $bar->advance();
                    continue;
                }
                
                // Skip DTEHM001 (root has no sponsor)
                if ($member_id == 'DTEHM001') {
                    $bar->advance();
                    continue;
                }
                
                $level1 = $referral['level1'] ?? null;
                
                // Handle orphaned users - assign to DTEHM001
                if (empty($level1) || $level1 == '' || $level1 == 'null') {
                    $level1 = 'DTEHM001';
                    $this->stats['orphaned']++;
                    $this->stats['orphaned_assigned']++;
                    $this->migration_log[] = [
                        'member_id' => $member_id,
                        'status' => 'orphan_assigned',
                        'message' => "Orphaned user assigned to DTEHM001 as sponsor",
                    ];
                }
                
                // Get sponsor by DTEHM ID
                $sponsor = User::where('dtehm_member_id', $level1)->first();
                
                if (!$sponsor) {
                    // If sponsor not found, default to DTEHM001
                    $sponsor = User::where('dtehm_member_id', 'DTEHM001')->first();
                    $this->stats['orphaned_assigned']++;
                    $this->migration_log[] = [
                        'member_id' => $member_id,
                        'status' => 'sponsor_missing',
                        'message' => "Sponsor {$level1} not found, assigned to DTEHM001",
                    ];
                }
                
                // CORRECT MAPPING:
                // sponsor_id = DTEHM ID (varchar) e.g., "DTEHM001"
                // parent_1 = User database ID (bigint) e.g., 1
                $update_data = [
                    'sponsor_id' => $sponsor->dtehm_member_id,  // Store DTEHM ID
                    'parent_1' => $sponsor->id,                  // Store user database ID
                ];
                
                // Build parent hierarchy (parent_2 to parent_10)
                // Each parent_X stores the USER ID (not DTEHM ID)
                $current_parent = $sponsor;
                for ($i = 2; $i <= 10; $i++) {
                    // Get the next level up by looking at current parent's sponsor
                    if (!empty($current_parent->sponsor_id)) {
                        $next_parent = User::where('dtehm_member_id', $current_parent->sponsor_id)->first();
                        if ($next_parent) {
                            $update_data["parent_{$i}"] = $next_parent->id;  // Store USER ID
                            $current_parent = $next_parent;
                        } else {
                            break; // No more parents in chain
                        }
                    } else {
                        break; // Current parent has no sponsor
                    }
                }
                
                // Use DB update to bypass Eloquent validation
                DB::table('users')
                    ->where('id', $user->id)
                    ->update($update_data);
                
            } catch (\Exception $e) {
                $this->migration_log[] = [
                    'member_id' => $referral['referrer_id'] ?? 'unknown',
                    'status' => 'failed',
                    'message' => 'Network update failed: ' . $e->getMessage(),
                ];
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("   ✓ Network connections updated");
        
        if ($this->stats['orphaned_assigned'] > 0) {
            $this->warn("   ⚠️  {$this->stats['orphaned_assigned']} orphaned users assigned to DTEHM001");
        }
    }
    
    private function validateMigration()
    {
        $this->info('   ✅ Validating migration...');
        
        $errors = [];
        
        // Check 1: DTEHM001 is ID 1
        $admin = User::where('dtehm_member_id', 'DTEHM001')->first();
        if (!$admin || $admin->id != 1) {
            $errors[] = "CRITICAL: DTEHM001 is not ID 1! (Got: " . ($admin ? $admin->id : 'null') . ")";
        }
        
        // Check 2: DTEHM001 is Admin
        if ($admin && $admin->user_type != 'Admin') {
            $errors[] = "CRITICAL: DTEHM001 is not Admin! (Got: {$admin->user_type})";
        }
        
        // Check 3: User count matches
        $new_count = User::count();
        $old_count = count($this->users_data);
        if ($new_count != $old_count) {
            $errors[] = "User count mismatch: old={$old_count}, new={$new_count}";
        }
        
        // Check 4: No broken sponsor links
        $broken_links = DB::select("
            SELECT u.dtehm_member_id, u.sponsor_id
            FROM users u
            LEFT JOIN users s ON u.sponsor_id = s.id
            WHERE u.sponsor_id IS NOT NULL AND s.id IS NULL
        ");
        
        if (!empty($broken_links)) {
            $errors[] = "Broken sponsor links found: " . count($broken_links);
        }
        
        // Check 5: All passwords are bcrypted
        $invalid_passwords = User::whereRaw('LENGTH(password) != 60')->count();
        if ($invalid_passwords > 0) {
            $errors[] = "{$invalid_passwords} users have invalid password hashes";
        }
        
        // Check 6: All users are DTEHM members
        $non_dtehm = User::where('is_dtehm_member', '!=', 'Yes')->count();
        if ($non_dtehm > 0) {
            $errors[] = "{$non_dtehm} users are not marked as DTEHM members";
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("   ❌ {$error}");
            }
            throw new \Exception('Migration validation failed!');
        }
        
        $this->info('   ✓ All validation checks passed');
    }
    
    private function displayStats()
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                     MIGRATION STATISTICS                       ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->info("   Total users processed:        {$this->stats['total']}");
        $this->info("   Successfully migrated:        {$this->stats['success']}");
        $this->info("   Failed:                       {$this->stats['failed']}");
        $this->info("   Orphaned users found:         {$this->stats['orphaned']}");
        $this->info("   Assigned to DTEHM001:         {$this->stats['orphaned_assigned']}");
        $this->newLine();
        
        // Show user counts
        $admin = User::where('dtehm_member_id', 'DTEHM001')->first();
        $this->info("   DTEHM001 details:");
        $this->info("     - Database ID:              {$admin->id}");
        $this->info("     - User Type:                {$admin->user_type}");
        $this->info("     - Name:                     {$admin->first_name} {$admin->last_name}");
        $this->info("     - Sponsor ID:               " . ($admin->sponsor_id ?? 'NULL (Root)'));
        $this->newLine();
        
        $users_with_sponsor = User::whereNotNull('sponsor_id')->count();
        $this->info("   Users with sponsors:          {$users_with_sponsor}");
        $this->info("   Users without sponsors:       " . (User::count() - $users_with_sponsor));
    }
    
    private function saveMigrationLog()
    {
        $log_file = storage_path('logs/migration_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($log_file, json_encode($this->migration_log, JSON_PRETTY_PRINT));
        $this->newLine();
        $this->info("📝 Migration log saved: {$log_file}");
    }
    
    private function findMember($member_id)
    {
        foreach ($this->members_data as $member) {
            if ($member['memberID'] == $member_id) {
                return $member;
            }
        }
        return null;
    }
    
    private function normalizePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        // Use Utils class if available, otherwise simple normalization
        if (method_exists(Utils::class, 'prepare_phone_number')) {
            return Utils::prepare_phone_number($phone);
        }
        
        // Simple normalization
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (substr($phone, 0, 1) == '0') {
            $phone = '+256' . substr($phone, 1);
        } elseif (substr($phone, 0, 3) == '256') {
            $phone = '+' . $phone;
        } elseif (substr($phone, 0, 1) != '+') {
            $phone = '+256' . $phone;
        }
        
        return $phone;
    }
}
