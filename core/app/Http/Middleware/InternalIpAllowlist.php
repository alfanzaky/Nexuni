<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InternalIpAllowlist
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Prevent IP spoofing via X-Forwarded-For if wildcard proxies are enabled
        if (env('TRUSTED_PROXIES') === '*') {
            Log::critical('InternalIpAllowlist is fundamentally bypassed because TRUSTED_PROXIES=* is configured. Please specify exact proxy IPs.');
            return response()->json(['error' => 'Insecure Server Configuration'], 500);
        }

        $allowedIps = config('app.internal_ips', []);

        if (! in_array($request->ip(), $allowedIps)) {
            Log::warning('Unauthorized IP attempt to internal API', [
                'ip' => $request->ip(),
                'url' => $request->url(),
            ]);

            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
