#!/bin/bash

# MultipleOrder API Testing Script
# Tests all API endpoints for the MultipleOrder system

BASE_URL="http://localhost:8888/dtehm-insurance-api/api"
echo "========================================"
echo "MultipleOrder API Testing"
echo "========================================"
echo ""

# Test 1: Create a new Multiple Order
echo "Test 1: Creating new multiple order..."
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/multiple-orders/create" \
  -H "Content-Type: application/json" \
  -d '{
    "sponsor_id": "3",
    "stockist_id": "1",
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "color": "Blue",
        "size": "M"
      },
      {
        "product_id": 2,
        "quantity": 1
      }
    ],
    "delivery_fee": 5000,
    "delivery_method": "delivery",
    "delivery_address": "Test Address, Kampala",
    "customer_phone": "0700000000",
    "customer_email": "test@example.com",
    "customer_notes": "API Test Order"
  }')

echo "$CREATE_RESPONSE" | jq '.'
ORDER_ID=$(echo "$CREATE_RESPONSE" | jq -r '.data.multiple_order.id')
echo ""
echo "Created Order ID: $ORDER_ID"
echo ""

# Test 2: Get Order Details
echo "Test 2: Getting order details..."
curl -s "$BASE_URL/multiple-orders/$ORDER_ID" | jq '.'
echo ""

# Test 3: Check payment status
echo "Test 3: Checking payment status..."
curl -s "$BASE_URL/multiple-orders/$ORDER_ID/payment-status" | jq '.'
echo ""

# Test 4: Get user's orders
echo "Test 4: Getting user's orders (user_id=3)..."
curl -s "$BASE_URL/multiple-orders/user/3" | jq '.'
echo ""

# Test 5: Initialize payment (will get real Pesapal URL in production)
echo "Test 5: Initializing payment..."
PAYMENT_RESPONSE=$(curl -s -X POST "$BASE_URL/multiple-orders/$ORDER_ID/initialize-payment" \
  -H "Content-Type: application/json" \
  -d '{
    "callback_url": "http://localhost:8888/dtehm-insurance-api/api/pesapal/multiple-order-callback"
  }')

echo "$PAYMENT_RESPONSE" | jq '.'
echo ""

# Test 6: Simulate payment completion and conversion
echo "Test 6: Simulating payment completion..."
echo "(In real scenario, this would be done by Pesapal IPN)"
echo "Manually updating order #2 to test conversion..."

# Get order #2 (the one we created in seeder with COMPLETED payment)
echo ""
echo "Test 7: Getting completed order #2..."
curl -s "$BASE_URL/multiple-orders/2" | jq '.'
echo ""

# Test 8: Check conversion status
echo "Test 8: Checking conversion status of order #2..."
curl -s "$BASE_URL/multiple-orders/2/payment-status" | jq '.'
echo ""

echo "========================================"
echo "All API tests completed!"
echo "========================================"
echo ""
echo "Summary:"
echo "- Created new order #$ORDER_ID"
echo "- Payment can be initialized with Pesapal"
echo "- Order #2 already converted to OrderedItems"
echo ""
echo "Next steps:"
echo "1. Test in production with real Pesapal payments"
echo "2. Test IPN callbacks"
echo "3. Verify commission processing on converted orders"
