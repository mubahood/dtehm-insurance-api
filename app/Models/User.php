<?php

namespace App\Models;

use Encore\Admin\Form\Field\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as RelationsBelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

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
}
