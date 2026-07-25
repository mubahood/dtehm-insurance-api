<?php

namespace App\Admin\Controllers;

use App\Models\AwardWithdrawal;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class AwardWithdrawalController extends AdminController
{
    protected $title = 'Award Withdrawals';

    protected function grid()
    {
        $grid = new Grid(new AwardWithdrawal());
        $grid->model()->orderBy('withdrawal_date', 'desc');

        // Pool balance summary header
        $grid->header(function () {
            $pool      = AwardWithdrawal::totalPool();
            $withdrawn = AwardWithdrawal::totalWithdrawn();
            $balance   = $pool - $withdrawn;
            $pct       = $pool > 0 ? round($withdrawn / $pool * 100, 1) : 0;

            $balanceColor = $balance > 0 ? '#00a65a' : '#dd4b39';

            return "
            <div style='display:flex;gap:15px;flex-wrap:wrap;margin-bottom:10px;'>
                <div style='background:#f4f6f9;padding:12px 20px;border-radius:4px;border-left:4px solid #f39c12;flex:1;min-width:180px;'>
                    <div style='font-size:11px;color:#999;text-transform:uppercase;letter-spacing:1px;'>Total Awards Pool Collected</div>
                    <div style='font-size:20px;font-weight:700;color:#f39c12;'>UGX " . number_format($pool, 0) . "</div>
                    <div style='font-size:11px;color:#999;'>1.2% of all sales</div>
                </div>
                <div style='background:#f4f6f9;padding:12px 20px;border-radius:4px;border-left:4px solid #dd4b39;flex:1;min-width:180px;'>
                    <div style='font-size:11px;color:#999;text-transform:uppercase;letter-spacing:1px;'>Total Spent on Awards</div>
                    <div style='font-size:20px;font-weight:700;color:#dd4b39;'>UGX " . number_format($withdrawn, 0) . "</div>
                    <div style='font-size:11px;color:#999;'>{$pct}% of pool used</div>
                </div>
                <div style='background:#f4f6f9;padding:12px 20px;border-radius:4px;border-left:4px solid {$balanceColor};flex:1;min-width:180px;'>
                    <div style='font-size:11px;color:#999;text-transform:uppercase;letter-spacing:1px;'>Available Balance</div>
                    <div style='font-size:20px;font-weight:700;color:{$balanceColor};'>UGX " . number_format($balance, 0) . "</div>
                    <div style='font-size:11px;color:#999;'>Ready to spend on awards</div>
                </div>
            </div>";
        });

        $grid->column('id', __('ID'))->sortable()->width(60);

        $grid->column('withdrawal_date', __('Date'))
            ->display(fn($d) => $d ? date('d M Y', strtotime($d)) : '—')
            ->sortable()->width(110);

        $grid->column('award_name', __('Award / Prize'))
            ->display(fn($v) => $v
                ? "<span class='label label-warning'>{$v}</span>"
                : '<span class="text-muted">—</span>')
            ->width(180);

        $grid->column('amount', __('Amount Spent'))
            ->display(fn($v) => "<strong style='color:#f39c12;font-size:13px;'>UGX " . number_format($v, 0) . "</strong>")
            ->sortable()
            ->totalRow(fn($t) => "<strong style='color:#f39c12;'>UGX " . number_format($t, 0) . "</strong>")
            ->width(150);

        $grid->column('description', __('Description'))
            ->display(fn($v) => \Illuminate\Support\Str::limit($v, 80))
            ->width(280);

        $grid->column('recipient', __('Recipient / Winner'))
            ->display(fn($v) => $v ?: '<span class="text-muted">—</span>')
            ->width(160);

        $grid->column('reference_number', __('Reference'))
            ->display(fn($v) => $v
                ? "<span class='label label-default'>{$v}</span>"
                : '<span class="text-muted">—</span>')
            ->width(130);

        $grid->column('creator.name', __('Recorded By'))
            ->display(function () {
                return optional($this->creator)->name ?? '<span class="text-muted">—</span>';
            })->width(140);

        $grid->column('created_at', __('Created'))
            ->display(fn($d) => date('d M Y', strtotime($d)))
            ->sortable()->width(110);

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->between('withdrawal_date', 'Date')->date();
            $filter->between('amount', 'Amount');
            $filter->like('award_name', 'Award Name');
            $filter->like('recipient', 'Recipient');
            $filter->like('reference_number', 'Reference');
        });

        $grid->export(function ($export) {
            $export->filename('Award_Withdrawals_' . date('Y-m-d'));
            $export->column('id', 'ID');
            $export->column('withdrawal_date', 'Date');
            $export->column('award_name', 'Award Name');
            $export->column('amount', 'Amount');
            $export->column('description', 'Description');
            $export->column('recipient', 'Recipient');
            $export->column('reference_number', 'Reference');
            $export->column('notes', 'Notes');
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(AwardWithdrawal::findOrFail($id));
        $show->field('id', 'ID');
        $show->field('withdrawal_date', 'Date');
        $show->field('award_name', 'Award Name');
        $show->field('amount', 'Amount')->as(fn($v) => 'UGX ' . number_format($v, 0));
        $show->field('description', 'Description');
        $show->field('recipient', 'Recipient / Winner');
        $show->field('reference_number', 'Reference');
        $show->field('notes', 'Notes');
        $show->field('creator.name', 'Recorded By');
        $show->field('created_at', 'Created At');
        return $show;
    }

    protected function form()
    {
        $form = new Form(new AwardWithdrawal());

        // Show available balance at the top
        $pool      = AwardWithdrawal::totalPool();
        $withdrawn = AwardWithdrawal::totalWithdrawn();
        $balance   = $pool - $withdrawn;

        $form->html("
        <div class='alert alert-warning'>
            <strong>Awards Pool Status</strong><br>
            Total Collected: <strong>UGX " . number_format($pool, 0) . "</strong> &nbsp;|&nbsp;
            Already Spent: <strong>UGX " . number_format($withdrawn, 0) . "</strong> &nbsp;|&nbsp;
            <span style='color:" . ($balance > 0 ? '#00a65a' : '#dd4b39') . ";font-size:15px;font-weight:700;'>
                Available: UGX " . number_format($balance, 0) . "
            </span>
        </div>
        ");

        $form->date('withdrawal_date', 'Withdrawal Date')
            ->default(date('Y-m-d'))
            ->rules('required');

        $form->text('award_name', 'Award / Prize Name')
            ->placeholder('e.g. Top Performer Trophy, Trip to Dubai')
            ->rules('required');

        $form->decimal('amount', 'Amount Spent (UGX)')
            ->rules('required|numeric|min:1')
            ->help('Maximum available: UGX ' . number_format($balance, 0));

        $form->textarea('description', 'Description / Purpose')
            ->rules('required')
            ->rows(3)
            ->placeholder('e.g. Reward for Q3 top performer — car purchase');

        $form->text('recipient', 'Recipient / Winner')
            ->placeholder('Name of person or team receiving the award');

        $form->text('reference_number', 'Reference Number')
            ->placeholder('Invoice, receipt, or transaction ref')
            ->rules('nullable');

        $form->textarea('notes', 'Additional Notes')
            ->rows(2)
            ->rules('nullable');

        $form->hidden('created_by_id');

        $form->disableCreatingCheck();
        $form->disableViewCheck();

        $form->saving(function (Form $form) {
            // Inject creator
            $form->created_by_id = \Admin::user()->id;

            // Guard: cannot spend more than available balance
            $editing   = $form->isEditing();
            $currentId = $editing ? $form->model()->id : null;
            $pool      = AwardWithdrawal::totalPool();
            $withdrawn = AwardWithdrawal::where(function ($q) use ($currentId) {
                if ($currentId) $q->where('id', '!=', $currentId);
            })->sum('amount');
            $available = $pool - $withdrawn;

            if ((float)$form->amount > $available) {
                admin_error(
                    'Insufficient Balance',
                    'Cannot record UGX ' . number_format($form->amount, 0) .
                    ' — only UGX ' . number_format($available, 0) . ' is available in the awards pool.'
                );
                return back()->withInput();
            }
        });

        return $form;
    }
}
