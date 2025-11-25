<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Utils;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthController extends Controller
{

    use ApiResponser;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {

        /* $token = auth('api')->attempt([
            'username' => 'admin',
            'password' => 'admin',
        ]);
        die($token); */
        $this->middleware('auth:api', ['except' => [
            'manifest',
            'login',
            'register'
        ]]);
    }


    public function manifest()
    {

        $carbon = new Carbon();
        $TOP_PRODUCTS = Product::where([])->orderBy('id', 'desc')->limit(1000)->get();
        $TOP_PRODUCTS = $TOP_PRODUCTS->shuffle();
        $TOP_4_PRODUCTS = $TOP_PRODUCTS->take(4);

        $SECTION_1_PRODUCTS = [];
        //TAKE NEXT 20 without the first 4
        if (count($TOP_PRODUCTS) > 4) {
            $SECTION_1_PRODUCTS = $TOP_PRODUCTS->slice(4, 30);
        } else {
            $SECTION_1_PRODUCTS = $TOP_PRODUCTS;
        }

        $SECTION_2_PRODUCTS = [];
        //TAKE NEXT 20 without the first 24
        if (count($TOP_PRODUCTS) > 24) {
            $SECTION_2_PRODUCTS = $TOP_PRODUCTS->slice(34, 30);
        } else {
            $SECTION_2_PRODUCTS = $TOP_PRODUCTS;
        }

        $manifest = [
            'FIRST_BANNER' => ProductCategory::where([
                'is_first_banner' => 'Yes'
            ])->first(),
            'SLIDER_CATEGORIES' => ProductCategory::where([
                'show_in_banner' => 'Yes'
            ])->get(),
            'TOP_4_PRODUCTS' => $TOP_4_PRODUCTS,
            'CATEGORIES' => ProductCategory::where([
                'show_in_categories' => 'Yes'
            ])->get(),
            'SECTION_1_PRODUCTS' => $SECTION_1_PRODUCTS,
            'SECTION_2_PRODUCTS' => $SECTION_2_PRODUCTS,
        ];

        return $this->success($manifest, 'Success');
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $query = auth('api')->user();
        return $this->success($query, $message = "Profile details", 200);
    }





    public function login(Request $r)
    {
        if ($r->username == null) {
            return $this->error('Username is required.');
        }

        if (isset($r->task)) {
            if ($r->task == 'reset_password') {
                $u = User::where('email', $r->email)->first();
                if ($u == null) {
                    return $this->error('User account with email ' . $r->email . ' not found.');
                }

                $code = $r->code;
                if ($code == null || strlen($code) < 3) {
                    return $this->error('Code is required.');
                }
                if ($u->intro != $code) {
                    return $this->error('Invalid code.');
                }
                $password = $r->password;
                if ($password == null || strlen($password) < 3) {
                    return $this->error('Password is required.');
                }
                $u->password = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $u->save();
                } catch (\Throwable $th) {
                    return $this->error('Failed to reset password because ' . $th->getMessage() . '.');
                }
                return $this->success($u, 'Password reset successfully.');
            } else if ($r->task == 'request_password_reset') {
                $u = User::where('email', $r->username)->first();
                if ($u == null) {
                    return $this->error('User account not found.');
                }
                try {
                    $u->send_password_reset();
                } catch (\Throwable $th) {
                    return $this->error('Failed to send password reset email because ' . $th->getMessage() . '.');
                }
                return $this->success($u, 'Password reset CODE sent to your email address ' . $u->email . '.');
            }
            return $this->error('Invalid task.');
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        $username = $r->get('username');

        $u = User::where('phone_number', $username)
            ->orWhere('username', $username)
            ->orWhere('email', $username)
            ->first();



        $phone_number = null;
        if ($u == null) {

            $phone_number = Utils::prepare_phone_number($r->username);
            if (Utils::phone_number_is_valid($phone_number)) {
                $phone_number = $r->phone_number;

                $u = User::where('phone_number', $phone_number)
                    ->orWhere('username', $phone_number)
                    ->orWhere('email', $phone_number)
                    ->first();
            }
        }

        if ($u == null) {
            return $this->error('User account not found. username: ' . $username . ' phone: ' . $phone_number);
        }

        if ($u->status == 'Deleted') {
            return $this->error('User account not found. Contact us for help.');
        }

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $u->id,
            'password' => trim($r->password),
        ]);


        if ($token == null) {
            return $this->error('Wrong credentials.');
        }



        $u->token = $token;
        $u->remember_token = $token;

        return $this->success($u, 'Logged in successfully.');
    }

    public function register(Request $r)
    {
        // Phone number is now primary identifier
        if ($r->phone_number == null || empty(trim($r->phone_number))) {
            return $this->error('Phone number is required.');
        }

        // Validate Uganda phone number format
        $phoneNumber = trim($r->phone_number);
        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', $phoneNumber); // Remove spaces and special chars

        if (substr($phoneNumber, 0, 3) == '256') {
            // Format: 256XXXXXXXXX (12 digits)
            if (strlen($phoneNumber) != 12) {
                return $this->error('Invalid Uganda phone number. Use format: 256700000000');
            }
        } elseif (substr($phoneNumber, 0, 1) == '0') {
            // Format: 0XXXXXXXXX (10 digits)
            if (strlen($phoneNumber) != 10) {
                return $this->error('Invalid Uganda phone number. Use format: 0700000000');
            }
        } else {
            return $this->error('Phone number must start with 256 or 0');
        }

        // ========================================
        // EMAIL IS OPTIONAL - NOT REQUIRED
        // Auto-generated as {phone}@dtehm.app if not provided
        // ========================================
        $email = null;
        if ($r->email != null && !empty(trim($r->email))) {
            if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Invalid email address format.');
            }
            $email = trim($r->email);
        }
        // Email will be auto-generated below if null

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        if ($r->name == null) {
            return $this->error('Name is required.');
        }

        // Check for existing user with same phone number (primary) or email
        $existingUser = Administrator::where('phone_number', $phoneNumber)
            ->orWhere('username', $phoneNumber);

        // Check email if provided
        if ($email != null) {
            $existingUser = $existingUser->orWhere('email', $email);
        }

        $u = $existingUser->first();

        if ($u != null) {
            if ($u->status == 'Deleted') {
                return $this->error('Account is deleted. Contact us for help.');
            }

            if ($u->phone_number == $phoneNumber) {
                return $this->error('User with same Phone number already exists.');
            }

            if ($email != null && $u->email == $email) {
                return $this->error('User with same Email address already exists.');
            }
        }

        $user = new Administrator();

        $name = trim($r->name);

        // Split name into first_name and last_name
        // Remove extra spaces and split
        $nameParts = preg_split('/\s+/', $name);

        if (count($nameParts) == 1) {
            // Only one name - use for both
            $user->first_name = $nameParts[0];
            $user->last_name = $nameParts[0];
        } elseif (count($nameParts) == 2) {
            // Two names - first and last
            $user->first_name = $nameParts[0];
            $user->last_name = $nameParts[1];
        } else {
            // Three or more names - first is first, rest is last
            $user->first_name = $nameParts[0];
            array_shift($nameParts);
            $user->last_name = implode(' ', $nameParts);
        }

        $user->name = $name;
        $user->username = $phoneNumber; // Phone number as username
        $user->email = $email ?? $phoneNumber . '@dtehm.app'; // Use phone as email if not provided
        $user->reg_number = $phoneNumber;
        $user->phone_number = $phoneNumber;

        // Set address from request if provided
        $user->address = $r->address != null ? trim($r->address) : '';

        // Set sponsor_id (DIP ID or DTEHM ID) from request - REQUIRED for mobile app
        if ($r->sponsor_id != null && !empty(trim($r->sponsor_id))) {
            $sponsorId = trim($r->sponsor_id);
            // Verify that sponsor exists (can be DIP ID or DTEHM Member ID)
            $sponsor = Administrator::where('business_name', $sponsorId)
                ->orWhere('dtehm_member_id', $sponsorId)
                ->first();
            if ($sponsor) {
                $user->sponsor_id = $sponsorId;
            } else {
                return $this->error('Invalid Sponsor ID. Sponsor must be an existing member in the system.');
            }
        } elseif ($r->from_mobile == 'yes') {
            // For mobile app, sponsor is required
            return $this->error('Sponsor ID is required. No user can be registered without a sponsor.');
        }

        // Set membership types
        $user->is_dtehm_member = $r->is_dtehm_member ?? 'No';
        $user->is_dip_member = $r->is_dip_member ?? 'No';

        // Set optional fields with empty defaults
        $user->profile_photo_large = '';
        $user->location_lat = '';
        $user->location_long = '';
        $user->facebook = '';
        $user->twitter = '';
        $user->linkedin = '';
        $user->website = '';
        $user->other_link = '';
        $user->cv = '';
        $user->language = '';
        $user->about = '';
        $user->country = '';
        $user->occupation = '';

        $user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);

        try {
            if (!$user->save()) {
                return $this->error('Failed to create account. Please try again.');
            }
        } catch (\Exception $e) {
            // Catch validation errors from boot() method
            return $this->error('Registration failed: ' . $e->getMessage());
        }

        $new_user = Administrator::find($user->id);
        if ($new_user == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }
        Config::set('jwt.ttl', 60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'username' => $phoneNumber,
            'password' => trim($r->password),
        ]);

        $new_user->token = $token;
        $new_user->remember_token = $token;

        // Calculate membership payment required
        $paymentRequired = 0;
        $membershipTypes = [];

        if ($new_user->is_dtehm_member == 'Yes') {
            $paymentRequired += 76000;
            $membershipTypes[] = 'DTEHM';
        }

        if ($new_user->is_dip_member == 'Yes') {
            $paymentRequired += 20000;
            $membershipTypes[] = 'DIP';
        }

        // Add payment info to response - DO NOT auto-generate membership IDs
        $response = [
            'user' => $new_user,
            'membership_payment' => [
                'required' => $paymentRequired > 0,
                'amount' => $paymentRequired,
                'types' => $membershipTypes,
                'breakdown' => [
                    'dtehm' => $new_user->is_dtehm_member == 'Yes' ? 76000 : 0,
                    'dip' => $new_user->is_dip_member == 'Yes' ? 20000 : 0,
                ],
                'status' => 'pending',
                'note' => 'Membership IDs will be generated after successful payment',
            ]
        ];

        $message = $paymentRequired > 0
            ? 'Account created successfully. Please complete membership payment to activate your account.'
            : 'Account created successfully.';

        return $this->success($response, $message);
    }

    /**
     * Get user's network/downline members
     * GET /api/user/network
     * Query params: level (all/direct/multi), page, per_page
     */
    public function getUserNetwork(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'Authentication required'
            ], 401);
        }
        $user = User::find($user->id);
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }


        $membershipId = $user->dtehm_member_id ?? $user->business_name;

        if (!$membershipId) {
            return response()->json([
                'code' => 0,
                'message' => 'You do not have a membership ID yet'
            ], 400);
        }

        // Get direct referrals (Level 1)
        $directReferrals = Administrator::where('sponsor_id', $membershipId)->get();

        // Get all downline members (up to 10 levels)
        $allDownline = [];
        $currentLevelMembers = $directReferrals;
        $maxLevels = 10;

        for ($level = 1; $level <= $maxLevels && count($currentLevelMembers) > 0; $level++) {
            foreach ($currentLevelMembers as $member) {
                $allDownline[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone_number_1 ?? $member->phone_number,
                    'dtehm_member_id' => $member->dtehm_member_id,
                    'dip_member_id' => $member->business_name,
                    'is_dtehm_member' => $member->is_dtehm_member,
                    'is_dip_member' => $member->is_dip_member,
                    'level' => $level,
                    'joined_at' => $member->created_at,
                    'status' => $member->status ?? 'Active',
                ];
            }

            // Get next level
            $nextLevelMembers = [];
            foreach ($currentLevelMembers as $member) {
                $memberId = $member->dtehm_member_id ?? $member->business_name;
                if ($memberId) {
                    $children = Administrator::where('sponsor_id', $memberId)->get();
                    $nextLevelMembers = array_merge($nextLevelMembers, $children->toArray());
                }
            }
            $currentLevelMembers = collect($nextLevelMembers)->map(function ($m) {
                return (object)$m;
            });
        }

        // Filter by level if requested
        $level = $request->get('level', 'all');
        if ($level == 'direct') {
            $filteredDownline = array_filter($allDownline, function ($member) {
                return $member['level'] == 1;
            });
        } elseif ($level != 'all' && is_numeric($level)) {
            $filteredDownline = array_filter($allDownline, function ($member) use ($level) {
                return $member['level'] == $level;
            });
        } else {
            $filteredDownline = $allDownline;
        }

        // Pagination
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        $total = count($filteredDownline);
        $offset = ($page - 1) * $perPage;
        $paginatedDownline = array_slice(array_values($filteredDownline), $offset, $perPage);

        // Calculate statistics
        $stats = [
            'total_members' => count($allDownline),
            'direct_referrals' => count($directReferrals),
            'dtehm_members' => count(array_filter($allDownline, function ($m) {
                return $m['is_dtehm_member'] == 'Yes';
            })),
            'dip_members' => count(array_filter($allDownline, function ($m) {
                return $m['is_dip_member'] == 'Yes';
            })),
            'levels_deep' => max(array_column($allDownline, 'level') ?: [0]),
        ];

        // Count by level
        $byLevel = [];
        for ($i = 1; $i <= 10; $i++) {
            $count = count(array_filter($allDownline, function ($m) use ($i) {
                return $m['level'] == $i;
            }));
            if ($count > 0) {
                $byLevel["level_$i"] = $count;
            }
        }
        $stats['by_level'] = $byLevel;

        return response()->json([
            'code' => 1,
            'data' => [
                'network' => $paginatedDownline,
                'statistics' => $stats,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ]
            ]
        ]);
    }

    /**
     * Get user's network hierarchy tree (upline and downline)
     * GET /api/user/network-tree
     * This endpoint returns the same structure as the admin panel hierarchy view
     */
    public function getNetworkTree(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'Authentication required'
            ], 401);
        }
        $user = User::find($user->id);
        if (!$user) {
            return response()->json([
                'code' => 0,
                'message' => 'User not found'
            ], 404);
        }

        try {
            // Get user's basic info
            $userInfo = [
                'id' => $user->id,
                'name' => $user->name ?? 'Unknown User',
                'phone' => $user->phone_number,
                'dip_id' => $user->business_name,
                'dtehm_id' => $user->dtehm_member_id,
                'sponsor_id' => $user->sponsor_id,
                'avatar' => $user->avatar,
                'is_dtehm_member' => $user->is_dtehm_member,
                'is_dip_member' => $user->is_dip_member,
            ];

            // Get sponsor info
            $sponsorInfo = null;
            if ($user->sponsor_id) {
                $sponsor = User::where('business_name', $user->sponsor_id)
                    ->orWhere('dtehm_member_id', $user->sponsor_id)
                    ->first();
                if ($sponsor) {
                    $sponsorInfo = [
                        'id' => $sponsor->id,
                        'name' => $sponsor->name ?? 'Unknown',
                        'dip_id' => $sponsor->business_name,
                        'dtehm_id' => $sponsor->dtehm_member_id,
                        'phone' => $sponsor->phone_number,
                    ];
                }
            }

            // Get upline (all parents)
            $upline = [];
            $parents = $user->getAllParents();
            if (!empty($parents) && is_array($parents)) {
                foreach ($parents as $level => $parent) {
                    if ($parent && $parent->id) {
                        $upline[] = [
                            'level' => str_replace('parent_', '', $level),
                            'level_name' => str_replace('_', ' ', strtoupper($level)),
                            'id' => $parent->id,
                            'name' => $parent->name ?? 'Unknown',
                            'phone' => $parent->phone_number,
                            'dip_id' => $parent->business_name,
                            'dtehm_id' => $parent->dtehm_member_id,
                            'sponsor_id' => $parent->sponsor_id,
                            'avatar' => $parent->avatar,
                        ];
                    }
                }
            }

            // Get downline organized by generation (1-10)
            $downline = [];
            $totalDownline = 0;

            for ($gen = 1; $gen <= 10; $gen++) {
                $genUsers = $user->getGenerationUsers($gen);
                $count = $genUsers->count();

                if ($count > 0) {
                    $members = [];
                    foreach ($genUsers as $genUser) {
                        if ($genUser && $genUser->id) {
                            $members[] = [
                                'id' => $genUser->id,
                                'name' => $genUser->name ?? 'Unknown User',
                                'phone' => $genUser->phone_number,
                                'dip_id' => $genUser->business_name,
                                'dtehm_id' => $genUser->dtehm_member_id,
                                'sponsor_id' => $genUser->sponsor_id,
                                'avatar' => $genUser->avatar,
                                'is_dtehm_member' => $genUser->is_dtehm_member,
                                'is_dip_member' => $genUser->is_dip_member,
                                'total_downline' => $genUser->getTotalDownlineCount(),
                                'created_at' => $genUser->created_at ? $genUser->created_at->format('Y-m-d H:i:s') : null,
                            ];
                        }
                    }

                    $downline[] = [
                        'generation' => $gen,
                        'count' => $count,
                        'members' => $members,
                    ];

                    $totalDownline += $count;
                }
            }

            // Calculate statistics
            $statistics = [
                'total_downline' => $totalDownline,
                'total_upline' => count($upline),
                'direct_referrals' => 0,
                'dtehm_members_count' => 0,
                'dip_members_count' => 0,
            ];

            // Count direct referrals (generation 1)
            if (!empty($downline) && isset($downline[0]) && $downline[0]['generation'] == 1) {
                $statistics['direct_referrals'] = $downline[0]['count'];
            }

            // Count DTEHM and DIP members
            foreach ($downline as $generation) {
                foreach ($generation['members'] as $member) {
                    if ($member['is_dtehm_member'] == 'Yes') {
                        $statistics['dtehm_members_count']++;
                    }
                    if ($member['is_dip_member'] == 'Yes') {
                        $statistics['dip_members_count']++;
                    }
                }
            }

            return response()->json([
                'code' => 1,
                'message' => 'Network tree retrieved successfully',
                'data' => [
                    'user' => $userInfo,
                    'sponsor' => $sponsorInfo,
                    'upline' => $upline,
                    'downline' => $downline,
                    'statistics' => $statistics,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get network tree', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve network tree: ' . $e->getMessage()
            ], 500);
        }
    }
}
