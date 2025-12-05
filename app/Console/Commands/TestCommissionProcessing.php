<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderedItem;
use App\Models\AccountTransaction;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;

class TestCommissionProcessing extends Command
{
    protected $signature = 'test:commission {item_id?}';
    protected $description = 'Test commission processing system with comprehensive validation';

    public function handle()
    {
        $this->info('============ COMMISSION SERVICE TEST ============');
        $this->newLine();

        $itemId = $this->argument('item_id');
        
        if ($itemId) {
            $item = OrderedItem::find($itemId);
            if (!$item) {
                $this->error("OrderedItem #{$itemId} not found!");
                return 1;
            }
        } else {
            // Find an unprocessed item
            $item = OrderedItem::where('commission_is_processed', 'No')
                ->where('has_detehm_seller', 'Yes')
                ->whereNotNull('dtehm_user_id')
                ->first();

            if (!$item) {
                $this->warn('No unprocessed items found. Looking for any item with DTEHM seller...');
                
                $item = OrderedItem::where('has_detehm_seller', 'Yes')
                    ->whereNotNull('dtehm_user_id')
                    ->first();
                
                if ($item) {
                    if ($this->confirm("Reset OrderedItem #{$item->id} for testing?", true)) {
                        // Reset for testing
                        DB::table('ordered_items')->where('id', $item->id)->update([
                            'commission_is_processed' => 'No',
                            'commission_processed_date' => null,
                        ]);
                        
                        // Delete existing transactions
                        $deleted = AccountTransaction::where('commission_reference_id', $item->id)
                            ->where('commission_type', 'LIKE', 'product_commission%')
                            ->delete();
                            
                        $this->info("✓ Reset item and deleted {$deleted} existing transactions");
                        $item->refresh();
                    }
                } else {
                    $this->error('No items with DTEHM seller found!');
                    return 1;
                }
            }
        }

        $this->newLine();
        $this->info("Testing with OrderedItem ID: {$item->id}");
        $this->line("Seller ID: {$item->dtehm_user_id}");
        $this->line("Stockist ID: {$item->stockist_user_id}");
        $this->line("Subtotal: UGX " . number_format($item->subtotal, 2));
        $this->line("Already Processed: {$item->commission_is_processed}");
        
        $this->newLine();
        $this->info('---- Processing Commission ----');
        
        $service = new CommissionService();
        $result = $service->processCommission($item);

        $this->newLine();
        $this->info('==== RESULT ====');
        $this->line("Success: " . ($result['success'] ? '✓ YES' : '✗ NO'));
        $this->line("Message: {$result['message']}");

        if ($result['success']) {
            $this->line("Total Commission: UGX " . number_format($result['total_commission'], 2));
            $this->line("Beneficiaries: {$result['beneficiaries']}");
            
            $this->newLine();
            $this->table(
                ['Level', 'User ID', 'Name', 'Amount'],
                collect($result['commissions'])->map(fn($c) => [
                    $c['level'],
                    $c['user_id'],
                    $c['user_name'],
                    'UGX ' . number_format($c['amount'], 2)
                ])
            );
        }

        $this->newLine();
        $this->info('==== ITEM AFTER PROCESSING ====');
        $item->refresh();
        $this->line("Commission Processed: {$item->commission_is_processed}");
        $this->line("Processed Date: {$item->commission_processed_date}");
        $this->line("Total Commission: UGX " . number_format($item->total_commission_amount ?? 0, 2));
        $this->line("Balance After: UGX " . number_format($item->balance_after_commission ?? 0, 2));

        $this->newLine();
        $this->info('==== ACCOUNT TRANSACTIONS CREATED ====');
        $transactions = AccountTransaction::where('commission_reference_id', $item->id)
            ->where('commission_type', 'LIKE', 'product_commission%')
            ->with('user')
            ->orderBy('id')
            ->get();

        $this->line("Total Transactions: {$transactions->count()}");
        $this->newLine();
        
        $this->table(
            ['ID', 'User', 'Amount', 'Type', 'Date'],
            $transactions->map(fn($t) => [
                $t->id,
                $t->user->name,
                'UGX ' . number_format($t->amount, 2),
                $t->commission_type,
                $t->transaction_date->format('Y-m-d')
            ])
        );

        $this->newLine();
        $this->info('==== TESTING DUPLICATE PREVENTION ====');
        $this->line('Attempting to process same item again...');
        $result2 = $service->processCommission($item);
        $this->line("Result: {$result2['message']}");

        if (!$result2['success']) {
            $this->info('✓ Duplicate prevention working correctly!');
        } else {
            $this->error('✗ ERROR: Duplicate commission was created!');
        }

        $this->newLine();
        $this->info('============ TEST COMPLETE ============');
        
        return 0;
    }
}
