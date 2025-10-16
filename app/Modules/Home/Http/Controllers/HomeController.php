<?php

namespace App\Modules\Home\Http\Controllers;

use App\Modules\Home\Http\Requests\HomeIndexRequest;
use App\Services\HomeService;
use Illuminate\Http\JsonResponse;

class HomeController
{
    public function __construct(protected HomeService $homeService) {}

    public function __invoke(HomeIndexRequest $request): JsonResponse
    {
        $data = $this->homeService->getHomeData(
            $request->includes(),
            (int)$request->input('limit_plans', 5),
            (int)$request->input('limit_trainers', 5),
            (int)$request->input('country_id', 0),
            (int)$request->input('city_id', 0)
        );

        return response()->json($data);
    }
}
