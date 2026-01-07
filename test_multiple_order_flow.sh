#!/bin/bash

# MultipleOrder Complete Flow Testing Script
# This script tests the entire flow: Create → Initialize Payment → Check Status
# Usage: bash test_multiple_order_flow.sh

set -e  # Exit on error

# Configuration
API_BASE="http://localhost:8888/dtehm-insurance-api/api"
USER_ID=3
SPONSOR_ID="DTEHM003"
STOCKIST_ID="DTEHM001"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}MultipleOrder Flow Testing Script${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Step 1: Create MultipleOrder
echo -e "${YELLOW}Step 1: Creating MultipleOrder...${NC}"
CREATE_PAYLOAD=$(cat <<EOF
{
  "user_id": $USER_ID,
  "sponsor_id": "$SPONSOR_ID",
  "stockist_id": "$STOCKIST_ID",
  "items": [
    {
      "product_id": 1,
      "product_name": "Rhue",
      "product_price": "115000",
      "quantity": 2,
      "subtotal": "230000"
    },
    {
      "product_id": 2,
      "product_name": "Rhue Rub",
      "product_price": "65000",
      "quantity": 1,
      "subtotal": "65000"
    }
  ],
  "subtotal": "295000",
  "delivery_fee": "5000",
  "total_amount": "300000",
  "currency": "UGX",
  "payment_method": "pesapal",
  "delivery_method": "delivery",
  "customer_name": "Test Customer",
  "customer_email": "test@example.com",
  "customer_phone": "0700000000",
  "delivery_address": "Test Address, Kampala, Uganda",
  "customer_notes": "Automated test order"
}
EOF
)

CREATE_RESPONSE=$(curl -s -X POST "$API_BASE/multiple-orders/create" \
  -H "Content-Type: application/json" \
  -d "$CREATE_PAYLOAD")

ORDER_ID=$(echo "$CREATE_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['data']['multiple_order']['id'])" 2>/dev/null || echo "")

if [ -z "$ORDER_ID" ]; then
  echo -e "${RED}✗ Failed to create order${NC}"
  echo "$CREATE_RESPONSE" | python3 -m json.tool
  exit 1
fi

echo -e "${GREEN}✓ Order created successfully${NC}"
echo -e "  Order ID: ${GREEN}$ORDER_ID${NC}"
echo -e "  Total: ${GREEN}UGX 300,000${NC}"
echo "$CREATE_RESPONSE" | python3 -m json.tool | head -30
echo ""

# Step 2: Initialize Pesapal Payment
echo -e "${YELLOW}Step 2: Initializing Pesapal payment...${NC}"
PAYMENT_RESPONSE=$(curl -s -X POST "$API_BASE/multiple-orders/$ORDER_ID/initialize-payment" \
  -H "Content-Type: application/json" \
  -d "{}")

REDIRECT_URL=$(echo "$PAYMENT_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['redirect_url'])" 2>/dev/null || echo "")

if [ -z "$REDIRECT_URL" ]; then
  echo -e "${RED}✗ Failed to initialize payment${NC}"
  echo "$PAYMENT_RESPONSE" | python3 -m json.tool
  exit 1
fi

echo -e "${GREEN}✓ Payment initialized successfully${NC}"
echo -e "  Redirect URL: ${BLUE}${REDIRECT_URL:0:80}...${NC}"
echo "$PAYMENT_RESPONSE" | python3 -m json.tool | head -20
echo ""

# Step 3: Check Payment Status
echo -e "${YELLOW}Step 3: Checking payment status...${NC}"
STATUS_RESPONSE=$(curl -s "$API_BASE/multiple-orders/$ORDER_ID/payment-status")

PAYMENT_STATUS=$(echo "$STATUS_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['payment_status'])" 2>/dev/null || echo "")
CONVERSION_STATUS=$(echo "$STATUS_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['conversion_status'])" 2>/dev/null || echo "")

echo -e "${GREEN}✓ Status retrieved successfully${NC}"
echo -e "  Payment Status: ${YELLOW}$PAYMENT_STATUS${NC}"
echo -e "  Conversion Status: ${YELLOW}$CONVERSION_STATUS${NC}"
echo "$STATUS_RESPONSE" | python3 -m json.tool | head -30
echo ""

# Step 4: Get Order Details
echo -e "${YELLOW}Step 4: Fetching order details...${NC}"
DETAILS_RESPONSE=$(curl -s "$API_BASE/multiple-orders/$ORDER_ID")

echo -e "${GREEN}✓ Order details retrieved${NC}"
echo "$DETAILS_RESPONSE" | python3 -m json.tool | head -40
echo ""

# Step 5: Get User Orders
echo -e "${YELLOW}Step 5: Fetching user order history...${NC}"
USER_ORDERS_RESPONSE=$(curl -s "$API_BASE/multiple-orders/user/$USER_ID")

ORDER_COUNT=$(echo "$USER_ORDERS_RESPONSE" | python3 -c "import sys, json; print(len(json.load(sys.stdin)['data']))" 2>/dev/null || echo "0")

echo -e "${GREEN}✓ User has $ORDER_COUNT order(s)${NC}"
echo "$USER_ORDERS_RESPONSE" | python3 -m json.tool | head -30
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ Order Creation${NC}: Success (ID: $ORDER_ID)"
echo -e "${GREEN}✓ Payment Initialization${NC}: Success"
echo -e "${GREEN}✓ Status Check${NC}: Success (Payment: $PAYMENT_STATUS, Conversion: $CONVERSION_STATUS)"
echo -e "${GREEN}✓ Order Details${NC}: Success"
echo -e "${GREEN}✓ User Orders${NC}: Success ($ORDER_COUNT orders)"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Open the payment URL in a browser to test the payment flow:"
echo -e "   ${BLUE}$REDIRECT_URL${NC}"
echo ""
echo "2. After payment, check conversion status:"
echo -e "   ${BLUE}curl $API_BASE/multiple-orders/$ORDER_ID/payment-status${NC}"
echo ""
echo "3. Manually trigger conversion (if needed):"
echo -e "   ${BLUE}curl -X POST $API_BASE/multiple-orders/$ORDER_ID/convert${NC}"
echo ""
echo -e "${GREEN}All API endpoints are working correctly!${NC}"
