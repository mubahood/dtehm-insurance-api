<?php

namespace App\Models;
 
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Form\Field\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as RelationsBelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Administrator implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * Boot method to handle model events
     */
    public static function boot() 
    {
        parent::boot();

        // Handle name splitting and validations before creating
        static::creating(function ($user) {
            self::sanitizeData($user);
            self::handleNameSplitting($user);
            self::validateUniqueFields($user);
            self::generateDipId($user);
        });

        // Handle name splitting and validations before updating
        static::updating(function ($user) {
            self::sanitizeData($user);
            self::handleNameSplitting($user);
            self::validateUniqueFields($user, true);
            self::generateDipId($user);
        });
    }

    /**
     * Sanitize user data - trim strings, clean up whitespace
     */
    protected static function sanitizeData($user)
    {
        // Trim phone number if not empty
        if (!empty($user->phone_number)) {
            $user->phone_number = trim($user->phone_number);
        }
        
        // Trim email if not empty
        if (!empty($user->email)) {
            $user->email = trim($user->email);
        }
        
        // Trim name if not empty
        if (!empty($user->name)) {
            $user->name = trim($user->name);
        }
        
        // Trim first_name if not empty
        if (!empty($user->first_name)) {
            $user->first_name = trim($user->first_name);
        }
        
        // Trim last_name if not empty
        if (!empty($user->last_name)) {
            $user->last_name = trim($user->last_name);
        }
        
        // Trim address if not empty
        if (!empty($user->address)) {
            $user->address = trim($user->address);
        }
    }

    /**
     * Handle name splitting - split full name into first_name and last_name
     */
    protected static function handleNameSplitting($user)
    {
        // If name is provided but first_name or last_name is empty, split the name
        if (!empty($user->name) && (empty($user->first_name) || empty($user->last_name))) {
            $nameParts = self::splitFullName($user->name);
            
            if (empty($user->first_name)) {
                $user->first_name = $nameParts['first_name'];
            }
            
            if (empty($user->last_name)) {
                $user->last_name = $nameParts['last_name'];
            }
        }
        
        // If first_name and last_name are provided but name is empty, combine them
        if (!empty($user->first_name) && !empty($user->last_name) && empty($user->name)) {
            $user->name = trim($user->first_name . ' ' . $user->last_name);
        }
        
        // If only name is provided during update, always split it
        if (!empty($user->name) && $user->isDirty('name')) {
            $nameParts = self::splitFullName($user->name);
            $user->first_name = $nameParts['first_name'];
            $user->last_name = $nameParts['last_name'];
        }
    }

    /**
     * Split full name into first name and last name intelligently
     */
    protected static function splitFullName($fullName)
    {
        // Trim and remove extra spaces
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));
        
        // Split by space
        $parts = explode(' ', $fullName);
        
        if (count($parts) == 1) {
            // Only one name provided - use it for both
            return [
                'first_name' => $parts[0],
                'last_name' => $parts[0]
            ];
        } elseif (count($parts) == 2) {
            // Two names - first and last
            return [
                'first_name' => $parts[0],
                'last_name' => $parts[1]
            ];
        } else {
            // Three or more names - first name is first part, last name is everything else
            $firstName = array_shift($parts);
            $lastName = implode(' ', $parts);
            
            return [
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
        }
    }

    /**
     * Validate unique fields (email and phone_number)
     */
    protected static function validateUniqueFields($user, $isUpdate = false)
    {
        // Validate email uniqueness (if provided and not null)
        if (!empty($user->email) && $user->email !== null) {
            $emailQuery = self::where('email', $user->email);
            
            // Exclude current user ID when updating
            if ($isUpdate && $user->id) {
                $emailQuery->where('id', '!=', $user->id);
            }
            
            if ($emailQuery->exists()) {
                throw new \Exception("The email '{$user->email}' is already registered. Please use a different email address.");
            }
        }

        // Validate phone_number uniqueness (if provided and not null)
        if (!empty($user->phone_number) && $user->phone_number !== null) {
            $phoneQuery = self::where('phone_number', $user->phone_number);
            
            // Exclude current user ID when updating
            if ($isUpdate && $user->id) {
                $phoneQuery->where('id', '!=', $user->id);
            }
            
            if ($phoneQuery->exists()) {
                throw new \Exception("The phone number '{$user->phone_number}' is already registered. Please use a different phone number.");
            }
        }
    }

    /**
     * Generate DIP ID for user (format: DIP0001, DIP0002, etc.)
     * The ID has constant length of 7 characters (DIP + 4 digits with leading zeros)
     */
    protected static function generateDipId($user)
    {
        // Only generate if business_name (DIP ID) is not already set
        if (!empty($user->business_name)) {
            return;
        }

        try {
            // Get the highest existing DIP ID number
            $lastUser = self::whereNotNull('business_name')
                ->where('business_name', 'LIKE', 'DIP%')
                ->orderBy('business_name', 'DESC')
                ->first();

            $nextNumber = 1;

            if ($lastUser && !empty($lastUser->business_name)) {
                // Extract the number from the last DIP ID (e.g., "DIP0045" -> 45)
                $lastNumber = intval(substr($lastUser->business_name, 3));
                $nextNumber = $lastNumber + 1;
            }

            // Format with leading zeros to maintain constant length (4 digits)
            // DIP0001, DIP0010, DIP0100, DIP1000, DIP9999
            $dipId = 'DIP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness (in rare case of race conditions)
            $attempts = 0;
            while (self::where('business_name', $dipId)->exists() && $attempts < 10) {
                $nextNumber++;
                $dipId = 'DIP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }

            $user->business_name = $dipId;

        } catch (\Exception $e) {
            // If generation fails, log the error but don't block user creation
            \Log::error('DIP ID generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the sponsor user by DIP ID
     * 
     * @return User|null
     */
    public function sponsor()
    {
        if (empty($this->sponsor_id)) {
            return null;
        }

        return self::where('business_name', $this->sponsor_id)->first();
    }

    /**
     * Get all users sponsored by this user
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sponsoredUsers()
    {
        if (empty($this->business_name)) {
            return collect([]);
        }

        return self::where('sponsor_id', $this->business_name)->get();
    }

    /**
     * Get count of users sponsored by this user
     * 
     * @return int
     */
    public function sponsoredUsersCount()
    {
        if (empty($this->business_name)) {
            return 0;
        }

        return self::where('sponsor_id', $this->business_name)->count();
    }

    /**
     * Alternatively, you can use fillable to explicitly allow specific fields:
     * Uncomment and modify if you prefer explicit fillable over guarded
     */
    /*
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'phone_number_2',
        'user_type',
        'is_membership_paid',
        'membership_paid_at',
        'membership_amount',
        'membership_payment_id',
        'membership_type',
        'membership_expiry_date',
        // Add other fields as needed
    ];
    */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function send_password_reset()
    {
        $u = $this;
        $u->intro = rand(100000, 999999);
        $u->save();
        $data['email'] = $u->email;
        if ($u->email == null || $u->email == "") {
            $data['email'] = $u->username;
        }
        $data['name'] = $u->name;
        $data['subject'] = env('APP_NAME') . " - Password Reset";
        $data['body'] = "<br>Dear " . $u->name . ",<br>";
        $data['body'] .= "<br>Please use the code below to reset your password.<br><br>";
        $data['body'] .= "CODE: <b>" . $u->intro . "</b><br>";
        $data['body'] .= "<br>Thank you.<br><br>";
        $data['body'] .= "<br><small>This is an automated message, please do not reply.</small><br>";
        $data['view'] = 'mail-1';
        $data['data'] = $data['body'];
        try {
            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function send_verification_code($email)
    {
        $u = $this;
        $u->intro = rand(100000, 999999);
        $u->save(); 
        $data['email'] = $email;
        if ($email == null || $email == "") {
            throw new \Exception("Email is required.");
        }

        $data['name'] = $u->name;
        $data['subject'] = env('APP_NAME') . " - Email Verification";
        $data['body'] = "<br>Dear " . $u->name . ",<br>";
        $data['body'] .= "<br>Please use the CODE below to verify your email address.<br><br>";
        $data['body'] .= "CODE: <b>" . $u->intro . "</b><br>";
        $data['body'] .= "<br>Thank you.<br><br>";
        $data['body'] .= "<br><small>This is an automated message, please do not reply.</small><br>";
        $data['view'] = 'mail-1';
        $data['data'] = $data['body'];
        try {
            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function programs()
    {
        return $this->hasMany(UserHasProgram::class, 'user_id');
    }

    /**
     * Get the user's wishlist items.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the user's wishlist products.
     */
    public function wishlistProducts()
    {
        return $this->hasManyThrough(Product::class, Wishlist::class, 'user_id', 'id', 'id', 'product_id');
    }

    /**
     * Get all reviews written by this user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get recent reviews by this user.
     */
    public function recentReviews($limit = 5)
    {
        return $this->hasMany(Review::class)->latest()->limit($limit);
    }

    /**
     * Check if user has reviewed a specific product.
     */
    public function hasReviewedProduct($productId)
    {
        return $this->reviews()->where('product_id', $productId)->exists();
    }

    /**
     * Get user's review for a specific product.
     */
    public function getProductReview($productId)
    {
        return $this->reviews()->where('product_id', $productId)->first();
    }

    /**
     * Get user's account transactions
     */
    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /**
     * Get user's withdraw requests
     */
    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    /**
     * Get user's project shares (investments)
     */
    public function projectShares()
    {
        return $this->hasMany(ProjectShare::class, 'investor_id');
    }

    /**
     * Get user's account balance (computed from account transactions)
     */
    public function getAccountBalanceAttribute()
    {
        return $this->accountTransactions()->sum('amount');
    }

    /**
     * Calculate and return account balance
     */
    public function calculateAccountBalance()
    {
        return $this->accountTransactions()->sum('amount');
    }

    /**
     * Get user's membership payment
     */
    public function membershipPayment()
    {
        return $this->belongsTo(MembershipPayment::class, 'membership_payment_id');
    }

    /**
     * Get all membership payments for this user
     */
    public function membershipPayments()
    {
        return $this->hasMany(MembershipPayment::class, 'user_id');
    }

    /**
     * Check if user has valid membership
     */
    public function hasValidMembership()
    {
        // Admin users always have access
        if ($this->isAdmin()) {
            return true;
        }

        // Check if membership is paid
        if (!$this->is_membership_paid) {
            return false;
        }

        // Check expiry date if applicable
        if ($this->membership_expiry_date) {
            return $this->membership_expiry_date >= now();
        }

        // LIFE membership (no expiry)
        return true;
    }

    /**
     * Check if user is admin (case-insensitive check)
     */
    public function isAdmin()
    {
        if (!$this->user_type) {
            return false;
        }
        return strtolower($this->user_type) === 'admin';
    }

    /**
     * Reset user password and send credentials via SMS
     * 
     * @return object Response object with success status and message
     */
    public function resetPasswordAndSendSMS()
    {
        $response = (object)[
            'success' => false,
            'message' => '',
            'password' => null,
            'sms_sent' => false,
            'sms_response' => null
        ];

        try {
            // Validate phone number
            if (empty($this->phone_number)) {
                $response->message = 'User has no phone number registered';
                return $response;
            }

            if (!Utils::phone_number_is_valid($this->phone_number)) {
                $response->message = 'User has invalid phone number: ' . $this->phone_number;
                return $response;
            }

            // Generate 6-digit random password
            $newPassword = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $response->password = $newPassword;

            // Hash and save password
            $this->password = password_hash($newPassword, PASSWORD_DEFAULT);
            
            try {
                $this->save();
            } catch (\Exception $e) {
                $response->message = 'Failed to save new password: ' . $e->getMessage();
                return $response;
            }

            // Prepare welcome message with credentials
            $appName = env('APP_NAME', 'DTEHM Insurance');
            $userName = $this->name ?? $this->first_name ?? 'User';
            
            $message = "Welcome to {$appName}! Your login credentials:\n"
                     . "Email: {$this->email}\n"
                     . "Password: {$newPassword}\n"
                     . "Download our app to get started!";

            // Send SMS
            $smsResponse = Utils::sendSMS($this->phone_number, $message);
            $response->sms_response = $smsResponse;
            $response->sms_sent = $smsResponse->success;

            if ($smsResponse->success) {
                $response->success = true;
                $response->message = 'Password reset successfully and SMS sent to ' . $this->phone_number;
            } else {
                $response->success = false;
                $response->message = 'Password reset but SMS failed: ' . $smsResponse->message;
            }

            return $response;

        } catch (\Exception $e) {
            $response->message = 'Error during password reset: ' . $e->getMessage();
            return $response;
        } catch (\Throwable $e) {
            $response->message = 'Critical error: ' . $e->getMessage();
            return $response;
        }
    }

    /**
     * Send welcome SMS with custom message
     * 
     * @param string|null $customMessage Custom message to send (optional)
     * @return object Response object
     */
    public function sendWelcomeSMS($customMessage = null)
    {
        $response = (object)[
            'success' => false,
            'message' => '',
            'sms_response' => null
        ];

        try {
            // Validate phone number
            if (empty($this->phone_number)) {
                $response->message = 'User has no phone number registered';
                return $response;
            }

            if (!Utils::phone_number_is_valid($this->phone_number)) {
                $response->message = 'Invalid phone number: ' . $this->phone_number;
                return $response;
            }

            // Prepare message
            $appName = env('APP_NAME', 'DTEHM Insurance');
            $userName = $this->name ?? $this->first_name ?? 'User';

            if ($customMessage) {
                $message = $customMessage;
            } else {
                $message = "Hello {$userName}! Welcome to {$appName}. "
                         . "Get comprehensive insurance coverage at your fingertips. "
                         . "Download our app today and secure your future!";
            }

            // Send SMS
            $smsResponse = Utils::sendSMS($this->phone_number, $message);
            $response->sms_response = $smsResponse;

            if ($smsResponse->success) {
                $response->success = true;
                $response->message = 'Welcome SMS sent successfully to ' . $this->phone_number;
            } else {
                $response->message = 'Failed to send SMS: ' . $smsResponse->message;
            }

            return $response;

        } catch (\Exception $e) {
            $response->message = 'Error sending welcome SMS: ' . $e->getMessage();
            return $response;
        } catch (\Throwable $e) {
            $response->message = 'Critical error: ' . $e->getMessage();
            return $response;
        }
    }
}
