<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\Payout;
use App\Models\TrainingDay;
use App\Models\UserRequest;
use App\Modules\Requests\Http\Requests\StoreUserRequest;
use App\Modules\Requests\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UserRequestController extends BaseController
{
    public function __construct(private readonly RequestService $service) {}

    public function index(Request $request)
    {
        $mine = $request->boolean('mine');
        $q = UserRequest::query();
        if ($mine) $q->where('user_id', $request->user()->id);
        return response()->json(['data' => $q->latest()->paginate(20)]);
    }

    public function show(string $id)
    {
        $req = UserRequest::findOrFail($id);
        $this->authorize('view', $req);
        return response()->json(['data' => $req]);
    }

    public function store(StoreUserRequest $request)
    {
        $req = $this->service->create($request->validated(), $request->user()->id);
        return response()->json(['data' => $req], 201);
    }

    public function complete(Request $request, string $id)
    {
        $req = UserRequest::findOrFail($id);
        $this->authorize('complete', $req);
        // check hours
        $approvedHours = (int) TrainingDay::where('user_request_id', $req->id)
            ->where('status', TrainingDay::STATUS_APPROVED)
            ->sum('hours_done');
        abort_if($approvedHours < $req->plan->hours_count, 422, 'Not enough hours');
        $this->service->complete($req);
        // create payout pending review
        Payout::create([
            'trainer_id' => $request->user()->id, // assuming trainer is authenticated to complete
            'user_request_id' => $req->id,
            'amount_minor' => $req->total_paid_minor - $req->app_fee_reserved_minor,
            'currency' => $req->currency,
            'status' => Payout::STATUS_PENDING_REVIEW,
        ]);
        return response()->json(['data' => $req]);
    }
}

