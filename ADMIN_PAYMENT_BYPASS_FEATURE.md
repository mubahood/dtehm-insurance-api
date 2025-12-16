# Admin Payment Bypass Feature - Product Purchase

## Overview
This feature allows admin and manager users to bypass the payment gateway when purchasing products on behalf of customers who have already paid via cash, bank transfer, or other offline methods.

## Implementation Date
December 16, 2025

## How It Works

### For Admin Users
1. When an admin user accesses the product purchase screen, they see an additional section: **"Admin: Product Already Paid?"**
2. The admin can select one of two options:
   - **Yes - Already Paid**: Creates the sale record immediately without going through Pesapal
   - **No - Need Payment**: Proceeds with normal payment gateway flow

### For Regular Users
- Regular users do not see the admin bypass section
- They always go through the normal payment gateway flow

## User Role Check
```dart
// In mobile app (Flutter)
if (_currentUser.isAdmin()) {
  // Show admin bypass option
}

// isAdmin() returns true if user has 'admin' or 'manager' role
```

## Database Changes

### Migration
**File**: `2025_12_16_192317_add_admin_payment_bypass_to_universal_payments_table.php`

**New Columns in `universal_payments` table**:
- `paid_by_admin` (boolean, default: false) - Indicates if payment was bypassed by admin
- `admin_payment_note` (text, nullable) - Optional note from admin about the payment
- `marked_paid_by` (bigint, nullable) - User ID of admin who marked it as paid
- `marked_paid_at` (timestamp, nullable) - When admin marked it as paid

## Backend Changes

### ProductPurchaseController

**New Request Parameters** (`POST /api/product-purchase/initialize`):
```json
{
  "product_id": 1,
  "quantity": 1,
  "sponsor_id": "DTEHM20250001",
  "stockist_id": "DTEHM20250002",
  "user_id": 123,
  "is_paid_by_admin": true,
  "admin_payment_note": "Payment already received via bank transfer"
}
```

**Flow Logic**:
1. If `is_paid_by_admin` is `true`:
   - Creates UniversalPayment with status `COMPLETED`
   - Sets `payment_gateway` to `admin_bypass`
   - Sets `payment_method` to `cash_or_other`
   - Immediately calls `processProductPurchase()` to create OrderedItem (sale record)
   - Returns success response with sale details
   
2. If `is_paid_by_admin` is `false` or not set:
   - Creates UniversalPayment with status `PENDING`
   - Initializes Pesapal payment gateway
   - Returns Pesapal redirect URL
   - Sale created after successful payment confirmation

## Mobile App Changes

### ProductPurchaseService

**Updated Method**:
```dart
Future<Map<String, dynamic>> initializePurchase({
  required int productId,
  required int quantity,
  required String sponsorId,
  required String stockistId,
  String? callbackUrl,
  bool isPaidByAdmin = false,         // NEW
  String? adminPaymentNote,           // NEW
})
```

### ProductPurchaseScreen

**New State Variables**:
```dart
LoggedInUserModel? _currentUser;
bool _isPaidByAdmin = false; // Default: No (not paid)
```

**UI Changes**:
- Admin section appears only when `_currentUser.isAdmin()` returns true
- Orange-bordered container with admin icon
- Two radio buttons for selection:
  - **Yes - Already Paid** → Creates sale directly
  - **No - Need Payment** → Normal payment flow
- Button text changes based on selection:
  - Admin bypass: "CREATE SALE (ADMIN BYPASS)"
  - Normal flow: "PROCEED TO PAYMENT"

## Response Format

### Admin Bypass Response (is_paid_by_admin = true)
```json
{
  "code": 1,
  "message": "Product purchase completed successfully (Admin Bypass)",
  "data": {
    "payment": {
      "id": 123,
      "payment_reference": "PAY-1234567890",
      "amount": 20000,
      "currency": "UGX",
      "status": "COMPLETED",
      "paid_by_admin": true
    },
    "product": {
      "id": 5,
      "name": "Premium Product",
      "quantity": 1,
      "unit_price": 20000,
      "total": 20000
    },
    "ordered_items": [
      {
        "ordered_item_id": 456,
        "product_id": 5,
        "product_name": "Premium Product",
        "quantity": 1,
        "amount": 20000
      }
    ],
    "admin_bypass": true
  }
}
```

### Normal Flow Response (is_paid_by_admin = false)
```json
{
  "code": 1,
  "message": "Product purchase initialized successfully",
  "data": {
    "payment": {
      "id": 123,
      "payment_reference": "PAY-1234567890",
      "amount": 20000,
      "currency": "UGX",
      "status": "PROCESSING"
    },
    "product": {
      "id": 5,
      "name": "Premium Product",
      "quantity": 1,
      "unit_price": 20000,
      "total": 20000
    },
    "pesapal": {
      "order_tracking_id": "xxx-xxx-xxx",
      "redirect_url": "https://pay.pesapal.com/...",
      "merchant_reference": "PRODUCT_123_1234567890"
    }
  }
}
```

## Security Considerations

1. **Role Verification**: The `isAdmin()` method checks for both 'admin' and 'manager' roles
2. **Backend Validation**: Backend should verify user permissions (future enhancement)
3. **Audit Trail**: All admin bypasses are logged with:
   - Who marked it as paid (`marked_paid_by`)
   - When it was marked (`marked_paid_at`)
   - Optional note (`admin_payment_note`)

## Testing Checklist

### Admin User Testing
- [ ] Admin sees the "Admin: Product Already Paid?" section
- [ ] Selecting "Yes - Already Paid" creates sale immediately
- [ ] Success dialog shows after admin bypass
- [ ] Sale appears in purchase history
- [ ] Database records show `paid_by_admin = 1`
- [ ] Selecting "No - Need Payment" goes through normal Pesapal flow

### Regular User Testing
- [ ] Regular user does NOT see admin bypass section
- [ ] Regular user always goes through Pesapal
- [ ] Regular user cannot bypass payment via API manipulation

### Database Verification
```sql
-- Check admin bypassed payments
SELECT 
    id,
    payment_reference,
    amount,
    status,
    paid_by_admin,
    admin_payment_note,
    marked_paid_by,
    marked_paid_at,
    payment_gateway
FROM universal_payments
WHERE paid_by_admin = 1
ORDER BY created_at DESC;
```

## Files Modified

### Backend (Laravel)
1. `database/migrations/2025_12_16_192317_add_admin_payment_bypass_to_universal_payments_table.php` - NEW
2. `app/Models/UniversalPayment.php` - Updated fillable and casts
3. `app/Http/Controllers/ProductPurchaseController.php` - Updated initialize() method

### Mobile (Flutter)
1. `lib/services/product_purchase_service.dart` - Added isPaidByAdmin parameter
2. `lib/screens/product_purchase_screen.dart` - Added admin UI and logic
3. `lib/models/LoggedInUserModel.dart` - Already has isAdmin() method

## Usage Example

### Admin Creating Sale for Cash Payment
1. Admin receives UGX 20,000 cash from customer
2. Admin opens product purchase screen
3. Selects product and enters quantity
4. Enters sponsor and stockist IDs
5. Selects "Yes - Already Paid" in admin section
6. Clicks "CREATE SALE (ADMIN BYPASS)"
7. Sale is created immediately
8. Success dialog confirms the purchase

## Future Enhancements
- [ ] Add backend permission check for admin bypass
- [ ] Add admin dashboard to view all bypassed payments
- [ ] Add ability to edit/update admin payment notes
- [ ] Add email notification when admin creates bypass sale
- [ ] Add report showing admin bypass statistics

## Support
For questions or issues, contact the development team.
