# Quick Testing Guide - Phone-Based Registration & Membership Payment

## Test Environment Setup
- Backend: http://localhost:8888/api
- Mobile: Run `flutter run` in dtehm-insurance directory

## Test Cases

### 1. Basic Registration (No Membership)
```bash
# API Test
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "phone_number": "0700111222",
    "address": "Kampala",
    "sponsor_id": "DTEHM20250001",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected: 
# - code: 1
# - username: "0700111222"
# - email: "0700111222@dtehm.app"
# - membership_payment.required: false
# - token returned
```

**Mobile Test:**
1. Open Register Screen
2. Enter: Name, Phone (0700111222), Address, Sponsor ID
3. Don't check membership boxes
4. Submit
5. Should redirect to MainScreen

### 2. DTEHM Membership Registration
```bash
# API Test
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "DTEHM Member",
    "phone_number": "0700222333",
    "sponsor_id": "DTEHM20250001",
    "is_dtehm_member": "Yes",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected:
# - membership_payment.required: true
# - membership_payment.amount: 76000
# - membership_payment.types: ["DTEHM"]
```

**Mobile Test:**
1. Open Register Screen
2. Enter details
3. ✅ Check "DTEHM Member" (76,000)
4. See total: UGX 76,000
5. Submit
6. Should redirect to MembershipPaymentScreen
7. Screen shows: "UGX 76,000"
8. Click "Pay Membership Fee"
9. Universal Payment screen opens
10. Shows "Membership - UGX 76,000"

### 3. DIP Membership Registration
```bash
# API Test
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "DIP Member",
    "phone_number": "0700333444",
    "sponsor_id": "DTEHM20250001",
    "is_dip_member": "Yes",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected:
# - membership_payment.amount: 20000
# - membership_payment.types: ["DIP"]
```

**Mobile Test:**
1. ✅ Check "DIP Member" only (20,000)
2. See total: UGX 20,000
3. Payment screen shows: "UGX 20,000"

### 4. Both Memberships
```bash
# API Test
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Full Member",
    "phone_number": "256700444555",
    "sponsor_id": "DTEHM20250001",
    "is_dtehm_member": "Yes",
    "is_dip_member": "Yes",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected:
# - membership_payment.amount: 96000
# - membership_payment.types: ["DTEHM", "DIP"]
# - breakdown: { "dtehm": 76000, "dip": 20000 }
```

**Mobile Test:**
1. ✅ Check both boxes
2. See total: UGX 96,000
3. MembershipPaymentScreen breakdown:
   - DTEHM Membership: UGX 76,000
   - DIP Membership: UGX 20,000
   - Total Amount: UGX 96,000

### 5. Invalid Phone Formats

#### Too Short (0 prefix)
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "070011122",
    "name": "Test",
    "password": "test123"
  }'

# Expected: "Invalid Uganda phone number. Use format: 0700000000"
```

#### Too Short (256 prefix)
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "25670011122",
    "name": "Test",
    "password": "test123"
  }'

# Expected: "Invalid Uganda phone number. Use format: 256700000000"
```

#### Wrong Prefix
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "123456789",
    "name": "Test",
    "password": "test123"
  }'

# Expected: "Phone number must start with 256 or 0"
```

**Mobile Test:**
- Enter: "070011122" → Error: "Invalid Uganda phone number"
- Enter: "123456" → Error: "Phone must start with 256 or 0"

### 6. Duplicate Phone
```bash
# First registration
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0700555666",
    "name": "First User",
    "sponsor_id": "DTEHM20250001",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Second with same phone
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0700555666",
    "name": "Second User",
    "sponsor_id": "DTEHM20250001",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected: "User with same Phone number already exists."
```

### 7. Invalid Sponsor
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0700666777",
    "name": "Test",
    "sponsor_id": "INVALID123",
    "password": "test123",
    "from_mobile": "yes"
  }'

# Expected: "Invalid Sponsor ID. Sponsor must be an existing member in the system."
```

### 8. Check Membership Status
```bash
# Get user token first from registration response
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X GET "http://localhost:8888/api/membership-check" \
  -H "Authorization: Bearer $TOKEN"

# Expected:
# {
#   "code": 1,
#   "data": {
#     "has_valid_membership": false,
#     "requires_payment": true,
#     "can_access_app": false
#   }
# }
```

## Visual Checks (Mobile)

### Flat Design Verification
✅ Registration Screen:
- All input fields have square corners
- No shadows on any containers
- No gradients in header (solid blue)
- Logo container is square (not circle)
- Button is square

✅ Membership Payment Screen:
- Square containers
- No shadows
- Solid colors only
- Clean borders
- Simple layout

✅ Universal Payment Screen:
- Square radio buttons
- No rounded corners on containers
- Flat buttons

### Copy Verification
✅ Registration:
- "Phone Number" label
- "This will be your username" helper text
- Simple error messages

✅ Membership Payment:
- "Payment Required" heading
- Simple breakdown table
- "Pay Membership Fee" button
- Minimal description

## Database Checks

### After Registration
```sql
-- Check user created correctly
SELECT 
    id,
    username,
    phone_number,
    email,
    is_dtehm_member,
    is_dip_member,
    dtehm_member_id,
    business_name,
    sponsor_id
FROM admin_users 
WHERE phone_number = '0700111222';

-- Expected:
-- username = phone_number
-- email = "0700111222@dtehm.app" (if no email provided)
-- is_dtehm_member = "Yes" or "No"
-- is_dip_member = "Yes" or "No"
-- dtehm_member_id = NULL (until payment)
-- business_name = NULL (until payment)
```

### After Payment
```sql
-- Check membership activated
SELECT 
    dtehm_member_id,
    business_name,
    is_membership_paid,
    membership_paid_at
FROM admin_users 
WHERE phone_number = '0700111222';

-- Expected after payment:
-- dtehm_member_id = "DTEHM20250XXX" (if DTEHM member)
-- business_name = "DIPXXXX" (if DIP member)
-- is_membership_paid = "Yes"
-- membership_paid_at = timestamp
```

## Mobile Database Check

```dart
// In app, after registration
LoggedInUserModel user = await LoggedInUserModel.getLoggedInUser();

print(user.username);           // Should be phone number
print(user.phone_number);       // Should match username
print(user.token);              // Should have JWT token
print(user.swimming);           // is_dtehm_member
print(user.father_name);        // is_dip_member
print(user.mother_name);        // dtehm_member_id (empty until payment)
print(user.phd_university_name); // sponsor_id

// Check SharedPreferences
String token = await Utils.getToken();
print(token); // Should match user.token
```

## Common Issues & Fixes

### Issue: Phone validation not working
**Fix:** Check format:
- ✅ `0700000000` (10 digits)
- ✅ `256700000000` (12 digits)
- ❌ `+256700000000` (not supported)
- ❌ `0700 000 000` (spaces not allowed)

### Issue: Membership amount wrong
**Fix:** Check user fields:
```dart
print(currentUser.swimming);    // Should be "Yes" for DTEHM
print(currentUser.father_name); // Should be "Yes" for DIP
```

### Issue: Token not saved
**Fix:** Check `save()` method in LoggedInUserModel
- Verify `Utils.setToken()` called
- Check SharedPreferences manually

### Issue: Membership screen shows 20K instead of 76K/96K
**Fix:** 
- Check `_loadData()` in MembershipPaymentScreen
- Verify field mapping in LoggedInUserModel
- Ensure API returns correct values

## Success Criteria

✅ Phone number validation working (10 or 12 digits)
✅ Registration creates user with username = phone
✅ Membership selection triggers payment
✅ Payment screen shows correct amount (76K/20K/96K)
✅ Breakdown displays correctly
✅ Token saved and accessible
✅ Flat design throughout (no rounded corners)
✅ Simplified copy everywhere
✅ Membership roadblock working
✅ Payment flow completes successfully

## Quick Commands

### Start Backend
```bash
cd /Applications/MAMP/htdocs/dtehm-insurance-api
php artisan serve --host=0.0.0.0 --port=8888
```

### Start Mobile
```bash
cd /Users/mac/Desktop/github/dtehm-insurance
flutter clean
flutter pub get
flutter run
```

### View Logs
```bash
# Backend
tail -f storage/logs/laravel.log

# Mobile (iOS)
flutter logs
```

### Reset Database (if needed)
```sql
DELETE FROM admin_users WHERE phone_number LIKE '0700%';
DELETE FROM membership_payments WHERE user_id IN (
    SELECT id FROM admin_users WHERE phone_number LIKE '0700%'
);
```

---

**Testing Status: Ready for execution**
**All features implemented and documented**
