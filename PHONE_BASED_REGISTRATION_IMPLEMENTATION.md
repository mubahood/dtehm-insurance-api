# Phone-Based Registration & Membership Payment Implementation

## Overview
Complete implementation of phone-number-based registration with dynamic DTEHM/DIP membership payment integration.

## Key Changes

### 1. Backend API Updates (ApiAuthController.php)

#### Registration Endpoint: `POST /api/users/register`
**Changes:**
- ✅ **Phone number is now PRIMARY identifier** (email optional)
- ✅ **Uganda phone validation**: Accepts `256XXXXXXXXX` (12 digits) or `0XXXXXXXXX` (10 digits)
- ✅ **Username = phone_number** automatically
- ✅ **Email generation**: If no email provided, uses `{phone_number}@dtehm.app`
- ✅ **No auto membership ID generation** for self-registered users
- ✅ **Login uses username (phone)** instead of email

**Request Body:**
```json
{
  "name": "John Doe",
  "phone_number": "0700123456",  // REQUIRED - Primary identifier
  "address": "Kampala, Uganda",   // Optional
  "sponsor_id": "DTEHM20250001",  // REQUIRED when from_mobile=yes
  "is_dtehm_member": "Yes",       // Optional - triggers 76K payment
  "is_dip_member": "Yes",         // Optional - triggers 20K payment
  "password": "password123",      // REQUIRED
  "from_mobile": "yes"            // REQUIRED for mobile registration
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
      "username": "0700123456",
      "phone_number": "0700123456",
      "email": "0700123456@dtehm.app",
      "name": "John Doe",
      "is_dtehm_member": "Yes",
      "is_dip_member": "Yes",
      "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
    },
    "membership_payment": {
      "required": true,
      "amount": 96000,
      "types": ["DTEHM", "DIP"],
      "breakdown": {
        "dtehm": 76000,
        "dip": 20000
      },
      "status": "pending",
      "note": "Membership IDs will be generated after successful payment"
    }
  }
}
```

**Validation Rules:**
- Phone format: Must start with `256` or `0`
- `256` format: Exactly 12 digits
- `0` format: Exactly 10 digits
- Sponsor ID: REQUIRED when `from_mobile=yes`, must exist in database
- Phone uniqueness: No duplicate phone numbers allowed

### 2. Mobile App Updates

#### RegisterScreen.dart
**Changes:**
- ✅ **Removed email field** completely
- ✅ **Phone number as primary field** with Uganda validation
- ✅ **Flat design applied**: Square corners, no shadows, no gradients
- ✅ **Real-time phone validation** on submission
- ✅ **Username helper text**: "This will be your username"

**Phone Validation:**
```dart
// Accepts both formats:
// 0700000000 (10 digits starting with 0)
// 256700000000 (12 digits starting with 256)
validator: FormBuilderValidators.compose([
  FormBuilderValidators.required(
    errorText: "Phone number is required",
  ),
  (value) {
    String cleaned = value.replaceAll(RegExp(r'[\s\-\(\)]'), '');
    
    if (cleaned.startsWith('256')) {
      if (cleaned.length != 12) {
        return "Invalid Uganda phone number. Use 256700000000 format";
      }
    } else if (cleaned.startsWith('0')) {
      if (cleaned.length != 10) {
        return "Invalid Uganda phone number. Use 0700000000 format";
      }
    } else {
      return "Phone must start with 256 or 0";
    }
    
    return null;
  },
]),
```

#### MembershipPaymentScreen.dart
**Changes:**
- ✅ **Dynamic payment amount**: Reads from user's `is_dtehm_member` and `is_dip_member`
- ✅ **Simplified UI**: Reduced wordings, flat design
- ✅ **Payment breakdown**: Shows DTEHM (76K) + DIP (20K) separately
- ✅ **Acts as roadblock**: Cannot proceed without payment
- ✅ **Integrated with membership-check endpoint**

**Amount Calculation:**
```dart
// Check membership types from user data
isDtehmMember = currentUser.swimming == 'Yes'; // is_dtehm_member
isDipMember = currentUser.father_name == 'Yes'; // is_dip_member

// Calculate total
paymentAmount = 0;
if (isDtehmMember) paymentAmount += 76000;
if (isDipMember) paymentAmount += 20000;
```

#### InitializePaymentScreen.dart (Universal Payment)
**Changes:**
- ✅ **DTEHM membership payment integrated**
- ✅ **Dynamic amount from user data**
- ✅ **Membership description updates**: Shows "DTEHM + DIP" or individual
- ✅ **Loads amount on init** if pre-selected

**Membership Payment Integration:**
```dart
// Get membership details from logged-in user
bool isDtehmMember = currentUser.swimming == 'Yes';
bool isDipMember = currentUser.father_name == 'Yes';

String membershipDesc = '';
if (isDtehmMember && isDipMember) {
  membershipDesc = 'DTEHM + DIP Membership Fee';
} else if (isDtehmMember) {
  membershipDesc = 'DTEHM Membership Fee';
} else if (isDipMember) {
  membershipDesc = 'DIP Membership Fee';
}

// Create payment with dynamic amount
PaymentItem(
  type: 'membership',
  id: 1,
  amount: totalAmount, // Dynamic: 76K, 20K, or 96K
  description: membershipDesc,
)
```

#### LoggedInUserModel.dart
**Changes:**
- ✅ **Added token field** for JWT authentication
- ✅ **Token saved to SharedPreferences** automatically
- ✅ **Field mapping updated**:
  - `swimming` → `is_dtehm_member` OR `tribe`
  - `father_name` → `is_dip_member` OR `father's name`
  - `mother_name` → `dtehm_member_id` OR `mother's name`
  - `phd_university_name` → `sponsor_id`

**Token Saving:**
```dart
save() async {
  await db.insert(tableName, toJson(), conflictAlgorithm: ConflictAlgorithm.replace);
  
  // Save token to SharedPreferences for API authentication
  if (token.isNotEmpty && token != remember_token) {
    await Utils.setToken(token);
  } else if (remember_token.isNotEmpty) {
    await Utils.setToken(remember_token);
  }
}
```

## API Testing

### Test 1: Register with Phone Number Only (No Membership)
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "phone_number": "0700111222",
    "sponsor_id": "DTEHM20250001",
    "password": "test123",
    "from_mobile": "yes"
  }'
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Account created successfully.",
  "data": {
    "user": {
      "username": "0700111222",
      "email": "0700111222@dtehm.app",
      "token": "..."
    },
    "membership_payment": {
      "required": false,
      "amount": 0
    }
  }
}
```

### Test 2: Register with DTEHM Membership
```bash
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
```

**Expected Response:**
```json
{
  "code": 1,
  "message": "Account created successfully. Please complete membership payment to activate your account.",
  "data": {
    "membership_payment": {
      "required": true,
      "amount": 76000,
      "types": ["DTEHM"],
      "breakdown": {
        "dtehm": 76000,
        "dip": 0
      }
    }
  }
}
```

### Test 3: Register with Both Memberships
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Full Member",
    "phone_number": "256700333444",
    "sponsor_id": "DTEHM20250001",
    "is_dtehm_member": "Yes",
    "is_dip_member": "Yes",
    "password": "test123",
    "from_mobile": "yes"
  }'
```

**Expected Response:**
```json
{
  "code": 1,
  "data": {
    "membership_payment": {
      "required": true,
      "amount": 96000,
      "types": ["DTEHM", "DIP"],
      "breakdown": {
        "dtehm": 76000,
        "dip": 20000
      }
    }
  }
}
```

### Test 4: Invalid Phone Format
```bash
curl -X POST http://localhost:8888/api/users/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Invalid Phone",
    "phone_number": "123456",
    "password": "test123",
    "from_mobile": "yes"
  }'
```

**Expected Response:**
```json
{
  "code": 0,
  "message": "Phone number must start with 256 or 0"
}
```

### Test 5: Check Membership Status
```bash
curl -X GET "http://localhost:8888/api/membership-check" \
  -H "Authorization: Bearer {USER_TOKEN}" \
  -H "User-Id: {USER_ID}"
```

**Expected Response:**
```json
{
  "code": 1,
  "data": {
    "user_id": 123,
    "has_valid_membership": false,
    "requires_payment": true,
    "can_access_app": false,
    "is_dtehm_member": "Yes",
    "is_dip_member": "Yes"
  }
}
```

## User Flow

### Complete Registration Flow:
1. **User opens Register Screen**
   - Phone number field (primary)
   - Full name, address, sponsor ID
   - Membership checkboxes (DTEHM 76K, DIP 20K)
   - Password fields

2. **User submits form**
   - Phone validated (Uganda format)
   - API creates account with `username = phone_number`
   - Token returned and saved automatically

3. **If membership selected:**
   - User redirected to `MembershipPaymentScreen`
   - Shows dynamic amount (76K, 20K, or 96K)
   - Displays payment breakdown
   - "Pay Now" button → `InitializePaymentScreen`

4. **Universal Payment Screen:**
   - Membership option shows dynamic amount
   - Supports Pesapal/Mobile Money
   - Creates payment with correct description
   - Callback activates membership and generates IDs

5. **After successful payment:**
   - `dtehm_member_id` generated (e.g., `DTEHM20250123`)
   - `business_name` generated (DIP ID if applicable)
   - User can access full app features

## Database Field Mapping

### admin_users table:
```
username               → Phone number (e.g., "0700123456")
email                  → Phone@dtehm.app or actual email
phone_number           → Same as username
is_dtehm_member        → "Yes" or "No"
is_dip_member          → "Yes" or "No"
dtehm_member_id        → Generated after payment (e.g., "DTEHM20250123")
business_name          → DIP ID (e.g., "DIP0123")
sponsor_id             → Referrer's member ID
remember_token         → JWT token
```

### LoggedInUserModel (Mobile):
```
username               → Phone number
token                  → JWT token (saved to SharedPreferences)
swimming               → is_dtehm_member
father_name            → is_dip_member
mother_name            → dtehm_member_id
phd_university_name    → sponsor_id
```

## Design Principles Applied

### Flat Design (Mobile):
- ✅ Square corners (`BorderRadius.zero`)
- ✅ No shadows removed (`elevation: 0`)
- ✅ No gradients (solid colors only)
- ✅ Clean borders instead of cards
- ✅ Minimal styling

### Simplified Copy:
- ✅ "Payment Required" instead of long descriptions
- ✅ Clear breakdown tables
- ✅ Direct action buttons
- ✅ Essential information only

## Security Considerations

1. **Phone Validation**: Backend validates Uganda format
2. **Unique Constraint**: Phone numbers must be unique
3. **Sponsor Verification**: Sponsor must exist in database
4. **Token Security**: JWT with 1-year expiry
5. **Password Hashing**: Using `password_hash()` with `PASSWORD_DEFAULT`
6. **No Auto-Billing**: Membership IDs generated only after confirmed payment

## Error Handling

### Backend Errors:
- Invalid phone format
- Duplicate phone number
- Invalid sponsor ID
- Missing required fields

### Mobile Errors:
- Phone format validation
- Network errors
- Payment failures
- Token expiry

## Testing Checklist

- [ ] Register with phone only (no membership)
- [ ] Register with DTEHM membership (76K)
- [ ] Register with DIP membership (20K)
- [ ] Register with both memberships (96K)
- [ ] Test invalid phone formats
- [ ] Test duplicate phone numbers
- [ ] Test missing sponsor ID
- [ ] Test membership payment flow
- [ ] Test token saving and reuse
- [ ] Test login with phone number
- [ ] Verify membership-check endpoint
- [ ] Test payment confirmation
- [ ] Verify membership ID generation after payment

## Status: ✅ IMPLEMENTATION COMPLETE

All changes implemented and integrated:
- Backend API updated ✅
- Registration screen updated ✅
- Membership payment screen updated ✅
- Universal payment integrated ✅
- User model updated ✅
- Token saving implemented ✅
- Flat design applied ✅
- Documentation complete ✅
