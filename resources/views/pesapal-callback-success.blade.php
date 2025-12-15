<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - DTEHM</title>
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
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon svg {
            width: 48px;
            height: 48px;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            animation: checkmark 0.5s ease-out 0.3s forwards;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes checkmark {
            to {
                stroke-dashoffset: 0;
            }
        }
        
        h1 {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .tracking-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .tracking-label {
            color: #718096;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .tracking-value {
            color: #2d3748;
            font-size: 14px;
            font-weight: 600;
            word-break: break-all;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            margin: 20px auto;
            border: 4px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        .note {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .close-button {
            margin-top: 24px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        .close-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .close-button:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <svg viewBox="0 0 52 52">
                <polyline points="14 27 22 35 38 17"/>
            </svg>
        </div>
        
        <h1>Payment Received!</h1>
        <p class="message">{{ $message ?? 'Your payment is being processed. Please wait while we confirm your transaction.' }}</p>
        
        @if(isset($order_tracking_id) && $order_tracking_id)
        <div class="tracking-info">
            <div class="tracking-label">Order Tracking ID</div>
            <div class="tracking-value">{{ $order_tracking_id }}</div>
        </div>
        @endif
        
        <div class="spinner"></div>
        
        <p class="note">
            We're verifying your payment with our payment provider. 
            This usually takes a few seconds. You can close this window and 
            check your purchase history in the app.
        </p>
        
        <button class="close-button" onclick="closeWindow()">
            Close & Return to App
        </button>
    </div>
    
    <script>
        function closeWindow() {
            // Try to close the window (works if opened by JS)
            if (window.opener) {
                window.close();
            } else {
                // Redirect to app or show confirmation
                alert('Please return to the app to view your purchase.');
                // Optionally redirect to app deep link
                // window.location.href = 'dtehm://product-purchase-success';
            }
        }
        
        // Auto-close after 10 seconds
        setTimeout(() => {
            closeWindow();
        }, 10000);
    </script>
</body>
</html>
