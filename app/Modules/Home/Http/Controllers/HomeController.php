<?php

namespace App\Modules\Home\Http\Controllers;

use App\Modules\Home\Services\HomeService;
use Illuminate\Http\{JsonResponse,Request};

final class HomeController
{
    public function __construct(protected HomeService $homeService) {}
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->homeService->getHomeData($request->city_id,(string)$request->input('q', '')));
    }
}
