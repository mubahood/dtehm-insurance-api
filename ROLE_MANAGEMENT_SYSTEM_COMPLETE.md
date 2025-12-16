# Role Management System - Complete Documentation

## Overview
Complete role-based access control system with backend API and mobile app integration. Roles are automatically loaded when the home page refreshes.

---

## Backend API

### Endpoints

#### 1. Get User Roles
```
GET /api/user-roles
Headers: User-Id: {user_id}
```

**Response:**
```json
{
  "code": 1,
  "message": "User roles retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Super Admin",
      "slug": "admin",
      "created_at": null,
      "updated_at": "2025-11-12 07:23:39"
    }
  ]
}
```

#### 2. Check Specific Role
```
GET /api/user-roles/check/{slug}
Headers: User-Id: {user_id}
```

**Response:**
```json
{
  "code": 1,
  "message": "User has the role",
  "data": {
    "slug": "admin",
    "has_role": true
  }
}
```

### Controller: `UserRoleController.php`
- Location: `app/Http/Controllers/UserRoleController.php`
- Methods:
  - `index()` - Get all user roles
  - `checkRole($slug)` - Check if user has specific role
  - `getUserIdFromHeader()` - Extract user ID from request

---

## Mobile App

### 1. Role Model (`lib/models/Role.dart`)

#### Basic Usage
```dart
// Create from JSON
final role = Role.fromJson({
  'id': 1,
  'name': 'Super Admin',
  'slug': 'admin',
});

// Convert to JSON
final json = role.toJson();

// Parse list
final roles = Role.fromJsonList(jsonList);
```

### 2. RoleChecker Utility

#### Usage Examples
```dart
import 'package:nudipu/models/Role.dart';

final roles = [...]; // List of Role objects
final checker = RoleChecker(roles);

// Check single role
if (checker.hasRole('admin')) {
  // User is admin
}

// Check multiple roles (ANY)
if (checker.hasAnyRole(['admin', 'manager'])) {
  // User has at least one of these roles
}

// Check multiple roles (ALL)
if (checker.hasAllRoles(['admin', 'manager'])) {
  // User has all these roles
}

// Convenient getters
if (checker.isAdmin) { } // Check for 'admin' role
if (checker.isManager) { } // Check for 'manager' role
if (checker.isClient) { } // Check for 'client' role

// Get role by slug
final adminRole = checker.getRoleBySlug('admin');

// Get all slugs/names
final slugs = checker.roleSlugs;
final names = checker.roleNames;

// Check count
final count = checker.roleCount;
if (checker.hasNoRoles) { }
```

### 3. RoleService (`lib/services/role_service.dart`)

#### Service Methods
```dart
import 'package:nudipu/services/role_service.dart';

// Fetch roles from API (with caching)
final roles = await RoleService.fetchUserRoles();

// Force refresh (bypass cache)
final roles = await RoleService.refresh();

// Check if user has role
final isAdmin = await RoleService.hasRole('admin');

// Get RoleChecker instance
final checker = await RoleService.getRoleChecker();

// Get cached RoleChecker (synchronous, no API call)
final checker = RoleService.getCachedRoleChecker();

// Clear cache
RoleService.clearCache();

// Initialize (called automatically on app start)
await RoleService.initialize();
```

#### Caching Strategy
- Roles cached for 30 minutes
- Background refresh on home page load
- Fallback to stored data if API fails
- Automatic save to LoggedInUserModel

### 4. LoggedInUserModel Integration

#### Updated Model
```dart
import 'package:nudipu/models/LoggedInUserModel.dart';

final user = await LoggedInUserModel.getLoggedInUser();

// Access roles
final roles = user.roles; // List<Role>

// Role checking methods
if (user.hasRole('admin')) {
  // User has admin role
}

if (user.hasAnyRole(['admin', 'manager'])) {
  // User has at least one role
}

if (user.hasAllRoles(['admin', 'manager'])) {
  // User has all roles
}

// Get specific role
final adminRole = user.getRoleBySlug('admin');

// Convenient getters
if (user.isAdminRole) { } // Has 'admin' role
if (user.isManagerRole) { } // Has 'manager' role
if (user.isClientRole) { } // Has 'client' role

// Get role lists
final slugs = user.roleSlugs;
final names = user.roleNames;
final count = user.roleCount;

// Get RoleChecker
final checker = user.getRoleChecker();
```

---

## Integration Points

### 1. Automatic Loading on Home Page
Roles are automatically loaded in these screens:
- `InsuranceDashboard` - Insurance home screen
- `MainScreen` - Main app screen

**Implementation:**
```dart
// In initState or init method
RoleService.fetchUserRoles().catchError((e) {
  print('Failed to load roles: $e');
  return <Role>[];
});
```

### 2. Login Integration
Add to login success handler:
```dart
// After successful login
await RoleService.initialize();
```

### 3. Logout Integration
Add to logout handler:
```dart
// Before logout
RoleService.clearCache();
```

---

## Usage Examples

### Example 1: Show Admin-Only Feature
```dart
import 'package:nudipu/models/LoggedInUserModel.dart';

Widget build(BuildContext context) {
  return FutureBuilder(
    future: LoggedInUserModel.getLoggedInUser(),
    builder: (context, snapshot) {
      if (!snapshot.hasData) return SizedBox();
      
      final user = snapshot.data!;
      
      // Show admin panel only if user is admin
      if (user.isAdminRole) {
        return AdminPanel();
      }
      
      return Container();
    },
  );
}
```

### Example 2: Conditional Navigation
```dart
void navigateBasedOnRole() async {
  final user = await LoggedInUserModel.getLoggedInUser();
  
  if (user.hasRole('admin')) {
    Get.to(() => AdminDashboard());
  } else if (user.hasRole('manager')) {
    Get.to(() => ManagerDashboard());
  } else {
    Get.to(() => ClientDashboard());
  }
}
```

### Example 3: Menu Item Visibility
```dart
List<MenuItem> buildMenu() async {
  final user = await LoggedInUserModel.getLoggedInUser();
  final checker = user.getRoleChecker();
  
  final menu = <MenuItem>[];
  
  // Always visible
  menu.add(MenuItem('Home', Icons.home));
  
  // Admin only
  if (checker.isAdmin) {
    menu.add(MenuItem('Users', Icons.people));
    menu.add(MenuItem('Settings', Icons.settings));
  }
  
  // Manager or Admin
  if (checker.hasAnyRole(['admin', 'manager'])) {
    menu.add(MenuItem('Reports', Icons.bar_chart));
  }
  
  return menu;
}
```

### Example 4: Protect API Calls
```dart
Future<void> deleteUser(int userId) async {
  final user = await LoggedInUserModel.getLoggedInUser();
  
  if (!user.isAdminRole) {
    Utils.toast('You do not have permission for this action');
    return;
  }
  
  // Proceed with deletion
  final response = await Utils.http_delete('users/$userId', {});
  // ...
}
```

---

## Database Schema

### Tables Used

#### `admin_roles`
```sql
- id (int) - Primary key
- name (varchar) - Display name (e.g., "Super Admin")
- slug (varchar) - Unique identifier (e.g., "admin")
- created_at (timestamp)
- updated_at (timestamp)
```

#### `admin_role_users`
```sql
- role_id (int) - Foreign key to admin_roles
- user_id (int) - Foreign key to users
- created_at (timestamp)
- updated_at (timestamp)
```

### Current Roles
- **Super Admin** (slug: `admin`)
- **System Manager** (slug: `manager`)
- **Client** (slug: `client`)

---

## Testing

### Backend Tests
```bash
# Get roles
curl -H "User-Id: 1" http://localhost:8888/dtehm-insurance-api/api/user-roles

# Check admin role
curl -H "User-Id: 1" http://localhost:8888/dtehm-insurance-api/api/user-roles/check/admin

# Check client role
curl -H "User-Id: 1" http://localhost:8888/dtehm-insurance-api/api/user-roles/check/client
```

### Mobile Tests
```dart
// Test role fetching
final roles = await RoleService.fetchUserRoles();
print('User has ${roles.length} roles');

// Test role checking
final user = await LoggedInUserModel.getLoggedInUser();
print('Is Admin: ${user.isAdminRole}');
print('Roles: ${user.roleNames.join(', ')}');

// Test caching
final cached = RoleService.getCachedRoleChecker();
print('Cached roles: ${cached.roleCount}');
```

---

## Error Handling

### Backend
- Returns `code: 0` with error message on failure
- Logs all errors to Laravel log
- Returns 401 if User-Id header missing
- Returns 500 on server errors

### Mobile
- Graceful fallback to cached/stored roles
- Silent failure in background refresh
- Logs all errors to console
- Never blocks UI on role fetch failure

---

## Performance

### Caching
- API responses cached for 30 minutes
- Stored in LoggedInUserModel (persisted across app restarts)
- Background refresh doesn't block UI
- Minimal API calls

### Best Practices
1. Use `getCachedRoleChecker()` for synchronous checks when possible
2. Call `fetchUserRoles()` only when needed (auto-loaded on home)
3. Don't check roles in build methods (use FutureBuilder)
4. Cache role checks in StatefulWidget state when checking repeatedly

---

## Consistency with Codebase

### Follows Project Patterns
✅ Uses `'code': 1/0` response pattern (not 'success')
✅ Square corners design (BorderRadius.zero)
✅ GetX navigation (Get.to)
✅ Utils helpers (Utils.toast, Utils.http_get)
✅ LoggedInUserModel storage pattern
✅ Consistent error handling
✅ Background data loading
✅ Type-safe JSON parsing

### Coding Style
✅ Descriptive variable names
✅ Comprehensive comments
✅ Helper methods for common operations
✅ Null safety
✅ Error logging
✅ Consistent formatting

---

## Summary

**Complete role management system ready for production use!**

✅ Backend API with 2 endpoints
✅ Role model with RoleChecker utility
✅ RoleService with caching
✅ LoggedInUserModel integration
✅ Automatic loading on home page refresh
✅ Comprehensive helper methods
✅ Type-safe implementation
✅ Error handling
✅ Performance optimized
✅ Fully documented
