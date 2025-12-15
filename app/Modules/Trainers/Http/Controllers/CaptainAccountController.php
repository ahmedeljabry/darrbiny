<?php

declare(strict_types=1);

namespace App\Modules\Trainers\Http\Controllers;

use App\Modules\Trainers\Http\Requests\CaptainAccountDetailsRequest;
use App\Modules\Trainers\Http\Resources\CaptainAccountResource;
use App\Modules\Trainers\Services\CaptainAccountService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class /user/subscriptionsCaptainAccountController extends BaseController
{
    public function __construct(private readonly CaptainAccountService $service) {}

    public function show(Request $request)
    {
        $profile = $this->service->getDetails($request->id ?: $request->user());
        return response()->json(['data' => new CaptainAccountResource($profile)]);
    }

    public function store(CaptainAccountDetailsRequest $request)
    {
        $profile = $this->service->upsert($request->user(), $request->validated());

        return response()->json([
            'message' => 'تم حفظ بيانات الحساب بنجاح',
            'data' => new CaptainAccountResource($profile),
        ]);
    }
}
