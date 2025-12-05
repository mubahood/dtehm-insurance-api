<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Utils;

class AuthController extends BaseAuthController
{
    /**
     * Handle a login request with multi-field authentication support.
     * 
     * Accepts login using:
     * 1. DTEHM Member ID (highest priority)
     * 2. DIP ID (business_name)
     * 3. Phone number (exact or normalized)
     * 4. Username
     * 5. Email (lowest priority)
     *
     * @param Request $request
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $credentials = $request->only([
            $this->username(), 
            'password'
        ]);

        $username = trim($credentials[$this->username()]);
        
        Log::info('Admin login attempt', ['username' => $username]);

        // Try to find user by multiple identifiers (same logic as API)
        // Priority: DTEHM ID > DIP ID > Phone > Username > Email
        $user = null;
        
        // 1. Check DTEHM Member ID
        if (empty($user)) {
            $user = User::where('dtehm_member_id', $username)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                Log::info('Admin found by DTEHM ID', ['user_id' => $user->id]);
            }
        }
        
        // 2. Check DIP ID (business_name)
        if (empty($user)) {
            $user = User::where('business_name', $username)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                Log::info('Admin found by DIP ID', ['user_id' => $user->id]);
            }
        }
        
        // 3. Check phone number (exact match)
        if (empty($user)) {
            $user = User::where('phone_number', $username)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                Log::info('Admin found by phone (exact)', ['user_id' => $user->id]);
            }
        }
        
        // 4. Check phone number with country code normalization
        if (empty($user)) {
            $phone_number = Utils::prepare_phone_number($username);
            if (Utils::phone_number_is_valid($phone_number)) {
                $user = User::where('phone_number', $phone_number)
                    ->where('user_type', 'Admin')
                    ->first();
                if ($user) {
                    Log::info('Admin found by phone (normalized)', ['user_id' => $user->id, 'normalized' => $phone_number]);
                }
            }
        }
        
        // 5. Check username
        if (empty($user)) {
            $user = User::where('username', $username)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                Log::info('Admin found by username', ['user_id' => $user->id]);
            }
        }
        
        // 6. Check email
        if (empty($user)) {
            $user = User::where('email', $username)
                ->where('user_type', 'Admin')
                ->first();
            if ($user) {
                Log::info('Admin found by email', ['user_id' => $user->id]);
            }
        }

        if ($user == null) {
            Log::warning('Admin login failed - user not found', ['username' => $username]);
            return back()->withInput()->withErrors([
                $this->username() => 'Account not found. Please check your DTEHM ID, DIP ID, phone number, username, or email and try again.'
            ]);
        }

        // Check if user is actually an admin
        if ($user->user_type !== 'Admin') {
            Log::warning('Admin login failed - not an admin', ['username' => $username, 'user_id' => $user->id]);
            return back()->withInput()->withErrors([
                $this->username() => 'Access denied. This account does not have admin privileges.'
            ]);
        }

        // Now attempt authentication with the found user's actual username/email
        // We'll use the id-based authentication or username-based
        $loginField = 'username';
        $loginValue = $user->username;
        
        // If username is empty, try email
        if (empty($loginValue)) {
            $loginField = 'email';
            $loginValue = $user->email;
        }

        $authenticated = $this->guard()->attempt([
            $loginField => $loginValue,
            'password' => $credentials['password'],
        ], $request->filled('remember'));

        if ($authenticated) {
            Log::info('Admin login successful', ['user_id' => $user->id]);
            return $this->sendLoginResponse($request);
        }

        Log::warning('Admin login failed - wrong password', ['username' => $username, 'user_id' => $user->id]);
        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * User logout.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect(config('admin.route.prefix'));
    }
}
