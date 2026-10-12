<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'account_balance_before',
        'status',
        'description',
        'payment_method',
        'payment_phone_number',
        'admin_note',
        'processed_by_id',
        'processed_at',
        'account_transaction_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'decimal:2',
        'account_balance_before' => 'decimal:2',
        'processed_by_id' => 'integer',
        'account_transaction_id' => 'integer',
        'processed_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_balance',
        'status_label',
        'can_be_processed',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function accountTransaction()
    {
        return $this->belongsTo(AccountTransaction::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 2);
    }

    public function getFormattedBalanceAttribute()
    {
        return 'UGX ' . number_format($this->account_balance_before, 2);
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    public function getCanBeProcessedAttribute()
    {
        return $this->status === 'pending' && is_null($this->account_transaction_id);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('status', 'pending')->whereNull('account_transaction_id');
    }

    /**
     * Validate if user has sufficient balance for withdrawal
     * 
     * @return bool
     */
    public function validateBalance()
    {
        $currentBalance = $this->user->calculateAccountBalance();
        return $currentBalance >= $this->amount && $this->amount > 0;
    }

    /**
     * Approve: deducts the amount from the member's balance (one negative AccountTransaction).
     *
     * Safe under double clicks and two admins at once: the request row and the member row are locked,
     * the status is re-read inside the lock, and the balance is checked at the moment of approval.
     *
     * @param User|\App\Models\Administrator|int $admin
     * @return array{success: bool, message: string, transaction: ?AccountTransaction}
     */
    public function approve($admin, $note = null)
    {
        $adminId = is_object($admin) ? $admin->id : (int) $admin;

        try {
            return \DB::transaction(function () use ($adminId, $note) {
                $locked = self::lockForUpdate()->find($this->id);
                if (!$locked) {
                    return ['success' => false, 'message' => 'Withdraw request not found.', 'transaction' => null];
                }
                if ($locked->status !== 'pending' || $locked->account_transaction_id) {
                    return ['success' => false, 'message' => 'This request was already ' . $locked->status . '.', 'transaction' => null];
                }
                if ((float) $locked->amount <= 0) {
                    return ['success' => false, 'message' => 'Invalid withdrawal amount.', 'transaction' => null];
                }

                User::where('id', $locked->user_id)->lockForUpdate()->first();
                $user = User::find($locked->user_id);
                if (!$user) {
                    return ['success' => false, 'message' => 'Member account not found.', 'transaction' => null];
                }
                $balance = (float) $user->calculateAccountBalance();
                if ($balance + 0.009 < (float) $locked->amount) {
                    return ['success' => false, 'message' => 'Insufficient balance: the member now has UGX ' . number_format($balance, 0) . '.', 'transaction' => null];
                }

                $transaction = AccountTransaction::create([
                    'user_id' => $locked->user_id,
                    'amount' => -abs((float) $locked->amount),
                    'transaction_date' => now(),
                    'description' => 'Withdrawal: ' . ($locked->description ?: 'Approved withdraw request #' . $locked->id),
                    'source' => 'withdrawal',
                    'created_by_id' => $adminId,
                ]);

                $locked->update([
                    'status' => 'approved',
                    'processed_by_id' => $adminId,
                    'processed_at' => now(),
                    'admin_note' => $note,
                    'account_transaction_id' => $transaction->id,
                ]);
                $this->setRawAttributes($locked->fresh()->getAttributes(), true);

                return ['success' => true, 'message' => 'Approved: UGX ' . number_format((float) $locked->amount, 0) . ' deducted from ' . $user->name . "'s balance.", 'transaction' => $transaction];
            });
        } catch (\Throwable $e) {
            \Log::error('Withdraw approval failed', ['id' => $this->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not approve this request. Please try again.', 'transaction' => null];
        }
    }

    /**
     * Reject: nothing is deducted; the reason is shown to the member.
     *
     * @return array{success: bool, message: string}
     */
    public function reject($admin, $reason)
    {
        $adminId = is_object($admin) ? $admin->id : (int) $admin;
        $reason = trim((string) $reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Please give a reason so the member knows why.'];
        }

        try {
            return \DB::transaction(function () use ($adminId, $reason) {
                $locked = self::lockForUpdate()->find($this->id);
                if (!$locked || $locked->status !== 'pending') {
                    return ['success' => false, 'message' => 'This request was already ' . ($locked->status ?? 'removed') . '.'];
                }
                $locked->update([
                    'status' => 'rejected',
                    'processed_by_id' => $adminId,
                    'processed_at' => now(),
                    'admin_note' => $reason,
                ]);
                $this->setRawAttributes($locked->fresh()->getAttributes(), true);
                return ['success' => true, 'message' => 'Request rejected. Nothing was deducted.'];
            });
        } catch (\Throwable $e) {
            \Log::error('Withdraw rejection failed', ['id' => $this->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reject this request. Please try again.'];
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion of processed requests
        static::deleting(function ($withdrawRequest) {
            if ($withdrawRequest->status !== 'pending') {
                throw new \Exception('Cannot delete processed withdraw requests.');
            }
            if ($withdrawRequest->account_transaction_id) {
                throw new \Exception('Cannot delete withdraw request with associated transaction.');
            }
        });
    }
}
