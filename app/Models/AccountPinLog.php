<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountPinLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['created_at' => 'datetime', 'sms_sent' => 'boolean'];

    public const LABELS = [
        'created' => 'Member created a PIN',
        'changed' => 'Member changed the PIN',
        'admin_set' => 'Office set a new PIN',
        'admin_generated' => 'Office generated a new PIN',
        'admin_unlocked' => 'Office unlocked the PIN',
        'admin_removed' => 'Office removed the PIN',
        'removed' => 'PIN removed',
        'locked' => 'Locked after 5 wrong tries',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}
