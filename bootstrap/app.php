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
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'ensure.admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
        
        // Force JSON for all API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'request_id' => $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id'),
                    ],
                    'errors' => [[
                        'code' => 401,
                        'message' => 'Unauthenticated',
                    ]],
                ], 401);
            }
        });
        
        // Handle all other exceptions for API routes
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $code = $e->getCode();
                $message = config('app.debug') || ($status >= 400 && $status < 500)
                    ? ($e->getMessage() ?: (\Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? __('Server error')))
                    : __('Server error');

                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'request_id' => $request->headers->get('X-Request-Id') ?? $request->attributes->get('request_id'),
                    ],
                    'errors' => [[
                        'code' => $code ?: $status,
                        'message' => $message,
                    ]],
                ], $status);
            }
        });
    })->create();
