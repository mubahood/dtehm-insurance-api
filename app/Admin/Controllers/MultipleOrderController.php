<?php

namespace App\Admin\Controllers;

use App\Models\MultipleOrder;
use App\Models\User;
use App\Models\Product;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Log;

class MultipleOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Multiple Orders (Cart Checkout)';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MultipleOrder());
        $grid->model()->orderBy('id', 'desc');

        // Add custom CSS for styling
        \Encore\Admin\Facades\Admin::style('
            .table-responsive { font-size: 12px; }
            .table > tbody > tr > td { padding: 8px; vertical-align: middle; }
            .table > thead > tr > th { padding: 8px; font-weight: 600; }
            .badge-success { background: #00a65a; }
            .badge-warning { background: #f39c12; }
            .badge-danger { background: #dd4b39; }
            .badge-info { background: #00c0ef; }
            .badge { padding: 3px 8px; border-radius: 3px; color: white; font-size: 11px; }
        ');

        // Header with statistics
        $grid->header(function ($query) {
            $total = $query->count();
            $totalRevenue = $query->sum('total_amount');
            $completed = $query->where('payment_status', 'COMPLETED')->count();
            $pending = $query->where('payment_status', 'PENDING')->count();
            $failed = $query->where('payment_status', 'FAILED')->count();
            $converted = $query->where('conversion_status', 'COMPLETED')->count();
            $paidByAdmin = $query->where('is_paid_by_admin', true)->count();

            return "<div style='background:#f4f6f9;padding:15px;margin-bottom:15px;border-radius:5px;'>
                        <div style='display:grid;grid-template-columns:repeat(4,1fr);gap:15px;'>
                            <div style='background:white;padding:12px;border-radius:4px;border-left:3px solid #3c8dbc;'>
                                <div style='color:#999;font-size:11px;margin-bottom:4px;'>TOTAL ORDERS</div>
                                <div style='font-size:24px;font-weight:700;color:#3c8dbc;'>{$total}</div>
                            </div>
                            <div style='background:white;padding:12px;border-radius:4px;border-left:3px solid #00a65a;'>
                                <div style='color:#999;font-size:11px;margin-bottom:4px;'>TOTAL REVENUE</div>
                                <div style='font-size:20px;font-weight:700;color:#00a65a;'>UGX " . number_format($totalRevenue, 0) . "</div>
                            </div>
                            <div style='background:white;padding:12px;border-radius:4px;border-left:3px solid #00c0ef;'>
                                <div style='color:#999;font-size:11px;margin-bottom:4px;'>COMPLETED</div>
                                <div style='font-size:24px;font-weight:700;color:#00c0ef;'>{$completed}</div>
                                <div style='font-size:10px;color:#999;margin-top:2px;'>Admin Cash: {$paidByAdmin}</div>
                            </div>
                            <div style='background:white;padding:12px;border-radius:4px;border-left:3px solid #f39c12;'>
                                <div style='color:#999;font-size:11px;margin-bottom:4px;'>PENDING / FAILED</div>
                                <div style='font-size:24px;font-weight:700;color:#f39c12;'>{$pending} / <span style='color:#dd4b39;'>{$failed}</span></div>
                                <div style='font-size:10px;color:#999;margin-top:2px;'>Converted: {$converted}</div>
                            </div>
                        </div>
                    </div>";
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Payment Status
            $filter->equal('payment_status', 'Payment Status')->select([
                'PENDING' => 'Pending',
                'COMPLETED' => 'Completed',
                'FAILED' => 'Failed',
                'CANCELLED' => 'Cancelled'
            ]);

            // Conversion Status
            $filter->equal('conversion_status', 'Conversion Status')->select([
                'PENDING' => 'Pending',
                'PROCESSING' => 'Processing',
                'COMPLETED' => 'Completed',
                'FAILED' => 'Failed'
            ]);

            // Admin Bypass Filter
            $filter->equal('is_paid_by_admin', 'Payment Method')->select([
                '1' => 'Admin Cash Payment',
                '0' => 'Online Payment (PesaPal)'
            ]);

            // User filter
            $filter->equal('user_id', 'Customer')->select(
                User::orderBy('name')->pluck('name', 'id')
            );

            // Sponsor filter
            $filter->equal('sponsor_id', 'Sponsor')->select(
                User::where('is_dtehm_member', 'Yes')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        $label = $user->name;
                        if ($user->dtehm_member_id) {
                            $label .= " ({$user->dtehm_member_id})";
                        }
                        return [$user->id => $label];
                    })
            );

            // Stockist filter
            $filter->equal('stockist_id', 'Stockist')->select(
                User::where('is_stockist', 'Yes')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        $label = $user->name;
                        return [$user->id => $label];
                    })
            );

            // Date range
            $filter->between('created_at', 'Order Date')->date();

            // Amount range
            $filter->between('total_amount', 'Total Amount');
        });

        // Quick search
        $grid->quickSearch('id', 'customer_phone', 'customer_email')->placeholder('Search by ID, Phone, Email...');

        // Export
        $grid->export(function ($export) {
            $export->filename('Multiple_Orders_' . date('Y-m-d'));
            $export->column('id', 'Order ID');
            $export->column('created_at', 'Date');
            $export->column('customer_name', 'Customer');
            $export->column('customer_phone', 'Phone');
            $export->column('total_amount', 'Amount');
            $export->column('payment_status', 'Payment Status');
            $export->column('is_paid_by_admin', 'Admin Cash');
            $export->column('conversion_status', 'Conversion');
        });

        // Columns
        $grid->column('id', __('Order ID'))->sortable()->width(80)->display(function ($id) {
            return "<span style='font-weight:700;color:#3c8dbc;font-size:14px;'>MO-{$id}</span>";
        });

        $grid->column('created_at', __('Date'))->display(function ($date) {
            return "<div style='line-height:1.4;'>
                        <div style='font-weight:500;font-size:12px;'>" . date('d M Y', strtotime($date)) . "</div>
                        <small style='color:#999;font-size:10px;'>" . date('H:i:s', strtotime($date)) . "</small>
                    </div>";
        })->sortable()->width(100);

        // Customer Info
        $grid->column('customer_info', __('Customer'))->display(function () {
            $user = User::find($this->user_id);
            $name = $user ? $user->name : $this->customer_name;
            $phone = $this->customer_phone;
            $email = $this->customer_email;

            $memberBadge = '';
            if ($user && $user->is_dtehm_member == 'Yes') {
                $memberBadge = "<span style='background:#00a65a;color:white;padding:2px 5px;border-radius:3px;font-size:9px;margin-left:4px;'>MEMBER</span>";
            }

            return "<div style='line-height:1.5;'>
                        <div style='font-weight:600;color:#333;'>{$name}{$memberBadge}</div>
                        <div style='font-size:11px;color:#666;'>{$phone}</div>
                        " . ($email ? "<div style='font-size:10px;color:#999;'>{$email}</div>" : "") . "
                    </div>";
        })->width(180);

        // Items Summary
        $grid->column('items_summary', __('Items'))->display(function () {
            $items = json_decode($this->items_json, true);
            if (!$items || !is_array($items)) {
                return "<span style='color:#999;'>No items</span>";
            }

            $itemCount = count($items);
            $totalQty = array_sum(array_column($items, 'quantity'));
            
            $itemsHtml = '';
            foreach (array_slice($items, 0, 2) as $item) {
                $product = Product::find($item['product_id']);
                $productName = $product ? $product->name : "Product #{$item['product_id']}";
                $qty = $item['quantity'];
                $itemsHtml .= "<div style='font-size:10px;color:#666;margin-bottom:2px;'>• {$productName} (×{$qty})</div>";
            }

            if ($itemCount > 2) {
                $more = $itemCount - 2;
                $itemsHtml .= "<div style='font-size:10px;color:#999;font-style:italic;'>+{$more} more...</div>";
            }

            return "<div>
                        <div style='font-weight:600;color:#3c8dbc;margin-bottom:4px;'>{$itemCount} Products ({$totalQty} units)</div>
                        {$itemsHtml}
                    </div>";
        })->width(200);

        // Financial Info
        $grid->column('financial_info', __('Amount'))->display(function () {
            $subtotal = number_format($this->subtotal, 0);
            $delivery = number_format($this->delivery_fee, 0);
            $total = number_format($this->total_amount, 0);

            return "<div style='line-height:1.5;text-align:right;'>
                        <div style='font-size:18px;font-weight:700;color:#00a65a;margin-bottom:4px;'>UGX {$total}</div>
                        <div style='font-size:10px;color:#666;'>Subtotal: {$subtotal}</div>
                        " . ($this->delivery_fee > 0 ? "<div style='font-size:10px;color:#666;'>Delivery: {$delivery}</div>" : "") . "
                    </div>";
        })->width(120);

        // Sponsor & Stockist
        $grid->column('sponsor_stockist', __('Sponsor/Stockist'))->display(function () {
            $sponsor = User::find($this->sponsor_id);
            $stockist = User::find($this->stockist_id);

            $sponsorName = $sponsor ? $sponsor->name : "ID: {$this->sponsor_id}";
            $stockistName = $stockist ? $stockist->name : "ID: {$this->stockist_id}";

            return "<div style='line-height:1.6;font-size:11px;'>
                        <div><strong style='color:#555;'>Sponsor:</strong> {$sponsorName}</div>
                        <div><strong style='color:#555;'>Stockist:</strong> {$stockistName}</div>
                    </div>";
        })->width(150);

        // Payment Status with Method
        $grid->column('payment_info', __('Payment'))->display(function () {
            $statusColors = [
                'PENDING' => 'warning',
                'COMPLETED' => 'success',
                'FAILED' => 'danger',
                'CANCELLED' => 'secondary'
            ];

            $color = $statusColors[$this->payment_status] ?? 'info';
            $statusBadge = "<span class='badge badge-{$color}'>{$this->payment_status}</span>";

            $methodBadge = '';
            if ($this->is_paid_by_admin) {
                $methodBadge = "<span style='background:#f39c12;color:white;padding:2px 6px;border-radius:3px;font-size:9px;display:inline-block;margin-top:4px;'>💰 CASH PAYMENT</span>";
            } else if ($this->pesapal_order_tracking_id) {
                $methodBadge = "<span style='background:#3c8dbc;color:white;padding:2px 6px;border-radius:3px;font-size:9px;display:inline-block;margin-top:4px;'>🌐 PESAPAL</span>";
            }

            $paidDate = '';
            if ($this->payment_status == 'COMPLETED' && $this->paid_at) {
                $paidDate = "<div style='font-size:9px;color:#999;margin-top:3px;'>" . date('d M Y H:i', strtotime($this->paid_at)) . "</div>";
            }

            return "<div>{$statusBadge}{$methodBadge}{$paidDate}</div>";
        })->width(120);

        // Conversion Status
        $grid->column('conversion_status', __('Conversion'))->display(function ($status) {
            $colors = [
                'PENDING' => 'secondary',
                'PROCESSING' => 'info',
                'COMPLETED' => 'success',
                'FAILED' => 'danger'
            ];

            $color = $colors[$status] ?? 'secondary';
            $badge = "<span class='badge badge-{$color}'>{$status}</span>";

            $convertedDate = '';
            if ($status == 'COMPLETED' && $this->converted_at) {
                $convertedDate = "<div style='font-size:9px;color:#999;margin-top:3px;'>" . date('d M Y', strtotime($this->converted_at)) . "</div>";
            }

            return "<div>{$badge}{$convertedDate}</div>";
        })->sortable()->width(100);

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            
            // Convert to Sales button (if not converted)
            if ($actions->row->payment_status == 'COMPLETED' && $actions->row->conversion_status != 'COMPLETED') {
                $actions->append('<a href="' . route('multiple-orders.convert', $actions->row->id) . '" 
                    class="btn btn-xs btn-success" 
                    onclick="return confirm(\'Convert this order to sales?\')">
                    <i class="fa fa-exchange"></i> Convert to Sales
                </a>');
            }

            // View Details button
            $actions->append('<a href="' . route('multiple-orders.show', $actions->row->id) . '" 
                class="btn btn-xs btn-primary">
                <i class="fa fa-eye"></i>
            </a>');
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        $grid->disableCreateButton();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(MultipleOrder::findOrFail($id));

        // Order Information
        $show->panel()->title('Order Information')->style('primary');
        
        $show->id('Order ID')->as(function ($id) {
            return "MO-{$id}";
        });
        $show->created_at('Order Date');
        $show->updated_at('Last Updated');

        // Customer Information
        $show->divider();
        $show->panel()->title('Customer Information')->style('info');
        
        $show->user_id('Customer')->as(function ($userId) {
            $user = User::find($userId);
            return $user ? "{$user->name} (ID: {$user->id})" : "Guest";
        });
        $show->customer_name('Customer Name');
        $show->customer_phone('Phone');
        $show->customer_email('Email');
        $show->customer_notes('Customer Notes');

        // Items Details
        $show->divider();
        $show->panel()->title('Order Items')->style('success');
        
        $show->items_json('Items')->as(function ($itemsJson) {
            $items = json_decode($itemsJson, true);
            if (!$items) return 'No items';

            $html = '<table class="table table-bordered table-striped" style="margin-top:10px;">
                <thead>
                    <tr style="background:#f4f6f9;">
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $productName = $product ? $product->name : "Product #{$item['product_id']}";
                $qty = $item['quantity'];
                $unitPrice = number_format($item['unit_price'], 0);
                $subtotal = number_format($item['subtotal'], 0);
                $options = [];
                if (!empty($item['color'])) $options[] = "Color: {$item['color']}";
                if (!empty($item['size'])) $options[] = "Size: {$item['size']}";
                $optionsText = !empty($options) ? implode(', ', $options) : '-';

                $html .= "<tr>
                    <td><strong>{$productName}</strong></td>
                    <td>{$qty}</td>
                    <td>UGX {$unitPrice}</td>
                    <td><strong style='color:#00a65a;'>UGX {$subtotal}</strong></td>
                    <td><small>{$optionsText}</small></td>
                </tr>";
            }

            $html .= '</tbody></table>';
            return $html;
        })->unescape();

        // Financial Details
        $show->divider();
        $show->panel()->title('Financial Details')->style('warning');
        
        $show->subtotal('Subtotal')->as(function ($amount) {
            return "UGX " . number_format($amount, 0);
        });
        $show->delivery_fee('Delivery Fee')->as(function ($amount) {
            return "UGX " . number_format($amount, 0);
        });
        $show->total_amount('Total Amount')->as(function ($amount) {
            return "<h3 style='color:#00a65a;margin:0;'>UGX " . number_format($amount, 0) . "</h3>";
        })->unescape();
        $show->currency('Currency');

        // Payment Information
        $show->divider();
        $show->panel()->title('Payment Information')->style('danger');
        
        $show->payment_status('Payment Status')->badge([
            'PENDING' => 'warning',
            'COMPLETED' => 'success',
            'FAILED' => 'danger'
        ]);
        
        $show->is_paid_by_admin('Payment Method')->as(function ($isPaid) {
            return $isPaid ? '💰 Admin Cash Payment' : '🌐 Online Payment (PesaPal)';
        })->label([
            true => 'warning',
            false => 'info'
        ]);

        $show->admin_payment_note('Admin Payment Note');
        $show->paid_at('Paid At');
        $show->payment_completed_at('Payment Completed At');

        // PesaPal Details
        $show->pesapal_order_tracking_id('PesaPal Tracking ID');
        $show->pesapal_merchant_reference('Merchant Reference');
        $show->pesapal_status_code('PesaPal Status');
        $show->pesapal_confirmation_code('Confirmation Code');

        // Sponsor & Stockist
        $show->divider();
        $show->panel()->title('Business Information')->style('success');
        
        $show->sponsor_id('Sponsor ID')->as(function ($sponsorId) {
            $sponsor = User::find($sponsorId);
            return $sponsor ? "{$sponsor->name} (ID: {$sponsor->id})" : "ID: {$sponsorId}";
        });
        $show->stockist_id('Stockist ID')->as(function ($stockistId) {
            $stockist = User::find($stockistId);
            return $stockist ? "{$stockist->name} (ID: {$stockist->id})" : "ID: {$stockistId}";
        });

        // Conversion Status
        $show->divider();
        $show->panel()->title('Conversion Status')->style('primary');
        
        $show->conversion_status('Conversion Status')->badge([
            'PENDING' => 'secondary',
            'PROCESSING' => 'info',
            'COMPLETED' => 'success',
            'FAILED' => 'danger'
        ]);
        $show->converted_at('Converted At');
        $show->conversion_result('Conversion Result');
        $show->conversion_error('Conversion Error');

        // Delivery Information
        $show->divider();
        $show->panel()->title('Delivery Information')->style('info');
        
        $show->delivery_method('Delivery Method');
        $show->delivery_address('Delivery Address');

        return $show;
    }

    /**
     * Convert order to sales
     */
    public function convert($id, Content $content)
    {
        $order = MultipleOrder::findOrFail($id);

        if ($order->conversion_status == 'COMPLETED') {
            admin_toastr('Order already converted to sales', 'warning');
            return redirect()->route('multiple-orders.show', $id);
        }

        if ($order->payment_status != 'COMPLETED') {
            admin_toastr('Cannot convert - payment not completed', 'error');
            return redirect()->route('multiple-orders.show', $id);
        }

        try {
            $result = $order->convertToOrderedItems();

            if ($result['success']) {
                admin_toastr('Order successfully converted to sales!', 'success');
                Log::info('Admin manually converted MultipleOrder', [
                    'order_id' => $order->id,
                    'sales_count' => count($result['ordered_items'] ?? [])
                ]);
            } else {
                admin_toastr('Conversion failed: ' . $result['message'], 'error');
            }
        } catch (\Exception $e) {
            admin_toastr('Conversion error: ' . $e->getMessage(), 'error');
            Log::error('Admin conversion error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('multiple-orders.show', $id);
    }

    /**
     * Disable form methods since orders are created via API only
     */
    protected function form()
    {
        admin_toastr('Multiple orders can only be created via mobile app checkout', 'info');
        return redirect()->route('multiple-orders.index');
    }
}
