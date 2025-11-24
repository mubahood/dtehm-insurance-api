# API Testing Quick Reference

**Base URL:** `http://localhost:8888/dtehm-insurance-api/api`  
**Date:** January 30, 2025

---

## Test 1: Register User with Membership

```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/users/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "phone_number": "+256700000000",
    "address": "Kampala, Uganda",
    "sponsor_id": "DTEHM20250001",
    "is_dtehm_member": "Yes",
    "is_dip_member": "Yes",
    "from_mobile": "yes"
  }'
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Account created successfully. Please complete membership payment to activate your account.",
  "data": {
    "user": { "id": 123, "name": "Test User", ... },
    "membership_payment": {
      "required": true,
      "amount": 96000,
      "types": ["DTEHM", "DIP"],
      "breakdown": { "dtehm": 76000, "dip": 20000 },
      "status": "pending"
    }
  }
}
```

---

## Test 2: Commission Calculation (Existing Endpoint)

```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/ajax/calculate-commissions" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002"
  }'
```

**Expected Response:**
```json
{
  "product": { "id": 1, "name": "Premium Laptop", "price": 850000 },
  "sponsor": { "id": 10, "name": "Jane Sponsor", "member_id": "DTEHM20250001" },
  "stockist": { "id": 20, "name": "John Stockist", "member_id": "DTEHM20250002" },
  "commissions": {
    "stockist": { "level": "Stockist", "rate": 8, "amount": 68000, ... },
    "gn1": { "level": "GN1", "rate": 3, "amount": 25500, ... },
    ...
  },
  "total_commission": 153000,
  "balance": 697000,
  "commission_percentage": 18
}
```

---

## Test 3: Get Products List

```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/products/list?page=1&per_page=10"
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Products retrieved successfully",
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Premium Laptop",
        "price": 850000,
        "feature_photo": "https://...",
        "category_name": "Electronics",
        "stock_quantity": 50,
        "in_stock": true
      }
    ],
    "pagination": {
      "total": 100,
      "per_page": 10,
      "current_page": 1,
      "last_page": 10
    }
  }
}
```

---

## Test 4: Get Product Details

```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/products/detail/1"
```

---

## Test 5: Calculate Commission (New Mobile Endpoint - Requires Auth)

```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/orders/calculate-commission" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002"
  }'
```

---

## Test 6: Login (Get Token)

```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/users/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Save the token from response for authenticated requests!**

---

## Test 7: Get User Balance (Requires Auth)

```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/user/balance" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

---

## Test 8: Get User Commissions (Requires Auth)

```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/user/commissions?page=1&per_page=20&commission_type=all" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

---

## Test 9: Get User Network (Requires Auth)

```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/user/network?page=1&per_page=20&level=all" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

---

## Test 10: Create Order (Requires Auth)

```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/orders/create" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002",
    "payment_method": "pesapal",
    "callback_url": "dtehm://payment-callback"
  }'
```

---

## Admin Panel Testing

### Access Admin Panel:
```
http://localhost:8888/dtehm-insurance-api/admin
```

### Test OrderedItem Form:
1. Navigate to "Ordered Items"
2. Click "New"
3. Select a product
4. Enter Sponsor ID (e.g., "DTEHM20250001")
5. Enter Stockist ID (e.g., "DTEHM20250002")
6. Watch commission calculation appear automatically
7. Verify commission breakdown shows all levels
8. Submit form

### Test OrderedItem Details:
1. Navigate to "Ordered Items"
2. Click on any row
3. View custom details page
4. Verify:
   - Sale summary
   - Product details
   - Sponsor & Stockist cards
   - Commission breakdown table
   - Total commission and balance

---

## Database Queries for Verification

```sql
-- Check if user was created
SELECT id, name, email, is_dtehm_member, is_dip_member, sponsor_id 
FROM admin_users 
ORDER BY id DESC LIMIT 5;

-- Check membership payment records
SELECT * FROM membership_payments ORDER BY id DESC LIMIT 5;

-- Check DTEHM memberships
SELECT * FROM dtehm_memberships ORDER BY id DESC LIMIT 5;

-- Check ordered items with commission fields
SELECT id, product, sponsor_id, stockist_id, sponsor_user_id, stockist_user_id, amount 
FROM ordered_items 
ORDER BY id DESC LIMIT 5;

-- Check account transactions (commissions)
SELECT id, user_id, type, amount, description, status, created_at 
FROM account_transactions 
WHERE type = 'commission' 
ORDER BY id DESC LIMIT 10;
```

---

## Expected Member ID Formats

- **DTEHM Member ID:** `DTEHM2025XXXX` (e.g., DTEHM20250001, DTEHM20250002)
- **DIP Member ID:** `DIPXXXX` (e.g., DIP0001, DIP0086)

Both formats are searchable as sponsor_id in the system.

---

## Common HTTP Status Codes

- `200` - Success
- `400` - Bad Request / Validation Error
- `401` - Unauthorized (Missing or invalid token)
- `404` - Resource Not Found
- `500` - Server Error

---

## JWT Token Usage

After login or registration, you'll receive a token. Use it in subsequent requests:

```bash
-H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

Token is also automatically stored in mobile app after successful registration/login.

---

**Quick Test Sequence:**

1. ✅ Test registration with membership
2. ✅ Test login to get token
3. ✅ Test product list (no auth needed)
4. ✅ Test commission calculation (with auth)
5. ✅ Test order creation (with auth)
6. ✅ Test get user balance (with auth)
7. ✅ Test admin panel OrderedItem form
8. ✅ Verify database records

---

**All endpoints ready for testing!**  
**Documentation:** See `MOBILE_APP_API_DOCUMENTATION.md` for full details
