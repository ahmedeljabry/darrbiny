<?php

declare(strict_types=1);

namespace App\Modules\Training\Http\Controllers;

use App\Models\TrainingDay;
use App\Models\UserRequest;
use App\Modules\Training\Http\Requests\StoreTrainingDayRequest;
use App\Modules\Training\Services\TrainingDayService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TrainingDayController extends BaseController
{
    public function __construct(private readonly TrainingDayService $service) {}

    public function index(Request $request)
    {
        $q = TrainingDay::query();
        if ($rid = $request->query('user_request_id')) $q->where('user_request_id', $rid);
        return response()->json(['data' => $q->latest('date')->paginate(20)]);
    }

    public function store(StoreTrainingDayRequest $request)
    {
        $req = UserRequest::findOrFail($request->string('user_request_id'));
        $this->authorize('logDay', $req);
        $day = $this->service->submit($req, $request->user()->id, $request->validated());
        return response()->json(['data' => $day], 201);
    }

    public function approve(Request $request, string $id)
    {
        $day = TrainingDay::findOrFail($id);
        $req = UserRequest::findOrFail($day->user_request_id);
        $this->authorize('approveDay', $req);
        $this->service->approve($day, $req);
        return response()->json(['data' => $day->fresh()]);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate(['reason' => ['required','string','max:500']]);
        $day = TrainingDay::findOrFail($id);
        $req = UserRequest::findOrFail($day->user_request_id);
        $this->authorize('approveDay', $req);
        $this->service->reject($day, (string) $request->string('reason'));
        return response()->json(['data' => $day->fresh()]);
    }
}

