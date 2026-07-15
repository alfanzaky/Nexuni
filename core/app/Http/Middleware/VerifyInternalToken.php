<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('app.internal_token');
        if (empty($expectedToken)) {
            Log::error('INTERNAL_API_TOKEN is not configured in the environment.');

            return response()->json(['error' => 'Internal Server Error'], 500);
        }

        $token = $request->header('X-Internal-Token', '');
        if (! hash_equals($expectedToken, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
