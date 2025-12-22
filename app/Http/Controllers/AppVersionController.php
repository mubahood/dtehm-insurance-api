<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utils;

class AppVersionController extends Controller
{
    /**
     * Check if app version is up to date
     * GET /api/app-version/check
     */
    public function checkVersion(Request $request)
    {
        try {
            // Get current version from request
            $currentVersion = $request->input('version');
            $platform = $request->input('platform'); // 'android' or 'ios'

            // Define latest versions
            $latestVersions = [
                'android' => [
                    'version' => '1.0.0',
                    'build_number' => 1,
                    'force_update' => false,
                    'download_url' => 'https://play.google.com/store/apps/details?id=com.dtehm.insurance',
                ],
                'ios' => [
                    'version' => '1.0.0',
                    'build_number' => 1,
                    'force_update' => false,
                    'download_url' => 'https://apps.apple.com/app/dtehm-insurance/id123456789',
                ],
            ];

            // Get platform-specific version
            $latestVersion = $latestVersions[$platform] ?? $latestVersions['android'];

            // Compare versions
            $needsUpdate = false;
            if ($currentVersion) {
                $needsUpdate = version_compare($currentVersion, $latestVersion['version'], '<');
            }

            return Utils::success([
                'needs_update' => $needsUpdate,
                'force_update' => $latestVersion['force_update'],
                'latest_version' => $latestVersion['version'],
                'current_version' => $currentVersion,
                'build_number' => $latestVersion['build_number'],
                'download_url' => $latestVersion['download_url'],
                'update_message' => $needsUpdate 
                    ? ($latestVersion['force_update'] 
                        ? 'A critical update is required. Please update to continue using the app.'
                        : 'A new version is available. Update now to get the latest features and improvements.')
                    : 'You are using the latest version.',
            ], 'Version check completed');
            
        } catch (\Exception $e) {
            \Log::error('App version check failed', [
                'error' => $e->getMessage(),
            ]);
            
            // On error, allow app to continue (don't block users)
            return Utils::success([
                'needs_update' => false,
                'force_update' => false,
                'latest_version' => $request->input('version', '1.0.0'),
                'current_version' => $request->input('version', '1.0.0'),
            ], 'Version check completed');
        }
    }
}
