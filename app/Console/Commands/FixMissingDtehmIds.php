<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixMissingDtehmIds extends Command
{
    protected $signature   = 'dtehm:fix-missing-ids {--dry-run : Show what would be changed without saving}';
    protected $description = 'Generate DTEHM IDs for users with is_dtehm_member=Yes who are missing one';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $users = User::where('is_dtehm_member', 'Yes')
            ->whereNull('dtehm_member_id')
            ->orderBy('id', 'asc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users missing a DTEHM ID. Nothing to do.');
            return 0;
        }

        $this->info("Found {$users->count()} user(s) missing a DTEHM ID" . ($dryRun ? ' (dry run)' : '') . ':');

        $fixed  = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                // Trigger the model's updating hook which calls generateDtehmMemberId()
                if (!$dryRun) {
                    // Touch a safe field so the updating hook fires
                    $user->updated_at = now();
                    $user->save();

                    $user->refresh();
                    $newId = $user->dtehm_member_id ?? 'STILL NULL';
                    $this->line("  ✓ ID {$user->id} {$user->name} → {$newId}");
                    $fixed++;
                } else {
                    $this->line("  → ID {$user->id} {$user->name} (phone: {$user->phone_number}) — would get next DTEHM ID");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ ID {$user->id} {$user->name}: " . $e->getMessage());
                $failed++;
            }
        }

        if (!$dryRun) {
            $this->info("\nDone. Fixed: {$fixed}, Failed: {$failed}");
        }

        return 0;
    }
}
