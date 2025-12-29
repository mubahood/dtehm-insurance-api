<!DOCTYPE html>
<html>
<head>
    <title>Initiate Member Payment - DTEHM Admin</title>
    <link rel="stylesheet" href="{{ admin_asset('vendor/laravel-admin/AdminLTE/dist/css/AdminLTE.min.css') }}">
    <link rel="stylesheet" href="{{ admin_asset('vendor/laravel-admin/font-awesome/css/font-awesome.min.css') }}">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2 px 10px rgba(0,0,0,0.1);
        }
        .member-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #05179F;
        }
        .amount-box {
            background: #05179F;
            color: white;
            padding: 30px;
            border-radius: 5px;
            text-align: center;
            margin: 30px 0;
        }
        .amount-box h2 {
            margin: 0;
            font-size: 48px;
            font-weight: bold;
        }
        .amount-box p {
            margin: 10px 0 0;
            font-size: 18px;
            opacity: 0.9;
        }
        .membership-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .membership-list li {
            padding: 12px;
            background: #f0f8ff;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 3px solid #05179F;
        }
        .membership-list li.paid {
            background: #d4edda;
            border-left-color: #28a745;
            opacity: 0.7;
        }
        .btn-payment {
            background: #05179F;
            color: white;
            padding: 15px 40px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn-payment:hover {
            background: #03125f;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="payment-container">
        <h1 style="color: #05179F; margin-bottom: 30px; text-align: center;">
            <i class="fa fa-credit-card"></i> Initiate Membership Payment
        </h1>

        <div class="member-info">
            <h3 style="margin-top: 0; color: #05179F;">Member Information</h3>
            <p><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
            <p><strong>Phone:</strong> {{ $user->phone_number }}</p>
            @if($user->email)
            <p><strong>Email:</strong> {{ $user->email }}</p>
            @endif
            @if($user->dtehm_member_id)
            <p><strong>DTEHM ID:</strong> {{ $user->dtehm_member_id }}</p>
            @endif
            @if($user->business_name)
            <p><strong>DIP ID:</strong> {{ $user->business_name }}</p>
            @endif
        </div>

        <div class="alert-info">
            <i class="fa fa-info-circle"></i> 
            <strong>Payment Process:</strong> After clicking "Proceed to Payment", you will be redirected to PesaPal to complete the payment securely.
        </div>

        <h3 style="color: #05179F;">Membership Type(s):</h3>
        <ul class="membership-list">
            @if($user->is_dtehm_member == 'Yes')
            <li class="{{ $hasDtehmPayment ? 'paid' : '' }}">
                <strong>DTEHM Membership</strong> - 76,000 UGX
                @if($hasDtehmPayment)
                <span style="color: #28a745; float: right;"><i class="fa fa-check-circle"></i> Already Paid</span>
                @else
                <span style="color: #dc3545; float: right;"><i class="fa fa-exclamation-circle"></i> Pending Payment</span>
                @endif
            </li>
            @endif
            
            @if($user->is_dip_member == 'Yes')
            <li class="{{ $hasDipPayment ? 'paid' : '' }}">
                <strong>DIP Membership</strong> - 20,000 UGX
                @if($hasDipPayment)
                <span style="color: #28a745; float: right;"><i class="fa fa-check-circle"></i> Already Paid</span>
                @else
                <span style="color: #dc3545; float: right;"><i class="fa fa-exclamation-circle"></i> Pending Payment</span>
                @endif
            </li>
            @endif
        </ul>

        <div class="amount-box">
            <p>Total Payment Amount</p>
            <h2>UGX {{ number_format($totalAmount, 0) }}</h2>
            <p style="font-size: 14px; margin-top: 15px;">
                Payment includes: {{ implode(' + ', $membershipTypes) }}
            </p>
        </div>

        <form action="{{ admin_url('membership-payment/process/' . $user->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn-payment">
                <i class="fa fa-credit-card"></i> Proceed to Payment (PesaPal)
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ admin_url('users') }}" class="btn-cancel">
                <i class="fa fa-arrow-left"></i> Cancel & Return to Users
            </a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong><i class="fa fa-exclamation-triangle"></i> Note:</strong>
            <ul style="margin-bottom: 0;">
                <li>Payment is processed through PesaPal payment gateway</li>
                <li>Member will receive payment confirmation via SMS/Email</li>
                <li>Membership will be activated automatically upon successful payment</li>
                <li>If payment is already completed for any membership, it will be skipped</li>
            </ul>
        </div>
    </div>
</body>
</html>
