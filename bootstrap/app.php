<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware stack
        $middleware->alias([
            'correlation' => \App\Http\Middleware\CorrelationId::class,
            'json.envelope' => \App\Http\Middleware\JsonResponseEnvelope::class,
            'req.log' => \App\Http\Middleware\LogRequestResponse::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'ensure.admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $code = $e->getCode();
                $message = config('app.debug') ? $e->getMessage() : __('Server error');

                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'request_id' => $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id'),
                    ],
                    'errors' => [[
                        'code' => $code,
                        'message' => $message,
                    ]],
                ], $status);
            }
        });
    })->create();
