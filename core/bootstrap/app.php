<?php

use App\Http\Middleware\H2HAuthMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        try {
            $proxies = config('app.trusted_proxies');
        } catch (Throwable $e) {
            $proxies = env('TRUSTED_PROXIES');
        }
        if ($proxies === '*') {
            $middleware->trustProxies(at: '*');
        } elseif ($proxies) {
            $middleware->trustProxies(at: explode(',', $proxies));
        }

        $middleware->alias([
            'h2h_auth' => H2HAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
