<?php

namespace App\Admin\Actions\Withdraw;

use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Approve one pending withdrawal: deducts the amount from the member's balance (locked, once). */
class ApproveWithdraw extends RowAction
{
    public $name = 'Approve';

    public function handle(Model $model, Request $request)
    {
        $note = trim((string) $request->get('note', ''));
        $result = $model->approve(Admin::user(), $note !== '' ? $note : null);
        return $result['success']
            ? $this->response()->success($result['message'])->refresh()
            : $this->response()->error($result['message'])->refresh();
    }

    public function form()
    {
        $r = $this->row;
        $balance = optional($r->user)->calculateAccountBalance() ?? 0;
        $method = $r->payment_method === 'mobile_money' ? 'Mobile money to ' . $r->payment_phone_number : ucfirst(str_replace('_', ' ', (string) $r->payment_method));
        $this->text('summary', 'Withdrawal')->default(
            'UGX ' . number_format((float) $r->amount, 0) . ' to ' . optional($r->user)->name . ' (' . $method . ')'
        )->readonly();
        $this->text('balance_now', 'Member balance now')->default('UGX ' . number_format((float) $balance, 0)
            . ((float) $balance < (float) $r->amount ? '  -  NOT ENOUGH, approval will be refused' : ''))->readonly();
        $this->textarea('note', 'Payout reference / note (optional)')->rows(2)
            ->placeholder('e.g. MoMo transaction ID, receipt number');
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-success grid-row-action" style="margin-right:4px"><i class="fa fa-check"></i> Approve</a>';
    }
}
