# Product Purchase Backend - API Testing Guide

## Backend Implementation Complete ✅

### Migration Status
- ✅ Database migration executed successfully
- ✅ `universal_payment_id` column added to `ordered_items` table
- ✅ Foreign key constraint established

### Files Created/Updated

#### 1. ProductPurchaseController.php
**Location:** `/app/Http/Controllers/ProductPurchaseController.php`

**Features:**
- ✅ Initialize product purchase (validate product, sponsor, stockist, create payment)
- ✅ Confirm payment (verify Pesapal status, create OrderedItem)
- ✅ Pesapal IPN handler (automatic payment confirmation webhook)
- ✅ Pesapal callback (redirect after payment)
- ✅ Purchase history (get user's paid orders)
- ✅ Purchase details (get single order details)

**Key Validations:**
- Product stock availability check
- Sponsor/Stockist must be active DTEHM members
- Atomic transaction (payment verification + OrderedItem creation)
- Stock quantity reduction
- Automatic commission processing (via OrderedItem observer)

#### 2. API Routes
**Location:** `/routes/api.php`

```php
// Product Purchase Routes
POST   /api/product-purchase/initialize
POST   /api/product-purchase/confirm
GET    /api/product-purchase/history
GET    /api/product-purchase/{id}

// Pesapal Handlers
POST   /api/product-purchase/pesapal/ipn
GET    /api/product-purchase/pesapal/callback
POST   /api/product-purchase/pesapal/callback
```

#### 3. Model Updates
- ✅ **UniversalPayment.php** - Added `orderedItems()` relationship and `isProductPurchase()` helper
- ✅ **OrderedItem.php** - Added `payment()` and `user()` relationships

#### 4. Success View
**Location:** `/resources/views/pesapal-callback-success.blade.php`
- Beautiful animated success page
- Shows order tracking ID
- Auto-closes after 10 seconds

---

## API Endpoint Documentation

### 1. Initialize Product Purchase

**Endpoint:** `POST /api/product-purchase/initialize`

**Purpose:** Start a new product purchase, create pending payment, initialize Pesapal gateway

**Headers:**
```json
{
  "User-Id": "123",
  "Content-Type": "application/json"
}
```

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 1,
  "sponsor_id": "DTEHM20250001",
  "stockist_id": "DTEHM20250002",
  "user_id": 123,
  "callback_url": "optional_custom_callback"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Product purchase initialized successfully",
  "data": {
    "payment": {
      "id": 456,
      "payment_reference": "PAY_1234567890",
      "amount": 50000,
      "currency": "UGX",
      "status": "PROCESSING"
    },
    "product": {
      "id": 1,
      "name": "DTEHM Product Name",
      "quantity": 1,
      "unit_price": 50000,
      "total": 50000
    },
    "pesapal": {
      "order_tracking_id": "xxx-xxx-xxx-xxx",
      "redirect_url": "https://pay.pesapal.com/iframe/xxx",
      "merchant_reference": "PRODUCT_456_1234567890"
    }
  }
}
```

**Error Responses:**

*Validation Error (422):*
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "product_id": ["The product id field is required."]
  }
}
```

*Insufficient Stock (400):*
```json
{
  "success": false,
  "message": "Insufficient stock. Available: 5, Requested: 10"
}
```

*Invalid Sponsor/Stockist (404):*
```json
{
  "success": false,
  "message": "Sponsor not found. Please verify the Sponsor ID."
}
```

*Not a DTEHM Member (400):*
```json
{
  "success": false,
  "message": "Sponsor must be an active DTEHM member"
}
```

*Pesapal Initialization Failed (500):*
```json
{
  "success": false,
  "message": "Failed to initialize payment gateway",
  "error": "API connection error"
}
```

---

### 2. Confirm Product Purchase

**Endpoint:** `POST /api/product-purchase/confirm`

**Purpose:** Verify payment status with Pesapal and create OrderedItem if successful

**Headers:**
```json
{
  "Content-Type": "application/json"
}
```

**Request Body:**
```json
{
  "payment_id": 456
}
```

OR

```json
{
  "order_tracking_id": "xxx-xxx-xxx-xxx"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product purchase confirmed successfully",
  "data": {
    "payment": {
      "id": 456,
      "payment_reference": "PAY_1234567890",
      "status": "COMPLETED",
      "amount": 50000,
      "items_processed": true
    },
    "ordered_items": [
      {
        "ordered_item_id": 789,
        "product_id": 1,
        "product_name": "DTEHM Product Name",
        "quantity": 1,
        "amount": 50000
      }
    ]
  }
}
```

**Error Responses:**

*Payment Not Found (404):*
```json
{
  "success": false,
  "message": "Payment record not found"
}
```

*Payment Not Completed (400):*
```json
{
  "success": false,
  "message": "Payment not completed. Status: Pending",
  "data": {
    "payment_status": "Pending",
    "payment": {...}
  }
}
```

*Already Processed (200):*
```json
{
  "success": true,
  "message": "Payment already processed",
  "data": {
    "payment": {...},
    "already_processed": true
  }
}
```

---

### 3. Pesapal IPN Handler

**Endpoint:** `POST /api/product-purchase/pesapal/ipn`

**Purpose:** Webhook endpoint for Pesapal to notify payment status changes

**Request Body (from Pesapal):**
```json
{
  "OrderTrackingId": "xxx-xxx-xxx-xxx",
  "OrderMerchantReference": "PRODUCT_456_1234567890"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "IPN processed"
}
```

---

### 4. Pesapal Callback Handler

**Endpoint:** `GET /api/product-purchase/pesapal/callback`

**Purpose:** Redirect page after user completes payment on Pesapal

**Query Parameters:**
```
?OrderTrackingId=xxx-xxx-xxx-xxx
&OrderMerchantReference=PRODUCT_456_1234567890
```

**Response:**
- HTML success page with animated checkmark
- Shows order tracking ID
- Auto-closes after 10 seconds

---

### 5. Purchase History

**Endpoint:** `GET /api/product-purchase/history`

**Purpose:** Get all product purchases for the authenticated user

**Headers:**
```json
{
  "User-Id": "123"
}
```

**Query Parameters:**
```
?per_page=20
&page=1
```

**Success Response (200):**
```json
{
  "code": 1,
  "message": "Purchase history retrieved successfully",
  "data": {
    "purchases": [
      {
        "id": 789,
        "order_number": "PROD_456_1",
        "product": {
          "id": 1,
          "name": "DTEHM Product Name",
          "image": "https://..."
        },
        "quantity": 1,
        "unit_price": 50000,
        "total_amount": 50000,
        "sponsor_id": "DTEHM20250001",
        "stockist_id": "DTEHM20250002",
        "payment_status": "PAID",
        "paid_at": "2025-12-15 10:30:00",
        "created_at": "2025-12-15 10:25:00"
      }
    ],
    "pagination": {
      "total": 15,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 6. Purchase Details

**Endpoint:** `GET /api/product-purchase/{id}`

**Purpose:** Get detailed information about a specific purchase

**Success Response (200):**
```json
{
  "code": 1,
  "message": "Purchase details retrieved successfully",
  "data": {
    "id": 789,
    "order_number": "PROD_456_1",
    "product": {
      "id": 1,
      "name": "DTEHM Product Name",
      "description": "Product details...",
      "image": "https://..."
    },
    "quantity": 1,
    "unit_price": 50000,
    "total_amount": 50000,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002",
    "payment": {
      "id": 456,
      "reference": "PAY_1234567890",
      "status": "COMPLETED",
      "payment_method": "mobile_money"
    },
    "commission": {
      "stockist": 5000,
      "seller": 3000,
      "processed": true
    },
    "payment_status": "PAID",
    "paid_at": "2025-12-15 10:30:00",
    "created_at": "2025-12-15 10:25:00"
  }
}
```

---

## Testing Checklist

### Backend API Tests

#### Initialize Purchase
- [ ] Test with valid product, sponsor, stockist
- [ ] Test with invalid product ID (404 error)
- [ ] Test with insufficient stock (400 error)
- [ ] Test with non-DTEHM sponsor (400 error)
- [ ] Test with non-DTEHM stockist (400 error)
- [ ] Test with missing User-Id header (401 error)
- [ ] Verify UniversalPayment record created
- [ ] Verify Pesapal redirect URL returned

#### Confirm Purchase
- [ ] Test with valid payment_id after successful Pesapal payment
- [ ] Test with order_tracking_id
- [ ] Test with already processed payment (idempotency)
- [ ] Test with pending payment (400 error)
- [ ] Verify OrderedItem created
- [ ] Verify product stock decreased
- [ ] Verify commission auto-processed
- [ ] Verify UniversalPayment marked as processed

#### Purchase History
- [ ] Test with valid User-Id
- [ ] Test pagination
- [ ] Test with user who has no purchases
- [ ] Verify only paid items returned

#### Purchase Details
- [ ] Test with valid ordered_item ID
- [ ] Test with invalid ID (404 error)
- [ ] Verify all details returned correctly

### Integration Tests

#### Full Purchase Flow
1. [ ] Call `/initialize` → Get redirect URL
2. [ ] (Manual) Complete payment on Pesapal
3. [ ] Pesapal sends IPN to `/pesapal/ipn`
4. [ ] Verify OrderedItem created automatically
5. [ ] Call `/history` → Verify order appears
6. [ ] Call `/details/{id}` → Verify all data correct

#### Edge Cases
- [ ] Duplicate IPN calls (should be idempotent)
- [ ] Multiple users buying same product simultaneously
- [ ] Product stock reaching zero
- [ ] Pesapal API timeout/failure handling
- [ ] Invalid sponsor/stockist combinations

---

## Manual Testing with cURL

### 1. Initialize Purchase

```bash
curl -X POST http://localhost/api/product-purchase/initialize \
  -H "Content-Type: application/json" \
  -H "User-Id: 123" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002",
    "user_id": 123
  }'
```

### 2. Confirm Purchase

```bash
curl -X POST http://localhost/api/product-purchase/confirm \
  -H "Content-Type: application/json" \
  -d '{
    "payment_id": 456
  }'
```

### 3. Get Purchase History

```bash
curl -X GET "http://localhost/api/product-purchase/history?per_page=10&page=1" \
  -H "User-Id: 123"
```

### 4. Get Purchase Details

```bash
curl -X GET http://localhost/api/product-purchase/789 \
  -H "Content-Type: application/json"
```

---

## Next Steps

### Backend (Remaining)
1. [ ] Test all endpoints with real data
2. [ ] Verify Pesapal integration works end-to-end
3. [ ] Test commission calculation accuracy
4. [ ] Add additional error handling if needed
5. [ ] Performance testing with concurrent purchases

### Flutter Mobile App (Next Phase)
1. Create `ProductPurchaseService` (API client)
2. Create UI screens:
   - Product purchase screen
   - Pesapal WebView screen
   - Purchase success screen
   - Purchase history screen
3. Implement WebView for Pesapal redirect
4. Handle deep links for callback
5. Add local caching for purchase history
6. Integrate with existing product listing
7. Add push notifications for payment confirmation

---

## Security Notes

✅ **Implemented:**
- User-Id header validation
- Product stock validation
- DTEHM member verification
- Idempotent payment processing
- Atomic transactions (payment + order creation)
- Detailed audit logging

⚠️ **Recommendations:**
- Add rate limiting to prevent abuse
- Add user authentication middleware (JWT)
- Implement CSRF protection for web callbacks
- Add IP whitelist for Pesapal IPN
- Monitor for duplicate payment attempts

---

## Database Changes

### ordered_items table
```sql
ALTER TABLE ordered_items 
ADD COLUMN universal_payment_id BIGINT UNSIGNED NULL,
ADD INDEX idx_universal_payment_id (universal_payment_id),
ADD FOREIGN KEY fk_ordered_items_universal_payment (universal_payment_id) 
  REFERENCES universal_payments(id) ON DELETE SET NULL;
```

✅ **Migration executed successfully**

---

## Logs to Monitor

```php
// Initialization
'Product purchase initialized'
'Pesapal: Initializing product purchase payment'

// Confirmation
'Product purchase confirmed successfully'
'OrderedItem created for product purchase'
'Product purchase processed successfully'

// IPN
'Pesapal IPN received for product purchase'

// Errors
'Product purchase initialization failed'
'Pesapal initialization failed for product purchase'
'Failed to process product purchase'
```

---

## Commission Processing

The commission system is **automatically triggered** when an OrderedItem is created:

1. OrderedItem observer (`app/Observers/OrderedItemObserver.php`) detects new record
2. Validates sponsor and stockist are DTEHM members
3. Processes commissions:
   - Stockist commission
   - Sponsor (seller) commission
   - 10-level parent hierarchy commissions
4. All commissions saved to database
5. User balances updated

**No manual commission processing needed!**

---

## Status: Backend Implementation Complete ✅

All backend endpoints are ready for testing and mobile app integration.
