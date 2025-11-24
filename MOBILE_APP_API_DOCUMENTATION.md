# Mobile App API Documentation

**Base URL:** `{APP_URL}/api`

**Date:** 2025-01-30

---

## Authentication

All endpoints marked with 🔒 require authentication token in header:
```
Authorization: Bearer {token}
```

---

## 1. User Registration & Authentication

### 1.1 Register New User
**POST** `/users/register`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe",
  "phone_number": "+256700000000",
  "address": "Kampala, Uganda",
  "sponsor_id": "DTEHM20250001",  // Required for mobile (from_mobile=yes)
  "is_dtehm_member": "Yes",        // Yes/No
  "is_dip_member": "Yes",          // Yes/No
  "from_mobile": "yes"             // Enforces sponsor requirement
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Account created successfully. Please complete membership payment to activate your account.",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "user@example.com",
      "phone_number": "+256700000000",
      "is_dtehm_member": "Yes",
      "is_dip_member": "Yes",
      "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      ...
    },
    "membership_payment": {
      "required": true,
      "amount": 96000,
      "types": ["DTEHM", "DIP"],
      "breakdown": {
        "dtehm": 76000,
        "dip": 20000
      },
      "status": "pending"
    }
  }
}
```

### 1.2 Login
**POST** `/users/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

## 2. Membership Payment

### 2.1 Initiate Membership Payment
**POST** `/membership/initiate-payment`

**Request:**
```json
{
  "user_id": 123,
  "payment_method": "pesapal",  // pesapal, mobile_money, bank
  "callback_url": "myapp://payment-callback"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Payment initialized successfully",
  "data": {
    "payment_id": 456,
    "amount": 96000,
    "membership_types": ["DTEHM", "DIP"],
    "payment_url": "https://pay.pesapal.com/iframe/...",
    "tracking_id": "PESAPAL-123456"
  }
}
```

### 2.2 Confirm Membership Payment
**POST** `/membership/confirm-payment`

**Request:**
```json
{
  "payment_id": 456,
  "transaction_reference": "PESAPAL-123456",
  "status": "success"  // success or failed
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Payment confirmed successfully. Your account is now active.",
  "data": {
    "user": { ... },
    "dtehm_member_id": "DTEHM20250123",
    "dip_member_id": "DIP0456",
    "membership_status": "Active",
    "sponsor_commission_created": true
  }
}
```

### 2.3 Check Payment Status
**GET** `/membership/payment-status/{payment_id}`

**Response:**
```json
{
  "code": 1,
  "data": {
    "payment_id": 456,
    "amount": 96000,
    "status": "completed",  // pending, completed, failed
    "membership_types": ["DTEHM", "DIP"],
    "transaction_reference": "PESAPAL-123456",
    "created_at": "2025-01-30 10:00:00",
    "paid_at": "2025-01-30 10:05:00"
  }
}
```

---

## 3. Products

### 3.1 Get Product List
**GET** `/products/list?page=1&per_page=20&category_id=1&search=laptop&sort_by=price&sort_order=asc`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `category_id` - Filter by category
- `search` - Search in name/description
- `sort_by` - Field to sort by (name, price, created_at)
- `sort_order` - asc or desc

**Response:**
```json
{
  "code": 1,
  "message": "Products retrieved successfully",
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Premium Laptop",
        "description": "High-performance laptop...",
        "price": 850000,
        "feature_photo": "https://...",
        "category_id": 1,
        "category_name": "Electronics",
        "stock_quantity": 50,
        "in_stock": true,
        "created_at": "2025-01-15 08:00:00"
      }
    ],
    "pagination": {
      "total": 100,
      "per_page": 20,
      "current_page": 1,
      "last_page": 5,
      "from": 1,
      "to": 20
    }
  }
}
```

### 3.2 Get Product Details
**GET** `/products/detail/{id}`

**Response:**
```json
{
  "code": 1,
  "data": {
    "id": 1,
    "name": "Premium Laptop",
    "description": "High-performance laptop...",
    "price": 850000,
    "feature_photo": "https://...",
    "category_id": 1,
    "category_name": "Electronics",
    "stock_quantity": 50,
    "in_stock": true,
    "specifications": "Processor: Intel i7...",
    "images": [
      "https://...",
      "https://..."
    ],
    "created_at": "2025-01-15 08:00:00",
    "related_products": [
      {
        "id": 2,
        "name": "Laptop Bag",
        "price": 45000,
        "feature_photo": "https://..."
      }
    ]
  }
}
```

### 3.3 Get Product Categories
**GET** `/products/categories`

**Response:**
```json
{
  "code": 1,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "description": "Electronic devices and accessories",
      "product_count": 150
    }
  ]
}
```

---

## 4. Orders 🔒

All order endpoints require authentication.

### 4.1 Calculate Commission Preview
**POST** `/orders/calculate-commission`

**Request:**
```json
{
  "product_id": 1,
  "quantity": 1,
  "sponsor_id": "DTEHM20250001",
  "stockist_id": "DTEHM20250002"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Commission calculated successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "Premium Laptop",
      "price": 850000,
      "quantity": 1,
      "total": 850000
    },
    "sponsor": {
      "id": 10,
      "name": "Jane Sponsor",
      "member_id": "DTEHM20250001"
    },
    "stockist": {
      "id": 20,
      "name": "John Stockist",
      "member_id": "DTEHM20250002"
    },
    "commissions": [
      {
        "level": "Stockist",
        "rate": 8,
        "amount": 68000,
        "member": {
          "id": 20,
          "name": "John Stockist",
          "member_id": "DTEHM20250002"
        }
      },
      {
        "level": "GN1",
        "rate": 3,
        "amount": 25500,
        "member": {
          "id": 10,
          "name": "Jane Sponsor",
          "member_id": "DTEHM20250001"
        }
      },
      {
        "level": "GN2",
        "rate": 2.5,
        "amount": 21250,
        "member": { ... }
      }
      // ... GN3 to GN10
    ],
    "summary": {
      "product_price": 850000,
      "stockist_commission": 68000,
      "network_commission": 85000,
      "total_commission": 153000,
      "balance": 697000,
      "commission_percentage": 18
    }
  }
}
```

### 4.2 Create Order
**POST** `/orders/create`

**Request:**
```json
{
  "product_id": 1,
  "quantity": 1,
  "sponsor_id": "DTEHM20250001",
  "stockist_id": "DTEHM20250002",
  "payment_method": "pesapal",
  "callback_url": "myapp://order-payment-callback"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Order created successfully. Please complete payment.",
  "data": {
    "order_id": 789,
    "ordered_item_id": 890,
    "total_amount": 850000,
    "payment_url": "https://pay.pesapal.com/iframe/...",
    "tracking_id": "PESAPAL-789012"
  }
}
```

### 4.3 Confirm Order Payment
**POST** `/orders/confirm-payment`

**Request:**
```json
{
  "order_id": 789,
  "transaction_reference": "PESAPAL-789012",
  "status": "success"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Payment confirmed successfully. Commissions have been distributed.",
  "data": {
    "order_id": 789,
    "status": "Paid",
    "commissions_created": 11,
    "total_commission": 153000
  }
}
```

### 4.4 Get My Orders
**GET** `/orders/my-orders?page=1&per_page=20&status=Paid`

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `status` - Filter by status (Pending Payment, Paid, Payment Failed)

**Response:**
```json
{
  "code": 1,
  "data": {
    "orders": [
      {
        "id": 789,
        "order_date": "2025-01-30 14:00:00",
        "total_amount": 850000,
        "status": "Paid",
        "payment_method": "pesapal",
        "items": [
          {
            "product_name": "Premium Laptop",
            "product_image": "https://...",
            "quantity": 1,
            "amount": 850000
          }
        ]
      }
    ],
    "pagination": {
      "total": 10,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

### 4.5 Get Order Details
**GET** `/orders/detail/{id}`

**Response:**
```json
{
  "code": 1,
  "data": {
    "id": 789,
    "order_date": "2025-01-30 14:00:00",
    "total_amount": 850000,
    "status": "Paid",
    "payment_method": "pesapal",
    "payment_reference": "PESAPAL-789012",
    "customer_name": "John Doe",
    "customer_phone": "+256700000000",
    "customer_address": "Kampala, Uganda",
    "items": [
      {
        "id": 890,
        "product": {
          "id": 1,
          "name": "Premium Laptop",
          "image": "https://...",
          "price": 850000
        },
        "quantity": 1,
        "amount": 850000,
        "sponsor": {
          "name": "Jane Sponsor",
          "member_id": "DTEHM20250001"
        },
        "stockist": {
          "name": "John Stockist",
          "member_id": "DTEHM20250002"
        }
      }
    ]
  }
}
```

---

## 5. Commissions & Balance 🔒

### 5.1 Get User Commissions
**GET** `/user/commissions?page=1&per_page=20&commission_type=all&start_date=2025-01-01&end_date=2025-01-31`

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `commission_type` - Filter: all, stockist, network, membership
- `start_date` - Start date (YYYY-MM-DD)
- `end_date` - End date (YYYY-MM-DD)

**Response:**
```json
{
  "code": 1,
  "data": {
    "commissions": [
      {
        "id": 123,
        "amount": 68000,
        "level": "Stockist",
        "description": "Stockist Commission from Order #789",
        "order_id": "789",
        "reference_type": "order",
        "reference_id": 890,
        "date": "2025-01-30 14:05:00",
        "status": "completed"
      },
      {
        "id": 124,
        "amount": 25500,
        "level": "GN1",
        "description": "GN1 Commission from Order #789",
        "order_id": "789",
        "reference_type": "order",
        "reference_id": 890,
        "date": "2025-01-30 14:05:00",
        "status": "completed"
      },
      {
        "id": 125,
        "amount": 10000,
        "level": "Membership Referral",
        "description": "DTEHM Membership Referral Commission for John Doe",
        "order_id": null,
        "reference_type": "membership",
        "reference_id": 456,
        "date": "2025-01-30 10:05:00",
        "status": "completed"
      }
    ],
    "summary": {
      "total_commissions": 103500,
      "current_balance": 103500,
      "currency": "UGX"
    },
    "pagination": {
      "total": 3,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

### 5.2 Get User Balance
**GET** `/user/balance`

**Response:**
```json
{
  "code": 1,
  "data": {
    "balance": 103500,
    "currency": "UGX"
  }
}
```

---

## 6. Network/Downline 🔒

### 6.1 Get User Network
**GET** `/user/network?page=1&per_page=20&level=all`

**Query Parameters:**
- `page` - Page number
- `per_page` - Items per page
- `level` - Filter: all, direct, 1, 2, 3, ..., 10

**Response:**
```json
{
  "code": 1,
  "data": {
    "network": [
      {
        "id": 50,
        "name": "Alice Member",
        "email": "alice@example.com",
        "phone": "+256700111111",
        "dtehm_member_id": "DTEHM20250050",
        "dip_member_id": "DIP0050",
        "is_dtehm_member": "Yes",
        "is_dip_member": "No",
        "level": 1,
        "joined_at": "2025-01-25 10:00:00",
        "status": "Active"
      },
      {
        "id": 51,
        "name": "Bob Member",
        "email": "bob@example.com",
        "phone": "+256700222222",
        "dtehm_member_id": "DTEHM20250051",
        "dip_member_id": null,
        "is_dtehm_member": "Yes",
        "is_dip_member": "No",
        "level": 2,
        "joined_at": "2025-01-26 15:30:00",
        "status": "Active"
      }
    ],
    "statistics": {
      "total_members": 25,
      "direct_referrals": 5,
      "dtehm_members": 20,
      "dip_members": 15,
      "levels_deep": 6,
      "by_level": {
        "level_1": 5,
        "level_2": 8,
        "level_3": 6,
        "level_4": 3,
        "level_5": 2,
        "level_6": 1
      }
    },
    "pagination": {
      "total": 25,
      "per_page": 20,
      "current_page": 1,
      "last_page": 2,
      "from": 1,
      "to": 20
    }
  }
}
```

---

## Commission Rates

| Level | Rate | Description |
|-------|------|-------------|
| Stockist | 8% | Direct stockist commission |
| GN1 | 3% | Generation 1 (Sponsor) |
| GN2 | 2.5% | Generation 2 |
| GN3 | 2% | Generation 3 |
| GN4 | 1.5% | Generation 4 |
| GN5 | 1% | Generation 5 |
| GN6 | 0.8% | Generation 6 |
| GN7 | 0.6% | Generation 7 |
| GN8 | 0.5% | Generation 8 |
| GN9 | 0.4% | Generation 9 |
| GN10 | 0.2% | Generation 10 |
| **Total Network** | **12%** | Sum of GN1-GN10 |
| **Total Commission** | **20%** | Stockist + Network (max) |

**Example:** Product price UGX 850,000
- Stockist: 68,000 (8%)
- Network: 85,000 (12%) distributed across GN1-GN10
- Total: 153,000 (18% if all 10 levels exist)
- Balance: 697,000 (82%)

---

## Membership Fees

| Membership Type | Fee (UGX) | Benefits |
|----------------|-----------|----------|
| DTEHM | 76,000 | Full network marketing privileges |
| DIP | 20,000 | Basic membership |
| Both | 96,000 | Complete package |

**Sponsor Commission:** 10,000 UGX for each DTEHM referral

---

## Error Responses

All errors follow this format:

```json
{
  "code": 0,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request / Validation Error
- `401` - Unauthorized (Missing or invalid token)
- `404` - Resource Not Found
- `500` - Server Error

---

## Testing with cURL

### Register User
```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/users/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "name": "Test User",
    "phone_number": "+256700000000",
    "sponsor_id": "DTEHM20250001",
    "is_dtehm_member": "Yes",
    "is_dip_member": "Yes",
    "from_mobile": "yes"
  }'
```

### Get Products
```bash
curl -X GET "http://localhost:8888/dtehm-insurance-api/api/products/list?page=1&per_page=10"
```

### Calculate Commission
```bash
curl -X POST "http://localhost:8888/dtehm-insurance-api/api/orders/calculate-commission" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002"
  }'
```

---

**Last Updated:** 2025-01-30  
**Version:** 1.0  
**Contact:** Development Team
