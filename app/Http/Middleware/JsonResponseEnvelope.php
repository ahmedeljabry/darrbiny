<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class JsonResponseEnvelope
{
    public function handle(Request $request, Closure $next)
    {
        /** @var BaseResponse $response */
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $original = $response->getData(true);
            $status = $response->getStatusCode();
            $success = $status >= 200 && $status < 300;
            $enveloped = [
                'success' => $success,
                'data' => $original['data'] ?? ($success ? $original : null),
                'meta' => array_merge([
                    'request_id' => $request->attributes->get('request_id'),
                ], $original['meta'] ?? []),
                'errors' => $original['errors'] ?? ($success ? [] : [[
                    'code' => $status,
                    'message' => $original['message'] ?? BaseResponse::$statusTexts[$status] ?? 'Error',
                ]]),
            ];
            $response->setData($enveloped);
        }

        return $response;
    }
}

