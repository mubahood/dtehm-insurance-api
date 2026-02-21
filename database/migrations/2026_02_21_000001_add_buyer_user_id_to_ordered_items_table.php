<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddBuyerUserIdToOrderedItemsTable extends Migration
{
    /**
     * Run the migrations.
     * Adds buyer_user_id column to ordered_items so we can correctly track
     * who actually purchased/paid for the item (distinct from sponsor and stockist).
     */
    public function up()
    {
        // 1. Add the column
        Schema::table('ordered_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ordered_items', 'buyer_user_id')) {
                $table->unsignedBigInteger('buyer_user_id')->nullable()->after('stockist_user_id')
                    ->comment('The user who actually bought/paid for this item');
                $table->index('buyer_user_id');
            }
        });

        // 2. Backfill from MultipleOrders (items created via bulk purchase conversion)
        $this->backfillFromMultipleOrders();

        // 3. Backfill from UniversalPayments (items created via direct purchase)
        $this->backfillFromUniversalPayments();

        Log::info('Migration: buyer_user_id column added and backfilled on ordered_items');
    }

    /**
     * Backfill buyer_user_id from multiple_orders conversion results.
     * MultipleOrder.user_id = the actual buyer.
     * MultipleOrder.conversion_result JSON contains the ordered_item IDs created.
     */
    private function backfillFromMultipleOrders()
    {
        $completedOrders = DB::table('multiple_orders')
            ->where('conversion_status', 'COMPLETED')
            ->whereNotNull('conversion_result')
            ->select('id', 'user_id', 'conversion_result')
            ->get();

        foreach ($completedOrders as $order) {
            if (empty($order->user_id) || empty($order->conversion_result)) {
                continue;
            }

            $result = json_decode($order->conversion_result, true);
            if (!$result || empty($result['items'])) {
                continue;
            }

            $orderedItemIds = array_column($result['items'], 'id');
            if (!empty($orderedItemIds)) {
                DB::table('ordered_items')
                    ->whereIn('id', $orderedItemIds)
                    ->whereNull('buyer_user_id')
                    ->update(['buyer_user_id' => $order->user_id]);

                Log::info("Backfilled buyer_user_id={$order->user_id} for " . count($orderedItemIds) . " items from MultipleOrder #{$order->id}");
            }
        }
    }

    /**
     * Backfill buyer_user_id from universal_payments.
     * UniversalPayment.user_id = the actual buyer.
     */
    private function backfillFromUniversalPayments()
    {
        DB::statement("
            UPDATE ordered_items oi
            INNER JOIN universal_payments up ON oi.universal_payment_id = up.id
            SET oi.buyer_user_id = up.user_id
            WHERE oi.buyer_user_id IS NULL
              AND oi.universal_payment_id IS NOT NULL
              AND up.user_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            if (Schema::hasColumn('ordered_items', 'buyer_user_id')) {
                $table->dropIndex(['buyer_user_id']);
                $table->dropColumn('buyer_user_id');
            }
        });
    }
}
