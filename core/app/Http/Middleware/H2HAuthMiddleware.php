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
        $ipRateLimitKey = 'h2h_failed_auth_'.$request->ip();

        // 0. Brute-Force Protection
        if (RateLimiter::tooManyAttempts($ipRateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($ipRateLimitKey);

            return response()->json(['message' => 'Too Many Failed Attempts'], 429, [
                'Retry-After' => $seconds,
            ]);
        }

        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $nonce = $request->header('X-Nonce');

        if (! $apiKey || ! $signature || ! $timestamp || ! $nonce) {
            return $this->failAuth('Missing required security headers', $ipRateLimitKey);
        }

        // 1. Timestamp Validation (max 5 minutes age, no future timestamps)
        $requestTime = strtotime($timestamp);
        if (! $requestTime || abs(time() - $requestTime) > 300) {
            return $this->failAuth('Request expired or invalid timestamp', $ipRateLimitKey);
        }

        // 2. Fast Nonce Validation (Replay Attack Prevention)
        $nonceKey = "h2h_nonce_{$apiKey}_{$nonce}";
        if (Cache::has($nonceKey)) {
            return $this->failAuth('Replay attack detected', $ipRateLimitKey);
        }

        // 3. API Key Validation
        $partner = Partner::where('api_key', $apiKey)->where('is_active', true)->first();

        if (! $partner) {
            return $this->failAuth('Invalid or inactive API Key', $ipRateLimitKey);
        }

        // 4. Rate Limiting Check (Legitimate Partner Quota)
        $rateLimitKey = "h2h_rate_limit_{$partner->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, $partner->rate_limit)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json(['message' => 'Too Many Requests'], 429, [
                'Retry-After' => $seconds,
            ]);
        }

        // 5. HMAC Signature Validation
        // The signature should be: HMAC-SHA256(payload + timestamp + nonce, api_secret)
        // NOTE: $partner->api_secret relies on the 'encrypted' cast in the Partner model 
        // to automatically decrypt the key into plaintext. If the cast is removed, validation will fail.
        $payload = $request->getContent(); // Raw JSON body
        $stringToSign = $payload.$timestamp.$nonce;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $partner->api_secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return $this->failAuth('Invalid Signature', $ipRateLimitKey);
        }

        // 6. Final Atomic Nonce Registration
        // This is done AFTER authentication to prevent unauthenticated attackers from exhausting nonces
        // on behalf of legitimate partners (Denial of Service).
        if (! Cache::add($nonceKey, true, now()->addMinutes(5))) {
            return $this->failAuth('Replay attack detected (concurrent)', $ipRateLimitKey);
        }

        // 7. Record Rate Limit Hit
        // Only increment the rate limiter for fully authenticated requests to prevent
        // unauthenticated attackers from exhausting the partner's quota.
        RateLimiter::hit($rateLimitKey, 60); // Decay in 60 seconds (rate_limit requests per minute)

        // Clear the failed attempts counter upon successful authentication
        RateLimiter::clear($ipRateLimitKey);

        // Attach partner to request for controller use
        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    /**
     * Handle an authentication failure by recording it and returning a 401 response.
     */
    private function failAuth(string $message, string $ipRateLimitKey): Response
    {
        RateLimiter::hit($ipRateLimitKey, 60); // Decay in 60 seconds

        return response()->json(['message' => $message], 401);
    }
}
