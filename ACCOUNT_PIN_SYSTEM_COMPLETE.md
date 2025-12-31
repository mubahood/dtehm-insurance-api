# Account PIN System - Complete Implementation

## Overview
Comprehensive 4-digit PIN security system for protecting sensitive financial operations (withdrawals and commission sharing).

## Database Schema

### Table: `account_pins`
```sql
CREATE TABLE account_pins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX(user_id)
);
```

**Fields:**
- `user_id`: Foreign key to admin_users table (one PIN per user)
- `pin_hash`: Bcrypt hashed PIN (never stored in plain text)
- `failed_attempts`: Counter for incorrect PIN entries
- `locked_until`: Timestamp for account lockout (30 minutes after 5 failed attempts)
- `last_changed_at`: Timestamp of last PIN change

## Backend Components

### 1. Model: AccountPin
**Location:** `app/Models/AccountPin.php`

**Key Methods:**
- `setPin($pin)`: Hash and store new PIN, reset attempts
- `verifyPin($pin)`: Verify PIN, handle lockout and attempt tracking
- `incrementFailedAttempts()`: Increment counter, lock after 5 failures
- `resetAttempts()`: Clear failed attempts and unlock
- `isLocked()`: Check if account is currently locked
- `getLockStatus()`: Get current lock status with details

**Security Features:**
- PIN hashing with bcrypt
- Auto-lockout after 5 failed attempts (30 minutes)
- Auto-unlock after lockout period expires
- Attempt counter reset on successful verification

### 2. Controller: AccountPinController
**Location:** `app/Http/Controllers/Api/AccountPinController.php`

**Endpoints:**

#### GET `/api/account-pin/has-pin`
Check if user has a PIN set up.

**Response:**
```json
{
    "code": 200,
    "message": "Success",
    "data": {
        "has_pin": true,
        "last_changed": "2025-12-30T21:30:00.000000Z"
    }
}
```

#### POST `/api/account-pin/create`
Create new PIN (first-time setup).

**Request:**
```json
{
    "pin": "1234",
    "pin_confirmation": "1234"
}
```

**Response:**
```json
{
    "code": 201,
    "message": "PIN created successfully",
    "data": {
        "created_at": "2025-12-30T21:30:00.000000Z"
    }
}
```

**Validation:**
- PIN must be exactly 4 digits
- PIN confirmation must match
- User must not already have a PIN

#### POST `/api/account-pin/verify`
Verify PIN for authentication.

**Request:**
```json
{
    "pin": "1234"
}
```

**Success Response:**
```json
{
    "code": 200,
    "message": "PIN verified successfully",
    "data": {
        "verified": true
    }
}
```

**Failure Response:**
```json
{
    "code": 400,
    "message": "Incorrect PIN. 4 attempt(s) remaining.",
    "data": {
        "verified": false,
        "attempts_remaining": 4
    }
}
```

**Locked Response:**
```json
{
    "code": 400,
    "message": "Account is locked. Please try again after 25 minutes.",
    "data": {
        "verified": false,
        "locked_until": "2025-12-30T22:00:00.000000Z"
    }
}
```

#### POST `/api/account-pin/change`
Change existing PIN.

**Request:**
```json
{
    "old_pin": "1234",
    "new_pin": "5678",
    "new_pin_confirmation": "5678"
}
```

**Response:**
```json
{
    "code": 200,
    "message": "PIN changed successfully",
    "data": {
        "changed_at": "2025-12-30T21:35:00.000000Z"
    }
}
```

**Validation:**
- Old PIN must be correct
- New PIN must be different from old PIN
- New PIN must be 4 digits
- Confirmation must match

#### GET `/api/account-pin/lock-status`
Get current lock status.

**Response (Not Locked):**
```json
{
    "code": 200,
    "message": "Success",
    "data": {
        "is_locked": false,
        "failed_attempts": 2,
        "attempts_remaining": 3
    }
}
```

**Response (Locked):**
```json
{
    "code": 200,
    "message": "Success",
    "data": {
        "is_locked": true,
        "locked_until": "2025-12-30T22:00:00.000000Z",
        "remaining_time": "25 minutes from now"
    }
}
```

#### POST `/api/account-pin/request-reset`
Request PIN reset with SMS OTP.

**Response:**
```json
{
    "code": 200,
    "message": "Reset code sent to your phone number",
    "data": {
        "phone_number": "0772****45"
    }
}
```

**SMS Sent:**
```
Your DTEHM PIN reset code is: 123456. Valid for 10 minutes.
```

#### POST `/api/account-pin/reset-with-otp`
Reset PIN with OTP verification.

**Request:**
```json
{
    "otp": "123456",
    "new_pin": "9876",
    "new_pin_confirmation": "9876"
}
```

**Response:**
```json
{
    "code": 200,
    "message": "PIN reset successfully",
    "data": {
        "reset_at": "2025-12-30T21:40:00.000000Z"
    }
}
```

### 3. Routes
**Location:** `routes/api.php`

```php
Route::prefix('account-pin')->middleware(EnsureTokenIsValid::class)->group(function () {
    Route::get('/has-pin', [AccountPinController::class, 'hasPin']);
    Route::post('/create', [AccountPinController::class, 'createPin']);
    Route::post('/verify', [AccountPinController::class, 'verifyPin']);
    Route::post('/change', [AccountPinController::class, 'changePin']);
    Route::get('/lock-status', [AccountPinController::class, 'getLockStatus']);
    Route::post('/request-reset', [AccountPinController::class, 'requestPinReset']);
    Route::post('/reset-with-otp', [AccountPinController::class, 'resetPinWithOtp']);
});
```

## Integration with Existing Features

### 1. Withdrawal Requests
**Location:** `app/Http/Controllers/WithdrawRequestController.php`

**Changes:**
- Added `pin` field to validation (required, 4 digits)
- Check if user has PIN before withdrawal
- Verify PIN before creating withdrawal request

**Updated Request:**
```json
{
    "amount": 50000,
    "description": "Withdraw to mobile money",
    "payment_method": "mobile_money",
    "payment_phone_number": "0772123456",
    "pin": "1234"
}
```

**Error Responses:**
```json
// No PIN set up
{
    "code": 0,
    "message": "You must create a PIN before making withdrawals. Please set up your PIN in Settings.",
    "data": null
}

// Wrong PIN
{
    "code": 0,
    "message": "Incorrect PIN. 3 attempt(s) remaining.",
    "data": {
        "attempts_remaining": 3
    }
}
```

### 2. Commission Sharing
**Location:** `app/Http/Controllers/AccountTransactionController.php` -> `shareCommission()`

**Changes:**
- Added `pin` field to validation (required, 4 digits)
- Check if user has PIN before sharing
- Verify PIN before transferring commission

**Updated Request:**
```json
{
    "receiver_id": 123,
    "amount": 10000,
    "description": "Commission gift",
    "pin": "1234"
}
```

**Error Responses:**
```json
// No PIN set up
{
    "code": 0,
    "message": "You must create a PIN before sharing commission. Please set up your PIN in Settings.",
    "data": null
}

// Wrong PIN
{
    "code": 0,
    "message": "Incorrect PIN. 2 attempt(s) remaining.",
    "data": {
        "attempts_remaining": 2
    }
}
```

### 3. User Model
**Location:** `app/Models/User.php`

**Added Relationship:**
```php
public function accountPin()
{
    return $this->hasOne(AccountPin::class, 'user_id');
}
```

## Security Features

1. **PIN Hashing**: All PINs hashed with bcrypt before storage
2. **Lockout Mechanism**: Account locked for 30 minutes after 5 failed attempts
3. **Attempt Tracking**: Failed attempts counter with remaining attempts feedback
4. **Auto-Unlock**: Expired locks automatically cleared on next verification
5. **OTP Reset**: SMS-based PIN reset with 10-minute validity
6. **Mandatory PIN**: Withdrawals and commission sharing require PIN

## Testing Checklist

### Backend Testing
- [ ] Create PIN (first time)
- [ ] Duplicate PIN creation attempt (should fail)
- [ ] Verify correct PIN
- [ ] Verify incorrect PIN (track attempts)
- [ ] Lockout after 5 failed attempts
- [ ] Auto-unlock after 30 minutes
- [ ] Change PIN (old PIN verification)
- [ ] Request PIN reset (SMS sent)
- [ ] Reset PIN with valid OTP
- [ ] Reset PIN with expired/invalid OTP
- [ ] Withdrawal without PIN (should fail)
- [ ] Withdrawal with correct PIN
- [ ] Withdrawal with incorrect PIN
- [ ] Commission sharing without PIN (should fail)
- [ ] Commission sharing with correct PIN
- [ ] Commission sharing with incorrect PIN

### Frontend Testing (Pending)
- [ ] PIN setup screen UI
- [ ] PIN verification dialog
- [ ] PIN change screen
- [ ] PIN reset with OTP
- [ ] Withdrawal flow with PIN
- [ ] Commission sharing flow with PIN
- [ ] Error handling for locked account
- [ ] Error handling for wrong PIN

## API Authentication
All endpoints require valid JWT token via `EnsureTokenIsValid` middleware.

**Headers:**
```
Authorization: Bearer {token}
```

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (wrong PIN, locked account) |
| 401 | Unauthorized |
| 404 | Not Found (no PIN) |
| 422 | Validation Failed |
| 500 | Server Error |

## Migration

### Running Migration
```bash
php artisan migrate --path=database/migrations/2025_12_30_212811_create_account_pins_table.php
```

### Rolling Back
```bash
php artisan migrate:rollback --step=1
```

## Files Created/Modified

### Created:
1. `database/migrations/2025_12_30_212811_create_account_pins_table.php`
2. `app/Models/AccountPin.php`
3. `app/Http/Controllers/Api/AccountPinController.php`

### Modified:
1. `routes/api.php` - Added PIN routes
2. `app/Models/User.php` - Added accountPin relationship
3. `app/Http/Controllers/WithdrawRequestController.php` - Added PIN verification
4. `app/Http/Controllers/AccountTransactionController.php` - Added PIN verification to shareCommission

## Next Steps: Flutter Implementation

### Required Screens:
1. **PinSetupScreen**: First-time PIN creation with confirmation
2. **PinVerificationDialog**: Reusable PIN verification dialog
3. **PinChangeScreen**: Change existing PIN (old → new)
4. **PinResetScreen**: Reset forgotten PIN with OTP

### Integration Points:
1. **Settings**: Add "Change PIN" option
2. **Withdrawal Screen**: Check hasPin, verify PIN before submission
3. **Commission Sharing Screen**: Verify PIN before transfer
4. **First Login**: Check hasPin, prompt setup if missing

## Notes

- PIN must be exactly 4 digits (no letters or symbols)
- Lockout duration: 30 minutes
- Failed attempts threshold: 5
- OTP validity: 10 minutes
- Minimum withdrawal: 1,000 UGX
- Minimum commission share: 100 UGX

---

**Implementation Date:** December 30, 2025  
**Status:** Backend Complete ✅ | Frontend Pending 🔄  
**Developer:** DTEHM Insurance API Team
