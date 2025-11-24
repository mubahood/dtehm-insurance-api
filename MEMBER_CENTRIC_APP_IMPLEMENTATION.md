# Member-Centric App Implementation Plan

## Overview
Transform the mobile app to be **member-centric** where regular users only see their own data, while admin users can see all data.

---

## ✅ COMPLETED

### 1. Navigation (Mobile App)
**File:** `/Users/mac/Desktop/github/dtehm-insurance/lib/screens/main_app/tabs/more_tab.dart`

**Status:** ✅ Already member-centric!

The navigation is already properly filtered:
- Admin-only sections are wrapped in `if (_isAdmin())` blocks
- Regular users don't see:
  - "Insurance Users" menu item  
  - "Administration" section (System Users, All Users, Reports, System Config)
- Function: `_isAdmin()` checks `mainController.userModel.user_type.toLowerCase() == 'admin'`

**No changes needed!**

---

## 🔄 IN PROGRESS - API Endpoint Filtering

### Critical Principle
**Every API endpoint MUST filter by logged_in_user_id UNLESS the user is an admin.**

```php
// Pattern to follow:
$user = Utils::get_user($request);
if (!$user) {
    return $this->error('User not found');
}

// For non-admin users, filter by user ID
if (!$user->isAdmin()) {
    $query = Model::where('user_id', $user->id);
} else {
    // Admins see all data
    $query = Model::query();
}
```

---

## 📋 ENDPOINTS TO VERIFY/FIX

### Priority 1: Insurance Endpoints (CRITICAL)

#### File: `app/Http/Controllers/ApiResurceController.php`

**1. insurance_users() - Line ~3162**
```php
// CURRENT: Returns ALL users
// SHOULD: Regular users should not access this endpoint at all
// ADMIN ONLY endpoint

public function insurance_users(Request $request)
{
    $user = Utils::get_user($request);
    if (!$user) {
        return $this->error('User not found');
    }
    
    // Admin-only endpoint
    if (!$user->isAdmin()) {
        return $this->error('Access denied. Admin privileges required.');
    }
    
    // Continue with existing logic for admins...
}
```

**2. get_items() - Need to find and check**
```php
// Should filter by user_id for non-admins
```

---

### Priority 2: Investment Endpoints

Check these routes in `routes/api.php` (around line 200-250):

1. **projects** → Should show all projects (OK)
2. **project-shares** → Filter by user_id (my shares only)
3. **project-transactions** → Filter by user_id (my transactions only)
4. **disbursements** → Filter by user_id (disbursements to me only)
5. **account-transactions** → Already filtered by user_id ✅

**Files to check:**
- `app/Http/Controllers/TransactionController.php`
- `app/Http/Controllers/DisbursementController.php`
- `app/Http/Controllers/AccountTransactionController.php`

---

### Priority 3: Insurance Transactions & Subscriptions

**Routes to check:**
- `api/insurance-programs` → Show all (OK - public programs)
- `api/insurance-subscriptions` → Filter by user_id
- `api/insurance-transactions` → Filter by user_id
- `api/insurance-subscription-payments` → Filter by user_id

**Controller:** `app/Http/Controllers/TransactionController.php`

Example fix:
```php
public function index(Request $request)
{
    $user = Utils::get_user($request);
    if (!$user) {
        return $this->error('User not found');
    }
    
    if (!$user->isAdmin()) {
        // Regular users see only their data
        $transactions = InsuranceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        // Admins see all
        $transactions = InsuranceTransaction::orderBy('created_at', 'desc')->get();
    }
    
    return $this->success($transactions);
}
```

---

### Priority 4: Medical Service Requests

**Route:** `api/medical-service-requests`

**Controller:** Check `app/Http/Controllers/MedicalServiceRequestController.php` or similar

```php
public function index(Request $request)
{
    $user = Utils::get_user($request);
    if (!$user) {
        return $this->error('User not found');
    }
    
    if (!$user->isAdmin()) {
        // Regular users see only their requests
        $requests = MedicalServiceRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        // Admins see all requests
        $requests = MedicalServiceRequest::orderBy('created_at', 'desc')->get();
    }
    
    return $this->success($requests);
}
```

---

### Priority 5: E-Commerce Orders

**✅ Already filtered!**

File: `app/Http/Controllers/ApiResurceController.php` line 980

```php
public function orders_get(Request $r)
{
    $u = auth('api')->user();
    // ... user validation ...
    
    $orders = [];
    foreach (Order::where(['user' => $u->id])->get() as $order) {
        // ...
    }
    return $this->success($orders);
}
```

---

## 🔍 VERIFICATION CHECKLIST

For EACH endpoint, verify:

1. ✅ Gets logged-in user via `Utils::get_user($request)`
2. ✅ Returns error if user not found
3. ✅ Checks `if (!$user->isAdmin())` before filtering
4. ✅ Filters query by `user_id` for non-admins
5. ✅ Admins can see all data
6. ✅ Test with regular user account
7. ✅ Test with admin account

---

## 🧪 TESTING PLAN

### Step 1: Create Test Accounts

```sql
-- Regular user (existing or create new)
SELECT * FROM users WHERE user_type = 'Customer' LIMIT 1;

-- Admin user
SELECT * FROM users WHERE user_type = 'Admin' LIMIT 1;
```

### Step 2: Test Each Endpoint

Using Postman or mobile app:

1. **Login as regular user**
2. Test each endpoint:
   - Insurance programs ✅
   - My subscriptions (should see only mine)
   - My transactions (should see only mine)
   - My shares (should see only mine)
   - My disbursements (should see only mine)
   - Medical requests (should see only mine)
   - Orders (should see only mine) ✅

3. **Login as admin**
4. Test same endpoints:
   - Should see ALL data for all users

### Step 3: Test Admin-Only Endpoints

As regular user, try to access:
- `/api/insurance-users` → Should return "Access denied"
- `/api/users` → Should return "Access denied" if exists

---

## 📄 IMPLEMENTATION STEPS

### Step 1: Audit All Controllers

Find all controllers that return lists of data:

```bash
cd /Applications/MAMP/htdocs/dtehm-insurance-api
grep -r "public function index" app/Http/Controllers/
grep -r "public function get" app/Http/Controllers/ApiResurceController.php
```

### Step 2: Apply Member-Centric Pattern

For each endpoint that returns user data:

```php
public function methodName(Request $request)
{
    // 1. Get user
    $user = Utils::get_user($request);
    if (!$user) {
        return $this->error('User not found');
    }
    
    // 2. Build query with conditional filtering
    $query = ModelName::query();
    
    if (!$user->isAdmin()) {
        $query->where('user_id', $user->id);
    }
    
    // 3. Execute query
    $results = $query->orderBy('created_at', 'desc')->get();
    
    return $this->success($results);
}
```

### Step 3: Protect Admin-Only Endpoints

For endpoints that should be admin-only:

```php
public function adminOnlyMethod(Request $request)
{
    $user = Utils::get_user($request);
    if (!$user) {
        return $this->error('User not found');
    }
    
    if (!$user->isAdmin()) {
        return $this->error('Access denied. Admin privileges required.', 403);
    }
    
    // Admin logic here...
}
```

---

## 🎯 SPECIFIC FILES TO UPDATE

### High Priority

1. **`app/Http/Controllers/ApiResurceController.php`**
   - `insurance_users()` → Make admin-only
   - Check all `public function` methods that return lists

2. **`app/Http/Controllers/TransactionController.php`**
   - `index()` → Filter by user_id
   - `show()` → Verify user owns the transaction

3. **`app/Http/Controllers/DisbursementController.php`**
   - `index()` → Filter by user_id
   - `show()` → Verify user owns the disbursement

4. **`app/Http/Controllers/AccountTransactionController.php`**
   - Should already be filtered ✅

5. **`app/Http/Controllers/MedicalServiceRequestController.php`** (if exists)
   - `index()` → Filter by user_id
   - `show()` → Verify user owns the request

### Medium Priority

6. **Project-related controllers**
   - Filter shares by user
   - Filter transactions by user

---

## 🚨 COMMON MISTAKES TO AVOID

1. ❌ **Don't hardcode admin check as:**
   ```php
   if ($user->user_type == 'Admin') // Bad - case sensitive
   ```
   
   ✅ **Do this:**
   ```php
   if ($user->isAdmin()) // Good - uses method
   ```

2. ❌ **Don't forget to filter relationships:**
   ```php
   $user->projects // Returns ALL projects!
   ```
   
   ✅ **Do this:**
   ```php
   $user->projects()->where('user_id', $user->id)->get()
   ```

3. ❌ **Don't allow users to access other users' data by ID:**
   ```php
   Route::get('/transactions/{id}', function($id) {
       return Transaction::find($id); // Any user can see any transaction!
   });
   ```
   
   ✅ **Do this:**
   ```php
   Route::get('/transactions/{id}', function(Request $request, $id) {
       $user = Utils::get_user($request);
       $transaction = Transaction::find($id);
       
       if (!$user->isAdmin() && $transaction->user_id != $user->id) {
           return response()->json(['error' => 'Access denied'], 403);
       }
       
       return $transaction;
   });
   ```

---

## 📊 SUMMARY

### Current Status

| Component | Status | Notes |
|-----------|--------|-------|
| Mobile Navigation | ✅ Done | Admin sections already hidden |
| E-Commerce Orders | ✅ Done | Already filtered by user |
| Insurance Endpoints | ⚠️ Needs Review | insurance_users needs admin check |
| Investment Endpoints | ⚠️ Needs Review | Filter by user_id |
| Medical Requests | ⚠️ Needs Review | Filter by user_id |
| Admin-Only Endpoints | ⚠️ Needs Review | Add admin checks |

### Next Steps

1. **Immediate:** Review and fix `insurance_users()` endpoint
2. **Short-term:** Audit and fix all list endpoints
3. **Testing:** Create test accounts and verify filtering
4. **Documentation:** Update API documentation with access rules

---

## 💡 RECOMMENDATIONS

1. **Create Middleware:** Consider creating a `MemberCentricMiddleware` that automatically filters queries by user_id

2. **Add Logging:** Log when non-admins try to access admin endpoints

3. **API Documentation:** Update API docs to clearly mark admin-only endpoints

4. **Mobile App:** Add error handling for 403 Forbidden responses

5. **Database Indexes:** Add indexes on `user_id` columns for better performance

---

## 🔐 SECURITY CONSIDERATIONS

1. Never trust client-side filtering
2. Always validate user ownership on the server
3. Use proper HTTP status codes (403 for forbidden, 401 for unauthorized)
4. Log suspicious access attempts
5. Consider rate limiting for sensitive endpoints

---

**Document Created:** 2025-01-24
**Last Updated:** 2025-01-24  
**Status:** In Progress - API Endpoint Review Phase
