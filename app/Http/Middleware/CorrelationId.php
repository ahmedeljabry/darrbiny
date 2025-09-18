<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrelationId
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->headers->get('X-Request-Id') ?: Str::uuid()->toString();
        $request->headers->set('X-Request-Id', $id);
        $request->attributes->set('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);
        return $response;
    }
}

