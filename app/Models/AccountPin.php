<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AccountPin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pin_hash',
        'failed_attempts',
        'locked_until',
        'last_changed_at',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'last_changed_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set PIN (hashes it automatically)
     */
    public function setPin($pin)
    {
        $this->pin_hash = Hash::make($pin);
        $this->last_changed_at = now();
        $this->failed_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Verify PIN
     */
    public function verifyPin($pin)
    {
        // Check if account is locked
        if ($this->isLocked()) {
            return [
                'success' => false,
                'message' => 'Account is locked. Please try again after ' . $this->locked_until->diffForHumans() . '.',
                'locked_until' => $this->locked_until->toDateTimeString(),
            ];
        }

        // Verify the PIN
        if (Hash::check($pin, $this->pin_hash)) {
            // Reset failed attempts on successful verification
            $this->resetAttempts();
            return [
                'success' => true,
                'message' => 'PIN verified successfully.',
            ];
        }

        // Increment failed attempts
        $this->incrementFailedAttempts();

        return [
            'success' => false,
            'message' => 'Incorrect PIN. ' . (5 - $this->failed_attempts) . ' attempt(s) remaining.',
            'attempts_remaining' => 5 - $this->failed_attempts,
        ];
    }

    /**
     * Increment failed attempts and lock if necessary
     */
    public function incrementFailedAttempts()
    {
        $this->failed_attempts++;

        // Lock account after 5 failed attempts for 30 minutes
        if ($this->failed_attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
        }

        $this->save();
    }

    /**
     * Reset failed attempts
     */
    public function resetAttempts()
    {
        $this->failed_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Check if account is locked
     */
    public function isLocked()
    {
        if ($this->locked_until === null) {
            return false;
        }

        // If lock has expired, reset attempts
        if (now()->greaterThan($this->locked_until)) {
            $this->resetAttempts();
            return false;
        }

        return true;
    }

    /**
     * Get lock status
     */
    public function getLockStatus()
    {
        if ($this->isLocked()) {
            return [
                'is_locked' => true,
                'locked_until' => $this->locked_until->toDateTimeString(),
                'remaining_time' => $this->locked_until->diffForHumans(),
            ];
        }

        return [
            'is_locked' => false,
            'failed_attempts' => $this->failed_attempts,
            'attempts_remaining' => 5 - $this->failed_attempts,
        ];
    }
}
