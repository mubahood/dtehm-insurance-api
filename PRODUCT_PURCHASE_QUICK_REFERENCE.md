# Product Purchase Quick Reference

## 🚀 Quick Start - Backend Setup Complete

### What's Ready
✅ API Controller: `ProductPurchaseController.php`  
✅ Routes: 7 endpoints under `/api/product-purchase/`  
✅ Database: Migration executed, `universal_payment_id` added to `ordered_items`  
✅ Models: Relationships added to `UniversalPayment` and `OrderedItem`  
✅ View: Pesapal success callback page  

---

## 📌 API Endpoints Summary

### Initialize Purchase
```
POST /api/product-purchase/initialize

Headers: { "User-Id": "123" }

Body: {
  "product_id": 1,
  "quantity": 1,
  "sponsor_id": "DTEHM20250001",
  "stockist_id": "DTEHM20250002",
  "user_id": 123
}

Returns: Pesapal redirect URL
```

### Confirm Purchase
```
POST /api/product-purchase/confirm

Body: { "payment_id": 456 }

Returns: OrderedItem details
```

### Purchase History
```
GET /api/product-purchase/history?per_page=20&page=1

Headers: { "User-Id": "123" }

Returns: Array of purchases
```

### Purchase Details
```
GET /api/product-purchase/{id}

Returns: Single purchase with full details
```

---

## 🔄 Purchase Flow (Mobile App Perspective)

```dart
// 1. User taps "Buy Now" on product
ProductPurchaseService.initialize({
  productId: product.id,
  quantity: 1,
  sponsorId: selectedSponsor,
  stockistId: selectedStockist,
  userId: currentUser.id
})

// 2. Get Pesapal URL
response = await http.post(...);
String pesapalUrl = response['data']['pesapal']['redirect_url'];

// 3. Open WebView
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (_) => PesapalWebView(url: pesapalUrl)
  )
);

// 4. Detect callback (WebView NavigationDelegate)
if (url.contains('/pesapal/callback')) {
  // Payment completed (Pesapal redirected)
  // Show success screen
  Navigator.pushReplacement(
    context,
    MaterialPageRoute(builder: (_) => PurchaseSuccessScreen())
  );
}

// 5. (Optional) Manually confirm payment
ProductPurchaseService.confirm(paymentId: paymentId);

// 6. View purchase history
List purchases = await ProductPurchaseService.getHistory();
```

---

## ✅ Validations Performed (Backend)

### Pre-Purchase (Initialize)
- Product exists
- Product has stock
- Sponsor is DTEHM member
- Stockist is DTEHM member
- User authenticated

### Post-Payment (Confirm)
- Payment verified with Pesapal
- Payment status = "Completed"
- OrderedItem not already created (idempotent)
- Stock available (double-check)

---

## 💡 Key Features

### Payment-First
OrderedItem only created AFTER Pesapal confirms payment

### Stock Safety
- Check at initialization
- Check again at confirmation
- Decrease within transaction
- Rollback on failure

### Commission Auto-Process
When OrderedItem created:
- Observer triggers automatically
- Sponsor commission calculated
- Stockist commission calculated
- 10-level hierarchy commissions
- User balances updated

### Error Handling
- Comprehensive try-catch blocks
- Detailed error messages
- Rollback on failures
- Extensive logging

---

## 🧪 Test with cURL

```bash
# Initialize
curl -X POST http://your-domain.com/api/product-purchase/initialize \
  -H "Content-Type: application/json" \
  -H "User-Id: 1" \
  -d '{
    "product_id": 1,
    "quantity": 1,
    "sponsor_id": "DTEHM20250001",
    "stockist_id": "DTEHM20250002",
    "user_id": 1
  }'

# Confirm
curl -X POST http://your-domain.com/api/product-purchase/confirm \
  -H "Content-Type: application/json" \
  -d '{"payment_id": 123}'

# History
curl -X GET http://your-domain.com/api/product-purchase/history \
  -H "User-Id: 1"

# Details
curl -X GET http://your-domain.com/api/product-purchase/456
```

---

## 📱 Flutter Integration Checklist

### Create Service
```dart
class ProductPurchaseService {
  static Future<Map> initialize({...}) async { }
  static Future<Map> confirm(int paymentId) async { }
  static Future<List> getHistory({int page = 1}) async { }
  static Future<Map> getDetails(int id) async { }
}
```

### Create Screens
- [ ] `ProductPurchaseScreen` - Product details + purchase form
- [ ] `PesapalWebViewScreen` - WebView for payment
- [ ] `PurchaseSuccessScreen` - Success animation
- [ ] `PurchaseHistoryScreen` - List of purchases
- [ ] `PurchaseDetailsScreen` - Single purchase details

### Add to Existing Product Listing
```dart
// In product_detail_screen.dart
ElevatedButton(
  onPressed: () => Navigator.push(
    context,
    MaterialPageRoute(
      builder: (_) => ProductPurchaseScreen(product: product)
    )
  ),
  child: Text("Buy Now")
)
```

---

## 🔍 Debugging

### Check if API is working
```bash
php artisan route:list | grep product-purchase
```

### Check logs
```bash
tail -f storage/logs/laravel.log
```

### Verify database
```sql
-- Check latest payment
SELECT * FROM universal_payments WHERE payment_type = 'product' ORDER BY created_at DESC LIMIT 1;

-- Check latest order
SELECT * FROM ordered_items WHERE universal_payment_id IS NOT NULL ORDER BY created_at DESC LIMIT 1;
```

---

## ⚠️ Important Notes

### Pesapal Configuration
Make sure `.env` has:
```
PESAPAL_CONSUMER_KEY=your_key
PESAPAL_CONSUMER_SECRET=your_secret
PESAPAL_ENVIRONMENT=sandbox  # or 'production'
PESAPAL_IPN_URL=https://your-domain.com/api/product-purchase/pesapal/ipn
```

### IPN URL Must Be Public
Pesapal webhook cannot reach localhost. Use:
- Ngrok for testing
- Public server for production

### User-Id Header
Mobile app must send `User-Id` header with every request:
```dart
headers: {
  'User-Id': currentUser.id.toString(),
  'Content-Type': 'application/json',
}
```

---

## 📊 Database Tables Involved

1. **universal_payments** - Payment records
2. **ordered_items** - Sale records (only after payment)
3. **products** - Product catalog (stock decreases)
4. **users** - Customer, sponsor, stockist
5. **account_transactions** - Commission records
6. **pesapal_transactions** - Pesapal tracking

---

## 🎯 Success Criteria

- [x] Backend API created and working
- [ ] Pesapal integration tested with real payment
- [ ] Commission calculation verified
- [ ] Stock management working
- [ ] Mobile app UI created
- [ ] End-to-end flow tested
- [ ] Error scenarios handled
- [ ] Production deployment

---

## 📞 Need Help?

### Common Issues

**"Sponsor not found"**  
→ Check sponsor exists in `users` table with `dtehm_member_id` or `business_name`

**"Insufficient stock"**  
→ Check `products.stock_quantity` is not null and > 0

**"Payment not completed"**  
→ Wait for Pesapal IPN or manually check payment status

**"Pesapal initialization failed"**  
→ Check `.env` credentials and internet connection

---

## 🚢 Deployment Steps

### Backend
1. Push code to server
2. Run migration: `php artisan migrate`
3. Clear cache: `php artisan cache:clear`
4. Test endpoints with Postman
5. Register IPN URL with Pesapal
6. Test with real payment

### Mobile App
1. Create Flutter screens
2. Integrate API service
3. Test on staging
4. Submit to app stores
5. Monitor for errors

---

**Status: Backend Ready for Mobile Integration ✅**

All backend components implemented and routes verified.  
Proceed with Flutter UI development.
