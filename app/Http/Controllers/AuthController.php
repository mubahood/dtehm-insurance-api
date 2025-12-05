<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        // If already authenticated, redirect to admin dashboard
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:4',
        ], [
            'username.required' => 'Username, email, or phone number is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 4 characters',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('username', 'remember'));
        }

        $identifier = trim($request->input('username'));
        $password = $request->input('password');
        $remember = $request->filled('remember');

        Log::info('Web login attempt', ['identifier' => $identifier]);

        // Try to find user by multiple identifiers with priority order
        // Priority: DTEHM ID > DIP ID > Phone > Username > Email
        $user = null;
        
        // 1. Check DTEHM Member ID
        if (empty($user)) {
            $user = Administrator::where('dtehm_member_id', $identifier)->first();
            if ($user) {
                Log::info('User found by DTEHM ID', ['user_id' => $user->id]);
            }
        }
        
        // 2. Check DIP ID (business_name)
        if (empty($user)) {
            $user = Administrator::where('business_name', $identifier)->first();
            if ($user) {
                Log::info('User found by DIP ID', ['user_id' => $user->id]);
            }
        }
        
        // 3. Check phone number (exact match)
        if (empty($user)) {
            $user = Administrator::where('phone_number', $identifier)->first();
            if ($user) {
                Log::info('User found by phone (exact)', ['user_id' => $user->id]);
            }
        }
        
        // 4. Check phone number with country code normalization
        if (empty($user)) {
            $phone_number = \App\Models\Utils::prepare_phone_number($identifier);
            if (\App\Models\Utils::phone_number_is_valid($phone_number)) {
                $user = Administrator::where('phone_number', $phone_number)->first();
                if ($user) {
                    Log::info('User found by phone (normalized)', ['user_id' => $user->id, 'normalized' => $phone_number]);
                }
            }
        }
        
        // 5. Check username
        if (empty($user)) {
            $user = Administrator::where('username', $identifier)->first();
            if ($user) {
                Log::info('User found by username', ['user_id' => $user->id]);
            }
        }
        
        // 6. Check email
        if (empty($user)) {
            $user = Administrator::where('email', $identifier)->first();
            if ($user) {
                Log::info('User found by email', ['user_id' => $user->id]);
            }
        }

        if (!$user) {
            Log::warning('Web login failed - user not found', ['identifier' => $identifier]);
            return back()
                ->withErrors(['username' => 'Account not found. Please check your DTEHM ID, DIP ID, phone number, username, or email and try again.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['password' => 'Incorrect password. Please try again.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Check if user is explicitly inactive or suspended
        // Allow login if status is: null, empty, "Active", "1", or any other value except "Inactive", "Suspended", "Banned", "Deleted"
        $blockedStatuses = ['Inactive', 'Suspended', 'Banned', 'Deleted', 'Disabled'];
        if (isset($user->status) && in_array($user->status, $blockedStatuses)) {
            return back()
                ->withErrors(['username' => 'Your account is inactive. Please contact the administrator.'])
                ->withInput($request->only('username', 'remember'));
        }

        // Attempt to log the user in
        Auth::guard('admin')->login($user, $remember);

        // Update last login time
        $user->last_seen = now();
        $user->save();

        // Regenerate session
        $request->session()->regenerate();

        // Redirect to intended page or admin dashboard
        return redirect()->intended(admin_base_path('/'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
