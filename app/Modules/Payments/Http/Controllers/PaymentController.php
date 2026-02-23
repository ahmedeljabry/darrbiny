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
     * Store plan payment transaction (full or partial)
     */
    public function plan(Request $request)
    {
        $validated = $request->validate([
            'user_request_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', 'in:wallet,tap'],
            'type' => ['sometimes', 'string', 'in:plan_full,plan_partial'],
            'status' => ['nullable', 'string', 'in:pending,succeeded,failed'],
        ]);

        $paymentType = $validated['type'] ?? Payment::TYPE_PLAN_FULL;
        $normalizedStatus = $validated['payment_method'] === 'wallet'
            ? Payment::STATUS_SUCCEEDED
            : ($validated['status'] ?? Payment::STATUS_PENDING);

        $request->merge([
            'type' => $paymentType,
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
