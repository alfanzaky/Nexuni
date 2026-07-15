<?php

namespace App\Http\Middleware;

use App\Domains\Partner\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class H2HAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $nonce = $request->header('X-Nonce');

        if (! $apiKey || ! $signature || ! $timestamp || ! $nonce) {
            return response()->json(['message' => 'Missing required security headers'], 401);
        }

        // 1. Timestamp Validation (max 5 minutes age)
        $requestTime = strtotime($timestamp);
        if (! $requestTime || (time() - $requestTime > 300)) {
            return response()->json(['message' => 'Request expired or invalid timestamp'], 401);
        }

        // 2. Nonce Validation (Replay Attack Prevention)
        $nonceKey = "h2h_nonce_{$apiKey}_{$nonce}";
        if (Cache::has($nonceKey)) {
            return response()->json(['message' => 'Replay attack detected'], 401);
        }

        // Cache the nonce for 5 minutes (matching the timestamp window)
        Cache::put($nonceKey, true, now()->addMinutes(5));

        // 3. API Key Validation
        $partner = Partner::where('api_key', $apiKey)->where('is_active', true)->first();

        if (! $partner) {
            return response()->json(['message' => 'Invalid or inactive API Key'], 401);
        }

        // 4. Rate Limiting
        $rateLimitKey = "h2h_rate_limit_{$partner->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, $partner->rate_limit)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json(['message' => 'Too Many Requests'], 429, [
                'Retry-After' => $seconds,
            ]);
        }
        RateLimiter::hit($rateLimitKey, 60); // Decay in 60 seconds (rate_limit requests per minute)

        // 5. HMAC Signature Validation
        // The signature should be: HMAC-SHA256(payload + timestamp + nonce, api_secret)
        $payload = $request->getContent(); // Raw JSON body
        $stringToSign = $payload.$timestamp.$nonce;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $partner->api_secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid Signature'], 401);
        }

        // Attach partner to request for controller use
        $request->attributes->set('partner', $partner);

        return $next($request);
    }
}
