<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchMarkAsNotPaid extends BatchAction
{
    public $name = 'Mark as Not Paid';

    public function handle(Collection $collection, Request $request)
    {
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($collection as $item) {
            try {
                // Skip if already not paid
                if ($item->item_is_paid === 'No') {
                    $skipped++;
                    continue;
                }

                $item->item_is_paid = 'No';
                $item->item_paid_date = null;
                $item->item_paid_amount = null;
                $item->save();
                $updated++;

            } catch (\Exception $e) {
                $failed++;
                \Log::error('Batch mark as not paid failed', [
                    'ordered_item_id' => $item->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $message = "Marked as Not Paid: {$updated}";
        if ($skipped > 0) {
            $message .= ", Skipped (already not paid): {$skipped}";
        }
        if ($failed > 0) {
            $message .= ", Failed: {$failed}";
        }

        return $this->response()->success($message)->refresh();
    }

    public function dialog()
    {
        $this->confirm('Are you sure you want to mark all selected items as NOT PAID?');
    }
}
