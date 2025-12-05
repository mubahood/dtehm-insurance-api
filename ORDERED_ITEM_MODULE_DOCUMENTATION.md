# OrderedItem Module - Complete Documentation

## Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Commission Structure](#commission-structure)
4. [Database Schema](#database-schema)
5. [Core Components](#core-components)
6. [API Endpoints](#api-endpoints)
7. [Usage Guide](#usage-guide)
8. [Validation Rules](#validation-rules)
9. [Error Handling](#error-handling)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)

---

## Overview

The OrderedItem module is a comprehensive MLM (Multi-Level Marketing) product sales and commission management system. It handles:

- Product sales recording
- Automatic commission calculation and distribution
- 10-level network hierarchy commission processing
- Stockist and sponsor commission management
- Real-time commission preview
- Transaction tracking and reporting

### Key Features

✅ **Automatic Commission Processing** - Commissions are calculated and distributed automatically when a sale is recorded  
✅ **10-Level MLM Network** - Supports up to 10 levels of network hierarchy (GN1-GN10)  
✅ **Duplicate Prevention** - Built-in safeguards against duplicate commission payments  
✅ **Real-time Validation** - Sponsor and stockist validation before sale submission  
✅ **Live Commission Preview** - Interactive form showing commission breakdown before submission  
✅ **Comprehensive Logging** - Detailed logs for debugging and audit trails  
✅ **Transaction Safety** - Database transactions ensure data integrity  

---

## System Architecture

### Component Flow

```
User Submits Sale Form
        ↓
OrderedItemController (Form Validation)
        ↓
OrderedItem Model (saving hook)
        ↓
Validates: Sponsor, Stockist, Product
        ↓
Calculates: unit_price, qty, subtotal
        ↓
OrderedItem Saved to Database
        ↓
OrderedItem Model (created hook)
        ↓
CommissionService::processCommission()
        ↓
Calculates commissions for:
  - Stockist (7%)
  - Sponsor (8%)
  - Parent Level 1-10 (3% to 0.2%)
        ↓
Creates AccountTransaction records
        ↓
Updates OrderedItem commission fields
        ↓
Commission Processing Complete
```

---

## Commission Structure

### Commission Rates (as of December 2025)

| Level | Role | Rate | Description |
|-------|------|------|-------------|
| Stockist | Product Distributor | **7.0%** | Person who stocks/distributes the product |
| Sponsor | Seller | **8.0%** | Person who sold the product (direct seller) |
| GN1 | Parent Level 1 | **3.0%** | First level in sponsor's upline |
| GN2 | Parent Level 2 | **2.5%** | Second level in sponsor's upline |
| GN3 | Parent Level 3 | **2.0%** | Third level in sponsor's upline |
| GN4 | Parent Level 4 | **1.5%** | Fourth level in sponsor's upline |
| GN5 | Parent Level 5 | **1.0%** | Fifth level in sponsor's upline |
| GN6 | Parent Level 6 | **0.8%** | Sixth level in sponsor's upline |
| GN7 | Parent Level 7 | **0.6%** | Seventh level in sponsor's upline |
| GN8 | Parent Level 8 | **0.5%** | Eighth level in sponsor's upline |
| GN9 | Parent Level 9 | **0.4%** | Ninth level in sponsor's upline |
| GN10 | Parent Level 10 | **0.2%** | Tenth level in sponsor's upline |

**Total Maximum Commission:** 27.5% (if all levels exist)

### Commission Calculation Example

**Product Price:** UGX 100,000

```
Stockist Commission:  7% = UGX 7,000
Sponsor Commission:   8% = UGX 8,000
GN1 Commission:       3% = UGX 3,000
GN2 Commission:     2.5% = UGX 2,500
GN3 Commission:       2% = UGX 2,000
... (and so on for GN4-GN10)
────────────────────────────────────
Total Commission:         UGX 27,500
Balance (Company):        UGX 72,500
```

---

## Database Schema

### `ordered_items` Table

#### Primary Fields
```sql
id                          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT
created_at                  TIMESTAMP
updated_at                  TIMESTAMP
```

#### Product & Sale Information
```sql
product                     BIGINT           -- Product ID (foreign key to products table)
qty                         INT              -- Quantity sold (default: 1)
unit_price                  DECIMAL(10,2)    -- Price per unit
subtotal                    DECIMAL(10,2)    -- Total sale amount (unit_price × qty)
amount                      DECIMAL(10,2)    -- Legacy field (same as unit_price)
color                       VARCHAR(255)     -- Optional product variant
size                        VARCHAR(255)     -- Optional product variant
```

#### Sponsor & Stockist Information
```sql
sponsor_id                  VARCHAR(255)     -- Sponsor's DTEHM ID or Business Name
sponsor_user_id             BIGINT           -- Sponsor's user ID (foreign key to users)
stockist_id                 VARCHAR(255)     -- Stockist's DTEHM ID or Business Name
stockist_user_id            BIGINT           -- Stockist's user ID (foreign key to users)
```

#### DTEHM Seller Information
```sql
has_detehm_seller           ENUM('Yes','No') -- Whether sale has a DTEHM seller
dtehm_seller_id             VARCHAR(255)     -- Seller's DTEHM ID
dtehm_user_id               BIGINT           -- Seller's user ID (foreign key to users)
```

#### Payment Tracking
```sql
item_is_paid                ENUM('Yes','No') -- Payment status
item_paid_date              TIMESTAMP        -- Date payment received
item_paid_amount            DECIMAL(10,2)    -- Amount paid
```

#### Commission Processing Status
```sql
commission_is_processed     ENUM('Yes','No') -- Whether commission has been processed
commission_processed_date   TIMESTAMP        -- Date commission was processed
total_commission_amount     DECIMAL(10,2)    -- Total commission distributed
balance_after_commission    DECIMAL(10,2)    -- Remaining balance after commissions
```

#### Commission Amounts by Level
```sql
commission_stockist         DECIMAL(10,2)    -- Stockist commission amount
commission_seller           DECIMAL(10,2)    -- Sponsor/seller commission amount
commission_parent_1         DECIMAL(10,2)    -- GN1 commission
commission_parent_2         DECIMAL(10,2)    -- GN2 commission
commission_parent_3         DECIMAL(10,2)    -- GN3 commission
commission_parent_4         DECIMAL(10,2)    -- GN4 commission
commission_parent_5         DECIMAL(10,2)    -- GN5 commission
commission_parent_6         DECIMAL(10,2)    -- GN6 commission
commission_parent_7         DECIMAL(10,2)    -- GN7 commission
commission_parent_8         DECIMAL(10,2)    -- GN8 commission
commission_parent_9         DECIMAL(10,2)    -- GN9 commission
commission_parent_10        DECIMAL(10,2)    -- GN10 commission
```

#### Parent User IDs (for tracking)
```sql
parent_1_user_id            BIGINT           -- GN1 user ID
parent_2_user_id            BIGINT           -- GN2 user ID
parent_3_user_id            BIGINT           -- GN3 user ID
parent_4_user_id            BIGINT           -- GN4 user ID
parent_5_user_id            BIGINT           -- GN5 user ID
parent_6_user_id            BIGINT           -- GN6 user ID
parent_7_user_id            BIGINT           -- GN7 user ID
parent_8_user_id            BIGINT           -- GN8 user ID
parent_9_user_id            BIGINT           -- GN9 user ID
parent_10_user_id           BIGINT           -- GN10 user ID
```

#### Legacy Field
```sql
order                       BIGINT           -- Optional: Reference to old Order model (deprecated)
```

---

## Core Components

### 1. OrderedItem Model
**Location:** `app/Models/OrderedItem.php`

**Purpose:** Represents a product sale record with automatic commission processing

**Key Methods:**
- `boot()` - Registers model event hooks
- `do_process_commission()` - Triggers commission calculation
- `pro()` - Relationship to Product model

**Model Hooks:**

**`creating` Hook:**
- Sets `commission_is_processed = 'No'` on new records

**`saving` Hook:**
- Validates sponsor ID exists and is active DTEHM member
- Validates stockist ID exists and is active DTEHM member
- Validates product exists
- Resolves user IDs from DTEHM IDs
- Fetches product price if not provided
- Calculates subtotal (unit_price × qty)
- Sets all required fields

**`created` & `updated` Hooks:**
- Automatically calls `do_process_commission()` after save

---

### 2. CommissionService
**Location:** `app/Services/CommissionService.php`

**Purpose:** Handles all commission calculation and distribution logic

**Key Methods:**

#### `processCommission(OrderedItem $orderedItem)`
Main commission processing method. Returns array with:
```php
[
    'success' => true/false,
    'message' => 'Status message',
    'item_id' => 123,
    'total_commission' => 27500.00,
    'beneficiaries' => 12,
    'commissions' => [
        ['level' => 'stockist', 'user_id' => 47, 'amount' => 7000.00],
        ['level' => 'sponsor', 'user_id' => 47, 'amount' => 8000.00],
        // ... parent levels
    ]
]
```

**Processing Steps:**
1. Validates commission not already processed
2. Validates DTEHM seller exists
3. Validates subtotal amount
4. Begins database transaction
5. Processes stockist commission (7%)
6. Processes sponsor commission (8%)
7. Loops through 10 parent levels
8. Creates AccountTransaction for each commission
9. Updates OrderedItem commission fields
10. Commits transaction
11. Returns result

**Error Handling:**
- Rolls back transaction on any error
- Logs all processing steps
- Returns detailed error information

#### `calculateCommission($amount, $percentage)`
Simple commission calculator:
```php
return round(($amount * $percentage) / 100, 2);
```

#### `createCommissionTransaction(...)`
Creates AccountTransaction record with:
- Professional narration (no emojis)
- Product details
- Commission rate and amount
- Sale reference
- Timestamp
- Duplicate prevention check

---

### 3. OrderedItemController
**Location:** `app/Admin/Controllers/OrderedItemController.php`

**Purpose:** Admin panel interface for managing product sales

**Key Methods:**

#### `grid()`
Displays list of all product sales with:
- Filters (product, sale ID, dates, price range)
- Export functionality
- Product images and details
- Sponsor/stockist information
- Commission status
- Payment status

#### `detail($id)`
Shows detailed view of a single sale with:
- Product information with image
- Sale breakdown (quantity, unit price, subtotal)
- Commission distribution (10-level breakdown)
- Commission status and processing date
- Beneficiary details for each level

#### `form()`
Interactive form for creating new sales with:
- Product dropdown selection
- Sponsor ID input (with validation)
- Stockist ID input (with validation)
- Live commission preview
- Real-time error display
- AJAX validation
- Auto-calculated fields

**Form Features:**
- **Live Preview:** Shows commission breakdown as you type
- **Validation:** Checks sponsor/stockist before submission
- **Error Display:** Clear error messages with field highlighting
- **Auto-calculation:** Automatically sets prices and totals
- **Commission Table:** Shows all 12 commission levels (stockist, sponsor, GN1-GN10)

---

## API Endpoints

### Calculate Commissions (AJAX)

**Endpoint:** `POST /api/ajax/calculate-commissions`

**Purpose:** Validates sponsor/stockist and calculates commission preview

**Request Body:**
```json
{
    "product_id": 18,
    "sponsor_id": "DIP001",
    "stockist_id": "DIP001"
}
```

**Success Response (200):**
```json
{
    "product": {
        "id": 18,
        "name": "Premium Health Insurance",
        "price": 35000.00
    },
    "sponsor": {
        "id": 47,
        "name": "John Doe",
        "business_name": "DIP001",
        "dtehm_member_id": "DTEHM001"
    },
    "stockist": {
        "id": 47,
        "name": "John Doe",
        "business_name": "DIP001",
        "dtehm_member_id": "DTEHM001"
    },
    "commissions": {
        "stockist": {
            "level": "Stockist",
            "rate": 7.0,
            "amount": 2450.00,
            "member": { ... }
        },
        "sponsor": {
            "level": "Sponsor",
            "rate": 8.0,
            "amount": 2800.00,
            "member": { ... }
        },
        "gn1": {
            "level": "Gn1",
            "rate": 3.0,
            "amount": 1050.00,
            "member": { ... } // or null if no parent
        },
        // ... gn2 through gn10
    },
    "total_commission": 9625.00,
    "balance": 25375.00
}
```

**Error Responses:**

**400 Bad Request:**
```json
{
    "error": "Missing required fields"
}
```

```json
{
    "error": "Invalid sponsor - must be a DTEHM member"
}
```

```json
{
    "error": "Invalid stockist - must be a DTEHM member"
}
```

**404 Not Found:**
```json
{
    "error": "Product not found"
}
```

---

## Usage Guide

### Creating a Product Sale (Admin Panel)

1. **Navigate to Product Sales**
   - Go to Admin Panel → Product Sales
   - Click "New" button

2. **Select Product**
   - Choose product from dropdown
   - Price is displayed next to product name

3. **Enter Sponsor ID**
   - Enter DTEHM Member ID (e.g., `DTEHM001`)
   - OR Business Name/DIP ID (e.g., `DIP001`)
   - System validates in real-time

4. **Enter Stockist ID**
   - Enter DTEHM Member ID or Business Name
   - System validates in real-time

5. **Review Commission Preview**
   - Commission breakdown appears automatically
   - Shows all levels with beneficiary names
   - Displays total commission and balance

6. **Submit Sale**
   - Click "Submit" button
   - Commission processes automatically
   - Success message appears

### Viewing Commission Details

1. **From Sales List**
   - Click on any sale ID
   - View detailed commission breakdown

2. **Commission Information Shown:**
   - Sale information (product, quantity, amount)
   - Commission status (processed/pending)
   - Beneficiary details for each level
   - Individual commission amounts
   - Total commission distributed
   - Balance remaining

---

## Validation Rules

### Sponsor Validation
```php
// Must provide sponsor_id
if (empty($sponsor_id)) {
    throw new Exception("Sponsor ID is required");
}

// Must exist in database
$sponsor = User::where('dtehm_member_id', $sponsor_id)
    ->orWhere('business_name', $sponsor_id)
    ->first();

if (!$sponsor) {
    throw new Exception("Sponsor not found for ID: {$sponsor_id}");
}

// Must be active DTEHM member
if ($sponsor->is_dtehm_member !== 'Yes') {
    throw new Exception("Sponsor is not an active DTEHM member");
}
```

### Stockist Validation
```php
// Same validation rules as Sponsor
if (empty($stockist_id)) {
    throw new Exception("Stockist ID is required");
}

$stockist = User::where('dtehm_member_id', $stockist_id)
    ->orWhere('business_name', $stockist_id)
    ->first();

if (!$stockist) {
    throw new Exception("Stockist not found for ID: {$stockist_id}");
}

if ($stockist->is_dtehm_member !== 'Yes') {
    throw new Exception("Stockist is not an active DTEHM member");
}
```

### Product Validation
```php
if (empty($product_id)) {
    throw new Exception("Product ID is required");
}

$product = Product::find($product_id);

if (!$product) {
    throw new Exception("Product not found for ID: {$product_id}");
}
```

### Price Validation
```php
$unit_price = floatval($unit_price);

if ($unit_price <= 0) {
    throw new Exception("Invalid product price: {$unit_price}");
}
```

### Quantity Validation
```php
$quantity = floatval($qty);

if ($quantity <= 0) {
    $quantity = 1; // Default to 1
}
```

---

## Error Handling

### Commission Processing Errors

**Duplicate Commission Prevention:**
```php
// Check if commission already exists
$existingTransaction = AccountTransaction::where('user_id', $user_id)
    ->where('commission_type', $commission_type)
    ->where('commission_reference_id', $ordered_item_id)
    ->first();

if ($existingTransaction) {
    Log::warning("Duplicate commission detected - skipping");
    return $existingTransaction;
}
```

**Transaction Safety:**
```php
DB::beginTransaction();
try {
    // Process commissions
    // Create transactions
    // Update ordered_item
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    Log::error("Commission processing failed", [
        'error' => $e->getMessage()
    ]);
    return ['success' => false, 'message' => $e->getMessage()];
}
```

**Model Hook Safety:**
```php
try {
    $result = $commissionService->processCommission($model);
} catch (Exception $e) {
    Log::error("Commission exception", ['error' => $e->getMessage()]);
    // Don't throw - allow sale to complete even if commission fails
}
```

### Form Validation Errors

**Frontend Validation:**
- Real-time AJAX validation
- Error messages displayed inline
- Field highlighting for invalid inputs
- Clear error descriptions

**Backend Validation:**
- Laravel form validation
- Model-level validation in saving hook
- Prevents invalid data from being saved
- Returns user to form with error message

---

## Testing

### Test Scenarios

#### 1. Valid Sale Creation
```php
// Create sale with valid data
$orderedItem = OrderedItem::create([
    'product' => 18,
    'sponsor_id' => 'DIP0046',
    'stockist_id' => 'DIP0046',
    'qty' => 1,
]);

// Verify commission processed
assert($orderedItem->commission_is_processed === 'Yes');
assert($orderedItem->total_commission_amount > 0);
```

#### 2. Invalid Sponsor
```php
try {
    $orderedItem = OrderedItem::create([
        'product' => 18,
        'sponsor_id' => 'INVALID_ID',
        'stockist_id' => 'DIP0046',
    ]);
    fail('Should have thrown exception');
} catch (Exception $e) {
    assert(str_contains($e->getMessage(), 'Sponsor not found'));
}
```

#### 3. Duplicate Commission Prevention
```php
// Process commission twice
$service = new CommissionService();
$result1 = $service->processCommission($orderedItem);
$result2 = $service->processCommission($orderedItem);

// Second attempt should fail
assert($result2['success'] === false);
assert(str_contains($result2['message'], 'already processed'));
```

#### 4. Network Hierarchy
```php
// Create 10-level network
$parent10 = User::factory()->create(['is_dtehm_member' => 'Yes']);
$parent9 = User::factory()->create(['is_dtehm_member' => 'Yes', 'parent_1' => $parent10->id]);
// ... create parent8 through parent1
$sponsor = User::factory()->create(['is_dtehm_member' => 'Yes', 'parent_1' => $parent1->id]);

// Create sale
$orderedItem = OrderedItem::create([
    'product' => 18,
    'sponsor_id' => $sponsor->business_name,
    'stockist_id' => $sponsor->business_name,
]);

// Verify all 10 levels received commission
assert($orderedItem->commission_parent_1 > 0);
assert($orderedItem->commission_parent_10 > 0);

// Verify commission amounts
$product = Product::find(18);
$expectedGn1 = ($product->price_1 * 3.0) / 100;
assert(abs($orderedItem->commission_parent_1 - $expectedGn1) < 0.01);
```

---

## Troubleshooting

### Common Issues

#### Issue 1: Form Not Submitting

**Symptoms:**
- Form appears to submit but nothing happens
- No error message shown
- Record not created

**Possible Causes:**
1. Debug statement (dd()) in code
2. Exception thrown without try-catch
3. JavaScript error in browser

**Solutions:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check browser console for JS errors
# Open browser DevTools → Console
```

#### Issue 2: Commission Not Processing

**Symptoms:**
- Sale created successfully
- `commission_is_processed = 'No'`
- No commission transactions created

**Possible Causes:**
1. `has_detehm_seller !== 'Yes'`
2. `dtehm_user_id` is null
3. Exception in commission processing

**Solutions:**
```php
// Check OrderedItem record
$item = OrderedItem::find($id);
dd([
    'has_detehm_seller' => $item->has_detehm_seller,
    'dtehm_user_id' => $item->dtehm_user_id,
    'sponsor_user_id' => $item->sponsor_user_id,
    'commission_is_processed' => $item->commission_is_processed,
]);

// Manually trigger commission
$service = new CommissionService();
$result = $service->processCommission($item);
dd($result);

// Check logs
grep "Commission" storage/logs/laravel.log
```

#### Issue 3: Duplicate Commissions

**Symptoms:**
- Multiple commission transactions for same sale
- Users receiving commission multiple times

**Possible Causes:**
1. Commission processing called multiple times
2. Duplicate prevention not working
3. Race condition

**Solutions:**
```php
// Check for duplicates
$transactions = AccountTransaction::where('commission_reference_id', $item_id)
    ->where('commission_type', 'product_commission_sponsor')
    ->get();

if ($transactions->count() > 1) {
    // Delete duplicates (keep oldest)
    $keep = $transactions->first();
    $transactions->skip(1)->each(function($tx) {
        $tx->delete();
    });
}

// Ensure commission_is_processed is set correctly
DB::table('ordered_items')
    ->where('id', $item_id)
    ->update(['commission_is_processed' => 'Yes']);
```

#### Issue 4: Invalid Commission Amounts

**Symptoms:**
- Commission amounts don't match expected rates
- Total commission exceeds sale amount
- Negative commission amounts

**Possible Causes:**
1. Commission rates changed but not updated in code
2. Calculation error
3. Invalid product price

**Solutions:**
```php
// Verify commission rates
$rates = CommissionService::COMMISSION_RATES;
dd($rates);

// Test calculation
$price = 100000;
$rate = 8.0;
$expected = ($price * $rate) / 100; // 8000
$actual = (new CommissionService())->calculateCommission($price, $rate);
assert($expected === $actual);

// Check product price
$product = Product::find($product_id);
if ($product->price_1 <= 0) {
    // Product has invalid price
}
```

#### Issue 5: Sponsor/Stockist Not Found

**Symptoms:**
- Error: "Sponsor not found for ID: XXX"
- Form validation fails

**Possible Causes:**
1. Incorrect ID format
2. User doesn't exist
3. User is not DTEHM member

**Solutions:**
```php
// Check user exists
$user = User::where('business_name', 'DIP0046')
    ->orWhere('dtehm_member_id', 'DIP0046')
    ->first();

if (!$user) {
    // User doesn't exist
}

if ($user->is_dtehm_member !== 'Yes') {
    // User is not DTEHM member
    // Update user: $user->update(['is_dtehm_member' => 'Yes']);
}
```

### Debug Commands

```bash
# Check OrderedItem records
php artisan tinker
>>> OrderedItem::latest()->first()

# Check commission transactions
>>> AccountTransaction::where('source', 'product_commission')->latest()->get()

# Check user network hierarchy
>>> $user = User::find(47);
>>> $user->parent_1; // First parent ID
>>> User::find($user->parent_1)->name; // Parent name

# Reprocess commission for item
>>> $item = OrderedItem::find(123);
>>> $service = new App\Services\CommissionService();
>>> $result = $service->processCommission($item);
>>> print_r($result);

# Check logs in real-time
tail -f storage/logs/laravel.log | grep Commission
```

---

## System Requirements

### Dependencies
- Laravel 8.x or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Encore Laravel Admin package

### Required Tables
- `ordered_items`
- `products`
- `users`
- `account_transactions`

### Required User Fields
- `id`
- `name`
- `business_name`
- `dtehm_member_id`
- `is_dtehm_member`
- `parent_1` through `parent_10`

---

## Maintenance

### Regular Tasks

**Weekly:**
- Review commission processing logs
- Check for failed commission attempts
- Verify commission totals match sales

**Monthly:**
- Audit commission transactions
- Compare commission rates with business rules
- Review network hierarchy integrity

### Performance Optimization

**Database Indexes:**
```sql
-- Add indexes for faster queries
CREATE INDEX idx_ordered_items_sponsor ON ordered_items(sponsor_user_id);
CREATE INDEX idx_ordered_items_stockist ON ordered_items(stockist_user_id);
CREATE INDEX idx_ordered_items_commission_status ON ordered_items(commission_is_processed);
CREATE INDEX idx_account_transactions_commission ON account_transactions(commission_reference_id, commission_type);
```

**Query Optimization:**
```php
// Use eager loading
$items = OrderedItem::with('pro')->get();

// Use select to limit fields
$items = OrderedItem::select('id', 'product', 'subtotal', 'commission_is_processed')->get();
```

---

## Change Log

### Version 2.0 (December 2025)
- ✅ Removed Order model dependency
- ✅ Added comprehensive validation
- ✅ Improved error handling
- ✅ Added duplicate prevention
- ✅ Enhanced logging
- ✅ Removed debug statements (dd)
- ✅ Professional transaction narrations
- ✅ Complete documentation

### Version 1.0 (November 2025)
- Initial implementation
- Basic commission processing
- Admin panel interface
- Live commission preview

---

## Support

For technical support or questions:

1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Contact development team
4. Create issue ticket with details:
   - OrderedItem ID
   - Error message
   - Log excerpts
   - Steps to reproduce

---

## License

This module is proprietary software developed for DTEHM Insurance System.  
© 2025 DTEHM. All rights reserved.
