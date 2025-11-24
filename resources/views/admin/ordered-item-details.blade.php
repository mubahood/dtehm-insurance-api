@extends('admin::index')

@section('content')
<style>
    .detail-card {
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
    }
    .detail-header {
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .detail-header h3 {
        margin: 0;
        color: #007bff;
        font-weight: 600;
        font-size: 18px;
    }
    .info-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        width: 180px;
        color: #555;
        font-size: 13px;
    }
    .info-value {
        flex: 1;
        color: #333;
        font-size: 13px;
    }
    .product-image {
        max-width: 200px;
        margin-bottom: 15px;
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
    .summary-box {
        background: #007bff;
        color: white;
        padding: 15px;
        text-align: center;
    }
    .summary-box h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }
    .summary-box p {
        margin: 5px 0 0 0;
        font-size: 13px;
    }
</style>

<div class="container-fluid" style="padding: 20px;">
    <!-- Back Button -->
    <div style="margin-bottom: 20px;">
        <a href="{{ $back_link }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Sale Summary -->
    <div class="detail-card">
        <div class="detail-header">
            <h3><i class="fa fa-shopping-cart"></i> Sale #{{ $item->id }}</h3>
            <small class="text-muted">{{ date('l, F j, Y - H:i:s', strtotime($item->created_at)) }}</small>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="info-row">
                    <div class="info-label">Sale ID:</div>
                    <div class="info-value"><strong>#{{ $item->id }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sale Date:</div>
                    <div class="info-value">{{ date('d M Y, H:i', strtotime($item->created_at)) }}</div>
                </div>
                @if($item->order)
                    @php
                        $order = \App\Models\Order::find($item->order);
                    @endphp
                    @if($order)
                    <div class="info-row">
                        <div class="info-label">Order Reference:</div>
                        <div class="info-value">
                            <a href="/admin/orders/{{ $order->id }}" target="_blank">
                                #{{ $order->id }} @if($order->receipt_number)({{ $order->receipt_number }})@endif
                            </a>
                        </div>
                    </div>
                    @endif
                @endif
            </div>
            <div class="col-md-4">
                <div class="summary-box">
                    <p style="margin: 0; font-size: 14px;">Total Amount</p>
                    <h2>UGX {{ number_format($item->amount ?: $item->subtotal, 0) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Information -->
    <div class="detail-card">
        <div class="detail-header">
            <h3><i class="fa fa-cube"></i> Product Details</h3>
        </div>

        @php
            $product = \App\Models\Product::find($item->product);
        @endphp

        @if($product)
        <div class="row">
            <div class="col-md-3 text-center">
                @if($product->feature_photo)
                    <img src="{{ $product->feature_photo }}" class="product-image" alt="{{ $product->name }}">
                @else
                    <div class="product-image" style="background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 200px;">
                        <i class="fa fa-image" style="font-size: 48px; color: #ccc;"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-9">
                <div class="info-row">
                    <div class="info-label">Product Name:</div>
                    <div class="info-value"><strong>{{ $product->name }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Category:</div>
                    <div class="info-value">
                        @php
                            $category = $product->category ? \App\Models\ProductCategory::find($product->category) : null;
                        @endphp
                        {{ $category ? $category->name : 'N/A' }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit Price:</div>
                    <div class="info-value"><strong style="color: #007bff; font-size: 18px;">UGX {{ number_format($item->unit_price, 0) }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Quantity:</div>
                    <div class="info-value"><strong style="font-size: 18px;">{{ $item->qty }} units</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Amount:</div>
                    <div class="info-value"><strong style="color: #28a745; font-size: 20px;">UGX {{ number_format($item->amount ?: $item->subtotal, 0) }}</strong></div>
                </div>
            </div>
        </div>
        @else
        <p class="text-muted">Product information not available</p>
        @endif
    </div>

    <!-- Sponsor & Stockist Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="detail-card">
                <div class="detail-header">
                    <h3><i class="fa fa-user"></i> Sponsor</h3>
                </div>

                @php
                    $sponsor = $item->sponsor_user_id ? \App\Models\User::find($item->sponsor_user_id) : null;
                @endphp

                @if($sponsor)
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><strong>{{ $sponsor->name }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Member ID:</div>
                    <div class="info-value">
                        @if($sponsor->dtehm_member_id)
                            <span class="badge badge-success">{{ $sponsor->dtehm_member_id }}</span>
                        @endif
                        @if($sponsor->business_name)
                            <span class="badge badge-primary">{{ $sponsor->business_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value">{{ $sponsor->phone_number }}</div>
                </div>
                @else
                <p class="text-muted">Sponsor information not available</p>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="detail-card">
                <div class="detail-header">
                    <h3><i class="fa fa-briefcase"></i> Stockist</h3>
                </div>

                @php
                    $stockist = $item->stockist_user_id ? \App\Models\User::find($item->stockist_user_id) : null;
                @endphp

                @if($stockist)
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><strong>{{ $stockist->name }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Member ID:</div>
                    <div class="info-value">
                        @if($stockist->dtehm_member_id)
                            <span class="badge badge-success">{{ $stockist->dtehm_member_id }}</span>
                        @endif
                        @if($stockist->business_name)
                            <span class="badge badge-primary">{{ $stockist->business_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value">{{ $stockist->phone_number }}</div>
                </div>
                @else
                <p class="text-muted">Stockist information not available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Commission Breakdown -->
    <div class="detail-card">
        <div class="detail-header">
            <h3><i class="fa fa-money"></i> Commission Breakdown & Network Structure</h3>
        </div>

        @php
            $price = $item->amount ?: $item->unit_price;
            
            // Calculate all commissions
            $stockistRate = 0.08;
            $gnRates = [0.03, 0.025, 0.02, 0.015, 0.01, 0.008, 0.006, 0.005, 0.004, 0.002];
            
            $stockistCommission = $price * $stockistRate;
            $gnCommissions = [];
            $totalGnCommission = 0;
            
            foreach ($gnRates as $i => $rate) {
                $amount = $price * $rate;
                $gnCommissions[$i + 1] = $amount;
                $totalGnCommission += $amount;
            }
            
            $totalCommission = $stockistCommission + $totalGnCommission;
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
            <div class="col-md-3">
                <div style="background: #e7f3ff; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Product Price</div>
                    <div style="font-size: 16px; font-weight: 600; color: #004085;">UGX {{ number_format($price, 0) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background: #fff3cd; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Stockist (8%)</div>
                    <div style="font-size: 16px; font-weight: 600; color: #856404;">UGX {{ number_format($stockistCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background: #d4edda; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Network (Gn1-10)</div>
                    <div style="font-size: 16px; font-weight: 600; color: #155724;">UGX {{ number_format($totalGnCommission, 0) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background: #f8d7da; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: #555; margin-bottom: 3px;">Total Commission</div>
                    <div style="font-size: 16px; font-weight: 600; color: #721c24;">UGX {{ number_format($totalCommission, 0) }}</div>
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
                    <td><strong>8%</strong></td>
                    <td><strong style="color: #856404;">UGX {{ number_format($stockistCommission, 0) }}</strong></td>
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
            <strong style="font-size: 13px;">Commission Structure:</strong>
            <ul style="margin: 8px 0 0 20px; font-size: 12px;">
                <li><strong>Stockist:</strong> Receives 8% for distributing the product</li>
                <li><strong>Network Levels (Gn1-Gn10):</strong> Sponsor's upline receives commissions at decreasing rates</li>
                <li><strong>Total Network Commission:</strong> {{ number_format(($totalGnCommission/$price)*100, 1) }}% distributed across 10 levels</li>
                <li><strong>Company Balance:</strong> {{ number_format(($balance/$price)*100, 1) }}% retained for operations</li>
            </ul>
        </div>
    </div>
</div>
@endsection
