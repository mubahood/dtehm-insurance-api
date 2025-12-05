<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixNetworkConnections extends Command
{
    protected $signature = 'fix:networks';
    protected $description = 'Fix network connections after migration';
    
    private $old_data;
    private $referrals_data;
    
    public function handle()
    {
        $this->info('=== FIXING NETWORK CONNECTIONS ===');
        
        // Load JSON data
        $json_file = base_path('dtehmhea_app.json');
        if (!file_exists($json_file)) {
            $this->error("JSON file not found: {$json_file}");
            return 1;
        }
        
        $this->old_data = json_decode(file_get_contents($json_file), true);
        
        // Extract referrals data
        foreach ($this->old_data as $record) {
            if ($record['type'] == 'table' && $record['name'] == 'referrals') {
                $this->referrals_data = $record['data'];
                break;
            }
        }
        
        if (empty($this->referrals_data)) {
            $this->error('Referrals data not found in JSON');
            return 1;
        }
        
        $this->info('Found ' . count($this->referrals_data) . ' referral records');
        
        // Update networks
        $bar = $this->output->createProgressBar(count($this->referrals_data));
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        foreach ($this->referrals_data as $referral) {
            try {
                $member_id = $referral['referrer_id'];
                $level1 = $referral['level1'] ?? null;
                
                // Find user
                $user = User::where('dtehm_member_id', $member_id)->first();
                
                if (!$user) {
                    $this->warn("\nUser not found: {$member_id}");
                    $errors++;
                    $bar->advance();
                    continue;
                }
                
                // Skip DTEHM001 (root user)
                if ($member_id == 'DTEHM001') {
                    $bar->advance();
                    continue;
                }
                
                // Handle orphaned users - assign to DTEHM001
                if (empty($level1) || $level1 == '' || $level1 == 'null') {
                    $level1 = 'DTEHM001';
                }
                
                // Get sponsor
                $sponsor = User::where('dtehm_member_id', $level1)->first();
                
                $update_data = [];
                
                if ($sponsor) {
                    $update_data['sponsor_id'] = $sponsor->id;
                    $update_data['parent_1'] = $sponsor->dtehm_member_id;
                } else {
                    // Default to DTEHM001
                    $root = User::where('dtehm_member_id', 'DTEHM001')->first();
                    $update_data['sponsor_id'] = $root->id;
                    $update_data['parent_1'] = 'DTEHM001';
                    $this->warn("\nSponsor {$level1} not found for {$member_id}, using DTEHM001");
                }
                
                // Store entire upline hierarchy
                for ($i = 2; $i <= 10; $i++) {
                    $level_key = "level{$i}";
                    $parent_key = "parent_{$i}";
                    
                    $level_value = $referral[$level_key] ?? null;
                    if (!empty($level_value) && $level_value != '' && $level_value != 'null') {
                        $update_data[$parent_key] = $level_value;
                    }
                }
                
                // Use DB update to bypass Eloquent validation
                DB::table('users')
                    ->where('id', $user->id)
                    ->update($update_data);
                    
                $updated++;
                
            } catch (\Exception $e) {
                $this->error("\nError updating {$member_id}: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Updated: {$updated}");
        $this->error("❌ Errors: {$errors}");
        
        // Validation
        $this->info("\n=== VALIDATION ===");
        $with_sponsor = User::whereNotNull('sponsor_id')->count();
        $without_sponsor = User::whereNull('sponsor_id')->where('dtehm_member_id', '!=', 'DTEHM001')->count();
        
        $this->info("Users with sponsor: {$with_sponsor}");
        $this->warn("Users without sponsor (non-root): {$without_sponsor}");
        
        return 0;
    }
}
