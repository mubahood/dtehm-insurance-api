<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send User Credentials - {{ env('APP_NAME') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.6;
        }

        .alert-success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .alert-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .user-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .user-info h3 {
            font-size: 18px;
            margin-bottom: 16px;
            color: #333;
        }

        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            width: 120px;
            flex-shrink: 0;
        }

        .info-value {
            color: #333;
            word-break: break-word;
        }

        .password-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .password-box strong {
            font-size: 32px;
            color: #856404;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }

        .password-box p {
            margin-top: 8px;
            color: #856404;
            font-size: 14px;
        }

        .sms-details {
            background: #e7f3ff;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .sms-details h4 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #0066cc;
        }

        .sms-details pre {
            background: white;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
            border: 1px solid #b3d9ff;
        }

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .icon {
            margin-right: 8px;
        }

        @media (max-width: 480px) {
            .buttons {
                flex-direction: column;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Send User Credentials</h1>
            <p>{{ env('APP_NAME') }}</p>
        </div>

        <div class="content">
            @if(isset($success) && $success)
                <div class="alert alert-success">
                    <div class="alert-icon">✅</div>
                    <strong>Success!</strong><br>
                    {{ $message }}
                </div>
            @endif

            @if(isset($error) && $error)
                <div class="alert alert-error">
                    <div class="alert-icon">❌</div>
                    <strong>Error!</strong><br>
                    {{ $error }}
                </div>
            @endif

            @if(isset($user) && $user)
                <div class="user-info">
                    <h3>👤 User Information</h3>
                    <div class="info-row">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $user->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $user->email ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">{{ $user->phone_number ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">User ID:</div>
                        <div class="info-value">#{{ $user->id }}</div>
                    </div>
                </div>

                @if(isset($response) && $response && isset($response->password))
                    <div class="password-box">
                        <p><strong>New Password Generated:</strong></p>
                        <strong>{{ $response->password }}</strong>
                        <p>(This password has been sent via SMS)</p>
                    </div>
                @endif

                @if(isset($response) && $response && isset($response->sms_response))
                    <div class="sms-details">
                        <h4>📨 SMS Delivery Details</h4>
                        <div class="info-row">
                            <div class="info-label">Status:</div>
                            <div class="info-value">{{ $response->sms_response->status ?? 'Unknown' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Code:</div>
                            <div class="info-value">{{ $response->sms_response->code ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Message ID:</div>
                            <div class="info-value">{{ $response->sms_response->messageID ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Recipients:</div>
                            <div class="info-value">{{ $response->sms_response->contacts ?? 'N/A' }}</div>
                        </div>
                    </div>
                @endif
            @endif

            <div class="buttons">
                <a href="{{ admin_url('auth/users') }}" class="btn btn-secondary">
                    <span class="icon">←</span> Back to Users
                </a>
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <span class="icon">🔄</span> Try Again
                </button>
            </div>
        </div>
    </div>
</body>
</html>
