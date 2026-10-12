<?php

namespace App\Admin\Actions\Withdraw;

use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Reject one pending withdrawal (nothing is deducted). A reason is required and shown to the member. */
class RejectWithdraw extends RowAction
{
    public $name = 'Reject';

    public const REASONS = [
        'Payment details are incorrect - please update your phone number' => 'Payment details are incorrect',
        'Amount is below the minimum allowed' => 'Amount below minimum',
        'Account is under review - please contact the office' => 'Account under review',
        'Duplicate request' => 'Duplicate request',
    ];

    public function handle(Model $model, Request $request)
    {
        $reason = trim((string) ($request->get('custom_reason') ?: $request->get('reason')));
        $result = $model->reject(Admin::user(), $reason);
        return $result['success']
            ? $this->response()->success($result['message'])->refresh()
            : $this->response()->error($result['message']);
    }

    public function form()
    {
        $this->select('reason', 'Reason')->options(array_combine(array_keys(self::REASONS), array_values(self::REASONS)));
        $this->textarea('custom_reason', 'Or write a reason')->rows(2)->placeholder('Shown to the member');
    }

    public function html()
    {
        return '<a class="btn btn-xs btn-danger grid-row-action"><i class="fa fa-times"></i> Reject</a>';
    }
}
