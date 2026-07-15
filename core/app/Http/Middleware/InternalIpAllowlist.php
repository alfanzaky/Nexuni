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
