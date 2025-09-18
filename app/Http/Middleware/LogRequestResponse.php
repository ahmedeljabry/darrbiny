<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestResponse
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $id = $request->attributes->get('request_id');
        Log::channel('stack')->info('api.request', [
            'id' => $id,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        Log::channel('stack')->info('api.response', [
            'id' => $id,
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ]);

        return $response;
    }
}

