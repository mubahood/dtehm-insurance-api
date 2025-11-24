# System Configuration API Guide

## Overview

The System Configuration API provides dynamic settings for the DTEHM Insurance mobile application, allowing administrators to update values through the web portal without requiring app updates or recompilation.

## Why This System Exists

**Problem**: Previously, values like membership fees (76,000 UGX for DTEHM, 20,000 UGX for DIP) were hardcoded in the mobile app. Changing these required:
- Modifying app code
- Recompiling the app
- Deploying to app stores
- Waiting for user updates

**Solution**: All configurable values are now stored in the database and fetched via API, allowing instant updates from the admin panel.

## Features

- ✅ **No authentication required** for config endpoints (public data)
- ✅ **Cached for performance** (1 hour cache with auto-clear on updates)
- ✅ **Organized by category** (company, membership, insurance, investment, app, contact, legal)
- ✅ **Admin-friendly interface** with tabbed form in Laravel-Admin
- ✅ **Backward compatible** with default values

## Available Endpoints

### 1. Get All Configurations
```
GET /api/config
```

**Response:**
```json
{
  "code": 1,
  "message": "Configuration retrieved successfully",
  "data": {
    "company": {
      "name": "DTEHM Health Insurance",
      "email": "info@dtehmhealth.com",
      "phone": "+256 700 000 000",
      "address": null,
      "website": null,
      "logo": null,
      "details": null
    },
    "membership": {
      "dtehm_fee": 76000,
      "dip_fee": 20000,
      "both_fee": 96000,
      "currency": "UGX",
      "referral_bonus_percentage": 5,
      "dtehm_description": "Full network marketing privileges",
      "dip_description": "Basic membership"
    },
    "insurance": {
      "price": 60000,
      "start_date": "2025-01-01",
      "description": "Comprehensive health insurance coverage"
    },
    "investment": {
      "minimum_amount": 10000,
      "share_price": 50000,
      "description": "Investment in DTEHM cooperative shares"
    },
    "payment": {
      "gateway": "pesapal",
      "callback_url": "https://dip.dtehmhealth.com/api/pesapal/callback"
    },
    "app": {
      "version": "1.0.0",
      "force_update": false,
      "maintenance_mode": false,
      "maintenance_message": "System is under maintenance. Please try again later."
    },
    "contact": {
      "phone": "+256 700 000 000",
      "email": "support@dtehmhealth.com",
      "address": null
    },
    "social": {
      "facebook": null,
      "twitter": null,
      "instagram": null,
      "linkedin": null
    },
    "legal": {
      "terms": null,
      "privacy": null,
      "about": null
    }
  }
}
```

### 2. Get Membership Fees (Most Used)
```
GET /api/config/membership/fees
```

**Response:**
```json
{
  "code": 1,
  "message": "Membership fees retrieved",
  "data": {
    "dtehm_membership_fee": 76000,
    "dip_membership_fee": 20000,
    "both_membership_fee": 96000,
    "currency": "UGX",
    "dtehm_description": "Full network marketing privileges",
    "dip_description": "Basic membership"
  }
}
```

**Usage in Mobile App:**
```dart
// Replace hardcoded values
// OLD: final dtehm_fee = 76000;
// NEW:
final response = await http.get(Uri.parse('$baseUrl/config/membership/fees'));
final data = json.decode(response.body)['data'];
final dtehm_fee = data['dtehm_membership_fee'];
final dip_fee = data['dip_membership_fee'];
final currency = data['currency'];
```

### 3. Check Maintenance Mode
```
GET /api/config/check/maintenance
```

**Response:**
```json
{
  "code": 1,
  "message": "Maintenance status retrieved",
  "data": {
    "maintenance_mode": false,
    "message": "System is under maintenance. Please try again later."
  }
}
```

**Usage:**
```dart
// Check before allowing app usage
final response = await http.get(Uri.parse('$baseUrl/config/check/maintenance'));
final data = json.decode(response.body)['data'];
if (data['maintenance_mode'] == true) {
  showDialog(context, data['message']);
  return; // Block app usage
}
```

### 4. Check App Version
```
POST /api/config/check/version
Content-Type: application/json

{
  "version": "1.0.0"
}
```

**Response:**
```json
{
  "code": 1,
  "message": "Version check completed",
  "data": {
    "current_version": "1.0.0",
    "server_version": "1.0.0",
    "update_required": false,
    "force_update": false
  }
}
```

**Usage:**
```dart
// Check on app startup
final response = await http.post(
  Uri.parse('$baseUrl/config/check/version'),
  body: json.encode({'version': '1.0.0'}),
  headers: {'Content-Type': 'application/json'},
);
final data = json.decode(response.body)['data'];
if (data['force_update'] == true) {
  // Force user to update
  navigateToStore();
} else if (data['update_required'] == true) {
  // Show optional update dialog
  showUpdateDialog();
}
```

### 5. Get Specific Configuration Value
```
GET /api/config/{key}
```

**Examples:**
- `/api/config/currency` → Returns `{"code": 1, "data": {"key": "currency", "value": "UGX"}}`
- `/api/config/dtehm_membership_fee` → Returns `{"code": 1, "data": {"key": "dtehm_membership_fee", "value": 76000}}`
- `/api/config/app_version` → Returns `{"code": 1, "data": {"key": "app_version", "value": "1.0.0"}}`

## Admin Panel Usage

### Accessing Settings
1. Login to admin panel: http://localhost:8888/dtehm-insurance-api/public/admin
2. Navigate to: **System** → **System Configurations**
3. Click on the single configuration record (ID: 1)

### Tabbed Interface

**Tab 1: Company Information**
- Company Name
- Email, Phone, Address
- Website, Logo, Details

**Tab 2: Membership Fees** ⭐ Most Important
- DTEHM Membership Fee (default: 76,000 UGX)
- DIP Membership Fee (default: 20,000 UGX)
- Currency (UGX, USD, EUR, etc.)
- Referral Bonus Percentage
- Descriptions for each membership type

**Tab 3: Insurance Settings**
- Insurance Price (default: 60,000 UGX)
- Start Date
- Description

**Tab 4: Investment Settings**
- Minimum Investment Amount (default: 10,000 UGX)
- Share Price (default: 50,000 UGX)
- Description

**Tab 5: Payment Gateway**
- Gateway Name (pesapal)
- Callback URL

**Tab 6: App Settings**
- App Version (e.g., "1.0.0")
- Force Update (boolean)
- Maintenance Mode (boolean)
- Maintenance Message

**Tab 7: Contact Information**
- Contact Phone
- Contact Email
- Contact Address

**Tab 8: Social Media**
- Facebook URL
- Twitter URL
- Instagram URL
- LinkedIn URL

**Tab 9: Legal Documents**
- Terms and Conditions (long text)
- Privacy Policy (long text)
- About Us (long text)

### Changing Membership Fees

**Example: Increase DTEHM fee to 80,000 UGX**

1. Go to **Tab 2: Membership Fees**
2. Change **DTEHM Membership Fee** from `76000` to `80000`
3. Click **Submit**
4. **Done!** Mobile app will fetch new value immediately

**No app update required!** 🎉

## Mobile App Integration

### Step 1: Create ConfigService

Create `lib/services/config_service.dart`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ConfigService {
  static const String baseUrl = 'http://10.0.2.2:8888/dtehm-insurance-api/public/api';
  
  // Cache config data for 1 hour
  static Map<String, dynamic>? _cachedConfig;
  static DateTime? _cacheTime;
  
  static Future<Map<String, dynamic>> getAllConfig() async {
    // Return cached if less than 1 hour old
    if (_cachedConfig != null && _cacheTime != null) {
      if (DateTime.now().difference(_cacheTime!).inMinutes < 60) {
        return _cachedConfig!;
      }
    }
    
    final response = await http.get(Uri.parse('$baseUrl/config'));
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['code'] == 1) {
        _cachedConfig = json['data'];
        _cacheTime = DateTime.now();
        return _cachedConfig!;
      }
    }
    throw Exception('Failed to load configuration');
  }
  
  static Future<Map<String, dynamic>> getMembershipFees() async {
    final response = await http.get(Uri.parse('$baseUrl/config/membership/fees'));
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['code'] == 1) {
        return json['data'];
      }
    }
    throw Exception('Failed to load membership fees');
  }
  
  static Future<bool> checkMaintenance() async {
    final response = await http.get(Uri.parse('$baseUrl/config/check/maintenance'));
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['data']['maintenance_mode'] ?? false;
    }
    return false;
  }
  
  static Future<Map<String, dynamic>> checkVersion(String currentVersion) async {
    final response = await http.post(
      Uri.parse('$baseUrl/config/check/version'),
      body: jsonEncode({'version': currentVersion}),
      headers: {'Content-Type': 'application/json'},
    );
    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['code'] == 1) {
        return json['data'];
      }
    }
    throw Exception('Failed to check version');
  }
  
  static void clearCache() {
    _cachedConfig = null;
    _cacheTime = null;
  }
}
```

### Step 2: Update RegisterScreen

Replace hardcoded fees in `lib/screens/account/RegisterScreen.dart`:

```dart
// OLD CODE:
// final dtehm_fee = 76000;
// final dip_fee = 20000;

// NEW CODE:
Map<String, dynamic>? _membershipFees;

@override
void initState() {
  super.initState();
  _loadMembershipFees();
}

Future<void> _loadMembershipFees() async {
  try {
    final fees = await ConfigService.getMembershipFees();
    setState(() {
      _membershipFees = fees;
    });
  } catch (e) {
    print('❌ Error loading fees: $e');
    // Fallback to defaults
    setState(() {
      _membershipFees = {
        'dtehm_membership_fee': 76000,
        'dip_membership_fee': 20000,
        'currency': 'UGX',
      };
    });
  }
}

// Use in UI:
Text('DTEHM: ${_membershipFees?['dtehm_membership_fee'] ?? 76000} ${_membershipFees?['currency'] ?? 'UGX'}')
```

### Step 3: Update MembershipPaymentScreen

Similar changes in `lib/screens/membership/MembershipPaymentScreen.dart`:

```dart
// Replace all hardcoded 76000 and 20000 with dynamic values from ConfigService
```

### Step 4: Add Maintenance Check in main.dart

```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Check maintenance mode
  try {
    final inMaintenance = await ConfigService.checkMaintenance();
    if (inMaintenance) {
      runApp(MaintenanceApp());
      return;
    }
  } catch (e) {
    print('Failed to check maintenance: $e');
  }
  
  runApp(MyApp());
}
```

### Step 5: Add Version Check

```dart
class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    // Check version on startup
    _checkVersion();
    return MaterialApp(...);
  }
  
  Future<void> _checkVersion() async {
    try {
      final versionInfo = await ConfigService.checkVersion('1.0.0');
      if (versionInfo['force_update'] == true) {
        // Show dialog and block app
        showForceUpdateDialog();
      } else if (versionInfo['update_required'] == true) {
        // Show optional update
        showOptionalUpdateDialog();
      }
    } catch (e) {
      print('Version check failed: $e');
    }
  }
}
```

## Database Schema

Table: `system_configurations`

```sql
CREATE TABLE `system_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT 'DTEHM Health Insurance',
  `company_email` varchar(255) DEFAULT 'info@dtehmhealth.com',
  `company_phone` varchar(255) DEFAULT '+256 700 000 000',
  `company_address` text,
  `company_website` varchar(255),
  `company_logo` varchar(255),
  `company_details` text,
  
  -- Membership Settings
  `dtehm_membership_fee` int DEFAULT 76000,
  `dip_membership_fee` int DEFAULT 20000,
  `currency` varchar(10) DEFAULT 'UGX',
  `referral_bonus_percentage` decimal(5,2) DEFAULT 5.00,
  `dtehm_membership_description` text,
  `dip_membership_description` text,
  
  -- Insurance Settings
  `insurance_price` int DEFAULT 60000,
  `insurance_start_date` date DEFAULT '2025-01-01',
  `insurance_description` text,
  
  -- Investment Settings
  `minimum_investment_amount` int DEFAULT 10000,
  `share_price` int DEFAULT 50000,
  `investment_description` text,
  
  -- App Settings
  `app_version` varchar(20) DEFAULT '1.0.0',
  `force_update` tinyint(1) DEFAULT 0,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `maintenance_message` text,
  
  -- Contact Information
  `contact_phone` varchar(255),
  `contact_email` varchar(255),
  `contact_address` text,
  
  -- Social Media
  `social_facebook` varchar(255),
  `social_twitter` varchar(255),
  `social_instagram` varchar(255),
  `social_linkedin` varchar(255),
  
  -- Payment Gateway
  `payment_gateway` varchar(50) DEFAULT 'pesapal',
  `payment_callback_url` text,
  
  -- Legal Documents
  `terms_and_conditions` longtext,
  `privacy_policy` longtext,
  `about_us` text,
  
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

## Caching Mechanism

**Backend Caching:**
- Uses Laravel Cache facade
- Cache key: `system_configuration`
- TTL: 1 hour (3600 seconds)
- Auto-clears on save/delete

**Mobile App Caching:**
- Uses in-memory cache
- TTL: 1 hour
- Manual clear with `ConfigService.clearCache()`

## Testing

```bash
# Test all endpoints
cd /Applications/MAMP/htdocs/dtehm-insurance-api

# 1. Get all config
curl http://localhost:8888/dtehm-insurance-api/public/api/config

# 2. Get membership fees
curl http://localhost:8888/dtehm-insurance-api/public/api/config/membership/fees

# 3. Check maintenance
curl http://localhost:8888/dtehm-insurance-api/public/api/config/check/maintenance

# 4. Get specific value
curl http://localhost:8888/dtehm-insurance-api/public/api/config/currency

# 5. Check version
curl -X POST http://localhost:8888/dtehm-insurance-api/public/api/config/check/version \
  -H "Content-Type: application/json" \
  -d '{"version":"1.0.0"}'
```

## Production Deployment

### Backend Steps:
1. Pull latest code with system configuration files
2. Run migration to add columns:
   ```bash
   php artisan migrate
   ```
3. Initialize default values:
   ```sql
   INSERT INTO system_configurations (...) VALUES (...);
   ```
4. Clear caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

### Mobile App Steps:
1. Create `ConfigService.dart`
2. Replace hardcoded values with API calls
3. Add maintenance check
4. Add version check
5. Update base URL to production: `https://dip.dtehmhealth.com/api`
6. Build and test

## Benefits Summary

✅ **No App Updates Required** - Change fees instantly from admin panel  
✅ **Centralized Control** - All settings in one place  
✅ **Cached for Speed** - No performance impact  
✅ **Backward Compatible** - Defaults ensure app works even if API fails  
✅ **Flexible** - Easy to add new configuration fields  
✅ **Maintenance Mode** - Can block app access when needed  
✅ **Version Control** - Force users to update if critical  
✅ **Multi-purpose** - Supports company info, legal docs, social links, etc.  

## Future Enhancements

- [ ] Add configuration history/audit trail
- [ ] Add A/B testing support (different fees for different users)
- [ ] Add regional pricing (different fees by country)
- [ ] Add promotional pricing (time-limited discounts)
- [ ] Add notification when config changes
- [ ] Add webhook support for real-time updates

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check API response codes (1 = success, 0 = error)
- Verify routes: `php artisan route:list | grep config`
- Clear caches if values don't update

---

**Created:** 2025-01-08  
**Version:** 1.0  
**Status:** Production Ready ✅
