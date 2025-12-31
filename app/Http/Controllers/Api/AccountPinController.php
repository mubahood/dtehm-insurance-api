<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountPin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountPinController extends Controller
{
    /**
     * Check if user has a PIN
     */
    public function hasPin(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            $accountPin = AccountPin::where('user_id', $user->id)->first();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => [
                    'has_pin' => $accountPin !== null,
                    'last_changed' => $accountPin ? $accountPin->last_changed_at : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to check PIN status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Create a new PIN
     */
    public function createPin(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Check if user already has a PIN
            $existingPin = AccountPin::where('user_id', $user->id)->first();
            if ($existingPin) {
                return response()->json([
                    'code' => 400,
                    'message' => 'You already have a PIN. Use change PIN instead.',
                    'data' => null
                ], 400);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'pin' => 'required|digits:4',
                'pin_confirmation' => 'required|same:pin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            // Create new PIN
            $accountPin = new AccountPin();
            $accountPin->user_id = $user->id;
            $accountPin->setPin($request->pin);

            return response()->json([
                'code' => 1,
                'message' => 'PIN created successfully',
                'data' => [
                    'created_at' => $accountPin->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to create PIN: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Verify PIN
     */
    public function verifyPin(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'pin' => 'required|digits:4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            // Get user's PIN
            $accountPin = AccountPin::where('user_id', $user->id)->first();
            if (!$accountPin) {
                return response()->json([
                    'code' => 404,
                    'message' => 'No PIN found. Please create a PIN first.',
                    'data' => null
                ], 404);
            }

            // Verify PIN
            $result = $accountPin->verifyPin($request->pin);

            if ($result['success']) {
                return response()->json([
                    'code' => 1,
                    'message' => $result['message'],
                    'data' => [
                        'verified' => true
                    ]
                ], 200);
            } else {
                return response()->json([
                    'code' => 400,
                    'message' => $result['message'],
                    'data' => [
                        'verified' => false,
                        'attempts_remaining' => $result['attempts_remaining'] ?? 0,
                        'locked_until' => $result['locked_until'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to verify PIN: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Change PIN
     */
    public function changePin(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'old_pin' => 'required|digits:4',
                'new_pin' => 'required|digits:4|different:old_pin',
                'new_pin_confirmation' => 'required|same:new_pin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            // Get user's PIN
            $accountPin = AccountPin::where('user_id', $user->id)->first();
            if (!$accountPin) {
                return response()->json([
                    'code' => 404,
                    'message' => 'No PIN found. Please create a PIN first.',
                    'data' => null
                ], 404);
            }

            // Verify old PIN first
            $verification = $accountPin->verifyPin($request->old_pin);
            if (!$verification['success']) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Old PIN is incorrect. ' . $verification['message'],
                    'data' => [
                        'attempts_remaining' => $verification['attempts_remaining'] ?? 0,
                    ]
                ], 400);
            }

            // Update to new PIN
            $accountPin->setPin($request->new_pin);

            return response()->json([
                'code' => 1,
                'message' => 'PIN changed successfully',
                'data' => [
                    'changed_at' => $accountPin->last_changed_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to change PIN: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get lock status
     */
    public function getLockStatus(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            $accountPin = AccountPin::where('user_id', $user->id)->first();
            if (!$accountPin) {
                return response()->json([
                    'code' => 404,
                    'message' => 'No PIN found',
                    'data' => null
                ], 404);
            }

            $status = $accountPin->getLockStatus();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $status
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to get lock status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Request PIN reset with SMS verification
     */
    public function requestPinReset(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Generate a 6-digit OTP
            $otp = rand(100000, 999999);

            // Store OTP in cache (for 10 minutes)
            cache()->put('pin_reset_otp_' . $user->id, $otp, now()->addMinutes(10));

            // Send SMS with OTP
            $message = "Your DTEHM PIN reset code is: {$otp}. Valid for 10 minutes.";
            
            try {
                \App\Models\Utils::send_sms($user->phone_number, $message);
            } catch (\Exception $e) {
                \Log::error('Failed to send PIN reset SMS: ' . $e->getMessage());
            }

            return response()->json([
                'code' => 1,
                'message' => 'Reset code sent to your phone number',
                'data' => [
                    'phone_number' => substr($user->phone_number, 0, 4) . '****' . substr($user->phone_number, -2),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to request PIN reset: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Verify OTP and reset PIN
     */
    public function resetPinWithOtp(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'otp' => 'required|digits:6',
                'new_pin' => 'required|digits:4',
                'new_pin_confirmation' => 'required|same:new_pin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            // Verify OTP
            $cachedOtp = cache()->get('pin_reset_otp_' . $user->id);
            if (!$cachedOtp || $cachedOtp != $request->otp) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid or expired OTP',
                    'data' => null
                ], 400);
            }

            // Get or create user's PIN
            $accountPin = AccountPin::where('user_id', $user->id)->first();
            if (!$accountPin) {
                $accountPin = new AccountPin();
                $accountPin->user_id = $user->id;
            }

            // Set new PIN
            $accountPin->setPin($request->new_pin);

            // Clear OTP from cache
            cache()->forget('pin_reset_otp_' . $user->id);

            return response()->json([
                'code' => 1,
                'message' => 'PIN reset successfully',
                'data' => [
                    'reset_at' => now(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Failed to reset PIN: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
