# Network Hierarchy Tree API

## Overview
This API endpoint provides the complete network hierarchy tree for the authenticated user, displaying both their upline (sponsors) and downline (referrals) organized by generation, exactly as shown in the web admin panel.

## Endpoint Details

### URL
```
GET /api/user/network-tree
```

### Authentication
**Required**: Yes (Bearer Token)

### Headers
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

## Response Structure

### Success Response (Code: 200)
```json
{
    "code": 1,
    "message": "Network tree retrieved successfully",
    "data": {
        "user": {
            "id": 123,
            "name": "John Doe",
            "phone": "0700123456",
            "dip_id": "DIP0001",
            "dtehm_id": "DTEHM20250001",
            "sponsor_id": "DIP0099",
            "avatar": "/storage/avatars/user123.jpg",
            "is_dtehm_member": "Yes",
            "is_dip_member": "Yes"
        },
        "sponsor": {
            "id": 99,
            "name": "Jane Smith",
            "dip_id": "DIP0099",
            "dtehm_id": "DTEHM20250099",
            "phone": "0700999999"
        },
        "upline": [
            {
                "level": "1",
                "level_name": "PARENT 1",
                "id": 99,
                "name": "Jane Smith",
                "phone": "0700999999",
                "dip_id": "DIP0099",
                "dtehm_id": "DTEHM20250099",
                "sponsor_id": "DIP0050",
                "avatar": "/storage/avatars/user99.jpg"
            },
            {
                "level": "2",
                "level_name": "PARENT 2",
                "id": 50,
                "name": "Bob Johnson",
                "phone": "0700505050",
                "dip_id": "DIP0050",
                "dtehm_id": null,
                "sponsor_id": "DIP0010",
                "avatar": null
            }
            // ... up to parent_10
        ],
        "downline": [
            {
                "generation": 1,
                "count": 5,
                "members": [
                    {
                        "id": 150,
                        "name": "Alice Brown",
                        "phone": "0701234567",
                        "dip_id": "DIP0150",
                        "dtehm_id": "DTEHM20250150",
                        "sponsor_id": "DIP0001",
                        "avatar": null,
                        "is_dtehm_member": "Yes",
                        "is_dip_member": "Yes",
                        "total_downline": 12,
                        "created_at": "2025-01-15 10:30:00"
                    },
                    {
                        "id": 151,
                        "name": "Charlie Davis",
                        "phone": "0701234568",
                        "dip_id": "DIP0151",
                        "dtehm_id": null,
                        "sponsor_id": "DIP0001",
                        "avatar": null,
                        "is_dtehm_member": "No",
                        "is_dip_member": "Yes",
                        "total_downline": 0,
                        "created_at": "2025-02-10 14:20:00"
                    }
                    // ... more generation 1 members
                ]
            },
            {
                "generation": 2,
                "count": 12,
                "members": [
                    // ... generation 2 members
                ]
            }
            // ... up to generation 10
        ],
        "statistics": {
            "total_downline": 45,
            "total_upline": 5,
            "direct_referrals": 5,
            "dtehm_members_count": 30,
            "dip_members_count": 45
        }
    }
}
```

### Error Response (Code: 401)
```json
{
    "code": 0,
    "message": "Authentication required"
}
```

### Error Response (Code: 500)
```json
{
    "code": 0,
    "message": "Failed to retrieve network tree: {error_message}"
}
```

## Response Fields Explained

### User Object
- **id**: User's database ID
- **name**: Full name of the user
- **phone**: User's phone number
- **dip_id**: DIP membership ID (business_name)
- **dtehm_id**: DTEHM membership ID
- **sponsor_id**: ID of the user's sponsor (can be DIP or DTEHM ID)
- **avatar**: Path to user's profile picture
- **is_dtehm_member**: "Yes" or "No"
- **is_dip_member**: "Yes" or "No"

### Sponsor Object
Contains basic info about the direct sponsor (same fields as above, minimal)

### Upline Array
- Ordered list of all ancestors from parent_1 to parent_10
- **level**: Numeric level (1-10)
- **level_name**: Display name (e.g., "PARENT 1", "PARENT 2")
- Each parent has same fields as user object

### Downline Array
- Organized by generation (1-10)
- Each generation has:
  - **generation**: Generation number (1-10)
  - **count**: Number of members in this generation
  - **members**: Array of member objects
- Member objects include:
  - All user fields (id, name, phone, etc.)
  - **total_downline**: Count of this member's own downline
  - **created_at**: When they joined

### Statistics Object
- **total_downline**: Total count of all downline members (generations 1-10)
- **total_upline**: Count of upline members
- **direct_referrals**: Count of generation 1 members only
- **dtehm_members_count**: How many downline are DTEHM members
- **dip_members_count**: How many downline are DIP members

## Usage Examples

### Flutter/Dart Example
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> getNetworkTree(String token) async {
  final response = await http.get(
    Uri.parse('https://your-api-domain.com/api/user/network-tree'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['code'] == 1) {
      return data['data'];
    } else {
      throw Exception(data['message']);
    }
  } else {
    throw Exception('Failed to load network tree');
  }
}

// Usage
void displayNetworkTree() async {
  try {
    final token = await getStoredToken(); // Your token storage method
    final treeData = await getNetworkTree(token);
    
    print('Total Downline: ${treeData['statistics']['total_downline']}');
    print('Direct Referrals: ${treeData['statistics']['direct_referrals']}');
    
    // Display downline by generation
    for (var gen in treeData['downline']) {
      print('Generation ${gen['generation']}: ${gen['count']} members');
      for (var member in gen['members']) {
        print('  - ${member['name']} (${member['dip_id']})');
      }
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

### JavaScript/React Example
```javascript
async function getNetworkTree(token) {
  const response = await fetch('https://your-api-domain.com/api/user/network-tree', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  const data = await response.json();
  
  if (data.code === 1) {
    return data.data;
  } else {
    throw new Error(data.message);
  }
}

// Usage in React component
function NetworkTreeComponent() {
  const [treeData, setTreeData] = useState(null);
  
  useEffect(() => {
    const token = localStorage.getItem('token');
    getNetworkTree(token)
      .then(data => setTreeData(data))
      .catch(error => console.error('Error:', error));
  }, []);

  return (
    <div>
      <h2>My Network</h2>
      {treeData && (
        <>
          <div>
            <h3>Statistics</h3>
            <p>Total Downline: {treeData.statistics.total_downline}</p>
            <p>Direct Referrals: {treeData.statistics.direct_referrals}</p>
            <p>DTEHM Members: {treeData.statistics.dtehm_members_count}</p>
          </div>
          
          <div>
            <h3>Downline by Generation</h3>
            {treeData.downline.map(gen => (
              <div key={gen.generation}>
                <h4>Generation {gen.generation} ({gen.count} members)</h4>
                <ul>
                  {gen.members.map(member => (
                    <li key={member.id}>
                      {member.name} - {member.phone} ({member.dip_id})
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}
```

## UI Display Recommendations

### For Mobile App (Flutter)

1. **Summary Card** (Top)
   - User's photo, name, IDs
   - Statistics badges (total downline, direct referrals)
   - Sponsor info button

2. **Tabbed View**
   - Tab 1: "Upline" - Show ancestor chain
   - Tab 2: "Downline" - Show generations with expandable sections
   - Tab 3: "Statistics" - Visual charts/graphs

3. **Generation Cards** (Expandable)
   - Each generation as an expandable card
   - Show count in collapsed state
   - Expand to show member list with:
     - Avatar (or placeholder)
     - Name, phone, IDs
     - Their downline count
     - Tap to view their tree

4. **Search & Filter**
   - Search by name, phone, or ID
   - Filter by membership type (DTEHM/DIP)
   - Filter by generation

### Color Coding
- Generation 1: Green
- Generation 2: Blue
- Generation 3: Yellow
- Generation 4: Red
- Generation 5: Purple
- Generation 6: Orange
- Generation 7: Teal
- Generation 8: Olive
- Generation 9: Aqua
- Generation 10: Navy

## Integration with Existing App

### Update Your API Service
Add the new endpoint to your API service class:

```dart
// lib/services/api_service.dart
class ApiService {
  static const String baseUrl = 'https://your-api-domain.com/api';
  
  Future<NetworkTreeResponse> getNetworkTree() async {
    final token = await getToken();
    final response = await http.get(
      Uri.parse('$baseUrl/user/network-tree'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );
    
    if (response.statusCode == 200) {
      return NetworkTreeResponse.fromJson(json.decode(response.body));
    } else {
      throw Exception('Failed to load network tree');
    }
  }
}
```

### Create Model Classes
```dart
// lib/models/network_tree.dart
class NetworkTreeResponse {
  final int code;
  final String message;
  final NetworkTreeData data;
  
  NetworkTreeResponse({
    required this.code,
    required this.message,
    required this.data,
  });
  
  factory NetworkTreeResponse.fromJson(Map<String, dynamic> json) {
    return NetworkTreeResponse(
      code: json['code'],
      message: json['message'],
      data: NetworkTreeData.fromJson(json['data']),
    );
  }
}

class NetworkTreeData {
  final NetworkUser user;
  final NetworkUser? sponsor;
  final List<NetworkParent> upline;
  final List<NetworkGeneration> downline;
  final NetworkStatistics statistics;
  
  // ... constructor and fromJson
}

class NetworkGeneration {
  final int generation;
  final int count;
  final List<NetworkMember> members;
  
  // ... constructor and fromJson
}

// ... other model classes
```

## Notes

- The endpoint matches the admin panel hierarchy view exactly
- Uses the same User model methods: `getAllParents()` and `getGenerationUsers()`
- Downline goes up to 10 generations deep
- All data is properly formatted and ready for display
- Includes comprehensive error handling and logging
- Statistics are calculated in real-time

## Testing

Test the endpoint using curl:

```bash
curl -X GET "https://your-api-domain.com/api/user/network-tree" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

Or use Postman:
1. Set Method to GET
2. URL: `https://your-api-domain.com/api/user/network-tree`
3. Add Header: `Authorization: Bearer YOUR_JWT_TOKEN`
4. Send request

## Support

If you encounter any issues with this API endpoint:
1. Check that the user is authenticated (valid JWT token)
2. Verify the user has a valid sponsor_id
3. Check Laravel logs at `storage/logs/laravel.log`
4. Ensure parent hierarchy is populated (runs automatically on user save)
