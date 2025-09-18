<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Models\UserRequest;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PaymentController extends BaseController
{
    public function __construct(private readonly PaymentService $service) {}

    public function reservation(Request $request)
    {
        $request->validate(['user_request_id' => ['required','uuid']]);
        $req = UserRequest::findOrFail($request->string('user_request_id'));
        $this->authorize('update', $req);
        $payment = $this->service->payReservation($req, $request->user()->id);
        return response()->json(['data' => $payment]);
    }

    public function plan(Request $request)
    {
        $request->validate(['user_request_id' => ['required','uuid']]);
        $req = UserRequest::findOrFail($request->string('user_request_id'));
        $this->authorize('update', $req);
        $payment = $this->service->payPlan($req, $request->user()->id);
        return response()->json(['data' => $payment]);
    }

    public function webhook(Request $request, string $provider)
    {
        // Verify webhook signatures via provider
        $valid = app(\App\Modules\Payments\Services\PaymentProvider::class)
            ->validateWebhook($request->all(), $request->headers->all());
        abort_unless($valid, 400, 'Invalid signature');
        // TODO: find payment by provider_ref or idempotency key and update status
        return response()->json(['data' => ['ok' => true]]);
    }
}
