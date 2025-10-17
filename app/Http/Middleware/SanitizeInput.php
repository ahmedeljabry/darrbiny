<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Mews\Purifier\Purifier;
use Illuminate\Http\Request;

class SanitizeInput
{
    public function __construct(private readonly Purifier $purifier) {}

    public function handle(Request $request, Closure $next)
    {
        $payload = $request->all();
        array_walk_recursive($payload, function (&$value, $key) {
            if (is_string($value) && str_contains($key, 'html')) {
                $value = purifier($value, 'default');
            }
        });
        $request->merge($payload);
        return $next($request);
    }
}

