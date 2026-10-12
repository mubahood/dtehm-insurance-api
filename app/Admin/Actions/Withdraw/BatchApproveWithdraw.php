<?php

namespace App\Admin\Actions\Withdraw;

use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Collection;

/** Approve all selected pending withdrawals; each one is checked and locked on its own. */
class BatchApproveWithdraw extends BatchAction
{
    public $name = 'Approve selected';

    public function handle(Collection $collection)
    {
        $ok = 0;
        $problems = [];
        foreach ($collection as $item) {
            if ($item->status !== 'pending') {
                continue;
            }
            $r = $item->approve(Admin::user());
            if ($r['success']) {
                $ok++;
            } else {
                $problems[] = '#' . $item->id . ': ' . $r['message'];
            }
        }
        $msg = "Approved {$ok} request(s)." . ($problems ? ' Not approved: ' . implode('; ', array_slice($problems, 0, 5)) : '');
        return ($problems ? $this->response()->warning($msg) : $this->response()->success($msg))->refresh();
    }

    public function dialog()
    {
        $this->confirm('Approve all selected pending withdrawals? Each amount will be deducted from the member balance.');
    }
}
