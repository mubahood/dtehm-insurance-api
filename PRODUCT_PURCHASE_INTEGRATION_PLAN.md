# 🛍️ Product Purchase Integration Plan - Mobile App
## DTEHM Health Ministries Insurance System

**Date:** 15 December 2025  
**Objective:** Integrate direct product purchasing into the mobile app with Pesapal payment gateway

---

## 📋 Current System Analysis

### Backend Infrastructure (Already Implemented)
✅ **Product Management:**
- `Product` model with pricing, stock, categories
- `ProductCategory` for product organization
- `Image` model for product photos
- Product attributes and specifications system

✅ **Order Management:**
- `OrderedItem` model - represents individual product sales
- Comprehensive commission system (sponsor + stockist + 10-level hierarchy)
- Payment tracking fields (`item_is_paid`, `item_paid_date`, `item_paid_amount`)
- Commission calculation and processing

✅ **Payment Infrastructure:**
- `UniversalPayment` model - unified payment handling
- Pesapal integration via `PesapalService` and `PesapalApiClient`
- Payment types: insurance, membership, investment, **products**
- IPN (Instant Payment Notification) callbacks
- Payment status tracking and verification

✅ **Mobile APIs (Partial):**
- `MobileProductController` - product listing and details (GET only)
- `MobileOrderController` - commission calculation, order creation (exists but needs integration)
- Product categories endpoint

### Mobile App Infrastructure (Already Implemented)
✅ **Models:**
- `Product.dart` - full product model with offline support
- `OrderOnline.dart` - order management
- `CartItem.dart` - cart functionality
- `UniversalPayment.dart` - payment tracking

✅ **Services:**
- `ProductService.dart` - product data management
- `PesapalService.dart` - payment gateway integration
- `OrderService.dart` - order management
- `ApiService.dart` - HTTP client

✅ **Screens:**
- `ProductsScreen.dart` - product listing
- `ProductScreen.dart` - product details
- Various shop-related screens

---

## 🎯 Implementation Strategy

### Core Business Rule
> **A product sale (OrderedItem) is only created AFTER successful payment confirmation**

This means:
1. User browses products → No database record
2. User initiates purchase → `UniversalPayment` created (status: PENDING)
3. User completes Pesapal payment → Payment status updated to COMPLETED
4. System creates `OrderedItem` → Sale officially recorded
5. Commissions are calculated and distributed

---

## 🏗️ Implementation Plan

### PHASE 1: Backend API Development

#### 1.1 Create Product Purchase Controller
**File:** `app/Http/Controllers/ProductPurchaseController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\UniversalPayment;
use App\Models\OrderedItem;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseController extends Controller
{
    /**
     * Initialize product purchase (create pending payment)
     * POST /api/product-purchase/initialize
     */
    public function initialize(Request $request)
    {
        // Validate request
        // Get user authentication
        // Validate product availability and stock
        // Validate sponsor and stockist
        // Calculate total amount
        // Create UniversalPayment record (status: PENDING)
        // Initialize Pesapal payment
        // Return payment details + redirect URL
    }

    /**
     * Confirm product purchase after payment (IPN callback)
     * POST /api/product-purchase/confirm
     */
    public function confirm(Request $request)
    {
        // Verify payment with Pesapal
        // Find UniversalPayment by tracking ID
        // Update payment status to COMPLETED
        // Create OrderedItem (actual sale record)
        // Process commissions
        // Update product stock
        // Send notifications
        // Return success response
    }

    /**
     * Get user's product purchase history
     * GET /api/product-purchase/history
     */
    public function history(Request $request)
    {
        // Get user ID from auth
        // Query OrderedItems for this user
        // Include product details
        // Include payment status
        // Return paginated list
    }

    /**
     * Get single purchase details
     * GET /api/product-purchase/{id}
     */
    public function details($id)
    {
        // Find OrderedItem by ID
        // Verify user ownership
        // Include product, payment, commission details
        // Return full purchase information
    }
}
```

**Key Features:**
- User authentication via header (`User-Id`)
- Product stock validation
- Sponsor/Stockist validation (DTEHM members only)
- Atomic transactions for payment + order creation
- Comprehensive error handling

#### 1.2 Update UniversalPayment Model
**File:** `app/Models/UniversalPayment.php`

Add product-specific methods:

```php
/**
 * Process product purchase after payment confirmation
 */
public function processProductPurchase()
{
    if ($this->payment_type !== 'product') {
        throw new \Exception('Not a product payment');
    }

    if ($this->status !== 'COMPLETED') {
        throw new \Exception('Payment not completed');
    }

    if ($this->items_processed) {
        throw new \Exception('Product already processed');
    }

    DB::transaction(function () {
        foreach ($this->payment_items as $item) {
            // Create OrderedItem
            // Process commissions
            // Update stock
        }

        $this->update([
            'items_processed' => true,
            'items_processed_at' => now(),
        ]);
    });
}

/**
 * Check if this is a product payment
 */
public function isProductPayment()
{
    return $this->payment_type === 'product';
}
```

#### 1.3 Add API Routes
**File:** `routes/api.php`

```php
// Product Purchase Routes (Mobile App)
use App\Http\Controllers\ProductPurchaseController;

Route::prefix('product-purchase')->group(function () {
    // Initialize purchase (create payment + Pesapal redirect)
    Route::post('/initialize', [ProductPurchaseController::class, 'initialize']);
    
    // Confirm purchase (after Pesapal payment)
    Route::post('/confirm', [ProductPurchaseController::class, 'confirm']);
    
    // Get purchase history (requires User-Id header)
    Route::get('/history', [ProductPurchaseController::class, 'history']);
    
    // Get single purchase details
    Route::get('/{id}', [ProductPurchaseController::class, 'details']);
    
    // Cancel/Refund purchase (optional)
    Route::post('/{id}/cancel', [ProductPurchaseController::class, 'cancel']);
});

// Pesapal IPN callback for product purchases
Route::post('/product-purchase/pesapal/ipn', [ProductPurchaseController::class, 'pesapalIPN']);
```

#### 1.4 Database Migration (if needed)
**File:** `database/migrations/2025_12_15_create_product_orders_table.php`

```php
// Optional: Create dedicated product_orders table for better tracking
// OR use existing ordered_items table with payment_id link

Schema::create('product_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('product_id')->constrained('products');
    $table->foreignId('universal_payment_id')->nullable()->constrained('universal_payments');
    $table->integer('quantity')->default(1);
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_amount', 10, 2);
    $table->string('sponsor_id');
    $table->string('stockist_id');
    $table->foreignId('sponsor_user_id')->constrained('users');
    $table->foreignId('stockist_user_id')->constrained('users');
    $table->enum('status', ['PENDING', 'PAID', 'PROCESSING', 'COMPLETED', 'CANCELLED'])->default('PENDING');
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

**OR** Add column to existing `ordered_items` table:

```php
Schema::table('ordered_items', function (Blueprint $table) {
    $table->foreignId('universal_payment_id')->nullable()->constrained('universal_payments');
});
```

---

### PHASE 2: Mobile App Development (Flutter)

#### 2.1 Create Product Purchase Service
**File:** `lib/services/ProductPurchaseService.dart`

```dart
import 'package:dio/dio.dart';
import '../utils/AppConfig.dart';
import '../models/LoggedInUserModel.dart';
import '../models/Product.dart';
import '../models/UniversalPayment.dart';

class ProductPurchaseService {
  static final Dio _dio = Dio();

  /// Initialize product purchase
  /// Returns: { payment_id, redirect_url, order_tracking_id }
  static Future<Map<String, dynamic>> initializePurchase({
    required int productId,
    required int quantity,
    required String sponsorId,
    required String stockistId,
    required int userId,
  }) async {
    try {
      final response = await _dio.post(
        '${AppConfig.API_BASE_URL}/product-purchase/initialize',
        data: {
          'product_id': productId,
          'quantity': quantity,
          'sponsor_id': sponsorId,
          'stockist_id': stockistId,
          'user_id': userId,
          'payment_gateway': 'pesapal',
          'callback_url': '${AppConfig.API_BASE_URL}/product-purchase/confirm',
        },
        options: Options(
          headers: {
            'User-Id': userId.toString(),
            'Accept': 'application/json',
          },
        ),
      );

      if (response.data['success'] == true) {
        return {
          'success': true,
          'payment_id': response.data['data']['payment']['id'],
          'redirect_url': response.data['data']['pesapal']['redirect_url'],
          'order_tracking_id': response.data['data']['pesapal']['order_tracking_id'],
          'merchant_reference': response.data['data']['pesapal']['merchant_reference'],
        };
      } else {
        return {
          'success': false,
          'message': response.data['message'] ?? 'Failed to initialize purchase',
        };
      }
    } catch (e) {
      print('❌ Purchase initialization error: $e');
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }

  /// Check payment status
  static Future<Map<String, dynamic>> checkPaymentStatus({
    required int paymentId,
  }) async {
    try {
      final response = await _dio.get(
        '${AppConfig.API_BASE_URL}/universal-payments/$paymentId/status',
      );

      return {
        'success': true,
        'status': response.data['data']['status'],
        'is_paid': response.data['data']['status'] == 'COMPLETED',
        'payment': response.data['data'],
      };
    } catch (e) {
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  /// Get purchase history
  static Future<List<dynamic>> getPurchaseHistory({
    required int userId,
    int page = 1,
  }) async {
    try {
      final response = await _dio.get(
        '${AppConfig.API_BASE_URL}/product-purchase/history',
        queryParameters: {'page': page},
        options: Options(
          headers: {'User-Id': userId.toString()},
        ),
      );

      if (response.data['code'] == 1) {
        return response.data['data'] ?? [];
      }
      return [];
    } catch (e) {
      print('❌ Error loading purchase history: $e');
      return [];
    }
  }
}
```

#### 2.2 Create Product Purchase Screen
**File:** `lib/screens/shop/ProductPurchaseScreen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:get/get.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../models/Product.dart';
import '../../models/LoggedInUserModel.dart';
import '../../services/ProductPurchaseService.dart';
import '../../utils/AppConfig.dart';
import '../../utils/Utils.dart';

class ProductPurchaseScreen extends StatefulWidget {
  final Product product;
  final int quantity;
  
  const ProductPurchaseScreen({
    Key? key,
    required this.product,
    this.quantity = 1,
  }) : super(key: key);

  @override
  _ProductPurchaseScreenState createState() => _ProductPurchaseScreenState();
}

class _ProductPurchaseScreenState extends State<ProductPurchaseScreen> {
  final LoggedInUserModel user = LoggedInUserModel.getLoggedInUser();
  
  String sponsorId = '';
  String stockistId = '';
  bool isLoading = false;
  
  @override
  void initState() {
    super.initState();
    // Auto-fill sponsor and stockist from user profile
    sponsorId = user.sponsor_id ?? '';
    stockistId = user.sponsor_id ?? ''; // Default to same as sponsor
  }

  Future<void> _initiatePurchase() async {
    if (sponsorId.isEmpty || stockistId.isEmpty) {
      Utils.toast('Please select sponsor and stockist');
      return;
    }

    setState(() => isLoading = true);
    EasyLoading.show(status: 'Initializing payment...');

    final result = await ProductPurchaseService.initializePurchase(
      productId: widget.product.id,
      quantity: widget.quantity,
      sponsorId: sponsorId,
      stockistId: stockistId,
      userId: user.id,
    );

    EasyLoading.dismiss();
    setState(() => isLoading = false);

    if (result['success'] == true) {
      // Navigate to payment WebView
      Get.to(() => ProductPaymentWebView(
        redirectUrl: result['redirect_url'],
        paymentId: result['payment_id'],
        orderTrackingId: result['order_tracking_id'],
      ));
    } else {
      Utils.toast(result['message'] ?? 'Failed to initialize payment', color: Colors.red);
    }
  }

  @override
  Widget build(BuildContext context) {
    final totalAmount = double.parse(widget.product.price_1) * widget.quantity;

    return Scaffold(
      appBar: AppBar(
        title: Text('Purchase Confirmation'),
        backgroundColor: CustomTheme.primary,
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Product Summary
            _buildProductSummary(),
            SizedBox(height: 24),
            
            // Sponsor Selection
            _buildSponsorSelection(),
            SizedBox(height: 16),
            
            // Stockist Selection
            _buildStockistSelection(),
            SizedBox(height: 24),
            
            // Price Summary
            _buildPriceSummary(totalAmount),
            SizedBox(height: 32),
            
            // Purchase Button
            _buildPurchaseButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildPurchaseButton() {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton(
        onPressed: isLoading ? null : _initiatePurchase,
        style: ElevatedButton.styleFrom(
          backgroundColor: CustomTheme.primary,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
        ),
        child: Text(
          isLoading ? 'Processing...' : 'Proceed to Payment',
          style: TextStyle(fontSize: 16, color: Colors.white),
        ),
      ),
    );
  }
}
```

#### 2.3 Create Payment WebView Screen
**File:** `lib/screens/shop/ProductPaymentWebView.dart`

```dart
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:get/get.dart';
import '../../services/ProductPurchaseService.dart';
import '../../utils/Utils.dart';
import 'ProductPurchaseSuccessScreen.dart';

class ProductPaymentWebView extends StatefulWidget {
  final String redirectUrl;
  final int paymentId;
  final String orderTrackingId;

  const ProductPaymentWebView({
    Key? key,
    required this.redirectUrl,
    required this.paymentId,
    required this.orderTrackingId,
  }) : super(key: key);

  @override
  _ProductPaymentWebViewState createState() => _ProductPaymentWebViewState();
}

class _ProductPaymentWebViewState extends State<ProductPaymentWebView> {
  late WebViewController _controller;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() => isLoading = true);
          },
          onPageFinished: (String url) {
            setState(() => isLoading = false);
            _checkPaymentCallback(url);
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.redirectUrl));
  }

  Future<void> _checkPaymentCallback(String url) async {
    // Check if we're on callback URL
    if (url.contains('/product-purchase/confirm') || 
        url.contains('payment-callback') ||
        url.contains('OrderTrackingId=${widget.orderTrackingId}')) {
      
      // Wait a bit for backend to process
      await Future.delayed(Duration(seconds: 2));
      
      // Check payment status
      final status = await ProductPurchaseService.checkPaymentStatus(
        paymentId: widget.paymentId,
      );

      if (status['success'] == true && status['is_paid'] == true) {
        // Payment successful - navigate to success screen
        Get.off(() => ProductPurchaseSuccessScreen(
          paymentId: widget.paymentId,
          orderTrackingId: widget.orderTrackingId,
        ));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Complete Payment'),
        backgroundColor: CustomTheme.primary,
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (isLoading)
            Center(
              child: CircularProgressIndicator(),
            ),
        ],
      ),
    );
  }
}
```

#### 2.4 Update Product Detail Screen
**File:** `lib/screens/shop/ProductScreen.dart`

Add "Buy Now" button:

```dart
// In product detail screen
ElevatedButton(
  onPressed: () {
    if (product.in_stock != 'Yes') {
      Utils.toast('Product out of stock', color: Colors.red);
      return;
    }

    // Navigate to purchase screen
    Get.to(() => ProductPurchaseScreen(
      product: product,
      quantity: selectedQuantity,
    ));
  },
  child: Text('Buy Now - UGX ${formatPrice(product.price_1)}'),
)
```

#### 2.5 Create Purchase History Screen
**File:** `lib/screens/shop/ProductPurchaseHistoryScreen.dart`

```dart
// Display user's purchase history
// List all completed product purchases
// Show payment status, product details, order ID
// Option to view details or download receipt
```

---

### PHASE 3: Payment Flow Integration

#### 3.1 Payment Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ MOBILE APP - Product Purchase Flow                          │
└─────────────────────────────────────────────────────────────┘

1. User browses products → ProductsScreen
   ↓
2. User views product details → ProductScreen
   ↓
3. User taps "Buy Now" → ProductPurchaseScreen
   ↓
4. User selects sponsor & stockist
   ↓
5. App calls: POST /api/product-purchase/initialize
   ├── Creates UniversalPayment (PENDING)
   ├── Validates product, sponsor, stockist
   ├── Calls Pesapal API
   └── Returns: { payment_id, redirect_url }
   ↓
6. App opens Pesapal WebView → ProductPaymentWebView
   ↓
7. User completes payment on Pesapal
   ↓
8. Pesapal redirects to callback URL
   ↓
9. Backend receives IPN notification
   ├── Verifies payment with Pesapal
   ├── Updates UniversalPayment (COMPLETED)
   ├── Creates OrderedItem (OFFICIAL SALE)
   ├── Processes commissions
   ├── Updates product stock
   └── Sends notifications
   ↓
10. App detects callback URL
    ├── Checks payment status
    └── Shows success screen
    ↓
11. User sees confirmation → ProductPurchaseSuccessScreen
    └── Order details, receipt, commission breakdown
```

#### 3.2 Commission Processing

When `OrderedItem` is created (after payment):

```php
// In ProductPurchaseController@confirm
public function confirm(Request $request)
{
    DB::transaction(function () use ($payment) {
        // Create OrderedItem
        $orderedItem = OrderedItem::create([
            'order' => 'PROD_' . $payment->id,
            'product' => $productId,
            'qty' => $quantity,
            'unit_price' => $product->price,
            'subtotal' => $totalAmount,
            'sponsor_id' => $sponsorId,
            'stockist_id' => $stockistId,
            'sponsor_user_id' => $sponsor->id,
            'stockist_user_id' => $stockist->id,
            'item_is_paid' => 'Yes',
            'item_paid_date' => now(),
            'item_paid_amount' => $totalAmount,
            'universal_payment_id' => $payment->id,
        ]);

        // Auto-process commissions (if enabled)
        // OrderedItem has boot method that auto-calculates commissions
        // Commissions distributed to:
        // - Stockist (direct commission)
        // - Sponsor
        // - Parent 1-10 in hierarchy
    });
}
```

---

### PHASE 4: Testing & Validation

#### 4.1 Backend Testing Checklist

- [ ] Product stock validation
- [ ] Sponsor/Stockist DTEHM membership validation
- [ ] Payment initialization with Pesapal
- [ ] IPN callback handling
- [ ] OrderedItem creation after payment
- [ ] Commission calculation accuracy
- [ ] Stock deduction after sale
- [ ] Duplicate payment prevention
- [ ] Error handling for failed payments
- [ ] Refund/cancellation flow

#### 4.2 Mobile App Testing Checklist

- [ ] Product browsing and filtering
- [ ] Product detail display
- [ ] Sponsor/Stockist selection
- [ ] Payment initialization
- [ ] WebView Pesapal integration
- [ ] Payment callback detection
- [ ] Success screen display
- [ ] Purchase history retrieval
- [ ] Offline data handling
- [ ] Error message display

#### 4.3 Test Scenarios

1. **Happy Path:** User buys product → Payment successful → OrderedItem created
2. **Stock Validation:** Product out of stock → Purchase blocked
3. **Payment Failure:** User cancels payment → No OrderedItem created
4. **Network Error:** Lost connection during payment → Resume on reconnect
5. **Duplicate Prevention:** Same payment processed twice → Only one OrderedItem
6. **Commission Accuracy:** Verify commission distribution to all levels

---

## 📊 Database Schema Updates

### Option 1: Use Existing `ordered_items` Table

**Add column:**
```sql
ALTER TABLE ordered_items 
ADD COLUMN universal_payment_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (universal_payment_id) REFERENCES universal_payments(id);
```

**Advantages:**
- Minimal database changes
- Reuses existing commission system
- Consistent with current architecture

### Option 2: Create New `product_purchases` Table

**Advantages:**
- Cleaner separation between product sales and other orders
- Easier to track payment-to-sale relationship
- Better for analytics and reporting

**Recommendation:** Use Option 1 (existing table) for faster implementation

---

## 🔐 Security Considerations

1. **User Authentication:**
   - Validate `User-Id` header on all requests
   - Ensure user can only view their own purchases
   - Verify user is active DTEHM member

2. **Payment Verification:**
   - Always verify payment status with Pesapal before creating sale
   - Use merchant reference to prevent duplicates
   - Validate payment amount matches product price

3. **Stock Protection:**
   - Atomic transactions for stock updates
   - Prevent negative stock levels
   - Handle concurrent purchases

4. **Commission Integrity:**
   - Validate sponsor/stockist membership status
   - Ensure commission rates are from database (not client input)
   - Log all commission transactions for audit

---

## 📱 Mobile App Models Required

### 1. Product Purchase Model
```dart
class ProductPurchase {
  int id;
  int productId;
  String productName;
  int quantity;
  double unitPrice;
  double totalAmount;
  String sponsorId;
  String stockistId;
  String status; // PENDING, PAID, COMPLETED
  int paymentId;
  String orderTrackingId;
  DateTime createdAt;
  DateTime? paidAt;
}
```

### 2. Update UniversalPayment Model
```dart
class UniversalPayment {
  // Add fields
  bool isProductPayment;
  List<ProductPurchaseItem> productItems;
}
```

---

## 🎨 UI/UX Enhancements

1. **Product Cards:**
   - Add "Buy Now" button
   - Show stock availability badge
   - Display estimated commission

2. **Purchase Flow:**
   - Step indicator (Select → Review → Pay → Confirm)
   - Loading states for payment processing
   - Clear error messages

3. **Purchase History:**
   - Filter by date, status, product
   - Search functionality
   - Receipt download/share

4. **Notifications:**
   - Push notification on successful purchase
   - Commission earned notification to sponsor/stockist
   - Order status updates

---

## 📅 Implementation Timeline

**Week 1: Backend Development**
- Day 1-2: Create `ProductPurchaseController`
- Day 3: Update `UniversalPayment` model
- Day 4: Add API routes and test endpoints
- Day 5: Testing and bug fixes

**Week 2: Mobile App Development**
- Day 1-2: Create `ProductPurchaseService`
- Day 3-4: Build purchase screens (Purchase, WebView, Success)
- Day 5: Integrate with existing product screens

**Week 3: Integration & Testing**
- Day 1-2: End-to-end testing
- Day 3: Bug fixes and refinements
- Day 4: Performance optimization
- Day 5: User acceptance testing

**Week 4: Deployment**
- Day 1-2: Staging deployment and testing
- Day 3: Production deployment
- Day 4-5: Monitoring and support

---

## ✅ Success Criteria

1. **Functional:**
   - ✅ Users can browse and purchase products
   - ✅ Payment via Pesapal works seamlessly
   - ✅ OrderedItem created only after successful payment
   - ✅ Commissions calculated and distributed correctly
   - ✅ Stock levels update accurately

2. **Performance:**
   - ✅ Product listing loads < 2 seconds
   - ✅ Payment initialization < 3 seconds
   - ✅ Payment confirmation < 5 seconds

3. **User Experience:**
   - ✅ Intuitive purchase flow
   - ✅ Clear payment status feedback
   - ✅ Easy access to purchase history
   - ✅ Responsive error handling

---

## 🚀 Next Steps

1. **Review this plan** with the development team
2. **Create database migration** for `universal_payment_id` in `ordered_items`
3. **Implement `ProductPurchaseController`** with all endpoints
4. **Test payment flow** in sandbox environment
5. **Build mobile app screens** following the design
6. **Integrate Pesapal WebView** properly
7. **End-to-end testing** before production

---

## 📞 Support & Maintenance

- **Error Monitoring:** Log all payment failures for investigation
- **Analytics:** Track purchase conversion rate, popular products
- **User Support:** Provide clear purchase history and receipts
- **Refunds:** Implement refund process for failed/disputed orders

---

**Document Version:** 1.0  
**Last Updated:** 15 December 2025  
**Author:** Development Team  
**Status:** Ready for Implementation
