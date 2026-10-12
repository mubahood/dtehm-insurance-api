<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Config\PesapalConfig;

class VerifyPesapalWebhook
{
    /**
     * Handle an incoming request.
     * Verify that webhook requests are coming from Pesapal
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log all webhook attempts for security monitoring
        Log::info('Pesapal webhook attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'payload' => $request->all()
        ]);

        // Verify the request is coming from Pesapal
        if (!$this->isPesapalRequest($request)) {
            Log::warning('Pesapal webhook verification failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason' => 'IP/User-Agent verification failed'
            ]);

            return response()->json([
                'error' => 'Unauthorized webhook request'
            ], 403);
        }

        // Verify required parameters are present
        if (!$this->hasRequiredParameters($request)) {
            Log::warning('Pesapal webhook missing required parameters', [
                'provided_params' => array_keys($request->all())
            ]);

            return response()->json([
                'error' => 'Missing required parameters'
            ], 400);
        }

        return $next($request);
    }

    /**
     * Verify if request is coming from Pesapal
     */
    /**
     * A request header (User-Agent) or sandbox flag proves nothing, so neither is trusted.
     * The real protection is that the handler never uses the request body for the payment outcome:
     * it re-fetches the status from Pesapal over an authenticated server-to-server call.
     * Here we only reject malformed notifications.
     */
    private function isPesapalRequest(Request $request): bool
    {
        $tracking = (string) $request->get('OrderTrackingId', '');
        return $tracking !== '' && preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $tracking) === 1;
    }

    /**
     * Verify that required parameters are present
     */
    private function hasRequiredParameters(Request $request): bool
    {
        $requiredParams = ['OrderTrackingId'];

        foreach ($requiredParams as $param) {
            if (!$request->has($param) || empty($request->get($param))) {
                return false;
            }
        }

        return true;
    }
}
