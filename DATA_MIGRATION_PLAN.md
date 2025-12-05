# DATA MIGRATION PLAN: OLD SYSTEM → NEW SYSTEM

**Generated:** January 2025  
**Purpose:** Migrate 195 users and their network hierarchies from `dtehmhea_app` to new system  
**Critical Requirement:** ZERO ERRORS TOLERANCE

---

## 1. DATA STRUCTURE ANALYSIS

### Old System Tables

#### A. `users` Table (195 records)
```json
{
  "userID": "2",           // Internal database ID (integer as string)
  "memberID": "DTEHM001",  // Public member identifier
  "fname": "Enostus",      // First name
  "lname": "Bwambale",     // Last name
  "username": "DTEHM001",  // Login username (matches memberID)
  "password": "12345",     // Plain text password (needs bcrypt)
  "role": "stockist",      // User role (stockist, admin, etc.)
  "lastLogin": "2025/12/05 04:22:44 pm"
}
```

#### B. `members` Table (185 records)
```json
{
  "memberID": "DTEHM001",              // Links to users.memberID
  "fname": "Enostus",                  // First name (may differ from users)
  "lname": "Nzwende",                  // Last name
  "email": "nzwendeenostus@gmail.com",
  "phone": "0782284788",
  "mobileMoney": "0782284788",
  "bankaccntName": "Nzwende Enostus",
  "bankname": "Centenary bank",
  "bankaccount": "152098870",
  "branch": "13",                      // Branch ID
  "password": "12345",                 // Duplicate from users
  "membership": "paid",                // Payment status
  "lastlogin": "nodate",
  "lastIP": "noip"
}
```

**Note:** 10 users exist in `users` but NOT in `members` (195 - 185 = 10)

#### C. `referrals` Table (194 records)
```json
{
  "rID": "37",                    // Referral record ID
  "referrer_id": "DTEHM041",      // The user this record belongs to
  "level1": "DTEHM001",           // Direct sponsor (parent)
  "level2": null,                 // Sponsor's sponsor
  "level3": null,                 // Level 3 upline
  "level4": null,                 // ... and so on
  "level5": null,
  "level6": null,
  "level7": null,
  "level8": null,
  "level9": null,
  "level10": null
}
```

**Network Logic:**
- `referrer_id`: The person being referred (the child)
- `level1`: Direct sponsor (parent) - Maps to new system's `sponsor_id`
- `level2-10`: Entire upline hierarchy stored redundantly
- **DTEHM001** has `level1 = null` (root of tree, no sponsor)

### Network Statistics

| Metric | Count |
|--------|-------|
| Total users | 195 |
| Total referrals | 194 |
| Total members | 185 |
| Users with sponsor (level1 not null) | 189 |
| Orphaned users (no sponsor) | 5 |
| Network depth (max) | Level 8 |
| Missing from members table | 10 users |

**Orphaned Users (No Sponsor):**
1. DTEHM001 (Root - Expected)
2. DTEHM089
3. DTEHM003
4. DTEHM057
5. DTEHM179

---

## 2. NEW SYSTEM STRUCTURE

### Key Fields in `users` Table

```
Essential Fields:
- id                           → Auto-increment (DTEHM001 MUST be 1)
- username                     → Old: username
- password                     → Old: password (BCRYPT)
- first_name                   → Old: members.fname
- last_name                    → Old: members.lname
- email                        → Old: members.email
- phone_number                 → Old: members.phone
- dtehm_member_id              → Old: memberID (MUST PRESERVE)
- sponsor_id                   → Old: referrals.level1 (Foreign Key to id)
- user_type                    → "Member" (DTEHM001 = "Admin")
- is_dtehm_member              → "Yes" (All users)
- dtehm_membership_is_paid     → Old: members.membership == "paid" ? "Yes" : "No"
- dtehm_membership_paid_amount → 76000 (Standard DTEHM fee)
- status                       → "Active" (Default)

Network Tracking (parent_1 to parent_10):
- parent_1                     → Old: referrals.level1 (sponsor's dtehm_member_id)
- parent_2                     → Old: referrals.level2
- parent_3                     → Old: referrals.level3
- ... up to parent_10

Additional Fields:
- reg_date                     → Now
- created_at                   → Now
- updated_at                   → Now
```

---

## 3. FIELD MAPPING RULES

### Direct Mappings
```
users.memberID          → users.dtehm_member_id       [MUST PRESERVE]
users.username          → users.username
users.fname             → users.first_name            [Use members.fname if different]
users.lname             → users.last_name             [Use members.lname if different]
users.role              → users.user_type             [Transform: see role mapping]
members.email           → users.email
members.phone           → users.phone_number          [Normalize: Utils::prepare_phone_number()]
members.mobileMoney     → users.whatsapp              [Optional]
members.bankaccntName   → [Future field]
members.bankname        → [Future field]
members.bankaccount     → [Future field]
members.branch          → [Future field]
members.membership      → users.dtehm_membership_is_paid [paid → Yes, else No]
referrals.level1        → users.sponsor_id            [Lookup ID from dtehm_member_id]
referrals.level1-10     → users.parent_1 to parent_10 [Store dtehm_member_id]
```

### Role Mapping
```
Old Role        → New user_type
─────────────────────────────────
stockist        → Member
admin           → Admin
member          → Member
[blank/other]   → Member
```

**Special Case:** DTEHM001 role="stockist" → user_type="Admin" (hardcoded override)

### Password Transformation
```php
// Old: password = "12345" (plain text)
// New: password = bcrypt("12345")
$new_password = bcrypt($old_password);
```

### Phone Normalization
```php
// Use existing utility
$normalized_phone = Utils::prepare_phone_number($members_phone);
// Example: "0782284788" → "+256782284788"
```

---

## 4. MIGRATION STRATEGY

### Step-by-Step Approach

#### PHASE 1: PRE-MIGRATION VALIDATION
```
✓ Verify old system data integrity
  - Check for duplicate memberIDs
  - Verify all referrals.level1 exist in users.memberID
  - Identify orphaned users
  - Validate email/phone uniqueness in new system

✓ Backup current database
  - mysqldump dtehm_insurance_api > backup_pre_migration.sql

✓ Prepare migration log table
  - CREATE TABLE migration_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      member_id VARCHAR(50),
      old_user_id INT,
      new_user_id INT,
      status ENUM('success', 'failed'),
      message TEXT,
      created_at TIMESTAMP
    )
```

#### PHASE 2: DATABASE PREPARATION
```sql
-- Truncate users table (CRITICAL: This deletes all existing users)
TRUNCATE TABLE users;

-- Reset auto-increment to 1 (ensures DTEHM001 gets ID 1)
ALTER TABLE users AUTO_INCREMENT = 1;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear related tables if necessary
TRUNCATE TABLE commissions;
TRUNCATE TABLE memberships;
TRUNCATE TABLE account_transactions;

-- Re-enable checks after migration
SET FOREIGN_KEY_CHECKS = 1;
```

#### PHASE 3: USER MIGRATION (Two-Pass Approach)

**PASS 1: Insert All Users (Without sponsor_id)**

Reason: sponsor_id is a foreign key. We must insert all users first, then link them.

```php
// Pseudocode
foreach ($old_users as $old_user) {
    $member_record = find_member_by_id($old_user['memberID']);
    
    User::create([
        'dtehm_member_id' => $old_user['memberID'],
        'username' => $old_user['username'],
        'password' => bcrypt($old_user['password']),
        'first_name' => $member_record['fname'] ?? $old_user['fname'],
        'last_name' => $member_record['lname'] ?? $old_user['lname'],
        'email' => $member_record['email'] ?? null,
        'phone_number' => Utils::prepare_phone_number($member_record['phone'] ?? ''),
        'user_type' => ($old_user['memberID'] == 'DTEHM001') ? 'Admin' : 'Member',
        'is_dtehm_member' => 'Yes',
        'dtehm_membership_is_paid' => ($member_record['membership'] == 'paid') ? 'Yes' : 'No',
        'dtehm_membership_paid_amount' => 76000,
        'status' => 'Active',
        'sponsor_id' => null, // Fill in Pass 2
        'parent_1' => null,   // Fill in Pass 2
        'parent_2' => null,   // ... and so on
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

**Sort Order:** MUST insert DTEHM001 FIRST to ensure it gets ID 1.

```php
// Sort old users array
usort($old_users, function($a, $b) {
    if ($a['memberID'] == 'DTEHM001') return -1;
    if ($b['memberID'] == 'DTEHM001') return 1;
    return strcmp($a['memberID'], $b['memberID']);
});
```

**PASS 2: Update Network Connections**

After all users inserted with their dtehm_member_id:

```php
foreach ($old_referrals as $referral) {
    $user = User::where('dtehm_member_id', $referral['referrer_id'])->first();
    
    if (!$user) {
        log_error("User not found: " . $referral['referrer_id']);
        continue;
    }
    
    // Get sponsor (level1)
    if ($referral['level1']) {
        $sponsor = User::where('dtehm_member_id', $referral['level1'])->first();
        if ($sponsor) {
            $user->sponsor_id = $sponsor->id;
            $user->parent_1 = $sponsor->dtehm_member_id;
        }
    }
    
    // Store entire upline hierarchy
    for ($i = 2; $i <= 10; $i++) {
        $level_key = "level{$i}";
        $parent_key = "parent_{$i}";
        if (!empty($referral[$level_key])) {
            $user->$parent_key = $referral[$level_key];
        }
    }
    
    $user->save();
}
```

#### PHASE 4: POST-MIGRATION VALIDATION

```php
// 1. Verify DTEHM001 is ID 1
$admin = User::where('dtehm_member_id', 'DTEHM001')->first();
if ($admin->id != 1) {
    throw new Exception("CRITICAL: DTEHM001 is not ID 1!");
}
if ($admin->user_type != 'Admin') {
    throw new Exception("CRITICAL: DTEHM001 is not Admin!");
}

// 2. Verify all users migrated
$new_count = User::count();
$old_count = count($old_users);
if ($new_count != $old_count) {
    throw new Exception("User count mismatch: old={$old_count}, new={$new_count}");
}

// 3. Verify network connections
$users_with_sponsor = User::whereNotNull('sponsor_id')->count();
$expected = 189; // From old system analysis
if ($users_with_sponsor != $expected) {
    log_warning("Sponsor count mismatch: expected={$expected}, got={$users_with_sponsor}");
}

// 4. Test each network connection
foreach (User::all() as $user) {
    if ($user->sponsor_id) {
        $sponsor = User::find($user->sponsor_id);
        if (!$sponsor) {
            log_error("Broken sponsor link: {$user->dtehm_member_id} → sponsor_id={$user->sponsor_id}");
        } else {
            // Verify parent_1 matches sponsor
            if ($user->parent_1 != $sponsor->dtehm_member_id) {
                log_error("parent_1 mismatch: {$user->dtehm_member_id}");
            }
        }
    }
}

// 5. Verify orphaned users
$orphans = User::whereNull('sponsor_id')->pluck('dtehm_member_id')->toArray();
$expected_orphans = ['DTEHM001', 'DTEHM089', 'DTEHM003', 'DTEHM057', 'DTEHM179'];
if (count(array_diff($orphans, $expected_orphans)) > 0) {
    log_warning("Unexpected orphaned users found");
}
```

---

## 5. CRITICAL REQUIREMENTS CHECKLIST

### MUST-HAVE Validations

- [ ] **DTEHM001 is ID 1**
  ```sql
  SELECT id, dtehm_member_id FROM users WHERE dtehm_member_id = 'DTEHM001';
  -- Expected: id = 1
  ```

- [ ] **DTEHM001 has user_type = 'Admin'**
  ```sql
  SELECT user_type FROM users WHERE dtehm_member_id = 'DTEHM001';
  -- Expected: Admin
  ```

- [ ] **All DTEHM IDs preserved exactly**
  ```sql
  -- Count must match
  SELECT COUNT(DISTINCT dtehm_member_id) FROM users;
  -- Expected: 195
  ```

- [ ] **All users marked as DTEHM members**
  ```sql
  SELECT COUNT(*) FROM users WHERE is_dtehm_member != 'Yes';
  -- Expected: 0
  ```

- [ ] **189 users have sponsors (excluding 5 orphans + 1 root)**
  ```sql
  SELECT COUNT(*) FROM users WHERE sponsor_id IS NOT NULL;
  -- Expected: 189
  ```

- [ ] **No broken sponsor links**
  ```sql
  SELECT u.dtehm_member_id, u.sponsor_id
  FROM users u
  LEFT JOIN users s ON u.sponsor_id = s.id
  WHERE u.sponsor_id IS NOT NULL AND s.id IS NULL;
  -- Expected: Empty result set
  ```

- [ ] **parent_1 matches sponsor's dtehm_member_id**
  ```sql
  SELECT u.dtehm_member_id, u.parent_1, s.dtehm_member_id as sponsor_dtehm_id
  FROM users u
  JOIN users s ON u.sponsor_id = s.id
  WHERE u.parent_1 != s.dtehm_member_id;
  -- Expected: Empty result set
  ```

- [ ] **All passwords are bcrypted (length = 60)**
  ```sql
  SELECT dtehm_member_id FROM users WHERE LENGTH(password) != 60;
  -- Expected: Empty result set
  ```

---

## 6. HANDLING EDGE CASES

### Case 1: Users Without Member Records (10 users)
**Problem:** 195 users but only 185 members  
**Solution:** Use users.fname/lname, leave email/phone blank or derive from username

```php
if (!$member_record) {
    // Use data from users table
    $email = null; // Or generate: {username}@dtehm.com
    $phone = null;
    $first_name = $old_user['fname'];
    $last_name = $old_user['lname'];
}
```

### Case 2: Orphaned Users (5 without sponsors)
**Problem:** DTEHM089, DTEHM003, DTEHM057, DTEHM179 have level1=null  
**Solution:** Leave sponsor_id=null, mark in migration log for manual review

```php
if (empty($referral['level1'])) {
    log_warning("Orphaned user: {$referral['referrer_id']} - No sponsor assigned");
    // sponsor_id remains null
}
```

### Case 3: Name Discrepancies (users.fname vs members.fname)
**Problem:** DTEHM001 has fname="Enostus" in users but "Enostus" in members, lname="Bwambale" vs "Nzwende"  
**Solution:** Prioritize members table (more complete data)

```php
$first_name = $member_record['fname'] ?? $old_user['fname'];
$last_name = $member_record['lname'] ?? $old_user['lname'];
```

### Case 4: Missing Referral Record
**Problem:** 195 users but 194 referrals (1 user has no referral record)  
**Solution:** Assume no sponsor if referral record missing

```php
$referral = find_referral($user['memberID']);
if (!$referral) {
    log_warning("No referral record for {$user['memberID']}");
    // Leave sponsor_id = null
}
```

---

## 7. ROLLBACK PLAN

### If Migration Fails

1. **Stop immediately at first critical error**
2. **Restore backup:**
   ```bash
   mysql -u root -p dtehm_insurance_api < backup_pre_migration.sql
   ```
3. **Review migration logs:**
   ```sql
   SELECT * FROM migration_logs WHERE status = 'failed';
   ```
4. **Fix issues in migration script**
5. **Re-test on staging database**
6. **Retry migration**

### Transaction Boundaries

Wrap entire migration in database transaction:

```php
DB::beginTransaction();
try {
    // Phase 1: Insert all users
    // Phase 2: Update network connections
    // Phase 3: Validate everything
    
    DB::commit();
    log_success("Migration completed successfully");
} catch (Exception $e) {
    DB::rollback();
    log_error("Migration failed: " . $e->getMessage());
    throw $e;
}
```

---

## 8. MIGRATION SCRIPT STRUCTURE

### Artisan Command: `php artisan migrate:old-users`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateOldUsers extends Command
{
    protected $signature = 'migrate:old-users {--dry-run : Run without saving to database}';
    protected $description = 'Migrate users from old system (dtehmhea_app.json)';
    
    private $old_data;
    private $users_data;
    private $members_data;
    private $referrals_data;
    private $stats = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'orphaned' => 0,
    ];
    
    public function handle()
    {
        $this->info('=== DTEHM USER MIGRATION ===');
        
        // Phase 1: Load and validate data
        if (!$this->loadOldData()) {
            return 1;
        }
        
        // Phase 2: Pre-migration checks
        if (!$this->validateOldData()) {
            return 1;
        }
        
        // Phase 3: Confirm truncate
        if (!$this->option('dry-run')) {
            if (!$this->confirm('This will TRUNCATE users table. Continue?', false)) {
                $this->error('Migration cancelled by user.');
                return 1;
            }
        }
        
        // Phase 4: Execute migration
        DB::beginTransaction();
        try {
            $this->truncateUsers();
            $this->migrateUsers();
            $this->updateNetworkConnections();
            $this->validateMigration();
            
            if ($this->option('dry-run')) {
                DB::rollback();
                $this->info('DRY RUN: No changes saved.');
            } else {
                DB::commit();
                $this->info('Migration completed successfully!');
            }
            
            $this->displayStats();
            return 0;
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    private function loadOldData() { /* ... */ }
    private function validateOldData() { /* ... */ }
    private function truncateUsers() { /* ... */ }
    private function migrateUsers() { /* ... */ }
    private function updateNetworkConnections() { /* ... */ }
    private function validateMigration() { /* ... */ }
    private function displayStats() { /* ... */ }
}
```

---

## 9. TESTING CHECKLIST

### Pre-Migration Tests
- [ ] Verify JSON file integrity
- [ ] Check for duplicate DTEHM IDs in old data
- [ ] Verify all level1 sponsors exist in users table
- [ ] Test on staging database first

### Post-Migration Tests
- [ ] DTEHM001 is ID 1 with Admin role
- [ ] All 195 users imported
- [ ] 189 users have valid sponsor_id
- [ ] 5 orphaned users identified (DTEHM089, etc.)
- [ ] No broken sponsor links (foreign key integrity)
- [ ] parent_1 matches sponsor's dtehm_member_id for all users
- [ ] All passwords are bcrypted (length=60)
- [ ] All phone numbers normalized
- [ ] All users have is_dtehm_member='Yes'
- [ ] Test login with DTEHM001 (should work with ID, username, phone, email)

### Network Validation Tests
```sql
-- Test 1: Root user has no sponsor
SELECT * FROM users WHERE dtehm_member_id = 'DTEHM001' AND sponsor_id IS NOT NULL;
-- Expected: Empty

-- Test 2: All sponsored users have valid sponsor
SELECT u.dtehm_member_id, u.sponsor_id
FROM users u
WHERE u.sponsor_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM users s WHERE s.id = u.sponsor_id);
-- Expected: Empty

-- Test 3: Network depth distribution
SELECT 
  CASE WHEN parent_1 IS NOT NULL THEN 1 ELSE 0 END +
  CASE WHEN parent_2 IS NOT NULL THEN 1 ELSE 0 END +
  CASE WHEN parent_3 IS NOT NULL THEN 1 ELSE 0 END +
  CASE WHEN parent_4 IS NOT NULL THEN 1 ELSE 0 END +
  CASE WHEN parent_5 IS NOT NULL THEN 1 ELSE 0 END AS depth,
  COUNT(*) as count
FROM users
GROUP BY depth
ORDER BY depth;
-- Expected: Similar distribution to old system (189@level1, 95@level2, etc.)

-- Test 4: Circular reference check (safety)
-- This should never happen but validate anyway
WITH RECURSIVE hierarchy AS (
  SELECT id, dtehm_member_id, sponsor_id, 1 as level
  FROM users WHERE dtehm_member_id = 'DTEHM001'
  
  UNION ALL
  
  SELECT u.id, u.dtehm_member_id, u.sponsor_id, h.level + 1
  FROM users u
  JOIN hierarchy h ON u.sponsor_id = h.id
  WHERE h.level < 20
)
SELECT COUNT(*) as total_in_network FROM hierarchy;
-- Expected: 195 (all users)
-- If less than 195, there are disconnected users
```

---

## 10. EXECUTION TIMELINE

### Estimated Duration: 30-60 minutes

| Phase | Task | Duration | Notes |
|-------|------|----------|-------|
| 1 | Pre-migration validation | 5 min | Check data integrity |
| 2 | Database backup | 2 min | Safety first |
| 3 | Truncate tables | 1 min | Point of no return |
| 4 | Insert 195 users (Pass 1) | 10 min | Sequential insertion |
| 5 | Update network connections (Pass 2) | 10 min | Foreign key updates |
| 6 | Post-migration validation | 15 min | Comprehensive checks |
| 7 | Manual verification | 10 min | Test login, check UI |

**Total:** ~53 minutes (conservative estimate)

---

## 11. SUCCESS CRITERIA

Migration is successful if ALL of these are true:

1. ✅ 195 users inserted into new system
2. ✅ DTEHM001 has database ID = 1
3. ✅ DTEHM001 has user_type = 'Admin'
4. ✅ All DTEHM IDs preserved exactly (no changes)
5. ✅ 189 users have valid sponsor_id
6. ✅ 5 expected orphans: DTEHM001, DTEHM089, DTEHM003, DTEHM057, DTEHM179
7. ✅ No broken sponsor links (all sponsor_ids exist)
8. ✅ parent_1 through parent_10 correctly populated
9. ✅ All users have is_dtehm_member = 'Yes'
10. ✅ All passwords are bcrypted
11. ✅ DTEHM001 can login to Laravel Admin
12. ✅ Network hierarchy preserved (levels 1-8)
13. ✅ Zero errors in migration log

---

## 12. POST-MIGRATION ACTIONS

After successful migration:

1. **Notify DTEHM001 (Super Admin)**
   - New login credentials (if password changed)
   - System access confirmed
   
2. **Test Key Workflows**
   - Login with various identifiers
   - View network tree
   - Create test commission
   - Generate reports
   
3. **Archive Old System Data**
   ```bash
   mv dtehmhea_app.json archive/dtehmhea_app_$(date +%Y%m%d).json
   ```

4. **Document Orphaned Users**
   - Create support ticket to investigate DTEHM089, DTEHM003, DTEHM057, DTEHM179
   - Determine correct sponsors if possible
   
5. **Monitor for Issues**
   - Check error logs for 48 hours
   - Watch for login issues
   - Verify commission calculations

---

## 13. APPENDIX

### A. Sample SQL Queries

**Find all descendants of DTEHM001:**
```sql
SELECT u.dtehm_member_id, u.first_name, u.last_name, s.dtehm_member_id as sponsor
FROM users u
LEFT JOIN users s ON u.sponsor_id = s.id
WHERE u.parent_1 = 'DTEHM001'
   OR u.parent_2 = 'DTEHM001'
   OR u.parent_3 = 'DTEHM001'
ORDER BY u.id;
```

**Generate network tree:**
```sql
WITH RECURSIVE tree AS (
  SELECT id, dtehm_member_id, sponsor_id, 0 as level, 
         dtehm_member_id as path
  FROM users WHERE dtehm_member_id = 'DTEHM001'
  
  UNION ALL
  
  SELECT u.id, u.dtehm_member_id, u.sponsor_id, t.level + 1,
         CONCAT(t.path, ' > ', u.dtehm_member_id)
  FROM users u
  JOIN tree t ON u.sponsor_id = t.id
)
SELECT * FROM tree ORDER BY level, dtehm_member_id;
```

### B. Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| "Duplicate entry for dtehm_member_id" | Running migration twice | Truncate and restart |
| "Foreign key constraint fails" | Sponsor not inserted yet | Use two-pass approach |
| "DTEHM001 is ID 2, not 1" | Incorrect sort order | Sort with DTEHM001 first |
| "Column 'email' cannot be null" | Missing member record | Use nullable fields |
| "189 users with sponsor, expected 189" | Correct! | No action needed |

---

**END OF MIGRATION PLAN**

**Next Step:** Review this document, get approval, then create the migration script (`app/Console/Commands/MigrateOldUsers.php`)
