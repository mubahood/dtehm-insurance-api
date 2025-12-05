<style>
    .info-row {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #555;
        font-size: 13px;
        display: inline-block;
        width: 140px;
    }
    .info-value {
        color: #333;
        font-size: 13px;
    }
    .product-image {
        max-width: 100%;
        border-radius: 8px;
        border: 2px solid #ddd;
    }
    .commission-table {
        width: 100%;
        margin-top: 10px;
    }
    .commission-table th {
        background: #f5f5f5;
        padding: 8px;
        font-weight: 600;
        font-size: 12px;
        border: 1px solid #ddd;
    }
    .commission-table td {
        padding: 8px;
        border: 1px solid #ddd;
        font-size: 12px;
    }
    .commission-table tr.stockist-row {
        background: #fff3cd;
    }
</style>

<!-- Back Button -->
<div style="margin-bottom: 15px;">
    <a href="{{ $back_link }}" class="btn btn-default">
        <i class="fa fa-arrow-left"></i> Back to List
    </a>
</div>

<!-- Sale Summary -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-shopping-cart"></i> Sale #{{ $item->id }}
        </h3>
        <div class="box-tools pull-right">
            <span class="label label-success" style="font-size: 14px; padding: 6px 12px;">
                UGX {{ number_format($item->amount ?: $item->subtotal, 0) }}
            </span>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <td class="info-label" style="width: 200px;">Sale ID:</td>
                    <td><strong>#{{ $item->id }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Sale Date:</td>
                    <td>{{ date('l, F j, Y - H:i:s', strtotime($item->created_at)) }}</td>
                </tr>
                @if($item->order)
                    @php
                        $order = \App\Models\Order::find($item->order);
                    @endphp
                    @if($order)
                    <tr>
                        <td class="info-label">Order Reference:</td>
                        <td>
                            <a href="/admin/orders/{{ $order->id }}" target="_blank">
                                #{{ $order->id }} @if($order->receipt_number)({{ $order->receipt_number }})@endif
                            </a>
                        </td>
                    </tr>
                    @endif
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Product Information -->
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-cube"></i> Product Details
        </h3>
    </div>
    <div class="box-body">
        @php
            $product = \App\Models\Product::find($item->product);
        @endphp

        @if($product)
        <div class="row">
            <div class="col-md-3 text-center">
                @if($product->feature_photo)
                    <img src="{{ url('storage/' . $product->feature_photo) }}" class="product-image" alt="{{ $product->name }}" style="max-width: 100%;">
                @else
                    <div style="background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 200px; border: 2px solid #ddd; border-radius: 8px;">
                        <i class="fa fa-image" style="font-size: 48px; color: #ccc;"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-9">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td class="info-label" style="width: 150px;">Product Name:</td>
                            <td><strong>{{ $product->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Category:</td>
                            <td>
                                @php
                                    $category = $product->category ? \App\Models\ProductCategory::find($product->category) : null;
                                @endphp
                                {{ $category ? $category->name : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Unit Price:</td>
                            <td><strong style="color: #007bff; font-size: 16px;">UGX {{ number_format($item->unit_price, 0) }}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Quantity:</td>
                            <td><strong style="font-size: 16px;">{{ $item->qty }} units</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Subtotal:</td>
                            <td><strong style="color: #28a745; font-size: 18px;">UGX {{ number_format($item->amount ?: $item->subtotal, 0) }}</strong></td>
                        </tr>
                        @if($item->points_earned)
                        <tr>
                            <td class="info-label">Points Earned:</td>
                            <td>
                                <span class="badge bg-purple" style="font-size: 14px; padding: 4px 10px;">
                                    <i class="fa fa-star"></i> {{ $item->points_earned }} points
                                </span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p class="text-muted">Product information not available</p>
        @endif
    </div>
</div>

<!-- Sponsor & Stockist Information -->
<div class="row">
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-user"></i> Sponsor (Seller)
                </h3>
            </div>
            <div class="box-body">
                @php
                    $sponsor = $item->sponsor_user_id ? \App\Models\User::find($item->sponsor_user_id) : null;
                @endphp

                @if($sponsor)
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td class="info-label" style="width: 120px;">Name:</td>
                            <td><strong>{{ $sponsor->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Member ID:</td>
                            <td>
                                @if($sponsor->dtehm_member_id)
                                    <span class="label label-success">{{ $sponsor->dtehm_member_id }}</span>
                                @endif
                                @if($sponsor->business_name)
                                    <span class="label label-primary">{{ $sponsor->business_name }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Phone:</td>
                            <td>{{ $sponsor->phone_number }}</td>
                        </tr>
                        @if($sponsor->total_points)
                        <tr>
                            <td class="info-label">Total Points:</td>
                            <td>
                                <span class="badge bg-purple"><i class="fa fa-star"></i> {{ number_format($sponsor->total_points) }} points</span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @else
                <p class="text-muted">Sponsor information not available</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-briefcase"></i> Stockist
                </h3>
            </div>
            <div class="box-body">
                @php
                    $stockist = $item->stockist_user_id ? \App\Models\User::find($item->stockist_user_id) : null;
                @endphp

                @if($stockist)
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td class="info-label" style="width: 120px;">Name:</td>
                            <td><strong>{{ $stockist->name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Member ID:</td>
                            <td>
                                @if($stockist->dtehm_member_id)
                                    <span class="label label-success">{{ $stockist->dtehm_member_id }}</span>
                                @endif
                                @if($stockist->business_name)
                                    <span class="label label-primary">{{ $stockist->business_name }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Phone:</td>
                            <td>{{ $stockist->phone_number }}</td>
                        </tr>
                    </tbody>
                </table>
                @else
                <p class="text-muted">Stockist information not available</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Commission Breakdown -->
<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-money"></i> Commission Breakdown & Network Structure
        </h3>
    </div>
    <div class="box-body">

        @php
            $price = $item->amount ?: $item->unit_price;
            
            // Calculate all commissions
            $stockistRate = 0.07;  // 7%
            $sponsorRate = 0.08;   // 8% - THE SELLER
            $gnRates = [0.03, 0.025, 0.02, 0.015, 0.01, 0.008, 0.006, 0.005, 0.004, 0.002];
            
            $stockistCommission = $price * $stockistRate;
            $sponsorCommission = $price * $sponsorRate;
            $gnCommissions = [];
            $totalGnCommission = 0;
            
            foreach ($gnRates as $i => $rate) {
                $amount = $price * $rate;
                $gnCommissions[$i + 1] = $amount;
                $totalGnCommission += $amount;
            }
            
            $totalCommission = $stockistCommission + $sponsorCommission + $totalGnCommission;
            $balance = $price - $totalCommission;
            
            // Get network hierarchy
            $networkMembers = [];
            if ($sponsor) {
                $currentMember = $sponsor;
                for ($i = 1; $i <= 10; $i++) {
                    if ($currentMember && $currentMember->sponsor_id) {
                        $parent = \App\Models\User::where('business_name', $currentMember->sponsor_id)
                            ->orWhere('dtehm_member_id', $currentMember->sponsor_id)
                            ->first();
                        if ($parent) {
                            $networkMembers[$i] = $parent;
                            $currentMember = $parent;
                        } else {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }
        @endphp

        <!-- Summary Cards -->
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-2">
                <div style="background: #e7f3ff; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Product Price</div>
                    <div style="font-size: 16px; font-weight: 600; color: #004085;">UGX {{ number_format($price, 0) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="background: #fff3cd; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Stockist (7%)</div>
                    <div style="font-size: 16px; font-weight: 600; color: #856404;">UGX {{ number_format($stockistCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="background: #d1ecf1; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Sponsor (8%)</div>
                    <div style="font-size: 16px; font-weight: 600; color: #0c5460;">UGX {{ number_format($sponsorCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="background: #d4edda; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Network (Gn1-10)</div>
                    <div style="font-size: 16px; font-weight: 600; color: #155724;">UGX {{ number_format($totalGnCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="background: #f8d7da; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Total Commission</div>
                    <div style="font-size: 16px; font-weight: 600; color: #721c24;">UGX {{ number_format($totalCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="background: #e2e3e5; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Balance</div>
                    <div style="font-size: 16px; font-weight: 600; color: #383d41;">UGX {{ number_format($balance, 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown Table -->
        <table class="commission-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Level</th>
                    <th style="width: 35%;">Beneficiary</th>
                    <th style="width: 20%;">Member ID</th>
                    <th style="width: 10%;">Rate</th>
                    <th style="width: 23%;">Commission</th>
                </tr>
            </thead>
            <tbody>
                <!-- Stockist -->
                <tr class="stockist-row">
                    <td><strong>Stockist</strong></td>
                    <td>
                        @if($stockist)
                            <strong>{{ $stockist->name }}</strong>
                        @else
                            <span class="text-muted">Not assigned</span>
                        @endif
                    </td>
                    <td>
                        @if($stockist)
                            @if($stockist->dtehm_member_id)
                                <span class="badge badge-success">{{ $stockist->dtehm_member_id }}</span>
                            @endif
                            @if($stockist->business_name)
                                <span class="badge badge-primary">{{ $stockist->business_name }}</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td><strong>7%</strong></td>
                    <td><strong style="color: #856404;">UGX {{ number_format($stockistCommission, 0) }}</strong></td>
                </tr>

                <!-- Sponsor (The Seller) -->
                <tr style="background: #d1ecf1;">
                    <td><strong>Sponsor</strong></td>
                    <td>
                        @if($sponsor)
                            <strong>{{ $sponsor->name }}</strong> <small class="text-muted">(The Seller)</small>
                        @else
                            <span class="text-muted">Not assigned</span>
                        @endif
                    </td>
                    <td>
                        @if($sponsor)
                            @if($sponsor->dtehm_member_id)
                                <span class="badge badge-success">{{ $sponsor->dtehm_member_id }}</span>
                            @endif
                            @if($sponsor->business_name)
                                <span class="badge badge-primary">{{ $sponsor->business_name }}</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td><strong>8%</strong></td>
                    <td><strong style="color: #0c5460;">UGX {{ number_format($sponsorCommission, 0) }}</strong></td>
                </tr>

                <!-- Network Levels (Gn1 - Gn10) -->
                @foreach([1,2,3,4,5,6,7,8,9,10] as $level)
                    @php
                        $percentage = [3, 2.5, 2, 1.5, 1, 0.8, 0.6, 0.5, 0.4, 0.2][$level - 1];
                        $member = isset($networkMembers[$level]) ? $networkMembers[$level] : null;
                    @endphp
                    <tr class="network-row">
                        <td><strong>Gn{{ $level }}</strong></td>
                        <td>
                            @if($member)
                                {{ $member->name }}
                            @else
                                <span class="text-muted" style="font-style: italic;">No parent at this level</span>
                            @endif
                        </td>
                        <td>
                            @if($member)
                                @if($member->dtehm_member_id)
                                    <span class="badge badge-success">{{ $member->dtehm_member_id }}</span>
                                @endif
                                @if($member->business_name)
                                    <span class="badge badge-primary">{{ $member->business_name }}</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $percentage }}%</td>
                        <td>
                            @if($member)
                                <strong style="color: #28a745;">UGX {{ number_format($gnCommissions[$level], 0) }}</strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot style="border-top: 2px solid #007bff;">
                <tr style="background: #f8f9fa;">
                    <td colspan="4" style="text-align: right; font-weight: 600; font-size: 13px; padding: 8px;">
                        Total Commission:
                    </td>
                    <td style="font-weight: 600; font-size: 14px; color: #dc3545; padding: 8px;">
                        UGX {{ number_format($totalCommission, 0) }}
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td colspan="4" style="text-align: right; font-weight: 600; font-size: 13px; padding: 8px;">
                        Balance (Company Profit):
                    </td>
                    <td style="font-weight: 600; font-size: 14px; color: #007bff; padding: 8px;">
                        UGX {{ number_format($balance, 0) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-left: 3px solid #007bff;">
            <strong style="font-size: 13px;"><i class="fa fa-info-circle"></i> Commission Structure:</strong>
            <ul style="margin: 8px 0 0 20px; font-size: 12px;">
                <li><strong>Stockist:</strong> Receives 8% for distributing the product</li>
                <li><strong>Network Levels (Gn1-Gn10):</strong> Sponsor's upline receives commissions at decreasing rates</li>
                <li><strong>Total Network Commission:</strong> {{ number_format(($totalGnCommission/$price)*100, 1) }}% distributed across 10 levels</li>
                <li><strong>Company Balance:</strong> {{ number_format(($balance/$price)*100, 1) }}% retained for operations</li>
            </ul>
        </div>
    </div>
</div>
