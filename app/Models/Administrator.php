<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator as BaseAdministrator;

/**
 * Extended Administrator Model with SMS functionality
 */
class Administrator extends BaseAdministrator
{
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
            
            // Determine which identifier to send based on priority
            // PRIORITY: 1) DTEHM ID, 2) DIP ID, 3) Phone Number
            $identifierLabel = null;
            $identifierValue = null;

            // Priority 1: DTEHM member with DTEHM ID
            if (!empty($this->dtehm_member_id)) {
                $identifierLabel = 'DTEHM ID';
                $identifierValue = $this->dtehm_member_id;
            }
            // Priority 2: DIP member with DIP ID (only if no DTEHM ID)
            elseif (!empty($this->business_name)) {
                $identifierLabel = 'DIP ID';
                $identifierValue = $this->business_name;
            }
            // Priority 3: Phone number as fallback
            elseif (!empty($this->phone_number)) {
                $identifierLabel = 'Phone';
                $identifierValue = $this->phone_number;
            }

            // If no identifier found, return error
            if (empty($identifierLabel) || empty($identifierValue)) {
                \Log::warning('User has no valid identifier - cannot send credentials', [
                    'user_id' => $this->id,
                    'dtehm_member_id' => $this->dtehm_member_id,
                    'business_name' => $this->business_name,
                    'phone_number' => $this->phone_number,
                ]);
                $response->message = 'User has no valid identifier (DTEHM ID, DIP ID, or Phone). Cannot send credentials.';
                return $response;
            }

            // Send only ONE message with the selected identifier
            $message = "{$identifierLabel}: {$identifierValue}\n"
                     . "Password: {$newPassword}\n"
                     . "App: https://shorturl.at/U2u7q";

            \Log::info('Preparing credentials SMS', [
                'user_id' => $this->id ?? null,
                'identifier_label' => $identifierLabel,
                'identifier_value' => $identifierValue
            ]);

            // Send SMS (only once)
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

    /**
     * Check if user has paid and has a valid membership
     * 
     * @return bool
     */
    public function hasValidMembership()
    {
        // Check if user type is Admin - admins always have access
        if ($this->isAdmin()) {
            \Log::info("User {$this->id} is admin - granting access");
            return true;
        }

        $hasValidPayment = false;

        // Check for DTEHM membership payment - MUST have both flag AND payment
        if ($this->is_dtehm_member === 'Yes') {
            $dtehmPaid = \App\Models\DtehmMembership::where('user_id', $this->id)
                ->where('status', 'CONFIRMED')
                ->exists();
            
            \Log::info("User {$this->id} - DTEHM check: flag='Yes', payment exists=" . ($dtehmPaid ? 'true' : 'false'));
            
            if ($dtehmPaid) {
                $hasValidPayment = true;
            }
        }

        // Check for DIP membership payment - MUST have both flag AND payment
        if ($this->is_dip_member === 'Yes') {
            $dipPaid = \App\Models\MembershipPayment::where('user_id', $this->id)
                ->where('status', 'CONFIRMED')
                ->exists();
            
            \Log::info("User {$this->id} - DIP check: flag='Yes', payment exists=" . ($dipPaid ? 'true' : 'false'));
            
            if ($dipPaid) {
                $hasValidPayment = true;
            }
        }

        \Log::info("User {$this->id} - Final result: hasValidPayment=" . ($hasValidPayment ? 'true' : 'false'));

        // User must have at least ONE confirmed payment to access
        // Just having the flag is not enough
        return $hasValidPayment;
    }

    /**
     * Check if user is an admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return in_array(strtolower($this->user_type ?? ''), ['admin', 'administrator']);
    }
}
