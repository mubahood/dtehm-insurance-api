<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_pobox',
        'company_address',
        'company_website',
        'company_logo',
        'company_details',
        'insurance_start_date',
        'insurance_price',
        'dtehm_membership_fee',
        'dip_membership_fee',
        'currency',
        'minimum_investment_amount',
        'share_price',
        'referral_bonus_percentage',
        'app_version',
        'force_update',
        'maintenance_mode',
        'maintenance_message',
        'contact_phone',
        'contact_email',
        'contact_address',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'payment_gateway',
        'payment_callback_url',
        'terms_and_conditions',
        'privacy_policy',
        'about_us',
    ];

    protected $casts = [
        'insurance_start_date' => 'datetime',
        'force_update' => 'boolean',
        'maintenance_mode' => 'boolean',
        'dtehm_membership_fee' => 'integer',
        'dip_membership_fee' => 'integer',
        'insurance_price' => 'integer',
        'minimum_investment_amount' => 'integer',
        'share_price' => 'integer',
        'referral_bonus_percentage' => 'decimal:2',
    ];

    /**
     * Get the singleton instance of system configuration
     * Uses caching for performance
     */
    public static function getInstance()
    {
        return Cache::remember('system_config', 3600, function () {
            return self::firstOrCreate(
                ['id' => 1],
                [
                    'company_name' => 'DTEHM Health Insurance',
                    'dtehm_membership_fee' => 76000,
                    'dip_membership_fee' => 20000,
                    'currency' => 'UGX',
                    'app_version' => '1.0.0',
                    'force_update' => false,
                    'maintenance_mode' => false,
                ]
            );
        });
    }

    /**
     * Clear the configuration cache
     */
    public static function clearCache()
    {
        Cache::forget('system_config');
    }

    /**
     * Get specific configuration value
     */
    public static function get($key, $default = null)
    {
        $config = self::getInstance();
        return $config->$key ?? $default;
    }

    /**
     * Set specific configuration value
     */
    public static function set($key, $value)
    {
        $config = self::getInstance();
        $config->$key = $value;
        $config->save();
        self::clearCache();
        return $config;
    }

    /**
     * Boot method to clear cache on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
