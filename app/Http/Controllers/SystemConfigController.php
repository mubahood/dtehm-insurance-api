<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * Get system configuration for mobile app
     * Public endpoint - no authentication required
     */
    public function getConfig()
    {
        try {
            $config = SystemConfiguration::getInstance();

            return response()->json([
                'code' => 1,
                'message' => 'Configuration retrieved successfully',
                'data' => [
                    // Company Information
                    'company' => [
                        'name' => $config->company_name,
                        'email' => $config->company_email,
                        'phone' => $config->company_phone,
                        'address' => $config->company_address,
                        'website' => $config->company_website,
                        'logo' => $config->company_logo ? url($config->company_logo) : null,
                        'details' => $config->company_details,
                    ],

                    // Membership Fees (Dynamic!)
                    'membership' => [
                        'dtehm_fee' => (int)$config->dtehm_membership_fee,
                        'dip_fee' => (int)$config->dip_membership_fee,
                        'both_fee' => (int)$config->dtehm_membership_fee + (int)$config->dip_membership_fee,
                        'currency' => $config->currency ?? 'UGX',
                        'referral_bonus_percentage' => (float)$config->referral_bonus_percentage,
                    ],

                    // Insurance
                    'insurance' => [
                        'price' => (int)$config->insurance_price,
                        'start_date' => $config->insurance_start_date,
                        'currency' => $config->currency ?? 'UGX',
                    ],

                    // Investment
                    'investment' => [
                        'minimum_amount' => (int)$config->minimum_investment_amount,
                        'share_price' => (int)$config->share_price,
                        'currency' => $config->currency ?? 'UGX',
                    ],

                    // App Settings
                    'app' => [
                        'version' => $config->app_version ?? '1.0.0',
                        'force_update' => (bool)$config->force_update,
                        'maintenance_mode' => (bool)$config->maintenance_mode,
                        'maintenance_message' => $config->maintenance_message,
                    ],

                    // Payment Gateway
                    'payment' => [
                        'gateway' => $config->payment_gateway ?? 'pesapal',
                        'callback_url' => $config->payment_callback_url,
                    ],

                    // Contact Information
                    'contact' => [
                        'phone' => $config->contact_phone,
                        'email' => $config->contact_email,
                        'address' => $config->contact_address,
                    ],

                    // Social Media
                    'social' => [
                        'facebook' => $config->social_facebook,
                        'twitter' => $config->social_twitter,
                        'instagram' => $config->social_instagram,
                        'linkedin' => $config->social_linkedin,
                    ],

                    // Legal
                    'legal' => [
                        'terms_and_conditions' => $config->terms_and_conditions,
                        'privacy_policy' => $config->privacy_policy,
                        'about_us' => $config->about_us,
                    ],

                    // System Currency
                    'currency' => $config->currency ?? 'UGX',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve configuration: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get specific configuration value
     */
    public function getValue(Request $request, $key)
    {
        try {
            $value = SystemConfiguration::get($key);

            if ($value === null) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Configuration key not found',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Configuration value retrieved',
                'data' => [
                    'key' => $key,
                    'value' => $value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Check if app maintenance mode is active
     */
    public function checkMaintenance()
    {
        try {
            $config = SystemConfiguration::getInstance();

            return response()->json([
                'code' => 1,
                'message' => 'Maintenance status retrieved',
                'data' => [
                    'maintenance_mode' => (bool)$config->maintenance_mode,
                    'message' => $config->maintenance_message,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Check app version and force update status
     */
    public function checkAppVersion(Request $request)
    {
        try {
            $config = SystemConfiguration::getInstance();
            $currentVersion = $request->input('version', '0.0.0');

            $isUpdateAvailable = version_compare($config->app_version, $currentVersion, '>');

            return response()->json([
                'code' => 1,
                'message' => 'Version check completed',
                'data' => [
                    'current_version' => $config->app_version,
                    'user_version' => $currentVersion,
                    'update_available' => $isUpdateAvailable,
                    'force_update' => (bool)$config->force_update,
                    'message' => $config->force_update 
                        ? 'Please update to the latest version to continue' 
                        : ($isUpdateAvailable ? 'New version available' : 'You are up to date'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get membership fees (most commonly used endpoint)
     */
    public function getMembershipFees()
    {
        try {
            $config = SystemConfiguration::getInstance();

            return response()->json([
                'code' => 1,
                'message' => 'Membership fees retrieved',
                'data' => [
                    'dtehm_membership_fee' => (int)$config->dtehm_membership_fee,
                    'dip_membership_fee' => (int)$config->dip_membership_fee,
                    'both_membership_fee' => (int)$config->dtehm_membership_fee + (int)$config->dip_membership_fee,
                    'currency' => $config->currency ?? 'UGX',
                    'dtehm_description' => 'Full network marketing privileges',
                    'dip_description' => 'Basic membership',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
