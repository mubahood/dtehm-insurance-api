<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShareholderWithdrawal extends Model
{
    protected $table = 'shareholder_withdrawals';

    protected $fillable = [
        'amount',
        'description',
        'recipient',
        'withdrawal_date',
        'reference_number',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'withdrawal_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(\Encore\Admin\Auth\Database\Administrator::class, 'created_by_id');
    }

    /**
     * Total amount collected into the shareholders pool (1.5% of all sales).
     */
    public static function totalPool(): float
    {
        return (float) DB::table('ordered_items')->sum('shareholder_commission');
    }

    /**
     * Total amount withdrawn from the pool.
     */
    public static function totalWithdrawn(): float
    {
        return (float) static::sum('amount');
    }

    /**
     * Current available balance = pool - withdrawn.
     */
    public static function availableBalance(): float
    {
        return static::totalPool() - static::totalWithdrawn();
    }
}
