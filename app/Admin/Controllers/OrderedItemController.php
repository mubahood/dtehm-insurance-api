<?php

namespace App\Admin\Controllers;

use App\Models\OrderedItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class OrderedItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Product Sales';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderedItem());
        $grid->model()->orderBy('id', 'desc');

        // Disable automatic column display to avoid relationship conflicts
        $grid->disableCreateButton(false);

        // Add custom CSS for compact professional styling
        \Encore\Admin\Facades\Admin::style('
            .table-responsive { font-size: 12px; }
            .table > tbody > tr > td { padding: 6px 8px; vertical-align: middle; }
            .table > thead > tr > th { padding: 8px 8px; font-weight: 600; }
            .grid-row-actions { white-space: nowrap; }
        ');

        // Grid title and description
        $grid->header(function ($query) {
            $total = $query->count();
            $totalRevenue = $query->sum('subtotal');
            $totalQty = $query->sum('qty');
            return "<div style='background:#f4f6f9;padding:10px 15px;margin-bottom:10px;border-radius:3px;border-left:3px solid #3c8dbc;'>
                        <strong style='color:#333;'>Total Sales:</strong> <span style='color:#3c8dbc;'>{$total}</span> &nbsp;|&nbsp; 
                        <strong style='color:#333;'>Total Units:</strong> <span style='color:#3c8dbc;'>" . number_format($totalQty) . "</span> &nbsp;|&nbsp; 
                        <strong style='color:#333;'>Total Revenue:</strong> <span style='color:#00a65a;font-size:15px;'>UGX " . number_format($totalRevenue, 0) . "</span>
                    </div>";
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();


            // Product filter
            $filter->equal('product', 'Product')->select(\App\Models\Product::orderBy('name')->pluck('name', 'id'));

            // Sponsor filter
            $filter->equal('sponsor_user_id', 'Sponsor')->select(
                \App\Models\User::where('is_dtehm_member', 'Yes')
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
            $filter->equal('stockist_user_id', 'Stockist')->select(
                \App\Models\User::where('is_dtehm_member', 'Yes')
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

            // Date range filter
            $filter->between('created_at', 'Sale Date')->date();

            // Quantity range
            $filter->between('qty', 'Quantity');

            // Price range
            $filter->between('subtotal', 'Total Amount');

            // Points filter
            $filter->between('points_earned', 'Points Earned');
        });

        // Quick search
        $grid->quickSearch('id')->placeholder('Search by ID...');

        // Export
        $grid->export(function ($export) {
            $export->filename('Product Sales');
            $export->column('id', 'ID');
            $export->column('created_at', 'Date');
            $export->column('order', 'Order ID');
            $export->column('product', 'Product ID');
            $export->column('qty', 'Quantity');
            $export->column('unit_price', 'Unit Price');
            $export->column('subtotal', 'Subtotal');
            $export->column('color', 'Color');
            $export->column('size', 'Size');
        });

        // Columns
        $grid->column('id', __('ID'))->sortable()->width(60)->display(function ($id) {
            return "<span style='font-weight:600;color:#3c8dbc;'>{$id}</span>";
        });

        $grid->column('created_at', __('Date'))->display(function ($date) {
            return "<div style='line-height:1.3;'><div style='font-weight:500;'>" . date('d M Y', strtotime($date)) . "</div><small style='color:#999;'>" . date('H:i', strtotime($date)) . "</small></div>";
        })->sortable()->width(100);

        // Display product name with price
        $grid->column('product_info', __('Product'))->display(function () {
            $productId = $this->getOriginal('product');
            $product = \App\Models\Product::find($productId);

            if ($product) {
                $imageHtml = '';
                if ($product->feature_photo) {
                    $imageUrl = url('storage/' . $product->feature_photo);
                    $imageHtml = "<img src='{$imageUrl}' style='width:40px;height:40px;object-fit:cover;border-radius:4px;margin-right:8px;border:1px solid #ddd;'>";
                }

                $price = number_format($product->price_1 ?? 0, 0);
                $category = $product->category ? (\App\Models\ProductCategory::find($product->category)?->name ?? '') : '';
                $categoryBadge = $category ? "<span style='background:#f5f5f5;color:#666;padding:1px 6px;border-radius:3px;font-size:10px;'>{$category}</span>" : '';

                return "<div style='display:flex;align-items:center;'>{$imageHtml}<div style='line-height:1.4;'><div style='font-weight:500;color:#333;font-size:12px;margin-bottom:2px;'>{$product->name}</div><div style='color:#00a65a;font-weight:500;font-size:11px;'>UGX {$price}</div>{$categoryBadge}</div></div>";
            }

            return "<span style='color:#999;font-size:11px;'>Product #{$productId}</span>";
        })->width(250);

        // Sponsor & Stockist Information
        $grid->column('sponsor_info', __('Sponsor'))->display(function () {
            if (!$this->sponsor_user_id) {
                return '<span style="color:#999;">-</span>';
            }
            $sponsor = \App\Models\User::find($this->sponsor_user_id);
            if ($sponsor) {
                $memberId = $sponsor->dtehm_member_id ?: $sponsor->business_name;
                return "<div style='line-height:1.3;'><div style='font-weight:500;color:#333;font-size:12px;'>{$sponsor->name}</div><small style='color:#999;font-size:10px;'>{$memberId}</small></div>";
            }
            return '<span style="color:#999;">-</span>';
        })->width(140);

        $grid->column('stockist_info', __('Stockist'))->display(function () {
            if (!$this->stockist_user_id) {
                return '<span style="color:#999;">-</span>';
            }
            $stockist = \App\Models\User::find($this->stockist_user_id);
            if ($stockist) {
                $memberId = $stockist->dtehm_member_id ?: $stockist->business_name;
                return "<div style='line-height:1.3;'><div style='font-weight:500;color:#333;font-size:12px;'>{$stockist->name}</div><small style='color:#999;font-size:10px;'>{$memberId}</small></div>";
            }
            return '<span style="color:#999;">-</span>';
        })->width(140);

        $grid->column('qty', __('Qty'))->sortable()->display(function ($qty) {
            return '<span style="font-weight:500;font-size:13px;color:#333;">' . $qty . '</span>';
        })->totalRow(function ($amount) {
            return "<strong style='color:#3c8dbc;font-size:13px;'>" . number_format($amount, 0) . "</strong>";
        })->width(60);

        $grid->column('amount', __('Total'))->display(function ($amount) {
            return '<span style="color:#00a65a;font-weight:500;font-size:12px;">UGX ' . number_format($amount, 0) . '</span>';
        })->sortable()->totalRow(function ($amount) {
            return "<strong style='color:#00a65a;font-size:13px;'>UGX " . number_format($amount, 0) . "</strong>";
        })->width(110);

        // Points Earned Column
        $grid->column('points_earned', __('Points'))->display(function ($points) {
            if (!$points || $points == 0) {
                return '<span style="color:#999;">-</span>';
            }
            return '<span style="background:#9C27B0;color:white;font-size:10px;padding:2px 6px;border-radius:3px;"><i class="fa fa-star"></i> ' . number_format($points) . '</span>';
        })->sortable()->totalRow(function ($amount) {
            return "<strong style='color:#9C27B0;font-size:12px;'><i class='fa fa-star'></i> " . number_format($amount, 0) . "</strong>";
        })->width(70);

        // Commission Summary Column
        $grid->column('commission_summary', __('Commissions'))->display(function () {
            $stockistRate = 0.07;
            $sponsorRate = 0.08;
            $gnRates = [0.03, 0.025, 0.02, 0.015, 0.01, 0.008, 0.006, 0.005, 0.004, 0.002];

            $price = $this->subtotal ?: ($this->amount ?: $this->unit_price);
            $stockistCommission = $price * $stockistRate;
            $sponsorCommission = $price * $sponsorRate;
            $totalGnCommission = array_sum(array_map(fn($r) => $price * $r, $gnRates));
            $totalCommission = $stockistCommission + $sponsorCommission + $totalGnCommission;
            $balance = $price - $totalCommission;

            return "<div style='line-height:1.4;font-size:11px;'>
                <div style='margin-bottom:2px;'><span style='color:#999;'>Stockist:</span> <span style='color:#f39c12;font-weight:500;'>UGX " . number_format($stockistCommission, 0) . "</span></div>
                <div style='margin-bottom:2px;'><span style='color:#999;'>Sponsor:</span> <span style='color:#00c0ef;font-weight:500;'>UGX " . number_format($sponsorCommission, 0) . "</span></div>
                <div style='margin-bottom:2px;'><span style='color:#999;'>Network:</span> <span style='color:#00a65a;font-weight:500;'>UGX " . number_format($totalGnCommission, 0) . "</span></div>
                <div style='margin-bottom:2px;padding-top:2px;border-top:1px solid #f0f0f0;'><span style='color:#999;'>Total:</span> <strong style='color:#dd4b39;'>UGX " . number_format($totalCommission, 0) . "</strong></div>
                <div style='padding-top:2px;border-top:1px solid #f0f0f0;'><span style='color:#999;'>Balance:</span> <strong style='color:#3c8dbc;'>UGX " . number_format($balance, 0) . "</strong></div>
            </div>";
        })->width(170);


        // Row actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

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
        $item = OrderedItem::findOrFail($id);

        // Get back link
        $back_link = admin_url('ordered-items');
        if (isset($_SERVER['HTTP_REFERER'])) {
            if ($_SERVER['HTTP_REFERER'] != null) {
                if (strlen($_SERVER['HTTP_REFERER']) > 10) {
                    $back_link = $_SERVER['HTTP_REFERER'];
                }
            }
        }

        return view('admin.ordered-item-details', [
            'item' => $item,
            'back_link' => $back_link
        ]);

        $show = new Show(OrderedItem::findOrFail($id));

        $show->panel()
            ->title('Product Sale Details')
            ->style('primary');

        $show->divider('Sale Information');

        $show->field('id', __('Sale ID'));
        $show->field('created_at', __('Sale Date'))->as(function ($date) {
            return date('l, F j, Y - H:i:s', strtotime($date));
        });

        $show->divider('Order Reference');

        // Display order ID - use getOriginal to avoid relationship conflict
        $show->field('order', __('Order'))->as(function () {
            $orderId = $this->getOriginal('order');
            if (!$orderId) {
                return '<span class="label label-default">Standalone Sale (No Order Attached)</span>';
            }
            $order = \App\Models\Order::find($orderId);
            if ($order) {
                $receipt = $order->receipt_number ?? "Order #{$orderId}";
                $customer = $order->customer_name ?? 'N/A';
                $status = [
                    0 => '<span class="label label-warning">Pending</span>',
                    1 => '<span class="label label-info">Processing</span>',
                    2 => '<span class="label label-success">Completed</span>',
                    3 => '<span class="label label-danger">Cancelled</span>',
                    4 => '<span class="label label-danger">Failed</span>',
                    5 => '<span class="label label-warning">Refunded</span>',
                ][$order->status] ?? '<span class="label label-default">Unknown</span>';

                return "
                    <strong>Receipt:</strong> {$receipt}<br>
                    <strong>Customer:</strong> {$customer}<br>
                    <strong>Status:</strong> {$status}
                ";
            }
            return "Order #{$orderId} (Not Found)";
        })->unescape();

        $show->divider('Product Details');

        // Display product with image - use getOriginal to avoid relationship conflict
        $show->field('product', __('Product'))->as(function () {
            $productId = $this->getOriginal('product');
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $image = $product->feature_photo
                    ? "<img src=" . url('storage/' . $product->feature_photo) . " style='width:60px;height:60px;object-fit:cover;border-radius:6px;margin-right:10px;border:2px solid #007bff;'>"
                    : "";
                $category = $product->category ? \App\Models\ProductCategory::find($product->category)?->name ?? 'N/A' : 'N/A';

                return "
                    {$image}
                    <strong>Name:</strong> {$product->name}<br>
                    <strong>Category:</strong> {$category}<br>
                    <strong>Product ID:</strong> {$product->local_id}<br>
                    <strong>Current Stock:</strong> " . ($product->quantity ?? 'N/A') . "
                ";
            }
            return "Product #{$productId} (Not Found)";
        })->unescape();

        $show->divider('Sale Breakdown');

        $show->field('qty', __('Quantity Sold'))->as(function ($qty) {
            return '<strong style="font-size:18px;">' . number_format($qty, 0) . ' units</strong>';
        })->unescape();

        $show->field('unit_price', __('Unit Price'))->as(function ($price) {
            return '<strong style="color:#00a65a;font-size:18px;">UGX ' . number_format($price, 0) . '</strong>';
        })->unescape();

        $show->field('subtotal', __('Subtotal (Total Amount)'))->as(function ($subtotal) {
            return '<strong style="color:#00a65a;font-size:24px;">UGX ' . number_format($subtotal, 0) . '</strong>';
        })->unescape();

        $show->divider('💰 Commission Distribution (10-Level MLM)');

        $show->field('commission_status', __('Commission Status'))->as(function () {
            if ($this->commission_is_processed === 'Yes') {
                $date = $this->commission_processed_date ? date('Y-m-d H:i', strtotime($this->commission_processed_date)) : 'N/A';
                $total = number_format($this->total_commission_amount ?? 0, 0);
                return "<span class='label label-success'>✓ Processed</span> on {$date}<br><strong>Total Commission: UGX {$total}</strong>";
            } elseif ($this->item_is_paid === 'Yes' && $this->has_detehm_seller === 'Yes') {
                return "<span class='label label-warning'>⏳ Pending Processing</span>";
            } else {
                return "<span class='label label-default'>Not Applicable</span>";
            }
        })->unescape();

        // Show commission breakdown if processed
        $show->field('commission_breakdown', __('Commission Breakdown'))->as(function () {
            if ($this->commission_is_processed !== 'Yes') {
                return '<p class="text-muted">Commission not yet processed or not applicable.</p>';
            }

            $html = '<table class="table table-bordered table-striped" style="margin-top:10px;">';
            $html .= '<thead><tr><th style="width:15%;">Level</th><th style="width:35%;">Beneficiary</th><th style="width:15%;">Rate</th><th style="width:35%;">Amount Earned</th></tr></thead>';
            $html .= '<tbody>';

            // Seller
            if ($this->commission_seller) {
                $seller = \App\Models\User::find($this->dtehm_user_id);
                $sellerName = $seller ? $seller->name : 'User #' . $this->dtehm_user_id;
                $html .= "<tr>";
                $html .= "<td><strong>Seller</strong></td>";
                $html .= "<td>{$sellerName}</td>";
                $html .= "<td>10%</td>";
                $html .= "<td><strong style='color:#00a65a;'>UGX " . number_format($this->commission_seller, 0) . "</strong></td>";
                $html .= "</tr>";
            }

            // Parents 1-10
            $percentages = [3, 2.5, 2, 1.5, 1, 0.8, 0.6, 0.4, 0.3, 0.2];
            for ($level = 1; $level <= 10; $level++) {
                $commissionField = "commission_parent_{$level}";
                $userIdField = "parent_{$level}_user_id";

                if ($this->$commissionField) {
                    $userId = $this->$userIdField;
                    $user = $userId ? \App\Models\User::find($userId) : null;
                    $userName = $user ? $user->name : ($userId ? "User #{$userId}" : 'Unknown');

                    $html .= "<tr>";
                    $html .= "<td><strong>Parent {$level}</strong></td>";
                    $html .= "<td>{$userName}</td>";
                    $html .= "<td>{$percentages[$level - 1]}%</td>";
                    $html .= "<td><strong style='color:#00a65a;'>UGX " . number_format($this->$commissionField, 0) . "</strong></td>";
                    $html .= "</tr>";
                }
            }

            $html .= '</tbody></table>';
            return $html;
        })->unescape();

        $show->divider('Additional Information');

        $show->field('color', __('Color'))->as(function ($color) {
            return $color ?: '<span class="text-muted">Not specified</span>';
        })->unescape();

        $show->field('size', __('Size'))->as(function ($size) {
            return $size ?: '<span class="text-muted">Not specified</span>';
        })->unescape();

        $show->field('amount', __('Amount (Legacy)'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('updated_at', __('Last Updated'))->as(function ($date) {
            return date('Y-m-d H:i:s', strtotime($date));
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderedItem());

        // Only show ID on edit
        if ($form->isEditing()) {
            $form->display('id', __('Sale ID'));
        }

        // Product Selection - Auto-select if product_id passed in URL
        $preselectedProductId = request()->get('product_id');

        $form->select('product', __('Product'))
            ->options(\App\Models\Product::orderBy('name', 'asc')->get()->mapWithKeys(function ($product) {
                $price = number_format($product->price_1, 0);
                return [$product->id => "{$product->name} (UGX {$price})"];
            }))
            ->rules('required')
            ->required()
            ->default($preselectedProductId);

        // Sponsor Selection (DTEHM Members only)
        $form->select('sponsor_user_id', __('Sponsor'))
            ->options(function () {
                return \App\Models\User::where('is_dtehm_member', 'Yes')
                    ->orderBy('name', 'asc')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        $label = $user->name;
                        if ($user->dtehm_member_id) {
                            $label .= " ({$user->dtehm_member_id})";
                        } elseif ($user->business_name) {
                            $label .= " ({$user->business_name})";
                        }
                        return [$user->id => $label];
                    });
            })
            ->rules('required')
            ->required()
            ->help('The person who will receive 8% commission (The Seller)');

        // Stockist Selection (DTEHM Members only)
        $form->select('stockist_user_id', __('Stockist'))
            ->options(function () {
                return \App\Models\User::where(
                    'is_stockist',
                    'Yes'
                )
                    ->orderBy('name', 'asc')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        $label = $user->name;
                        if ($user->dtehm_member_id) {
                            $label .= " ({$user->dtehm_member_id})";
                        } elseif ($user->business_name) {
                            $label .= " ({$user->business_name})";
                        }
                        return [$user->id => $label];
                    });
            })
            ->rules('required')
            ->required()
            ->help('The person who will receive 7% commission');

        // Quantity
        $form->number('qty', __('Quantity'))
            ->default(1)
            ->min(1)
            ->rules('required|integer|min:1')
            ->required()
            ->help('Number of units to sell');

        // Error display area
        $form->html('<div id="validation-errors" style="display:none; margin: 15px 0 10px 0; padding: 12px 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; color: #856404;">
            <strong><i class="fa fa-exclamation-triangle"></i> Validation Error:</strong>
            <p id="error-message" style="margin: 5px 0 0 0;"></p>
        </div>');

        // Hidden fields (will be auto-calculated)
        $form->hidden('unit_price');
        $form->hidden('subtotal');
        $form->hidden('amount');
        $form->hidden('sponsor_id');
        $form->hidden('stockist_id');

        // Live Summary Display - Flat Simple Design
        $form->html('<div id="commission-summary" style="display:none; margin: 0px 0 0 0;">
            <h5 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333;">Commission Breakdown</h5>
            <table class="table table-bordered" style="margin: 0;">
                <thead style="background: #f5f5f5;">
                    <tr>
                        <th style="padding: 8px; font-size: 12px; font-weight: 600;">Level</th>
                        <th style="padding: 8px; font-size: 12px; font-weight: 600;">Beneficiary</th>
                        <th style="padding: 8px; font-size: 12px; font-weight: 600;">Rate</th>
                        <th style="padding: 8px; font-size: 12px; font-weight: 600;">Amount</th>
                    </tr>
                </thead>
                <tbody id="commission-table-body">
                    <tr><td colspan="4" style="text-align: center; padding: 15px; color: #999;">Select product, sponsor & stockist...</td></tr>
                </tbody>
                <tfoot style="border-top: 2px solid #ddd;">
                    <tr style="background: #f9f9f9;">
                        <td colspan="3" style="padding: 8px; font-weight: 600;">Product Price</td>
                        <td id="summary-product-price" style="padding: 8px; font-weight: 600;">UGX 0</td>
                    </tr>
                    <tr style="background: #fff;">
                        <td colspan="3" style="padding: 8px; font-weight: 600; color: #dc3545;">Total Commission</td>
                        <td id="summary-total-commission" style="padding: 8px; font-weight: 600; color: #dc3545;">UGX 0</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td colspan="3" style="padding: 8px; font-weight: 600; color: #28a745;">Balance</td>
                        <td id="summary-balance" style="padding: 8px; font-weight: 600; font-size: 15px; color: #28a745;">UGX 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>');

        // JavaScript for live updates with validation
        $ajaxUrl = url('api/ajax/calculate-commissions');
        $form->html("<script>
            $(document).ready(function() {
                console.log('OrderedItem form script loaded');
                
                // Function to show error
                function showError(message) {
                    $('#error-message').text(message);
                    $('#validation-errors').show();
                    $('#commission-summary').hide();
                }
                
                // Function to hide error
                function hideError() {
                    $('#validation-errors').hide();
                }
                
                // Function to update commission summary
                function updateCommissionSummary() {
                    console.log('updateCommissionSummary called');
                    
                    var productId = $('select[name=\"product\"]').val();
                    var sponsorUserId = $('select[name=\"sponsor_user_id\"]').val();
                    var stockistUserId = $('select[name=\"stockist_user_id\"]').val();
                    var quantity = $('input[name=\"qty\"]').val() || 1;
                    
                    console.log('Values:', {productId: productId, sponsorUserId: sponsorUserId, stockistUserId: stockistUserId, quantity: quantity});
                    
                    // Hide summary and errors if any field is empty
                    if (!productId || !sponsorUserId || !stockistUserId) {
                        $('#commission-summary').hide();
                        hideError();
                        return;
                    }
                    
                    // Show loading state
                    hideError();
                    $('#commission-table-body').html('<tr><td colspan=\"4\" style=\"text-align: center; padding: 20px; color: #999;\"><i class=\"fa fa-spinner fa-spin\"></i> Validating and calculating...</td></tr>');
                    $('#commission-summary').show();
                    
                    console.log('Making AJAX request...');
                    
                    // Fetch commission calculation
                    $.ajax({
                        url: '" . $ajaxUrl . "',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            product_id: productId,
                            sponsor_user_id: sponsorUserId,
                            stockist_user_id: stockistUserId,
                            quantity: quantity
                        }),
                        success: function(response) {
                            console.log('Success response:', response);
                            hideError();
                            
                            // Build commission table
                            var tableHtml = '';
                            
                            // Stockist row (highlighted)
                            var stockist = response.commissions.stockist;
                            tableHtml += '<tr style=\"background: #fff3cd;\">';
                            tableHtml += '<td><strong>' + stockist.level + '</strong></td>';
                            tableHtml += '<td><strong>' + stockist.member.name + '</strong> (' + (stockist.member.dtehm_member_id || stockist.member.business_name) + ')</td>';
                            tableHtml += '<td style=\"text-align: center;\"><strong>' + stockist.rate + '%</strong></td>';
                            tableHtml += '<td style=\"text-align: right;\"><strong>UGX ' + Math.round(stockist.amount).toLocaleString() + '</strong></td>';
                            tableHtml += '</tr>';
                            
                            // Sponsor row (highlighted in cyan) - THE SELLER
                            var sponsor = response.commissions.sponsor;
                            tableHtml += '<tr style=\"background: #d1ecf1;\">';
                            tableHtml += '<td><strong>' + sponsor.level + '</strong></td>';
                            tableHtml += '<td><strong>' + sponsor.member.name + '</strong> (' + (sponsor.member.dtehm_member_id || sponsor.member.business_name) + ')</td>';
                            tableHtml += '<td style=\"text-align: center;\"><strong>' + sponsor.rate + '%</strong></td>';
                            tableHtml += '<td style=\"text-align: right;\"><strong>UGX ' + Math.round(sponsor.amount).toLocaleString() + '</strong></td>';
                            tableHtml += '</tr>';
                            
                            // Gn1 to Gn10 rows
                            for (var i = 1; i <= 10; i++) {
                                var gn = response.commissions['gn' + i];
                                
                                tableHtml += '<tr>';
                                tableHtml += '<td>' + gn.level + '</td>';
                                
                                if (gn.member) {
                                    tableHtml += '<td>' + gn.member.name + ' (' + (gn.member.dtehm_member_id || gn.member.business_name) + ')</td>';
                                } else {
                                    tableHtml += '<td style=\"color: #999; font-style: italic;\">No parent at this level</td>';
                                }
                                
                                tableHtml += '<td style=\"text-align: center;\">' + gn.rate + '%</td>';
                                
                                if (gn.member) {
                                    tableHtml += '<td style=\"text-align: right;\">UGX ' + Math.round(gn.amount).toLocaleString() + '</td>';
                                } else {
                                    tableHtml += '<td style=\"text-align: right; color: #999;\">-</td>';
                                }
                                
                                tableHtml += '</tr>';
                            }
                            
                            $('#commission-table-body').html(tableHtml);
                            
                            // Update totals
                            var totalPrice = response.product.price * quantity;
                            var totalCommission = response.total_commission * quantity;
                            var totalBalance = response.balance * quantity;
                            
                            $('#summary-product-price').text('UGX ' + Math.round(totalPrice).toLocaleString());
                            $('#summary-total-commission').text('UGX ' + Math.round(totalCommission).toLocaleString());
                            $('#summary-balance').text('UGX ' + Math.round(totalBalance).toLocaleString());
                            
                            // Set hidden form fields
                            $('input[name=\"unit_price\"]').val(response.product.price);
                            $('input[name=\"subtotal\"]').val(totalPrice);
                            $('input[name=\"amount\"]').val(totalPrice);
                            $('input[name=\"sponsor_id\"]').val(response.sponsor.dtehm_member_id || response.sponsor.business_name);
                            $('input[name=\"stockist_id\"]').val(response.stockist.dtehm_member_id || response.stockist.business_name);
                            
                            $('#commission-summary').show();
                        },
                        error: function(xhr) {
                            console.log('Error response:', xhr);
                            var errorMsg = 'Error: Unable to calculate commissions';
                            
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            } else if (xhr.status === 404) {
                                errorMsg = 'Error: API endpoint not found';
                            } else if (xhr.status === 500) {
                                errorMsg = 'Error: Server error occurred';
                            }
                            
                            // Check specific error messages
                            if (errorMsg.includes('sponsor') || errorMsg.includes('Sponsor')) {
                                $('.select2-selection--single[aria-labelledby*=\"sponsor\"]').css('border-color', '#dc3545');
                            }
                            if (errorMsg.includes('stockist') || errorMsg.includes('Stockist')) {
                                $('.select2-selection--single[aria-labelledby*=\"stockist\"]').css('border-color', '#dc3545');
                            }
                            
                            showError(errorMsg);
                            $('#commission-summary').hide();
                        }
                    });
                }
                
                // Reset border colors on change
                $('select[name=\"sponsor_user_id\"], select[name=\"stockist_user_id\"]').on('change', function() {
                    $('.select2-selection--single').css('border-color', '');
                });
                
                // Attach event listeners
                $('select[name=\"product\"], select[name=\"sponsor_user_id\"], select[name=\"stockist_user_id\"], input[name=\"qty\"]').on('change input', function() {
                    console.log('Field changed, updating summary');
                    updateCommissionSummary();
                });
                
                // Initial update if editing (with delay to ensure DOM is ready)
                setTimeout(function() {
                    console.log('Initial update check');
                    var productId = $('select[name=\"product\"]').val();
                    var sponsorUserId = $('select[name=\"sponsor_user_id\"]').val();
                    var stockistUserId = $('select[name=\"stockist_user_id\"]').val();
                    
                    if (productId && sponsorUserId && stockistUserId) {
                        console.log('Triggering initial update');
                        updateCommissionSummary();
                    }
                }, 1000);
            });
        </script>");

        // Saving hook
        $form->saving(function (Form $form) {
            // Validate product
            if (!$form->product) {
                admin_error('Error', 'Product is required');
                return back();
            }

            $product = \App\Models\Product::find($form->product);
            if (!$product) {
                admin_error('Error', 'Selected product not found');
                return back();
            }

            // Validate sponsor
            if (!$form->sponsor_user_id) {
                admin_error('Error', 'Sponsor is required');
                return back();
            }

            $sponsor = \App\Models\User::find($form->sponsor_user_id);
            if (!$sponsor || $sponsor->is_dtehm_member !== 'Yes') {
                admin_error('Error', 'Invalid Sponsor - must be a DTEHM member');
                return back();
            }

            // Validate stockist
            if (!$form->stockist_user_id) {
                admin_error('Error', 'Stockist is required');
                return back();
            }

            $stockist = \App\Models\User::find($form->stockist_user_id);
            if (!$stockist || $stockist->is_dtehm_member !== 'Yes') {
                admin_error('Error', 'Invalid Stockist - must be a DTEHM member');
                return back();
            }

            // Validate quantity
            $qty = $form->qty ?? 1;
            if ($qty < 1) {
                admin_error('Error', 'Quantity must be at least 1');
                return back();
            }

            // Set automatic values
            $form->unit_price = $product->price_1;
            $form->subtotal = $product->price_1 * $qty;
            $form->amount = $product->price_1 * $qty;
            $form->has_detehm_seller = 'Yes';
            $form->dtehm_seller_id = $sponsor->dtehm_member_id ?: $sponsor->business_name;
            $form->dtehm_user_id = $sponsor->id;
        });

        // Saved hook
        $form->saved(function (Form $form) {
            $item = $form->model();
            \Illuminate\Support\Facades\Log::info("Product Sale Created: Item #{$item->id}, Product: {$item->product}, Sponsor: {$item->sponsor_id}, Stockist: {$item->stockist_id}");
            admin_success('Success', 'Product sale recorded successfully!');
        });

        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
