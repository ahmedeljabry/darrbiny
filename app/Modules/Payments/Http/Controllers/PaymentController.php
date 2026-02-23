<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Payment,UserRequest};
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Modules\Payments\Http\{Resources\PaymentResource};

class PaymentController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(private readonly PaymentService $service) {}

    /**
     * Store plan payment transaction (full payment after offer selection)
     */
    public function plan(Request $request)
    {
        $validated = $request->validate([
            'user_request_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', 'in:wallet,tap'],
            'status' => ['nullable', 'string', 'in:pending,succeeded,failed'],
        ]);

        $normalizedStatus = $validated['payment_method'] === 'wallet'
            ? Payment::STATUS_SUCCEEDED
            : ($validated['status'] ?? Payment::STATUS_PENDING);

        // This endpoint is exclusively for full plan subscription payments.
        $request->merge([
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => $normalizedStatus,
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);
        $payment = $this->service->payWithWallet(
            $req,
            $request->user(),
            $request
        );
        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
