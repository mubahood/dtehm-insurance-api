# Product Sales & Commission System - Complete Implementation TODO

**Date:** 24 November 2025  
**Last Updated:** 30 January 2025  
**Project:** DTEHM Insurance API & Mobile App

**Status:** Phase 1 & 2 COMPLETED ✅  
**Next:** Phase 3 - Mobile App UI Implementation

---

## Quick Links
- [API Documentation](MOBILE_APP_API_DOCUMENTATION.md)
- [Implementation Summary](MOBILE_APP_API_IMPLEMENTATION_SUMMARY.md)

---

## PHASE 1: API IMPLEMENTATION ✓

### 1.1 Database & Models ✓
- [x] Add sponsor_id and stockist_id to ordered_items table
- [x] Update OrderedItem model with fillable fields
- [x] Create migration for new fields

### 1.2 Commission Calculation API ✓
- [x] Create `/api/ajax/calculate-commissions` endpoint
- [x] Implement 8% stockist commission
- [x] Implement Gn1-Gn10 network commissions (3%, 2.5%, 2%, 1.5%, 1%, 0.8%, 0.6%, 0.5%, 0.4%, 0.2%)
- [x] Return sponsor hierarchy (parent_1 to parent_10)
- [x] Calculate total commission and balance
- [x] Validate sponsor and stockist IDs

### 1.3 Admin Panel - OrderedItem Form ✓
- [x] Add Product dropdown field
- [x] Add Sponsor ID text field with validation
- [x] Add Stockist ID text field with validation
- [x] Implement live AJAX commission calculation
- [x] Display commission breakdown table
- [x] Show Product Price, Total Commission, Balance
- [x] Auto-populate hidden fields (sponsor_user_id, stockist_user_id)

### 1.4 Admin Panel - OrderedItem Grid ✓
- [x] Add Sponsor info column
- [x] Add Stockist info column
- [x] Add Commission summary column (consolidated)
- [x] Update product display with image and price
- [x] Optimize columns and remove clutter

### 1.5 Admin Panel - OrderedItem Details Page ✓
- [x] Create custom detail view (ordered-item-details.blade.php)
- [x] Display sale information with product details
- [x] Show sponsor and stockist cards
- [x] Display complete commission breakdown table
- [x] Show network hierarchy (Stockist + Gn1-Gn10)
- [x] Calculate and display total commission and balance
- [x] Add commission structure explanation

---

## PHASE 2: MOBILE APP API ENDPOINTS ✅ COMPLETED

### 2.1 User Registration API Enhancement ✅ COMPLETED
- [x] **Endpoint:** `POST /api/users/register`
- [x] Add `sponsor_id` field (required)
- [x] Add `is_dtehm_member` field (Yes/No)
- [x] Add `is_dip_member` field (Yes/No)
- [x] Validate sponsor exists in system
- [x] Calculate total payment required:
  - DTEHM: 76,000 UGX
  - DIP: 20,000 UGX
  - Both: 96,000 UGX
- [x] Return payment details in response
- [x] Create user and return token with payment info

### 2.2 Membership Payment API ✅ COMPLETED
- [x] **Endpoint:** `POST /api/membership/initiate-payment`
- [x] Accept user_id, membership_type (dtehm, dip, both)
- [x] Calculate amount based on membership type
- [x] Integrate with payment gateway (Pesapal)
- [x] Return payment URL/reference
- [x] **Endpoint:** `POST /api/membership/confirm-payment`
- [x] Verify payment with payment gateway
- [x] Create DtehmMembership record if DTEHM member
- [x] Create MembershipPayment record
- [x] Update user status to Active
- [x] Generate DTEHM Member ID (DTEHM20250XXX)
- [x] Generate DIP Member ID (DIPXXXX)
- [x] Create sponsor commission (10,000 UGX for DTEHM referral)
- [x] **Endpoint:** `GET /api/membership/payment-status/{payment_id}`

### 2.3 Product Listing API ✅ COMPLETED
- [x] **Endpoint:** `GET /api/products/list`
- [x] Return all active products with:
  - Product ID, name, description
  - Price, feature photo
  - Category information
  - Stock quantity
- [x] Add pagination support
- [x] Add category filter
- [x] Add search functionality
- [x] Add sorting options

### 2.4 Product Details API ✅ COMPLETED
- [x] **Endpoint:** `GET /api/products/detail/{id}`
- [x] Return complete product details
- [x] Include product images gallery
- [x] Return related products
- [x] **Endpoint:** `GET /api/products/categories`

### 2.5 Product Purchase API (Order Creation) ✅ COMPLETED
- [x] **Endpoint:** `POST /api/orders/calculate-commission` (Preview)
- [x] **Endpoint:** `POST /api/orders/create`
- [x] Accept product_id, quantity, sponsor_id, stockist_id
- [x] Validate sponsor and stockist exist
- [x] Create Order record
- [x] Create OrderedItem record with commission fields
- [x] Calculate commission preview
- [x] Initiate payment with payment gateway (Pesapal)
- [x] Return order details and payment URL

### 2.6 Order Payment Confirmation API ✅ COMPLETED
- [x] **Endpoint:** `POST /api/orders/confirm-payment`
- [x] Verify payment with payment gateway
- [x] Update order status to Paid
- [x] Calculate and store all commissions:
  - Stockist commission (8%)
  - Gn1-Gn10 commissions
- [x] Create AccountTransaction records for each beneficiary
- [x] Update member balances
- [x] Update product stock quantity

### 2.7 User Orders History API ✅ COMPLETED
- [x] **Endpoint:** `GET /api/orders/my-orders`
- [x] **Endpoint:** `GET /api/orders/detail/{id}`
- [x] Return user's order history
- [x] Include order items with product details
- [x] Show sponsor and stockist information
- [x] Add pagination and filters

### 2.8 Commission Earnings API ✅ COMPLETED
- [x] **Endpoint:** `GET /api/user/commissions`
- [x] Return all commission transactions
- [x] Show commission source (Stockist, Gn1-Gn10, Membership)
- [x] Display order reference for each commission
- [x] Calculate total earnings
- [x] Add date range filters
- [x] Add commission type filters
- [x] **Endpoint:** `GET /api/user/balance`

### 2.9 Network Hierarchy API ✅ COMPLETED
- [x] **Endpoint:** `GET /api/user/network`
- [x] Return user's downline members (up to 10 levels)
- [x] Show direct referrals (Level 1)
- [x] Display network statistics
- [x] Show total team size by level
- [x] Count DTEHM and DIP members

---

## PHASE 3: MOBILE APP UI IMPLEMENTATION (Flutter)

### 3.1 Registration Screen Enhancement
- [ ] Add "Sponsor ID" text field (required)
- [ ] Add "Are you a DTEHM Member?" toggle (Yes/No)
- [ ] Add "Are you a DIP Member?" toggle (Yes/No)
- [ ] Show calculated membership fee dynamically:
  - DTEHM only: UGX 76,000
  - DIP only: UGX 20,000
  - Both: UGX 96,000
- [ ] Add "Total Payment Required" display
- [ ] Update registration flow to include membership payment
- [ ] Validate sponsor ID before proceeding

### 3.2 Membership Payment Screen (New)
- [ ] Create MembershipPaymentScreen
- [ ] Display membership type selected
- [ ] Show breakdown of fees
- [ ] Integrate Pesapal payment
- [ ] Show payment options (Mobile Money, Card)
- [ ] Handle payment success/failure
- [ ] Navigate to confirmation screen

### 3.3 Products Screen (New)
- [ ] Create ProductsScreen with grid/list view
- [ ] Display product cards with:
  - Product image
  - Product name
  - Price (UGX format)
  - Stock status
- [ ] Add search bar
- [ ] Add category filters
- [ ] Implement pull-to-refresh
- [ ] Add loading and error states

### 3.4 Product Details Screen (New)
- [ ] Create ProductDetailScreen
- [ ] Display full product information
- [ ] Show product image gallery (swipeable)
- [ ] Display price prominently
- [ ] Show stock availability
- [ ] Add quantity selector
- [ ] Add "Buy Now" button
- [ ] Show product description

### 3.5 Product Purchase Screen (New)
- [ ] Create ProductPurchaseScreen
- [ ] Show product summary
- [ ] Display quantity and total price
- [ ] Add "Sponsor ID" field (pre-filled if user has sponsor)
- [ ] Add "Stockist ID" field (required)
- [ ] Show commission preview:
  - Stockist commission (8%)
  - Your potential earnings (if in network)
- [ ] Add "Proceed to Payment" button
- [ ] Validate sponsor and stockist IDs

### 3.6 Order Payment Screen (New)
- [ ] Create OrderPaymentScreen
- [ ] Display order summary
- [ ] Show commission breakdown
- [ ] Integrate Pesapal payment
- [ ] Handle payment confirmation
- [ ] Navigate to order confirmation

### 3.7 Order Confirmation Screen (New)
- [ ] Create OrderConfirmationScreen
- [ ] Display success message
- [ ] Show order details
- [ ] Show commission earned (if applicable)
- [ ] Add "View Order" button
- [ ] Add "Continue Shopping" button

### 3.8 My Orders Screen (New)
- [ ] Create MyOrdersScreen
- [ ] Display order history in list
- [ ] Show order status (Pending, Completed, Cancelled)
- [ ] Display order date and total
- [ ] Add filter by status
- [ ] Implement pull-to-refresh
- [ ] Navigate to order details on tap

### 3.9 Order Details Screen (New)
- [ ] Create OrderDetailsScreen
- [ ] Display complete order information
- [ ] Show ordered items with images
- [ ] Display commission earned (if user in network)
- [ ] Show payment status
- [ ] Add action buttons (Track, Cancel if pending)

### 3.10 Commissions/Earnings Screen (New)
- [ ] Create CommissionsScreen
- [ ] Display total earnings prominently
- [ ] Show commission history in list
- [ ] Display commission type (Stockist, Gn1-Gn10)
- [ ] Show product name for each commission
- [ ] Display commission amount and date
- [ ] Add filter by date range
- [ ] Show earnings chart/graph

### 3.11 My Network Screen (New)
- [ ] Create MyNetworkScreen
- [ ] Display direct referrals count
- [ ] Show total team size
- [ ] Display network tree/hierarchy
- [ ] Show earnings from network
- [ ] Add "Invite Member" button with share link

### 3.12 Profile Screen Enhancement
- [ ] Add "Membership Status" section
- [ ] Display DTEHM Member ID (if member)
- [ ] Display DIP Member ID (if member)
- [ ] Show membership expiry dates
- [ ] Add "Renew Membership" button
- [ ] Display "My Sponsor" information
- [ ] Add navigation to My Orders
- [ ] Add navigation to Commissions
- [ ] Add navigation to My Network

---

## PHASE 4: STATE MANAGEMENT & SERVICES

### 4.1 API Service Layer
- [ ] Create `ProductService` class
  - [ ] getProducts()
  - [ ] getProductById()
  - [ ] searchProducts()
- [ ] Create `OrderService` class
  - [ ] createOrder()
  - [ ] confirmPayment()
  - [ ] getUserOrders()
  - [ ] getOrderById()
- [ ] Create `MembershipService` class
  - [ ] initiatePayment()
  - [ ] confirmPayment()
  - [ ] getMembershipStatus()
- [ ] Create `CommissionService` class
  - [ ] getCommissions()
  - [ ] getCommissionStats()
  - [ ] calculateCommissionPreview()
- [ ] Create `NetworkService` class
  - [ ] getNetworkHierarchy()
  - [ ] getNetworkStats()
  - [ ] getReferrals()

### 4.2 State Management (Provider/Riverpod)
- [ ] Create ProductProvider
- [ ] Create OrderProvider
- [ ] Create CommissionProvider
- [ ] Create NetworkProvider
- [ ] Create CartProvider (if needed)
- [ ] Update AuthProvider with membership fields

### 4.3 Models/DTOs
- [ ] Create Product model
- [ ] Create Order model
- [ ] Create OrderItem model
- [ ] Create Commission model
- [ ] Create NetworkMember model
- [ ] Create MembershipPayment model
- [ ] Update User model with membership fields

---

## PHASE 5: PAYMENT INTEGRATION

### 5.1 Pesapal Integration
- [ ] Add Pesapal SDK to Flutter app
- [ ] Create PesapalService class
- [ ] Implement initiate payment flow
- [ ] Implement payment callback handling
- [ ] Add payment verification
- [ ] Handle payment errors gracefully
- [ ] Test with test credentials

### 5.2 Payment UI Components
- [ ] Create PaymentMethodSelector widget
- [ ] Create PaymentProcessingDialog
- [ ] Create PaymentSuccessScreen
- [ ] Create PaymentFailedScreen

---

## PHASE 6: TESTING & VALIDATION

### 6.1 API Testing
- [ ] Test registration with membership payment
- [ ] Test product purchase flow
- [ ] Test commission calculation
- [ ] Test network hierarchy retrieval
- [ ] Test payment confirmation
- [ ] Test edge cases (invalid sponsor, out of stock, etc.)

### 6.2 Mobile App Testing
- [ ] Test complete registration flow
- [ ] Test product browsing and purchase
- [ ] Test payment integration
- [ ] Test commission display
- [ ] Test network hierarchy view
- [ ] Test on multiple devices (Android/iOS)
- [ ] Test with different screen sizes
- [ ] Test offline scenarios

### 6.3 User Acceptance Testing
- [ ] Test with real users
- [ ] Collect feedback
- [ ] Fix reported issues
- [ ] Optimize user experience

---

## PHASE 7: DOCUMENTATION & DEPLOYMENT

### 7.1 API Documentation
- [ ] Document all new endpoints
- [ ] Add request/response examples
- [ ] Document error codes
- [ ] Create Postman collection

### 7.2 Mobile App Documentation
- [ ] Update README with new features
- [ ] Document state management approach
- [ ] Add screenshots of new screens
- [ ] Create user guide

### 7.3 Deployment
- [ ] Deploy API updates to production
- [ ] Build and test mobile app release
- [ ] Submit to Play Store
- [ ] Submit to App Store
- [ ] Monitor for issues

---

## CURRENT STATUS
- **Phase 1 (API Implementation):** ✅ **COMPLETED**
- **Phase 2 (Mobile App APIs):** 🔄 **NEXT - IN PROGRESS**
- **Phase 3 (Mobile App UI):** ⏳ **PENDING**
- **Phase 4 (State Management):** ⏳ **PENDING**
- **Phase 5 (Payment Integration):** ⏳ **PENDING**
- **Phase 6 (Testing):** ⏳ **PENDING**
- **Phase 7 (Documentation):** ⏳ **PENDING**

---

## NOTES
- All commission rates are final: Stockist 8%, Gn1-10 as specified
- Membership fees: DTEHM 76,000 UGX, DIP 20,000 UGX
- Payment gateway: Pesapal
- Network hierarchy: 10 levels (parent_1 to parent_10)
- Sponsor commission: 10,000 UGX for DTEHM referral
