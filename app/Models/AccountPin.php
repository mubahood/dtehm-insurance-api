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
     * Who is acting right now. Admin operations set this so the history shows the office (and which
     * admin) instead of the member. Null = the member themself (app).
     */
    public static ?array $actor = null;

    /** PINs nobody should use (the office cannot set these; the generator never makes them). */
    public static function isWeak(string $pin): bool
    {
        if (!preg_match('/^\d{4}$/', $pin)) {
            return true;
        }
        if (count(array_unique(str_split($pin))) === 1) {
            return true; // 0000, 1111 ...
        }
        $d = array_map('intval', str_split($pin));
        $up = $down = true;
        for ($i = 1; $i < 4; $i++) {
            $up = $up && $d[$i] === ($d[$i - 1] + 1) % 10;
            $down = $down && $d[$i] === ($d[$i - 1] + 9) % 10;
        }
        return $up || $down || in_array($pin, ['1122', '1212', '2580', '0852', '1004', '2000', '2020', '2025', '2026'], true);
    }

    /** A random 4-digit PIN that is not weak. */
    public static function generatePin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::isWeak($pin));
        return $pin;
    }

    /** Set (or replace) a member's PIN on behalf of the office; clears any lock. */
    public static function adminSet(int $userId, string $pin, int $adminId, string $action = 'admin_set', ?string $note = null): self
    {
        return self::asAdmin($adminId, $action, $note, function () use ($userId, $pin) {
            $row = self::firstOrNew(['user_id' => $userId]);
            $row->setPin($pin);
            return $row;
        });
    }

    public static function adminUnlock(int $userId, int $adminId, ?string $note = null): ?self
    {
        return self::asAdmin($adminId, 'admin_unlocked', $note, function () use ($userId) {
            $row = self::where('user_id', $userId)->first();
            if ($row) {
                $row->resetAttempts();
                self::log($userId, 'admin_unlocked');
            }
            return $row;
        });
    }

    /** Remove the PIN: the member is asked to create a new one in the app. */
    public static function adminRemove(int $userId, int $adminId, ?string $note = null): bool
    {
        return self::asAdmin($adminId, 'admin_removed', $note, function () use ($userId) {
            $row = self::where('user_id', $userId)->first();
            return $row ? (bool) $row->delete() : false;
        });
    }

    private static function asAdmin(int $adminId, string $action, ?string $note, callable $fn)
    {
        $previous = self::$actor;
        self::$actor = ['type' => 'admin', 'id' => $adminId, 'action' => $action, 'note' => $note];
        try {
            return $fn();
        } finally {
            self::$actor = $previous;
        }
    }

    /** Write one history line. Never breaks the PIN operation itself. */
    public static function log(int $userId, string $action, array $extra = []): void
    {
        try {
            $a = self::$actor;
            AccountPinLog::create(array_merge([
                'user_id' => $userId,
                'action' => $a['action'] ?? $action,
                'actor_type' => $a['type'] ?? 'member',
                'actor_id' => $a['id'] ?? $userId,
                'note' => $a['note'] ?? null,
                'ip' => request() ? request()->ip() : null,
                'created_at' => now(),
            ], $extra));
        } catch (\Throwable $e) {
            \Log::warning('PIN history not written: ' . $e->getMessage());
        }
    }

    protected static function booted()
    {
        static::created(function (self $pin) {
            self::log($pin->user_id, 'created');
        });
        static::updated(function (self $pin) {
            if ($pin->wasChanged('pin_hash')) {
                self::log($pin->user_id, 'changed');
            } elseif ($pin->wasChanged('locked_until') && $pin->locked_until && self::$actor === null) {
                self::log($pin->user_id, 'locked', ['actor_type' => 'system', 'actor_id' => null]);
            }
        });
        static::deleted(function (self $pin) {
            self::log($pin->user_id, 'removed');
        });
    }

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
